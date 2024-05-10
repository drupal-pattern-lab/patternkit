<?php

namespace Drupal\patternkit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\patternkit\Asset\PatternDependencyResolver;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\Schema\ContextBuilderTrait;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\patternkit\Schema\UnresolvedSchema;

/**
 * A service for loading and preparing pattern instances.
 */
class PatternRepository {

  use ContextBuilderTrait;

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $discovery;

  /**
   * The pattern dependency resolver service.
   *
   * @var \Drupal\patternkit\Asset\PatternDependencyResolver
   */
  protected PatternDependencyResolver $dependencyResolver;

  /**
   * The schema data preprocessor factory service.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessorFactory
   */
  protected DataPreProcessorFactory $dataPreProcessorFactory;

  /**
   * The entity storage handler for the patternkit_pattern entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * A statically cached collection of loaded patterns.
   *
   * @var \Drupal\patternkit\Entity\PatternInterface[]
   */
  protected array $patterns = [];

  /**
   * Constructs a PatternRepository object.
   *
   * @param \Drupal\patternkit\Asset\PatternDiscoveryInterface $discovery
   *   The pattern discovery service.
   * @param \Drupal\patternkit\Asset\PatternDependencyResolver $dependencyResolver
   *   The pattern dependency resolver service.
   * @param \Drupal\patternkit\Schema\DataPreProcessorFactory $dataPreProcessorFactory
   *   The data preprocessor factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    PatternDiscoveryInterface $discovery,
    PatternDependencyResolver $dependencyResolver,
    DataPreProcessorFactory $dataPreProcessorFactory,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->discovery = $discovery;
    $this->dependencyResolver = $dependencyResolver;
    $this->dataPreProcessorFactory = $dataPreProcessorFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get a loaded pattern instance.
   *
   * This method provides a centralized route for loading pattern instances to
   * cut down on repetitive instantiation and schema processing. As such, it is
   * recommended all pattern instantiation is routed through this method instead
   * of instantiating a pattern directly from entity storage or the definition
   * unless there is an explicit reason for doing so.
   *
   * This method statically caches all pattern instances that have been loaded
   * and will attempt to return already instantiated pattern instances if one
   * is available for the requested pattern.
   *
   * If the optional "$bundled" parameter is omitted, whatever pattern instance
   * is already loaded is returned, or a new one is created from the definition
   * with the unbundled schema. If the bundling of the schema doesn't matter for
   * the use case in question, this is the most efficient method for loading
   * the pattern.
   *
   * If a bundled schema is needed, the "$bundled" parameter may be set to true,
   * and the pattern instance will be bundled and statically cached if it is not
   * already available.
   *
   * If an unbundled schema is explicitly needed, the "$bundled" parameter may
   * be set to false, and a new unbundled pattern instance will be created if a
   * statically cached instance with a bundled schema already exists.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern to be loaded.
   * @param bool|null $bundled
   *   (optional) Whether the pattern schema should be bundled with all
   *   dependencies. Defaults to null.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The loaded pattern entity.
   *
   * @throws \Drupal\patternkit\Exception\UnknownPatternException
   *   Throws an exception if no definition is found for the requested pattern.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPattern(string $pattern_id, ?bool $bundled = NULL): PatternInterface {
    if (isset($this->patterns[$pattern_id])) {
      $pattern = $this->patterns[$pattern_id];

      if ($bundled === FALSE && $pattern->isBundled()) {
        // Unset the pattern result, so it can be rebuilt from the definition.
        unset($pattern);
      }
    }

    // If the pattern could not be loaded as needed, rebuild it from the
    // original definition.
    if (!isset($pattern)) {
      $definition = $this->discovery->getPatternDefinition($pattern_id);

      if (is_null($definition)) {
        throw new UnknownPatternException(sprintf('No known pattern definition for "%s".', $pattern_id));
      }

      /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
      $pattern = $this->patternStorage()->create($definition);
      $this->patterns[$pattern_id] ??= $pattern;
    }

    // Ensure the bundling is set properly if a specific option was set.
    if ($bundled && !$pattern->isBundled()) {
      $this->bundleSchema($pattern);
    }

    return $pattern;
  }

  /**
   * Get the namespaced identifiers for all discovered patterns.
   *
   * @return string[]
   *   An array of namespaced pattern identifiers for all discovered patterns.
   */
  public function getAllPatternNames(): array {
    $definitions = $this->discovery->getPatternDefinitions();

    // Flatten the array.
    $patterns = array_merge([], ...array_values($definitions));

    // Return only the pattern names.
    return array_keys($patterns);
  }

  /**
   * Get an array loaded pattern entities for all dependencies of a pattern.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern to be loaded.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface[]
   *   An associative array of loaded Pattern entities keyed by their namespaced
   *   identifier and ordered from least to most dependencies.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \MJS\TopSort\CircularDependencyException
   * @throws \MJS\TopSort\ElementNotFoundException
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   * @throws \JsonException
   */
  protected function getPatternStack(string $pattern_id): array {
    $dependencies = $this->dependencyResolver->getOrderedDependencies($pattern_id);

    $stack = [];
    foreach ($dependencies as $dependency) {
      $stack[$dependency] = $this->getPattern($dependency, FALSE);
    }

    return $stack;
  }

  /**
   * Get a fully resolved schema including all referenced dependencies.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern to be loaded.
   *
   * @return object|\JsonSerializable
   *   The parsed schema with all dependencies bundled and referenced locally.
   *
   * @throws \Drupal\patternkit\Exception\UnknownPatternException
   * @throws \MJS\TopSort\CircularDependencyException
   * @throws \MJS\TopSort\ElementNotFoundException
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public function getBundledPatternSchema(string $pattern_id): object {
    // Preload the dependent patterns for access from the ref provider.
    $stack = $this->getPatternStack($pattern_id);

    // Get the top level schema without resolving references.
    $schema = json_decode($stack[$pattern_id]->getSchema());

    // Remove the top-level id to prevent issues with reference resolution.
    unset($schema->id, $schema->{'$id'});

    // Track a mapping of pointers for embedded schemas to replace references.
    $reference_map = [];

    // Map circular references back to the root of the schema.
    $reference_map[$pattern_id] = '#';

    // Load dependencies into the schema for local reference.
    $definitions = $schema->definitions ?? (object) [];
    foreach ($stack as $pattern_name => $pattern) {
      // Don't embed the parent schema being assembled.
      if ($pattern_name === $pattern_id) {
        continue;
      }
      $subschema = json_decode($pattern->getSchema());

      // Remove leading '@' and replace slashes for referencing.
      $definition_name = str_replace('/', '_', ltrim($pattern_name, '@'));

      $definitions->$definition_name = $subschema;
      $reference_map[$pattern_name] = "#/definitions/$definition_name";
    }
    // Don't alter the schema if no definitions need to be added.
    if (empty((array) $definitions)) {
      return $schema;
    }

    $schema->definitions = $definitions;

    // Prepare a context using a preprocessor to rewrite the reference links to
    // use local references.
    /** @var \Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor $preprocessor */
    $preprocessor = $this->dataPreProcessorFactory->getPreProcessor('bundler');
    $preprocessor->setBundleMap($reference_map);
    $context = $this->contextBuilder()->getConfiguredContext([
      'dereference' => TRUE,
      'dataPreProcessor' => $preprocessor,
    ]);

    return UnresolvedSchema::import($schema, $context);
  }

  /**
   * Update a pattern entity to include a bundled schema.
   *
   * Bundling will load schemas from all schema dependencies and embed them into
   * the pattern's schema for local reference.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern instance to be bundled.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The provided pattern instance after bundling has been completed.
   */
  protected function bundleSchema(PatternInterface $pattern): PatternInterface {
    $bundled_schema = $this->getBundledPatternSchema($pattern->getAssetId());
    $pattern->setSchema($bundled_schema);
    $pattern->setBundled();

    return $pattern;
  }

  /**
   * Get the 'patternkit_pattern' entity storage handler.
   *
   * Defer loading of the entity storage handler to avoid failure to fetch it
   * during initialization.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage handler for the 'patternkit_pattern' entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function patternStorage(): EntityStorageInterface {
    if (!isset($this->patternStorage)) {
      $this->patternStorage = $this->entityTypeManager->getStorage('patternkit_pattern');
    }

    return $this->patternStorage;
  }

}
