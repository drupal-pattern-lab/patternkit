<?php

/**
 * @file
 * API Callbacks for Patternkit endpoints.
 */

/**
 * Returns the JSON-encoded Patternkit schema for the provided pattern.
 *
 * @param string $library
 *   The name of the pattern library to search.
 * @param string $pattern
 *   The name of the pattern to use for retrieving the schema.
 */
function patternkit_api_pattern_schema($library, $pattern) {
  $subtype = $pattern;
  if (!empty($library)) {
    $subtype = "{$library}_{$pattern}";
  }
  drupal_json_output(_patternkit_get_metadata($subtype));
}
