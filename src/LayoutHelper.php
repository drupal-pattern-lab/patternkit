<?php

namespace Drupal\patternkit;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * A utility service to ease working with patternkit components in layouts.
 */
class LayoutHelper implements LoggerAwareInterface {

  use LayoutEntityHelperTrait;
  use LoggerAwareTrait;
  use StringTranslationTrait;

  /**
   * Pattern storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * The patternkit update helper service.
   *
   * @var \Drupal\patternkit\UpdateHelper
   */
  protected UpdateHelper $updateHelper;

  /**
   * Constructs the Patternkit UpdateHelper class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $keyValueExpirable
   *   The expirable key value factory service.
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service.
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface|null $layoutTempstoreRepository
   *   The layout tempstore repository service.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface|null $sectionStorageManager
   *   The section storage manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   *   Throws an exception if an attempt to instantiate the service is made
   *   without providing the required layout builder services.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected KeyValueExpirableFactoryInterface $keyValueExpirable,
    protected PatternRepository $patternRepository,
    protected ?LayoutTempstoreRepositoryInterface $layoutTempstoreRepository = NULL,
    protected $sectionStorageManager = NULL,
  ) {
    if ($layoutTempstoreRepository === NULL || $sectionStorageManager === NULL) {
      throw new MissingDependencyException("Layout Builder is required to use the 'patternkit.helper.layout' service.");
    }

    $this->patternStorage = $entityTypeManager->getStorage('patternkit_pattern');
  }

  /**
   * Get patternkit components from a given layout.
   *
   * @param \Drupal\layout_builder\SectionListInterface $sectionList
   *   The layout section storage to search through.
   *
   * @return \Drupal\layout_builder\SectionComponent[]
   *   An array of patternkit section components keyed by the concatenated array
   *   path to the component within the layout in the form:
   *   `<section_delta>.<component_uuid>`.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPatternComponentsFromLayout(SectionListInterface $sectionList): array {
    $results = [];

    foreach ($sectionList->getSections() as $section_delta => $section) {
      foreach ($section->getComponents() as $uuid => $component) {
        if ($this->updateHelper()->isPatternkitComponent($component)) {
          $results["$section_delta.$uuid"] = $component;
        }
      }
    }

    return $results;
  }

  /**
   * Update patterns within layout builder components.
   *
   * @param array $filter
   *   (optional) An associative array of filter values. Allowed keys are:
   *   - `library`: Filters updates to only patterns within the specified
   *     pattern library.
   *   - `entity_type`: An entity type machine name. Only patterns within
   *     displays on this entity type will be updated.
   *   - `bundle`: A bundle machine name to filter layout updates by. For this
   *     filter to be used, an `entity_type` value is also required.
   *   - `display_mode`: A specific display mode to limit updates to. For this
   *     filter to be used, an `entity_type` and `bundle` value is also
   *     required.
   *   - `pattern`: A pattern asset name to limit updates.
   *
   * @return int[]
   *   A tuple of update counts containing the following values:
   *   - The number of blocks updated.
   *   - The number of entity layouts updated.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function updateLayoutBuilderComponents(array $filter = []): array {
    $block_count = 0;
    $entity_count = 0;

    $entity_type = $filter['entity_type'] ?? '';
    $bundle = $filter['bundle'] ?? '';
    $display_mode = $filter['display_mode'] ?? '';

    // Gather section storages from all entities and entity type layouts.
    $entity_sections = $this->getSectionStorageFromEntities($entity_type, $bundle, $display_mode);

    // Gather section storages from the tempstore, to update layout drafts.
    $temp_sections = $this->getSectionStorageFromTempStore();

    // Prepare a closure for updating each component.
    $update_callback = $this->getUpdateCallback($block_count, $filter);

    // Iterate over sections within each section store to update patterns and
    // blocks within them.
    $this->logger->info('Updating layout components for entity layouts.');
    $updated = $this->applyToSectionComponents($entity_sections, $update_callback);
    $entity_count += count($updated);

    $this->logger->info('Updating layout components for uncommitted layouts.');
    $updated = $this->applyToSectionComponents($temp_sections, $update_callback);
    $entity_count += count($updated);

    return [$block_count, $entity_count];
  }

  /**
   * Create a closure for logging messages only once.
   *
   * @return \Closure
   *   A closure that buffers logged messages for each section storage item. The
   *   closure expects the following arguments:
   *   - SectionStorageInterface $section_storage
   *   - string $storage_id
   */
  protected function getBufferedLogger(): \Closure {
    // Track which section storage items have been logged already.
    $logBuffer = new \SplObjectStorage();

    // Prepare a closure to only log the layout update notification once instead
    // of every time a component within each layout is processed.
    return function (SectionStorageInterface $section_storage, string $storage_id) use ($logBuffer) {
      if (!$logBuffer->contains($section_storage)) {
        $this->logger->notice('Updating layout components for layout @storage_id.', [
          '@storage_id' => $storage_id,
        ]);
        $logBuffer->attach($section_storage);
      }
    };
  }

  /**
   * Create a closure for updating pattern components in a layout.
   *
   * @param int $num_updated
   *   (optional) A counter for how many blocks have been updated. This value is
   *   passed by reference for updating directly.
   * @param array $filter
   *   (optional) An associative array of filter values. Allowed keys are:
   *   - `library`: Filters updates to only patterns within the specified
   *     pattern library.
   *   - `entity_type`: An entity type machine name. Only patterns within
   *     displays on this entity type will be updated.
   *   - `bundle`: A bundle machine name to filter layout updates by. For this
   *     filter to be used, an `entity_type` value is also required.
   *   - `display_mode`: A specific display mode to limit updates to. For this
   *     filter to be used, an `entity_type` and `bundle` value is also
   *     required.
   *   - `pattern`: A pattern asset name to limit updates.
   * @param bool $silent
   *   (optional) An option to silence progress logging. Defaults to FALSE which
   *   logs each item being processed. TRUE will prevent these messages from
   *   being logged.
   *
   * @return \Closure
   *   A closure for use with
   *   @link \Drupal\patternkit\LayoutHelper::applyToSectionComponents @endlink
   *   to update pattern components within a layout.
   */
  public function getUpdateCallback(int &$num_updated = 0, array $filter = [], bool $silent = FALSE): \Closure {
    // Track which section storage items have been logged already.
    // Optionally pass in an empty method to silence logging if set.
    $log_with_buffering = $silent ? fn() => NULL : $this->getBufferedLogger();

    // Prepare a closure for updating each component.
    return function (string $storage_id, SectionStorageInterface $section_storage, SectionComponent $component) use ($filter, &$num_updated, $log_with_buffering): bool {
      $update_helper = $this->updateHelper();

      // Log the notification for updating this section storage item.
      $log_with_buffering($section_storage, $storage_id);

      $updated = FALSE;
      if ($update_helper->filterComponent($component, $filter)) {
        if ($configuration = $update_helper->updateBlockComponentPattern($component)) {
          $component->setConfiguration($configuration);
          $num_updated++;
          $updated = TRUE;
        }
      }

      return $updated;
    };
  }

  /**
   * Apply a callback function to all components within given sections.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface[] $section_storage
   *   An array of section storage items to be iterated over.
   * @param callable $callback
   *   A callback to apply to each section component. The callback should expect
   *   the following arguments:
   *   - $storage_id: The string identifier for the section storage instance
   *     being processed.
   *   - $section_storage: The
   *     @link \Drupal\layout_builder\SectionStorageInterface @endlink layout
   *     containing the component being processed.
   *   - $component: The @link \Drupal\layout_builder\SectionComponent @endlink
   *     element being processed.
   *   - ...$args: Any additional arguments provided to this method.
   *
   *   The callback should return a boolean value indicating whether the
   *   component was changed and the containing section layout should be saved.
   * @param mixed ...$args
   *   Additional arguments to be passed to the callback function.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface[]
   *   A collection of all updated sections.
   */
  public function applyToSectionComponents(array $section_storage, callable $callback, ...$args): array {
    $changed_sections = [];
    foreach ($section_storage as $storage_id => $storage_item) {
      $needs_save = FALSE;
      foreach ($storage_item->getSections() as $section) {
        foreach ($section->getComponents() as $component) {
          try {
            $updated = call_user_func_array($callback, array_merge([
              $storage_id,
              $storage_item,
              $component,
            ], $args));
            assert(is_bool($updated), 'Callback function is expected to return a boolean value');
          }
          catch (\Exception $exception) {
            $this->logger->error('Failed to apply changes to component @component: @message', [
              '@component' => $component->getUuid(),
              '@message' => $exception->getMessage(),
            ]);

            // Don't save updates after error.
            $updated = FALSE;
          }

          $needs_save = $needs_save ?: $updated;
        }
      }

      if ($needs_save) {
        $storage_item->save();
        $changed_sections[$storage_id] = $storage_item;
      }
    }

    return $changed_sections;
  }

  /**
   * Get layout section storage instances for entities.
   *
   * @param string $entity_type
   *   (Optional) An entity type to filter by.
   * @param string $bundle
   *   (Optional) An entity bundle to filter by.
   * @param string $display_mode
   *   (Optional) A specific display mode to filter by.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface[]
   *   All section storage instances from the temp store.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSectionStorageFromEntities(string $entity_type = '', string $bundle = '', string $display_mode = ''): array {
    $section_storages = [];

    // Assemble display type filter.
    $filter = '';
    if ($entity_type) {
      $filter = $entity_type;

      if ($bundle) {
        $filter .= '.' . $bundle;

        if ($display_mode) {
          $filter .= '.' . $display_mode;
        }
      }
    }

    // Get definitions for types of section storage to be updated.
    $storage_definitions = $this->sectionStorageManager->getDefinitions();

    // Identify applicable display modes to be updated.
    $display_storage = $this->entityTypeManager->getStorage('entity_view_display');
    $displays = $display_storage->loadMultiple();
    /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $display */
    foreach ($displays as $display_id => $display) {
      // Skip if layout builder is not used on this display.
      if (!$display instanceof LayoutBuilderEntityViewDisplay
        || !$display->isLayoutBuilderEnabled()) {
        continue;
      }

      // Skip any displays that don't match our filters.
      if ($filter && !str_starts_with($display_id, $filter)) {
        continue;
      }

      // Load various types of storage, most notably default entity layouts and
      // entity-specific overrides.
      foreach (array_keys($storage_definitions) as $section_storage_type) {
        // Prepare contexts to enable loading of layouts with required contexts.
        $contexts = [];
        $contexts['display'] = EntityContext::fromEntity($display);
        $contexts['view_mode'] = new Context(new ContextDefinition('string'), $display->getMode());

        // Gathers entity type layouts.
        if ($section_storage = $this->sectionStorageManager->load($section_storage_type, $contexts)) {
          $section_storages[$section_storage_type . ':' . $section_storage->getStorageId()] = $section_storage;
        }

        // Load section storage for all targeted entities.
        $entity_storage = $this->entityTypeManager->getStorage($display->getTargetEntityTypeId());
        $entity_definition = $this->entityTypeManager->getDefinition($display->getTargetEntityTypeId());

        // Filter which entities are loaded and iterated through.
        // NB: Entity type should already be filtered due to limitation of which
        // displays are iterated through.
        if ($entity_type && $bundle) {
          $ids = $entity_storage->getQuery()
            ->accessCheck(FALSE)
            ->condition($entity_definition->getKey('bundle'), $bundle)
            ->execute();
        }

        // Load and iterate through all entities of this type if no bundle
        // filter was provided.
        foreach ($entity_storage->loadMultiple($ids ?? NULL) as $entity) {
          $contexts['entity'] = EntityContext::fromEntity($entity);
          $storage = $this->sectionStorageManager->findByContext($contexts, new CacheableMetadata());
          $section_storages[$section_storage_type . ':' . $storage->getStorageId()] = $storage;
        }
      }
    }

    return $section_storages;
  }

  /**
   * Get all section storage instances from the temp store.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface[]
   *   All section storage instances from the temp store.
   */
  public function getSectionStorageFromTempStore(): array {
    $section_storages = [];

    $storage_definitions = $this->sectionStorageManager->getDefinitions();
    foreach (array_keys($storage_definitions) as $section_storage_type) {
      $key_value = $this->keyValueExpirable
        ->get("tempstore.shared.layout_builder.section_storage.$section_storage_type");

      foreach ($key_value->getAll() as $key => $value) {
        // Capture the section storage saved in the temp store.
        $section_storages[] = $value->data['section_storage'];

        // Attempt to identify contexts for loading additional storages based
        // on routing.
        $parts = explode('.', $key);
        $context_key = implode('.', array_slice($parts, 0, 2));
        $contexts = $this->sectionStorageManager->loadEmpty($section_storage_type)
          ->deriveContextsFromRoute($context_key, [], '', []);

        // Capture the additional storage if one loads with the contexts.
        if ($section_storage = $this->sectionStorageManager->load($section_storage_type, $contexts)) {
          $section_storages[] = $section_storage;
        }
      }
    }

    return $section_storages;
  }

  /**
   * Inject the update helper service for use in operations.
   *
   * @param \Drupal\patternkit\UpdateHelper $updateHelper
   *   The update helper service for use.
   */
  public function setUpdateHelper(UpdateHelper $updateHelper) {
    $this->updateHelper = $updateHelper;
  }

  /**
   * Get the patternkit update helper service.
   *
   * @return \Drupal\patternkit\UpdateHelper
   *   The patternkit update helper service.
   */
  protected function updateHelper(): UpdateHelper {
    if (!isset($this->updateHelper)) {
      $this->updateHelper = \Drupal::service('patternkit.helper.update');
    }

    return $this->updateHelper;
  }

}
