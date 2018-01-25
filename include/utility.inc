<?php

/**
 * @file
 * Utility functions for Patternkit.
 */

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
