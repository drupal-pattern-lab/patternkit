<?php

namespace Drupal\patternkit\BulkOperations\Worker;

/**
 * A common interface for all layout operations batch worker implementations.
 *
 * Most implementations should extend
 * @link \Drupal\patternkit\BulkOperations\Worker\LayoutOperationsBatchWorkerBase @endlink
 * to implement this interface.
 */
interface LayoutOperationsBatchWorkerInterface {

  /**
   * Get the plugin identifier for the queue to reference.
   *
   * @return string
   *   The plugin ID for the queue to be processed.
   */
  public static function getQueueId(): string;

  /**
   * Batch worker to execute queued update operations.
   *
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   Batch context. It may be an array, or implementing \ArrayObject in the
   *   case of Drush.
   */
  public function execute(&$context): void;

}
