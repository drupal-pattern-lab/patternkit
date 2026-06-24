<?php

declare(strict_types=1);

namespace Drupal\patternkit;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Asset\AssetQueryStringInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\Plugin\EditorManager;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Form\PatternLibraryJSONForm;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

/**
 * A helper service to assist building a JSONEditor form into a render array.
 */
class JsonEditorFormBuilder implements PatternFormBuilderInterface, LoggerAwareInterface {

  use LoggerAwareTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The query string service.
   *
   * @var \Drupal\Core\Asset\AssetQueryStringInterface
   */
  protected AssetQueryStringInterface $queryString;

  /**
   * The patternkit settings configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The editor plugin manager service.
   *
   * @var \Drupal\editor\Plugin\EditorManager
   */
  protected EditorManager $editorPluginManager;

  /**
   * The entity storage handler for editor entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $editorStorage;

  /**
   * Track whether editor support is available.
   *
   * @var bool
   */
  private bool $supportsEditor;

  /**
   * Constructs a JsonEditorFormBuilder object.
   *
   * @param \Drupal\patternkit\PatternLibraryPluginManager $pluginManagerLibraryPattern
   *   The pattern library plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   */
  public function __construct(
    protected readonly PatternLibraryPluginManager $pluginManagerLibraryPattern,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly StateInterface $state,
    protected $messenger,
    protected $stringTranslation,
  ) {}

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array{
   *   theme?: string,
   *   icons?: string,
   *   wysiwygEditorName?: string,
   *   disablePropertiesButtons?: bool,
   *   disableJsonEditButton?: bool,
   *   ajaxCacheResponse?: bool,
   *   ajaxCacheBuster?: string,
   *   schemaJson?: string,
   *   startingJson?: string,
   * } $settings
   *
   * @todo Make $pattern param optional and add schema value to $settings if provided.
   */
  public function buildEditorForm(?PatternInterface $pattern = NULL, array $settings = []): array {
    // Load the pattern schema into settings if provided.
    if ($pattern !== NULL) {
      $settings['schemaJson'] = $pattern->getSchema();
    }
    else {
      Assert::keyExists($settings, 'schemaJson', 'If no pattern is provided, the schema should be assigned in $settings["schemaJson"].');
      Assert::string($settings['schemaJson'], 'The schema JSON value assigned to "schemaJson" should be a string.');
    }

    // Ensure a cache buster string is prepared.
    if (empty($settings['ajaxCacheBuster'])) {
      $settings['ajaxCacheBuster'] = $this->getQueryString();
    }

    // Build the placeholder for the JSONEditor instance to render within.
    $editor = [
      '#type'     => 'html_tag',
      '#tag'      => 'div',
      '#value'    => '',
      '#attributes' => [
        'id' => 'patternkit-editor-target',
        'style' => ['all: initial; background: white; display: inline-block; width: 100%;'],
      ],
      '#attached' => [
        'drupalSettings' => [
          'patternkitEditor' => $settings,
        ],
        'library' => [
          'patternkit/patternkit.jsoneditor',
        ],
      ],
      'holder' => [
        '#type'     => 'html_tag',
        '#tag'      => 'div',
        '#attributes' => [
          'id' => 'editor_holder',
        ],
      ],
    ];

    // Attach theme and icon libraries if necessary.
    $this->attachAssets($editor, $settings);

    // Add necessary WYSIWYG dependencies.
    if ($this->supportsWysiwygEditor()) {
      $this->prepareWysiwygEditor($editor);
    }

    return $editor;
  }

  /**
   * Attach necessary assets for theme and icon selections.
   *
   * @param array $form
   *   The form element being assembled. This is altered directly.
   * @param array $settings
   *   The settings for the behavior of the form.
   */
  protected function attachAssets(array &$form, array $settings): void {
    // Identify attachments for selected theme and icon sets.
    if (!empty($settings['theme'])) {
      $theme_attachments = $this->getThemeAttachments($settings['theme']);
    }
    if (!empty($settings['icons']) && $settings['icons'] !== '_none') {
      $icon_attachments = $this->getIconAttachments($settings['icons']);
    }

    // Merge the identified attachments for inclusion in the render array.
    $attachments = BubbleableMetadata::mergeAttachments($theme_attachments ?? [], $icon_attachments ?? []);
    $form['#attached'] = BubbleableMetadata::mergeAttachments($form['#attached'] ?? [], $attachments);
  }

  /**
   * Load and attach dependencies for selected WYSIWYG editors.
   *
   * @param array $element
   *   The render element for the JSONEditor form element. This value is passed
   *   by reference and altered directly.
   */
  protected function prepareWysiwygEditor(array &$element): void {
    $config = $this->config();

    // Stop here if an accepted editor was not selected.
    $accepted_editors = ['ckeditor5'];
    $editor = $config['patternkit_json_editor_wysiwyg'];
    if (empty($editor) || !is_string($editor) || !in_array($editor, $accepted_editors)) {
      // @todo Log a warning if a non-existent or ineligible toolbar was selected.
      return;
    }

    if (empty($selected_toolbar = $config['patternkit_json_editor_ckeditor_toolbar'])) {
      // Stop here if no toolbar was selected.
      return;
    }

    // Attempt to load the editor for the selected toolbar.
    try {
      /** @var \Drupal\editor\Plugin\EditorPluginInterface $editor_instance */
      $editor_instance = $this->getEditorPluginManager()->createInstance($editor);
    }
    catch (PluginException $exception) {
      // Log the error and stop here if the editor could not be loaded.
      $msg = $this->t('Unable to load plugin for editor %editor. %exception', [
        '%editor' => $editor,
        '%exception' => $exception->getMessage(),
      ]);
      $this->logger->error($msg);
      $this->messenger()->addError($msg);
      return;
    }

    if ($editor_instance) {
      /** @var \Drupal\editor\Entity\Editor|null $editor_entity */
      $editor_entity = $this->editorStorage->load($selected_toolbar);
      if ($editor_entity && $editor_entity->status()) {
        $editor_settings = &$element['#attached']['drupalSettings']['patternkitEditor'];
        $editor_settings['wysiwygEditorName'] = $editor;
        $editor_settings['patternkitCKEditorConfig'] = $editor_instance->getJSSettings($editor_entity);
        // Pushes the selected toolbar to drupalSettings, for use client-side
        // in DrupalCKEditor5.afterInputReady.
        $editor_settings['patternkitCKEditorConfig']['selected_toolbar'] = $selected_toolbar;
      }
      else {
        $msg = $this->t('Missing Editor entity @name', ['@name' => $selected_toolbar]);
        $this->logger->error($msg);
        $this->messenger()->addError($msg);
      }
    }
    else {
      $msg = $this->t('Unable to create WYSIWYG editor instance for @name', ['@name' => $editor]);
      $this->logger->error($msg);
      $this->messenger()->addError($msg);
    }

    // Attaches attachments for the selected editor.
    if ($editor_plugin_manager = $this->getEditorPluginManager()) {
      $attachments = $editor_plugin_manager->getAttachments([$selected_toolbar]);
      $element['#attached'] = BubbleableMetadata::mergeAttachments($element['#attached'], $attachments);
    }
  }

  /**
   * Statically load the configuration values for the JSONEditor form.
   *
   * @return array
   *   Configured settings for the JSONEditor form.
   */
  protected function config(): array {
    if (!isset($this->config)) {
      $this->config = $this->configFactory->get(PatternLibraryJSONForm::SETTINGS);
    }

    return $this->config->get();
  }

  /**
   * Get the editor plugin manager service if available.
   *
   * @return \Drupal\editor\Plugin\EditorManager|null
   *   The editor plugin manager service or null if not available.
   */
  protected function getEditorPluginManager(): ?EditorManager {
    return $this->editorPluginManager ?? NULL;
  }

  /**
   * Set the editor plugin manager service.
   *
   * @param \Drupal\editor\Plugin\EditorManager $editor_plugin_manager
   *   The editor plugin manager service.
   *
   * @codeCoverageIgnore
   */
  public function setEditorPluginManager(EditorManager $editor_plugin_manager): void {
    $this->editorPluginManager = $editor_plugin_manager;
  }

  /**
   * Get the request query string.
   *
   * @return string
   *   The request query string.
   */
  protected function getQueryString(): string {
    if (!empty($this->queryString)) {
      return $this->queryString->get();
    }
    else {
      return $this->state->get('system.css_js_query_string', '0');
    }
  }

  /**
   * Set the query string service.
   *
   * @param \Drupal\Core\Asset\AssetQueryStringInterface $query_string
   *   The query string service.
   *
   * @codeCoverageIgnore
   */
  public function setQueryStringService(AssetQueryStringInterface $query_string): void {
    $this->queryString = $query_string;
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
      try {
        if ($this->entityTypeManager->hasHandler('editor', 'storage')) {
          $this->editorStorage = $this->entityTypeManager->getStorage('editor');
        }
      }
      catch (PluginException) {
        // Ignore the exception since we'll return false later.
      }

      $this->supportsEditor = isset($this->editorStorage, $this->editorPluginManager);
    }

    return $this->supportsEditor;
  }

  /**
   * Lookup asset attachments for the selected theme.
   *
   * @param string $theme
   *   The name of the theme to be used.
   *
   * @return array
   *   A render array attachments array for assignment to the '#attached' key.
   *
   * @todo Reimplement with ability to alter results allowing third-party
   *   modules to introduce new theme options.
   */
  protected function getThemeAttachments(string $theme): array {
    // Fail here if no theme info is found for this name.
    if (empty(PatternLibraryJSONForm::THEMES[$theme])) {
      $this->logger->error('Unable to find theme information for selected theme %theme.', [
        '%theme' => $theme,
      ]);
      return [];
    }

    $library = PatternLibraryJSONForm::THEMES[$theme];
    return [
      'library' => [
        $library,
      ],
      'drupalSettings' => [
        'patternkitEditor' => [
          'theme' => $theme,
        ],
      ],
    ];
  }

  /**
   * Lookup asset attachments for the selected icon sets.
   *
   * @param string $icons
   *   The name of the icon set to be used.
   *
   * @return array
   *   A render array attachments array for assignment to the '#attached' key.
   *
   * @todo Reimplement with ability to alter results allowing third-party
   *   modules to introduce new icon options.
   */
  protected function getIconAttachments(string $icons): array {
    // Fail here if no theme info is found for this name.
    if (empty(PatternLibraryJSONForm::ICONS[$icons])) {
      $this->logger->error('Unable to find theme information for selected icons %icons.', [
        '%icons' => $icons,
      ]);
      return [];
    }

    $library = PatternLibraryJSONForm::ICONS[$icons];
    return [
      'library' => [
        $library,
      ],
      'drupalSettings' => [
        'patternkitEditor' => [
          'icons' => $icons,
        ],
      ],
    ];
  }

}
