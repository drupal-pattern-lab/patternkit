<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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

  use StringTranslationTrait;

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

  /**
   * {@inheritDoc}
   */
  public function fetchAssets(PatternInterface $pattern, PatternEditorConfig $config = NULL) {
    return $this->parser->fetchPatternAssets($pattern, $config);
  }

  /**
   * Overrides the JSON Library render method.
   *
   * {@inheritDoc}
   *
   * @throws \Throwable
   *
   * @todo Return render arrays for Twig only.
   */
  public function render(array $assets): array {
    if ($this->twig === NULL) {
      $this->twig = \Drupal::service('twig');
    }
    $elements = [];
    /** @var \Drupal\patternkit\Entity\Pattern $pattern */
    foreach ($assets as $pattern) {
      $template = $pattern->getTemplate();
      if (empty($template)) {
        return [];
      }
      $pattern->config = $pattern->config ?? [];
      $output = [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => $pattern->config,
      ];
      if ($this->twig->isDebug()) {
        $hash = $pattern->getHash();
        $asset_id = $pattern->getAssetId();
        $path = trim($pattern->getAssets()['twig'] ?: $this->t('Could not resolve file path.'), DRUPAL_ROOT);
        try {
          $version_value = $pattern->get('version')->getValue();
          $version = array_pop($version_value)['value'];
        }
        catch (\Exception $exception) {
          $version = $this->t('No version information available.');
        }
        $output['#template'] = "\n\n<!-- THEME DEBUG -->"
          . "\n<!-- PATTERNKIT VERSION: " . Html::escape($version) . ' -->'
          . "\n<!-- PATTERNKIT HASH: " . Html::escape($hash) . ' -->'
          . "\n<!-- PATTERNKIT PATH: " . Html::escape($asset_id) . ' -->'
          . "\n<!-- BEGIN OUTPUT FROM '" . Html::escape($path) . "' -->\n\n"
          . $output['#template']
          .= "\n<!-- END OUTPUT FROM '" . Html::escape($path) . "' -->\n\n";
      }
      $elements[] = $output;
    }
    return $elements;
  }

}
