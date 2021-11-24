<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\JSONSchemaEditorTrait;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides mechanisms for parsing and rendering a Library of patterns.
 *
 * @PatternLibrary(
 *   id = "json",
 * )
 */
class PatternLibraryJSON extends PatternLibraryPluginDefault implements ContainerFactoryPluginInterface {
  use JSONSchemaEditorTrait;

  /**
   * Attaches services.
   *
   * @param string $root
   *   The application root path.
   *   e.g. '/var/www/docroot'.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Serialization service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Static state of the application.
   * @param \Drupal\patternkit\Asset\PatternLibraryParserInterface $parser
   *   Pattern library parser service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Extension config retrieval.
   * @param array $configuration
   *   Config.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin Definition.
   */
  public function __construct(
    $root,
    SerializationInterface $serializer,
    StateInterface $state,
    PatternLibraryParserInterface $parser,
    ConfigFactoryInterface $config_factory,
    array $configuration,
    $plugin_id,
    $plugin_definition) {

    $this->serializer = $serializer;
    $this->state = $state;
    parent::__construct($root, $parser, $config_factory, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates a new Pattern Library using the given container.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): PatternLibraryPluginInterface {
    $root = $container->get('app.root');
    /** @var \Drupal\Component\Serialization\SerializationInterface $serializer */
    $serializer = $container->get('serialization.json');
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $container->get('state');
    /** @var \Drupal\patternkit\Asset\PatternLibraryParserInterface $json_parser */
    $json_parser = $container->get('patternkit.asset.library.parser.json');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    return new static($root, $serializer, $state, $json_parser, $config_factory, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Implements getEditor().
   *
   * {@inheritDoc}
   */
  public function getEditor(PatternInterface $pattern = NULL,
    PatternEditorConfig $config = NULL) {
    $config = $config ?? new PatternEditorConfig();
    $config->hostname = \Drupal::request()->getHost() ?? 'default';
    // @todo Update config JS/CSS with module path.
    return $this->schemaEditor($pattern->getSchema() ?? '', $config);
  }

  /**
   * Implements render().
   *
   * {@inheritDoc}
   */
  public function render(array $assets): array {
    $elements = [];
    foreach ($assets as $pattern) {
      $elements[] = [
        '#markup' => $pattern->config ?? [],
      ];
    }
    return $elements;
  }

}
