<?php

namespace Drupal\patternkit\BulkOperations;

use Drupal\patternkit\BulkOperations\Worker\LayoutOperationsBatchWorkerInterface;
use Drupal\patternkit\BulkOperations\Worker\LayoutUpdateBatchWorker;

/**
 * Batching service for updating pattern blocks within layouts.
 */
class LayoutUpdateBatchManager {

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

      // Sum all changes.
      $blocks = $layouts = 0;
      array_walk($results, function (array $display_results) use (&$blocks, &$layouts) {
        $blocks += $display_results['blocks'];
        $layouts += $display_results['entities'];
      });

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
