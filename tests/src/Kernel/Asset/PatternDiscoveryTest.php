<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\Tests\patternkit_example\Traits\PatternkitExampleLibraryAwareTrait;

/**
 * Test the PatternDiscovery class.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternDiscovery
 * @group patternkit
 */
class PatternDiscoveryTest extends KernelTestBase {

  use PatternkitExampleLibraryAwareTrait;

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
   * An array containing expected definitions to be discovered.
   *
   * @var array|\string[][]
   */
  protected array $expectedDefinitions = ['@patternkit' => []];

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
    $this->expectedDefinitions['@patternkit'] = $this->getPatternNames();
  }

  /**
   * @covers ::getPatternsByNamespace
   */
  public function testGetPatternsByNamespace() {
    $definitions = $this->discovery->getPatternsByNamespace('@patternkit');

    $this->assertIsArray($definitions);
    $this->assertCount(count($this->expectedDefinitions['@patternkit']), $definitions);
    $this->assertEqualsCanonicalizing($this->expectedDefinitions['@patternkit'], array_keys($definitions));
  }

  /**
   * @covers ::getPatternDefinitions
   */
  public function testGetPatternDefinitions() {
    $definitions = $this->discovery->getPatternDefinitions();

    $this->assertIsArray($definitions);

    // With these enabled modules, there should only be one namespace defined.
    $this->assertEquals(['@patternkit'], array_keys($definitions));

    // Within the @patternkit namespace we should know the definitions found.
    $this->assertEqualsCanonicalizing($this->expectedDefinitions['@patternkit'], array_keys($definitions['@patternkit']));
  }

  /**
   * @covers ::clearCachedDefinitions
   */
  public function testClearCachedDefinitions() {
    $cache = $this->container->get('cache.discovery');
    $collector = $this->container->get('patternkit.pattern.discovery.collector');

    $cacheEntry = $cache->get($collector::PERSISTENT_CACHE_ID);
    $this->assertFalse($cacheEntry, 'Expected the pattern discovery cache to be empty initially.');

    $this->discovery->getPatternDefinitions();

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
