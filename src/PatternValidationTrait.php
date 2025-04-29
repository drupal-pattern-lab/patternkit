<?php

namespace Drupal\patternkit;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\patternkit\Schema\ContextBuilderTrait;
use Drupal\patternkit\Schema\SchemaFactoryTrait;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\SchemaContract;

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
   * Validate provided content against a provided schema string.
   *
   * @param string $schema_string
   *   A JSON string containing the schema to be used for validation.
   * @param string $content_string
   *   A JSON string containing the data to be validated against the provided
   *   schema.
   *
   * @return bool
   *   TRUE if the content is valid for the given schema, FALSE otherwise.
   */
  protected function validateContent(string $schema_string, string $content_string): bool {
    [
      'schema' => $schema,
      'context' => $context,
    ] = $this->getSchema($schema_string);

    // As long as this is parsed as an associative array, the swaggest library
    // will fail to properly validate "requires" properties. Until more general
    // JSON parsing throughout the Patternkit module is standardized to NOT use
    // associative arrays, however, this should remain as an associative array
    // for consistency.
    // See https://www.drupal.org/project/patternkit/issues/3294740.
    $content = json_decode($content_string, associative: TRUE);

    return $this->validate($schema, $context, $content);
  }

  /**
   * Validate parsed content against a pattern's schema.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern instance for content to be validated against.
   * @param array $content
   *   The content values being assigned to the pattern component.
   *
   * @return bool
   *   TRUE if the content is valid for the given schema, FALSE otherwise.
   */
  protected function validatePattern(PatternInterface $pattern, array $content): bool {
    // Create a schema object instance with the pattern's saved schema.
    $schema_string = $pattern->getSchema();

    [
      'schema' => $schema,
      'context' => $context,
    ] = $this->getSchema($schema_string);

    return $this->validate($schema, $context, $content);
  }

  /**
   * Load a schema from the provided JSON string.
   *
   * @param string $schema_string
   *   A JSON string containing a JSON schema to be parsed.
   *
   * @return array{'schema': \Swaggest\JsonSchema\SchemaContract, 'context': \Swaggest\JsonSchema\Context}
   *   An associative array of the parsed schema object and a context for use
   *   during validation.
   *
   * @throws \Drupal\patternkit\Exception\SchemaException
   * @throws \Drupal\patternkit\Exception\SchemaReferenceException
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   */
  private function getSchema(string $schema_string): array {
    $schema = $this->schemaFactory()->createInstance($schema_string);

    // Load the default context for use during validation.
    $context = $this->contextBuilder()->getDefaultContext();

    return [
      'schema' => $schema,
      'context' => $context,
    ];
  }

  /**
   * Validate the provided content against the loaded JSON schema object.
   *
   * @param \Swaggest\JsonSchema\SchemaContract $schema
   *   The loaded JSON schema object to be used for validation.
   * @param \Swaggest\JsonSchema\Context $context
   *   The schema context to use during validation.
   * @param mixed $content
   *   The parsed content to be validated against the provided schema.
   *
   * @return bool
   *   TRUE if the content is valid for the given schema, FALSE otherwise.
   */
  private function validate(SchemaContract $schema, Context $context, mixed $content): bool {
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
