<?php

namespace Drupal\patternkit\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternkitEnvironmentAwareInterface;
use Drupal\patternkit\PatternkitEnvironmentAwareTrait;
use Drupal\patternkit\PatternRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A deriver to create block derivatives for all discovered patterns.
 */
class PatternkitBlock extends DeriverBase implements ContainerDeriverInterface, LoggerAwareInterface, PatternkitEnvironmentAwareInterface {

  use LoggerAwareTrait;
  use PatternkitEnvironmentAwareTrait;
  use StringTranslationTrait;

  /**
   * Used to populate all the types of Patternkit blocks based on libraries.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Provides patternkit configurable settings.
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   Provides a list of discovered pattern metadata and instantiates patterns.
   * @param \Drupal\Core\Entity\EntityStorageInterface $patternStorage
   *   Loads and saves patternkit pattern entities to storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $blockStorage
   *   Loads and saves patternkit block entities to storage.
   */
  public function __construct(
    protected readonly ImmutableConfig $config,
    protected readonly PatternRepository $patternRepository,
    protected readonly EntityStorageInterface $patternStorage,
    protected readonly EntityStorageInterface $blockStorage,
  ) {}

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function create(ContainerInterface $container, $base_plugin_id): static {
    $config_factory = $container->get('config.factory');
    $entity_manager = $container->get('entity_type.manager');

    $instance = new static(
      $config_factory->get(PatternkitSettingsForm::SETTINGS),
      $container->get('patternkit.pattern.repository'),
      $entity_manager->getStorage('patternkit_pattern'),
      $entity_manager->getStorage('patternkit_block'),
    );

    $instance->setLogger($container->get('logger.channel.patternkit'));
    $instance->setPatternkitEnvironment($container->get('patternkit.environment'));

    return $instance;
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
          '@lib' => $lib ?: 'patternkit',
          '@category' => $pattern->getCategory() ?: 'default',
        ]),
        'admin_label' => $this->t('[Patternkit] @pattern', ['@pattern' => $pattern->label()]),
        'pattern' => $pattern,
      ];
      $this->derivatives[$pattern_id] += $base_plugin_definition;
    }

    if ($this->getEnvironmentFeature(PatternkitEnvironment::FEATURE_ENABLE_REUSABLE_BLOCKS, FALSE)) {
      $this->derivatives += $this->getReusableBlockDerivatives($base_plugin_definition);
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

  /**
   * Load derivative definitions for reusable blocks.
   *
   * @param array $base_plugin_definition
   *   The definition array of the base plugin.
   *
   * @return array
   *   An array of full derivative definitions keyed on derivative id, but only
   *   for Patternkit's defined reusable blocks.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @deprecated in patternkit:9.1.2 and is removed from patternkit:9.2.0.
   *    Reusable blocks should be removed or replaced before updating.
   * @see https://www.drupal.org/node/3517440
   *
   * @internal
   */
  #[\Deprecated(
    message: 'Patternkit reusable block support will be removed in Patternkit 9.2.0.',
    since: '9.1.2',
  )]
  private function getReusableBlockDerivatives(array $base_plugin_definition): array {
    if (\PHP_VERSION_ID < 80400) {
      @trigger_deprecation('drupal/patternkit', '9.1.2', 'Reusable block support will be removed from Patternkit in version 9.2.0.');
    }
    $derivatives = [];

    $patternkit_blocks = [];
    $pk_block_ids = $this->blockStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('reusable', TRUE)
      ->execute();
    if ($pk_block_ids) {
      /** @var \Drupal\patternkit\Entity\PatternkitBlock[] $patternkit_blocks */
      $patternkit_blocks = $this->blockStorage->loadMultiple($pk_block_ids);
    }
    foreach ($patternkit_blocks as $patternkit_block) {
      $pkb_uuid = $patternkit_block->uuid();
      $block_pattern_id = $patternkit_block->getPattern() ?? FALSE;

      // Log a failure to load if the pattern could not be identified as
      // expected.
      if (!$block_pattern_id || !str_starts_with($block_pattern_id, '@')) {
        $this->logger->error('Failed to load reusable Patternkit block "@uuid". Related pattern identified as "@pattern".', [
          '@uuid' => $pkb_uuid,
          '@pattern' => $block_pattern_id,
        ]);

        continue;
      }

      // Load the Pattern entity associated with this derivative.
      $block_derivative_id = static::assetToDerivativeId($block_pattern_id);

      $derivative_pattern_entity = $this->derivatives[$block_derivative_id]['pattern'] ?? $this->patternRepository->getPattern($block_pattern_id);

      // Assemble the derivative definition.
      $derivatives[$pkb_uuid] = $base_plugin_definition;
      $derivatives[$pkb_uuid]['pattern'] = $derivative_pattern_entity;
      $derivatives[$pkb_uuid]['admin_label'] = $patternkit_block->label();
      $derivatives[$pkb_uuid]['config_dependencies']['content'] = [
        $patternkit_block->getConfigDependencyName(),
      ];
    }

    return $derivatives;
  }

}
