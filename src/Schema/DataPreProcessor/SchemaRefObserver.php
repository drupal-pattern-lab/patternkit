<?php

namespace Drupal\patternkit\Schema\DataPreProcessor;

use Drupal\patternkit\Schema\SchemaHelper;
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
   * @var string[]
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
    if ($import) {
      $this->processImport($data, $schema);
    }
    else {
      $this->processValidation($data, $schema);
    }

    return $data;
  }

  /**
   * Track references during import operations.
   *
   * @param mixed $data
   *   Original data being processed.
   * @param \Swaggest\JsonSchema\Schema $schema
   *   The schema being assembled.
   */
  protected function processImport($data, Schema $schema): void {
    if (property_exists((object) $data, '$ref')) {
      $url = is_object($data) ? $data->{'$ref'} : $data['$ref'];
      $this->trackReference($url);
    }
  }

  /**
   * Track references during non-import validation operations.
   *
   * @param mixed $data
   *   The data being processed.
   * @param \Swaggest\JsonSchema\Schema $schema
   *   The schema matching the data being validated.
   *
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   */
  protected function processValidation($data, Schema $schema): void {
    if (SchemaHelper::isCompositionSchema($schema)) {
      // Only record valid references within a composition schema.
      $subschema = SchemaHelper::getCompositionSchema($schema, $data);
      foreach ($subschema->getFromRefs() ?? [] as $fromRef) {
        $this->trackReference($fromRef);
      }
    }
    else {
      foreach ($schema->getFromRefs() ?? [] as $fromRef) {
        // We may get here while traversing within composition schemas with data
        // that doesn't validate against the referenced schema and shouldn't be
        // recorded.
        if (SchemaHelper::isValid($schema, $data)) {
          $this->trackReference($fromRef);
        }
      }
    }
  }

  /**
   * Record a detected schema reference.
   *
   * @param string $reference
   *   The detected reference URL.
   */
  protected function trackReference(string $reference): void {
    // Assign as the key to mitigate duplication.
    $this->references[$reference] = $reference;
  }

  /**
   * Get all references observed in the schema.
   *
   * @return string[]
   *   An array of observed references.
   */
  public function getReferences(): array {
    return array_values($this->references);
  }

  /**
   * Get observed references external to the schema.
   *
   * @param string[] $exclusions
   *   (optional) An array of references that should be excluded from results.
   *   The most notable use for this is to filter out self-references to a
   *   pattern being processed.
   * @param bool $include_unresolved
   *   (optional) Whether to include discovered references that aren't resolved
   *   to known patterns. Defaults to FALSE and excludes unresolved references.
   *
   * @return string[]
   *   An array of external references from the schema. Where possible, all
   *   detected references are resolved to their matching asset ID.
   */
  public function getExternalReferences(array $exclusions = [], bool $include_unresolved = FALSE): array {
    $references = [];

    // Translate all discovered references into asset IDs where possible.
    foreach ($this->references as $reference) {
      if (($asset_id = $this->getAssetFromReference($reference)) || $include_unresolved) {

        $ref = $asset_id ?: $reference;
        $references[$ref] = $ref;
      }
    }

    // Filter out all exclusions.
    $filtered = array_diff($references, $exclusions);

    // Return only a list of the found references.
    return $filtered;
  }

  /**
   * Parse an asset ID from an encountered reference.
   *
   * @param string $reference
   *   The encountered reference string to be parsed.
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

  /**
   * Reset detected references to none.
   */
  public function resetReferences(): void {
    $this->references = [];
  }

}
