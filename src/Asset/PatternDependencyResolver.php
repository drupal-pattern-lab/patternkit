<?php

namespace Drupal\patternkit\Asset;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\patternkit\Schema\ContextBuilderTrait;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\patternkit\Schema\UnresolvedSchema;
use MJS\TopSort\Implementations\FixedArraySort;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * A service to parse and identify interdependencies between pattern schemas.
 */
class PatternDependencyResolver implements LoggerAwareInterface {

  use ContextBuilderTrait;
  use LoggerAwareTrait;

  /**
   * The entity storage handler for the 'patternkit_pattern' entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * An associative array of pattern dependencies keyed by pattern ID.
   *
   * @var string[][]
   */
  protected array $patternDependencies = [];

  /**
   * Create a new PatternDependencyResolver instances.
   *
   * @param \Drupal\patternkit\Asset\PatternDiscoveryInterface $patternDiscovery
   *   The pattern discovery service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\patternkit\Schema\DataPreProcessorFactory $dataPreProcessorFactory
   *   The data preprocessor factory service.
   */
  public function __construct(
    protected PatternDiscoveryInterface $patternDiscovery,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected DataPreProcessorFactory $dataPreProcessorFactory,
  ) {}

  /**
   * Identify dependencies for all discovered patterns.
   *
   * @return string[][]
   *   An associative array of all pattern dependencies keyed by the namespaced
   *   pattern ID. The entries for each namespaced pattern ID should consist of
   *   a simple string array of pattern dependency names.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllPatternDependencies(): array {
    if (!empty($this->patternDependencies)) {
      return $this->patternDependencies;
    }

    $patternStorage = $this->patternStorage();

    $patternsByNamespace = $this->patternDiscovery->getPatternDefinitions();
    foreach ($patternsByNamespace as $patterns) {
      foreach ($patterns as $pattern_id => $definition) {
        /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
        $pattern = $patternStorage->create($definition);

        $this->patternDependencies[$pattern_id] = $pattern->getDependencies();
      }
    }

    return $this->patternDependencies;
  }

  /**
   * Identify external references contained in a given schema.
   *
   * @param string $schema_json
   *   The JSON content for a schema to be scanned.
   * @param string $pattern_id
   *   The identifier for the pattern being processed.
   *
   * @return string[]
   *   An array of all external references discovered within the schema content.
   *
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   * @throws \JsonException
   */
  public function getSchemaDependencies(string $schema_json, string $pattern_id): array {
    // Prepare an observer to record references as the schema is imported.
    /** @var \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver $observer */
    $observer = $this->dataPreProcessorFactory->getPreProcessor('ref_observer');

    // Load the observer into context for use with the schema.
    $context = $this->contextBuilder()->getConfiguredContext([
      'dereference' => FALSE,
      'dataPreProcessor' => $observer,
    ]);

    // Import the schema, but don't resolve references.
    UnresolvedSchema::import(json_decode($schema_json, FALSE, 512, JSON_THROW_ON_ERROR), $context);

    return array_keys($observer->getExternalReferences([$pattern_id]));
  }

  /**
   * Sort all patterns in order of least to most dependencies.
   *
   * @param array|null $patterns
   *   (optional) An associative array of pattern dependency names keyed by
   *   pattern ID.
   *
   * @return string[]
   *   An array of pattern names in order for loading based on dependencies.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \JsonException
   * @throws \MJS\TopSort\CircularDependencyException
   * @throws \MJS\TopSort\ElementNotFoundException
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public function getPatternLoadOrder(?array $patterns = NULL): array {
    if (is_null($patterns)) {
      $patterns = $this->getAllPatternDependencies();
    }
    else {
      assert(Inspector::assertAllArrays($patterns),
        'Patterns list is expected to be an associative array of pattern dependency strings keyed by pattern name.');
    }

    $sorter = new FixedArraySort();

    foreach ($patterns as $pattern => $dependencies) {
      assert(Inspector::assertAllStrings($dependencies), 'Listed dependencies should only contain dependency names');

      // Remove circular dependencies.
      if (in_array($pattern, $dependencies)) {
        $dependencies = array_diff($dependencies, [$pattern]);
      }

      $sorter->add($pattern, $dependencies);
    }

    return $sorter->sort();
  }

  /**
   * Recursively identify all dependencies for a pattern.
   *
   * Identify all dependencies of a pattern and return them in the order they
   * should be loaded.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern to be resolved.
   *
   * @return array
   *   An array of ordered dependencies sequenced in the best order for loading
   *   all dependencies first.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \JsonException
   * @throws \MJS\TopSort\CircularDependencyException
   * @throws \MJS\TopSort\ElementNotFoundException
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public function getOrderedDependencies(string $pattern_id): array {
    if (empty($this->patternDependencies)) {
      $this->getAllPatternDependencies();
    }

    if (!isset($this->patternDependencies[$pattern_id])) {
      throw new \RuntimeException('Unknown pattern: ' . $pattern_id);
    }

    $dependencies = $this->resolvePatternDependencies($pattern_id);

    // Include the requested pattern in the load order.
    $dependencies[] = $pattern_id;

    // Get immediate dependencies for all identified pattern dependencies.
    $dependency_map = array_intersect_key($this->patternDependencies, array_flip($dependencies));

    return $this->getPatternLoadOrder($dependency_map);
  }

  /**
   * Recursively identify all dependencies of the given pattern.
   *
   * @param string $pattern_id
   *   A namespaced pattern identifier to resolve dependencies for.
   * @param string[] $known_dependencies
   *   (optional) An array of known dependencies to exclude from results.
   *
   * @return string[]
   *   An array of all dependencies recursively resolved from those of the
   *   specified pattern. If provided, any dependencies specified in the
   *   $known_dependencies parameter will be excluded from results. Note the
   *   order of the returned dependencies is not sorted.
   */
  public function resolvePatternDependencies(string $pattern_id, array $known_dependencies = []): array {
    $pattern_dependencies = $this->patternDependencies[$pattern_id];
    $new_dependencies = array_diff($pattern_dependencies, $known_dependencies);

    $sub_dependencies = [];
    foreach ($new_dependencies as $dependency) {
      $sub_dependencies[] = $this->resolvePatternDependencies($dependency, array_merge([$pattern_id], $new_dependencies, $known_dependencies));
    }

    $all_sub_dependencies = call_user_func_array('array_merge', $sub_dependencies);

    $all_dependencies = array_merge($pattern_dependencies, $all_sub_dependencies);

    // Filter results to minimize recursion.
    return array_diff(
      $all_dependencies,
      // Don't return already known dependencies.
      $known_dependencies,
      // Don't return circular dependencies.
      [$pattern_id],
    );
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
