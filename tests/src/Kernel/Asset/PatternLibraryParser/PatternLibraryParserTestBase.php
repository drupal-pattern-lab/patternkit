<?php

namespace Drupal\Tests\patternkit\Kernel\Asset\PatternLibraryParser;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Asset\PatternLibraryParserBase;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;

/**
 * Test Pattern Library Parser plugins.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternLibraryParserBase
 * @group patternkit
 */
class PatternLibraryParserTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'patternkit_example',
    'patternkit_test',
    'system',
    'text',
  ];

  /**
   * Test instance of the pattern library parser plugin.
   *
   * @var \Drupal\patternkit\Asset\PatternLibraryParserInterface|null
   */
  protected ?PatternLibraryParserInterface $plugin;

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
  }

  /**
   * Test basic component discovery.
   *
   * @covers ::discoverComponents
   */
  public function testDiscoverComponents() {
    $module_path = $this->getModulePath('patternkit_example');
    $component_path = $module_path . '/lib/patternkit/src';
    $components = PatternLibraryParserBase::discoverComponents($component_path, ['json']);
    $expected_components = [
      'example' =>
        [
          'json' => $module_path . '/lib/patternkit/src/atoms/example/src/example.json',
        ],
      'example_filtered' =>
        [
          'json' => $module_path . '/lib/patternkit/src/atoms/example_filtered/src/example_filtered.json',
        ],
      'example_ref' =>
        [
          'json' => $module_path . '/lib/patternkit/src/atoms/example_ref/src/example_ref.json',
        ],
    ];

    $this->assertIsArray($components);
    $this->assertEquals($expected_components, $components);
  }

  /**
   * @covers ::createPattern
   */
  public function testCreatePattern() {
    $this->markTestIncomplete('Test not yet implemented.');
  }

  /**
   * Test asset fetching.
   *
   * @covers ::fetchAssets
   */
  public function testFetchAssets() {
    $this->markTestIncomplete('Test not yet implemented.');
  }

  /**
   * @covers ::fetchPatternAssets
   */
  public function testFetchPatternAssets() {
    $this->markTestIncomplete('Test not yet implemented.');
  }

  /**
   * @covers ::fetchSingleAsset
   */
  public function testFetchSingleAsset() {
    $this->markTestIncomplete('Test not yet implemented.');
  }

  /**
   * @covers ::fetchFragmentAssets
   */
  public function testFetchFragmentAssets() {
    $this->markTestIncomplete('Test not yet implemented.');
  }

  /**
   * @covers ::parsePatternLibraryInfo
   */
  public function testParsePatternLibraryInfo() {
    $this->markTestIncomplete('Test not yet implemented.');
  }

}
