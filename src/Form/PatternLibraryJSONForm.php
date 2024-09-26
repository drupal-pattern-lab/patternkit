<?php

namespace Drupal\patternkit\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for configuring the JSON Library Plugin.
 *
 * @todo Simplify config value names to remove prefix.
 * @todo Rename config values to align with JSONEditor identifiers.
 * @todo Add input to specify additional settings to pass to JSONEditor.
 */
class PatternLibraryJSONForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Settings identifier.
   *
   * @var string
   */
  public const SETTINGS = 'patternkit.settings';

  /**
   * Array of themes supported by the JSON Schema Editor.
   *
   * @var array
   *
   * @see https://github.com/json-editor/json-editor
   */
  public const THEMES = [
    'cygnet' => 'patternkit/cygnet_no_shadow_dom',
  ];

  /**
   * Array of icons supported by the JSON Schema Editor.
   *
   * @var array
   *
   * @see https://github.com/json-editor/json-editor
   *
   * @todo Move to yml config.
   */
  public const ICONS = [
    'fontawesome3' => 'patternkit/icons.fontawesome3',
    'fontawesome4' => 'patternkit/icons.fontawesome4',
    'fontawesome5' => 'patternkit/icons.fontawesome5',
  ];

  /**
   * Array of editor options supported by the JSON Schema Editor.
   *
   * @var array
   *
   * @see self::getAllowedWysiwygEditors()
   */
  public const EDITORS = [
    'ckeditor5' => 'CKEditor5',
  ];

  /**
   * A map of config keys to JSON Editor JS identifiers.
   *
   * @var array<string, string>
   *
   * @todo Remove this once config names are directly renamed.
   * @internal
   */
  public const JSON_EDITOR_SETTINGS_MAP = [
    'patternkit_json_editor_theme' => 'theme',
    'patternkit_json_editor_icons' => 'icons',
    'patternkit_json_editor_disable_properties_buttons' => 'disablePropertiesButtons',
    'patternkit_json_editor_disable_edit_json_button' => 'disableJsonEditButton',
    'patternkit_json_editor_cache_schemas' => 'ajaxCacheResponses',
    'patternkit_json_editor_wysiwyg' => 'wysiwygEditorName',
  ];

  /**
   * Track whether editor support is available.
   *
   * @var bool
   */
  private bool $supportsEditor;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) :array {
    $config = $this->config(static::SETTINGS);

    $themes = array_keys(static::THEMES);
    $form['patternkit_json_editor_theme'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Select the JSON Editor Theme'),
      '#options' => array_combine($themes, $themes),
      '#default_value' => $config->get('patternkit_json_editor_theme'),
    ];

    $icons = array_keys(static::ICONS);
    $form['patternkit_json_editor_icons'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the icons to be used with the editor'),
      '#required' => FALSE,
      '#options' => array_combine($icons, $icons),
      '#empty_value' => '_none',
      '#default_value' => $config->get('patternkit_json_editor_icons'),
    ];

    $form['patternkit_json_editor_disable_properties_buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the properties buttons'),
      '#description' => $this->t('Select whether to hide the property buttons (maps to json-editor option <em>disable_properties</em>).'),
      '#default_value' => $config->get('patternkit_json_editor_disable_properties_buttons') ?? FALSE,
    ];

    $form['patternkit_json_editor_disable_edit_json_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the JSON Edit button'),
      '#description' => $this->t('Select whether to show the Edit JSON button (maps to json-editor option <em>disable_edit_json</em>).'),
      '#default_value' => $config->get('patternkit_json_editor_disable_edit_json_button') ?? TRUE,
    ];

    $form['patternkit_json_editor_cache_schemas'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cache external schema references'),
      '#description' => $this->t('Select whether JSON Editor should cache schema lookups, which can speed up the editor form (maps to JSON Editor option <em>ajax_cache_responses</em>).'),
      '#default_value' => $config->get('patternkit_json_editor_cache_schemas') ?? FALSE,
    ];

    $form['patternkit_json_editor_token_clear'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace empty token values'),
      '#description' => $this->t('Remove tokens from text if they cannot be replaced with a value.'),
      '#default_value' => $config->get('patternkit_json_editor_token_clear') ?? FALSE,
    ];

    // Add necessary WYSIWYG dependencies.
    if ($this->supportsWysiwygEditor()) {
      $form['patternkit_json_editor_wysiwyg'] = [
        '#type' => 'select',
        '#title' => $this->t('WYSIWYG editor'),
        '#options' => $this->getAllowedWysiwygEditors(),
        '#empty_value' => '',
        '#default_value' => $config->get('patternkit_json_editor_wysiwyg') ?: '',
      ];

      $ckeditor_toolbar_options = [];
      foreach ($this->entityTypeManager->getStorage('editor')->loadMultiple() as $editor) {
        $accepted_editors = [
          'ckeditor5',
        ];
        /** @var \Drupal\editor\EditorInterface $editor */
        if (in_array($editor->getEditor(), $accepted_editors)) {
          $ckeditor_toolbar_options[$editor->id()] = $editor->label();
        }
      }
      asort($ckeditor_toolbar_options, SORT_NATURAL);

      $form['patternkit_json_editor_ckeditor_toolbar'] = [
        '#type' => 'select',
        '#title' => $this->t('CKEditor toolbar'),
        '#options' => $ckeditor_toolbar_options,
        '#empty_value' => '',
        '#default_value' => $config->get('patternkit_json_editor_ckeditor_toolbar') ?: '',
        '#states' => [
          'visible' => [
            ':input[name="patternkit_json_editor_wysiwyg"]' => [
              ['value' => 'ckeditor5'],
            ],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [static::SETTINGS];
  }

  /**
   * Get a list of options for allowed WYSIWYG editors to select.
   *
   * @return array
   *   An options list of WYSIWYG editors with all dependencies available.
   */
  protected function getAllowedWysiwygEditors(): array {
    // Remove options from supported editors if their required modules are not
    // enabled.
    $excluded = [];

    if (!$this->moduleHandler->moduleExists('ckeditor5')) {
      $excluded[] = 'CKEditor5';
    }

    return array_diff(static::EDITORS, $excluded);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'patternkit_json_editor_config';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($this->supportsWysiwygEditor()) {
      // Validate editor and WYSIWYG toolbar selections.
      $wysiwyg = $form_state->getValue('patternkit_json_editor_wysiwyg');
      $toolbar = $form_state->getValue('patternkit_json_editor_ckeditor_toolbar');
      $supported_wysiwygs = ['ckeditor5'];

      if ($toolbar && in_array($wysiwyg, $supported_wysiwygs)) {
        $editor_storage = $this->entityTypeManager->getStorage('editor');
        /** @var \Drupal\editor\EditorInterface|null $editor */
        $editor = $editor_storage->load($toolbar);

        if ($editor === NULL || $editor->getEditor() !== $wysiwyg) {
          $form_state->setErrorByName('patternkit_json_editor_ckeditor_toolbar', $this->t(
            'The selected toolbar is not configured to use the selected editor. Expected to find "@expected", but found "@actual" instead.',
            [
              '@expected' => $wysiwyg,
              '@actual' => $editor->getEditor(),
            ]
          ));
        }
      }
    }
  }

  /**
   * Tests whether requirements for loading editor plugins are available.
   *
   * @return bool
   *   TRUE if all requirements for loading an editor are available, otherwise
   *   FALSE.
   */
  protected function supportsWysiwygEditor(): bool {
    // If we've already tested this return the previous result.
    if (isset($this->supportsEditor)) {
      return $this->supportsEditor;
    }
    else {
      $this->supportsEditor = FALSE;
      try {
        if ($this->entityTypeManager->hasHandler('editor', 'storage')) {
          $this->supportsEditor = TRUE;
        }
      }
      catch (PluginException) {
        // Do nothing and return.
      }
    }

    return $this->supportsEditor;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_values = $form_state->getValues();
    $config = $this->config(self::SETTINGS);
    $config->set('patternkit_json_editor_theme', $form_values['patternkit_json_editor_theme']);
    $config->set('patternkit_json_editor_icons', $form_values['patternkit_json_editor_icons']);
    $config->set('patternkit_json_editor_disable_properties_buttons', (bool) $form_values['patternkit_json_editor_disable_properties_buttons']);
    $config->set('patternkit_json_editor_disable_edit_json_button', (bool) $form_values['patternkit_json_editor_disable_edit_json_button']);
    $config->set('patternkit_json_editor_cache_schemas', (bool) $form_values['patternkit_json_editor_cache_schemas']);
    $config->set('patternkit_json_editor_token_clear', (bool) $form_values['patternkit_json_editor_token_clear']);

    // Set empty string values if not set; depends on the $this->buildForm()
    // usage of $this->supportsWysiwyg().
    $editor = $form_values['patternkit_json_editor_wysiwyg'] ?? '';
    $config->set('patternkit_json_editor_wysiwyg', $editor);
    $toolbar = $form_values['patternkit_json_editor_ckeditor_toolbar'] ?? '';
    $config->set('patternkit_json_editor_ckeditor_toolbar', $toolbar);

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
