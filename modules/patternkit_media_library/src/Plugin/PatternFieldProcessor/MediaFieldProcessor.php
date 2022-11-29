<?php

namespace Drupal\patternkit_media_library\Plugin\PatternFieldProcessor;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\filter\FilterPluginManager;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorBase;
use Drupal\patternkit_media_library\Form\MediaLibrarySettingsForm;
use Swaggest\JsonSchema\SchemaContract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A pattern field processor for rendering media tokens into markup.
 *
 * This processor targets image and video media fields as the server-side
 * counterpart to the media-selection process where selected media values are
 * populated with a '<drupal-media>' token. When a field potentially containing
 * these tokens is identified, this processor parses the value with the
 * 'media_embed' text filter to replace the token with the appropriate rendered
 * markup for the referenced media entity.
 *
 * When processing schema values, this processor searches for schema properties
 * matching the following template:
 *
 * @code
 * {
 *   "type" => "string",
 *   "format" => "image-media|video-media"
 * }
 * @endcode
 *
 * @PatternFieldProcessor(
 *   id = "media",
 *   label = @Translation("Media"),
 *   description = @Translation("Media token content processor.")
 * )
 */
class MediaFieldProcessor extends PatternFieldProcessorBase {

  /**
   * Global settings for the Patternkit Media Library module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $settings;

  /**
   * The filter plugin manager service.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected FilterPluginManager $filterPluginManager;

  /**
   * The media embed filter plugin.
   *
   * @var \Drupal\filter\Plugin\FilterInterface
   */
  protected FilterInterface $filter;

  /**
   * Default configuration options for the media filter plugin.
   *
   * @var array
   */
  protected array $filterDefaults = [
    'settings' => [
      'default_view_mode' => 'patternkit',
    ],
  ];

  /**
   * Constructor for the MediaFieldProcessor plugin.
   *
   * @param array $configuration
   *   The plugin instance configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   Globally configured settings for the Patternkit Media Library module.
   * @param \Drupal\filter\FilterPluginManager $filterPluginManager
   *   The plugin manager for filter plugins.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, ImmutableConfig $settings, FilterPluginManager $filterPluginManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->settings = $settings;
    $this->filterPluginManager = $filterPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $configFactory = $container->get('config.factory');
    $settings = $configFactory->get(MediaLibrarySettingsForm::SETTINGS);
    $filterManager = $container->get('plugin.manager.filter');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $settings,
      $filterManager,
    );
  }

  /**
   * {@inheritdoc}
   *
   * Test for image or video media fields to be processed.
   *
   * @code
   * {
   *   "type" => "string",
   *   "format" => "image-media|video-media"
   * }
   * @endcode
   */
  public function applies(SchemaContract $propertySchema, $propertyValue = NULL): bool {
    // Fail early if there is no value to filter.
    return (bool) $propertyValue
      // Confirm all the properties are present on the property schema.
      && (
        property_exists($propertySchema, 'type')
        && property_exists($propertySchema, 'format')
        && $propertySchema->type == 'string'
      )
      // Test the format.
      && (
        $propertySchema->format == 'image-media'
        || $propertySchema->format == 'video-media'
      )
      // Don't bother with filtering if there isn't a token in the value.
      && strpos($propertyValue, '<drupal-media') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function apply(SchemaContract $propertySchema, $value, array $context, BubbleableMetadata $bubbleableMetadata): string {
    // @todo Load the active language.
    $output = $this->getFilter()->process($value, LanguageInterface::LANGCODE_DEFAULT);

    // @todo Apply bubbleable metadata.
    return $output->getProcessedText();
  }

  /**
   * Lazy-load the filter when it's needed.
   *
   * @return \Drupal\filter\Plugin\FilterInterface
   *   The loaded media token filter.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getFilter(): FilterInterface {
    if (!isset($this->filter)) {
      // Assign the default view mode from configuration if available.
      if ($view_mode = $this->settings->get('media_display_mode')) {
        $this->filterDefaults['settings']['default_view_mode'] = $view_mode;
      }

      $this->filter = $this->filterPluginManager->createInstance('media_embed', $this->filterDefaults);
    }

    return $this->filter;
  }

}
