<?php

/**
 * @file
 * Post-update operations for the Patternkit module.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Utility\UpdateException;
use Drupal\Component\Plugin\Exception\PluginException;

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
