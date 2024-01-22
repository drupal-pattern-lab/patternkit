<?php

namespace Drupal\patternkit\Schema\DataPreProcessor;

use Drupal\patternkit\Schema\SchemaRefHandler;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Schema;

/**
 * A data preprocessor that normalizes schema references.
 */
class RefNormalizerDataPreProcessor implements DataPreProcessor {

  /**
   * The schema handler service for working with schema content.
   *
   * @var \Drupal\patternkit\Schema\SchemaRefHandler
   */
  protected SchemaRefHandler $schemaRefHandler;

  /**
   * Create a new RefNormalizerDataPreProcessor instance.
   *
   * @param \Drupal\patternkit\Schema\SchemaRefHandler $schemaRefHandler
   *   The schema handler service.
   */
  public function __construct(SchemaRefHandler $schemaRefHandler) {
    $this->schemaRefHandler = $schemaRefHandler;
  }

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
    // We have nothing to observe if we're not importing.
    if (!$import) {
      return $data;
    }

    if (property_exists((object) $data, '$ref')) {
      $url = $data->{'$ref'};

      if ($url[0] === '@') {
        $data->{'$ref'} = $this->schemaRefHandler->normalizeNamespacedSchemaReference($url);
      }
    }

    return $data;
  }

}
