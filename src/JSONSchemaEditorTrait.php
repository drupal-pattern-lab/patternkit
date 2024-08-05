<?php

namespace Drupal\patternkit;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Asset\AssetQueryStringInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\Plugin\EditorManager;

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
  use StringTranslationTrait;

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
   *
   * @deprecated in patternkit:9.1.0-beta10 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\JsonEditorFormBuilder::buildEditorForm() instead.
   * @see https://www.drupal.org/project/patternkit/issues/3386854
   */
  public function schemaEditor(string $schema, PatternEditorConfig $config): array {
    @trigger_error(__METHOD__ . ' is deprecated in patternkit:9.1.0-beta10 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\JsonEditorFormBuilder::buildEditorForm() instead. See https://www.drupal.org/project/patternkit/issues/3386854', E_USER_DEPRECATED);

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

    /** @var \Drupal\patternkit\JsonEditorFormBuilder $builder */
    $builder = \Drupal::service('patternkit.jsoneditor.form_builder');
    return $builder->buildEditorForm(NULL, $editor_settings);
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
