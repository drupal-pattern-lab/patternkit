<?php

/**
 * @file
 * Documentation for custom hooks defined in the Patternkit module.
 */

/**
 * Alter the list of registered field processor plugins.
 *
 * @param array $info
 *   The field processor plugin definitions to be altered.
 */
function hook_pattern_field_processor_info_alter(array &$info): void {
  // Override the class for a provided processor.
  if (isset($info['token'])) {
    $info['token']['class'] = '\My\Custom\ProcessorPluginClass';
  }

  // Unregister a provided processor.
  unset($info['wysiwyg']);
}
