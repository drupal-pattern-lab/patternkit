<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
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
abstract class PatternLibraryPluginDefault extends PluginBase implements PatternLibraryPluginInterface {
  use StringTranslationTrait;
  use DependencySerializationTrait;
  use MessengerTrait;

  /**
   * Stores patternkit configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Parses library files into a Patternkit Library.
   *
   * @var \Drupal\patternkit\Asset\PatternLibraryParserInterface
   */
  protected $parser;

  /**
   * The application root path.
   *
   * Example: '/var/www/docroot'.
   *
   * @var string
   */
  protected $root;

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
  public function __construct($root,
    PatternLibraryParserInterface $parser,
    ConfigFactoryInterface $config_factory,
    array $configuration,
    $plugin_id,
    $plugin_definition) {

    $this->config = $config_factory->get(PatternkitSettingsForm::SETTINGS);
    $this->root = $root;
    $this->parser = $parser;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * Implements build().
   *
   * {@inheritDoc}
   */
  public function build() {
    $build = [];
    $build['#settings'] = $this->getConfiguration();
    $build['#definition'] = $this->pluginDefinition;
    return $build;
  }

  /**
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   * @param \Drupal\patternkit\PatternEditorConfig|null $config
   *
   * @return array
   */
  public function fetchAssets(PatternInterface $pattern, PatternEditorConfig $config = NULL) {
    return $this->parser->fetchPatternAssets($pattern, $config);
  }

  /**
   * Gets the plugin configuration.
   *
   * @return array
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Implements setConfiguration().
   *
   * {@inheritDoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * Provides the default plugin configuration.
   *
   * @return array
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * Implements calculateDependencies().
   *
   * {@inheritDoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Implements getMetadata().
   *
   * {@inheritDoc}
   *
   * @todo Provide full library metadata.
   */
  public function getMetadata(Extension $extension, PatternLibrary $library, $path): array {
    $path = $this->root . '/' . $path;
    return $this->parser->parsePatternLibraryInfo($library, $path);
  }

}
