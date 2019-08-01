<?php

namespace Drupal\patternkit;

use Drupal\patternkit\Form\PatternLibraryJSONForm;

/**
 * Adds a schema editor render array generator without needing a full service.
 */
trait JSONSchemaEditorTrait {

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
   * @param array $schema
   *   JSON Schema to display.
   * @param \Drupal\patternkit\PatternEditorConfig $config
   *   Editor configuration settings.
   *
   * @return array
   *   Schema editor Drupal render array.
   */
  public function schemaEditor(array $schema, PatternEditorConfig $config): array {
    $schema_json = $this->serializer::encode($schema);
    $starting_json = $config !== NULL
      ? $this->serializer::encode($config->fields)
      : $config;
    $theme = $this->config->get('patternkit_json_editor_theme');
    $icons = $this->config->get('patternkit_json_editor_icons');
    $editor_settings = [
      'hostname' => $config->hostname,
      'icons' => $icons,
      'schemaJson' => $schema_json,
      'startingJson' => $starting_json,
      'theme' => $theme,
    ];

    if (isset(PatternLibraryJSONForm::THEMES[$theme])) {
      $theme_info = PatternLibraryJSONForm::THEMES[$theme];
      $editor_settings['themeStylesheet'] = !empty($theme_info['css']) ? $this->getLibraryAssetUrlFromUri($theme_info['css']) : '';
      if (!empty($theme_info['js'])) {
        $editor_settings['themeJS'][] = $this->getLibraryAssetUrlFromUri($theme_info['js']);
      }
    }
    if (isset(PatternLibraryJSONForm::ICONS[$icons])) {
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
    $markup = <<<HTML
<div id="editor-shadow-injection-target"></div>
HTML;

    return [
      '#type'     => 'markup',
      '#markup'   => $markup,
      '#attached' => [
        'drupalSettings' => [
          'patternkitEditor' => $editor_settings,
        ],
        'library' => ['patternkit/patternkit.jsoneditor'],
      ],
    ];
  }

}
