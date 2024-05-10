<?php

namespace Drupal\patternkit\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Drupal\patternkit\PatternRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A deriver to create block derivatives for all discovered patterns.
 */
class PatternkitBlock extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Configuration for the patternkit module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * A logger for output of messages during processing.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The storage handler for patternkit patterns.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * The storage handler for patternkit blocks.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $blockStorage;

  /**
   * The patternkit repository service.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

  /**
   * Used to populate all the types of Patternkit blocks based on libraries.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Provides patternkit configurable settings.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Generates and provides log channels.
   * @param \Drupal\patternkit\PatternRepository $pattern_repository
   *   Provides a list of discovered pattern metadata and instantiates patterns.
   * @param \Drupal\Core\Entity\EntityStorageInterface $pattern_storage
   *   Loads and saves patternkit pattern entities to storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_storage
   *   Loads and saves patternkit block entities to storage.
   */
  public function __construct(
    ImmutableConfig $config,
    LoggerChannelInterface $logger,
    PatternRepository $pattern_repository,
    EntityStorageInterface $pattern_storage,
    EntityStorageInterface $block_storage,
  ) {
    $this->config = $config;
    $this->logger = $logger;
    $this->patternRepository = $pattern_repository;
    $this->patternStorage = $pattern_storage;
    $this->blockStorage = $block_storage;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function create(ContainerInterface $container, $base_plugin_id): self {
    $config_factory = $container->get('config.factory');
    $entity_manager = $container->get('entity_type.manager');

    return new static(
      $config_factory->get(PatternkitSettingsForm::SETTINGS),
      $container->get('logger.channel.patternkit'),
      $container->get('patternkit.pattern.repository'),
      $entity_manager->getStorage('patternkit_pattern'),
      $entity_manager->getStorage('patternkit_block'),
    );
  }

  /**
   * Translate the pattern library asset ID to a derivative ID.
   *
   * @param string $asset_id
   *   An asset ID in the format '@library_name/path/to/pattern'.
   *
   * @return string
   *   A derivative ID in the format 'library__name_path_to_pattern'.
   */
  public static function assetToDerivativeId(string $asset_id): string {
    return trim(str_replace('/', '_', str_replace('_', '__', $asset_id)), '@');
  }

  /**
   * Translate the derivative ID to a pattern library asset ID.
   *
   * @param string $derivative_id
   *   A derivative ID in the format
   *   'patternkit_block:library__name_path_to_pattern'.
   *
   * @return string
   *   An asset ID in the format '@library_name/path/to/pattern'.
   *
   * @todo Deprecate this function, and rewrite dependent methods.
   */
  public static function derivativeToAssetId(string $derivative_id): string {
    return '@' . str_replace('//', '_', str_replace('_', '/', $derivative_id));
  }

  /**
   * Provides block definitions from pattern libraries and reusable patterns.
   *
   * @param array $base_plugin_definition
   *   The definition array of the base plugin.
   *
   * @return array
   *   An array of full derivative definitions keyed on derivative id.
   *
   *   Definitions per Drupal Core convention are keyed as:
   *
   * @code plugin_id:definition_id{:variant} @endcode
   * @see getDerivativeDefinition()
   * @example
   * @code patternkit_block:pattern.library.name_path_to_pattern @endcode
   *   to keep consistency with other block plugins.
   *
   *   Drupal block derivative definitions appear in both schema and URLs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    // Reset the discovered definitions.
    $this->derivatives = [];
    $patterns = [];
    try {
      $patterns = $this->patternRepository->getAllPatternNames();
    }
    catch (\Exception $exception) {
      $this->logger->error('Error loading patterns for derivative blocks: @message', ['@message' => $exception->getMessage()]);
    }

    $libraries_config = $this->config->get('patternkit_libraries');
    foreach ($patterns as $pattern_name) {
      try {
        $pattern = $this->patternRepository->getPattern($pattern_name);
      }
      catch (\Exception $exception) {
        $this->logger->error('Error loading patterns for derivative blocks: @message', ['@message' => $exception->getMessage()]);
        continue;
      }
      $pattern_id = $this->assetToDerivativeId($pattern_name);
      $lib = $pattern->getLibrary();
      if (isset($libraries_config[$lib])
        && ($libraries_config[$lib]['enabled'] === 0 || $libraries_config[$lib]['visible'] === 0)) {
        continue;
      }
      $this->derivatives[$pattern_id] = [
        'category' => (string) $this->t('Patternkit:@lib/@category', [
          '@lib' => $lib ?? 'patternkit',
          '@category' => $pattern->getCategory() ?? 'default',
        ]),
        'admin_label' => $this->t('[Patternkit] @pattern', ['@pattern' => $pattern->label()]),
        'pattern' => $pattern,
      ];
      $this->derivatives[$pattern_id] += $base_plugin_definition;
    }

    // @todo Load from pattern repository and handle missing patterns.
    $patternkit_blocks = [];
    $pk_block_ids = $this->blockStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('reusable', TRUE)
      ->execute();
    if ($pk_block_ids) {
      $patternkit_blocks = $this->blockStorage->loadMultiple($pk_block_ids);
    }
    foreach ($patternkit_blocks as $patternkit_block) {
      $pkb_uuid = $patternkit_block->uuid();
      $this->derivatives[$pkb_uuid] = $base_plugin_definition;
      $block_pattern_id = $patternkit_block->getPattern() ?? '';
      $this->derivatives[$pkb_uuid]['pattern'] = $this->derivatives[$block_pattern_id]['pattern'] ?? $this->patternStorage->create([]);
      $this->derivatives[$pkb_uuid]['admin_label'] = $patternkit_block->label();
      $this->derivatives[$pkb_uuid]['config_dependencies']['content'] = [
        $patternkit_block->getConfigDependencyName(),
      ];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
