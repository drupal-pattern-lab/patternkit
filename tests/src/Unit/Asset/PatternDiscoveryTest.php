<?php

namespace Drupal\Tests\patternkit\Unit\Asset;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\patternkit\Asset\LibraryNamespaceResolverInterface;
use Drupal\patternkit\Asset\PatternDiscovery;
use Drupal\patternkit\Asset\PatternDiscoveryLoader;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockBuilder;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * Tests for the PatternDiscovery class.
 *
 * @group patternkit
 * @uses \Drupal\Core\Cache\Cache
 * @uses \Drupal\patternkit\PatternkitEnvironmentAwareTrait
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternDiscovery
 */
class PatternDiscoveryTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * A cache backend mock.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Cache\CacheBackendInterface>
   */
  protected ObjectProphecy $cache;

  /**
   * A mock for the pattern discovery loader service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Asset\PatternDiscoveryLoader>
   */
  protected ObjectProphecy $loader;

  /**
   * A mock library namespace resolver.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Asset\LibraryNamespaceResolverInterface>
   */
  protected ObjectProphecy $libraryNamespaceResolver;

  /**
   * A mock logger channel service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * A mock patternkit environment.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternkitEnvironment>
   */
  protected ObjectProphecy $patternkitEnvironment;

  /**
   * The pattern discovery service being tested.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscovery
   */
  protected PatternDiscovery $discovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loader = $this->prophesize(PatternDiscoveryLoader::class);
    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $this->libraryNamespaceResolver = $this->prophesize(LibraryNamespaceResolverInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->patternkitEnvironment = $this->prophesize(PatternkitEnvironment::class);

    // Test with debug mode disabled and all environment options using default
    // values.
    $this->patternkitEnvironment->isDebug()->willReturn(FALSE);
    $this->patternkitEnvironment->getFeatureOption(Argument::type('string'), Argument::any())
      ->willReturnArgument(1);

    $this->discovery = new PatternDiscovery(
      $this->loader->reveal(),
      $this->libraryNamespaceResolver->reveal(),
      $this->cache->reveal(),
      $this->patternkitEnvironment->reveal(),
    );
    $this->discovery->setLogger($this->logger->reveal());
  }

  /**
   * Get a preconfigured mock builder instance with injected dependencies.
   *
   * @param string[] $methods
   *   (Optional) An array of test class methods to be mocked. These methods
   *   will be marked for mocking, but expectations for them will still need
   *   to be configured on the returned instance.
   *
   * @return \PHPUnit\Framework\MockObject\MockBuilder<\Drupal\patternkit\Asset\PatternDiscovery>
   *   The mocked object with injected dependencies.
   */
  protected function getPartialMock(array $methods = []): MockBuilder {
    return $this->getMockBuilder(PatternDiscovery::class)
      ->setConstructorArgs([
        $this->loader->reveal(),
        $this->libraryNamespaceResolver->reveal(),
        $this->cache->reveal(),
        $this->patternkitEnvironment->reveal(),
      ])
      ->onlyMethods($methods);
  }

  /**
   * Test namespace loading with a cache miss.
   */
  public function testGetPatternsByNamespaceWithCacheMiss(): void {
    $namespace = '@patternkit';
    $cache_key = PatternDiscovery::CACHE_BIN_PREFIX . substr($namespace, 1);

    // Mock definition data to be returned.
    $expected_data = (array) $this->getRandomGenerator()->object();

    // Expect a cache request.
    $this->cache->get($cache_key)
      // Expect the cache request to be triggered only for the first request;
      // the second request should load from memory.
      ->shouldBeCalledOnce()
      // Return a cache miss the first time.
      ->willReturn(FALSE);

    // Expect the definitions to be loaded from the collector.
    $this->loader->getPatternsByNamespace($namespace)
      // This should only happen once, during the first request.
      ->shouldBeCalledOnce()
      ->willReturn($expected_data);

    // Expect the result data to be cached on the first request.
    $this->cache->set($cache_key, $expected_data, Cache::PERMANENT, PatternDiscovery::CACHE_TAGS)
      ->shouldBeCalledOnce();

    // Trigger the first request that should be uncached.
    $definitions = $this->discovery->getPatternsByNamespace($namespace);

    // Expect the intended result data.
    $this->assertEquals($expected_data, $definitions);

    // Trigger the second request that should be loaded from memory.
    $definitions = $this->discovery->getPatternsByNamespace($namespace);

    // Expect the intended result data.
    $this->assertEquals($expected_data, $definitions);
  }

  /**
   * Test namespace loading with a cache hit.
   */
  public function testGetPatternsByNamespaceWithCacheHit(): void {
    $namespace = '@patternkit';
    $cache_key = PatternDiscovery::CACHE_BIN_PREFIX . substr($namespace, 1);

    // Mock definition data to be returned.
    $expected_data = (array) $this->getRandomGenerator()->object();

    // Expect a cache request.
    $this->cache->get($cache_key)
      // Expect the cache request to be triggered only for the first request;
      // the second request should load from memory.
      ->shouldBeCalledOnce()
      // Return a cache hit the first time.
      ->willReturn((object) ['data' => $expected_data]);

    // Definitions should not be requested from the collector.
    $this->loader->getPatternsByNamespace($namespace)
      ->shouldNotBeCalled();

    // Cached data should not get re-set.
    $this->cache->set($cache_key, Argument::cetera())
      ->shouldNotBeCalled();

    // Trigger the first request that should get a cache hit.
    $definitions = $this->discovery->getPatternsByNamespace($namespace);

    // Expect the intended result data.
    $this->assertEquals($expected_data, $definitions);

    // Trigger the second request that should be loaded from memory.
    $definitions = $this->discovery->getPatternsByNamespace($namespace);

    // Expect the intended result data.
    $this->assertEquals($expected_data, $definitions);
  }

  /**
   * Test cache handling with the bypass option enabled.
   */
  public function testCacheBypass(): void {
    $namespace = '@patternkit';

    // Disable caches.
    $this->discovery->useCaches(FALSE);

    // Mock definition data to be returned from the collector.
    $expected_data = (array) $this->getRandomGenerator()->object();
    $this->loader->getPatternsByNamespace($namespace)
      ->willReturn($expected_data);

    // Caches should never be read or set.
    $this->cache->get(Argument::cetera())->shouldNotBeCalled();
    $this->cache->set(Argument::cetera())->shouldNotBeCalled();

    // Trigger the first request that should be uncached.
    $definitions = $this->discovery->getPatternsByNamespace($namespace);

    // Expect the intended result data.
    $this->assertEquals($expected_data, $definitions);

    // Trigger the second request that should be loaded from memory.
    $definitions = $this->discovery->getPatternsByNamespace($namespace);

    // Expect the intended result data.
    $this->assertEquals($expected_data, $definitions);
  }

  /**
   * Test memory reset when cache use gets disabled.
   */
  public function testCacheBypassReset(): void {
    $namespace = '@patternkit';
    $cache_key = PatternDiscovery::CACHE_BIN_PREFIX . substr($namespace, 1);

    // Initially enable caches to populate memory.
    $this->discovery->useCaches(TRUE);

    // Mock definition data to be returned.
    $expected_data = (array) $this->getRandomGenerator()->object();

    // Expect a cache request.
    $this->cache->get($cache_key)
      // Expect the cache request to be triggered only for the first request;
      // the second request should load from memory, and the third should not
      // query the cache since it has been disabled.
      ->shouldBeCalledOnce()
      // Return a cache hit the first time.
      ->willReturn((object) ['data' => $expected_data]);

    // Definitions should not be requested from the collector yet, but we'll
    // check this throughout the test.
    $this->loader->getPatternsByNamespace($namespace)
      ->willReturn($expected_data);

    // Cached data should not get re-set.
    $this->cache->set($cache_key, Argument::cetera())
      ->shouldNotBeCalled();

    // Trigger the first request that should get a cache hit and store data
    // in memory.
    $definitions = $this->discovery->getPatternsByNamespace($namespace);
    $this->assertEquals($expected_data, $definitions);

    // The collector should not have been called yet since we got a cache hit.
    $this->loader->getPatternsByNamespace($namespace)
      ->shouldNotHaveBeenCalled();

    // Trigger the second request that should be loaded from memory.
    $definitions = $this->discovery->getPatternsByNamespace($namespace);
    $this->assertEquals($expected_data, $definitions);

    // The collector should not have been called since we loaded from memory.
    $this->loader->getPatternsByNamespace($namespace)
      ->shouldNotHaveBeenCalled();

    // Disable caches, and this should also reset the memory store resulting in
    // a call to the collector to rebuild definitions.
    $this->discovery->useCaches(FALSE);

    // Trigger the lookup that will miss memory matches and bypass caches.
    $definitions = $this->discovery->getPatternsByNamespace($namespace);
    $this->assertEquals($expected_data, $definitions);

    // The collector should have been called since memory was cleared and cache
    // usage has been disabled.
    $this->loader->getPatternsByNamespace($namespace)->shouldHaveBeenCalled();
  }

  /**
   * Test behavior when looking up an unknown namespace.
   */
  public function testGetPatternsByNamespaceWithUnknownNamespace(): void {
    $namespace = '@unknown';

    // Bubble up an exception from the collector while discovering patterns.
    $message = 'Unable to load "' . $namespace . '" namespace.';
    $exception = new UnknownPatternException($message);
    $this->loader->getPatternsByNamespace($namespace)->willThrow($exception);

    // Expect an exception to be thrown.
    $this->expectExceptionObject($exception);

    // Request the unknown namespace.
    $this->discovery->getPatternsByNamespace($namespace);
  }

  public function testGetPatternDefinition(): void {
    // Get the partial mock for testing.
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\patternkit\Asset\PatternDiscovery $mock */
    $mock = $this->getPartialMock(['getPatternsByNamespace'])
      ->getMock();
    $mock->setLogger($this->logger->reveal());

    // Prepare mocked pattern definitions to return.
    $namespace = '@patternkit';
    $known_pattern_id = '@patternkit/example/pattern';
    $unknown_pattern_id = '@patternkit/unknown/pattern';
    $definitions = [
      $known_pattern_id => (array) $this->getRandomGenerator()->object(),
    ];

    $mock->method('getPatternsByNamespace')
      ->with($namespace)
      ->willReturn($definitions);

    $this->assertEquals($definitions[$known_pattern_id], $mock->getPatternDefinition($known_pattern_id));
    $this->assertNull($mock->getPatternDefinition($unknown_pattern_id));
  }

  public function testGetPatternDefinitionWithUnknownNamespace(): void {
    $namespace = '@unknown';
    $pattern_id = '@unknown/pattern/id';

    // Bubble up an exception from the collector while discovering patterns.
    $message = 'Unable to load "' . $namespace . '" namespace.';
    $exception = new UnknownPatternException($message);
    $this->loader->getPatternsByNamespace($namespace)->willThrow($exception);

    // Expect an exception to be logged.
    $this->logger->error(
      Argument::containingString('Unable to load pattern definition'),
      Argument::withEntry('%pattern', $pattern_id)
    )->shouldBeCalled();

    // Expect exceptions to be caught and logged, but NULL returned.
    $this->assertNull($this->discovery->getPatternDefinition($pattern_id));
  }

  public function testGetPatternDefinitions(): void {
    $mock_library_definitions = [
      // Mock a library definition with patterns defined.
      '@patternkit' => [
        'patterns' => [],
      ],

      // Mock a library definition without patterns defined.
      '@core' => [],

      // Mock a second library definition with patterns defined.
      '@example' => [
        'patterns' => [],
      ],
    ];

    // Define test data to use and validate for library patterns.
    $patternkit_patterns = (array) $this->getRandomGenerator()->object();
    $example_patterns = (array) $this->getRandomGenerator()->object();

    // Provide the mocked library definitions.
    $this->libraryNamespaceResolver->getLibraryDefinitions()
      ->willReturn($mock_library_definitions);

    // Get the partial mock for testing.
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\patternkit\Asset\PatternDiscovery $mock */
    $mock = $this->getPartialMock(['getPatternsByNamespace'])
      ->getMock();
    $mock->setLogger($this->logger->reveal());

    // Mock the namespace lookups.
    $mock->expects($this->exactly(2))
      ->method('getPatternsByNamespace')
      ->willReturnMap([
        ['@patternkit', $patternkit_patterns],
        ['@example', $example_patterns],
      ]);

    $expected = [
      '@patternkit' => $patternkit_patterns,
      '@example' => $example_patterns,
    ];
    $this->assertEquals($expected, $mock->getPatternDefinitions());
  }

  public function testClearCachedDefinitions(): void {
    // Prepare a cache tags invalidator to monitor.
    $invalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);
    $this->getContainerWithCacheTagsInvalidator($invalidator->reveal());

    // Expect cache tags to be invalidated.
    $invalidator->invalidateTags(PatternDiscovery::CACHE_TAGS)
      ->shouldBeCalledOnce();

    $this->discovery->clearCachedDefinitions();
  }

  public function testGetCacheTags(): void {
    $this->assertEquals(PatternDiscovery::CACHE_TAGS, $this->discovery->getCacheTags());
  }

  public function testRebuild(): void {
    // Get the partial mock for testing.
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\patternkit\Asset\PatternDiscovery $mock */
    $mock = $this->getPartialMock([
      'clearCachedDefinitions',
      'getPatternDefinitions',
    ])->getMock();
    $mock->setLogger($this->logger->reveal());

    // Expect caches to be cleared and all definitions to be rebuilt.
    $mock->expects($this->once())->method('clearCachedDefinitions');
    $mock->expects($this->once())->method('getPatternDefinitions');

    $mock->rebuild();
  }

}
