<?php

namespace Drupal\patternkit\Schema\DataPreProcessor;

use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Schema;

/**
 * Preprocess data to cast arrays to objects if desired by the schema.
 */
class ObjectCoercionDataPreProcessor implements DataPreProcessor {

  /**
   * {@inheritdoc}
   */
  public function process($data, Schema $schema, $import = TRUE) {
    if (property_exists($schema, 'type') && $schema->type == 'object' && is_array($data)) {
      $data = (object) $data;
    }

    return $data;
  }

}
