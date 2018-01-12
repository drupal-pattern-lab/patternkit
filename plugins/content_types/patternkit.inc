<?php

/**
 * @file
 * Patternkit Panes.
 *
 * Exposes patternkit modules to panels for placement on the page.
 * Author: Jason Smith <jason.smith@redhat.com>.
 */

/**
 * Plugin declaration for panels.
 *
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title'        => t('Patternkit Pattern'),
  'description'  => t(
    'Add and configure a patternkit pattern module to the page'
  ),
  'category'     => array(t('Patternkit'), -9),
  'content type' => 'patternkit_patternkit_content_type_content_type',
  'admin info'   => 'patternkit_patternkit_content_type_admin_info',
  'defaults'     => array(),
  'all contexts' => TRUE, // This is NEEDED to be able to use substitution strings in your pane.
);

/**
 * Generate the content type (pane) definitions.
 *
 * @param string $subtype
 *   Requested module type.
 *
 * @return mixed
 *   Metadata for the content type requested.
 */
function patternkit_patternkit_content_type_content_type($subtype) {
  $types = patternkit_patternkit_content_type_content_types();
  if (isset($types[$subtype])) {
    return $types[$subtype];
  }

  if (!user_is_anonymous()) {
    drupal_set_message(
      t(
        "The patternkit framework module (:module) appears to be missing. Remove from this panel, or replace the missing code and clear caches.",
        array(
          ':module' => $subtype,
        )
      ),
      'warning',
      FALSE
    );
  }

  return FALSE;
}

/**
 * Utility function to generate the pane configs.
 *
 * This function determines which panes are available for use on any
 * particular panel.
 *
 * @return array
 *   Collection of pane metadata
 */
function patternkit_patternkit_content_type_content_types() {
  // No reason to generate repeatedly, so (static) caching results.
  $types = &drupal_static(__FUNCTION__, array());
  if (!empty($types)) {
    return $types;
  }

  // Get all of the module metadata.
  $modules = _patternkit_get_metadata();

  // Loop through the found modules and create panes for each.
  foreach ($modules as $module_name => $module_meta) {
    $module_key = 'pk_' . $module_name;
    $category = !empty($module_meta->category) ? $module_meta->category : 'uncategorized';

    // Create the pane config.
    $types[$module_key] = array(
      'category'    => "Patternkit/$category",
      'icon'        => 'icon_field.png',
      'title'       => $module_meta->title,
      'description' => $module_meta->title . ' ' . $module_meta->version,
      'defaults'    => array(),
      'cache'       => TRUE,
    );
  }

  return $types;
}

/**
 * Implements hook_content_type_admin_title().
 */
function patternkit_patternkit_content_type_admin_title(
  $subtype,
  $conf,
  $context
) {
  $module = _patternkit_get_metadata($subtype);
  if (empty($module->title)) {
    $title = "BROKEN/MISSING MODULE";
  }
  else {
    $title = $module->title;
  }

  return t(
    'READABLENAME (MODULENAME Pattern)',
    array(
      'READABLENAME' => $title,
      'MODULENAME'   => $subtype,
    )
  );
}

/**
 * Implements hook_content_type_admin_title().
 */
function patternkit_patternkit_content_type_admin_info(
  $subtype,
  $conf,
  $context
) {
  $module = _patternkit_get_metadata($subtype);
  $block = new stdClass();

  $block->title = $subtype;
  $block->content = "$subtype";

  if (empty($module->title)) {
    $block->title = "BROKEN/MISSING MODULE ($subtype)";
    $block->content = "BROKEN/MISSING MODULE";

    return $block;
  }

  // Add to the block content the admin presentation.
  $block->content = _patternkit_admin_info_presentation_render(
    $conf,
    $module,
    $subtype,
    $block->content
  );

  return $block;
}

/**
 * Implements hook_content_type_edit_form().
 */
function patternkit_patternkit_content_type_edit_form($form, &$form_state) {

  // Get the subtype from the form state for simpler code.
  $subtype = $form_state['subtype_name'];
  // Instance configuration for the form is in form_state['conf'];.
  $conf = $form_state['conf'];
  // Get all of the module metadata from the pattern path.
  $module = _patternkit_get_metadata($subtype);

  // Remove the title override fields.
  unset($form['override_title_markup']);
  unset($form['override_title']);
  unset($form['override_title_text']);
  unset($form['override_title_heading']);

  // @TODO: Re-enable the other formats.
  $form['presentation_style'] = array(
    '#type'          => 'select',
    '#title'         => 'Presentation style',
    '#options'       => array(
      //      'webcomponent' => 'Web component',
      'html'         => 'HTML inline',
      //      'json'         => 'JSON (with js/css)',
    ),
    '#default_value' => isset($conf['presentation_style']) ? $conf['presentation_style'] : '',
  );

  // Add version as hidden field.
  $form['version'] = array(
    '#type'  => 'hidden',
    '#value' => isset($conf['version']) ? $conf['version'] : $module->version,
  );

  // Add module subtype for simpler tracking.
  $form['module_subtype'] = array(
    '#type'  => 'hidden',
    '#value' => $form_state['subtype_name'],
  );

  // Add Instance UUID as hidden field.
  $form['instance_uuid'] = array(
    '#type'  => 'hidden',
    '#value' => isset($conf['instance_uuid']) ? $conf['instance_uuid'] : ctools_uuid_generate(
    ),
  );

  // Add Instance config (if exists) as hidden field.
  $form['instance_config'] = array(
    '#type'          => 'hidden',
    '#attributes'    => array(
      'id' => 'schema_instance_config',
    ),
    '#default_value' => isset($conf['instance_config']) ? $conf['instance_config'] : '',
  );

  $patternkit_host = variable_get(
    'patternkit_pl_host',
    'http://localhost:9001'
  );
  $url = $patternkit_host . '/schema/editor/' . substr($subtype, 3);

  if (!empty($conf['instance_config'])) {
    $config = json_decode($conf['instance_config']);
    $url .= !empty($config->lzstring) ? "?data=" . $config->lzstring : '';
  }

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

  $form['configuration'] = array(
    '#type'   => 'markup',
    '#markup' => $markup,
  );

  return $form;
}

/**
 * Implements hook_content_type_edit_form_submit().
 */
function patternkit_patternkit_content_type_edit_form_submit(
  &$form,
  &$form_state
) {
  // Clear instance render cache.
  $subtype = $form_state['values']['module_subtype'];
  $instance_id = $form_state['values']['instance_uuid'];
  $config = json_decode($form_state['values']['instance_config']);

  $cid = "pkmod:{$subtype}:{$instance_id}";
  cache_clear_all($cid, 'cache_patternkit', TRUE);

  $config->instance_id = $instance_id;
  $config->presentation_style = $form_state['values']['presentation_style'];

  $pk_obj = (object) _patternkit_fetch_json_and_assets($subtype, $config);

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

  $config->pkdata = $pk_obj;

  $form_state['values']['instance_config'] = json_encode($config);

  $form_state['conf'] = $form_state['values'];
}

/**
 * Implements hook_content_type_render().
 */
function patternkit_patternkit_content_type_render(
  $subtype,
  $instance_config,
  $args,
  $context
) {
  $base_dependencies = array();

  // Flag if a module block should be cached or not.
  $cacheable = variable_get('patternkit_render_cache', FALSE);

  // If an instance configuration provides a UUID, use it. If not, we should
  // not cache this item because the uuid will be different each time.
  if (empty($instance_config['instance_uuid'])) {
    $cacheable = FALSE;
    $instance_id = ctools_uuid_generate();
  }
  else {
    $instance_id = $instance_config['instance_uuid'];
  }

  // Create the cache key to be used for this object. Note that we are relying
  // on code elsewhere to clear this cache on modification. The md5 against
  // context is because context can change independently of instance config/ID.
  // Need to be able to cache for all contexts of a specific config. We add the
  // logged in check to prevent cached admin links from appearing in frontend.
  // It also mitigates the difference between esi delivery when logged in vs
  // not.
  global $language;
  $cid = "pkmod:{$subtype}:{$instance_id}:";
  $cid .= md5(
    json_encode(
      array(
        user_is_logged_in(),
        $context,
        $instance_config,
        $language->language,
      )
    )
  );

  // Load module specific config.
  $module = _patternkit_get_metadata($subtype);
  static $is_processed;

  // Initialize our static (if necessary).
  if (is_null($is_processed)) {
    $is_processed = array();
  }

  // If we've already processed this module, don't add it to active again.
  // This array is used by the frontend to bootstrap, we want each
  // module to appear only once in this array.
  if (!isset($is_processed[$subtype])) {
    // Add to bootstrap list.
    $base_dependencies['js'][] = array(
      'patternkit' => array(
        'active' => array(
          $subtype => array(
            $instance_id,
          ),
        ),
      ),
      'type' => 'setting',
    );
    $is_processed[$subtype] = 1;
  }

  // If the item is cacheable, fetch it and return it.
  if ($cacheable === TRUE) {
    // Attempt to fetch the cached pane.
    $cached = cache_get($cid, 'cache_patternkit');
    if ($cached !== FALSE) {

      // Set flag to let other modules know content is patternkit stuff.
      $cached->data->pkpane = 1;

      return $cached->data;
    }
  }

  $module->subtype = $subtype;

  // Pull the dependencies and configuration.
  $config = json_decode($instance_config['instance_config'], TRUE);

  // Get the body of content.
  $content = _patternkit_render_block($module, $instance_id, $config, $context);

  // Replace ctools substitutions (if any) from context.
  $content = ctools_context_keyword_substitute($content, array(), $context);

  // Replace core tokens in the body (if any).
  $content = token_replace(
    $content,
    array(
      'patternkit' => array(
        'pattern'     => $subtype,
        'instance_id' => $instance_id,
      ),
    )
  );
  //
  //  if (!empty($config['pkdata']['attachments'])) {
  //    $settings = _patternkit_merge_js(
  //      $config['pkdata']['attachments'],
  //      $base_dependencies
  //    );
  //    dpm($settings, 'settings');
  //    dpm($base_dependencies, 'bd');
  //  }


  // Build the response object.
  $block = new stdClass();
  $block->title = '';
  $block->content = array(
    '#markup'   => $content,
    '#attached' => $config['pkdata']['attachments'],
  );

  // Parse TTL and add to params.
  if (!empty($module->ttl)) {
    $ttl = $module->ttl;
  }
  else {
    // Default ttl to 30 days.
    $ttl = variable_get('patternkit_default_module_ttl', 30 * 24 * 60 * 60);
  }

  // Save to the cache bin (if caching is enabled).
  if ($cacheable === TRUE) {
    cache_set($cid, $block, 'cache_patternkit', time() + $ttl);
  }

  // Set flag to let other modules know content is ngpanes stuff.
  $block->ngpane = 1;

  return $block;
}

/************* Utility functions below *************/

/**
 * Render the actual contents of the module.
 *
 * @param object $module
 *   Module of interest.
 * @param string $instance_id
 *   The unique ID for the module we are working on.
 * @param array $config
 *   Array of vars from instance configuration / context.
 * @param array $context
 *   Context array (if available).
 *
 * @return string|object
 *   Content of the panel block.
 */
function _patternkit_render_block(
  $module,
  $instance_id,
  array $config,
  array $context = NULL
) {
  if (isset($module->subtype)) {
    $subtype = $module->subtype;
  }
  else {
    return 'MISSING/BROKEN';
  }

  if ($config['presentation_style'] == 'webcomponent') {
    $subtype = substr($subtype, 3);
    $body = "<$subtype-pattern></$subtype-pattern>";
  }
  else {
    $filename = "public://patternkit/$subtype/{$config['instance_id']}/body.html";
    $body = file_get_contents($filename);
  }
  return $body;
}

/**
 * Helper function to get the html markup for module admin presentation.
 *
 * @param array $conf
 *   The module configuration.
 * @param object $module
 *   The module metadata.
 * @param string $subtype
 *   The module machine name.
 * @param string $block_content
 *   The current module admin block content.
 *
 * @return string
 *   The html markup for the module admin presentation
 */
function _patternkit_admin_info_presentation_render(
  array $conf,
  $module,
  $subtype,
  $block_content
) {
  $result = patternkit_patternkit_content_type_render($subtype, $conf, array(), array());

  $output = $result->content;
  return $output;
}

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
    'pattern'     => $subtype,
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
