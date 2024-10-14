<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Extension\Extension;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Entity\PatternInterface;

/**
 * Service that renders a collection of patterns.
 */
interface PatternLibraryPluginInterface extends PluginInspectionInterface {

  /**
   * Fetches and loads all assets for a provided Pattern.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern instance to load assets for.
   *
   * @return array{template: string, schema: array}
   *   The loaded assets for the pattern. Should have the following keys:
   *   - string template
   *     A renderable template string.
   *   - array schema
   *     A PHP Schema for the Pattern.
   *
   * @throws \Drupal\patternkit\Exception\PatternAssetException
   *   Throws an exception if required assets are unavailable.
   */
  public function fetchAssets(PatternInterface $pattern): array;

  /**
   * Get the editor render array for a pattern editor form.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern to build a form for.
   * @param array $editor_config
   *   (Optional) Configuration overrides for the behavior of the pattern form.
   *
   * @return array
   *   A render array to produce the pattern form.
   */
  public function getPatternEditor(PatternInterface $pattern, array $editor_config = []): array;

  /**
   * Returns metadata for patterns within the provided collection path.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to retrieve pattern metadata from.
   * @param \Drupal\patternkit\PatternLibrary $library
   *   The metadata for the library that is being retrieved.
   * @param string $path
   *   The path to the pattern library collection.
   *
   * @return \Drupal\patternkit\Entity\Pattern[]
   *   The resulting pattern metadata.
   */
  public function getMetadata(Extension $extension, PatternLibrary $library, string $path): array;

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

  /**
   * Get the library parser for the library plugin.
   *
   * @return \Drupal\patternkit\Asset\PatternLibraryParserInterface
   *   The library parser used by this plugin.
   */
  public function getParser(): PatternLibraryParserInterface;

}
