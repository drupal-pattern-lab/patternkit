<?php

/**
 * @file
 * Post-update operations for the Patternkit module.
 */

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\UpdateException;
use Drupal\patternkit\Exception\UnknownPatternException;

/**
 * Populate the dependencies field for all existing pattern entities.
 */
function patternkit_post_update_set_pattern_dependencies(): TranslatableMarkup {
  $libraryPluginManager = \Drupal::service('plugin.manager.library.pattern');
  $pattern_storage = \Drupal::entityTypeManager()
    ->getStorage('patternkit_pattern');

  // Cache parser plugins once they're loaded to avoid repetitive plugin
  // creation.
  $parsers = [];

  // Count how many patterns were updated for final reporting.
  $updated = 0;

  // Load all existing pattern entities.
  $entities = $pattern_storage->loadMultiple();

  // Iterate through all pattern entities to set dependencies.
  /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
  foreach ($entities as $id => $pattern) {
    try {
      // Load the specific parser for the pattern.
      $plugin_id = $pattern->getLibraryPluginId();
      $parsers[$plugin_id] ??= $libraryPluginManager->createInstance($plugin_id)
        ->getParser();
      $plugin = $parsers[$plugin_id];

      // Use the parser to get all pattern dependencies.
      if (method_exists($plugin, 'getSchemaDependencies')) {
        $dependencies = $plugin->getSchemaDependencies($pattern->getSchema(), $pattern->getAssetId());
      }

      if ($dependencies) {
        // Set the discovered dependencies on the pattern entity.
        $pattern->setDependencies($dependencies);

        // Update the revision in place to avoid breaking references.
        $pattern->setNewRevision(FALSE);

        // Store the updated values.
        $pattern->save();

        $updated++;
      }
    }
    catch (PluginException $pluginException) {
      $message = sprintf('Failed to load plugin "%s" for pattern entity %d for pattern %s.',
        $pattern->getLibraryPluginId(), $id, $pattern->getAssetId());
      \Drupal::logger('patternkit')->error($message);
      throw new UpdateException($message);
    }
    catch (EntityStorageException $exception) {
      $message = sprintf('Failed to update pattern entity %d for pattern %s: %s',
        $id, $pattern->getAssetId(), $exception->getMessage());
      \Drupal::logger('patternkit')->error($message);
      throw new UpdateException($message);
    }
  }

  return t('Set dependencies on @count existing patterns.', [
    '@count' => $updated,
  ]);
}

/**
 * Remove the root path from assets saved in stored pattern entities.
 */
function patternkit_post_update_remove_cached_root_path(&$sandbox): TranslatableMarkup {
  $batch_size = 5;

  $pattern_storage = \Drupal::entityTypeManager()
    ->getStorage('patternkit_pattern');
  if (empty($sandbox)) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['updated'] = 0;
    $sandbox['failed'] = 0;
    $sandbox['total'] = $pattern_storage->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  if ($sandbox['total'] > 0) {
    $root = \Drupal::root();
    $discovery = \Drupal::service('patternkit.pattern.discovery');

    $results = $pattern_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', $sandbox['current'], '>')
      ->sort('id')
      ->range(0, $batch_size)
      ->execute();

    /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
    foreach ($pattern_storage->loadMultiple($results) as $pattern) {
      $assets = $pattern->getAssets();

      $needs_update = FALSE;
      foreach ($assets as $path) {
        if (str_starts_with($path, '/') || !file_exists("$root/$path")) {
          $needs_update = TRUE;
        }
      }

      // Update and save pattern.
      if ($needs_update) {
        try {
          // Load the definition object to get the current asset values.
          $pattern_definition = $discovery->getPatternDefinition($pattern->getAssetId());

          // Fail here if the definition couldn't be found.
          if ($pattern_definition === NULL) {
            $message = t('Unable to find pattern definition for pattern ID "@pattern".', [
              '@pattern' => $pattern->getAssetId(),
            ]);
            throw new UnknownPatternException($message);
          }

          // Update the assets to use paths in the definition.
          $pattern->setAssets($pattern_definition['assets']);
          $pattern->setNewRevision(FALSE);
          $pattern->save();
          $sandbox['updated']++;
        }
        catch (EntityStorageException | UnknownPatternException $exception) {
          \Drupal::logger('patternkit')
            ->error('Failed to update assets for pattern "@pattern": @message', [
              '@pattern' => $pattern->getAssetId(),
              '@message' => $exception->getMessage(),
            ]);
          $sandbox['failed']++;
        }
      }

      $sandbox['current'] = $pattern->id();
      $sandbox['progress']++;
    }
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($sandbox['progress'] != $sandbox['total']) {
    $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total'];
  }

  // Set a progress message.
  return t('Processed @progress of @total patterns with @updates and @failures.', [
    '@progress' => $sandbox['progress'],
    '@total' => $sandbox['total'],
    '@updates' => \Drupal::translation()->formatPlural($sandbox['updated'], '1 update', '@count updates'),
    '@failures' => \Drupal::translation()->formatPlural($sandbox['failed'], '1 failure', '@count failures'),
  ]);
}
