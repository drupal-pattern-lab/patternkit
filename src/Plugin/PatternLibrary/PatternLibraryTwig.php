<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternEditorConfig;
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
   * @var \Drupal\Core\Template\TwigEnvironment|null
   */
  protected ?TwigEnvironment $twig = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    // Replace the parser with the Twig parser instead.
    $instance->parser = $container->get('patternkit.asset.library.parser.twig');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAssets(PatternInterface $pattern, ?PatternEditorConfig $config = NULL): array {
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
    /** @var \Drupal\patternkit\Entity\Pattern $pattern */
    foreach ($assets as $pattern) {
      $template = $pattern->getTemplate();
      if (empty($template)) {
        return [];
      }
      $pattern->config ??= [];
      $output = [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => $pattern->config,
      ];
      if ($this->getTwigEnvironment()->isDebug()) {
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

  /**
   * Get the lazy-loaded twig environment service.
   *
   * Since this plugin is loaded from the patternkit twig loader
   * ('@twig.loader.patternlibrary'), a circular dependency on the twig
   * environment is created. To resolve this, the twig enviornment is only
   * needed at render time for this plugin, so it is lazy loaded from the
   * container at that time.
   *
   * @return \Drupal\Core\Template\TwigEnvironment
   *   The twig environment service.
   *
   * @see \Drupal\patternkit\Loader\PatternLibraryLoader
   */
  public function getTwigEnvironment(): TwigEnvironment {
    if (!$this->twig) {
      $this->twig = \Drupal::service('twig');
    }

    return $this->twig;
  }

}
