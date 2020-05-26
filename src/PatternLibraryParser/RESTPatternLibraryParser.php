<?php

namespace Drupal\patternkit\PatternLibraryParser;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\patternkit\Pattern;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibraryParserInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class RESTLib.
 */
class RESTPatternLibraryParser implements PatternLibraryParserInterface {

  /** @var \Drupal\Core\Cache\CacheBackendInterface  */
  protected $cache;

  /** @var \GuzzleHttp\ClientInterface */
  protected $client;

  /** @var \Drupal\Core\Config\ImmutableConfig */
  protected $config;

  /** @var \Drupal\Core\File\FileSystemInterface */
  protected $fs;

  /** @var \Drupal\Core\Logger\LoggerChannelInterface */
  protected $logger;

  /** @var \Drupal\Component\Serialization\SerializationInterface */
  protected $serializer;

  /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface */
  protected $wrapperManager;

  /**
   * PatternkitRESTLib constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The Drupal Caching backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal Config Factory.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client for rest API library retrieval.
   * @param \Drupal\Core\File\FileSystemInterface $fs
   *   The Drupal file system.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The Drupal logger channel.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The Drupal JSON serializer.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $wrapperManager
   *   The Drupal Stream Wrapper Manager.
   */
  public function __construct(
    CacheBackendInterface $cache,
    ClientInterface $client,
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $fs,
    LoggerChannelInterface $logger,
    SerializationInterface $serializer,
    StreamWrapperManagerInterface $wrapperManager
    ) {
    $this->cache = $cache;
    $this->client = $client;
    $this->config = $config_factory->get('patternkit');
    $this->fs = $fs;
    $this->logger = $logger;
    $this->serializer = $serializer;
    $this->wrapperManager = $wrapperManager;
  }

  /**
   * Fetches all assets for a pattern.
   *
   * @param Pattern $pattern
   *   The pattern to use for asset retrieval.
   * @param PatternEditorConfig $config
   *   The configuration object to use for provisioning the pattern.
   *
   * @return \Drupal\patternkit\Pattern
   *   The pattern parameter with updated asset references.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fetchPatternAssets(Pattern $pattern,
    PatternEditorConfig $config) :Pattern {

    $patternkit_host = $this->config->get('patternkit_pl_host');

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

    // @TODO: Request failure handling.
    $body = $result->getBody();
    $response = $body->read($body->getSize());
    $pk_obj = $this->serializer->deserialize($response, 'object', 'json');
    $category = $pattern->category;
    $dir = "public://patternkit/$category/{$config->instance_id}";
    if (!$this->fs->prepareDirectory($dir)) {
      // @TODO: Failure handling.
      $this->logger->error(
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
      // @TODO: Failure handling.
      $this->logger->error(
        "Unable to create static archive of the JSON pklugins artifact for $category."
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

        // Leading double slashes eliminated above, leaving only relatives.
        $path = "$dir/" . dirname(trim($asset_url, '.'));
        $filename = basename(trim($asset_url, '.'));

        if (!$this->fs->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY)) {
          $this->logger->error(
            "Unable to create folder ($path) to contain the pklugins artifacts."
          );
        }

        // Generate the full path to the source asset.
        $full_asset_url = $patternkit_host . preg_replace('/^\\.\\//', $pattern->path . '/', $asset_url);

        // What follows is for store/cache model.
        $asset_src = $this->client->request('GET', $full_asset_url);
        // May consider some way of doing this
        // $dest_path = "$dir/" . md5($asset_src->data) . ".$asset_type";.
        $dest_path = $path . $filename;

        $save_result = $this->fs->saveData(
          $asset_src->getBody()->getContents(),
          $dest_path,
          FileSystemInterface::EXISTS_REPLACE
        );

        if ($save_result === FALSE) {
          // @todo: handle failure.
          continue;
        }

        // Convert stream wrapper to relative path, if appropriate.
        $scheme = StreamWrapperManagerInterface::getScheme($save_result);
        if ($scheme && $this->wrapperManager->isValidScheme($scheme)) {
          $wrapper = $this->wrapperManager->getViaScheme($scheme);
          $save_result = $wrapper->getDirectoryPath() . "/" . StreamWrapperManagerInterface::getTarget(
              $save_result
            );
        }
        $pattern->raw_assets[$asset_url] = $save_result;
      }
    }

    // Depending on the type of presentation, we want to fetch the data
    // differently.
    switch ($config->presentation_style) {
      case 'webcomponent':
        $url = $patternkit_host . '/api/render/webcomponent';
        $result = $this->client->request(
          'GET',
          $url,
          [
            'headers'  => ['Content-Type' => 'application/json'],
            'jsondata' => $config->rawJSON,
            // 'timeout' => 10,
            'method'   => 'POST',
          ]
        );

        // @todo: Request failure handling.

        // Create the stub object.
        $pk_obj = (object) [
          'PatternkitPattern' => $category,
          'attachments' => [],
          'body'        => 'fragment.html',
        ];

        $dir = "public://patternkit/$category/{$config->instance_id}";
        if (!$this->fs->prepareDirectory($dir, $this->fs::CREATE_DIRECTORY)) {
          \Drupal::messenger()->addMessage(
            t(
              'Unable to create folder or save metadata/assets for plugin @plugin',
              ['@plugin' => $category]
            ),
            \Drupal::messenger()::TYPE_ERROR
          );
          $this->logger->error(
            'Unable to create folder or save metadata/assets for plugin @plugin',
            ['@plugin' => $category]
          );
        }

        // Fetch the body html artifact.
        $save_result = $this->fs->saveData(
          $result->getBody()->getContents(),
          "$dir/body.html",
          $this->fs::EXISTS_REPLACE
        );

        // Convert stream wrapper to relative path, if appropriate.
        $scheme = $this->wrapperManager::getScheme($save_result);
        if ($scheme && $this->wrapperManager->isValidScheme($scheme)) {
          $wrapper = $this->wrapperManager->getViaScheme($scheme);
          $save_result = $wrapper->getDirectoryPath() . "/" . $this->wrapperManager::getTarget(
              $save_result
            );
        }

        $pk_obj->body = $save_result;

        $pk_obj->attachments['js']['https://cdnjs.cloudflare.com/ajax/libs/webcomponentsjs/0.7.24/webcomponents.min.js'] = [
          'type'   => 'external',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => 0,
        ];

        // Add the header link rel import.
        $pk_obj->attachments['drupal_add_html_head_link'][] = [
          [
            'rel'  => 'import',
            'href' => $pk_obj->body,
          ]
        ];

        if ($save_result === FALSE) {
          \Drupal::messenger()->addMessage(
            t(
              'Unable to save metadata/assets for plugin @plugin',
              ['@plugin' => $category]
            ),
            \Drupal::messenger()::TYPE_ERROR
          );
          \Drupal::logger('patternkit')->error(
            'Unable to save metadata/assets for plugin @plugin',
            ['@plugin' => $category]
          );
          // @todo: Do something.
        }
        break;

      case 'html':
        $url = $patternkit_host . '/api/render/html';
        $result = $this->client->request(
          'GET',
          $url,
          [
            'headers' => ['Content-Type' => 'application/json'],
            'data'    => $config->rawJSON,
            // 'timeout' => 10,.
            'method'  => 'POST',
          ]
        );

        // @todo: Request failure handling.

        $pk_obj->pattern = $category;

        $dir = "public://patternkit/$category/{$config->instance_id}";
        if (!$this->fs->prepareDirectory($dir, $this->fs::CREATE_DIRECTORY)) {
          \Drupal::messenger()->addMessage(
            t(
              'Unable to create folder or save metadata/assets for plugin @plugin',
              ['@plugin' => $category]
            ),
            \Drupal::messenger()::TYPE_ERROR
          );
          \Drupal::logger('patternkit')->error(
            'Unable to create folder or save metadata/assets for plugin @plugin',
            ['@plugin' => $category]
          );
        }

        // Fetch the body html artifact.
        $save_result = $this->fs->saveData(
          $result->getBody()->getContents(),
          "$dir/body.html",
          $this->fs::EXISTS_REPLACE
        );

        // Convert stream wrapper to relative path, if appropriate.
        $scheme = $this->wrapperManager::getScheme($save_result);
        if ($scheme && $this->wrapperManager->isValidScheme($scheme)) {
          $wrapper = $this->wrapperManager->getViaScheme($scheme);
          $save_result = $wrapper->getDirectoryPath() . "/" . $this->wrapperManager::getTarget(
              $save_result
            );
        }

        $pk_obj->body = $save_result;

        if ($save_result === FALSE) {
          \Drupal::messenger()->addMessage(
            t(
              'Unable to save metadata/assets for plugin @plugin',
              ['@plugin' => $category]
            ),
            \Drupal::messenger()::TYPE_ERROR
          );
          \Drupal::logger('patternkit')->error(
            'Unable to save metadata/assets for plugin @plugin',
            ['@plugin' => $category]
          );
          // @todo: Do something.
        }

        foreach (['early', 'deferred'] as $stage) {
          foreach ($pk_obj->assets->js->{$stage} as $asset_fragment) {
            $path = $pk_obj->raw_assets[$asset_fragment];

            if (strpos($path, 'public://patternkit/') === 0) {
              $pk_obj->attachments['js'][$path] = [
                'type'   => 'file',
                'scope'  => $stage === 'early' ? 'header' : 'footer',
                'group'  => JS_DEFAULT,
                'weight' => 0,
              ];
            }
            else {
              $pk_obj->attachments['js'][$path] = [
                'type'   => 'external',
                'scope'  => $stage === 'early' ? 'header' : 'footer',
                'group'  => JS_DEFAULT,
                'weight' => 0,
              ];
            }
          }
        }

        foreach ($pk_obj->assets->css->list as $asset_fragment) {
          $path = $pk_obj->raw_assets[$asset_fragment];

          if (strpos($path, 'public://patternkit/') === 0) {
            $pk_obj->attachments['css'][$path] = [
              'type'   => 'file',
              'scope'  => 'header',
              'group'  => JS_DEFAULT,
              'weight' => 0,
            ];
          }
          else {
            $pk_obj->attachments['css'][$path] = [
              'type'   => 'external',
              'scope'  => 'header',
              'group'  => JS_DEFAULT,
              'weight' => 0,
            ];
          }
        }

        foreach ($pk_obj->assets->css->shared as $asset_fragment) {
          $path = $pk_obj->raw_assets[$asset_fragment->src];

          if (strpos($path, 'public://patternkit/') === 0) {
            $pk_obj->attachments['css'][$path] = [
              'type'   => 'file',
              'scope'  => 'header',
              'group'  => JS_DEFAULT,
              'weight' => 0,
            ];
          }
          else {
            $pk_obj->attachments['css'][$path] = [
              'type'   => 'external',
              'scope'  => 'header',
              'group'  => JS_DEFAULT,
              'weight' => 0,
            ];
          }
        }
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
   * @param PatternEditorConfig $config
   *   Optional configuration settings for the editor.
   *
   * @return mixed
   *   The renderable pattern editor.
   */
  public function getEditor($subtype = NULL, PatternEditorConfig $config = NULL) {
    $patternkit_host = $this->config->get('patternkit_pl_host');
    $url = $patternkit_host . '/schema/editor/' . substr($subtype, 3);

    if ($config !== NULL) {
      $url .= !empty($config->lzstring) ? '?data=' . $config->lzstring : '';
    }
    // @todo Move to external template.
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
   *   Thrown on HTTP request error.
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
      $this->logger->error(
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

  /**
   * @inheritDoc
   */
  public function createPattern($name, $schema): Pattern {
    // TODO: Implement createPattern() method.
  }

  /**
   * @inheritDoc
   */
  public static function discoverComponents($path, array $filter = []): array {
    // TODO: Implement discoverComponents() method.
  }

  /**
   * @inheritDoc
   */
  public static function fetchAssets($subtype, $config): Pattern {
    self::
  }

  /**
   * @inheritDoc
   */
  public function fetchFragmentAssets($subtype, $config, &$pk_obj): Pattern {
    // TODO: Implement fetchFragmentAssets() method.
  }

  /**
   * @inheritDoc
   */
  public static function fetchSingleAsset($dir, $asset_prefix, $asset_url) {
    // TODO: Implement fetchSingleAsset() method.
  }

  /**
   * @inheritDoc
   */
  public function parsePatternLibraryInfo(array $library, $path): array {
    // TODO: Implement parsePatternLibraryInfo() method.
  }

}
