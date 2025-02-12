<?php

namespace Drupal\patternkit;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\patternkit\Schema\ContextBuilderTrait;
use Drupal\patternkit\Schema\SchemaFactoryTrait;
use Swaggest\JsonSchema\InvalidValue;

/**
 * A supporting trait for validating pattern content.
 */
trait PatternValidationTrait {

  use ContextBuilderTrait;
  use SchemaFactoryTrait;

  /**
   * An array of any exceptions encountered during validation.
   *
   * @var array<\Drupal\patternkit\Exception\SchemaValidationException>
   */
  protected array $exceptions = [];

  /**
   * Validate content being assigned to the pattern component.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern instance for content to be validated against.
   * @param array $content
   *   The content values being assigned to the pattern component.
   *
   * @return bool
   *   True if the content validates successfully. False otherwise.
   */
  protected function validateContent(PatternInterface $pattern, array $content): bool {
    // Create a schema object instance with the pattern's saved schema.
    $schema_string = $pattern->getSchema();
    $schema = $this->schemaFactory()->createInstance($schema_string);

    // Load the default context for use during validation.
    $context = $this->contextBuilder()->getDefaultContext();

    try {
      // Feed the content into the schema object with the default context to
      // test for validation.
      $schema->in($content, $context);
    }
    catch (InvalidValue $exception) {
      $exception = new SchemaValidationException(
        message: 'Assigned content does not validate successfully.',
        previous: $exception,
      );

      // Insert exception at the beginning to keep the most recent exceptions
      // first.
      array_unshift($this->exceptions, $exception);

      // Return false if a validation error was encountered.
      return FALSE;
    }

    // If we got here, no validation error was encountered so the content
    // should be valid.
    return TRUE;
  }

  /**
   * Tests if exceptions have been encountered during validation.
   *
   * @return bool
   *   Returns TRUE if an exception has been encountered, FALSE otherwise.
   */
  public function hasExceptions(): bool {
    return !empty($this->exceptions);
  }

  /**
   * Get an array of all exceptions that have been encountered.
   *
   * @return array<\Drupal\patternkit\Exception\SchemaValidationException>
   *   An array of all validation exceptions that have been encountered.
   */
  public function getExceptions(): array {
    return $this->exceptions;
  }

}
