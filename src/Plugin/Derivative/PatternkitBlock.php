<?php

namespace Drupal\patternkit\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Drupal\patternkit\Asset\LibraryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PatternkitBlock extends DeriverBase implements ContainerDeriverInterface {

  /** @var \Drupal\Core\Config\ImmutableConfig */
  protected $config;

  /** @var \Drupal\Core\Logger\LoggerChannelInterface */
  protected $logger;

    /** @var \Drupal\Core\Entity\EntityStorageInterface */
  protected $patternkitStorage;

  /** @var \Drupal\patternkit\Asset\LibraryInterface */
  protected $library;

  /**
   * Used to populate all of the types of Patternkit blocks based on libraries.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Provides patternkit configurable settings.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Generates and provides log channels.
   * @param \Drupal\patternkit\Asset\LibraryInterface $library
   *   Provides a list of pattern libraries with metadata.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Loads and saves entities to storage.
   */
  public function __construct(
    ImmutableConfig $config,
    LoggerChannelInterface $logger,
    LibraryInterface $library,
    EntityStorageInterface $storage) {
    $this->config = $config;
    $this->logger = $logger;
    $this->library = $library;
    $this->patternkitStorage = $storage;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function create(ContainerInterface $container, $base_plugin_id): PatternkitBlock {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    $config = $config_factory->get(PatternkitSettingsForm::SETTINGS);
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
    $entity_manager = $container->get('entity_type.manager');
    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $logger = $container->get('logger.channel.patternkit');
    /** @var \Drupal\patternkit\Asset\LibraryInterface $library */
    $library = $container->get('patternkit.asset.library');
    return new static(
      $config,
      $logger,
      $library,
      $entity_manager->getStorage('patternkit_block'));
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
  public static function assetToDerivativeId($asset_id) {
    return trim(str_replace('/', '_', str_replace('_', '__', $asset_id)),'@');
  }

  /**
   * Translate the derivative ID to a pattern library asset ID.
   *
   * @param string $derivative_id
   *   A derivative ID in the format 'patternkit_block:library__name_path_to_pattern'.
   *
   * @return string
   *   An asset ID in the format '@library_name/path/to/pattern'.
   *
   * @todo Deprecate this function, and rewrite dependent methods.
   */
  public static function derivativeToAssetId($derivative_id) {
    return '@' . str_replace('//', '_', str_replace('_', '/', $derivative_id));
  }

  /**
   * Provides block definitions from pattern libraries and reusable patterns.
   *
   * @param array $base_definition
   *   The definition array of the base plugin.
   * @return array
   *   An array of full derivative definitions keyed on derivative id.
   *
   * Definitions per Drupal Core convention are keyed as:
   * @code plugin_id:definition_id{:variant} @endcode
   * @example
   * @code patternkit_block:pattern.library.name_path_to_pattern @endcode
   * to keep consistency with other block plugins.
   *
   * Drupal block derivative definitions appear in both schema as well as URLs.
   *
   * @see getDerivativeDefinition()
   */
  public function getDerivativeDefinitions($base_definition): array {
    // Reset the discovered definitions.
    $this->derivatives = [];
    $patterns = [];
    try {
      $patterns = $this->library->getAssets();
    }
    catch (\Exception $exception) {
      $this->logger->error('Error loading patterns for derivative blocks: @message', ['@message' => $exception->getMessage()]);
    }

    $libraries_config = $this->config->get('patternkit_libraries');
    foreach ($patterns as $pattern_key => $pattern_metadata) {
      try {
        $pattern = Pattern::create($pattern_metadata);
      }
      catch (\Exception $e) {
        $this->logger->error('Error loading patterns for derivative blocks: @message', ['@message' => $exception->getMessage()]);
        continue;
      }
      $pattern_id = $this->assetToDerivativeId($pattern_key);
      $lib = $pattern->getLibrary();
      if (isset($libraries_config[$lib])
        && ($libraries_config[$lib]['enabled'] === 0 || $libraries_config[$lib]['visible'] === 0)) {
        continue;
      }
      $this->derivatives[$pattern_id] = [
        'category' => (string) t('Patternkit:@lib/@category', [
          '@lib'      => $lib ?? 'patternkit',
          '@category' => $pattern->getCategory() ?? 'default',
        ]),
        'admin_label' => t('[Patternkit] @pattern', ['@pattern' => $pattern->label()]),
        'pattern' => $pattern,
      ];
      $this->derivatives[$pattern_id] += $base_definition;
    }

    $patternkit_blocks = $this->patternkitStorage->loadByProperties(['reusable' => TRUE]);
    foreach ($patternkit_blocks as $patternkit_block) {
      $pkb_uuid = $patternkit_block->uuid();
      $this->derivatives[$pkb_uuid] = $base_definition;
      $block_pattern_id = $patternkit_block->getPattern() ?? '';
      $this->derivatives[$pkb_uuid]['pattern'] = $this->derivatives[$block_pattern_id]['pattern'] ?? Pattern::create([]);
      $this->derivatives[$pkb_uuid]['admin_label'] = $patternkit_block->label();
      $this->derivatives[$pkb_uuid]['config_dependencies']['content'] = [
        $patternkit_block->getConfigDependencyName(),
      ];
    }
    return parent::getDerivativeDefinitions($base_definition);
  }

}
