<?php

namespace Drupal\patternkit\PatternLibraryParser;

use Drupal\patternkit\PatternLibraryParserBase;

class WebcomponentPatternLibraryParser extends PatternLibraryParserBase {

  /**
   * Fetch and cache assets to render this pattern.
   *
   * @param string $subtype
   *   Pattern machine name.
   * @param object $config
   *   Configuration object for this instance of the pattern.
   * @param object $pk_obj
   *   The patternkit pattern object to extend.
   *
   * @return object
   *   The patternkit object representing the pattern.
   *
   * @todo Finish implementation.
   */
  public function fetchAssets($subtype, $config, &$pk_obj) {
    $patternkit_host = variable_get(
      'patternkit_pl_host',
      'http://localhost:9001'
    );

    //json_decode($config->rawJSON);

    $url = $patternkit_host . '/api/render/webcomponent';
    $result = drupal_http_request(
      $url,
      array(
        'headers'  => array('Content-Type' => 'application/json'),
        'jsondata' => $config->rawJSON,
        // 'timeout' => 10,.
        'method'   => 'POST',
      )
    );

    // @TODO: Request failure handling.

    // Create the stub object.
    $pk_obj = (object) array(
      'PatternkitPattern' => $subtype,
      'attachments' => array(),
      'body'        => 'fragment.html',
    );

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

    $pk_obj->attachments['js']['https://cdnjs.cloudflare.com/ajax/libs/webcomponentsjs/0.7.24/webcomponents.min.js'] = array(
      'type'   => 'external',
      'scope'  => 'header',
      'group'  => JS_DEFAULT,
      'weight' => 0,
    );

    // Add the header link rel import.
    $pk_obj->attachments['drupal_add_html_head_link'][] = array(
      array(
        'rel'  => 'import',
        'href' => $pk_obj->body,
      ),
    );

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

    return $pk_obj;
  }

}
