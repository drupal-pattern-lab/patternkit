<?php

namespace Drupal\patternkit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines pattern_field_processor annotation object.
 *
 * @Annotation
 */
class PatternFieldProcessor extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
