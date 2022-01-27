<?php

namespace Drupal\patternkit;

use Drupal\ckeditor\Plugin\Editor\CKEditor;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\editor\Entity\Editor;
use Drupal\patternkit\Form\PatternLibraryJSONForm;

/**
 * Adds a schema editor render array generator without needing a full service.
 */
trait JSONSchemaEditorTrait {

  use MessengerTrait;
  use LoggerChannelTrait;

  /**
   * Patternkit config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Encodes and decodes strings.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * Application static state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
  public function getLibraryAssetUrlFromUri($uri): string {
    $url = file_create_url($uri);
    if ($url !== $uri) {
      $query_string = $this->state->get('system.css_js_query_string') ?: '0';
      $query_string_separator = (strpos($url, '?') !== FALSE) ? '&' : '?';
      $url = file_url_transform_relative($url) . $query_string_separator . $query_string;
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
  public function schemaEditor($schema, PatternEditorConfig $config): array {
    $starting_json = $config !== NULL
      ? $this->serializer::encode($config->fields)
      : $config;
    $theme = $this->config->get('patternkit_json_editor_theme');
    $icons = $this->config->get('patternkit_json_editor_icons');
    $editor_settings = [
      'hostname' => $config->hostname,
      'icons' => $icons,
      'schemaJson' => $schema,
      'startingJson' => $starting_json,
      'theme' => $theme,
      'wysiwygEditorName' => $this->config->get('patternkit_json_editor_wysiwyg') ?? '',
      'useShadowDom' => $this->config->get('patternkit_json_editor_use_shadow_dom') ?? TRUE,
      'disablePropertiesButtons' => $this->config->get('patternkit_json_editor_disable_properties_buttons') ?? FALSE,
    ];

    if (isset(PatternLibraryJSONForm::THEMES[$theme])) {
      $theme_info = PatternLibraryJSONForm::THEMES[$theme];
      if (!$editor_settings['useShadowDom'] && !empty($theme_info['css_no_shadow_dom'])) {
        $editor_settings['themeStylesheet'] = $this->getLibraryAssetUrlFromUri($theme_info['css_no_shadow_dom']);
      }
      elseif (!empty($theme_info['css'])) {
        $editor_settings['themeStylesheet'] = $this->getLibraryAssetUrlFromUri($theme_info['css']);
      }
      else {
        $editor_settings['themeStylesheet'] = '';
      }
      if (!empty($theme_info['js'])) {
        $editor_settings['themeJS'][] = $this->getLibraryAssetUrlFromUri($theme_info['js']);
      }
    }
    if (!empty(PatternLibraryJSONForm::ICONS[$icons])) {
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

    if ($this->config->get('patternkit_json_editor_wysiwyg') == 'ckeditor') {
      $selected_toolbar = $this->config->get('patternkit_json_editor_ckeditor_toolbar');
      if (!empty($selected_toolbar)) {
        // The following code builds a CKEditor toolbar. Code stolen from
        // \Drupal\editor\Element::preRenderTextFormat.
        $ckeditor_instance = CKEditor::create(\Drupal::getContainer(), [], 'ckeditor', ['provider' => 'patternkit']);
        /** @var \Drupal\editor\Entity\Editor $ckeditor_config */
        $ckeditor_entity = Editor::load($selected_toolbar);
        if ($ckeditor_entity && $ckeditor_entity->status()) {
          $editor_settings['patternkitCKEditorConfig'] = $ckeditor_instance->getJSSettings($ckeditor_entity);
          // Pushes the selected toolbar to drupalSettings, so that client-side so
          // that DrupalCKEditor.afterInputReady
          $editor_settings['patternkitCKEditorConfig']['selected_toolbar'] = $selected_toolbar;
        }
        else {
          $msg = $this->t('Missing Editor entity @name', ['@name' => $selected_toolbar]);
          $this->getLogger('patternkit')->error($msg);
          $this->messenger()->addError($msg);
        }
      }
    }

    // @todo Move to own JS file & Drupal Settings config var.
    $element = [
      '#type'     => 'html_tag',
      '#tag'      => 'div',
      '#value'    => '',
      '#attributes' => [
        'id' => 'patternkit-editor-target',
        'style' => ['all: initial; background: white; display: inline-block; width: 100%;']
      ],
      '#attached' => [
        'drupalSettings' => [
          'patternkitEditor' => $editor_settings,
        ],
        'library' => [
          'patternkit/patternkit.jsoneditor',
          'ckeditor/drupal.ckeditor',
          'editor/drupal.editor',
        ],
      ],
    ];

    // Attaches attachments for selected editor.
    if (!empty($selected_toolbar)) {
      /** @var \Drupal\editor\Plugin\EditorManager $editor_plugin_manager */
      $editor_plugin_manager = \Drupal::service('plugin.manager.editor');
      $element['#attached'] = BubbleableMetadata::mergeAttachments($element['#attached'], $editor_plugin_manager->getAttachments([$selected_toolbar]));
    }

    return $element;
  }

}
