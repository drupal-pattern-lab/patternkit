<?php

namespace Drupal\patternkit\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\block\BlockInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\patternkit\Asset\LibraryInterface;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Plugin\Derivative\PatternkitBlock;
use Drush\Commands\DrushCommands;

/**
 * Commands for working with the dev branch of Patternkit.
 */
class PatternkitCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Block storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $blockStorage;

  /**
   * Asset Library manager.
   *
   * @var \Drupal\patternkit\Asset\LibraryInterface
   */
  protected LibraryInterface $library;

  /**
   * Pattern storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $patternDiscovery;

  /**
   * The pattern repository service.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * Constructs the Patternkit Drush Commands functionality.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\patternkit\Asset\LibraryInterface $library
   *   The patternkit library service.
   * @param \Drupal\patternkit\Asset\PatternDiscoveryInterface $patternDiscovery
   *   The pattern discovery service.
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LibraryInterface $library,
    PatternDiscoveryInterface $patternDiscovery,
    PatternRepository $patternRepository,
    CacheTagsInvalidatorInterface $cacheTagsInvalidator
  ) {
    parent::__construct();

    $this->entityTypeManager = $entityTypeManager;
    $this->blockStorage = $entityTypeManager->getStorage('patternkit_block');
    $this->patternStorage = $entityTypeManager->getStorage('patternkit_pattern');
    $this->library = $library;
    $this->patternDiscovery = $patternDiscovery;
    $this->patternRepository = $patternRepository;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
  }

  /**
   * Update from an old dev version of Patternkit.
   *
   * Note: this is a brute-force method for updating from dev versions
   * to stable releases. Use with caution.
   *
   * @command patternkit:devUpdate
   * @aliases pkdu, patternkit-dev-update
   */
  public function devUpdate(): void {
    $logger = $this->logger();
    if (!\Drupal::moduleHandler()->moduleExists('layout_builder')) {
      $logger->notice($this->t('Patternkit from-dev updates only apply to Layout Builder, which is not enabled. Skipping Patternkit from-dev updates.'));
      return;
    }

    $entity_count = 0;
    $block_count = 0;

    /** @var \Drupal\Core\Block\BlockManager $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    /** @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager */
    $section_storage_manager = \Drupal::service('plugin.manager.layout_builder.section_storage');
    $storage_definitions = $section_storage_manager->getDefinitions();
    /** @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $tempstore_repository */
    $tempstore_repository = \Drupal::service('layout_builder.tempstore_repository');

    $section_storages = [];
    // Gather section storages from all entities and entity type layouts.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $view_mode_storage */
    $display_storage = $this->entityTypeManager->getStorage('entity_view_display');
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
        $entity_storage = $this->entityTypeManager->getStorage($display->getTargetEntityTypeId());
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
          $section_storages[] = $section_storage;
        }
      }
    }

    foreach ($section_storages as $section_storage) {
      foreach ($section_storage->getSections() as $section_delta => $section) {
        foreach ($section->getComponents() as $component_delta => $component) {
          $plugin_id = $component->getPluginId();
          if (strpos($plugin_id, 'patternkit') === FALSE) {
            continue;
          }
          $config = $component->get('configuration');
          if (!isset($config['patternkit_block_id'])) {
            continue;
          }
          $logger->debug($this->t('Updating block plugin with id @plugin:', ['@plugin' => $plugin_id]));

          // Old plugin ids were in the format:
          // 'patternkit_block:library.name_path_to_pattern'.
          // New plugin ids are:
          // 'patternkit_block:library__name_path_to_pattern'.
          if (strpos($plugin_id, '.')) {
            $config['id'] = 'patternkit_block:' . str_replace('.', '_', str_replace('_', '__', substr($plugin_id, strlen('patternkit_block:'))));
          }
          $pattern_id = '@' . str_replace('//', '_', str_replace('_', '/', substr($config['id'], strlen('patternkit_block:'))));
          $pattern_asset = $this->library->getLibraryAsset($pattern_id);
          if ($pattern_asset === NULL) {
            $logger->debug($this->t('Could not find pattern with ID @id.', ['@id' => $pattern_id]));
            continue;
          }
          try {
            /** @var \Drupal\Patternkit\entity\PatternInterface $pattern */
            $pattern = $this->patternStorage->create($pattern_asset);
          }
          catch (\Exception $exception) {
            $logger->debug($this->t('Could not create pattern with ID @id.', ['@id' => $pattern_id]));
            continue;
          }
          if ($pattern === NULL) {
            $logger->debug($this->t('Could not finish creating pattern with ID @id.', ['@id' => $pattern_id]));
            continue;
          }
          $pattern_cache = $this->patternStorage->loadByProperties([
            'library' => $pattern->getLibrary(),
            'path' => $pattern->getPath(),
          ]);
          /** @var \Drupal\Patternkit\entity\PatternInterface $pattern_loaded */
          $pattern_loaded = end($pattern_cache);
          if ($pattern_loaded !== NULL) {
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
          /** @var \Drupal\patternkit\Entity\PatternkitBlock $patternkit_block */
          $patternkit_block = $this->blockStorage->load($config['patternkit_block_id']);
          $config['patternkit_block_rid'] = $patternkit_block->getRevisionId();
          $section_storage
            ->getSection($section_delta)
            ->getComponent($component->getUuid())
            ->setConfiguration($config);
          $block_count++;
        }
      }
      $section_storage->save();
      $tempstore_repository->set($section_storage);
      $entity_count++;
    }
    $logger->info($this->t('Updated @entities entity layouts with @blocks Patternkit blocks.',
      ['@entities' => $entity_count, '@blocks' => $block_count]));
    $block_manager->clearCachedDefinitions();
    $this->entityTypeManager->clearCachedDefinitions();
    $logger->notice($this->t('Successfully ran Patternkit from-dev updates.'));
  }

  /**
   * Update all patterns in a library.
   *
   * @command patternkit:libUpdate
   * @aliases pklu, patternkit-lib-update
   *
   * @return bool
   *   Drush expected command result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function libUpdate(): bool {
    $logger = $this->logger();

    $lb_enabled = FALSE;
    if (\Drupal::moduleHandler()->moduleExists('layout_builder')) {
      $lb_enabled = TRUE;
    }

    $entity_count = 0;
    $block_count = 0;

    /** @var \Drupal\Core\Block\BlockManager $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');

    if ($lb_enabled) {
      /** @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager */
      $section_storage_manager = \Drupal::service('plugin.manager.layout_builder.section_storage');
      $storage_definitions = $section_storage_manager->getDefinitions();
      /** @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $tempstore_repository */
      $tempstore_repository = \Drupal::service('layout_builder.tempstore_repository');
      $section_storages = [];
      // Gather section storages from all entities and entity type layouts.
      /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $view_mode_storage */
      $display_storage = $this->entityTypeManager->getStorage('entity_view_display');
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
          $entity_storage = $this->entityTypeManager->getStorage($display->getTargetEntityTypeId());
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
          $contexts = $section_storage_manager->loadEmpty($section_storage_type)
            ->deriveContextsFromRoute($key, [], '', []);
          $section_storages[] = $value->data['section_storage'];
          if ($section_storage = $section_storage_manager->load($section_storage_type, $contexts)) {
            $section_storages[] = $section_storage;
          }
        }
      }

      foreach ($section_storages as $section_storage) {
        foreach ($section_storage->getSections() as $section_delta => $section) {
          /** @var \Drupal\block\BlockInterface $component */
          foreach ($section->getComponents() as $component_delta => $component) {
            if (!$configuration = $this->updateBlockComponentPluginPattern($component)) {
              continue;
            }
            $section_storage
              ->getSection($section_delta)
              ->getComponent($component->getUuid())
              ->setConfiguration($configuration);
            $block_count++;
          }
          $section_storage->save();
          $tempstore_repository->set($section_storage);
          $entity_count++;
        }
      }
    }
    /** @var \Drupal\block\BlockInterface $block */
    foreach ($this->blockStorage->loadMultiple() as $block) {
      if (!$block instanceof BlockInterface) {
        continue;
      }
      $plugin = $block->getPlugin();
      $this->updateBlockComponentPluginPattern($plugin);
      $block_count++;
    }

    $logger->notice($this->t('Parsed @entities entity layouts with @blocks Patternkit blocks.',
      ['@entities' => $entity_count, '@blocks' => $block_count]));
    $block_manager->clearCachedDefinitions();
    $this->entityTypeManager->clearCachedDefinitions();
    $logger->notice($this->t('Completed running Patternkit library updates.'));

    return TRUE;
  }

  /**
   * Updates a Patternkit Block Component Plugin's Pattern to latest.
   *
   * @param \Drupal\block\BlockInterface|\Drupal\layout_builder\SectionComponent $component
   *   The block plugin or component to update.
   * @param string|null $library_name
   *   The name of the library to match against, or NULL to skip matching.
   *
   * @return false|array
   *   The updated configuration, or FALSE if it failed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function updateBlockComponentPluginPattern($component, ?string $library_name = NULL) {
    $logger = $this->logger();

    $plugin_id = $component->getPluginId();
    if (strpos($plugin_id, 'patternkit') === FALSE) {
      return FALSE;
    }
    $configuration = $component->get('configuration');
    if (!isset($configuration['patternkit_block_id'])
      && !((int) $configuration['patternkit_block_id'] > 0)) {
      return FALSE;
    }
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $patternkit_block */
    if (isset($configuration['patternkit_block_rid'])
      && (int) $configuration['patternkit_block_rid'] > 0) {
      $patternkit_block = $this->blockStorage->loadRevision($configuration['patternkit_block_rid']);
      if ($patternkit_block === NULL) {
        return FALSE;
      }
    }
    else {
      $patternkit_block = $this->blockStorage->load($configuration['patternkit_block_id']);
      if ($patternkit_block === NULL) {
        return FALSE;
      }
      $configuration['patternkit_block_rid'] = $patternkit_block->getLoadedRevisionId();
    }
    $logger->notice($this->t('Updating block plugin with id @plugin.',
      ['@plugin' => $plugin_id]));
    try {
      $plugin = $component->getPlugin();
      $pattern_id = PatternkitBlock::derivativeToAssetId($plugin->getDerivativeId());
      if (!empty($configuration['pattern'])) {
        /** @var \Drupal\patternkit\entity\PatternInterface|null $pattern */
        $pattern = $this->patternStorage->loadRevision($configuration['pattern']);
      }
      else {
        /** @var \Drupal\patternkit\entity\PatternInterface|null $pattern */
        $pattern = $this->patternRepository->getPattern($pattern_id);
      }
    }
    catch (\Exception $exception) {
      $logger->error($this->t('Unable to load the pattern @pattern. Check the logs for more info.', ['@pattern' => $pattern_id ?? $plugin->getPluginId()]));
      return FALSE;
    }
    finally {
      if (is_null($pattern)) {
        $logger->error($this->t('Unable to load the pattern @pattern. Check the logs for more info.', ['@pattern' => $pattern_id ?? $plugin->getPluginId()]));
        return FALSE;
      }
    }
    if ($library_name && $pattern->getLibrary() !== $library_name) {
      return FALSE;
    }

    try {
      $base_pattern = $this->patternRepository->getPattern($pattern_id, TRUE);
    }
    catch (\Exception $exception) {
      $logger->error($this->t("Failed to load base pattern for @pattern.", ['@pattern' => $pattern_id]));
      return FALSE;
    }

    if ($base_pattern->getHash() === $pattern->getHash()) {
      return FALSE;
    }
    $logger->notice($this->t('Updating pattern from @old to @new.',
      ['@old' => $pattern->getVersion(), '@new' => $base_pattern->getVersion()]));
    $pattern->setNewRevision();
    $pattern->isDefaultRevision(TRUE);
    $pattern->setSchema($base_pattern->getSchema());
    $pattern->setTemplate(($base_pattern->getTemplate()));
    $pattern->setVersion($base_pattern->getVersion());
    $pattern->save();
    $configuration['pattern'] = $pattern->getRevisionId();
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $patternkit_block */
    $patternkit_block = $this->blockStorage->load($configuration['patternkit_block_id']);
    $configuration['patternkit_block_rid'] = $patternkit_block->getRevisionId();

    return $configuration;
  }

  /**
   * Get pattern info as discovered by the system.
   *
   * @param string $pattern_id
   *   The pattern identifier to view. For example,
   *   '@patternkit/atoms/example/src/example'.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *   The property data for the requested pattern.
   *
   * @command patternkit:pattern:info
   * @aliases pkinfo
   *
   * @usage drush pkinfo @patternkit/atoms/example/src/example
   *   Show pattern information for the example pattern.
   *
   * @format table
   * @field-labels
   *   id: Pattern ID
   *   name: Pattern name
   *   category: Category
   *   description: Description
   *   library: Library
   *   plugin: Library plugin
   *   path: Path
   *   version: Version
   */
  public function patternInfo(string $pattern_id): PropertyList {
    $pattern = $this->getPattern($pattern_id);

    $info = [
      'id' => $pattern->getAssetId(),
      'name' => $pattern->getName(),
      'category' => $pattern->getCategory(),
      'description' => $pattern->getDescription(),
      'library' => $pattern->getLibrary(),
      'plugin' => $pattern->getLibraryPluginId(),
      'path' => $pattern->getPath(),
      'version' => $pattern->getVersion(),
      // 'assets' => (string) $pattern->getAssets(),
    ];

    return new PropertyList($info);
  }

  /**
   * List all discovered patterns.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The structured output data.
   *
   * @command patternkit:pattern:list
   * @aliases pklist
   *
   * @usage drush pklist --filter=patternkit
   *   List all patterns in the patternkit library.
   * @usage drush pklist --filter='plugin=twig'
   *   List all patterns using the twig plugin.
   * @usage drush pklist --filter=patternkit --format=sections
   *   Show all available pattern information for patterns in the patternkit
   *   library.
   *
   * @list-delimiter :
   * @table-style compact
   * @field-labels
   *   id: Pattern ID
   *   name: Pattern name
   *   category: Category
   *   description: Description
   *   library: Library
   *   plugin: Library plugin
   *   path: Path
   *   version: Version
   * @default-table-fields id,plugin
   * @filter-default-field id
   */
  public function patternList(): RowsOfFields {
    $patterns = $this->patternRepository->getAllPatternNames();

    $list = [];
    foreach ($patterns as $pattern) {
      $list[$pattern] = $this->patternInfo($pattern)->getArrayCopy();
    }

    return new RowsOfFields($list);
  }

  /**
   * Get the schema discovered for a pattern.
   *
   * @param string $pattern_id
   *   The pattern identifier to view. For example,
   *   '@patternkit/atoms/example/src/example'.
   * @param bool $bundled
   *   (optional) Whether to load the schema with all dependencies bundled.
   *   Defaults to false and loads the unbundled pattern schema.
   *
   * @return string
   *   The schema content for the requested pattern.
   *
   * @command patternkit:pattern:schema
   * @aliases pkschema
   *
   * @option bundled Whether the bundled schema should be loaded.
   *
   * @usage drush pkschema @patternkit/atoms/example/src/example
   *   Get the JSON schema content for the example pattern.
   * @usage drush pkschema @patternkit/atoms/example/src/example | jq
   *   Format and filter a schema with jq.
   * @usage drush pkschema @patternkit/atoms/example/src/example --bundled
   *   Get the JSON schema content with all dependencies bundled.
   */
  public function patternSchema(string $pattern_id, bool $bundled = FALSE): string {
    $pattern = $this->patternRepository->getPattern($pattern_id, $bundled);
    $schema = json_decode($pattern->getSchema());

    // Re-encode for formatting.
    return json_encode($schema, JSON_PRETTY_PRINT);
  }

  /**
   * Invalidate caches for patternkit cache data.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   A status message for Drush to display.
   *
   * @command patternkit:cache:clear
   * @aliases pkcc
   *
   * @format string
   */
  public function clearCache(): string {
    $tags = ['patternkit_patterns', 'patternkit_libraries'];

    $this->cacheTagsInvalidator->invalidateTags($tags);

    return $this->t('Invalidated all patternkit pattern and library cache entries.');
  }

  /**
   * Load a pattern instance.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern to be loaded.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The loaded pattern instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getPattern(string $pattern_id): PatternInterface {
    return $this->patternRepository->getPattern($pattern_id);
  }

}
