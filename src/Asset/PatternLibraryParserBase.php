<?php

namespace Drupal\patternkit\Asset;

use Drupal\Core\Asset\LibraryDiscoveryParser;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternEditorConfig;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

/**
 * Base functionality for a pattern library parser.
 */
abstract class PatternLibraryParserBase extends LibraryDiscoveryParser implements PatternLibraryParserInterface {

  /**
   * Returns a new Patternkit Pattern.
   *
   * @param string $name
   *   The name of the pattern.
   * @param array|object $data
   *   The optional data for the pattern.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   A fully-instantiated Patternkit Pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createPattern($name, $data): PatternInterface {
    // Pattern schemas contain values needed for Pattern fields.
    $values = ['name' => $name];
    return Pattern::create($values + $data);
  }

  /**
   * Returns an array of file components grouped by file basename and extension.
   *
   * @param string $path
   *   The fully-qualified path to discover component files.
   * @param array $filter
   *   An optional filter of file extensions to search for.
   *
   * @return array
   *   Array of file components.
   *   [basename][extension] = filename.
   */
  public static function discoverComponents($path, array $filter = []): array {
    $components = [];
    $rdit = new RecursiveDirectoryIterator($path, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO);
    /** @var \SplFileInfo $file */
    foreach (new \RecursiveIteratorIterator($rdit) as $file) {
      // Skip directories and non-files.
      if (!$file->isFile()) {
        continue;
      }
      $file_path = $file->getPath();

      // Skip tests folders.
      if (strpos($file_path, '/tests') !== FALSE) {
        continue;
      }

      // Get the file extension for the file.
      $file_ext = $file->getExtension();
      if (!in_array(strtolower($file_ext), $filter, TRUE)) {
        continue;
      }

      // We use file_basename as a unique key, it is required that the
      // JSON and twig file share this basename.
      $file_basename = $file->getBasename('.' . $file_ext);

      // Build an array of all the filenames of interest, keyed by name.
      $components[$file_basename][$file_ext] = "$file_path/$file_basename.$file_ext";
    }
    return $components;
  }

  /**
   * Implements fetchAssets().
   *
   * {@inheritDoc}
   *
   * @todo Finish implementation.
   */
  public static function fetchAssets($subtype, $config): PatternInterface {
    $patternkit_host = variable_get(
      'patternkit_pl_host',
      'http://localhost:9001'
    );

    $url = $patternkit_host . '/api/render/json';
    $result = drupal_http_request(
      $url,
      array(
        'headers' => array('Content-Type' => 'application/json'),
        'data'    => $config->rawJSON,
        'timeout' => 10,
        'method'  => 'POST',
      )
    );

    // @TODO: Request failure handling.

    $pk_obj = json_decode($result->data);

    $dir = "public://patternkit/$subtype/{$config->instance_id}";
    if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
      // @TODO: Failure handling.
      _patternkit_show_error(
        "Unable to create folder ($dir) to contain the pklugins artifacts."
      );
    }

    // Fetch the body html artifact.
    $save_result = file_unmanaged_save_data(
      $pk_obj->body,
      "$dir/manifest.json",
      FILE_EXISTS_REPLACE
    );
    $pk_obj->body = $save_result;

    if ($save_result == FALSE) {
      // @TODO: Failure handling.
      _patternkit_show_error(
        "Unable to create static archive of the JSON pklugins artifact for $subtype."
      );
    }

    $weight = 100;
    $assets = array();

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
      $pk_obj->assets->css->shared = array();
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

        $save_result = static::fetchSingleAsset($dir, $pk_obj->path, $asset_url);
        $pk_obj->raw_assets[$asset_url] = $save_result;
      }
    }

    return $pk_obj;
  }

  /**
   * Fetches all assets for a pattern.
   *
   * @param \Drupal\patternkit\entity\PatternInterface $pattern
   *   The pattern to use for asset retrieval.
   * @param \Drupal\patternkit\PatternEditorConfig|null $config
   *   The configuration object to use for provisioning the pattern.
   *
   * @return array
   *   An array of loaded patterns assets.
   */
  public function fetchPatternAssets(PatternInterface $pattern,
    PatternEditorConfig $config = NULL): array {
    // @todo Add support for twig lib attachments such as JS and images.
    $assets = $pattern->getAssets();
    foreach ($assets as $asset => $data) {
      if (!empty($data)) {
        continue;
      }
      if (file_exists($asset)) {
        $assets[$assets] = file_get_contents($asset);
      }
      // $this->fetchSingleAsset();
    }
    return $assets;
  }

  /**
   * Fetch a single asset from the patternkit platform.
   *
   * @param string $dir
   *   The path to put the file under.
   * @param string $asset_url
   *   Relative path of the asset to the pattern folder.
   *
   * @return bool|string
   *   FALSE on failure, stream path on success.
   *
   * @todo Finish implementation.
   */
  public static function fetchSingleAsset($dir, $asset_prefix, $asset_url) {
    $patternkit_host = variable_get(
      'patternkit_pl_host',
      'http://localhost:9001'
    );

    // Leading double slashes eliminated above, leaving only relatives.
    $path = "$dir/" . dirname(trim($asset_url, '.'));
    $filename = basename(trim($asset_url, '.'));

    if (!file_prepare_directory($path, FILE_CREATE_DIRECTORY)) {
      _patternkit_show_error(
        "Unable to create folder ($path) to contain the pklugins artifacts."
      );
    }

    // Generate the full path to the source asset.
    $full_asset_url = $patternkit_host . preg_replace('/^\\.\\//', $asset_prefix . '/', $asset_url);

    // What follows is for store/cache model.
    $asset_src = drupal_http_request($full_asset_url);
    // May consider some way of doing this
    // $dest_path = "$dir/" . md5($asset_src->data) . ".$asset_type";.
    $dest_path = $path . $filename;

    $save_result = file_unmanaged_save_data(
      $asset_src->data,
      $dest_path,
      FILE_EXISTS_REPLACE
    );

    if ($save_result == FALSE) {
      // @TODO: handle failure.
      return FALSE;
    }

    // Convert stream wrapper to relative path, if appropriate.
    $scheme = file_uri_scheme($save_result);
    if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
      $wrapper = file_stream_wrapper_get_instance_by_scheme($scheme);
      $save_result = $wrapper->getDirectoryPath() . "/" . file_uri_target(
          $save_result
        );
    }
    return $save_result;
  }

  /**
   * Implements fetchFragmentAssets.
   *
   * {@inheritDoc}
   *
   * @todo Finish implementation.
   */
  public function fetchFragmentAssets($subtype, $config, &$pk_obj): PatternInterface {
    $patternkit_host = variable_get(
      'patternkit_pl_host',
      'http://localhost:9001'
    );

    $url = $patternkit_host . '/api/render/html';
    $result = drupal_http_request(
      $url,
      array(
        'headers' => array('Content-Type' => 'application/json'),
        'data'    => $config->rawJSON,
        // 'timeout' => 10,.
        'method'  => 'POST',
      )
    );

    // @TODO: Request failure handling.

    $pk_obj->pattern = $subtype;

    $dir = "public://patternkit/$subtype/{$config->instance_id}";
    if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
      drupal_set_message(
        t(
          'Unable to create folder or save metadata/assets for plugin @plugin',
          array(
            '@plugin' => $subtype,
          )
        ),
        'error'
      );
      watchdog(
        'patternkit',
        'Unable to create folder or save metadata/assets for plugin @plugin',
        array(
          '@plugin' => $subtype,
        ),
        WATCHDOG_ERROR
      );
    }

    // Fetch the body html artifact.
    $save_result = file_unmanaged_save_data(
      $result->data,
      "$dir/body.html",
      FILE_EXISTS_REPLACE
    );

    // Convert stream wrapper to relative path, if appropriate.
    $scheme = file_uri_scheme($save_result);
    if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
      $wrapper = file_stream_wrapper_get_instance_by_scheme($scheme);
      $save_result = $wrapper->getDirectoryPath() . "/" . file_uri_target(
          $save_result
        );
    }

    $pk_obj->body = $save_result;

    if ($save_result == FALSE) {
      drupal_set_message(
        t(
          'Unable to save metadata/assets for plugin @plugin',
          array(
            '@plugin' => $subtype,
          )
        ),
        'error'
      );
      watchdog(
        'patternkit',
        'Unable to save metadata/assets for plugin @plugin',
        array(
          '@plugin' => $subtype,
        ),
        WATCHDOG_ERROR
      );
      // @TODO: do something.
    }

    foreach (array('early', 'deferred') as $stage) {
      foreach ($pk_obj->assets->js->{$stage} as $asset_fragment) {
        $path = $pk_obj->raw_assets[$asset_fragment];

        if (substr($path, 0, 19) == 'public://patternkit/') {
          $pk_obj->attachments['js'][$path] = array(
            'type'   => 'file',
            'scope'  => $stage == 'early' ? 'header' : 'footer',
            'group'  => JS_DEFAULT,
            'weight' => 0,
          );
        }
        else {
          $pk_obj->attachments['js'][$path] = array(
            'type'   => 'external',
            'scope'  => $stage == 'early' ? 'header' : 'footer',
            'group'  => JS_DEFAULT,
            'weight' => 0,
          );
        }
      }
    }

    foreach ($pk_obj->assets->css->list as $asset_fragment) {
      $path = $pk_obj->raw_assets[$asset_fragment];

      if (substr($path, 0, 19) == 'public://patternkit/') {
        $pk_obj->attachments['css'][$path] = array(
          'type'   => 'file',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => 0,
        );
      }
      else {
        $pk_obj->attachments['css'][$path] = array(
          'type'   => 'external',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => 0,
        );
      }
    }

    foreach ($pk_obj->assets->css->shared as $asset_fragment) {
      $path = $pk_obj->raw_assets[$asset_fragment->src];

      if (substr($path, 0, 19) == 'public://patternkit/') {
        $pk_obj->attachments['css'][$path] = array(
          'type'   => 'file',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => 0,
        );
      }
      else {
        $pk_obj->attachments['css'][$path] = array(
          'type'   => 'external',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => 0,
        );
      }
    }

    return $pk_obj;
  }

  /**
   * Merge js dependency arrays.
   *
   * Adds $js2 to $js1 and returns the merged array.
   *
   * @param array $js1
   *   First array.
   * @param array $js2
   *   Second array.
   *
   * @return array
   *   The merged array.
   */
  public function mergeJs(array $js1, array $js2): array {
    $ret = array();

    $x = 0;
    if (!empty($js1)) {
      foreach ($js1 as $key => $val) {
        // If this is a 'setting' or 'inline' it will be numeric.
        if (is_numeric($key)) {
          $ret[$x++] = $val;
        }
        else {
          $ret[$key] = $val;
        }
      }
    }
    if (!empty($js2)) {
      foreach ($js2 as $key => $val) {
        // If this is a 'setting' or 'inline' it will be numeric.
        if (is_numeric($key)) {
          $ret[$x++] = $val;
        }
        else {
          $ret[$key] = $val;
        }
      }
    }
    return $ret;
  }

}
