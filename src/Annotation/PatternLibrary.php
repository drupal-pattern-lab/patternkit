<?php

namespace Drupal\patternkit\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\patternkit\PatternLibraryPluginDefault;

/**
 * Defines a Pattern Library annotation object.
 *
 * Pattern libraries such as 'file, 'json', 'twig', etc. define methods for
 * parsing, loading, editing, and rendering their patterns.
 *
 * Plugin namespace: Plugin\PatternLibrary
 *
 * @see \Drupal\patternkit\PatternLibraryInterface
 * @see \Drupal\patternkit\PatternLibraryPluginDefault
 * @see \Drupal\patternkit\PatternLibraryPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class PatternLibrary extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public string $label;

  /**
   * An optional description for advanced layouts.
   *
   * Sometimes layouts are so complex that the name is insufficient to describe
   * a layout such that a visually impaired administrator could layout a page
   * for a non-visually impaired audience. If specified, it will provide a
   * description that is used for accessibility purposes.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public string $description;

  /**
   * The layout plugin class.
   *
   * This default value is used for plugins defined in layouts.yml that do not
   * specify a class themselves.
   *
   * @var string
   */
  public string $class = PatternLibraryPluginDefault::class;

}
