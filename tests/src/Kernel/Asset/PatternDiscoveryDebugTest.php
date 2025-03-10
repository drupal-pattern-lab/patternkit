<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\patternkit\Asset\PatternDiscovery;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests for the PatternDiscovery service with debug options enabled.
 */
#[Group('patternkit')]
#[CoversClass(PatternDiscovery::class)]
class PatternDiscoveryDebugTest extends PatternkitKernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'patternkit_debug_test',
  ];

  /**
   * The cache bin ID for the discovered patternkit patterns.
   *
   * @var string
   */
  protected string $cacheBin = PatternDiscovery::CACHE_BIN_PREFIX . 'patternkit';

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

    $this->patternDiscovery = $this->container->get('patternkit.pattern.discovery');
    $this->cache = $this->container->get('cache.discovery');

    // Ensure the pattern cache is empty to start with.
    $this->cache->delete($this->cacheBin);
  }

  /**
   * Ensure cache entries are not saved with debug mode enabled.
   */
  public function testCacheSaveBypass() {
    // Confirm the cache starts out empty.
    $this->assertFalse($this->cache->get($this->cacheBin));

    // Load data that would get cached.
    $result = $this->patternDiscovery->getPatternsByNamespace('@patternkit');
    $this->assertIsArray($result);
    $this->assertNotEmpty($result);

    // Confirm the cache ends empty.
    $this->assertFalse($this->cache->get($this->cacheBin), 'Expected to find no cache entry.');
  }

  public function testCacheLoadBypass() {
    // Seed the cache with dummy data to confirm what is returned.
    $dummy_data = [
      '@patternkit' => [
        '@my/made/up/pattern' => (array) $this->getRandomGenerator()->object(),
      ],
    ];
    $this->cache->set($this->cacheBin, $dummy_data);

    // Test loaded content to confirm it doesn't match the cached data.
    $actual = $this->patternDiscovery->getPatternsByNamespace('@patternkit');
    $this->assertIsArray($actual);

    // Expect known patterns to be discovered.
    $this->assertArrayHasKey('@patternkit/atoms/example/src/example', $actual, 'Expected to find discovered patterns from the filesystem.');

    // Expect the seeded cache data to not be loaded.
    $this->assertArrayNotHasKey('@my/made/up/pattern', $actual, 'Expected cache data to not be loaded.');
  }

}
