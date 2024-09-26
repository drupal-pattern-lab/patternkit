<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\block\BlockInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock as EntityPatternkitBlock;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Psr\Log\LoggerInterface;

/**
 * A utility service to perform helpful commands for update processes.
 */
class UpdateHelper {

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
   * Pattern storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * The expirable key value factory service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected KeyValueExpirableFactoryInterface $keyValueExpirable;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The patternkit logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The Patternkit layout helper service, if Layout Builder is available.
   *
   * @var \Drupal\patternkit\LayoutHelper|null
   */
  protected ?LayoutHelper $layoutHelper;

  /**
   * Constructs the Patternkit UpdateHelper class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $keyValueExpirable
   *   The expirable key value factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel to use for logging updates.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    KeyValueExpirableFactoryInterface $keyValueExpirable,
    ModuleHandlerInterface $moduleHandler,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->blockStorage = $entityTypeManager->getStorage('patternkit_block');
    $this->patternStorage = $entityTypeManager->getStorage('patternkit_pattern');
    $this->keyValueExpirable = $keyValueExpirable;
    $this->moduleHandler = $moduleHandler;
    $this->logger = $logger;
  }

  /**
   * Test whether a component is provided by patternkit.
   *
   * @param \Drupal\block\BlockInterface|\Drupal\layout_builder\SectionComponent $component
   *   The component to be tested.
   *
   * @return bool
   *   True if the component is provided by patternkit. False otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function isPatternkitComponent($component): bool {
    $plugin_id = $component->getPluginId();

    return str_contains($plugin_id, 'patternkit');
  }

  /**
   * Update all pattern components throughout blocks and layouts.
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
  public function updateAllPatternComponents(array $filter = []): array {
    // Track how many items were updated.
    $block_count = 0;
    $entity_count = 0;

    // Update all layout builder components first if available.
    if ($this->layoutHelper()) {
      $this->logger->info('Updating patterns throughout defined layouts.');
      [
        $block_count,
        $entity_count,
      ] = $this->layoutHelper()->updateLayoutBuilderComponents($filter);
    }

    // Update all block components next.
    if (empty($filter['entity_type'])) {
      $this->logger->info('Updating patterns throughout block components.');
      $block_count += $this->updateBlockComponents($filter);
    }
    else {
      $this->logger->info('Skipping block updates due to the presence of entity filters.');
    }

    $this->logger->notice('Updated @entities entity layouts and @blocks Patternkit blocks.',
      ['@entities' => $entity_count, '@blocks' => $block_count]);

    return [
      $block_count,
      $entity_count,
    ];
  }

  /**
   * Update patterns referenced in block plugins.
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
   * @return int
   *   A count of how many blocks were updated.
   */
  public function updateBlockComponents(array $filter = []): int {
    $block_count = 0;

    // Update all blocks.
    foreach ($this->blockStorage->loadMultiple() as $block) {
      if (!$block instanceof BlockInterface) {
        continue;
      }

      try {
        if ($this->filterComponent($block, $filter)) {
          if ($this->updateBlockComponentPattern($block) !== FALSE) {
            $block_count++;
          }
        }
      }
      catch (\Exception $exception) {
        $this->logger->error('Failed to update pattern for block id @id: @message', [
          '@id' => $block->id(),
          '@message' => $exception->getMessage(),
        ]);
      }
    }

    return $block_count;
  }

  /**
   * Updates a Patternkit Block component's pattern to latest.
   *
   * @param \Drupal\block\BlockInterface|\Drupal\layout_builder\SectionComponent $component
   *   The block plugin or section component to update.
   *
   * @return false|array
   *   The updated configuration, or FALSE if it failed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \JsonException
   */
  public function updateBlockComponentPattern($component) {
    $plugin = $component->getPlugin();
    assert($plugin instanceof PatternkitBlock);

    $this->logger->info('Updating block plugin with id @plugin.',
      ['@plugin' => $plugin->getPluginId()]);

    try {
      $patternkit_block = $this->getBlockFromComponent($component);
    }
    catch (\Exception $ignored) {
      // We'll log the failure in the 'finally' block below.
    }
    finally {
      if (empty($patternkit_block)) {
        $this->logger->error('Unable to load the block entity for block @block_id. Check the logs for more info.', [
          '@block_id' => $plugin->getPluginId(),
        ]);
        return FALSE;
      }
    }

    // Load the pattern referenced from the component.
    try {
      $pattern = $this->getPatternFromComponent($component);
    }
    catch (\Exception $ignored) {
      // We'll log the failure in the 'finally' block below.
    }
    finally {
      if (empty($pattern)) {
        $this->logger->error('Unable to load the pattern for block @block_id. Check the logs for more info.', [
          '@block_id' => $plugin->getPluginId(),
        ]);
        return FALSE;
      }
    }

    try {
      // Load the base pattern for comparison to test if updates are needed.
      $base_pattern = $plugin->getBasePattern();
    }
    catch (\Exception $exception) {
      $this->logger->error('Failed to load base pattern for @pattern.', ['@pattern' => $pattern->getAssetId()]);
      return FALSE;
    }

    // Skip if the pattern doesn't need to be updated.
    if ($base_pattern->getHash() === $pattern->getHash()) {
      $this->logger->info('Pattern "@pattern" did not need to be updated.', ['@pattern' => $pattern->getAssetId()]);
      return FALSE;
    }

    // Update the pattern with new configuration from the base pattern.
    $this->logger->notice('Updating pattern "@pattern" from "@old" to "@new".',
      [
        '@pattern' => $pattern->getAssetId(),
        '@old' => $pattern->getVersion(),
        '@new' => $base_pattern->getVersion(),
      ]);

    // Update the pattern and save configuration changes to the component.
    try {
      $plugin->updatePattern($pattern, $base_pattern);

      // Configuration including the pattern and block entity references or
      // serialized block entity values may have changed, so they need to be
      // persisted to the component.
      $component->set('configuration', $plugin->getConfiguration());
    }
    catch (\Exception $exception) {
      $this->logger->error('Failed to update pattern "@pattern" for block "@block_id".',
        [
          '@pattern' => $pattern->getAssetId(),
          '@block_id' => $plugin->getPluginId(),
        ]);
      return FALSE;
    }

    // Ensure the block revision ID is up-to-date.
    $configuration = $component->get('configuration');
    $configuration['patternkit_block_rid'] = $patternkit_block->getLoadedRevisionId();

    // Return the updated configuration.
    return $configuration;
  }

  /**
   * Load the block referenced from a component.
   *
   * @param \Drupal\block\BlockInterface|\Drupal\layout_builder\SectionComponent $component
   *   The block plugin or component to update.
   *
   * @return \Drupal\patternkit\Entity\PatternkitBlock|null
   *   The loaded patternkit block or null if it could not be loaded.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getBlockFromComponent($component): ?EntityPatternkitBlock {
    $plugin = $component->getPlugin();

    if ($plugin instanceof PatternkitBlock) {
      $block = $plugin->getBlockEntity();

      // Don't return a newly stubbed out block if an existing one wasn't found.
      if ($block->isNew()) {
        $block = NULL;
      }
    }

    return $block ?? NULL;
  }

  /**
   * Load a pattern from a component's configuration.
   *
   * @param \Drupal\block\BlockInterface|\Drupal\layout_builder\SectionComponent $component
   *   The block plugin or component to update.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface|null
   *   The loaded pattern entity or null if it could not be loaded.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\patternkit\Exception\UnknownPatternException
   */
  public function getPatternFromComponent($component): ?PatternInterface {
    $plugin = $component->getPlugin();

    if ($plugin instanceof PatternkitBlock) {
      $pattern = $plugin->getPatternEntity();
    }

    return $pattern ?? NULL;
  }

  /**
   * Get the layout helper service if Layout Builder is enabled.
   *
   * @return \Drupal\patternkit\LayoutHelper|null
   *   The layout helper service or null if it is unavailable.
   */
  protected function layoutHelper(): ?LayoutHelper {
    if (isset($this->layoutHelper)) {
      return $this->layoutHelper;
    }
    elseif ($this->moduleHandler->moduleExists('layout_builder')) {
      $this->layoutHelper = \Drupal::service('patternkit.helper.layout');
      $this->layoutHelper->setUpdateHelper($this);
      return $this->layoutHelper;
    }
    else {
      return $this->layoutHelper = NULL;
    }
  }

  /**
   * Set a layout helper service for usage.
   *
   * @param \Drupal\patternkit\LayoutHelper $layoutHelper
   *   The layout helper service to assign for usage.
   */
  public function setLayoutHelper(LayoutHelper $layoutHelper): void {
    $this->layoutHelper = $layoutHelper;
    $this->layoutHelper->setUpdateHelper($this);
  }

  /**
   * Apply filters to a component to prevent updates to filtered components.
   *
   * @param \Drupal\block\BlockInterface|\Drupal\layout_builder\SectionComponent $component
   *   The block plugin or section component to update.
   * @param array $filter
   *   (optional) An associative array of filter values. Allowed keys are:
   *   - `library`: Filters updates to only patterns within the specified
   *     pattern library.
   *   - `pattern`: A pattern asset name to limit updates.
   *
   * @return \Drupal\patternkit\Plugin\Block\PatternkitBlock|false
   *   The loaded patternkit block plugin instance or false if filters excluded
   *   this component from processing.
   */
  public function filterComponent($component, array $filter = []) {
    try {
      if (!$this->isPatternkitComponent($component)) {
        return FALSE;
      }

      $plugin = $component->getPlugin();

      // Fail now if we got an unexpected plugin type.
      if (!$plugin instanceof PatternkitBlock) {
        $this->logger->warning('Encountered unknown plugin type "@plugin" while processing block ID "@block_id".', [
          '@plugin' => get_class($plugin),
          '@block_id' => method_exists($component, 'getUuid') ? $component->getUuid() : $component->uuid(),
        ]);
        return FALSE;
      }

      // Abort now if the derivative doesn't load properly.
      if ($plugin->getDerivativeId() === NULL) {
        $this->logger->error('Encountered broken block plugin: @block_id', [
          '@block_id' => $plugin->getPluginId(),
        ]);
        return FALSE;
      }

      $pattern_id = $plugin->getPatternId();

      // Stop here if this doesn't match the library filter.
      if (isset($filter['library']) && stripos($pattern_id, (string) $filter['library']) !== 0) {
        $this->logger->info('Skipping block since pattern ID "@id" did not start with library filter "@filter".', [
          '@id' => $pattern_id,
          '@filter' => $filter['library'],
        ]);
        return FALSE;
      }

      // Exclude any patterns not matching the pattern name filter if provided.
      if (isset($filter['pattern']) && stripos($pattern_id, (string) $filter['pattern']) !== 0) {
        $this->logger->info('Skipping block since pattern ID "@id" did not match pattern filter "@filter".', [
          '@id' => $pattern_id,
          '@filter' => $filter['pattern'],
        ]);
        return FALSE;
      }
    }
    catch (PluginException $pluginException) {
      $this->logger->error('Unable to load the plugin for block @block_id: @message', [
        '@block_id' => method_exists($component, 'getUuid') ? $component->getUuid() : $component->uuid(),
        '@message' => $pluginException->getMessage(),
      ]);
      return FALSE;
    }

    return $plugin;
  }

}
