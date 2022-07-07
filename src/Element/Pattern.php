<?php

namespace Drupal\patternkit\Element;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternLibraryPluginInterface;

/**
 * Provides a render element to display a pattern.
 *
 * Properties:
 * - '#pattern': The loaded Pattern entity to be rendered.
 * - '#config': Configuration to be passed to the pattern for rendering.
 * - '#context': Context values for rendering the pattern.
 *
 * Usage Example:
 * @code
 * $build['example_pattern'] = [
 *   '#type' => 'pattern',
 *   '#pattern' => 'example',
 *   '#config' => [
 *     'text' => '[node:title]',
 *     'formatted_text' => '<p><strong>My formatted text</strong></p>',
 *   ],
 *   '#context' => [
 *     'node' => $node,
 *   ],
 * ];
 * @endcode
 *
 * @RenderElement("pattern")
 */
class Pattern extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#pre_render' => [
        [get_class($this), 'preRenderPatternElement'],
      ],
      '#pattern' => NULL,
      '#config' => [],
      '#context' => [],
    ];
  }

  /**
   * Pattern element pre render callback.
   *
   * @param array $element
   *   An associative array containing the properties of the pattern element.
   *
   * @return array
   *   The modified element.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function preRenderPatternElement(array $element): array {
    /** @var \Drupal\patternkit\Entity\Pattern $pattern */
    $pattern = $element['#pattern'];

    // Fail early if a pattern was unable to be loaded.
    if (is_null($pattern)) {
      $element = [
        '#markup' => t('Pattern unavailable.'),
      ];
      return $element;
    }

    $pattern->config = $element['#config'];
    $pattern->context = $element['#context'];

    $bubbleableMetadata = new BubbleableMetadata();
    static::preprocessConfigValues($pattern, $pattern->config, $pattern->context, $bubbleableMetadata);

    $library_plugin = static::getPatternLibraryPlugin($pattern);
    $elements = $library_plugin->render([$pattern]);

    // Apply all bubbleable metadata from preprocessing.
    $bubbleableMetadata->applyTo($elements);

    return $elements;
  }

  /**
   * Load the appropriate pattern library plugin for rendering the pattern.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern entity being prepared for rendering.
   *
   * @return \Drupal\patternkit\PatternLibraryPluginInterface
   *   The pattern library plugin needed by the given pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected static function getPatternLibraryPlugin(PatternInterface $pattern): PatternLibraryPluginInterface {
    /** @var \Drupal\patternkit\PatternLibraryPluginManager $pattern_plugin_manager */
    $patternLibraryPluginManager = \Drupal::service('plugin.manager.library.pattern');

    $pattern_plugin = $pattern->getLibraryPluginId();
    $library_plugin_id = !empty($pattern_plugin) ? $pattern_plugin : 'twig';

    return $patternLibraryPluginManager->createInstance($library_plugin_id);
  }

  /**
   * Execute value processing on configuration values.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern being prepared and processed.
   * @param array $config
   *   Configuration values for the pattern being prepared.
   * @param array $context
   *   Context values configured for the pattern being prepared.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   Bubbleable metadata to be tracked and updated during processing.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function preprocessConfigValues(PatternInterface $pattern, array &$config, array $context, BubbleableMetadata $bubbleable_metadata): void {
    /** @var \Drupal\patternkit\PatternFieldProcessorPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.pattern_field_processor');
    $manager->processSchemaValues($pattern, $config, $context, $bubbleable_metadata);
  }

}
