<?php

/**
 * @file
 * Provides the admin settings form for Patternkit.
 */

/**
 * Drupal Form API callback for the admin form.
 *
 * @param array $form
 *   Drupal Form API form array.
 * @param array $form_state
 *   Drupal Form API form state array.
 *
 * @return array
 *   A Drupal Form API admin form array.
 */
function patternkit_config_form(array $form, array &$form_state) {
  $libraries = patternkit_pattern_libraries();
  $library_options = array();
  $library_values = array();
  foreach ($libraries as $library) {
    $lib_title = $library->getTitle();
    $lib_desc = $lib_title;
    $lib_metadata = $library->getMetadata();
    if (!empty($lib_metadata)) {
      $library_values[] = $lib_title;
      $lib_desc = t('@title (@count patterns)', array(
        '@title' => $lib_title,
        '@count' => count($lib_metadata),
      ));
    }
    $library_options[$lib_title] = $lib_desc;
  }
  $form['patternkit_libraries'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Enabled Patternkit Libraries'),
    '#options' => $library_options,
    '#default_value' => $library_values,
    '#disabled' => TRUE,
  );

  $form['patternkit_pl_host'] = array(
    '#type' => 'textfield',
    '#title' => t('PatternLab/Kit REST Services Host'),
    '#default_value' => variable_get('patternkit_pl_host',
      'http://localhost:9001'),
    '#size' => '120',
    '#maxlength' => '1023',
  );

  $form['patternkit_cache_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use the Patternkit Library Cache'),
    '#default_value' => variable_get('patternkit_cache_enabled', TRUE),
  );

  $form['patternkit_render_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use the Patternkit Disk Render Cache'),
    '#default_value' => variable_get('patternkit_render_cache', FALSE),
  );

  $form['patternkit_default_module_ttl'] = array(
    '#type' => 'textfield',
    '#title' => t('Patternkit Default Pattern TTL (in ms)'),
    '#default_value' => variable_get('patternkit_default_module_ttl',
      PATTERNKIT_DEFAULT_TTL),
  );

  $form['#submit'][] = 'patternkit_config_form_submit';

  return system_settings_form($form);
}

/**
 * Rebuilds Patternkit data on form save.
 *
 * @param array $form
 *   Drupal Form API form array.
 * @param array $form_state
 *   Drupal Form API form state array.
 *
 * @see system_settings_form_submit()
 */
function patternkit_config_form_submit(array $form, array &$form_state) {
  if ($form_state['values']['patternkit_cache_enabled']
    && !variable_get('patternkit_cache_enabled', TRUE)) {
    $libraries = patternkit_pattern_libraries();
    foreach ($libraries as $library) {
      $library->getCachedMetadata(NULL, TRUE);
    }
  }

  drupal_set_message(t('Rebuilt Patternkit Library Cache.'));
}
