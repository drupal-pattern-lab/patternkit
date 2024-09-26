<?php

namespace Drupal\Tests\patternkit\Kernel\Asset\PatternLibraryParser;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\patternkit_example\Traits\PatternkitExampleLibraryAwareTrait;
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

  use PatternkitExampleLibraryAwareTrait;

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
    $filter = ['json'];
    $components = PatternLibraryParserBase::discoverComponents($component_path, $filter);
    $expected_components = $this->getExpectedFiles($component_path, $filter);

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
   * @covers ::fetchPatternAssets
   */
  public function testFetchPatternAssets() {
    $this->markTestIncomplete('Test not yet implemented.');
  }

  /**
   * @covers ::parsePatternLibraryInfo
   */
  public function testParsePatternLibraryInfo() {
    $this->markTestIncomplete('Test not yet implemented.');
  }

}
