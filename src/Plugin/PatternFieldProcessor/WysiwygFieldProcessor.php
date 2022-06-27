<?php

namespace Drupal\patternkit\Plugin\PatternFieldProcessor;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Swaggest\JsonSchema\SchemaContract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A pattern field processor for filtering WYSIWYG content fields.
 *
 * @PatternFieldProcessor(
 *   id = "wysiwyg",
 *   label = @Translation("WYSIWYG"),
 *   description = @Translation("WYSIWYG content processor.")
 * )
 */
class WysiwygFieldProcessor extends PatternFieldProcessorBase {

  /**
   * Global settings for the Patternkit module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $patternkitSettings;

  /**
   * The renderer service for generating processed output.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Constructor for the WysiwygFieldProcessor plugin.
   *
   * @param array $configuration
   *   The plugin instance configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Config\ImmutableConfig $patternkitSettings
   *   Globally configured settings for the Patternkit module.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service for rendering output.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, ImmutableConfig $patternkitSettings, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->patternkitSettings = $patternkitSettings;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $container->get('config.factory');
    $settings = $configFactory->get('patternkit.settings');
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $container->get('renderer');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $settings,
      $renderer,
    );
  }

  /**
   * {@inheritdoc}
   *
   * Expect a global text profile to be configured, and a property schema like
   * the following:
   *
   * @code
   * {
   *   "type" => "string",
   *   "format" => "html",
   *   "options" => {
   *     "wysiwyg" => true,
   *   }
   * }
   * @endcode
   */
  public function applies(SchemaContract $propertySchema, $propertyValue = NULL): bool {
    $textProfile = $this->getTextProfile();
    return is_string($textProfile) && $textProfile !== ''
      && property_exists($propertySchema, 'type') && $propertySchema->type == 'string'
      && property_exists($propertySchema, 'format') && $propertySchema->format == 'html'
      && isset($propertySchema->options) && property_exists($propertySchema->options, 'wysiwyg')
      && $propertySchema->options->wysiwyg == TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function apply(SchemaContract $propertySchema, $value, array $context, BubbleableMetadata $bubbleableMetadata): string {
    $element = [
      '#type' => 'processed_text',
      '#text' => $value,
      '#format' => $this->getTextProfile(),
    ];

    return (string) $this->renderer->render($element);
  }

  /**
   * Get the globally configured WYSIWYG text editor profile.
   *
   * @return string|null
   *   The machine name of the text profile configured in Patternkit settings.
   */
  public function getTextProfile(): ?string {
    return $this->patternkitSettings->get('patternkit_json_editor_ckeditor_toolbar');
  }

}
