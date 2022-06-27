<?php

namespace Drupal\patternkit\Plugin\PatternFieldProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Swaggest\JsonSchema\SchemaContract;

/**
 * Interface for pattern_field_processor plugins.
 */
interface PatternFieldProcessorInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Test whether the FieldProcessor should apply to this schema property.
   *
   * @param \Swaggest\JsonSchema\SchemaContract $propertySchema
   *   The schema for the property being processed.
   * @param mixed|null $propertyValue
   *   The value saved for the property being processed.
   *
   * @return bool
   *   True if the processor applies to this property or false if not.
   */
  public function applies(SchemaContract $propertySchema, $propertyValue = NULL): bool;

  /**
   * Apply the processor to the given property and return the transformed value.
   *
   * @param \Swaggest\JsonSchema\SchemaContract $propertySchema
   *   The schema for the property being processed.
   * @param mixed $value
   *   The value saved for the property being processed.
   * @param array $context
   *   Any context values available for the pattern.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleableMetadata
   *   A bubbleable metadata object to capture any necessary context during
   *   processing.
   *
   * @return mixed
   *   The processed value for this property. This value should match the value
   *   type for the property being processed.
   */
  public function apply(SchemaContract $propertySchema, $value, array $context, BubbleableMetadata $bubbleableMetadata);

}
