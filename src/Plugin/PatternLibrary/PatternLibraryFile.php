<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\patternkit\Asset\PatternLibraryParser\FilePatternLibraryParser;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Loader\FilesystemLoader;

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
    return new static(
      $container->getParameter('app.root'),
      $container->get('patternkit.asset.library.parser.file'),
      $container->get('config.factory'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditor(?PatternInterface $pattern = NULL, ?PatternEditorConfig $config = NULL): string {
    @trigger_error(__METHOD__ . ' is deprecated in patternkit:9.1.0-beta10 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryJSON::getPatternEditor instead. See https://www.drupal.org/project/patternkit/issues/3386854', E_USER_DEPRECATED);
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternEditor(PatternInterface $pattern, array $editor_config = []): array {
    // @todo Implement getPatternEditor() method.
    return [];
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
        $template = sprintf('@%s#/%s', $pattern->url, $template);
      }
      $namespace = '';
      // If a namespace is provided, break it up.
      if (str_starts_with($template, '@')) {
        [$namespace] = explode('#', $template);
      }

      // @todo Refactor to properly implement DI.
      /** @var \Drupal\Core\Template\TwigEnvironment $twig */
      $twig       = \Drupal::service('twig');
      $template   = $twig->load("$namespace/$pattern->filename");
      $elements[] = $template->render($pattern->config ?? []);
    }
    return $elements;
  }

}
