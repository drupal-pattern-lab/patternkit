<?php

namespace Drupal\patternkit_usage_tracking\BulkOperations;

use Drupal\patternkit\BulkOperations\LayoutDiscoveryBatchManager as PatternkitLayoutDiscoveryBatchManager;
use Drupal\patternkit_usage_tracking\BulkOperations\Worker\LayoutDiscoveryBatchWorker;

/**
 * Batching service for discovering and queuing layouts for usage scanning.
 */
class LayoutDiscoveryBatchManager extends PatternkitLayoutDiscoveryBatchManager {

  /**
   * {@inheritdoc}
   */
  public function generateBatch(int $batch_size = 0, array $options = []): array {
    $batch = parent::generateBatch($batch_size, $options);
    $batch['title'] = $this->t('Batching layouts to scan for tracked entity types.');

    return $batch;
  }

  /**
   * {@inheritdoc}
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success) {
      \Drupal::messenger()->addStatus(t('Identified @count layouts for scanning on the "@entity_type_label (@entity_type_id)" entity type.', [
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
   * {@inheritdoc}
   */
  protected static function getBatchWorker(): LayoutDiscoveryBatchWorker {
    return \Drupal::classResolver(LayoutDiscoveryBatchWorker::class);
  }

}
