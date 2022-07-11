<?php

namespace Drupal\patternkit;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\patternkit\Asset\LibraryInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\Schema\SchemaWalkerFactory;
use Swaggest\JsonSchema\Path\PointerUtil;
use Swaggest\JsonSchema\SchemaContract;

/**
 * PatternFieldProcessor plugin manager.
 */
class PatternFieldProcessorPluginManager extends DefaultPluginManager {

  /**
   * Encodes and decodes configuration for storage.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected SerializationInterface $serializer;

  /**
   * Loads discovered patterns.
   *
   * @var \Drupal\patternkit\Asset\LibraryInterface
   */
  protected LibraryInterface $library;

  /**
   * The logger channel for logging any errors or notifications.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The schema walker factory service.
   *
   * @var \Drupal\patternkit\Schema\SchemaWalkerFactory
   */
  protected SchemaWalkerFactory $schemaWalkerFactory;

  /**
   * Constructs PatternFieldProcessorPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization service for unserializing schemas.
   * @param \Drupal\patternkit\Asset\LibraryInterface $library
   *   The pattern library service for loading discovered patterns.
   * @param \Drupal\patternkit\Schema\SchemaWalkerFactory $schemaWalkerFactory
   *   The SchemaWalkerFactory service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   The logger channel for logging any errors.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    SerializationInterface $serializer,
    LibraryInterface $library,
    SchemaWalkerFactory $schemaWalkerFactory,
    LoggerChannelInterface $loggerChannel
  ) {
    parent::__construct(
      'Plugin/PatternFieldProcessor',
      $namespaces,
      $module_handler,
      'Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorInterface',
      'Drupal\patternkit\Annotation\PatternFieldProcessor'
    );

    $this->serializer = $serializer;
    $this->library = $library;
    $this->schemaWalkerFactory = $schemaWalkerFactory;
    $this->logger = $loggerChannel;

    $this->alterInfo('pattern_field_processor_info');
    $this->setCacheBackend($cache_backend, 'pattern_field_processor_plugins');
  }

  /**
   * Run processors on configured pattern values for a given schema.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern being processed.
   * @param array $values
   *   The configured values for the pattern being processed. This value is
   *   passed by reference and manipulated directly.
   * @param array $context
   *   Any context values configured for the pattern being processed.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleableMetadata
   *   A bubbleable metadata object to capture any necessary context during
   *   processing.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\patternkit\Exception\SchemaException
   * @throws \Drupal\patternkit\Exception\SchemaReferenceException
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   */
  public function processSchemaValues(PatternInterface $pattern, array &$values, array $context, BubbleableMetadata $bubbleableMetadata): void {
    // Load and instantiate all defined schema field processors.
    $processors = $this->loadProcessors();

    $schema = $pattern->getSchema();

    try {
      $values = $this->traverseSchema(
        $schema,
        $values,
        [$this, 'applyProcessors'],
        $processors,
        $context,
        $bubbleableMetadata
      );
    }
    catch (SchemaException $schemaException) {
      // Log the error before throwing it further.
      $this->logger->error("Error while processing schema values for the pattern @pattern.<br />Exception:<pre>@msg</pre>Data:<pre>@data</pre>", [
        '@pattern' => $pattern->getAssetId(),
        '@msg' => $schemaException->getMessage(),
        '@data' => print_r($values, TRUE),
      ]);

      throw $schemaException;
    }
  }

  /**
   * Instantiate all defined processors.
   *
   * @return \Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorInterface[]
   *   An array of all instantiated pattern field processor plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function loadProcessors(): array {
    // Load the processors for this phase.
    $processorDefinitions = $this->getDefinitions();

    // Load each processor for application.
    $processors = [];
    foreach ($processorDefinitions as $definition) {
      $processors[$definition['id']] = $this->createInstance($definition['id'], $definition);
    }

    return $processors;
  }

  /**
   * Apply processors to the given schema value.
   *
   * @param \Swaggest\JsonSchema\SchemaContract $propertySchema
   *   The schema for the property to be processed.
   * @param mixed $value
   *   The value assignment for the property being processed.
   * @param \Drupal\patternkit\Plugin\PatternFieldProcessor\PatternFieldProcessorInterface[] $processors
   *   An array of instantiated pattern field processor plugins to be applied.
   * @param array $context
   *   Any context values for application of field processors.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleableMetadata
   *   A bubbleable metadata object to capture any metadata to be maintained.
   *
   * @return mixed
   *   The resulting value after all processors have been applied.
   */
  public function applyProcessors(SchemaContract $propertySchema, $value, array $processors, array $context, BubbleableMetadata $bubbleableMetadata) {
    foreach ($processors as $processor) {
      if ($processor->applies($propertySchema, $value)) {
        // @todo Add validation preventing values that don't match schema expectations.
        // For example, if a render array is returned for a string property,
        // subsequently executed processors on this value won't know to
        // expect or how to handle the render array instead of a string.
        $value = $processor->apply($propertySchema, $value, $context, $bubbleableMetadata);
      }
    }

    return $value;
  }

  /**
   * Recursively traverse over schema properties for the provided values.
   *
   * @param string $schema_json
   *   The JSON string representation of the schema to be traversed.
   * @param array|object $values
   *   The values configured for the object using the provided schema.
   * @param callable $callback
   *   A callback to be executed with each value. The callback should accept the
   *   following arguments:
   *   - $propertySchema: The schema for the property being processed.
   *   - $value: The value provided for the property being processed.
   *   - ...$args: The provided $args parameter(s).
   * @param mixed ...$args
   *   Additional arguments to pass into the provided callback.
   *
   * @return mixed
   *   The modified collection of values after processing.
   *
   * @throws \Drupal\patternkit\Exception\SchemaException
   * @throws \Drupal\patternkit\Exception\SchemaReferenceException
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   */
  public function traverseSchema(string $schema_json, $values, callable $callback, ...$args) {
    $schemaWalker = $this->schemaWalkerFactory->createFromString($schema_json, $values);

    // Iterate over the values provided for the schema.
    foreach ($schemaWalker as $key => $value) {
      $propertySchema = $schemaWalker->getPropertySchema();

      // @todo Add handling for processing values with an unassociated property schema.
      if ($propertySchema !== NULL) {
        $value = call_user_func($callback, $propertySchema, $value, ...$args);

        // Update the value directly within the iterator.
        $schemaWalker->setCurrentValue($value);
      }
      else {
        $path = $schemaWalker->getSubIterator()->getSchema()->getDocumentPath();
        $valuePath = PointerUtil::getDataPointer($path);
        $this->logger->debug("Unknown property schema for %key at %path given the value:"
          . "<br /><pre>@propertyValue</pre>"
          . "<br /><br /><strong>Full value set</strong><br /><pre>@values</pre>", [
            '%key' => $key,
            '%path' => $valuePath,
            '@propertyValue' => var_export($value, TRUE),
            '@values' => var_export($values, TRUE),
          ]);
      }
    }

    // Get a copy of the altered array from the iterator since the values were
    // changed there. Iterators cannot pass their values by reference, so this
    // workaround was needed.
    return $schemaWalker->getArrayCopy();
  }

}
