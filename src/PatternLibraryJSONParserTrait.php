<?php

namespace Drupal\patternkit;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\patternkit\Asset\PatternDependencyResolver;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Schema\ContextBuilderTrait;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\patternkit\Schema\UnresolvedSchema;

/**
 * Provides methods for parsing JSON schema.
 */
trait PatternLibraryJSONParserTrait {

  use ContextBuilderTrait;

  /**
   * Serializes and de-serializes data.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected SerializationInterface $serializer;

  /**
   * The schema data preprocessor factory service.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessorFactory
   */
  protected DataPreProcessorFactory $dataPreProcessorFactory;

  /**
   * The pattern dependency resolver service.
   *
   * @var \Drupal\patternkit\Asset\PatternDependencyResolver
   */
  protected PatternDependencyResolver $patternDependencyResolver;

  /**
   * Fetches a JSON Schema from a file URI.
   *
   * @param string $file_uri
   *   URI of the JSON file to decode.
   *
   * @return mixed
   *   Truthy mixed data if the contents are successfully decoded.
   */
  public function fetchJsonSchemaAsset(string $file_uri) {
    return $this->serializer::decode(file_get_contents($file_uri));
  }

  /**
   * Process schema content to normalize reference links.
   *
   * This method normalizes reference links for consistency to replace all
   * namespace references with a URL routing through the Patternkit API
   * controller.
   *
   * @param string $schema_json
   *   The raw schema content for normalization.
   *
   * @return string
   *   The schema content after all references have been normalized.
   *
   * @throws \JsonException
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   * @throws \Drupal\patternkit\Exception\MissingDataPreProcessorException
   */
  public function normalizeSchemaReferences(string $schema_json): string {
    $preprocessor = $this->dataPreProcessorFactory->getPreProcessor('normalizer');
    $context_options = [
      'dataPreProcessor' => $preprocessor,
    ];

    // Prepare the context.
    $context = $this->contextBuilder()->getConfiguredContext($context_options);

    // @todo Consolidate this call with other serialization operations.
    $schema_data = json_decode($schema_json, FALSE, 512, JSON_THROW_ON_ERROR);
    $schema = UnresolvedSchema::import($schema_data, $context);

    return json_encode($schema, JSON_THROW_ON_ERROR);
  }

  /**
   * Identify all external dependencies referenced in a schema.
   *
   * @param string $schema_json
   *   The JSON content of the schema to be parsed.
   * @param string $pattern_id
   *   The namespaced identifier for the pattern being parsed.
   *
   * @return string[]
   *   An array of namespaced pattern references referenced within the schema
   *   content.
   *
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public function getSchemaDependencies(string $schema_json, string $pattern_id): array {
    return $this->patternDependencyResolver->getSchemaDependencies($schema_json, $pattern_id);
  }

  /**
   * Test if a string could be a JSON schema object.
   *
   * This test is not definitive to determine if a string represents a JSON
   * schema. It only tests to make sure a JSON string is valid for decoding and
   * is an object.
   *
   * @param string $json
   *   The string to test.
   *
   * @return bool
   *   TRUE if this could be a JSON schema, FALSE otherwise.
   */
  protected function isJson(string $json): bool {
    // For our purposes we only want to accept objects, so we can rule out based
    // on the first non-whitespace character if it isn't an opening brace.
    if (ltrim($json)[0] !== '{') {
      return FALSE;
    }

    // Attempt to decode the string as JSON and return based on errors.
    json_decode($json);
    return json_last_error() === JSON_ERROR_NONE;
  }

  /**
   * Apply necessary processing for discovered pattern schemas.
   *
   * @param string $schema_json
   *   The raw JSON string for the discovered pattern schema.
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern entity being prepared and processed.
   *
   * @return string
   *   The processed JSON schema string with any necessary alterations.
   *
   * @throws \JsonException
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public function processSchema(string $schema_json, PatternInterface $pattern): string {
    // Replace any $ref links with relative paths.
    $processed_schema = $this->normalizeSchemaReferences($schema_json);

    // Identify and record all pattern reference dependencies.
    $dependencies = $this->getSchemaDependencies($processed_schema, $pattern->getAssetId());
    $pattern->setDependencies($dependencies);

    return $processed_schema;
  }

}
