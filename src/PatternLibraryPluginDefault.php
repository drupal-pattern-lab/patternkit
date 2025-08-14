<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Form\PatternkitSettingsForm;

/**
 * Provides a default class for PatternLibrary plugins.
 *
 * Includes several useful methods for adding functionality to a Patternkit
 * plugin, such as configuration to use with a settings form.
 *
 * @see \Drupal\patternkit\Form\PatternLibraryJSONForm
 * @see \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryJSON
 */
abstract class PatternLibraryPluginDefault extends PluginBase implements ConfigurableInterface, DependentPluginInterface, PatternLibraryPluginInterface {
  use StringTranslationTrait;
  use DependencySerializationTrait;
  use MessengerTrait;

  /**
   * Stores patternkit configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Attaches services.
   *
   * @param string $root
   *   The application root path.
   *   e.g. '/var/www/docroot'.
   * @param \Drupal\patternkit\Asset\PatternLibraryParserInterface $parser
   *   Pattern library parser service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Extension config retrieval.
   * @param array $configuration
   *   Plugin config.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin Definition.
   */
  public function __construct(
    protected readonly string $root,
    protected PatternLibraryParserInterface $parser,
    ConfigFactoryInterface $config_factory,
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
  ) {
    $this->config = $config_factory->get(PatternkitSettingsForm::SETTINGS);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * Implements build().
   */
  public function build(): array {
    $build = [];
    $build['#settings'] = $this->getConfiguration();
    $build['#definition'] = $this->pluginDefinition;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAssets(PatternInterface $pattern): array {
    return $this->parser->fetchPatternAssets($pattern);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Provide full library metadata.
   * @todo Refactor to remove passing in the extension object.
   */
  public function getMetadata(Extension $extension, PatternLibrary $library, string $path): array {
    return $this->parser->parsePatternLibraryInfo($library, $path);
  }

  /**
   * {@inheritdoc}
   */
  public function getParser(): PatternLibraryParserInterface {
    return $this->parser;
  }

}
