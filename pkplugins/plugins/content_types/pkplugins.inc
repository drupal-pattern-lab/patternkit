<?php
/**
 * @file
 * Patternkit Panes.
 * Exposes patternkit modules to panels for placement on the page.
 * Author: Jason Smith <jason.smith@redhat.com>
 */


/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title'        => t('Patternkit Pattern'),
  'description'  => t('Add and configure a patternkit pattern module to the page'),
  'category'     => array(t('Patternkit'), -9),
  'content type' => 'pkplugins_pkplugins_content_type_content_type',
  'admin info'   => 'pkplugins_pkplugins_content_type_admin_info',
  'defaults'     => array(),
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
function pkplugins_pkplugins_content_type_content_type($subtype) {
  $types = pkplugins_pkplugins_content_type_content_types();
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
function pkplugins_pkplugins_content_type_content_types() {
  // No reason to generate repeatedly, so (static) caching results.
  $types = &drupal_static(__FUNCTION__, array());
  if (!empty($types)) {
    return $types;
  }

  // Get all of the module metadata.
  $modules = _pkplugins_get_metadata();

  // Loop through the found modules and create panes for each.
  foreach ($modules as $module_name => $module_meta) {
    $module_key = 'pk_' . $module_name;
    $category   = !empty($module_meta->category) ? $module_meta->category : 'uncategorized';

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
function pkplugins_pkplugins_content_type_admin_title($subtype, $conf, $context) {
  $module = _pkplugins_get_metadata($subtype);
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
function pkplugins_pkplugins_content_type_admin_info($subtype, $conf, $context) {
  $module = _pkplugins_get_metadata($subtype);
  $block  = new stdClass();

  $block->title   = $subtype;
  $block->content = "$subtype";

  if (empty($module->title)) {
    $block->title   = "BROKEN/MISSING MODULE ($subtype)";
    $block->content = "BROKEN/MISSING MODULE";

    return $block;
  }

  // Add to the block content the admin presentation
  $block->content = _pkplugins_admin_info_presentation_render($conf, $module, $subtype, $block->content);

  return $block;
}

/**
 * Implements hook_content_type_edit_form().
 */
function pkplugins_pkplugins_content_type_edit_form($form, &$form_state) {

  // Get the subtype from the form state for simpler code.
  $subtype = $form_state['subtype_name'];
  // Instance configuration for the form is in form_state['conf'];
  $conf = $form_state['conf'];
  // Get all of the module metadata from the pattern path.
  $module = _pkplugins_get_metadata($subtype);

  // Remove the title override fields.
  unset($form['override_title_markup']);
  unset($form['override_title']);
  unset($form['override_title_text']);
  unset($form['override_title_heading']);

  $form['presentation_style'] = array(
    '#type'          => 'select',
    '#title'         => 'Presentation style',
    '#options'       => array(
      'webcomponent' => 'Web component',
      'html'         => 'HTML inline',
      'json'         => 'JSON (with js/css)',
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
    '#value' => isset($conf['instance_uuid']) ? $conf['instance_uuid'] : ctools_uuid_generate(),
  );

  // Add Instance config (if exists) as hidden field.
  $form['instance_config'] = array(
    '#type'          => 'hidden',
    '#attributes'    => array(
      'id' => 'schema_instance_config',
    ),
    '#default_value' => isset($conf['instance_config']) ? $conf['instance_config'] : '',
  );

  $patternkit_host = variable_get('pkplugins_pk_host', 'http://10.254.254.254:8080');
  $url             = $patternkit_host . '/schema/editor/' . substr($subtype, 3);

  if (!empty($conf['instance_config'])) {
    $config = json_decode($conf['instance_config']);
    $url .= !empty($config->lzstring) ? "?data=" . $config->lzstring : '';
  }

  $markup = "
  <iframe id='schema-editor-iframe' width='100%' height='1000px' src='$url'></iframe>
  <script>
    // Enlarge the ctools modal to make it easier to work with the iframe.
    jQuery('.ctools-modal-content').animate({width:'100%', height:'100%'})
    jQuery('#modalContent').animate({'width': '100%', 'left':'0px', 'top':'0px'});
    jQuery('#modal-content').animate({'width': '100%', 'height': '100%'});
     
    var schemaDataSaved = false;
    // Respond to data events.
    window.addEventListener('message', function(event) {
      if (event.data.name && event.data.name == 'saveData') {
        let configObject = JSON.stringify(event.data);
        document.getElementById('schema_instance_config').value = configObject;
        console.log('config object', configObject);
        schemaDataSaved = true;
        jQuery('#pkplugins-pkplugins-content-type-edit-form').trigger('submit');
      }
    });    
    
    document.getElementById('pkplugins-pkplugins-content-type-edit-form').onsubmit = function(){
      if (schemaDataSaved == false) {
        var frame = document.getElementById('schema-editor-iframe');
        frame.contentWindow.postMessage('sendSaveData', '*');
        return false;
      }
    };
  </script>
  ";


  $form['configuration'] = array(
    '#type'   => 'markup',
    '#markup' => $markup,
  );


  return $form;
}

/**
 * Implements hook_content_type_edit_form_submit().
 */
function pkplugins_pkplugins_content_type_edit_form_submit(&$form, &$form_state) {
  // Clear instance render cache.
  $subtype     = $form_state['values']['module_subtype'];
  $instance_id = $form_state['values']['instance_uuid'];
  $config      = json_decode($form_state['values']['instance_config']);

  $cid = "pkmod:{$subtype}:{$instance_id}";
  cache_clear_all($cid, 'cache_pkplugins', TRUE);

  $config->instance_id = $instance_id;
  $config->presentation_style = $form_state['values']['presentation_style'];

  $pk_obj = (object) array();

  // Depending on the type of presentation, we want to fetch the data
  // differently.
  switch ($config->presentation_style) {
    case 'webcomponent':
      $pk_obj = _pkplugins_fetch_webcomponent_assets($subtype, $config);
      break;

    case 'html':
      $pk_obj = _pkplugins_fetch_fragment_assets($subtype, $config);
      break;

    case 'json':
      $pk_obj = _pkplugins_fetch_json_assets($subtype, $config);
      break;
  }

  $config->pkdata = $pk_obj;

  $form_state['values']['instance_config'] = json_encode($config);

  $form_state['conf'] = $form_state['values'];
}

/**
 * Implements hook_content_type_render().
 */
function pkplugins_pkplugins_content_type_render($subtype, $instance_config, $args, $context) {
  $base_dependencies = array();

  // Flag if a module block should be cached or not.
  $cacheable = variable_get('pkplugins_render_cache', FALSE);

  // If an instance configuration provides a UUID, use it. If not, we should
  // not cache this item because the uuid will be different each time.
  if (empty($instance_config['instance_uuid'])) {
    $cacheable   = FALSE;
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
  $module = _pkplugins_get_metadata($subtype);
  static $is_processed;

  // Initialize our static (if necessary).
  if (is_null($is_processed)) {
    $is_processed = array();
  }

  // If we've already processed this module, don't add it to active again.
  // This array is used by the frontend to bootstrap, we want each
  // module to appear only once in this array.
  if (!isset($is_processed[$module->module_name])) {
    // If this is a module, add to bootstrap list.
    if ($module->type == 'module') {
      $base_dependencies['js'][] = array(
        'data' => array(
          'pkplugins' => array(
            'active' => array($module->module_name),
          ),
        ),
        'type' => 'setting',
      );
    }
    $is_processed[$module->module_name] = 1;
  }

  // If the item is cacheable, fetch it and return it.
  if (0 && $cacheable === TRUE) {
    // Attempt to fetch the cached pane.
    $cached = cache_get($cid, 'cache_pkplugins');
    if ($cached !== FALSE) {

      // Set flag to let other modules know content is pkplugin stuff.
      $cached->data->pkpane = 1;

      return $cached->data;
    }
  }

  $module->subtype = $subtype;

  // Pull the dependencies and configuration.
  $config = json_decode($instance_config['instance_config'], TRUE);

  // Get the body of content.
  $content = _pkplugins_render_block($module, $instance_id, $config, $context);

  if ($config['presentation_style'] != 'webcomponent') {
    // Replace tokens in the body (if any).
    $content = token_replace(
      $content,
      array(
        'custom' => array(
          'token' => 'THIS WAS TOTALLY A TOKEN BEFORE - WINZ!!!!',
        )
      )
    );
  }

  // Build the response object.
  $block          = new stdClass();
  $block->title   = '';
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
    $ttl = variable_get('pkplugins_default_module_ttl', 30 * 24 * 60 * 60);
  }

  // Save to the cache bin (if caching is enabled).
  if ($cacheable === TRUE) {
    cache_set($cid, $block, 'cache_pkplugins', time() + $ttl);
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
 * @param array  $params
 *   Array of vars from instance configuration / context.
 * @param array  $context
 *   Context array (if available).
 *
 * @return string|object
 *   Content of the panel block.
 */
function _pkplugins_render_block($module, $instance_id, $config, $context) {
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
    $filename = "public://pkplugins/$subtype/{$config['instance_id']}/body.html";
    $body = file_get_contents($filename);
  }
  return $body;
}


/**
 * Helper function to get the html markup for module admin presentation
 *
 * @param array  $conf
 *   The module configuration
 * @param object $module
 *   The module metadata
 * @param string $subtype
 *   The module machine name
 * @param string $block_content
 *   The current module admin block content
 *
 * @return string
 *   The html markup for the module admin presentation
 */
function _pkplugins_admin_info_presentation_render($conf, $module, $subtype, $block_content) {
  // Set a default presentation.
  $presentation_title = t('Raw instance data');
  $raw_config         = '<pre>' . print_r($conf, 1) . '</pre>';


  $jsconf = json_decode($conf['instance_config']);
  dpm($jsconf, 'conf on presentation render');

  $result = pkplugins_pkplugins_content_type_render($subtype, $conf, array(), array());

  $output = $result->content;

  //$output = _pkplugins_render_block($module, $conf)
  //$output = $presentation_title . $raw_config;


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
 * @return
 *
 */
function _pkplugins_fetch_json_assets($subtype, $config) {
  $patternkit_host = variable_get('pkplugins_pk_host', 'http://10.254.254.254:8080');

  $url    = $patternkit_host . '/api/render/json';
  $result = drupal_http_request(
    $url,
    array(
      'headers' => array('Content-Type' => 'application/json'),
      'data'    => $config->rawJSON,
      //      'timeout' => 10,
      'method'  => 'POST',
    )
  );

  // @TODO: Request failure handling.

  $pk_obj = json_decode($result->data);

  $dir = "public://pkplugins/$subtype/{$config->instance_id}";
  if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
    // @TODO: Failure handling
  }

  // Fetch the body html artifact.
  $save_result  = file_unmanaged_save_data(
    $pk_obj->body,
    "$dir/body.html",
    FILE_EXISTS_REPLACE
  );
  $pk_obj->body = $save_result;


  if ($save_result == FALSE) {
    // @TODO: do something.
  }

  $weight = 100;

  // For now, we're leaving out JS
  // @TODO: Figure out a long term solution here.
  unset($pk_obj->assets['footer_js']);
  unset($pk_obj->assets['js']);

  // Fetch and update the assets in the dependency list. (if local)
  foreach ($pk_obj->assets as $type => $assets) {
    switch ($type) {
      case 'footer_js':
        $asset_type = 'js';
        break;
      default:
        $asset_type = $type;
        break;
    }
    foreach ($assets as $asset_url) {
      // If the asset is being loaded with arbitrary scheme, assume external.
      if (_pkplugin_strbegins($asset_url, '//')) {

        $pk_obj->attachments[$asset_type][$asset_url] = array(
          'type'   => 'external',
          'scope'  => 'header',
          'group'  => JS_DEFAULT,
          'weight' => $weight++,
        );
        continue;
      }

      // Leading double slashes eliminated above, leaving only relatives.
      if (_pkplugin_strbegins($asset_url, '/')) {

        $asset_url                                    = $patternkit_host . $asset_url;
        $pk_obj->attachments[$asset_type][$asset_url] = array(
          'type'   => 'external',
          'scope'  => $type == 'footer_js' ? 'footer' : 'header',
          'group'  => JS_DEFAULT,
          'weight' => $weight++,
        );

        // What follows is only for store/cache model. We cannot use currently.
        $url       = $patternkit_host . $asset_url;
        $asset_src = drupal_http_request($url);
        $dest_path = "$dir/" . md5($asset_src->data) . ".$asset_type";

        $save_result = file_unmanaged_save_data(
          $asset_src->data,
          $dest_path,
          FILE_EXISTS_REPLACE
        );

        if ($save_result == FALSE) {
          // @TODO: handle failure.
        }

        // Convert stream wrapper to relative path, if appropriate.
        $scheme = file_uri_scheme($save_result);
        if ($scheme && file_stream_wrapper_valid_scheme($scheme)) {
          $wrapper     = file_stream_wrapper_get_instance_by_scheme($scheme);
          $save_result = $wrapper->getDirectoryPath() . "/" . file_uri_target($save_result);
        }

        $pk_obj->attachments[$asset_type][$save_result] = array(
          'type'   => 'file',
          'scope'  => $type == 'footer_js' ? 'footer' : 'header',
          'weight' => $weight++,
        );
      }
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
 *
 * @return
 *
 */
function _pkplugins_fetch_fragment_assets($subtype, $config) {
  $patternkit_host = variable_get('pkplugins_pk_host', 'http://10.254.254.254:8080');

  $url    = $patternkit_host . '/api/render/html';
  $result = drupal_http_request(
    $url,
    array(
      'headers' => array('Content-Type' => 'application/json'),
      'data'    => $config->rawJSON,
      //      'timeout' => 10,
      'method'  => 'POST',
    )
  );

  // @TODO: Request failure handling.

  // Create the stub object.
  $pk_obj = (object) array(
    'pattern'     => $subtype,
    'attachments' => array(),
    'body'        => 'fragment.html',
  );

  $dir = "public://pkplugins/$subtype/{$config->instance_id}";
  if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
    // @TODO: Failure handling
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
    $wrapper     = file_stream_wrapper_get_instance_by_scheme($scheme);
    $save_result = $wrapper->getDirectoryPath() . "/" . file_uri_target($save_result);
  }

  $pk_obj->body = $save_result;


  if ($save_result == FALSE) {
    // @TODO: do something.
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
 *
 * @return
 *
 */
function _pkplugins_fetch_webcomponent_assets($subtype, $config) {
  $patternkit_host = variable_get('pkplugins_pk_host', 'http://10.254.254.254:8080');

  $url    = $patternkit_host . '/api/render/webcomponent';
  $result = drupal_http_request(
    $url,
    array(
      'headers' => array('Content-Type' => 'application/json'),
      'data'    => $config->rawJSON,
      //      'timeout' => 10,
      'method'  => 'POST',
    )
  );

  // @TODO: Request failure handling.

  // Create the stub object.
  $pk_obj = (object) array(
    'pattern'     => $subtype,
    'attachments' => array(),
    'body'        => 'fragment.html',
  );

  $dir = "public://pkplugins/$subtype/{$config->instance_id}";
  if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
    // @TODO: Failure handling
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
    $wrapper     = file_stream_wrapper_get_instance_by_scheme($scheme);
    $save_result = $wrapper->getDirectoryPath() . "/" . file_uri_target($save_result);
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
    // @TODO: do something.
  }

  return $pk_obj;
}

/**
 * Determine if a string starts with a string.
 *
 * @param string $haystack
 *   String to search.
 * @param        $needle
 *   String to search for.
 *
 * @return bool
 *   TRUE if found, FALSE if not.
 */
function _pkplugin_strbegins($haystack, $needle) {
  return substr($haystack, 0, strlen($needle)) == $needle;
}