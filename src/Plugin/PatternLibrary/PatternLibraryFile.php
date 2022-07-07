<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\patternkit\Asset\PatternLibraryParser\FilePatternLibraryParser;
use Twig\Loader\FilesystemLoader;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides mechanisms for parsing and rendering a File Library of patterns.
 *
 * @PatternLibrary(
 *   id = "file",
 * )
 */
class PatternLibraryFile extends PatternLibraryPluginDefault implements ContainerFactoryPluginInterface {

  /**
   * Twig environment service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected TwigEnvironment $twig;

  /**
   * Twig file loader.
   *
   * @var \Twig\Loader\FilesystemLoader
   */
  protected FilesystemLoader $twigLoader;

  /**
   * File pattern library parser service.
   *
   * @var \Drupal\patternkit\Asset\PatternLibraryParser\FilePatternLibraryParser
   */
  protected FilePatternLibraryParser $fileParser;

  /**
   * Creates a new File Pattern Library using the given container.
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $root = $container->getParameter('app.root');
    /** @var \Drupal\patternkit\Asset\PatternLibraryParserInterface $file_parser */
    $file_parser = $container->get('patternkit.asset.library.parser.file');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    return new static($root, $file_parser, $config_factory, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditor(PatternInterface $pattern = NULL, ?PatternEditorConfig $config = NULL): string {
    // @todo Implement getEditor() method.
    return '';
  }

  /**
   * Overrides the JSON Library render method.
   *
   * {@inheritdoc}
   *
   * @throws \Throwable
   *
   * @todo Return render arrays for File only.
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
      $bare = basename($file);
      /** @var \Drupal\Core\Template\TwigEnvironment $twig */
      $twig       = \Drupal::service('twig');
      $template   = $twig->load("$namespace/$pattern->filename");
      $elements[] = $template->render($pattern->config ?? []);
    }
    return $elements;
  }

}
