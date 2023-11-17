<?php

namespace Drupal\patternkit\Asset;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Discovers information for pattern assets defined by libraries.
 *
 * Pattern information is statically cached. Patterns are keyed by library
 * namespace.
 *
 * @see \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface
 *   Methods from this interface have been duplicated here since the underlying
 *   plugin definition discovery doesn't necessarily overlap the same way.
 */
interface PatternDiscoveryInterface {

  /**
   * Get all discovered pattern definitions.
   *
   * @return array<string, array<string, array<string, mixed>>>
   *   An associative array of all discovered pattern definitions keyed by
   *   namespace and then fully-namespaced pattern identifier.
   */
  public function getPatternDefinitions(): array;

  /**
   * Get pattern definitions for the given namespace.
   *
   * @param string $namespace
   *   The library namespace to load patterns from. Example: '@patternkit'.
   *
   * @return array<string, array<string, mixed>>
   *   An associative array of pattern definitions provided by the given
   *   namespace. Each definition is keyed by the pattern asset identifier.
   *
   * @throws \Drupal\patternkit\Exception\UnknownPatternException
   *   Throws an exception if a namespace cannot be found.
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
   * Clears static and persistent plugin definition caches.
   *
   * Don't resort to calling \Drupal::cache()->delete() and friends to make
   * Drupal detect new or updated plugin definitions. Always use this method on
   * the appropriate plugin type's plugin manager!
   */
  public function clearCachedDefinitions(): void;

  /**
   * Disable the use of caches.
   *
   * Can be used to ensure that uncached pattern definitions are returned,
   * without invalidating all cached information.
   *
   * This will also remove all local/static caches.
   *
   * @param bool $use_caches
   *   FALSE to not use any caches.
   */
  public function useCaches(bool $use_caches = FALSE): void;

  /**
   * Initialize the cache backend.
   *
   * Plugin definitions are cached using the provided cache backend.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend): void;

}
