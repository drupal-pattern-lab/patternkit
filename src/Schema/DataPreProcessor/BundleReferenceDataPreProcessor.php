<?php

namespace Drupal\patternkit\Schema\DataPreProcessor;

use Drupal\patternkit\Schema\SchemaRefHandler;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Schema;

/**
 * A data preprocessor that replaces external references with bundled pointers.
 *
 * This preprocessor is most useful when trying to bundle schemas with one or
 * more references to other schemas into a single document. This preprocessor
 * assumes the referenced schemas have been bundled into the schema being
 * processed, and JSON pointers to their content are available in the bundleMap
 * parameter.
 */
class BundleReferenceDataPreProcessor implements DataPreProcessor {

  /**
   * The schema handler service.
   *
   * @var \Drupal\patternkit\Schema\SchemaRefHandler
   */
  protected SchemaRefHandler $schemaRefHandler;

  /**
   * A collection of JSON pointers keyed by namespaced pattern ids.
   *
   * @var array
   */
  protected array $bundleMap = [];

  /**
   * Create a new SchemaRefObserver instance.
   *
   * @param \Drupal\patternkit\Schema\SchemaRefHandler $schemaRefHandler
   *   The schema handler service.
   * @param array $bundleMap
   *   An associative array of JSON pointers keyed by the namespaced pattern id
   *   whose schema content they point to.
   */
  public function __construct(SchemaRefHandler $schemaRefHandler, array $bundleMap = []) {
    $this->schemaRefHandler = $schemaRefHandler;
    $this->bundleMap = $bundleMap;
  }

  /**
   * Set the bundled reference map to be used.
   *
   * @param array $bundleMap
   *   An associative array of JSON pointers keyed by the namespaced pattern id
   *   whose schema content they point to.
   */
  public function setBundleMap(array $bundleMap): void {
    $this->bundleMap = $bundleMap;
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
      $url = is_object($data) ? $data->{'$ref'} : $data['$ref'];

      if ($parsed = $this->schemaRefHandler->parseNormalizedSchemaReference($url)) {
        if (isset($this->bundleMap[$parsed['asset_id']])) {
          $pointer = $this->bundleMap[$parsed['asset_id']];
          if ($parsed['pointer']) {
            $pointer .= "{$parsed['pointer']}";
          }
          if (is_object($data)) {
            $data->{'$ref'} = $pointer;
          }
          else {
            $data['$ref'] = $pointer;
          }
        }
      }
    }

    return $data;
  }

}
