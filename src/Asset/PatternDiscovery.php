<?php

namespace Drupal\patternkit\Asset;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Utility\Error;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternkitEnvironmentAwareInterface;
use Drupal\patternkit\PatternkitEnvironmentAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * The pattern discovery service.
 *
 * This service provides an interface to discover and load pattern definitions
 * per namespace or by fully-namespaced pattern identifier.
 */
class PatternDiscovery implements PatternDiscoveryInterface, PatternkitEnvironmentAwareInterface, LoggerAwareInterface {

  use LoggerAwareTrait;
  use PatternkitEnvironmentAwareTrait;
  use UseCacheBackendTrait;

  /**
   * The prefix for all pattern definition cache bin entries.
   */
  const CACHE_BIN_PREFIX = 'patternkit_pattern_definitions:';

  /**
   * Cache tags to apply to cache entries.
   *
   * @var string[]
   */
  const CACHE_TAGS = [
    // Clear cache entries when all patternkit data is invalidated.
    'patternkit',

    // Clear this pattern data specifically.
    'patternkit_patterns',

    // Clear when the underlying library data is invalidated.
    'patternkit_libraries',
    'library_info',
  ];

  /**
   * The final collection of pattern definitions, statically cached.
   *
   * @var array<string, array<string, mixed>>
   */
  protected array $patternDefinitions = [];

  /**
   * Creates a new PatternDiscovery instance.
   *
   * @param \Drupal\patternkit\Asset\PatternDiscoveryLoader $loader
   *   The cache collector for pattern definitions.
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface $resolver
   *   The namespace resolver service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\patternkit\PatternkitEnvironment $patternkitEnvironment
   *   The patternkit environment service to be used.
   */
  public function __construct(
    protected PatternDiscoveryLoader $loader,
    protected LibraryNamespaceResolverInterface $resolver,
    protected $cacheBackend,
    protected PatternkitEnvironment $patternkitEnvironment,
  ) {
    // Disable caches if we're in debug mode or have this cache specifically
    // disabled.
    $disable_caches = $this->isDebug() ||
      $this->getEnvironmentFeature(PatternkitEnvironment::FEATURE_BYPASS_PATTERN_CACHE, FALSE);
    if ($disable_caches) {
      $this->useCaches = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternsByNamespace(string $namespace): array {
    assert(!empty($namespace) && $namespace[0] === '@', 'The pattern namespace is expected to begin with an "@" symbol.');

    if (!isset($this->patternDefinitions[$namespace])) {
      // Not prepared, try to load from cache.
      $cid = static::CACHE_BIN_PREFIX . substr($namespace, 1);
      if ($cache = $this->cacheGet($cid)) {
        $this->patternDefinitions[$namespace] = $cache->data;
      }
      else {
        // Rebuild the definitions and put it into the cache.
        $this->patternDefinitions[$namespace] = $this->buildPatternDefinitions($namespace);
        $this->cacheSet($cid, $this->patternDefinitions[$namespace], Cache::PERMANENT, $this->getCacheTags());
      }
    }

    return $this->patternDefinitions[$namespace];
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternDefinition(string $pattern): ?array {
    $namespace = $this->getNamespaceFromPatternIdentifier($pattern);

    try {
      $patterns = $this->getPatternsByNamespace($namespace);
      return $patterns[$pattern] ?? NULL;
    }
    catch (UnknownPatternException $exception) {
      $context['%pattern'] = $pattern;
      $context += Error::decodeException($exception);
      $this->logger->error('Unable to load pattern definition %pattern due to error @message %function (line %line of %file). <pre>@backtrace_string</pre>', $context);

      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternDefinitions(): array {
    $libraries = $this->resolver->getLibraryDefinitions();

    $patterns = [];
    foreach ($libraries as $library => $definition) {
      // Skip this library if it doesn't define any patterns.
      if (!isset($definition['patterns'])) {
        continue;
      }

      $patterns[$library] = $this->getPatternsByNamespace($library);
    }

    return $patterns;
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend): void {
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches(bool $use_caches = FALSE): void {
    $this->useCaches = $use_caches;
    if (!$use_caches) {
      $this->patternDefinitions = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions(): void {
    if ($cache_tags = $this->getCacheTags()) {
      // Use the cache tags to clear the cache.
      Cache::invalidateTags($cache_tags);
    }
    else {
      $namespaces = array_keys($this->patternDefinitions);

      $keys = array_map(fn(string $namespace) => static::CACHE_BIN_PREFIX . substr($namespace, 1), $namespaces);
      $this->cacheBackend->deleteMultiple($keys);
    }

    $this->patternDefinitions = [];
  }

  /**
   * Get cache tags to be used for all cache entries.
   *
   * @return string[]
   *   An array of all cache tags to use for all cache entries.
   */
  public function getCacheTags(): array {
    return static::CACHE_TAGS;
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

  /**
   * Assemble pattern definitions for the specified namespace.
   *
   * @param string $namespace
   *   A pattern namespace prefixed with an '@'. For example, "@patternkit".
   *
   * @return array<string, array<string, \Drupal\patternkit\Entity\Pattern>>
   *   An associative array of pattern definitions provided by the given
   *   namespace keyed by the pattern's asset ID.
   *
   * @throws \Drupal\patternkit\Exception\UnknownPatternException
   *   Throws an exception if the namespace is not found.
   *
   * @todo Replace dependency on collector service.
   */
  protected function buildPatternDefinitions(string $namespace): array {
    return $this->loader->getPatternsByNamespace($namespace);
  }

  /**
   * Rebuild the cache of pattern definitions.
   */
  public function rebuild(): void {
    $this->clearCachedDefinitions();
    $this->getPatternDefinitions();
  }

}
