<?php

namespace Drupal\patternkit_usage_tracking\BulkOperations\Worker;

use Drupal\patternkit\BulkOperations\Worker\LayoutDiscoveryBatchWorker as PatternkitLayoutDiscoveryBatchWorker;
use Drupal\patternkit_usage_tracking\BulkOperations\LayoutUsageTrackingBatchManager;
use Drupal\patternkit_usage_tracking\Plugin\QueueWorker\PatternUsageQueueWorker;

/**
 * Batch worker for discovering and queuing layouts for tracking pattern usages.
 */
class LayoutDiscoveryBatchWorker extends PatternkitLayoutDiscoveryBatchWorker {

  /**
   * {@inheritdoc}
   */
  public function getQueueId(): string {
    return PatternUsageQueueWorker::QUEUE_ID;
  }

  /**
   * {@inheritdoc}
   */
  protected function progressMessage($context): string {
    return $this->t('Queueing "@entity_type" layouts for processing: @current of @total', [
      '@entity_type' => $this->entityType->id(),
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function setQueueRunnerBatch(): void {
    $update_batch = LayoutUsageTrackingBatchManager::generateBatch();
    batch_set($update_batch);
  }

}
