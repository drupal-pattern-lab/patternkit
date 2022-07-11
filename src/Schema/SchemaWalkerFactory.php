<?php

namespace Drupal\patternkit\Schema;

use Swaggest\JsonSchema\SchemaContract;

/**
 * A factory service for creating SchemaWalker instances.
 *
 * This factory service allows the creation of SchemaWalker instances with
 * properly loaded context to support the resolution of normalized Patternkit
 * schemas.
 */
class SchemaWalkerFactory {

  /**
   * The schema factory service for creating new schema instances.
   *
   * @var \Drupal\patternkit\Schema\SchemaFactory
   */
  protected SchemaFactory $schemaFactory;

  /**
   * Create a new SchemaWalkerFactory instance.
   *
   * @param \Drupal\patternkit\Schema\SchemaFactory $schemaFactory
   *   The schema factory service for creating new schema instances.
   */
  public function __construct(SchemaFactory $schemaFactory) {
    $this->schemaFactory = $schemaFactory;
  }

  /**
   * Create a new SchemaWalker instance.
   *
   * @param \Swaggest\JsonSchema\SchemaContract $schema
   *   The schema for reference during traversal with values.
   * @param array|object $values
   *   The values to be used to traverse the schema.
   *
   * @return \Drupal\patternkit\Schema\SchemaWalker
   *   A configured SchemaWalker instance.
   */
  public function createInstance(SchemaContract $schema, $values): SchemaWalker {
    return new SchemaWalker($schema, $values);
  }

  /**
   * Create a new SchemaWalker from a schema's JSON string.
   *
   * @param string $schema_json
   *   The string representation of the schema to be used.
   * @param array|object $values
   *   Values to be traversed against the schema.
   *
   * @return \Drupal\patternkit\Schema\SchemaWalker
   *   A configured SchemaWalker instance.
   *
   * @throws \Drupal\patternkit\Exception\SchemaReferenceException
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   * @throws \Drupal\patternkit\Exception\SchemaException
   */
  public function createFromString(string $schema_json, $values): SchemaWalker {
    $schema = $this->schemaFactory->createInstance($schema_json);

    return $this->createInstance($schema, $values);
  }

}
