<?php

namespace Drupal\Tests\patternkit\Kernel\Asset\PatternLibraryParser;

use Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternLibrary;

/**
 * Test description.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser
 * @group patternkit
 */
class TwigPatternLibraryParserTest extends PatternLibraryParserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = $this->container->get('patternkit.asset.library.parser.twig');
  }

  /**
   * @covers ::createPattern
   */
  public function testCreatePattern() {
    $pattern_name = '@patternkit/atoms/example/src/example';
    $values = [
      'text' => $this->getRandomGenerator()->string(),
      'formatted_text' => $this->getRandomGenerator()->paragraphs(1),
      'image_url' => $this->getRandomGenerator()->string(),
      'hidden' => $this->getRandomGenerator()->string(),
    ];

    $pattern = $this->plugin->createPattern($pattern_name, $values);

    $this->assertInstanceOf(PatternInterface::class, $pattern);
    $this->assertEquals($values['text'], $pattern->text);
    $this->assertEquals($values['formatted_text'], $pattern->formatted_text);
    $this->assertEquals($values['image_url'], $pattern->image_url);
    $this->assertEquals($values['hidden'], $pattern->hidden);
  }

  /**
   * @covers ::fetchPatternAssets
   */
  public function testFetchPatternAssets() {
    // Test fetching for simple, flat pattern.
    $pattern_name = '@patternkit/atoms/example/src/example';
    $pattern_definition = $this->patternDiscovery->getPatternDefinition($pattern_name);
    $pattern = $this->plugin->createPattern($pattern_name, $pattern_definition);

    $assets = $this->plugin->fetchPatternAssets($pattern);

    $pattern_path = $this->getDrupalRoot() . '/' . $this->getModulePath('patternkit_example') . '/lib/patternkit/src/atoms/example/src/';

    $this->assertIsArray($assets);
    $this->assertArrayHasKey('schema', $assets);
    $this->assertJsonStringEqualsJsonFile($pattern_path . 'example.json', $assets['schema']);
    $this->assertArrayHasKey('template', $assets);
    $this->assertStringEqualsFile($pattern_path . 'example.twig', $assets['template']);

    // Test fetching for a pattern with references.
    $pattern_name = '@patternkit/atoms/example_ref/src/example_ref';
    $pattern_definition = $this->patternDiscovery->getPatternDefinition($pattern_name);
    $pattern = $this->plugin->createPattern($pattern_name, $pattern_definition);

    $assets = $this->plugin->fetchPatternAssets($pattern);

    $pattern_path = $this->getDrupalRoot() . '/' . $this->getModulePath('patternkit_example') . '/lib/patternkit/src/atoms/example_ref/src/';

    $this->assertIsArray($assets);
    $this->assertArrayHasKey('schema', $assets);
    $this->assertJsonStringNotEqualsJsonFile($pattern_path . 'example_ref.json', $assets['schema'], 'The example_ref schema file was not expected to match raw file content with references contained.');
    $this->assertStringContainsString('\/api\/patternkit\/patternkit\/atoms\/example\/src\/example?asset=schema', $assets['schema'], 'The schema was expected to contain a rewritten pattern URL reference.');
    $this->assertArrayHasKey('template', $assets);
    $this->assertStringEqualsFile($pattern_path . 'example_ref.twig', $assets['template']);
  }

  /**
   * Test basic component discovery.
   *
   * @covers ::discoverComponents
   */
  public function testDiscoverComponents() {
    $module_path = $this->getModulePath('patternkit_example');
    $component_path = $module_path . '/lib/patternkit/src';
    $components = TwigPatternLibraryParser::discoverComponents($component_path, [
      'json',
      'twig',
    ]);
    $expected_components = [
      'example' =>
        [
          'json' => $module_path . '/lib/patternkit/src/atoms/example/src/example.json',
          'twig' => $module_path . '/lib/patternkit/src/atoms/example/src/example.twig',
        ],
      'example_filtered' =>
        [
          'json' => $module_path . '/lib/patternkit/src/atoms/example_filtered/src/example_filtered.json',
          'twig' => $module_path . '/lib/patternkit/src/atoms/example_filtered/src/example_filtered.twig',
        ],
      'example_ref' =>
        [
          'json' => $module_path . '/lib/patternkit/src/atoms/example_ref/src/example_ref.json',
          'twig' => $module_path . '/lib/patternkit/src/atoms/example_ref/src/example_ref.twig',
        ],
    ];

    $this->assertIsArray($components);
    $this->assertEquals($expected_components, $components);
  }

  /**
   * @covers ::parsePatternLibraryInfo
   */
  public function testParsePatternLibraryInfo() {
    $module_path = $this->getModulePath('patternkit_test');
    $lib_path = $module_path . '/lib/patternkit/src';
    $pattern_library = new PatternLibrary(
      'patternkit_twig',
      'module',
      'patternkit_test',
    );
    $pattern_library->setPatternInfo([
      $lib_path => [
        'plugin' => 'twig',
        'type' => 'directory',
        'data' => $lib_path,
      ],
    ]);

    $lib_data = $this->plugin->parsePatternLibraryInfo($pattern_library, $lib_path);

    $this->assertIsArray($lib_data);
    $this->assertArrayHasKey('atoms/example/src/example', $lib_data);
    $this->assertArrayHasKey('atoms/example_filtered/src/example_filtered', $lib_data);
    $this->assertArrayHasKey('atoms/example_ref/src/example_ref', $lib_data);
  }

}
