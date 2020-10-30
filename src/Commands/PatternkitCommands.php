<?php

namespace Drupal\patternkit\Commands;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Entity\PatternInterface;
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
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\Core\Block\BlockManager $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    /** @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager */
    $section_storage_manager = \Drupal::service('plugin.manager.layout_builder.section_storage');
    $storage_definitions = $section_storage_manager->getDefinitions();
    /** @var \Drupal\patternkit\Asset\LibraryInterface $library */
    $library =\Drupal::service('patternkit.asset.library');

    $section_storages = [];
    // Gather section storages from all entities and entity type layouts.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $view_mode_storage */
    $display_storage = $entity_type_manager->getStorage('entity_view_display');
    $displays = $display_storage->loadMultiple();
    /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $display */
    foreach ($displays as $display) {
      if (!$display instanceof LayoutBuilderEntityViewDisplay) {
        continue;
      }
      if (!$display->isLayoutBuilderEnabled()) {
        continue;
      }

      foreach ($storage_definitions as $section_storage_type => $storage_definition) {
        $contexts = [];
        $contexts['display'] = EntityContext::fromEntity($display);
        $contexts['view_mode'] = new Context(new ContextDefinition('string'), $display->getMode());
        // Gathers entity type layouts.
        if ($section_storage = $section_storage_manager->load($section_storage_type, $contexts)) {
          $section_storages[] = $section_storage;
        }

        // Gathers entity layouts.
        $entity_storage = $entity_type_manager->getStorage($display->getTargetEntityTypeId());
        foreach ($entity_storage->loadMultiple() as $entity) {
          $contexts['entity'] = EntityContext::fromEntity($entity);
          $section_storages[] = $section_storage_manager->findByContext($contexts, new CacheableMetadata());
        }
      }
    }

    // Gather section storages from the tempstore, to update layout drafts.
    /** @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_factory */
    $key_value_factory = \Drupal::service('keyvalue.expirable');
    foreach (array_keys($storage_definitions) as $section_storage_type) {
      $key_value = $key_value_factory->get("tempstore.shared.layout_builder.section_storage.$section_storage_type");

      foreach ($key_value->getAll() as $key => $value) {
        $key = substr($key, 0, strpos($key, '.', strpos($key, '.') + 1));
        $contexts = $section_storage_manager->loadEmpty($section_storage_type)->deriveContextsFromRoute($key, [], '', []);
        $section_storages[] = $value->data['section_storage'];
        if ($section_storage = $section_storage_manager->load($section_storage_type, $contexts)) {
          highlight_string("<?php\n\$data =\n" . var_export($section_storage, true) . ";\n?>");
          $section_storages[] = $section_storage;
        }
      }
    }

    foreach ($section_storages as $section_storage) {
      foreach ($section_storage->getSections() as $section_delta => $section) {
        foreach ($section->getComponents() as $component_delta => $component) {
          $plugin_id = $component->getPluginId();
          if (strstr($plugin_id, 'patternkit')) {
            $config = $component->get('configuration');
            if (!isset($config['patternkit_block_id'])) {
              continue;
            }
            $config['id'] = 'patternkit_block:' . str_replace('.', '_', str_replace('_', '__', substr($plugin_id, strlen('patternkit_block:'))));
            /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $pattern_storage */
            $pattern_storage = $entity_type_manager->getStorage('patternkit_pattern');
            $pattern_id = \Drupal\patternkit\Plugin\Derivative\PatternkitBlock::derivativeToAssetId($config['id']);
            /** @var PatternInterface $pattern */
            $pattern = Pattern::create($library->getLibraryAsset($pattern_id));
            if ($pattern === NULL) {
              continue;
            }
            $pattern_cache = $pattern_storage->loadByProperties(['library' => $pattern->getLibrary(), 'path' => $pattern->getPath()]);
            /** @var PatternInterface $pattern_loaded */
            $pattern_loaded = end($pattern_cache);
            if (!empty($pattern_loaded)) {
              if ($pattern_loaded->getHash() !== $pattern->getHash()) {
                $pattern->setNewRevision();
                $pattern->isDefaultRevision(TRUE);
              }
              else {
                $pattern = $pattern_loaded;
              }
            }
            $pattern->save();
            $config['pattern'] = $pattern->getRevisionId();
            $component->setConfiguration($config);
            $section_storage->save();
          }
        }
      }
    }
    $block_manager->clearCachedDefinitions();
    $this->logger()->notice(t('Successfully ran Patternkit from-dev updates.'));
  }

}
