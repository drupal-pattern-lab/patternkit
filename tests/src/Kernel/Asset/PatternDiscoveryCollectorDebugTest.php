<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\patternkit\Asset\PatternDiscoveryCollector;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests for the PatternDiscoveryCollector class with debug options enabled.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternDiscoveryCollector
 * @group patternkit
 */
class PatternDiscoveryCollectorDebugTest extends PatternkitKernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'patternkit_debug_test',
  ];

  /**
   * The discovery cache for testing cache entries.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $patternDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cache = $this->container->get('cache.discovery');

    // Ensure the pattern cache is empty to start with.
    $this->cache->delete(PatternDiscoveryCollector::PERSISTENT_CACHE_ID);

    $this->patternDiscovery = $this->container->get('patternkit.pattern.discovery');
  }

  /**
   * @covers ::destruct
   * @covers ::lazyLoadCache
   * @covers ::updateCache
   */
  public function testCacheSaveBypass() {
    // Confirm the cache starts out empty.
    $this->assertFalse($this->cache
      ->get(PatternDiscoveryCollector::PERSISTENT_CACHE_ID));

    // Load data that would get cached.
    $result = $this->patternDiscovery->getPatternsByNamespace('@patternkit');
    $this->assertIsArray($result);
    $this->assertNotEmpty($result);

    // Trigger the destruction process that should store data in the cache.
    $this->container->get('patternkit.pattern.discovery.collector')->destruct();

    // Confirm the cache ends empty.
    $this->assertFalse($this->cache
      ->get(PatternDiscoveryCollector::PERSISTENT_CACHE_ID));
  }

  /**
   * @covers ::lazyLoadCache
   * @covers ::resolveCacheMiss
   */
  public function testCacheLoadBypass() {
    // Seed the cache with dummy data to confirm what is returned.
    $dummy_data = [
      '@patternkit' => [
        '@my/made/up/pattern' => (array) $this->getRandomGenerator()->object(),
      ],
    ];
    $this->cache->set(PatternDiscoveryCollector::PERSISTENT_CACHE_ID, $dummy_data);

    // Test loaded content to confirm it doesn't match the cached data.
    $actual = $this->patternDiscovery->getPatternsByNamespace('@patternkit');
    $this->assertNotEquals($dummy_data['@patternkit'], $actual);
    $this->assertArrayNotHasKey('@my/made/up/pattern', $actual);
    $this->assertArrayHasKey('@patternkit/atoms/example/src/example', $actual);
  }

}
