<?php

namespace Drupal\patternkit\BulkOperations;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\patternkit\BulkOperations\Worker\LayoutDiscoveryBatchWorker;

/**
 * Batching service for discovering and queueing layouts for update.
 */
class LayoutDiscoveryBatchManager {

  use StringTranslationTrait;

  /**
   * The default size of the batch for the revision queries.
   */
  const BATCH_SIZE = 100;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a LayoutDiscoveryBatchManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $translation;
  }

  /**
   * Get entity types that should be tracked.
   *
   * @param string|null $entity_type_filter
   *   (Optional) An entity type filter value to limit processing to.
   *
   * @return string[]
   *   An array of entity type IDs to be tracked.
   *
   * @todo Flesh this out to support configuration and more entity types.
   */
  protected function getEntityTypesToTrack(?string $entity_type_filter): array {
    $entity_types = [
      'node',
    ];

    if ($entity_type_filter) {
      $entity_types = array_intersect($entity_types, [$entity_type_filter]);
    }

    return $entity_types;
  }

  /**
   * Populate the queue to process entities and revisions.
   *
   * Generate a batch to queue processes for all entities.
   *
   * @param int $batch_size
   *   (Optional) The batch size to use when executing the batch process to
   *   populate the queue. Defaults to static::BATCH_SIZE.
   * @param array{run_queue?: bool, filter?: array{entity_type?: string, bundle?: string, display_mode?: string, library?: string, pattern?: string}} $options
   *   An array of options to influence batching behavior and filter processed
   *   blocks.
   */
  public function populateQueue(int $batch_size = 0, array $options = []): void {
    $batch = $this->generateBatch($batch_size, $options);
    batch_set($batch);
  }

  /**
   * Create a batch to queue the entity types in bulk.
   *
   * @param int $batch_size
   *   (Optional) The batch size to use when executing the batch process to
   *   populate the queue. Defaults to static::BATCH_SIZE.
   * @param array{revisions?: string, run_queue?: bool, filter?: array{entity_type?: string, bundle?: string, display_mode?: string, library?: string, pattern?: string}} $options
   *   An array of options to influence batching behavior and filter processed
   *   blocks.
   *
   * @return array{operations: array<array{callable-string, array}>, finished: callable-string, title: \Drupal\Core\StringTranslation\TranslatableMarkup, progress_message: \Drupal\Core\StringTranslation\TranslatableMarkup, error_message: \Drupal\Core\StringTranslation\TranslatableMarkup}
   *   The batch array.
   *
   * @see self::entityTypeBatchWorker()
   * @see self::entityTypeBatchFinished()
   */
  public function generateBatch(int $batch_size = 0, array $options = []): array {
    $batch_size = $batch_size > 0 ? $batch_size : static::BATCH_SIZE;

    // Apply default options accounting for anything not explicitly passed in.
    $options += [
      'revisions' => LayoutDiscoveryBatchWorker::REVISION_MODE_DEFAULT,
      'run_queue' => FALSE,
      'filter' => [],
    ];
    $options['filter'] += [
      'entity_type' => NULL,
      'bundle' => NULL,
      'display_mode' => NULL,
      'library' => NULL,
      'pattern' => NULL,
    ];

    $operations = [];
    $to_track = $this->getEntityTypesToTrack($options['filter']['entity_type']);
    $types = array_intersect_key($this->entityTypeManager->getDefinitions(), array_flip($to_track));
    foreach (array_keys($types) as $entity_type_id) {
      $operations[] = [
        static::class . '::batchWorker',
        [$entity_type_id, $batch_size, $options],
      ];
    }

    return [
      'operations' => $operations,
      'finished' => static::class . '::batchFinished',
      'title' => $this->t('Batching layout pattern updates for tracked entity types.'),
      'progress_message' => $this->t('Processed @current of @total entity types.'),
      'error_message' => $this->t('This batch encountered an error.'),
    ];
  }

  /**
   * Batch worker to queue all updatable displays for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id, for example 'node'.
   * @param int $batch_size
   *   The batch size.
   * @param array{revisions: string, run_queue: bool, filter: array{entity_type?: string, bundle?: string, display_mode?: string, library?: string, pattern?: string}} $options
   *   An array of options to influence batching behavior and filter processed
   *   blocks.
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   Batch context. It may be an array, or implementing \ArrayObject in the
   *   case of Drush.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see self::generateBatch()
   * @see self::entityTypeBatchFinished()
   */
  public static function batchWorker(string $entity_type_id, int $batch_size, array $options, &$context): void {
    $worker = static::getBatchWorker();
    $worker->execute($entity_type_id, $batch_size, $options, $context);
  }

  /**
   * Finish callback for entity type queue batch processing.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The results array.
   * @param array $operations
   *   The operations array.
   *
   * @see self::generateBatch()
   * @see self::entityTypeBatchWorker()
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success) {
      \Drupal::messenger()->addStatus(t('Identified @count layouts for updating on the "@entity_type_label (@entity_type_id)" entity type.', [
        '@count' => count($results['discovered_displays']),
        '@entity_type_label' => $results['entity_type_label'],
        '@entity_type_id' => $results['entity_type_id'],
      ]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      \Drupal::messenger()->addMessage(
        t('An error occurred while processing @operation with arguments: @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * Get the batch worker instance for executing batch operations.
   *
   * @return \Drupal\patternkit\BulkOperations\Worker\LayoutDiscoveryBatchWorker
   *   The instantiated batch worker.
   */
  protected static function getBatchWorker(): LayoutDiscoveryBatchWorker {
    return \Drupal::classResolver(LayoutDiscoveryBatchWorker::class);
  }

}
