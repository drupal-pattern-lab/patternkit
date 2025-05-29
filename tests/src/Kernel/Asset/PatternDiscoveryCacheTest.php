<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\patternkit\Asset\PatternDiscovery;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;
use Drupal\Tests\patternkit_example\Traits\PatternkitExampleLibraryAwareTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests for the PatternDiscovery service with cache options enabled.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternDiscovery
 */
class PatternDiscoveryCacheTest extends PatternkitKernelTestBase {

  use ProphecyTrait;
  use PatternkitExampleLibraryAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',

    // Include the test module for additional libraries.
    'patternkit_test',
  ];

  /**
   * An array containing expected definitions to be discovered.
   *
   * @var array|string[][]
   */
  protected array $expectedDefinitions = ['@patternkit' => []];

  /**
   * The pattern discovery service being tested.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $discovery;

  /**
   * A mocked cache backend service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Cache\CacheBackendInterface>
   */
  protected ObjectProphecy $cacheBackend;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->discovery = $this->container->get('patternkit.pattern.discovery');

    // Initialize the list of expected definitions for comparison.
    $this->expectedDefinitions['@patternkit'] = $this->getPatternNames();
  }

  public function testGetPatternsByNamespace(): void {
    $definitions = $this->discovery->getPatternsByNamespace('@patternkit');

    $this->assertIsArray($definitions);
    $this->assertCount(count($this->expectedDefinitions['@patternkit']), $definitions);
    $this->assertEqualsCanonicalizing($this->expectedDefinitions['@patternkit'], array_keys($definitions));
  }

  public function testGetPatternDefinitions(): void {
    $definitions = $this->discovery->getPatternDefinitions();

    $this->assertIsArray($definitions);

    // With these enabled modules, there should only be these namespaces.
    $this->assertEquals(['@patternkit', '@patternkit_twig', '@patternkit_json'], array_keys($definitions));

    // Within the @patternkit namespace we should know the definitions found.
    $this->assertEqualsCanonicalizing($this->expectedDefinitions['@patternkit'], array_keys($definitions['@patternkit']));
  }

  public function testClearCachedDefinitions(): void {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache */
    $cache = $this->container->get('cache.patternkit');

    $namespaces = [
      '@patternkit',
      '@patternkit_twig',
      '@patternkit_json',
    ];
    $cache_keys = array_map([$this, 'namespaceToCacheKey'], $namespaces);

    // Validate that no cache entries exist first.
    $this->assertSameSize($cache_keys, $namespaces);
    $this->assertEmpty($cache->getMultiple($cache_keys), 'Expected no cache entries to exist yet.');

    // Load the definitions which should populate cache entries.
    $this->discovery->getPatternDefinitions();

    // Duplicate the list of keys for loading since the provided list is altered
    // by reference.
    $load_list = $cache_keys;

    // Validate cache entries exist and were loaded.
    $entries = $cache->getMultiple($load_list);
    $this->assertEmpty($load_list, 'Expected all cache keys to have been loaded.');
    $this->assertSameSize($namespaces, $entries, 'Expected a cache entry for each namespace.');

    // Spot-check the stored data for the @patternkit namespace since we know
    // the expected content of those.
    $patternkit_entry = $entries[$this->namespaceToCacheKey('@patternkit')];
    $this->assertIsObject($patternkit_entry);
    $this->assertIsArray($patternkit_entry->data);
    $this->assertEqualsCanonicalizing($this->expectedDefinitions['@patternkit'], array_keys($patternkit_entry->data));

    // Attempt to clear the caches.
    $this->discovery->clearCachedDefinitions();

    // Validate that no cache entries exist.
    $this->assertSameSize($cache_keys, $namespaces);
    $this->assertEmpty($cache->getMultiple($cache_keys), 'Expected no cache entries to exist.');
  }

  /**
   * Validate lookup of library namespaces is stored in independent bins.
   */
  public function testIndependentCacheBins(): void {
    $this->mockCacheBackend();

    // Expect the cache to be tested, but return a miss.
    $this->cacheBackend->get(PatternDiscovery::CACHE_BIN_PREFIX . 'patternkit')
      // Subsequent requests to a different library should NOT trigger a request
      // for this cache bin again.
      ->shouldBeCalledOnce()
      ->willReturn(FALSE);

    // Expect the built result to get stored in a namespace-specific bin.
    $this->cacheBackend->set(
      PatternDiscovery::CACHE_BIN_PREFIX . 'patternkit',
      Argument::type('array'),
      Cache::PERMANENT,
      PatternDiscovery::CACHE_TAGS,
    )->shouldBeCalledOnce();

    // Look up one namespace first.
    $this->discovery->getPatternsByNamespace('@patternkit');

    // Expect a cache test for the new library, but return a miss.
    $this->cacheBackend->get(PatternDiscovery::CACHE_BIN_PREFIX . 'patternkit_twig')
      ->shouldBeCalledOnce()
      ->willReturn(FALSE);

    // Expect the built result to be stored ina  namespace-specific bin.
    $this->cacheBackend->set(
      PatternDiscovery::CACHE_BIN_PREFIX . 'patternkit_twig',
      Argument::type('array'),
      Cache::PERMANENT,
      PatternDiscovery::CACHE_TAGS,
    )->shouldBeCalledOnce();

    // Lookup the second namespace.
    $this->discovery->getPatternsByNamespace('@patternkit_twig');
  }

  public function testUseCaches(): void {
    $this->mockCacheBackend();

    // Ensure caches are disabled.
    $this->discovery->useCaches();

    // Cache entries should never be requested or set.
    $this->cacheBackend->get(Argument::cetera())->shouldNotBeCalled();
    $this->cacheBackend->set(Argument::cetera())->shouldNotBeCalled();

    $definitions = $this->discovery->getPatternDefinitions();

    $this->assertNotEmpty($definitions);
  }

  /**
   * Load the mocked cache backend into the service for test usage.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Cache\CacheBackendInterface>
   *   The cache backend prophecy set for usage by the discovery service.
   */
  protected function mockCacheBackend(): ObjectProphecy {
    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);
    $this->discovery->setCacheBackend($this->cacheBackend->reveal());

    return $this->cacheBackend;
  }

  /**
   * Translate a library namespace to cache keys.
   *
   * @param string $namespace
   *   The library namespace. This is expected to be prefixed with an '@'.
   *
   * @return string
   *   The cache key for the namespace.
   */
  protected function namespaceToCacheKey(string $namespace): string {
    return PatternDiscovery::CACHE_BIN_PREFIX . substr($namespace, 1);
  }

}
