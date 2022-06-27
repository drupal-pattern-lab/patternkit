<?php

namespace Drupal\patternkit\Schema;

use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\RemoteRefProvider;
use Swaggest\JsonSchema\Schema;
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
   * The reference provider to be used for loading schema references.
   *
   * @var \Swaggest\JsonSchema\RemoteRefProvider
   */
  protected RemoteRefProvider $refProvider;

  /**
   * Create a new SchemaWalkerFactory instance.
   *
   * @param \Swaggest\JsonSchema\RemoteRefProvider $refProvider
   *   The ref provider for loading schema references.
   */
  public function __construct(RemoteRefProvider $refProvider) {
    $this->refProvider = $refProvider;
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
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public function createFromString(string $schema_json, $values): SchemaWalker {
    $schema = Schema::import(json_decode($schema_json), new Context($this->refProvider));

    return $this->createInstance($schema, $values);
  }

}
