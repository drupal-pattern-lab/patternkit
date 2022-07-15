<?php

namespace Drupal\patternkit\Schema;

use Drupal\patternkit\Exception\SchemaValidationException;
use Swaggest\JsonSchema\Exception as JsonSchemaException;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\SchemaContract;
use Swaggest\JsonSchema\Structure\ObjectItemContract;

/**
 * A utility class with helper methods for interacting with schemas.
 */
class SchemaHelper {

  /**
   * Identify if a schema uses composition constraints related to traversal.
   *
   * With the assumption that data at this point has already validated, the only
   * composition constraints of concern for traversal where the constraint
   * related to a specific value are:
   * - 'anyOf'
   * - 'oneOf'
   * - 'allOf'
   *
   * @param \Swaggest\JsonSchema\SchemaContract $schema
   *   The schema to be tested.
   *
   * @return bool
   *   TRUE if composition rules are used in the schema. FALSE otherwise.
   */
  public static function isCompositionSchema(SchemaContract $schema): bool {
    foreach (['anyOf', 'oneOf', 'allOf'] as $constraint) {
      // Test like this instead of using isset() or empty() to avoid triggering
      // an exception if we're working with a Wrapper instance.
      // @see \Swaggest\JsonSchema\Wrapper::__isset
      if (property_exists($schema, $constraint) && $schema->$constraint !== NULL) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Identify the schema matching a value in a composed constraint.
   *
   * Composed constraints to be tested include:
   * - 'anyOf'
   * - 'oneOf'
   * - 'allOf'
   *
   * @param \Swaggest\JsonSchema\SchemaContract $schema
   *   The schema to be tested.
   * @param mixed $value
   *   The value being assigned into the specified schema property.
   *
   * @return \Swaggest\JsonSchema\SchemaContract|null
   *   The schema that validates for the given value or NULL if one could not be
   *   identified.
   *
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   *   Throws an exception if the given value fails to validate against the
   *   provided schema.
   */
  public static function getCompositionSchema(SchemaContract $schema, $value): ?SchemaContract {
    // Cast associative arrays to an object for validation against object
    // constraints if necessary.
    // Test with PHP core function if available (PHP >= 8.1).
    // @todo Consider adding a polyfil dependency or in-module implementation.
    if (!function_exists('array_is_list')) {
      require_once __DIR__ . '/../../patternkit.polyfil.php';
    }
    $value = is_array($value) && !array_is_list($value) ? static::castArrayToObject($value) : $value;

    try {
      // Get a globally configured execution context for use in schema
      // operations. This will ensure expected options as well as configured
      // remote reference providers and data preprocessors are available.
      $context = \Drupal::service('patternkit.schema.schema_factory')->getDefaultContext();
      $validationResult = $schema->in($value, $context);
    }
    catch (InvalidValue $exception) {
      // Set the parent document path for context on the exception.
      $exception->addPath($schema->getDocumentPath());

      // Wrap the underlying exception in our own class to abstract the library
      // in use.
      $wrappedException = new SchemaValidationException($exception->getMessage(), $exception->getCode(), $exception);
      $wrappedException->setSchema($schema);

      throw $wrappedException;
    }

    // Complex values validate and return a schema object, so we can use that to
    // identify which rules were matched.
    if ($validationResult instanceof ObjectItemContract) {
      $documentPath = $validationResult->getDocumentPath();

      // Parse the document path from the object match to identify which schema
      // rule to load as the property schema.
      $parts = explode('->', $documentPath);
      $matches = [];
      preg_match('#^(\w+)\[(\d+)\]$#', $parts[1], $matches);
      [, $rule, $index] = $matches;

      // Load the matching schema rule from the original schema object in order
      // to ensure the full schema is loaded in the case of ref values.
      return $schema->$rule[$index];
    }
    // Simple values only return the value itself, so we'll need to manually
    // traverse the constraints and identify what applied.
    else {
      // @todo Evaluate whether allOf needs to be processed separately and aggregated.
      $constraints = $schema->jsonSerialize();
      $composition = array_merge(
        $constraints->anyOf ?? [],
        $constraints->oneOf ?? [],
        $constraints->allOf ?? [],
      );

      // Return the first composition schema that matches the given value.
      foreach ($composition as $constraint) {
        try {
          if ($constraint->in($value)) {
            return $constraint;
          }
        }
        catch (JsonSchemaException $ignored) {
          // Ignore validation exceptions at this point since we're expecting
          // them until we find one of the constraints that successfully
          // applies.
        }
      }

      return NULL;
    }
  }

  /**
   * Recursively cast an array value to an object.
   *
   * @param array $value
   *   An array of values, usually an associative array, to recursively cast to
   *   stdClass objects.
   *
   * @return object
   *   The provided array values recursively cast as stdClass objects.
   */
  public static function castArrayToObject(array $value): object {
    return json_decode(json_encode($value));
  }

}
