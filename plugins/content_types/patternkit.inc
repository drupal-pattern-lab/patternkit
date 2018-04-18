<?php

/**
 * @file
 * Patternkit Panes.
 *
 * Exposes patternkit modules to panels for placement on the page.
 * @author Jason Smith <jason.smith@redhat.com>.
 *
 * @maintainers Jason Smith <jason.smith@redhat.com>
 *   Derek Reese <derek.reese@redhat.com>
 */

require_once __DIR__ . '/../../include/utility.inc';

/**
 * Plugin declaration for panels.
 *
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 *
 * The 'all contexts' is NEEDED to be able to use substitution strings.
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
  'all contexts' => TRUE,
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
        'The patternkit framework module (:module) appears to be missing. Remove from this panel, or replace the missing code and clear caches.',
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
  /** @var \PatternkitPattern $module_meta */
  foreach ($modules as $module_name => $module_meta) {
    $module_key = $module_meta->subtype;
    $library = $module_meta->library->getTitle();
    $legacy_module_key = $module_meta->legacySubtype;
    $category = "$library: "
      . (!empty($module_meta->category) ? $module_meta->category : 'uncategorized');

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
    $title = 'BROKEN/MISSING MODULE';
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
    $block->content = 'BROKEN/MISSING MODULE';

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
  if (empty($module)) {
    return $form;
  }

  // Remove the title override fields.
  unset($form['override_title_markup'],
    $form['override_title'],
    $form['override_title_text'],
    $form['override_title_heading']);

  // @TODO: Re-enable the other formats like JSON and webcomponent.
  $form['presentation_style'] = array(
    '#type'          => 'select',
    '#title'         => 'Presentation style',
    '#options'       => array(
      'html'         => 'HTML inline',
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

  $config = NULL;
  if (!empty($conf['instance_config'])) {
    $config = new PatternkitEditorConfig(json_decode($conf['instance_config']));
    if (empty($module->schema->properties)
      || array_keys(get_object_vars($module->schema->properties))
      !== array_keys(get_object_vars($config->fields))) {
      drupal_set_message(t("This module's schema has been updated. You may want to recreate this pane."));
    }
  }
  $editor = $module->library->getEditor($subtype, $config);
  if (!is_array($editor)) {
    $editor = array(
      '#type'   => 'markup',
      '#markup' => $editor,
    );
  }
  $form['configuration'] = $editor;

  return $form;
}

/**
 * Implements hook_content_type_edit_form_submit().
 */
function patternkit_patternkit_content_type_edit_form_submit(
  &$form,
  &$form_state
) {
  $subtype = $form_state['values']['module_subtype'];
  $instance_id = $form_state['values']['instance_uuid'];

  // Clear instance render cache.
  $cid = "pkmod:{$subtype}:{$instance_id}";
  cache_clear_all($cid, 'cache_patternkit', TRUE);

  $config = new PatternkitEditorConfig();
  $config->fields = json_decode($form_state['values']['instance_config']);
  $config->instance_id = $instance_id;
  $config->presentation_style = $form_state['values']['presentation_style'];
  $pattern = _patternkit_get_metadata($subtype);
  $pk_obj = $pattern->library->fetchPatternAssets($pattern, $config);
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
  if ($is_processed === NULL) {
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

  // Replace any tokens inside field values.
  array_walk_recursive($config['fields'], function (&$value, $key, $context = array()) {
    if ($key && is_string($value)) {
      $value = ctools_context_keyword_substitute($value, array(), $context);
      $value = token_replace($value);
    }
  }, $context);

  // Get the body of content.
  $content = _patternkit_render_block($module, $instance_id, $config, $context);

  // Replace ctools substitutions (if any) from context.
  $content = ctools_context_keyword_substitute($content, array(), $context);

  // Replace core tokens in the body (if any).
  $content = token_replace(
    $content,
    array(
      'patternkit' => array(
        'PatternkitPattern' => $subtype,
        'instance_id' => $instance_id,
      ),
    )
  );

  // @todo Merge attachment JS and dependencies.
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
    $ttl = variable_get('patternkit_default_module_ttl', PATTERNKIT_DEFAULT_TTL);
  }

  // Save to the cache bin (if caching is enabled).
  if ($cacheable === TRUE) {
    cache_set($cid, $block, 'cache_patternkit', time() + $ttl);
  }

  // @todo Re-evaluate use of this flag.
  // Set flag to let other modules know content is ngpanes stuff.
  $block->ngpane = 1;

  return $block;
}

/**
 * Utility functions.
 *
 * @ingroup utility
 * @{
 */

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
  array $context = NULL) {

  $body = 'MISSING/BROKEN';
  if (!isset($module->subtype)) {
    return $body;
  }
  $subtype = $module->subtype;
  $pattern = _patternkit_get_metadata($subtype);
  if (!$pattern) {
    return $body;
  }
  // @todo Handle context variable replacements.
  return $pattern->getRenderedMarkup(new PatternkitEditorConfig($config));
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

  return $result->content;
}

/**
 * @}
 */
