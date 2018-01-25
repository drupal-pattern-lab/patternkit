<?php

/**
 * @file
 * Utility functions for Patternkit.
 */


/**
 * Fetch and cache assets to render this pattern.
 *
 * @param string $subtype
 *   Pattern machine name.
 * @param object $config
 *   Configuration object for this instance of the pattern.
 *
 * @return object
 *   The patternkit object of interest.
 */
function _patternkit_fetch_json_and_assets($subtype, $config) {
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

      $save_result = _patternkit_fetch_single_asset($dir, $pk_obj->path, $asset_url);
      $pk_obj->raw_assets[$asset_url] = $save_result;
    }
  }

  return $pk_obj;
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
 */
function _patternkit_fetch_single_asset($dir, $asset_prefix, $asset_url) {
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
 */
function _patternkit_fetch_fragment_assets($subtype, $config, &$pk_obj) {
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
 */
function _patternkit_fetch_webcomponent_assets($subtype, $config, &$pk_obj) {
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

/**
 * Determine if a string starts with a string.
 *
 * @param string $haystack
 *   String to search.
 * @param string $needle
 *   String to search for.
 *
 * @return bool
 *   TRUE if found, FALSE if not.
 */
function _patternkit_strbegins($haystack, $needle) {
  return substr($haystack, 0, strlen($needle)) == $needle;
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
function _patternkit_merge_js(array $js1, array $js2) {
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

/**
 * Utility function to get all Patternkit module metadata.
 *
 * @param string|null $subtype
 *   If specified, return only the first metadata found for this subtype.
 * @param bool $reset
 *   Optionally force the meta data to be reloaded.
 *
 * @return array|\PatternkitPattern
 *   Array of metadata objects found or object if specific module requested.
 *
 * @todo Add support for multiple library implementation of a pattern.
 */
function _patternkit_get_metadata($subtype = NULL, $reset = FALSE) {
  $metadata = array();
  foreach (patternkit_pattern_libraries() as $lib) {
    if ($subtype === NULL) {
      $metadata += $lib->getCachedMetadata(NULL, $reset);
      continue;
    }
    $module = $lib->getCachedMetadata($subtype, $reset);
    if ($module !== NULL) {
      return $module;
    }
  }
  return $metadata;
}

/**
 * Parses schema properties for $ref and updates their location.
 *
 * @param object $properties
 *   The properties to parse.
 * @param array &$metadata
 *   The library metadata to lookup referenced patterns in.
 *
 * @return object
 *   The updated schema properties object.
 */
function _patternkit_schema_ref($properties, array &$metadata) {
  foreach ($properties as $property => $value) {
    if (!is_scalar($value)) {
      $new_value = _patternkit_schema_ref($value, $metadata);
      if (is_object($properties)) {
        $properties->{$property} = $new_value;
      }
      if (is_array($properties)) {
        $properties[$property] = $new_value;
      }
      continue;
    }
    if ($property !== '$ref') {
      continue;
    }
    $pattern = strstr($value, '.json', TRUE);
    $ref = substr($value, strlen("$pattern.json"));
    if (!isset($metadata[$pattern])) {
      unset($properties->{$property});
      continue;
    }
    $properties->{$property} = $metadata[$pattern]->url . $ref;
  }
  return $properties;
}

/**
 * Utility function to display patternkit error messages.
 *
 * Checks if permissions and configuration allow showing error, then displays.
 *
 * @param string $msg
 *   The message to display (with t vars as appropriate).
 * @param array $vars
 *   Optional array of replacement text appropriate for use in t function.
 * @param string $status
 *   Status for the error message. Passed to drupal_set_message.
 */
function _patternkit_show_error($msg, array $vars = array(), $status = 'error') {
  if (variable_get('patternkit_show_errors', FALSE) && user_is_logged_in()) {
    drupal_set_message(format_string($msg, $vars), $status);
  }
  if (variable_get('patternkit_log_errors', FALSE)) {
    watchdog('patternkit', $msg, $vars, WATCHDOG_ERROR);
  }
}
