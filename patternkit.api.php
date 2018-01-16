<?php

/**
 * @file
 * Hooks provided by Patternkit.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Add a Drupal-cached Patternkit Library.
 *
 * @return PatternkitDrupalCachedLib[]
 *   An array of pattern libraries.
 */
function hook_patternkit_library() {
  $rest_lib = new PatternkitRESTLib();
  return array($rest_lib);
}

/**
 * @} End of "addtogroup hooks".
 */

// @todo Add JSON Schema spec.
