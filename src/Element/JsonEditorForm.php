<?php

declare(strict_types=1);

namespace Drupal\patternkit\Element;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\Form\PatternLibraryJSONForm;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\PatternRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a render element to display a JSONEditor form.
 *
 * Properties:
 * - #pattern_name: The name of the pattern to be loaded and used for the form.
 * - #pattern_entity: (optional) The loaded Pattern entity for the form.
 * - #data: (optional) A serialized string of JSON values for the form.
 * - #config: (optional) An associative array of configuration options for
 *     JSONEditor behaviors.
 *
 * Usage Example:
 * @code
 * $build['jsoneditor_form'] = [
 *   '#type' => 'jsoneditor_form',
 *   '#pattern_name' => '@patternkit/atoms/example/src/example',
 *   '#config' => [
 *     'disablePropertiesButtons' => FALSE,
 *   ],
 * ];
 * @endcode
 *
 * @RenderElement("jsoneditor_form")
 *
 * @see \Drupal\patternkit\JsonEditorFormBuilder
 */
class JsonEditorForm extends FormElementBase implements ContainerFactoryPluginInterface {

  /**
   * Patternkit config values.
   *
   * @var array<string, string|bool>
   */
  protected array $config;

  /**
   * Assembled settings for the instance behavior of the JSON Editor form.
   *
   * @var array<string, mixed>
   */
  protected array $settings = [];

  /**
   * Prepared default behavior of the JSON Editor form before overrides.
   *
   * @var array<string, mixed>
   */
  protected array $defaults = [];

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#pre_render' => [
        [$this, 'loadPattern'],
        [$this, 'buildEditorForm'],
      ],
      '#process' => [
        [static::class, 'processEditorForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#pattern_name' => NULL,
      '#pattern_entity' => NULL,
      '#schema' => '',
      // Pattern instance configuration values.
      '#data' => '{}',
      // Editor configuration options to be overridden.
      '#config' => [],
    ];
  }

  /**
   * Constructs a JsonEditorForm plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\patternkit\PatternLibraryPluginManager $libraryPluginManager
   *   The pattern library parser plugin manager service.
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected PatternLibraryPluginManager $libraryPluginManager,
    protected PatternRepository $patternRepository,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.library.pattern'),
      $container->get('patternkit.pattern.repository'),
      $container->get('config.factory'),
    );
  }

  /**
   * Preload the pattern entity if not available yet.
   *
   * @param array $element
   *   An associative array containing the properties of the pattern element.
   *
   * @return array
   *   The modified element.
   *
   * @todo Resolve duplication with \Drupal\patternkit\Element\Pattern::loadPattern.
   */
  public function loadPattern(array $element): array {
    if (empty($element['#pattern_entity'])) {
      if (isset($element['#pattern_name']) && is_string($element['#pattern_name'])) {
        $pattern_id = $element['#pattern_name'];

        try {
          $element['#pattern_entity'] = $this->patternRepository->getPattern($pattern_id);
        }
        catch (UnknownPatternException $exception) {
          // Replace the pattern output with an error element for more
          // sophisticated output handling.
          // @see \Drupal\patternkit\Element\PatternError
          $element['error'] = [
            '#type' => 'pattern_error',
            '#pattern' => $element['#pattern_name'],
            '#config' => $element['#config'] ?? [],
            '#context' => $element['#context'] ?? [],
            '#exception' => $exception,
          ];

          return $element;
        }
      }
      else {
        $exception = new UnknownPatternException('No pattern specified for form creation.');

        $element['error'] = [
          '#type' => 'pattern_error',
          '#pattern' => '',
          '#exception' => $exception,
        ];

        return $element;
      }
    }

    $element['#schema'] = $element['#pattern_entity']->getSchema() ?? '';

    return $element;
  }

  /**
   * Build out the render array for the editor to be displayed.
   *
   * @param array $element
   *   An associative array containing the properties of the jsoneditor_form
   *   element.
   *
   * @return array
   *   The modified element.
   */
  public function buildEditorForm(array $element): array {
    // Stop early if the pattern entity is unavailable.
    if (empty($element['#pattern_entity']) || !($element['#pattern_entity'] instanceof PatternInterface)) {
      return $element;
    }

    // Add instructions to guide inclusion of token values.
    $element['configuration_description']['#markup'] = $this->t('Provide context for your pattern. You can use tokens and Twig in your values.');

    // Load the in-place configuration options for the editor to be passed in.
    $editor_overrides = $element['#config'] ?? [];

    // Assemble the full instance settings for the editor.
    $settings = $this->editorSettings($element['#schema'], $element['#data'] ?? '{}', $editor_overrides);

    // Load the editor from the pattern's library plugin.
    $pattern = $element['#pattern_entity'];
    $library = $this->libraryPluginManager->getPluginForPattern($pattern);
    $editor = $library->getPatternEditor($pattern, $settings);

    // If the editor is a render array, attach it directly. Otherwise, use the
    // returned string as the markup to be included.
    if (is_string($editor)) {
      $element['editor'] = [
        '#type' => 'inline_template',
        '#template' => $editor,
      ];
    }
    else {
      $element['editor'] = $editor;
    }

    return $element;
  }

  /**
   * Expands the form element to include a hidden field to track value.
   *
   * @param array $element
   *   The render element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param array $form
   *   The full form.
   *
   * @return array
   *   The updated render element.
   */
  public static function processEditorForm(&$element, FormStateInterface $form_state, array $form) {
    // Add a hidden field to capture configuration from the JSONEditor form.
    $element['instance_config'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'schema_instance_config',
      ],
      '#default_value' => $element['#data'] ?? '{}',
      '#parents' => $element['#parents'],
    ];

    return $element;
  }

  /**
   * Statically load the configured settings for default JSON Editor behaviors.
   *
   * @return array
   *   The prepared default settings for JSON Editor.
   */
  protected function editorSettings(string $schema, string $data, array $overrides = []): array {
    if (empty($this->settings)) {
      $defaults = $this->defaultEditorSettings();

      $this->settings = [
        'schemaJson' => $schema,
        // Replace an empty string with an empty JSON object for parsing.
        'startingJson' => ($data == '') ? '{}' : $data,
      ] + $overrides + $defaults;
    }

    return $this->settings;
  }

  /**
   * Statically load the configured settings for default JSON Editor behaviors.
   *
   * @return array<string, string|bool>
   *   The prepared default settings for JSON Editor.
   */
  protected function defaultEditorSettings(): array {
    if (empty($this->defaults)) {
      if (!isset($this->config)) {
        $config_object = $this->configFactory->get(PatternLibraryJSONForm::SETTINGS);
        $config_array = isset($config_object) ? $config_object->get() : [];

        // Map config names to JS-friendly identifiers.
        // @todo Remove this once internal config names are renamed.
        $mapped_config = [];
        $map = PatternLibraryJSONForm::JSON_EDITOR_SETTINGS_MAP;
        array_walk($config_array, function ($value, $key) use (&$mapped_config, $map) {
          if (array_key_exists($key, $map)) {
            // Relabel the value if a mapped key is found.
            $mapped_config[$map[$key]] = $value;
          }
          else {
            // Maintain the existing key if no mapped key is found.
            $mapped_config[$key] = $value;
          }
        });

        $this->config = $mapped_config;
      }

      $this->defaults = $this->config + [
        'theme' => 'cygnet',
        'icons' => '',
        'wysiwygEditorName' => '',
        'disablePropertiesButtons' => FALSE,
        'disableJsonEditButton' => TRUE,
        'ajaxCacheResponses' => FALSE,
      ];
    }

    return $this->defaults;
  }

}
