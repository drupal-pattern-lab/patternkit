<?php

namespace Drupal\patternkit\BulkOperations;

use Drupal\patternkit\BulkOperations\Worker\LayoutOperationsBatchWorkerInterface;
use Drupal\patternkit\BulkOperations\Worker\LayoutUpdateBatchWorker;

/**
 * Batching service for updating pattern blocks within layouts.
 *
 * @phpstan-type ResultSet array{
 *   blocks: int,
 *   entities: int,
 *   errors: int,
 *   display_id: string,
 * }
 * @phpstan-type ResultList List<ResultSet>
 */
class LayoutUpdateBatchManager {

  /**
   * The name of the key-value store collection capturing results.
   */
  const RESULTS_COLLECTION = 'patternkit.update';

  /**
   * Generate a batch for running queued update operations.
   *
   * @return array{operations: array<array{callable-string, array}>, finished: callable-string, title: \Drupal\Core\StringTranslation\TranslatableMarkup, progress_message: \Drupal\Core\StringTranslation\TranslatableMarkup, error_message: \Drupal\Core\StringTranslation\TranslatableMarkup}
   *   The batch array.
   *
   * @see static::batchWorker()
   * @see static::batchFinished()
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
      'title' => t('Updating patterns in discovered layouts.'),
      'progress_message' => t('Updated @current of @total layouts.'),
      'error_message' => t('This batch encountered an error.'),
    ];
  }

  /**
   * Batch worker to execute queued update operations.
   *
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   Batch context. It may be an array, or implementing \ArrayObject in the
   *   case of Drush.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see static::generateBatch()
   * @see static::batchFinished()
   */
  public static function batchWorker(&$context): void {
    $worker = static::getBatchWorker();
    $worker->execute($context);
  }

  /**
   * Finish callback for queue execution batch processing.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The results array.
   * @param array $operations
   *   The operations array.
   *
   * @see static::generateBatch()
   * @see static::batchWorker()
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    if ($success) {
      $t = \Drupal::translation();

      // Sum all changes and track for reporting.
      $blocks = $layouts = 0;
      array_walk($results, function (array $display_results) use (&$blocks, &$layouts) {
        // Sum all changes for this display.
        $blocks += $display_results['blocks'];
        $layouts += $display_results['entities'];
      });

      // Record results for detailed reporting.
      static::recordResults($results);

      \Drupal::messenger()->addStatus(t('Processed @total with updates made to @blocks on @layouts.', [
        '@total' => $t->formatPlural(count($results), '1 layout', '@count layouts'),
        '@blocks' => $t->formatPlural($blocks, '1 block', '@count blocks'),
        '@layouts' => $t->formatPlural($layouts, '1 layout', '@count layouts'),
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
   * Store batch results in temporary storage for later review and reporting.
   *
   * @param List<array<string, string|int>> $results
   *   The array of results assembled from batch operations.
   *
   * @phpstan-param ResultList $results
   */
  protected static function recordResults(array $results): void {
    $store = \Drupal::keyValue(static::RESULTS_COLLECTION);

    $store->set('results', $results);
  }

  /**
   * Get most recently saved batch update results.
   *
   * @return array
   *   An array of results from the last batch update operation.
   *
   * @phpstan-return ResultList
   */
  public static function getMostRecentResults(): array {
    $store = \Drupal::keyValue(static::RESULTS_COLLECTION);

    return $store->get('results', []);
  }

  /**
   * Get the batch worker instance for executing batch operations.
   *
   * @return \Drupal\patternkit\BulkOperations\Worker\LayoutOperationsBatchWorkerInterface
   *   The instantiated batch worker.
   */
  protected static function getBatchWorker(): LayoutOperationsBatchWorkerInterface {
    /** @var \Drupal\patternkit\BulkOperations\Worker\LayoutOperationsBatchWorkerInterface $worker */
    $worker = \Drupal::classResolver(LayoutUpdateBatchWorker::class);
    return $worker;
  }

}
