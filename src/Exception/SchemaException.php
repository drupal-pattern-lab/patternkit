<?php

namespace Drupal\patternkit\Exception;

use Swaggest\JsonSchema\SchemaContract;

/**
 * Base exception for unexpected events during Schema processing.
 */
class SchemaException extends \RuntimeException {

  /**
   * The schema being processed at the time of the exception.
   *
   * @var \Swaggest\JsonSchema\SchemaContract|null
   */
  protected ?SchemaContract $schema = null;

  /**
   * Set the schema being processed at the time of the exception.
   *
   * @param \Swaggest\JsonSchema\SchemaContract $schema
   *   The schema being processed at the time of the exception.
   *
   * @return static
   */
  public function setSchema(SchemaContract $schema) {
    $this->schema = $schema;

    return $this;
  }

  /**
   * Get the schema being processed at the time of the exception.
   *
   * @return \Swaggest\JsonSchema\SchemaContract|null
   *   The schema being processed at the time of the exception or null if it
   *   was not set.
   */
  public function getSchema(): ?SchemaContract {
    return $this->schema;
  }

}
