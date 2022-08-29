<?php

namespace Drupal\patternkit\Schema\DataPreProcessor;

use Drupal\patternkit\Schema\SchemaRefHandler;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Schema;

/**
 * A data preprocessor that observes and records references during processing.
 */
class SchemaRefObserver implements DataPreProcessor {

  /**
   * The schema handler service.
   *
   * @var \Drupal\patternkit\Schema\SchemaRefHandler
   */
  protected SchemaRefHandler $schemaRefHandler;

  /**
   * A collection of references observed during schema processing.
   *
   * @var array
   */
  protected array $references = [];

  /**
   * Create a new SchemaRefObserver instance.
   *
   * @param \Drupal\patternkit\Schema\SchemaRefHandler $schemaRefHandler
   *   The schema handler service.
   */
  public function __construct(SchemaRefHandler $schemaRefHandler) {
    $this->schemaRefHandler = $schemaRefHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function process($data, Schema $schema, $import = TRUE) {
    // We have nothing to observe if we're not importing.
    if (!$import) {
      return $data;
    }

    if (property_exists((object) $data, '$ref')) {
      $url = is_object($data) ? $data->{'$ref'} : $data['$ref'];
      // @todo Figure out why the document path is empty at this point.
      $this->references[$url][] = $schema->getDocumentPath();
    }

    return $data;
  }

  /**
   * Get all references observed in the schema.
   *
   * @return string[][]
   *   An array of arrays keyed by the observed reference Url, and containing an
   *   array of schema paths where the reference was observed.
   */
  public function getReferences(): array {
    return $this->references;
  }

  /**
   * Get observed references external to the schema.
   *
   * @param string[] $exclusions
   *   (optional) An array of references that should be excluded from results.
   *   The most notable use for this is to filter out self-references to a
   *   pattern being processed.
   *
   * @return string[]
   *   An array of external references from the schema.
   */
  public function getExternalReferences(array $exclusions = []): array {
    $references = [];

    foreach (array_keys($this->references) as $reference) {
      if ($asset_id = $this->getAssetFromReference($reference)) {
        $references[$asset_id] = $asset_id;
      }
    }

    // Filter out all exclusions.
    $filtered = array_diff($references, $exclusions);

    // Return only a list of the found references.
    return array_values($filtered);
  }

  /**
   * Parse an asset ID from an encountered reference.
   *
   * @param string $reference
   *   The encountered reference string ot be parsed.
   *
   * @return string|false
   *   The namespaced asset ID or false if one could not be identified.
   */
  protected function getAssetFromReference(string $reference) {
    if (str_starts_with($reference, '@')) {
      $parsed = $this->schemaRefHandler->parseNamespacedSchemaReference($reference);
    }
    elseif (str_starts_with($reference, '/api/patternkit/')) {
      $parsed = $this->schemaRefHandler->parseNormalizedSchemaReference($reference);
    }
    else {
      return FALSE;
    }

    return $parsed['asset_id'] ?? FALSE;
  }

}
