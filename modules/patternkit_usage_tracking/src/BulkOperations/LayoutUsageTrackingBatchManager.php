<?php

namespace Drupal\patternkit_usage_tracking\BulkOperations;

use Drupal\patternkit\BulkOperations\LayoutUpdateBatchManager;
use Drupal\patternkit\BulkOperations\Worker\LayoutOperationsBatchWorkerInterface;
use Drupal\patternkit_usage_tracking\BulkOperations\Worker\LayoutUsageBatchWorker;

/**
 * Batching service for scanning pattern usages within layouts.
 *
 * @phpstan-type ResultSet array{
 *   blocks: int,
 *   entities: int,
 *   errors: int,
 *   num_tracked, int,
 *   display_id: string,
 * }
 */
class LayoutUsageTrackingBatchManager extends LayoutUpdateBatchManager {

  /**
   * {@inheritdoc}
   */
  const RESULTS_COLLECTION = 'patternkit.usage_scan';

  /**
   * {@inheritdoc}
   */
  public static function generateBatch(): array {
    $operations = [];

    // Define an update operation to process the queued layout updates.
    $operations[] = [
      static::class . '::batchWorker',
      [],
    ];

    return [
      'operations' => $operations,
      'finished' => static::class . '::batchFinished',
      'title' => t('Detecting patterns in discovered layouts.'),
      'progress_message' => t('Processed @current of @total layouts.'),
      'error_message' => t('This batch encountered an error.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success) {
      $t = \Drupal::translation();

      // Sum all changes.
      $num_tracked = $errors = 0;
      array_walk($results, function (array $display_results) use (&$num_tracked, &$errors) {
        $num_tracked += $display_results['num_tracked'] ?? 0;
        $errors += $display_results['errors'] ?? 0;
      });

      // Record results for detailed reporting.
      static::recordResults($results);

      \Drupal::messenger()->addStatus(t('Processed @total and discovered @usages with @errors.', [
        '@total' => $t->formatPlural(count($results), '1 layout', '@count layouts'),
        '@usages' => $t->formatPlural($num_tracked, '1 pattern usage', '@count pattern usages'),
        '@errors' => $t->formatPlural($errors, '1 error', '@count errors'),
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
  protected static function getBatchWorker(): LayoutOperationsBatchWorkerInterface {
    /** @var \Drupal\patternkit\BulkOperations\Worker\LayoutOperationsBatchWorkerInterface $worker */
    $worker = \Drupal::classResolver(LayoutUsageBatchWorker::class);
    return $worker;
  }

}
