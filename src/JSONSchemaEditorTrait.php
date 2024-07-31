<?php

namespace Drupal\patternkit;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Asset\AssetQueryStringInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\State\StateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorManager;
use Drupal\patternkit\Form\PatternLibraryJSONForm;

/**
 * Adds a schema editor render array generator without needing a full service.
 *
 * @deprecated in patternkit:9.1.0-beta10 and is removed from patternkit:9.1.0. Use JsonEditorFormBuilder instead.
 * @see https://www.drupal.org/project/patternkit/issues/3386854
 * @see \Drupal\patternkit\JsonEditorFormBuilder
 *
 * @codeCoverageIgnore
 */
trait JSONSchemaEditorTrait {

  use MessengerTrait;
  use LoggerChannelTrait;

  /**
   * Patternkit config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Encodes and decodes strings.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected SerializationInterface $serializer;

  /**
   * Application static state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The file url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * The editor plugin manager service.
   *
   * @var \Drupal\editor\Plugin\EditorManager
   */
  protected EditorManager $editorPluginManager;

  /**
   * The query string service.
   *
   * @var \Drupal\Core\Asset\AssetQueryStringInterface
   */
  protected AssetQueryStringInterface $queryString;

  /**
   * Returns a fully-qualified asset URL from a URI.
   *
   * @param string $uri
   *   A URI that includes a scheme.
   *
   * @return string
   *   A fully-qualified asset URL.
   *
   * @see \Drupal\Core\Asset\CssCollectionRenderer::render
   *
   * @todo Eval if this belongs in PatternkitLibraryDiscovery instead.
   */
  public function getLibraryAssetUrlFromUri(string $uri): string {
    $url = $this->fileUrlGenerator->generateAbsoluteString($uri);
    if ($url !== $uri) {
      $query_string = $this->getQueryString();
      $query_string_separator = str_contains($url, '?') ? '&' : '?';
      $url = $this->fileUrlGenerator->transformRelative($url) . $query_string_separator . $query_string;
    }
    return $url;
  }

  /**
   * Returns a schema editor Drupal render array.
   *
   * @param string $schema
   *   JSON Schema to display.
   * @param \Drupal\patternkit\PatternEditorConfig $config
   *   Editor configuration settings.
   *
   * @return array
   *   Schema editor Drupal render array.
   */
  public function schemaEditor(string $schema, PatternEditorConfig $config): array {
    $starting_json = $config !== NULL
      ? $this->serializer::encode($config->fields)
      : $config;

    $query_string = $this->getQueryString();
    if ($query_string === '0') {
      $query_string = '';
    }

    $theme = $this->config->get('patternkit_json_editor_theme');
    $icons = $this->config->get('patternkit_json_editor_icons');
    $editor_settings = [
      'hostname' => $config->hostname,
      'icons' => $icons,
      'schemaJson' => $schema,
      'startingJson' => $starting_json,
      'theme' => $theme,
      'wysiwygEditorName' => $this->config->get('patternkit_json_editor_wysiwyg') ?? '',
      'disablePropertiesButtons' => $this->config->get('patternkit_json_editor_disable_properties_buttons') ?? FALSE,
      'disableJsonEditButton' => $this->config->get('patternkit_json_editor_disable_edit_json_button') ?? TRUE,
      'ajaxCacheResponses' => $this->config->get('patternkit_json_editor_cache_schemas') ?? FALSE,
      'ajaxCacheBuster' => $query_string,
    ];

    if (isset(PatternLibraryJSONForm::THEMES[$theme])) {
      $theme_info = PatternLibraryJSONForm::THEMES[$theme];
      if (isset($theme_info['default'])) {
        $json_editor_theme_library_name = $theme_info['default'];
      }
      elseif (isset($theme_info['shadow_dom'])) {
        $json_editor_theme_library_name = $theme_info['shadow_dom'];
      }
    }
    if (!empty($icons) && $icons !== '_none' && !empty(PatternLibraryJSONForm::ICONS[$icons])) {
      $editor_settings['iconStylesheet'] = $this->getLibraryAssetUrlFromUri(PatternLibraryJSONForm::ICONS[$icons]);
    }
    $editor_css_override = $this->config->get('patternkit_json_editor_css');
    if (!empty($editor_css_override)) {
      $css_files = explode(',', $editor_css_override);
      foreach ($css_files as $css) {
        $editor_settings['themeCSS'][] = $this->getLibraryAssetUrlFromUri(trim($css));
      }
    }
    $editor_js_override = $this->config->get('patternkit_json_editor_js');
    if (!empty($editor_js_override)) {
      $js_files = explode(',', $editor_js_override);
      foreach ($js_files as $js) {
        $editor_settings['themeJS'][] = $this->getLibraryAssetUrlFromUri(trim($js));
      }
    }

    // @todo Move to own JS file & Drupal Settings config var.
    $element = [
      '#type'     => 'html_tag',
      '#tag'      => 'div',
      '#value'    => '',
      '#attributes' => [
        'id' => 'patternkit-editor-target',
        'style' => ['all: initial; background: white; display: inline-block; width: 100%;'],
      ],
      '#attached' => [
        'drupalSettings' => [
          'patternkitEditor' => $editor_settings,
        ],
        'library' => [
          'patternkit/patternkit.jsoneditor',
        ],
      ],
    ];

    if (isset($json_editor_theme_library_name)) {
      $element['#attached']['library'][] = $json_editor_theme_library_name;
    }
    $this->prepareWysiwygEditor($element);

    return $element;
  }

  /**
   * Load and attach dependencies for selected WYSIWYG editors.
   *
   * @param array $element
   *   The render element for the JSONEditor form element. This value is passed
   *   by reference and altered directly.
   */
  protected function prepareWysiwygEditor(array &$element): void {
    $accepted_editors = [
      'ckeditor5' => CKEditor5::class,
    ];
    $editor = $this->config->get('patternkit_json_editor_wysiwyg');
    if (empty($editor) || !is_string($editor) || !array_key_exists($editor, $accepted_editors)) {
      // Stop here if an accepted editor was not selected.
      return;
    }

    if (empty($selected_toolbar = $this->config->get('patternkit_json_editor_ckeditor_toolbar'))) {
      // Stop here if no toolbar was selected.
      return;
    }

    // Attempt to load the editor for the selected toolbar.
    if (class_exists($accepted_editors[$editor])) {
      $ckeditor_instance = $accepted_editors[$editor]::create(\Drupal::getContainer(), [], $editor, ['provider' => 'patternkit']);
    }
    else {
      // Log the error and stop here if the editor could not be loaded.
      $msg = $this->t('Unable to load class for editor %editor. Expected to find %class.', [
        '%editor' => $editor,
        '%class' => $accepted_editors[$editor],
      ]);
      $this->getLogger('patternkit')->error($msg);
      $this->messenger()->addError($msg);
      return;
    }

    if ($ckeditor_instance) {
      /** @var \Drupal\editor\Entity\Editor $ckeditor_entity */
      $ckeditor_entity = Editor::load($selected_toolbar);
      if ($ckeditor_entity && $ckeditor_entity->status()) {
        $editor_settings = &$element['#attached']['drupalSettings']['patternkitEditor'];
        $editor_settings['patternkitCKEditorConfig'] = $ckeditor_instance->getJSSettings($ckeditor_entity);
        // Pushes the selected toolbar to drupalSettings, for use clientside
        // in DrupalCKEditor5.afterInputReady.
        $editor_settings['patternkitCKEditorConfig']['selected_toolbar'] = $selected_toolbar;
      }
      else {
        $msg = $this->t('Missing Editor entity @name', ['@name' => $selected_toolbar]);
        $this->getLogger('patternkit')->error($msg);
        $this->messenger()->addError($msg);
      }
    }
    else {
      $msg = $this->t('Unable to create WYSIWYG editor instance for @name', ['@name' => $editor]);
      $this->getLogger('patternkit')->error($msg);
      $this->messenger()->addError($msg);
    }

    // Attach library dependencies.
    $element['#attached']['library'][] = 'editor/drupal.editor';
    if ($editor == 'ckeditor5') {
      $element['#attached']['library'][] = 'core/ckeditor5.editorClassic';
    }

    // Attaches attachments for the selected editor.
    if (!empty($selected_toolbar) && $editor_plugin_manager = $this->getEditorPluginManager()) {
      $attachments = $editor_plugin_manager->getAttachments([$selected_toolbar]);
      $element['#attached'] = BubbleableMetadata::mergeAttachments($element['#attached'], $attachments);
    }
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
   */
  protected function setEditorPluginManager(EditorManager $editor_plugin_manager): void {
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
   */
  protected function setQueryStringService(AssetQueryStringInterface $query_string): void {
    $this->queryString = $query_string;
  }

}
