<?php

/**
 * Class DrupalCachedPatternLib.
 */
abstract class PatternkitDrupalCachedLib implements PatternkitLibInterface {

  /**
   * Utility function to get all Patternkit module metadata.
   *
   * @return array
   *   Array of metadata objects found.
   */
  abstract protected function getRawMetadata();

  /**
   * Creates a new pattern in this library.
   *
   * @param object|array $schema
   *   Optional JSON Schema to preset the pattern.
   *
   * @return \PatternkitPattern
   *   The new pattern.
   */
  public function createPattern($schema = array()) {
    return new PatternkitPattern($this, $schema);
  }

  /**
   * Utility function to get specified Patternkit module metadata.
   *
   * @param string|null $subtype
   *   If specified, return only metadata for this subtype.
   * @param bool $reset
   *   Optionally force the meta data to be reloaded.
   *
   * @return array|null|PatternkitPattern
   *   Array of metadata objects found or object if specific module requested.
   */
  public function getCachedMetadata($subtype = NULL, $reset = FALSE) {
    // @todo Use a service UUID handler for cache name.
    $id = $this->getId();
    $cached_metadata = &drupal_static(__METHOD__ . $id);

    // If the static cache doesn't exist, or we've called with reset, rebuild.
    if ($cached_metadata === NULL || $reset) {
      $patternkit_cache_enabled = variable_get('patternkit_cache_enabled', TRUE);
      // If cache is enabled, attempt to load from cache.
      if ($patternkit_cache_enabled
        && ($cache = cache_get("patternkit_pk_metadata_$id"))) {
        $cached_metadata = $cache->data;
      }
      else {
        $cached_metadata = $this->getRawMetadata();
        // Cache the data so that we don't have to build it again.
        // (if cache enabled, otherwise just a slow, redundant memcache set).
        if ($patternkit_cache_enabled === TRUE) {
          // Explicit copy of the data into cache_set to avoid implicit copy.
          cache_set("patternkit_pk_metadata_$id",
            $cached_metadata, 'cache', CACHE_PERMANENT);
        }
      }
    }

    // If we are requesting data for a specific module type, return just
    // that data.
    if ($subtype !== NULL && strtolower($subtype) !== 'none') {
      if (substr($subtype, 0, 3) == 'pk_') {
        $subtype = substr($subtype, 3);
      }
      if (!empty($cached_metadata[strtolower($subtype)])) {
        return $cached_metadata[strtolower($subtype)];
      }
      _patternkit_show_error(
        'Patternkit module does not appear to exist (%module), verify module info/usage.',
        array('%module' => $subtype)
      );

      return NULL;
    }

    return $cached_metadata;
  }

  /**
   * Utility function to get specified Patternkit module metadata.
   *
   * @param string|null $subtype
   *   If specified, return only metadata for this subtype.
   *
   * @return array|null|PatternkitPattern
   *   Array of metadata objects found or object if specific module requested.
   */
  public function getMetadata($subtype = NULL) {
    return $this->getCachedMetadata($subtype);
  }

}
