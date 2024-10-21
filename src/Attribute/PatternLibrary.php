<?php

namespace Drupal\patternkit\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\patternkit\PatternLibraryPluginDefault;

/**
 * Defines a Pattern Library attribute object.
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
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PatternLibrary extends Plugin {

  /**
   * Constructs a PatternLibrary attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The description of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $category
   *   (optional) The human-readable category.
   * @param class-string $class
   *   (optional) The layout plugin class. This default value is used for
   *   plugins defined in layouts.yml that do not specify a class themselves.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?TranslatableMarkup $category = NULL,
    public string $class = PatternLibraryPluginDefault::class,
    ?string $deriver = NULL,
  ) {
    parent::__construct($id, $deriver);
  }

}
