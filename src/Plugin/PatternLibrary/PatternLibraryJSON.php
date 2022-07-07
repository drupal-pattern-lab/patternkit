<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\JSONSchemaEditorTrait;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\PatternLibraryPluginDefault;
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
   * Creates a new PatternLibraryJSON plugin instance.
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
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator service.
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
    FileUrlGeneratorInterface $file_url_generator,
    array $configuration,
    string $plugin_id,
    $plugin_definition
  ) {
    $this->serializer = $serializer;
    $this->state = $state;
    $this->fileUrlGenerator = $file_url_generator;

    parent::__construct(
      $root,
      $parser,
      $config_factory,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Creates a new Pattern Library using the given container.
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $root = $container->getParameter('app.root');
    $serializer = $container->get('serialization.json');
    $state = $container->get('state');
    $json_parser = $container->get('patternkit.asset.library.parser.json');
    $config_factory = $container->get('config.factory');
    $file_url_generator = $container->get('file_url_generator');

    $plugin = new static(
      $root,
      $serializer,
      $state,
      $json_parser,
      $config_factory,
      $file_url_generator,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    // Conditionally inject the editor plugin manager if it is available.
    if ($container->has('plugin.manager.editor')) {
      $plugin->setEditorPluginManager($container->get('plugin.manager.editor'));
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditor(?PatternInterface $pattern = NULL, ?PatternEditorConfig $config = NULL): array {
    $config ??= new PatternEditorConfig();
    $config->hostname = \Drupal::request()->getHost() ?? 'default';
    // @todo Update config JS/CSS with module path.
    return $this->schemaEditor($pattern->getSchema() ?? '', $config);
  }

  /**
   * {@inheritdoc}
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
