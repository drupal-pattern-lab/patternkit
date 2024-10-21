<?php

namespace Drupal\patternkit\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines pattern_field_processor attribute object.
 *
 * Plugin types for preprocessing pattern content data for manipulation prior
 * to rendering.
 *
 * Plugin Namespace: Plugin\PatternFieldProcessor
 *
 * @see \Drupal\patternkit\PatternFieldProcessorPluginManager
 * @see \Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorBase
 * @see \Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PatternFieldProcessor extends Plugin {

  /**
   * Constructs a PatternFieldProcessor attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The description of the plugin.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    ?string $deriver = NULL,
  ) {
    parent::__construct($id, $deriver);
  }

}
