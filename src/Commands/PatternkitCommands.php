<?php

namespace Drupal\patternkit\Commands;

use Drush\Commands\DrushCommands;

/**
 * Commands for working with the dev branch of Patternkit.
 */
class PatternkitCommands extends DrushCommands {

  /**
   * Update from an old dev version of Patternkit.
   *
   * @command patternkit:devUpdate
   */
  public function devUpdate() {
    $this->logger()->warning(dt('Ensure Patternkit has been uninstalled and re-installed before running.'));

    /** @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager */
    $section_storage_manager = \Drupal::service('plugin.manager.layout_builder.section_storage');
    /** @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_factory */
    $key_value_factory = \Drupal::service('keyvalue.expirable');

    foreach (array_keys($section_storage_manager->getDefinitions()) as $section_storage_type) {
      $key_value = $key_value_factory->get("tempstore.shared.layout_builder.section_storage.$section_storage_type");

      foreach ($key_value->getAll() as $key => $value) {
        $key = substr($key, 0, strpos($key, '.', strpos($key, '.') + 1));
        $contexts = $section_storage_manager->loadEmpty($section_storage_type)->deriveContextsFromRoute($key, [], '', []);
        $section_storages[] = $value->data['section_storage'];
        if ($section_storage = $section_storage_manager->load($section_storage_type, $contexts)) {
          $section_storages[] = $section_storage;
        }
        foreach ($section_storages as $section_storage) {
          foreach ($section_storage->getSections() as $section_delta => $section) {
            foreach ($section->getComponents() as $component_delta => $component) {
              $plugin_id = $component->getPluginId();
              if (strstr($plugin_id, 'patternkit')) {
                $config = $component->get('configuration');
                $config['id'] = str_replace('.', '_', $plugin_id);
                $component->setConfiguration($config);
                $section_storage->save();
              }
            }
          }
        }
      }
    }
    $this->logger()->success(dt('Successfully ran Patternkit from-dev updates.'));
  }

}
