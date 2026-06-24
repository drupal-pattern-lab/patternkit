<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel\Validation;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Hook\ValidityStampCronHooks;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;

/**
 * Kernel tests for ValidityStampCronHooks::cron() enqueue logic.
 *
 * Tests the phased query strategy, backlog guard, and steady-state behavior
 * using real DB state and the live Drupal queue service.
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampCronEnqueueKernelTest extends PatternkitKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'filter',
    'block',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('patternkit_block');
    $this->installEntitySchema('patternkit_pattern');
    $this->installSchema('patternkit', ['patternkit_block_validity']);
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['patternkit']);
  }

  /**
   * Builds a ValidityStampCronHooks instance from the container.
   */
  protected function createHooks(): ValidityStampCronHooks {
    return ValidityStampCronHooks::create($this->container);
  }

  /**
   * Returns the number of items in the validity scan queue.
   */
  protected function queueCount(): int {
    /** @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = $this->container->get('queue');
    return (int) $queueFactory->get(ValidityStampCronHooks::QUEUE_ID)->numberOfItems();
  }

  /**
   * Creates and saves a published patternkit_block.
   */
  protected function createBlock(string $patternId, string $langcode = 'en'): PatternkitBlock {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => $langcode,
      'pattern_id' => $patternId,
      'data' => ['value' => '{}'],
      'status' => TRUE,
    ]);
    $block->save();
    return $block;
  }

  /**
   * Creates a pattern entity via direct DB insert.
   */
  protected function createPattern(string $library, string $path): PatternInterface {
    $db = $this->container->get('database');
    $uuid = \Drupal::service('uuid')->generate();
    $schema = '{"type":"object"}';
    $hash = md5($schema);

    $entityId = $db->insert('pattern')
      ->fields([
        'uuid' => $uuid,
        'hash' => $hash,
        'name__value' => $this->randomMachineName(),
        'assets' => serialize([]),
        'category' => 'test',
        'description' => '',
        'library' => $library,
        'libraryPluginId' => 'json',
        'path' => $path,
        'schema' => $schema,
        'template' => '',
        'version' => '1.0',
      ])
      ->execute();

    $revisionId = $db->insert('pattern_revision')
      ->fields([
        'id' => $entityId,
        'hash' => $hash,
        'schema' => $schema,
        'template' => '',
        'version' => '1.0',
        'revision_default' => 1,
      ])
      ->execute();

    $db->update('pattern')
      ->fields(['revision' => $revisionId])
      ->condition('id', $entityId)
      ->execute();

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_pattern');
    $pattern = $storage->loadRevision($revisionId);
    assert($pattern instanceof PatternInterface);
    return $pattern;
  }

  /**
   * Unstamped block translations are enqueued on cron run.
   */
  public function testCronEnqueuesUnstampedBlocks(): void {
    $this->createBlock('@patternkit/atoms/test/a');
    $this->createBlock('@patternkit/atoms/test/b');

    $this->assertSame(0, $this->queueCount());

    $this->createHooks()->cron();

    $this->assertSame(2, $this->queueCount());
  }

  /**
   * Backlog guard skips enqueue when queue depth meets BACKLOG_GUARD_THRESHOLD.
   *
   * The threshold is intentionally higher than BATCH_LIMIT so a freshly-added
   * batch does not immediately suppress the following cron run.
   */
  public function testCronBacklogGuardSkipsEnqueueWhenQueueFull(): void {
    /** @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = $this->container->get('queue');
    $queue = $queueFactory->get(ValidityStampCronHooks::QUEUE_ID);

    // Pre-fill queue to BACKLOG_GUARD_THRESHOLD.
    for ($i = 0; $i < ValidityStampCronHooks::BACKLOG_GUARD_THRESHOLD; $i++) {
      $queue->createItem(['block_entity_id' => $i, 'langcode' => 'en']);
    }

    // Create a real unstamped block.
    $this->createBlock('@patternkit/atoms/test/c');

    $before = $this->queueCount();
    $this->createHooks()->cron();
    $after = $this->queueCount();

    $this->assertSame(
      $before,
      $after,
      'Backlog guard must prevent enqueue when queue depth meets or exceeds BACKLOG_GUARD_THRESHOLD.',
    );
  }

  /**
   * When all blocks are stamped, stale blocks are enqueued instead.
   */
  public function testCronEnqueuesStaleBlocksWhenUnstampedBacklogIsEmpty(): void {
    $pattern = $this->createPattern('patternkit', 'atoms/test/stale-cron');
    $block = $this->createBlock('@patternkit/atoms/test/stale-cron');
    $blockId = (int) $block->id();

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');

    // Write a stamp at the current revision — not stale yet.
    $writer->writeStamp($blockId, 'en', (int) $block->getRevisionId(), (int) $pattern->getRevisionId(), TRUE, FALSE, 'cron_scan');

    // Advance the pattern to a new revision.
    $db = $this->container->get('database');
    $newRevId = $db->insert('pattern_revision')
      ->fields([
        'id' => $pattern->id(),
        'hash' => md5('{}v2'),
        'schema' => '{"type":"object"}',
        'template' => '',
        'version' => '1.1',
        'revision_default' => 1,
      ])
      ->execute();
    $db->update('pattern')
      ->fields(['revision' => $newRevId])
      ->condition('id', $pattern->id())
      ->execute();

    // Now the stamp is stale. Unstamped backlog is empty (block is stamped).
    $this->assertSame(0, $this->queueCount());
    $this->createHooks()->cron();
    $this->assertSame(1, $this->queueCount());
  }

  /**
   * No-op when all blocks are stamped at the current pattern revision.
   */
  public function testCronIsNoOpWhenAllBlocksAreCurrentlyStamped(): void {
    $pattern = $this->createPattern('patternkit', 'atoms/test/steady');
    $block = $this->createBlock('@patternkit/atoms/test/steady');
    $blockId = (int) $block->id();

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $writer->writeStamp($blockId, 'en', (int) $block->getRevisionId(), (int) $pattern->getRevisionId(), TRUE, FALSE, 'cron_scan');

    $this->createHooks()->cron();

    $this->assertSame(0, $this->queueCount());
  }

  /**
   * Unpublished blocks are not enqueued.
   */
  public function testCronDoesNotEnqueueUnpublishedBlocks(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => 'en',
      'pattern_id' => '@patternkit/atoms/test/unpublished',
      'data' => ['value' => '{}'],
      'status' => FALSE,
    ]);
    $block->save();

    $this->createHooks()->cron();

    $this->assertSame(0, $this->queueCount());
  }

  /**
   * Blocks referencing unresolvable pattern IDs are skipped without enqueue.
   *
   * When every stamped block in the Phase 2 candidate window references a
   * pattern_id with no matching pattern entity (e.g. a deleted pattern), no
   * items are enqueued. The cursor must still advance so the next run examines
   * a different window.
   */
  public function testCronSkipsUnresolvablePatternRefs(): void {
    // Create a stamped block whose pattern_id has no pattern entity in the DB.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => 'en',
      'pattern_id' => '@patternkit/atoms/test/ghost-pattern',
      'data' => ['value' => '{}'],
      'status' => TRUE,
    ]);
    $block->save();
    $blockId = (int) $block->id();

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    // Stamp the block at a plausible revision ID so it enters Phase 2.
    $writer->writeStamp($blockId, 'en', (int) $block->getRevisionId(), 999, TRUE, FALSE, 'cron_scan');

    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');
    $this->assertSame(0, (int) $state->get(ValidityStampCronHooks::STALE_OFFSET_STATE_KEY, 0));

    $this->createHooks()->cron();

    // No items should be enqueued — the pattern entity does not exist.
    $this->assertSame(0, $this->queueCount());
    // Cursor must have advanced past the unresolvable candidate.
    $this->assertGreaterThan(0, (int) $state->get(ValidityStampCronHooks::STALE_OFFSET_STATE_KEY, 0));
  }

  /**
   * Phase 2 cursor advances by candidates_examined each cron run.
   *
   * When a window contains no stale blocks (all current), the cursor must
   * still advance so subsequent runs examine new candidates rather than
   * re-scanning the same window indefinitely.
   */
  public function testCronPhase2CursorAdvancesEachRun(): void {
    $pattern = $this->createPattern('patternkit', 'atoms/test/cursor-adv');
    $block = $this->createBlock('@patternkit/atoms/test/cursor-adv');
    $blockId = (int) $block->id();

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    // Stamp at current revision — block is fresh, not stale.
    $writer->writeStamp(
      $blockId,
      'en',
      (int) $block->getRevisionId(),
      (int) $pattern->getRevisionId(),
      TRUE,
      FALSE,
      'cron_scan',
    );

    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');
    $this->assertSame(0, (int) $state->get(ValidityStampCronHooks::STALE_OFFSET_STATE_KEY, 0));

    $this->createHooks()->cron();

    // The window had 1 candidate (the fresh block). Cursor must advance by 1.
    $this->assertSame(1, (int) $state->get(ValidityStampCronHooks::STALE_OFFSET_STATE_KEY, 0));
  }

  /**
   * Phase 2 cursor resets to 0 when the query returns no candidates.
   *
   * When the offset exceeds the table size, candidates_examined is 0 and
   * the cursor must reset so the next run starts a fresh cycle.
   */
  public function testCronPhase2CursorResetsWhenTableEndReached(): void {
    $pattern = $this->createPattern('patternkit', 'atoms/test/cursor-reset');
    $block = $this->createBlock('@patternkit/atoms/test/cursor-reset');
    $blockId = (int) $block->id();

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $writer->writeStamp(
      $blockId,
      'en',
      (int) $block->getRevisionId(),
      (int) $pattern->getRevisionId(),
      TRUE,
      FALSE,
      'cron_scan',
    );

    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');
    // Place the cursor past the end of the table (only 1 row exists).
    $state->set(ValidityStampCronHooks::STALE_OFFSET_STATE_KEY, 100);

    $this->createHooks()->cron();

    // No candidates were returned; cursor must reset to 0.
    $this->assertSame(0, (int) $state->get(ValidityStampCronHooks::STALE_OFFSET_STATE_KEY, 0));
    $this->assertSame(0, $this->queueCount());
  }

  /**
   * Phase 1 activity resets the Phase 2 cursor.
   *
   * When unstamped blocks are found (Phase 1), the Phase 2 cursor must be
   * reset to 0 so the next Phase 2 pass starts from the beginning of the
   * table. The stamped population may have changed significantly while Phase 1
   * was draining.
   */
  public function testCronPhase1ActivityResetsCursor(): void {
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');
    // Set a non-zero cursor to verify Phase 1 resets it.
    $state->set(ValidityStampCronHooks::STALE_OFFSET_STATE_KEY, 42);

    // Create an unstamped block to trigger Phase 1.
    $this->createBlock('@patternkit/atoms/test/phase1-reset');

    $this->createHooks()->cron();

    // Phase 1 ran; cursor must be reset to 0.
    $this->assertSame(0, (int) $state->get(ValidityStampCronHooks::STALE_OFFSET_STATE_KEY, 0));
    // The unstamped block must have been enqueued.
    $this->assertSame(1, $this->queueCount());
  }

}
