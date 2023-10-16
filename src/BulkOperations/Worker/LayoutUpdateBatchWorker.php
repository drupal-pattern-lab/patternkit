<?php

namespace Drupal\patternkit\BulkOperations\Worker;

use Drupal\patternkit\Plugin\QueueWorker\LayoutUpdateQueueWorker;

/**
 * Batch worker for updating pattern blocks within layouts.
 */
class LayoutUpdateBatchWorker extends LayoutOperationsBatchWorkerBase {

  /**
   * {@inheritdoc}
   */
  public static function getQueueId(): string {
    return LayoutUpdateQueueWorker::QUEUE_ID;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBatchProgressMessage($context): string {
    return $this->t('Processing queued Patternkit layout updates: @current of @total', [
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

}
