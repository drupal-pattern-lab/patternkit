<?php

namespace Drupal\patternkit_usage_tracking\BulkOperations\Worker;

use Drupal\patternkit\BulkOperations\Worker\LayoutOperationsBatchWorkerBase;
use Drupal\patternkit_usage_tracking\Plugin\QueueWorker\PatternUsageQueueWorker;

/**
 * Batch worker for recording pattern usages within layouts.
 */
class LayoutUsageBatchWorker extends LayoutOperationsBatchWorkerBase {

  /**
   * {@inheritdoc}
   */
  public static function getQueueId(): string {
    return PatternUsageQueueWorker::QUEUE_ID;
  }

}
