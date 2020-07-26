<?php

namespace Drupal\patternkit;

use Drupal\Core\Extension\Extension;
use Drupal\patternkit\Entity\PatternInterface;

/**
 * Service that renders a collection of patterns.
 */
interface PatternLibraryPluginInterface {

  /**
   * Fetches and loads all assets for a provided Pattern
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   * @param \Drupal\patternkit\PatternEditorConfig|null $config
   *
   * @return array
   *   The loaded assets for the pattern. Should have the following keys:
   *   - string template
   *     A renderable template string.
   *   - array schema
   *     A PHP Schema for the Pattern.
   */
  public function fetchAssets(PatternInterface $pattern, PatternEditorConfig $config = NULL);

  /**
   * Returns renderable data or markup for a pattern editor.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface|null $pattern
   *   If specified, return an editor customized for this pattern.
   * @param PatternEditorConfig $config
   *   Optional configuration settings for the editor.
   *
   * @return mixed
   *   The renderable pattern editor.
   */
  public function getEditor(PatternInterface $pattern = NULL, PatternEditorConfig $config = NULL);

  /**
   * Returns metadata for patterns within the provided collection path.
   *
   * @param Extension $extension
   *   The extension to retrieve pattern metadata from.
   * @param \Drupal\patternkit\PatternLibrary $library
   *   The metadata for the library that is being retrieved.
   * @param string $path
   *   The path to the pattern library collection.
   *
   * @return \Drupal\patternkit\Entity\Pattern[]
   *   The resulting pattern metadata.
   */
  public function getMetadata(Extension $extension, PatternLibrary $library, $path): array;

  /**
   * Returns renderable data or markup for a provided array of patterns.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface[] $assets
   *   An array of \Drupal\patternkit\Entity\PatternInterface to render.
   *
   * @return array
   *   Renderable data or markup.
   */
  public function render(array $assets): array;

}
