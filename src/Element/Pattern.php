<?php

namespace Drupal\patternkit\Element;

use Drupal\patternkit\PatternFieldProcessorPluginManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
 *   '#pattern' => Pattern,
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
class Pattern extends RenderElement implements ContainerFactoryPluginInterface {

  /**
   * The pattern field processor plugin manager.
   *
   * @var \Drupal\patternkit\PatternFieldProcessorPluginManager
   */
  protected PatternFieldProcessorPluginManager $fieldProcessorPluginManager;

  /**
   * The pattern library plugin manager.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager
   */
  protected PatternLibraryPluginManager $libraryPluginManager;

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#pre_render' => [
        [$this, 'preRenderPatternElement'],
      ],
      '#pattern' => NULL,
      '#config' => [],
      '#context' => [],
    ];
  }

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\patternkit\PatternFieldProcessorPluginManager $fieldProcessorPluginManager
   *   The field processor plugin manager service.
   * @param \Drupal\patternkit\PatternLibraryPluginManager $libraryPluginManager
   *   The pattern library parser plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PatternFieldProcessorPluginManager $fieldProcessorPluginManager, PatternLibraryPluginManager $libraryPluginManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->fieldProcessorPluginManager = $fieldProcessorPluginManager;
    $this->libraryPluginManager = $libraryPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.pattern_field_processor'),
      $container->get('plugin.manager.library.pattern'),
    );
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
  public function preRenderPatternElement(array $element): array {
    /** @var \Drupal\patternkit\Entity\Pattern $pattern */
    $pattern = $element['#pattern'];

    // Fail early if a pattern was unable to be loaded.
    if (is_null($pattern)) {
      return [
        '#markup' => t('Pattern unavailable.'),
      ];
    }

    $pattern->config = $element['#config'];
    $pattern->context = $element['#context'];

    try {
      $bubbleableMetadata = new BubbleableMetadata();
      $this->fieldProcessorPluginManager->processSchemaValues($pattern, $pattern->config, $pattern->context, $bubbleableMetadata);

      $library_plugin = $this->getPatternLibraryPlugin($pattern);
      $elements = $library_plugin->render([$pattern]);

      // Apply all bubbleable metadata from preprocessing.
      $bubbleableMetadata->applyTo($elements);
    }
    catch (SchemaException $exception) {
      // Replace the pattern output with an error element for more sophisticated
      // output handling.
      // @see \Drupal\patternkit\Element\PatternError
      $elements = [];
      $elements['error'] = [
        '#type' => 'pattern_error',
        '#pattern' => $element['#pattern'],
        '#config' => $element['#config'],
        '#context' => $element['#context'],
        '#exception' => $exception,
      ];
    }

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
  protected function getPatternLibraryPlugin(PatternInterface $pattern): PatternLibraryPluginInterface {
    $pattern_plugin = $pattern->getLibraryPluginId();
    $library_plugin_id = !empty($pattern_plugin) ? $pattern_plugin : 'twig';

    /** @var \Drupal\patternkit\PatternLibraryPluginInterface */
    $plugin = $this->libraryPluginManager->createInstance($library_plugin_id);
    return $plugin;
  }

}
