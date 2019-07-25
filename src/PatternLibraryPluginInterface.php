<?php

namespace Drupal\patternkit;

use Drupal\Core\Extension\Extension;

/**
 * Service that renders a collection of patterns.
 */
interface PatternLibraryPluginInterface {

  /**
   * Returns renderable data or markup for a pattern editor.
   *
   * @param \Drupal\patternkit\Pattern|null $pattern
   *   If specified, return an editor customized for this pattern.
   * @param PatternEditorConfig $config
   *   Optional configuration settings for the editor.
   *
   * @return mixed
   *   The renderable pattern editor.
   */
  public function getEditor(Pattern $pattern = NULL, PatternEditorConfig $config = NULL);

  /**
   * Returns metadata for patterns within the provided collection path.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to retrieve pattern metadata from.
   * @param array $library
   *   The metadata for the library that is being retrieved.
   * @param string $path
   *   The path to the pattern library collection.
   *
   * @return \Drupal\patternkit\Pattern[]
   *   The resulting pattern metadata.
   */
  public function getMetadata(Extension $extension, array $library, $path): array;

  /**
   * Returns renderable data or markup for a provided array of patterns.
   *
   * @param array $assets
   *   An array of \Drupal\patternkit\Pattern to render.
   *
   * @return array
   *   Renderable data or markup.
   */
  public function render(array $assets): array;

}
