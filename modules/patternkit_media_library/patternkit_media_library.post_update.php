<?php

/**
 * @file
 * Post update functions for Patternkit Media Library.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Populate missing alt text for image fields in pattern blocks.
 */
function patternkit_media_library_post_update_populate_image_alt_text(array &$sandbox): TranslatableMarkup {
  $batch_size = 20;

  /** @var \Drupal\Core\Entity\EntityStorageInterface $block_storage */
  $block_storage = \Drupal::entityTypeManager()->getStorage('patternkit_block');

  // Initialize sandbox on first run.
  if (empty($sandbox)) {
    $sandbox['progress'] = 0;
    $sandbox['current_id'] = 0;
    $sandbox['total'] = $block_storage->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $sandbox['updated'] = 0;
    $sandbox['skipped'] = 0;
    $sandbox['failed'] = 0;
  }

  if ($sandbox['total'] > 0) {
    /** @var \Drupal\patternkit_media_library\BlockAltTextUpdater $updater */
    $updater = \Drupal::service('patternkit_media_library.block_alt_text_updater');

    // Query for blocks in batches.
    $ids = $block_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', $sandbox['current_id'], '>')
      ->sort('id')
      ->range(0, $batch_size)
      ->execute();

    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    foreach ($block_storage->loadMultiple($ids) as $block) {
      $result = $updater->processBlock($block);

      // Update statistics based on result.
      if ($result['status'] === 'updated') {
        $sandbox['updated']++;
      }
      elseif ($result['status'] === 'failed') {
        $sandbox['failed']++;
      }
      else {
        $sandbox['skipped']++;
      }

      $sandbox['current_id'] = $block->id();
      $sandbox['progress']++;
    }
  }

  // Calculate progress.
  if ($sandbox['progress'] != $sandbox['total']) {
    $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total'];
  }

  // Return progress message.
  return t('Processed @progress of @total pattern blocks with @updates and @failures.', [
    '@progress' => $sandbox['progress'],
    '@total' => $sandbox['total'],
    '@updates' => \Drupal::translation()->formatPlural($sandbox['updated'], '1 update', '@count updates'),
    '@failures' => \Drupal::translation()->formatPlural($sandbox['failed'], '1 failure', '@count failures'),
  ]);
}
