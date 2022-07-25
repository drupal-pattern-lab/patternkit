<?php

namespace Drupal\patternkit\Asset;

use Drupal\Core\Cache\CacheCollectorInterface;

/**
 * The pattern discovery service.
 *
 * This service provides an interface to discover and load pattern definitions
 * per namespace or by fully-namespaced pattern identifier.
 */
class PatternDiscovery implements PatternDiscoveryInterface {

  /**
   * The library discovery cache collector.
   *
   * @var \Drupal\Core\Cache\CacheCollectorInterface
   */
  protected CacheCollectorInterface $collector;

  /**
   * The library namespace resolver service.
   *
   * @var \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface
   */
  protected LibraryNamespaceResolverInterface $resolver;

  /**
   * The final collection of pattern definitions, statically cached.
   *
   * @var array
   */
  protected array $patternDefinitions = [];

  /**
   * Creates a new PatternDiscovery instance.
   *
   * @param \Drupal\patternkit\Asset\PatternDiscoveryCollector $collector
   *   The cache collector for pattern definitions.
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface $resolver
   *   The namespace resolver service.
   */
  public function __construct(PatternDiscoveryCollector $collector, LibraryNamespaceResolverInterface $resolver) {
    $this->collector = $collector;
    $this->resolver = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternsByNamespace(string $namespace): array {
    assert($namespace[0] === '@', 'The pattern namespace is expected to begin with an "@" symbol.');

    if (!isset($this->patternDefinitions[$namespace])) {
      $this->patternDefinitions[$namespace] = $this->collector->get($namespace);
    }

    return $this->patternDefinitions[$namespace];
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternDefinition(string $pattern): ?array {
    $namespace = $this->getNamespaceFromPatternIdentifier($pattern);

    $patterns = $this->getPatternsByNamespace($namespace);
    return $patterns[$pattern] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternDefinitions(): array {
    $libraries = $this->resolver->getLibraryDefinitions();

    foreach ($libraries as $library => $definition) {
      // Skip this library if it doesn't define any patterns.
      if (!isset($definition['patterns'])) {
        continue;
      }

      $this->getPatternsByNamespace($library);
    }

    return $this->patternDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions(): void {
    $this->patternDefinitions = [];
    $this->collector->clear();
  }

  /**
   * Get the namespace from a fully-namespaced pattern identifier.
   *
   * @param string $pattern
   *   The fully-namespaced pattern identifier to parse.
   *
   * @return string
   *   The namespace portion of the pattern identifier.
   */
  protected function getNamespaceFromPatternIdentifier(string $pattern): string {
    return strstr($pattern, '/', TRUE);
  }

}
