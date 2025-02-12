<?php

namespace Drupal\patternkit\Schema;

/**
 * A trait for accessing the SchemaFactory service and setting it for use.
 *
 * @see \Drupal\patternkit\Schema\SchemaFactory
 *
 * @internal
 */
trait SchemaFactoryTrait {

  /**
   * The schema factory service.
   *
   * @var \Drupal\patternkit\Schema\SchemaFactory
   */
  protected SchemaFactory $schemaFactory;

  /**
   * Set the schema factory service to be used.
   *
   * @param \Drupal\patternkit\Schema\SchemaFactory $schemaFactory
   *   The schema factory service to be used.
   */
  public function setSchemaFactory(SchemaFactory $schemaFactory): void {
    $this->schemaFactory = $schemaFactory;
  }

  /**
   * Lazy-load the Schema Factory service.
   *
   * @return \Drupal\patternkit\Schema\SchemaFactory
   *   The loaded Schema Factory service.
   */
  protected function schemaFactory(): SchemaFactory {
    if (!isset($this->schemaFactory)) {
      $this->schemaFactory = \Drupal::service('patternkit.schema.schema_factory');
    }

    return $this->schemaFactory;
  }

}
