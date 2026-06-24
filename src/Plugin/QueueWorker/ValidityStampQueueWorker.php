<?php

declare(strict_types=1);

namespace Drupal\patternkit\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\Exception\SchemaReferenceException;
use Drupal\patternkit\Utility\PatternValidationHelper;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates one patternkit_block translation and writes a cron validity stamp.
 *
 * Queue items must contain:
 *   - block_entity_id (int): The patternkit_block entity ID.
 *   - langcode (string): The translation language code.
 *
 * Validates against the current default pattern revision. Stamps are written
 * with stamp_source = 'cron_scan'. Missing entities or patterns are logged
 * and skipped without throwing.
 */
#[QueueWorker(
  id: 'patternkit_validity_scan',
  title: new TranslatableMarkup('Patternkit validity stamp scan'),
  cron: ['time' => 60],
)]
final class ValidityStampQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The queue ID for the validity stamp scan queue.
   */
  public const QUEUE_ID = 'patternkit_validity_scan';

  /**
   * Constructs a new ValidityStampQueueWorker.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\patternkit\Validation\ValidityStampWriterInterface $stampWriter
   *   The validity stamp writer.
   * @param \Drupal\patternkit\Utility\PatternValidationHelper $validationHelper
   *   The pattern validation helper.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection, used to look up the current pattern revision
   *   without evicting the full entity cache.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ValidityStampWriterInterface $stampWriter,
    protected readonly PatternValidationHelper $validationHelper,
    protected readonly Token $token,
    protected readonly LoggerInterface $logger,
    protected readonly Connection $connection,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('patternkit.validity.stamp_writer'),
      $container->get('patternkit.helper.validation'),
      $container->get('token'),
      $container->get('logger.channel.patternkit_cron'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param array{block_entity_id: int, langcode: string} $data
   *   Queue item data.
   */
  public function processItem($data): void {
    $blockEntityId = (int) $data['block_entity_id'];
    $langcode = (string) $data['langcode'];

    // Load the block entity; it may have been deleted since enqueue.
    $blockStorage = $this->entityTypeManager->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock|null $block */
    $block = $blockStorage->load($blockEntityId);
    if ($block === NULL) {
      $this->logger->notice(
        'ValidityStampQueueWorker: block @id not found; skipping.',
        ['@id' => $blockEntityId],
      );
      return;
    }

    // Guard against the translation being removed between enqueue and
    // processing.
    if (!$block->hasTranslation($langcode)) {
      $this->logger->notice(
        'ValidityStampQueueWorker: block @id has no @langcode translation; skipping.',
        ['@id' => $blockEntityId, '@langcode' => $langcode],
      );
      return;
    }

    // Switch to the target translation so field values and revision ID reflect
    // the correct language variant.
    $block = $block->getTranslation($langcode);
    $blockRevisionId = (int) $block->getRevisionId();
    $patternId = (string) $block->get('pattern_id')->value;

    // Resolve the current default pattern revision for staleness comparison.
    $pattern = $this->resolveCurrentPattern($patternId);
    if ($pattern === NULL) {
      $this->logger->notice(
        'ValidityStampQueueWorker: no pattern found for pattern_id "@id" on block @block; skipping.',
        ['@id' => $patternId, '@block' => $blockEntityId],
      );
      return;
    }

    $patternRevisionId = (int) $pattern->getRevisionId();

    // Decode block data through the entity interface so callers are insulated
    // from any future change to the underlying serialized format.
    try {
      $content = $block->getData();
    }
    catch (\JsonException $e) {
      $this->logger->notice(
        'ValidityStampQueueWorker: block @id (@langcode) has malformed JSON data; skipping.',
        ['@id' => $blockEntityId, '@langcode' => $langcode],
      );
      return;
    }

    // Validate content against the pattern schema; exceptions accumulate on
    // the helper so they can be inspected without throwing.
    // SchemaReferenceException or SchemaException from schema compilation
    // indicate a broken pattern schema, not a transient error. Skip rather
    // than retry.
    $this->validationHelper->clearExceptions();
    try {
      $this->validationHelper->validatePattern($pattern, $content);
    }
    catch (SchemaReferenceException $e) {
      $this->logger->warning(
        'ValidityStampQueueWorker: block @id (@langcode) references pattern "@pattern" with an unresolvable $ref in its schema; skipping.',
        ['@id' => $blockEntityId, '@langcode' => $langcode, '@pattern' => $patternId],
      );
      return;
    }
    catch (SchemaException $e) {
      $this->logger->warning(
        'ValidityStampQueueWorker: block @id (@langcode) references pattern "@pattern" with an invalid schema; skipping.',
        ['@id' => $blockEntityId, '@langcode' => $langcode, '@pattern' => $patternId],
      );
      return;
    }
    $isValid = !$this->validationHelper->hasExceptions();

    // Token detection operates on the JSON string form; encode from the already
    // decoded array to avoid a second decode.
    $dataString = json_encode($content) ?: '';
    $hasTokens = !empty($this->token->scan($dataString));

    try {
      $this->stampWriter->writeStamp(
        $blockEntityId,
        $langcode,
        $blockRevisionId,
        $patternRevisionId,
        $isValid,
        $hasTokens,
        ValidityStampWriterInterface::STAMP_SOURCE_CRON,
      );
    }
    catch (\Throwable $e) {
      $this->logger->warning(
        'ValidityStampQueueWorker: failed to write stamp for block @id (@langcode): @message',
        [
          '@id' => $blockEntityId,
          '@langcode' => $langcode,
          '@message' => $e->getMessage(),
        ],
      );
    }
  }

  /**
   * Resolves the current default pattern entity for a pattern asset name.
   *
   * PatternRepository::getPattern() operates at the discovery/filesystem
   * layer and can synthesize in-memory entities from plugin data that have
   * no persisted revision ID, making it unsuitable for the stamp comparison
   * that needs the DB-persisted default revision.
   *
   * Rather than calling resetCache() (which evicts every cached pattern
   * entity and forces a full reload on the next access), this method queries
   * the pattern base table directly for the current default revision ID and
   * loads only that specific revision. Subsequent accesses to the same entity
   * within the same request can still be served from cache.
   *
   * @param string $patternId
   *   The pattern asset name, e.g. '@patternkit/atoms/example/src/example'.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface|null
   *   The current default pattern revision, or NULL if not found.
   */
  private function resolveCurrentPattern(string $patternId): ?PatternInterface {
    // Asset name format: '@<library>/<path>'. Strip the leading '@'.
    if (!str_starts_with($patternId, '@')) {
      return NULL;
    }
    $withoutAt = substr($patternId, 1);
    $slashPos = strpos($withoutAt, '/');
    if ($slashPos === FALSE) {
      return NULL;
    }
    $library = substr($withoutAt, 0, $slashPos);
    $path = substr($withoutAt, $slashPos + 1);

    // Query the pattern base table directly so the revision pointer reflects
    // the live DB state rather than a potentially stale entity-cache entry.
    $revisionId = $this->connection->select('pattern', 'p')
      ->fields('p', ['revision'])
      ->condition('p.library', $library)
      ->condition('p.path', $path)
      ->orderBy('p.id', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if ($revisionId === FALSE || $revisionId === NULL) {
      return NULL;
    }

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $patternStorage */
    $patternStorage = $this->entityTypeManager->getStorage('patternkit_pattern');
    /** @var \Drupal\patternkit\Entity\PatternInterface|null $pattern */
    $pattern = $patternStorage->loadRevision((int) $revisionId);
    return $pattern;
  }

}
