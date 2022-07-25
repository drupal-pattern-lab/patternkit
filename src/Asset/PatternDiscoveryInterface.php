<?php

namespace Drupal\patternkit\Asset;

/**
 * Discovers information for pattern assets defined by libraries.
 *
 * Pattern information is statically cached. Patterns are keyed by library
 * namespace.
 */
interface PatternDiscoveryInterface {

  /**
   * Get all discovered pattern definitions.
   *
   * @return array
   *   An array of all discovered pattern definitions keyed by namespace and
   *   then fully-namespaced pattern identifier.
   */
  public function getPatternDefinitions(): array;

  /**
   * Get pattern definitions for the given namespace.
   *
   * @param string $namespace
   *   The library namespace to load patterns from. Example: '@patternkit'.
   *
   * @return array
   *   An array of pattern definitions provided by the given namespace.
   */
  public function getPatternsByNamespace(string $namespace): array;

  /**
   * Get a specific pattern definition.
   *
   * @param string $pattern
   *   A fully namespaced pattern identifier.
   *   Example: '@patternkit/atoms/example/src/example'.
   *
   * @return array|null
   *   The loaded pattern definition or NULL if the pattern isn't found.
   */
  public function getPatternDefinition(string $pattern): ?array;

  /**
   * Clears static and persistent pattern definition caches.
   */
  public function clearCachedDefinitions(): void;

}
