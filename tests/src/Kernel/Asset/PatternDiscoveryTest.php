<?php

namespace Drupal\patternkit\Asset;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the PatternDiscovery class.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternDiscovery
 * @group patternkit
 */
class PatternDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'patternkit_example',
    'system',
    'text',
  ];

  /**
   * The pattern discovery service being tested.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface|null
   */
  protected ?PatternDiscoveryInterface $discovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->discovery = $this->container->get('patternkit.pattern.discovery');
  }

  /**
   * @covers ::getPatternsByNamespace
   */
  public function testGetPatternsByNamespace() {
    $definitions = $this->discovery->getPatternsByNamespace('@patternkit');

    $this->assertIsArray($definitions);
    $this->assertCount(3, $definitions);
    $this->assertArrayHasKey('@patternkit/atoms/example/src/example', $definitions);
    $this->assertArrayHasKey('@patternkit/atoms/example_filtered/src/example_filtered', $definitions);
    $this->assertArrayHasKey('@patternkit/atoms/example_ref/src/example_ref', $definitions);
  }

  /**
   * @covers ::getPatternDefinitions
   */
  public function testGetPatternDefinitions() {
    $definitions = $this->discovery->getPatternDefinitions();

    $this->assertIsArray($definitions);

    // With these enabled modules, there should only be one namespace defined.
    $this->assertCount(1, $definitions);
    $this->assertArrayHasKey('@patternkit', $definitions);

    // Within the @patternkit namespace there should be 3 definitions.
    $this->assertArrayHasKey('@patternkit/atoms/example/src/example', $definitions['@patternkit']);
    $this->assertArrayHasKey('@patternkit/atoms/example_filtered/src/example_filtered', $definitions['@patternkit']);
    $this->assertArrayHasKey('@patternkit/atoms/example_ref/src/example_ref', $definitions['@patternkit']);
  }

  /**
   * @covers ::clearCachedDefinitions
   */
  public function testClearCachedDefinitions() {
    $cache = $this->container->get('cache.discovery');
    $collector = $this->container->get('patternkit.pattern.discovery.collector');

    $cacheEntry = $cache->get($collector::PERSISTENT_CACHE_ID);
    $this->assertFalse($cacheEntry, 'Expected the pattern discovery cache to be empty initially.');

    $definitions = $this->discovery->getPatternDefinitions();

    // Trigger end of request destruction to persist data for testing.
    $collector->destruct();

    $cacheEntry = $cache->get($collector::PERSISTENT_CACHE_ID);
    $this->assertIsArray($cacheEntry->data);
    $this->assertNotEmpty($cacheEntry->data);
    $this->assertArrayHasKey('@patternkit', $cacheEntry->data, 'Expected cache entry to contain "@patternkit" patterns.');
    $this->assertNotEmpty($cacheEntry->data['@patternkit'], 'Expected patterns to be discovered and cached in the "@patternkit" namespace.');

    $this->discovery->clearCachedDefinitions();
    $cacheEntry = $cache->get($collector::PERSISTENT_CACHE_ID);
    $this->assertFalse($cacheEntry, 'Expected pattern cache entry to be empty after clearing definitions.');
  }

}
