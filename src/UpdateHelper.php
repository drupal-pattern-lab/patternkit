<?php

namespace Drupal\patternkit;

use Drupal\block\BlockInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock as EntityPatternkitBlock;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\patternkit\Plugin\Derivative\PatternkitBlock as DerivativePatternkitBlock;

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
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The patternkit pattern repository service.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

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
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $keyValueExpirable
   *   The expirable key value factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel to use for logging updates.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    PatternRepository $patternRepository,
    KeyValueExpirableFactoryInterface $keyValueExpirable,
    ModuleHandlerInterface $moduleHandler,
    LoggerChannelInterface $logger
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->blockStorage = $entityTypeManager->getStorage('patternkit_block');
    $this->patternStorage = $entityTypeManager->getStorage('patternkit_pattern');
    $this->patternRepository = $patternRepository;
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
  public function updateAllPatternComponents(array $filter = []) {
    // Track how many items were updated.
    $block_count = 0;
    $entity_count = 0;

    // Update all layout builder components first if available.
    if ($this->layoutHelper()) {
      $this->logger->info('Updating patterns throughout defined layouts.');
      [
        $block_count,
        $entity_count,
      ] = $this->layoutHelper->updateLayoutBuilderComponents($filter);
    }

    // Update all block components next.
    if (empty($filter['entity_type'])) {
      $this->logger->info('Updating patterns throughout block components.');
      $block_count += $this->updateBlockComponents($filter['library'] ?? NULL);
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
   * @param string|null $library_name
   *   (Optional) A namespaced pattern library name to limit updates to. Only
   *   patterns starting with this library name will be updated.
   *
   * @return int
   *   A count of how many blocks were updated.
   */
  public function updateBlockComponents(?string $library_name = NULL): int {
    $block_count = 0;

    // Update all blocks.
    foreach ($this->blockStorage->loadMultiple() as $block) {
      if (!$block instanceof BlockInterface) {
        continue;
      }

      try {
        $plugin = $block->getPlugin();
        $result = $this->updateBlockComponentPattern($plugin, $library_name);

        if ($result !== FALSE) {
          $block_count++;
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
   * @param string|null $library_name
   *   (optional) The name of the library to match against, or NULL to skip
   *   matching. Example: '@patternkit'.
   *
   * @return false|array
   *   The updated configuration, or FALSE if it failed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function updateBlockComponentPattern($component, ?string $library_name = NULL) {
    if (!$this->isPatternkitComponent($component)) {
      return FALSE;
    }

    $plugin_id = $component->getPluginId();
    $derivative_id = $component->getPlugin()->getDerivativeId();
    if ($derivative_id === NULL) {
      $this->logger->error('Encountered broken block plugin: @block_id', [
        '@block_id' => $plugin_id,
      ]);
      return FALSE;
    }

    $pattern_id = DerivativePatternkitBlock::derivativeToAssetId($derivative_id);

    // Stop here if this doesn't match the library filter.
    if ($library_name && stripos($pattern_id, $library_name) !== 0) {
      $this->logger->info('Skipping block since pattern ID "@id" did start with library filter "@filter".', [
        '@id' => $pattern_id,
        '@filter' => $library_name,
      ]);
      return FALSE;
    }

    $configuration = $component->get('configuration');

    $patternkit_block = $this->getBlockFromComponent($component);

    // Ensure the block revision ID is up-to-date.
    $configuration['patternkit_block_rid'] = $patternkit_block->getLoadedRevisionId();

    $this->logger->info($this->t('Updating block plugin with id @plugin.',
      ['@plugin' => $plugin_id]));

    // Load the pattern referenced from the component.
    try {
      $pattern = $this->getPatternFromComponent($component);
    }
    catch (\Exception $ignored) {
      // We'll log the failure in the 'finally' block below.
    }
    finally {
      if (!isset($pattern)) {
        $this->logger->error($this->t('Unable to load the pattern @pattern for block @block_id. Check the logs for more info.', [
          '@pattern' => $pattern_id ?? $component->getPlugin()->getPluginId(),
          '@block_id' => $plugin_id,
        ]));
        return FALSE;
      }
    }

    // Don't update patterns outside the indicated library if provided. Ignore
    // case when comparing the library name and trim the prefixed '@' from the
    // library filter value.
    if ($library_name && strcasecmp($pattern->getLibrary(), ltrim($library_name, '@')) !== 0) {
      $this->logger->info('Skipping block since pattern library "@library" did not match filter "@filter".', [
        '@library' => $pattern->getLibrary(),
        '@filter' => $library_name,
      ]);
      return FALSE;
    }

    try {
      // Load the base pattern for comparison to test if updates are needed.
      $base_pattern = $this->patternRepository->getPattern($pattern_id, TRUE);
    }
    catch (\Exception $exception) {
      $this->logger->error($this->t("Failed to load base pattern for @pattern.", ['@pattern' => $pattern_id]));
      return FALSE;
    }

    // Skip if the pattern doesn't need to be updated.
    if ($base_pattern->getHash() === $pattern->getHash()) {
      $this->logger->info($this->t('Pattern @pattern did not need to be updated.', ['@pattern' => $pattern_id]));
      return FALSE;
    }

    // Update the pattern with new configuration from the base pattern.
    $this->logger->notice($this->t('Updating pattern @pattern from @old to @new.',
      [
        '@pattern' => $pattern->getAssetId(),
        '@old' => $pattern->getVersion(),
        '@new' => $base_pattern->getVersion(),
      ]));
    $this->updatePattern($pattern, $base_pattern);

    // Update configuration to reference the updated pattern entity revision.
    $configuration['pattern'] = $pattern->getRevisionId();

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
   * Update a Pattern entity to use updated configuration.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern entity to be updated.
   * @param \Drupal\patternkit\Entity\PatternInterface $base_pattern
   *   The pattern entity to pull updated values from.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws \JsonException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updatePattern(PatternInterface $pattern, PatternInterface $base_pattern): int {
    $pattern->setNewRevision();
    $pattern->isDefaultRevision(TRUE);
    $pattern->updateFromPattern($base_pattern);

    return $pattern->save();
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

}
