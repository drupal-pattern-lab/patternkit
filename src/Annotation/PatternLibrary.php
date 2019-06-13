<?php

namespace Drupal\patternkit\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Drupal\patternkit\PatternLibraryPluginDefinition;

/**
 * Defines a Pattern Library annotation object.
 *
 * Pattern Libraries such as 'twig', 'REST', 'react' etc. define methods for
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
  public $id;

  /**
   * The human-readable name.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $label;

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
  public $description;

  /**
   * The human-readable category.
   *
   * @var string
   *
   * @see \Drupal\Component\Plugin\CategorizingPluginManagerInterface
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * The layout plugin class.
   *
   * This default value is used for plugins defined in layouts.yml that do not
   * specify a class themselves.
   *
   * @var string
   */
  public $class = PatternLibraryPluginDefault::class;

  /**
   * {@inheritdoc}
   */
  public function get() {
    return new PatternLibraryPluginDefinition($this->definition);
  }

}
