<?php

namespace Drupal\patternkit_media_library\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\patternkit_media_library\BlockAltTextUpdater;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Patternkit Media Library.
 */
class MediaLibraryCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Constructs a MediaLibraryCommands instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\patternkit_media_library\BlockAltTextUpdater $blockUpdater
   *   The block alt text updater service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected BlockAltTextUpdater $blockUpdater,
  ) {
    parent::__construct();
  }

  /**
   * Populate missing alt text for images in pattern blocks.
   *
   * @param array $options
   *   The command options.
   *
   * @usage drush patternkit:populate-alt-text
   *   Update all pattern blocks to populate missing alt text.
   * @usage drush pk-alt --dry-run
   *   Show what would be updated without saving changes.
   * @usage drush pk-alt --limit=50
   *   Process only the first 50 blocks.
   *
   * @command patternkit:populate-alt-text
   * @aliases pk-alt
   *
   * @option dry-run Show what would be updated without saving changes.
   * @option limit Limit the number of blocks to process (for testing).
   */
  public function populateAltText(array $options = ['dry-run' => FALSE, 'limit' => NULL]): void {
    $dry_run = $options['dry-run'];
    $limit = $options['limit'];

    /** @var \Drupal\Core\Entity\EntityStorageInterface $block_storage */
    $block_storage = $this->entityTypeManager->getStorage('patternkit_block');

    // Build the query.
    $query = $block_storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('id');

    if ($limit) {
      $query->range(0, $limit);
    }

    $block_ids = $query->execute();
    $total = count($block_ids);

    if ($total === 0) {
      $this->io()->success($this->t('No pattern blocks found.'));
      return;
    }

    $this->io()->text($this->t('Processing @count pattern blocks...', ['@count' => $total]));

    if ($dry_run) {
      $this->io()->warning($this->t('DRY RUN MODE: No changes will be saved.'));
    }

    // Track statistics.
    $stats = [
      'processed' => 0,
      'updated' => 0,
      'skipped' => 0,
      'failed' => 0,
      'fields_updated' => 0,
    ];

    // Process blocks.
    $batch_size = 50;
    foreach (array_chunk($block_ids, $batch_size, TRUE) as $chunk) {
      /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
      foreach ($block_storage->loadMultiple($chunk) as $block) {
        $stats['processed']++;

        $result = $this->blockUpdater->processBlock($block, $dry_run);

        // Display dry run messages.
        if ($dry_run && !empty($result['messages'])) {
          foreach ($result['messages'] as $message) {
            $this->io()->text('  ' . $message);
          }
        }

        // Update statistics based on result.
        if ($result['status'] === 'updated') {
          $stats['updated']++;
          $stats['fields_updated'] += $result['fields_updated'];
        }
        elseif ($result['status'] === 'failed') {
          $stats['failed']++;
        }
        else {
          $stats['skipped']++;
        }
      }
    }

    // Display summary.
    $this->io()->newLine();
    $this->io()->section($this->t('Summary'));
    $this->io()->table(
      [$this->t('Metric'), $this->t('Count')],
      [
        [$this->t('Total processed'), $stats['processed']],
        [$this->t('Blocks updated'), $stats['updated']],
        [$this->t('Fields updated'), $stats['fields_updated']],
        [$this->t('Blocks skipped'), $stats['skipped']],
        [$this->t('Blocks failed'), $stats['failed']],
      ]
    );

    if ($stats['failed'] > 0) {
      $this->io()->warning($this->t('Some blocks failed to update. Check the logs for details.'));
    }

    if ($dry_run) {
      $this->io()->note($this->t('This was a dry run. No changes were saved. Run without --dry-run to apply changes.'));
    }
    elseif ($stats['updated'] > 0) {
      $this->io()->success($this->t('Successfully updated @count blocks with @fields image alt text values.', [
        '@count' => $stats['updated'],
        '@fields' => $stats['fields_updated'],
      ]));
    }
    else {
      $this->io()->success($this->t('No blocks needed updating.'));
    }
  }

}
