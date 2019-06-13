<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\patternkit\Pattern;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibraryParser\TwigPatternLibraryParser;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides mechanisms for parsing and rendering a Twig Library of patterns.
 *
 * @PatternLibrary(
 *   id = "twig",
 * )
 */
class PatternLibraryTwig extends PatternLibraryPluginDefault implements ContainerFactoryPluginInterface {

  /**
   * @var string
   *   The application root path.
   *   e.g. '/var/www/docroot'.
   */
  protected $root;

  /** @var \Symfony\Component\Serializer\SerializerInterface */
  protected $serializer;

  /** @var \Drupal\Core\State\StateInterface */
  protected $state;

  /** @var \Drupal\Core\Template\TwigEnvironment */
  protected $twig;

  /**
   * Twig file loader.
   *
   * @var \Twig\Loader\FilesystemLoader
   */
  protected $twigLoader;

  /** @var \Drupal\patternkit\PatternLibraryParser\TwigPatternLibraryParser */
  protected $twigParser;

  /**
   * Constructs a Twig PatternLibrary.
   *
   * @param string $root
   *   The application root path.
   *   e.g. '/var/www/docroot'.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   Library discovery service.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Serialization service.
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   Twig template service.
   * @param \Drupal\patternkit\PatternLibraryParser\TwigPatternLibraryParser $twig_parser
   *   Twig pattern library parser service.
   *
   * {@inheritdoc}
   *
   */
  public function __construct(
    $root,
    SerializationInterface $serializer,
    TwigPatternLibraryParser $twig_parser,
    array $configuration,
    $plugin_id,
    $plugin_definition) {

    $this->root = $root;
    $this->serializer = $serializer;
    $this->twigParser = $twig_parser;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $configuration, $plugin_id, $plugin_definition): PatternLibraryTwig {
    $root = $container->get('app.root');
    /** @var \Drupal\Component\Serialization\SerializationInterface $serializer */
    $serializer = $container->get('serialization.json');
    /** @var TwigPatternLibraryParser $twig_parser */
    $twig_parser = $container->get('patternkit.library.discovery.parser');
    return new static($root, $serializer, $twig_parser, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditor(Pattern $pattern = NULL,
    PatternEditorConfig $config = NULL) {
    $hostname = $_SERVER['HTTP_HOST'];
    $pattern_schema = $pattern ? $pattern->schema : new \stdClass();
    $schema_json = $this->serializer::encode($pattern_schema);
    $starting_json = $config !== NULL
      ? $this->serializer::encode($config->fields)
      : $config;
    // @todo Move to own JS file & Drupal Settings config var.
    $markup = <<<HTML
<div id="editor-shadow-injection-target"></div>
HTML;

    return [
      '#type'     => 'markup',
      '#markup'   => $markup,
      '#attached' => [
        'drupalSettings' => [
          'patternkitEditor' => [
            'hostname' => $hostname,
            'schemaJson' => $schema_json,
            'startingJson' => $starting_json,
          ],
        ],
        'library' => ['patternkit/patternkit.editor'],
      ],
    ];
  }

  /**
   * Returns the metadata for a Twig pattern library.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension that contains the library.
   * @param string $library
   *   The name of the library, which will also be used as the Twig namespace.
   * @param string $path
   *   The path to the Twig pattern library.
   *
   * @return array
   *   The metadata for this library.
   *
   * @todo Provide full library metadata.
   */
  public function getMetadata(Extension $extension, $library, $path): array {
    $path = $this->root . '/' . $path;
    return $this->twigParser->parseLibraryInfo($library, $path, ['libraryPluginId' => $this->pluginId]);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Throwable
   */
  public function render(array $assets): array {
    $elements = [];
    foreach ($assets as $pattern) {
      if (empty($pattern->filename) || empty($pattern->config)) {
        return [];
      }
      $template = $pattern->filename;
      // Add the namespace, if provided.
      if (!empty($pattern->url)) {
        $template = '@' . $pattern->url . '#/' . $template;
      }
      $namespace = '';
      $file = $template;
      // If a namespace is provided, break it up.
      if (strpos($template, '@') === 0) {
        [$namespace, $file] = explode('#', $template);
      }
      $bare       = basename($file);
      /** @var \Drupal\Core\Template\TwigEnvironment $twig */
      $twig       = \Drupal::service('twig');
      $template   = $twig->load("$namespace/$pattern->filename");
      $elements[] = $template->render($pattern->config);
    }
    return $elements;
  }

}
