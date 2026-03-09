<?php

declare(strict_types=1);

namespace Drupal\patternkit\Element;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\SchemaValidationException;
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
 * - #default_value: (optional) A serialized string of JSON values for the form.
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
 *   '#default_value' => '{"text": "My text value"}',
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
        [static::class, 'preRenderGroup'],
      ],
      '#process' => [
        [static::class, 'processAjaxForm'],
        [static::class, 'processGroup'],
        [static::class, 'processEditorForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#input' => TRUE,
      '#pattern_name' => NULL,
      '#pattern_entity' => NULL,
      '#schema' => '',
      // Editor configuration options to be overridden.
      '#config' => [],
      // Validate upon submissions.
      '#element_validate' => [
        [static::class, 'validatePatternConfig'],
      ],
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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
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

    // Always derive schema from the current pattern entity so rebuilt forms
    // use the latest schema value even when stale element properties persist
    // across AJAX rebuilds.
    $element['#schema'] = $element['#pattern_entity']->getSchema() ?? '';

    // Assemble the full instance settings for the editor.
    $value = $element['#value'] ?? $element['#default_value'] ?? '{}';
    $settings = $this->editorSettings($element['#schema'], $value, $editor_overrides);

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
    // Retrieve the form-submitted value if available, otherwise, default to the
    // original element data.
    $data = $form_state->getValue($element['#parents']);

    // Update the element data value to persist it to the next form build.
    // This ensures even if it was loaded from a previous form submission, the
    // element initialization data is synchronized with the submitted value.
    $element['#value'] = $data;

    // Add a hidden field to capture configuration from the JSONEditor form.
    $element['instance_config'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'schema_instance_config',
      ],
      '#value' => $data,
      '#parents' => $element['#parents'],
    ];

    return $element;
  }

  /**
   * Validates the pattern configuration.
   *
   * @param array $element
   *   The render element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param array $form
   *   The full form.
   *
   * @see \Drupal\patternkit\Element\JsonEditorForm::getInfo()
   */
  public static function validatePatternConfig(&$element, FormStateInterface $form_state, array $form): void {
    $value = $form_state->getValue($element['#parents']);
    $pattern = $element['#pattern_entity'];
    $schema = $pattern->getSchema();
    $validationHelper = \Drupal::service('patternkit.helper.validation');
    $validationHelper->validateContent($schema, $value);
    if ($validationHelper->hasExceptions()) {

      $message = 'Content has failed to validate. See errors below.';

      // Log additional information if debug mode is enabled.
      /** @var \Drupal\patternkit\PatternkitEnvironment $environment */
      $environment = \Drupal::service('patternkit.environment');
      if ($environment->isDebug()) {
        $exceptions = $validationHelper->getExceptions();
        $exception = array_shift($exceptions);
        if ($exception instanceof SchemaValidationException && $previous = $exception->getPrevious()) {
          $message .= "\n" . $previous->getMessage();
        }
        else {
          $message .= "\n" . $exception->getMessage();
        }
      }

      $form_state->setError($element, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return is_string($input) ? $input : NULL;
  }

  /**
   * Builds settings for the JSON Editor (schema, starting data, defaults).
   *
   * Combines schema, starting data, overrides, and defaults into the settings
   * payload consumed by the pattern editor.
   *
   * Settings are cached in $this->settings for reuse within the element
   * instance to avoid rebuilding the same payload on repeated calls.
   *
   * @param string $schema
   *   The JSON schema string for the pattern editor.
   * @param string $data
   *   The starting JSON data for the editor form.
   * @param array $overrides
   *   Optional settings overrides from element configuration.
   *
   * @return array
   *   The prepared default settings for JSON Editor.
   */
  protected function editorSettings(string $schema, string $data, array $overrides = []): array {
    if (empty($this->settings) || ($this->settings['schemaJson'] ?? '') !== $schema) {
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

        // @todo Remove this once internal config names are renamed.
        $this->config = $this->mapConfigNames($config_array);
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

  /**
   * Map server-side configuration values to JS names.
   *
   * @param mixed $config_array
   *   The server-side configuration values to be mapped to JS names.
   *
   * @return array
   *   An array of config mapped to JS-friendly names.
   *
   * @todo Remove this once internal config names are renamed.
   */
  private function mapConfigNames(mixed $config_array): array {
    // Map config names to JS-friendly identifiers.
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

    return $mapped_config;
  }

}
