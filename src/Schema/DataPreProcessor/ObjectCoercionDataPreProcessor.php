<?php

namespace Drupal\patternkit\Schema\DataPreProcessor;

use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Schema;

/**
 * Preprocess data to cast arrays to objects if desired by the schema.
 */
class ObjectCoercionDataPreProcessor implements DataPreProcessor {

  /**
   * Process the provided schema data.
   *
   * @param mixed $data
   *   The original data prior to processing.
   * @param \Swaggest\JsonSchema\Schema $schema
   *   The related schema for the data being processed.
   * @param bool $import
   *   A boolean flag indicating if schema data is currently being imported.
   *
   * @return mixed
   *   Processed data.
   */
  public function process($data, Schema $schema, $import = TRUE) {
    if (property_exists($schema, 'type') && $schema->type == 'object' && is_array($data)) {
      $data = (object) $data;
    }

    return $data;
  }

}
