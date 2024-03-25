<?php

namespace Drupal\patternkit_usage_tracking\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\patternkit_usage_tracking\BulkOperations\LayoutDiscoveryBatchManager;
use Drupal\patternkit_usage_tracking\BulkOperations\LayoutUsageTrackingBatchManager;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Commands for working with the dev branch of Patternkit.
 */
class PatternkitUsageTrackingCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The layout batch manager service.
   *
   * @var \Drupal\patternkit_usage_tracking\BulkOperations\LayoutDiscoveryBatchManager
   */
  protected LayoutDiscoveryBatchManager $batchManager;

  /**
   * The storage handler for the 'pattern_usage' entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $usageStorage;

  /**
   * Constructs the Patternkit Usage Tracking Drush Commands functionality.
   *
   * @param \Drupal\patternkit_usage_tracking\BulkOperations\LayoutDiscoveryBatchManager $batchManager
   *   The layout batch manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(LayoutDiscoveryBatchManager $batchManager, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();

    $this->batchManager = $batchManager;
    $this->usageStorage = $entityTypeManager->getStorage('pattern_usage');
  }

  /**
   * Scan layouts for pattern usages.
   *
   * @option 'default'|latest'|'all' $revisions
   *   The selection mode for entity revisions to update. Options are:
   *   - default: The current default revision is loaded.
   *   - latest: The latest entity revision is loaded. This is usually the
   *     current default revision unless a newer unpublished revision exists.
   *   - all: Selection should include all discovered entity revisions.
   * @option no-run-queue
   *   Prevent execution of discovered layout updates and leave them in the
   *   queue for later processing instead.
   *
   * @usage drush pkuscan
   *   Scan layouts for all default revisions.
   * @usage drush pkuscan --revisions=all
   *   Scan layouts for all available revisions.
   * @usage drush pkuscan --no-run-queue
   *   Discover all layouts for scanning, but leave them in the
   *   'patternkit_usage_tracking' queue for later processing.
   *
   * @command patternkit:usage:scan
   * @aliases pkuscan
   */
  public function batchScan($options = [
    'revisions' => 'default',
    'no-run-queue' => FALSE,
  ]) {
    // Confirm whether execution should continue if usage data already exists.
    if ($this->usageStorage->hasData()) {
      if (!$this->confirm(dt('Usage data already exists. Re-running the usage scanning operation improperly may result in duplication of records. Would you like to continue?'))) {
        // Abort if the user chose not to continue.
        return static::EXIT_FAILURE;
      }
    }
    // Validate the selected revision mode.
    $revision_modes = ['default', 'latest', 'all'];
    if (!in_array($options['revisions'], $revision_modes)) {
      throw new InvalidArgumentException(dt('Invalid option "@value" provided for "--revisions" flag. Valid options are: @options', [
        '@value' => $options['revisions'],
        '@options' => implode(',', $revision_modes),
      ]));
    }

    $update_options = [
      'revisions' => $options['revisions'],
      'run_queue' => !$options['no-run-queue'],
    ];

    $this->batchManager->populateQueue(5, $update_options);
    drush_backend_batch_process();
  }

  /**
   * View results from the last execution of batch scanning.
   *
   * @usage drush pkuscan-results
   *   List the scan results for the last scan operation.
   * @usage drush pkuscan-results --filter='num_tracked!=0'
   *   List results for all displays with discovered blocks.
   *
   * @command patternkit:usage:results
   * @aliases pkuscan-results
   *
   * @field-labels
   *   display_id: Display ID
   *   blocks: Blocks
   *   entities: Layouts
   *   errors: Errors
   *   num_tracked: Tracked
   * @default-table-fields display_id,num_tracked,errors
   * @filter-default-field display_id
   */
  public function batchScanResults(): RowsOfFields {
    $results = LayoutUsageTrackingBatchManager::getMostRecentResults();

    return new RowsOfFields($results);
  }

}
