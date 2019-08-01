<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\patternkit\PatternLibraryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides mechanisms for parsing and rendering a Twig Library of patterns.
 *
 * @PatternLibrary(
 *   id = "twig",
 * )
 */
class PatternLibraryTwig extends PatternLibraryJSON {

  /**
   * Twig environment service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * Twig file loader.
   *
   * @var \Twig\Loader\FilesystemLoader
   */
  protected $twigLoader;

  /**
   * Twig pattern library parser service.
   *
   * @var \Drupal\patternkit\PatternLibraryParser\TwigPatternLibraryParser
   */
  protected $twigParser;

  /**
   * Creates a new Twig Pattern Library using the given container.
   *
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): PatternLibraryPluginInterface {
    $root = $container->get('app.root');
    /** @var \Drupal\Component\Serialization\SerializationInterface $serializer */
    $serializer = $container->get('serialization.json');
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $container->get('state');
    /** @var \Drupal\patternkit\PatternLibraryParserInterface $twig_parser */
    $twig_parser = $container->get('patternkit.library.discovery.parser.twig');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    return new static($root, $serializer, $state, $twig_parser, $config_factory, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Overrides the JSON Library render method.
   *
   * {@inheritdoc}
   *
   * @throws \Throwable
   */
  public function render(array $assets): array {
    $elements = [];
    foreach ($assets as $pattern) {
      if (empty($pattern->filename)) {
        return [];
      }
      // @todo Allow filename to cache the template contents based on settings.
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
      $elements[] = $template->render($pattern->config ?? []);
    }
    return $elements;
  }

}
