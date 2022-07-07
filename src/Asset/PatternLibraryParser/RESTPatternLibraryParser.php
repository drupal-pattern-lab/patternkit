<?php

namespace Drupal\patternkit\Asset\PatternLibraryParser;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternEditorConfig;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * A library parser implementation for REST pattern libraries.
 */
class RESTPatternLibraryParser {

  /**
   * HTTP client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $client;

  /**
   * Filesystem interface service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fs;

  /**
   * RESTPatternLibraryParser constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend service.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer service.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client for rest API library retrieval.
   * @param \Drupal\Core\File\FileSystemInterface $fs
   *   The file system service.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    SerializerInterface $serializer,
    ClientInterface $client,
    FileSystemInterface $fs
    ) {
    $this->client = $client;
    $this->fs = $fs;
  }

  /**
   * Fetches all assets for a pattern.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern to use for asset retrieval.
   * @param \Drupal\patternkit\PatternEditorConfig $config
   *   The configuration object to use for provisioning the pattern.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The pattern parameter with updated asset references.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fetchPatternAssets(PatternInterface $pattern,
    PatternEditorConfig $config) :PatternInterface {

    $patternkit_host = $this->configFactory->get('patternkit_pl_host');

    $url = $patternkit_host . '/api/render/json';
    $result = $this->client->request(
      'GET',
      $url,
      [
        'headers' => ['Content-Type' => 'application/json'],
        'data'    => $config->rawJSON,
        'timeout' => 10,
        'method'  => 'POST',
      ]
    );

    // @todo Request failure handling.
    $body = $result->getBody();
    $response = $body->read($body->getSize());
    $pk_obj = $this->serializer->deserialize($response, 'object', 'json');
    $subtype = $pattern->subtype;
    $dir = "public://patternkit/$subtype/{$config->instance_id}";
    if (!$this->fs->prepareDirectory($dir)) {
      // @todo Failure handling.
      _patternkit_show_error(
        "Unable to create folder ($dir) to contain the pklugins artifacts."
      );
    }

    // Fetch the body html artifact.
    $save_result = $this->fs->saveData(
      $pk_obj->body,
      "$dir/manifest.json",
      $this->fs::EXISTS_REPLACE
    );
    $pk_obj->body = $save_result;

    if ($save_result === FALSE) {
      // @todo Failure handling.
      _patternkit_show_error(
        "Unable to create static archive of the JSON pklugins artifact for $subtype."
      );
    }

    $assets = [];

    // Normalize the object for easier processing.
    if (!empty($pk_obj->assets)) {
      $pk_obj->assets->js->early;
      $pk_obj->assets->js->deferred;
      $pk_obj->assets->css->list;
      $pk_obj->assets->css->shared;
    }
    else {
      $pk_obj->assets->js->early = $pk_obj->global_assets->js;
      $pk_obj->assets->js->deferred = $pk_obj->global_assets->footer_js;
      $pk_obj->assets->css->list = $pk_obj->global_assets->css;
      $pk_obj->assets->css->shared = [];
    }

    if (!empty($pk_obj->assets)) {
      $assets['js'] = array_merge(
        $pk_obj->assets->js->early,
        $pk_obj->assets->js->deferred
      );

      $assets['css'] = $pk_obj->assets->css->list;
      // Fetch and update the assets in the shared dependency list.
      if (!empty($pk_obj->assets->css->shared)) {
        foreach ($pk_obj->assets->css->shared as $asset) {
          $assets['css'][] = $asset->src;
        }
      }
    }
    else {
      $assets['js'] = array_merge(
        $pk_obj->global_assets->js,
        $pk_obj->global_assets->footer_js
      );

      $assets['css'] = $pk_obj->global_assets->css;
    }

    foreach ($assets as $asset_type => $urls) {
      foreach ($urls as $asset_url) {
        // If the asset is being loaded with arbitrary scheme, assume external.
        $pos = strpos($asset_url, '//');
        if ($pos !== FALSE && $pos <= 11) {
          continue;
        }

        $save_result = _patternkit_fetch_single_asset($dir, $pk_obj->path, $asset_url);
        $pk_obj->raw_assets[$asset_url] = $save_result;
      }
    }

    // Depending on the type of presentation, we want to fetch the data
    // differently.
    switch ($config->presentation_style) {
      case 'webcomponent':
        _patternkit_fetch_webcomponent_assets($subtype, $config, $pk_obj);
        break;

      case 'html':
        _patternkit_fetch_fragment_assets($subtype, $config, $pk_obj);
        break;

      case 'json':
        break;
    }

    $pattern->attachments = $pk_obj;

    return $pattern;
  }

  /**
   * Returns the id of the Pattern Library.
   *
   * @return string
   *   The Pattern Library id.
   */
  public function getId() :string {
    return 'patternkit_rest_api';
  }

  /**
   * Returns the title of the Pattern Library.
   *
   * @return string
   *   The Pattern Library title.
   */
  public function getTitle() :string {
    return 'Patternkit REST API';
  }

  /**
   * Returns renderable data or markup for a pattern editor.
   *
   * @param string|null $subtype
   *   If specified, return an editor customized for this subtype.
   * @param \Drupal\patternkit\PatternEditorConfig|null $config
   *   Optional configuration settings for the editor.
   *
   * @return mixed
   *   The renderable pattern editor.
   */
  public function getEditor(string $subtype = NULL, ?PatternEditorConfig $config = NULL): string {
    $patternkit_host = $this->configFactory->get('patternkit_pl_host');
    $url = $patternkit_host . '/schema/editor/' . substr($subtype, 3);

    if ($config !== NULL) {
      $url .= !empty($config->lzstring) ? '?data=' . $config->lzstring : '';
    }
    // @todo Move to external phptemplate.
    $markup = <<< HTML
<iframe id='schema-editor-iframe' width='100%' height='1000px' src='$url'></iframe>
<script>
  // Enlarge the ctools modal to make it easier to work with the iframe.
  jQuery('.ctools-modal-content').animate({width:'100%', height:'100%'});
  jQuery('#modalContent').animate({'width': '100%', 'left':'0px', 'top':'0px'});
  jQuery('#modal-content').animate({'width': '100%', 'height': '100%'});

  var schemaDataSaved = false;
  // Respond to data events.
  window.addEventListener('message', function(event) {
    if (event.data.name && event.data.name === 'saveData') {
      var configObject = JSON.stringify(event.data);
      document.getElementById('schema_instance_config').value = configObject;
      console.log('config object', configObject);
      schemaDataSaved = true;
      jQuery('#patternkit-patternkit-content-type-edit-form').trigger('submit');
    }
  });

  document.getElementById('patternkit-patternkit-content-type-edit-form').onsubmit = function(){
    if (schemaDataSaved === false) {
      var frame = document.getElementById('schema-editor-iframe');
      frame.contentWindow.postMessage('sendSaveData', '*');
      return false;
    }
  };
</script>
HTML;

    return $markup;
  }

  /**
   * Utility function to get all Patternkit module metadata.
   *
   * @return array
   *   Array of metadata objects found.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getRawMetadata() :array {
    $patternkit_host = $this->config->get('patternkit_pl_host');

    try {
      $request = $this->client->request(
        'GET',
        $patternkit_host . '/api/patterns',
        [
          'headers' => ['Content-Type' => 'application/json'],
          'timeout' => 10,
        ]
      );
    }
    catch (\Exception $exception) {
      return [];
    }
    $body = $request->getBody();
    $patterns = $body->read($body->getSize());
    if (empty($patterns)
      || !empty($request->error)
      || (int) $request->getStatusCode() !== 200) {
      _patternkit_show_error(
        'Patternkit failed to load metadata from service (%service_uri): %error',
        [
          '%service_uri' => $patternkit_host . '/api/patterns',
          '%error'       => !empty($request->error) ? $request->error : $request->getStatusCode(),
        ]
      );
      return [];
    }
    /** @var array $metadata */
    $metadata = $this->serializer->deserialize($patterns, 'array', 'json');
    foreach ($metadata as $subtype => $pattern) {
      $pattern->library = &$this;
      $metadata[$subtype] = $pattern;
    }
    return $metadata;
  }

}
