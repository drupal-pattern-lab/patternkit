<?php

namespace Drupal\patternkit\Commands;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
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
    if (!\Drupal::moduleHandler()->moduleExists('layout_builder')) {
      $this->logger()->notice(t('Patternkit from-dev updates only apply to Layout Builder, which is not enabled. Skipping Patternkit from-dev updates.'));
      return;
    }

    $entity_count = 0;
    $block_count = 0;

    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\Core\Block\BlockManager $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    /** @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager */
    $section_storage_manager = \Drupal::service('plugin.manager.layout_builder.section_storage');
    $storage_definitions = $section_storage_manager->getDefinitions();
    /** @var \Drupal\patternkit\Asset\LibraryInterface $library */
    $library =\Drupal::service('patternkit.asset.library');
    $logger = $this->logger();

    $section_storages = [];
    // Gather section storages from all entities and entity type layouts.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $view_mode_storage */
    $display_storage = $entity_type_manager->getStorage('entity_view_display');
    $displays = $display_storage->loadMultiple();
    /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $display */
    foreach ($displays as $display) {
      if (!$display instanceof \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay) {
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
          if (!strstr($plugin_id, 'patternkit')) {
            continue;
          }
          $config = $component->get('configuration');
          if (!isset($config['patternkit_block_id'])) {
            continue;
          }
          $logger->debug(t('Updating block plugin with id @plugin:', ['@plugin' => $plugin_id]));
          /**
           * Old plugin ids were in the format:
           *   'patternkit_block:library.name_path_to_pattern'
           *
           * New plugin ids are:
           *   'patternkit_block:library__name_path_to_pattern'
           */
          if (strpos($plugin_id, '.')) {
            $config['id'] = 'patternkit_block:' . str_replace('.', '_', str_replace('_', '__', substr($plugin_id, strlen('patternkit_block:'))));
          }
          /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $pattern_storage */
          $pattern_storage = $entity_type_manager->getStorage('patternkit_pattern');
          $pattern_id = '@' . str_replace('//', '_', str_replace('_', '/', substr($config['id'], strlen('patternkit_block:'))));
          $pattern_asset = $library->getLibraryAsset($pattern_id);
          if ($pattern_asset === NULL) {
            $logger->debug(t('Could not find pattern with ID @id.', ['@id' => $pattern_id]));
            continue;
          }
          try {
            /** @var PatternInterface $pattern */
            $pattern = Pattern::create($pattern_asset);
          }
          catch (\Exception $exception) {
            $logger->debug(t('Could not create pattern with ID @id.', ['@id' => $pattern_id]));
            continue;
          }
          if ($pattern === NULL) {
            $logger->debug(t('Could not finish creating pattern with ID @id.', ['@id' => $pattern_id]));
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
          $block_count++;
        }
      }
      $section_storage->save();
      $entity_count++;
    }
    $this->logger()->info(t('Updated @entities entity layouts with @blocks Patternkit blocks.',
      ['@entities' => $entity_count, '@blocks' => $block_count]));
    $block_manager->clearCachedDefinitions();
    $this->logger()->notice(t('Successfully ran Patternkit from-dev updates.'));
  }

}
