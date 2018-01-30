<?php

/**
 * Interface PatternLibInterface.
 */
interface PatternkitLibInterface {

  /**
   * Creates a new pattern in this library.
   *
   * @param object|array $schema
   *   Optional JSON Schema to preset the pattern.
   *
   * @return \PatternkitPattern
   *   The new pattern.
   */
  public function createPattern($schema = array());

  /**
   * Fetches all assets for a pattern.
   *
   * @param \PatternkitPattern $pattern
   *   The pattern to use for asset retrieval.
   * @param \PatternkitEditorConfig $config
   *   The configuration object to use for provisioning the pattern.
   *
   * @return \PatternkitPattern
   *   The pattern parameter with updated asset references.
   */
  public function fetchPatternAssets(PatternkitPattern $pattern,
    \PatternkitEditorConfig $config);

  /**
   * Returns rendered markup for a provided pattern.
   *
   * @param \PatternkitPattern $pattern
   *   The pattern to render.
   * @param \PatternkitEditorConfig $config
   *   The editor configuration for the pattern.
   *
   * @return string
   *   The rendered pattern HTML.
   */
  public function getRenderedPatternMarkup(PatternkitPattern $pattern,
    PatternkitEditorConfig $config);

  /**
   * Returns the id of the Pattern Library.
   *
   * @return string
   *   The Pattern Library id.
   */
  public function getId();

  /**
   * Returns the title of the Pattern Library.
   *
   * @return string
   *   The Pattern Library title.
   */
  public function getTitle();

  /**
   * Returns renderable data or markup for a pattern editor.
   *
   * @param string|null $subtype
   *   If specified, return an editor customized for this subtype.
   * @param \PatternkitEditorConfig $config
   *   Optional configuration settings for the editor.
   *
   * @return mixed
   *   The renderable pattern editor.
   */
  public function getEditor($subtype = NULL, PatternkitEditorConfig $config = NULL);

  /**
   * Utility function to get specified Patternkit module metadata.
   *
   * @param string|null $subtype
   *   If specified, return only metadata for this subtype.
   *
   * @return array|null|PatternkitPattern
   *   Array of metadata objects found or object if specific module requested.
   */
  public function getMetadata($subtype = NULL);

}
