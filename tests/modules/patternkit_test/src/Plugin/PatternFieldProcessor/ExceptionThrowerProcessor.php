<?php

namespace Drupal\patternkit_test\Plugin\PatternFieldProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorBase;
use Swaggest\JsonSchema\SchemaContract;

/**
 * A pattern field processor for testing to throw exceptions when flagged.
 *
 * When processing patterns, if this processor encounters a value with an
 * 'exception' key, a new SchemaException is thrown.
 *
 * @PatternFieldProcessor(
 *   id = "exception",
 *   label = @Translation("Exception"),
 *   description = @Translation("Testing processor to throw exceptions.")
 * )
 */
class ExceptionThrowerProcessor extends PatternFieldProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function applies(SchemaContract $propertySchema, $propertyValue = NULL): bool {
    return is_array($propertyValue) && isset($propertyValue['exception']);
  }

  /**
   * {@inheritdoc}
   */
  public function apply(SchemaContract $propertySchema, $value, $context, BubbleableMetadata $bubbleableMetadata) {
    $message = $value['exception'];
    throw new SchemaException($message);
  }

}
