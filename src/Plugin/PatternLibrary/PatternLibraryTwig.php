<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\patternkit\Annotation\PatternLibrary;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternEditorConfig;
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
   * @var \Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser
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
    /** @var \Drupal\patternkit\Asset\PatternLibraryParserInterface $twig_parser */
    $twig_parser = $container->get('patternkit.asset.library.parser.twig');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    return new static($root, $serializer, $state, $twig_parser, $config_factory, $configuration, $plugin_id, $plugin_definition);
  }

  public function fetchAssets(PatternInterface $pattern, PatternEditorConfig $config = NULL) {
    return $this->parser->fetchPatternAssets($pattern, $config);
  }

  /**
   * Overrides the JSON Library render method.
   *
   * {@inheritdoc}
   *
   * @throws \Throwable
   *
   * @todo Return render arrays for Twig only.
   */
  public function render(array $assets): array {
    $elements = [];
    foreach ($assets as $pattern) {
      $template = $pattern->getTemplate();
      if (empty($template)) {
        return [];
      }
      $pattern->config = $pattern->config ?? [];
      $elements[] = [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => $pattern->config,
      ];
    }
    return $elements;
  }

}
