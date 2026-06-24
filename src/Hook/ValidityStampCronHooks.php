<?php

declare(strict_types=1);

namespace Drupal\patternkit\Hook;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\patternkit\Plugin\QueueWorker\ValidityStampQueueWorker;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements hook_cron to enqueue patternkit_block translations for validation.
 *
 * Uses a phased query strategy:
 * - Phase 1: Enqueue unstamped block translations (backlog guard applied).
 * - Phase 2: Only when the unstamped backlog is empty, enqueue stale-stamp
 *   translations (blocks whose stamp pattern_revision_id is behind the
 *   pattern's current default revision).
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 */
final class ValidityStampCronHooks implements ContainerInjectionInterface {

  /**
   * Queue ID for the validity stamp scan queue.
   */
  public const QUEUE_ID = ValidityStampQueueWorker::QUEUE_ID;

  /**
   * Maximum items to enqueue per cron run.
   */
  public const BATCH_LIMIT = 50;

  /**
   * Queue depth at which the backlog guard suppresses enqueue.
   *
   * Set to twice BATCH_LIMIT so a freshly-enqueued batch does not immediately
   * trip the guard on the next cron run. Enqueue is skipped only when the
   * worker is falling behind by a full batch or more.
   */
  public const BACKLOG_GUARD_THRESHOLD = self::BATCH_LIMIT * 2;

  /**
   * State key persisting the Phase 2 candidate-window offset across cron runs.
   *
   * The offset is advanced by the number of candidates examined each run and
   * reset to 0 when the end of the table is reached (candidates_examined == 0)
   * or when Phase 1 becomes active again (unstamped blocks are found).
   */
  public const STALE_OFFSET_STATE_KEY = 'patternkit.validity_stamp_cron.stale_offset';

  /**
   * Constructs a new ValidityStampCronHooks.
   *
   * @param \Drupal\patternkit\Validation\ValidityStampWriterInterface $stampWriter
   *   The validity stamp writer (provides enqueue queries).
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service, used to persist the Phase 2 cursor offset.
   */
  public function __construct(
    protected ValidityStampWriterInterface $stampWriter,
    protected QueueFactory $queueFactory,
    protected LoggerInterface $logger,
    protected StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('patternkit.validity.stamp_writer'),
      $container->get('queue'),
      $container->get('logger.channel.patternkit_cron'),
      $container->get('state'),
    );
  }

  /**
   * Implements hook_cron().
   *
   * Enqueues patternkit_block translations for validity stamp (re)validation
   * using a two-phase priority strategy. Each cron run enqueues at most
   * BATCH_LIMIT items; the queue worker processes them over subsequent cron
   * runs within the time budget Drupal allocates to the worker.
   *
   * Over time this creates a continuous, self-regulating loop:
   * - New or never-validated blocks are stamped first (Phase 1).
   * - Once all blocks carry a stamp, pattern updates cause those stamps to go
   *   stale, and Phase 2 re-enqueues them for revalidation.
   * - After revalidation, stamps reflect the current pattern revision and the
   *   cycle returns to steady state until the next pattern update.
   *
   * The backlog guard prevents runaway queue growth: if unprocessed items
   * already fill the batch window, enqueueing more would only push work
   * further out without reducing latency. Cron skips enqueue until the
   * worker drains the existing backlog.
   */
  #[Hook('cron')]
  public function cron(): void {
    $queue = $this->queueFactory->get(static::QUEUE_ID);

    // Skip if the worker hasn't kept up — adding more items now would only
    // delay the existing backlog further.
    $queueSize = $queue->numberOfItems();
    if ($queueSize >= static::BACKLOG_GUARD_THRESHOLD) {
      $this->logger->debug(
        'ValidityStampCronHooks: backlog guard triggered; queue has @count items (threshold @threshold). Skipping enqueue.',
        ['@count' => $queueSize, '@threshold' => static::BACKLOG_GUARD_THRESHOLD],
      );
      return;
    }

    // Phase 1: blocks with no stamp at all take priority — they have never
    // been validated and their status is fully unknown.
    $unstamped = $this->stampWriter->getUnstampedBlockTranslations(static::BATCH_LIMIT);
    if (!empty($unstamped)) {
      foreach ($unstamped as $row) {
        $queue->createItem($row);
      }
      $this->logger->debug(
        'ValidityStampCronHooks: enqueued @count unstamped block translation(s).',
        ['@count' => count($unstamped)],
      );
      // Reset Phase 2 cursor so the next Phase 2 pass starts from the
      // beginning of the table (the unstamped backlog may have been cleared
      // by the time Phase 2 resumes, and the table population may have
      // changed significantly).
      $this->state->set(static::STALE_OFFSET_STATE_KEY, 0);
      return;
    }

    // Phase 2: all blocks are stamped — re-enqueue any whose stamp pre-dates
    // the pattern's current default revision (i.e. the pattern has been
    // updated since the block was last validated).
    //
    // A cursor offset is persisted in state so each cron run advances a window
    // through the full stamped population. Without the cursor, a batch window
    // dominated by unresolvable rows would never advance past those rows and
    // genuinely stale blocks later in the table would never be found.
    $offset = (int) $this->state->get(static::STALE_OFFSET_STATE_KEY, 0);
    $staleResult = $this->stampWriter->getStaleStampBlockTranslations(static::BATCH_LIMIT, $offset);
    $stale = $staleResult['stale'];
    $candidatesExamined = $staleResult['candidates_examined'];
    $unresolvable = $staleResult['unresolvable'];
    if (!empty($unresolvable)) {
      $patternIds = array_unique(array_column($unresolvable, 'pattern_id'));
      $this->logger->warning(
        'ValidityStampCronHooks: @count block translation(s) reference pattern IDs with no matching pattern entity and cannot be revalidated: @ids',
        ['@count' => count($unresolvable), '@ids' => implode(', ', $patternIds)],
      );
    }
    if ($candidatesExamined === 0) {
      // The query returned no rows at this offset — we have passed the end of
      // the table. Reset the cursor so the next cron run starts a fresh cycle.
      $this->state->set(static::STALE_OFFSET_STATE_KEY, 0);
      $this->logger->debug('ValidityStampCronHooks: no-op — all blocks are currently stamped at the current pattern revision.');
      return;
    }

    // Advance the cursor by the number of candidates examined this run so the
    // next cron run picks up where this one left off.
    $this->state->set(static::STALE_OFFSET_STATE_KEY, $offset + $candidatesExamined);

    if (empty($stale)) {
      // All candidates in this window were fresh or unresolvable; nothing to
      // enqueue. The cursor has been advanced; the next run will examine the
      // following window.
      $this->logger->debug(
        'ValidityStampCronHooks: Phase 2 window at offset @offset had no stale blocks; cursor advanced.',
        ['@offset' => $offset],
      );
      return;
    }
    foreach ($stale as $row) {
      $queue->createItem($row);
    }
    $this->logger->debug(
      'ValidityStampCronHooks: enqueued @count stale-stamp block translation(s).',
      ['@count' => count($stale)],
    );
  }

}
