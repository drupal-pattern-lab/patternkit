<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\LibraryInterface;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Drupal\patternkit\PatternLibrary;

/**
 * Tests for the Patternkit Library implementation.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\Library
 * @group patternkit
 * @group legacy
 */
class LibraryTest extends KernelTestBase {

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
   * The library service being tested.
   *
   * @var \Drupal\patternkit\Asset\LibraryInterface|null
   */
  protected ?LibraryInterface $library;

  /**
   * The cache backend for the discovery cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|null
   */
  protected ?CacheBackendInterface $cache;

  /**
   * The editable patternkit settings.
   *
   * @var \Drupal\Core\Config\Config|null
   */
  protected ?Config $patternkitConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('patternkit');

    $this->library = $this->container->get('patternkit.asset.library');
    $this->cache = $this->container->get('cache.discovery');
    $this->patternkitConfig = $this->config(PatternkitSettingsForm::SETTINGS);
  }

  /**
   * @covers ::getLibraries
   *
   * @todo Validate discovered patterns.
   */
  public function testGetLibraries() {
    $libraries = $this->library->getLibraries();

    $this->assertIsArray($libraries);
    $this->assertNotEmpty($libraries);
    $this->assertContainsOnlyInstancesOf(PatternLibrary::class, $libraries);

    $this->assertArrayHasKey('patternkit', $libraries);
    $this->assertArrayHasKey('patternkit_twig', $libraries);
    $this->assertArrayHasKey('patternkit_json', $libraries);

    // Test standardization of the pattern library data.
    $library = $libraries['patternkit'];
    $pattern_lib = reset($library->patterns);
    $this->assertIsArray($pattern_lib);
    $this->assertEquals([
      'plugin' => 'twig',
      'type' => 'directory',
      'data' => $this->getModulePath('patternkit_example') . '/lib/patternkit/src',
      'version' => \Drupal::VERSION,
    ], $pattern_lib);

    // Confirm discovery of the patternkit_twig library.
    $library = $libraries['patternkit_twig'];
    $pattern_lib = reset($library->patterns);
    $this->assertIsArray($pattern_lib);
    $this->assertEquals([
      'plugin' => 'twig',
      'type' => 'directory',
      'data' => $this->getModulePath('patternkit_test') . '/lib/patternkit/src',
      'version' => \Drupal::VERSION,
    ], $pattern_lib);

    // Confirm discovery of the patternkit_json library.
    $library = $libraries['patternkit_json'];
    $pattern_lib = reset($library->patterns);
    $this->assertIsArray($pattern_lib);
    $this->assertEquals([
      'plugin' => 'json',
      'type' => 'directory',
      'data' => $this->getModulePath('patternkit_test') . '/lib/patternkit/src',
      'version' => \Drupal::VERSION,
    ], $pattern_lib);
  }

  /**
   * @covers ::getLibrariesByExtension
   */
  public function testGetLibrariesByExtension() {
    // Test for the library discovered from patternkit_example.
    $libraries = $this->library->getLibrariesByExtension('patternkit_example');

    $this->assertIsArray($libraries);
    $this->assertCount(1, $libraries);
    $this->assertInstanceOf(PatternLibrary::class, $libraries['patternkit']);

    // Test for the library discovered from our test module.
    $libraries = $this->library->getLibrariesByExtension('patternkit_test');

    $this->assertIsArray($libraries);
    $this->assertCount(2, $libraries);
    $this->assertArrayHasKey('patternkit_twig', $libraries);
    $this->assertArrayHasKey('patternkit_json', $libraries);
  }

  /**
   * @covers ::getLibraryByName
   */
  public function testGetLibraryByName() {
    $library = $this->library->getLibraryByName('patternkit', 'invalid_library');
    $this->assertEquals(FALSE, $library);

    $library = $this->library->getLibraryByName('patternkit_example', 'patternkit');
    $this->assertIsArray($library);
    $this->assertNotEmpty($library);
    $this->assertArrayHasKey('patterns', $library);
    $this->assertCount(1, $library['patterns']);
    $this->assertArrayHasKey($this->getModulePath('patternkit_example') . '/lib/patternkit/src', $library['patterns']);

    $library = $this->library->getLibraryByName('patternkit_test', 'patternkit_twig');
    $this->assertIsArray($library);
    $this->assertNotEmpty($library);
    $this->assertArrayHasKey('patterns', $library);
    $this->assertCount(1, $library['patterns']);
    $this->assertArrayHasKey($this->getModulePath('patternkit_test') . '/lib/patternkit/src', $library['patterns']);

    $library = $this->library->getLibraryByName('patternkit_test', 'patternkit_json');
    $this->assertIsArray($library);
    $this->assertNotEmpty($library);
    $this->assertArrayHasKey('patterns', $library);
    $this->assertCount(1, $library['patterns']);
    $this->assertArrayHasKey($this->getModulePath('patternkit_test') . '/lib/patternkit/src', $library['patterns']);
  }

  /**
   * @covers ::getLibraryDefinitions
   */
  public function testGetLibraryDefinitions() {
    $libraries = $this->library->getLibraryDefinitions();

    $this->assertIsArray($libraries);
    $this->assertNotEmpty($libraries);
    $this->assertContainsOnlyInstancesOf(PatternLibrary::class, $libraries);

    // Assert that each library is registered and has patterns defined.
    $expected_libraries = [
      'patternkit',
      'patternkit_twig',
      'patternkit_json',
    ];

    $expected_patterns = [
      'atoms/example/src/example',
      'atoms/example_filtered/src/example_filtered',
      'atoms/example_ref/src/example_ref',
    ];

    foreach ($expected_libraries as $library_name) {
      $this->assertArrayHasKey($library_name, $libraries, "The library '$library_name' was not found.");

      $library = $libraries[$library_name];
      $this->assertNotEmpty($library->getPatternInfo(), 'Pattern info was not persisted into library objects.');
      $this->assertNotEmpty($library->patterns, "No patterns were discovered for the library '$library_name'.");

      foreach ($expected_patterns as $pattern_name) {
        $this->assertArrayHasKey($pattern_name, $library->patterns, "The expected pattern '$pattern_name' was not discovered for the library '$library_name'.");
        $this->assertIsArray($library->patterns[$pattern_name]);
      }
    }
  }

  /**
   * @covers ::getAssets
   */
  public function testGetAssets() {
    $assets = $this->library->getAssets();

    $this->assertIsArray($assets);
    $this->assertNotEmpty($assets);

    $expected_assets = [
      '@patternkit/atoms/example/src/example',
      '@patternkit/atoms/example_filtered/src/example_filtered',
      '@patternkit/atoms/example_ref/src/example_ref',
      '@patternkit_twig/atoms/example/src/example',
      '@patternkit_twig/atoms/example_filtered/src/example_filtered',
      '@patternkit_twig/atoms/example_ref/src/example_ref',
      '@patternkit_json/atoms/example/src/example',
      '@patternkit_json/atoms/example_filtered/src/example_filtered',
      '@patternkit_json/atoms/example_ref/src/example_ref',
    ];

    foreach ($expected_assets as $asset_id) {
      $this->assertArrayHasKey($asset_id, $assets, "The expected pattern \"$asset_id\" was not found.");
      $this->assertIsArray($assets[$asset_id]);
    }
  }

  /**
   * @covers ::getLibraryAsset
   */
  public function testGetLibraryAsset() {
    $asset = $this->library->getLibraryAsset('@patternkit/atoms/example/src/example');

    $this->assertIsArray($asset);
    $this->assertNotEmpty($asset);

    $asset = $this->library->getLibraryAsset('@patternkit/atoms/example/src/invalid_asset');
    $this->assertNull($asset);
  }

  /**
   * @covers ::buildByExtension
   */
  public function testBuildByExtension() {
    $libraries = $this->library->buildByExtension('module', 'patternkit_example');

    $this->assertIsArray($libraries);
    $this->assertNotEmpty($libraries, 'No libraries were discovered from the patternkit_example module.');
    $this->assertContainsOnlyInstancesOf(PatternLibrary::class, $libraries);

    // Confirm the patternkit library was found and validate details.
    $this->assertArrayHasKey('patternkit', $libraries);
    $this->assertEquals([
      'plugin' => 'twig',
      'type' => 'directory',
      'data' => $this->getModulePath('patternkit_example') . '/lib/patternkit/src',
      'version' => \Drupal::VERSION,
    ], reset($libraries['patternkit']->patterns));

    $libraries = $this->library->buildByExtension('module', 'patternkit_test');

    $this->assertIsArray($libraries);
    $this->assertNotEmpty($libraries, 'No libraries were discovered from the patternkit_test module.');
    $this->assertContainsOnlyInstancesOf(PatternLibrary::class, $libraries);
    $this->assertCount(2, $libraries);

    // Confirm the patternkit twig library was found and validate details.
    $this->assertArrayHasKey('patternkit_twig', $libraries);
    $this->assertNotEmpty($libraries['patternkit_twig']->js);
    $this->assertNotEmpty($libraries['patternkit_twig']->css);
    $this->assertEquals([
      'plugin' => 'twig',
      'type' => 'directory',
      'data' => $this->getModulePath('patternkit_test') . '/lib/patternkit/src',
      'version' => \Drupal::VERSION,
    ], reset($libraries['patternkit_twig']->patterns));

    // Confirm the patternkit json library was found and validate details.
    $this->assertArrayHasKey('patternkit_json', $libraries);
    $this->assertNotEmpty($libraries['patternkit_json']->js);
    $this->assertNotEmpty($libraries['patternkit_json']->css);
    $this->assertEquals([
      'plugin' => 'json',
      'type' => 'directory',
      'data' => $this->getModulePath('patternkit_test') . '/lib/patternkit/src',
      'version' => \Drupal::VERSION,
    ], reset($libraries['patternkit_json']->patterns));
  }

  /**
   * Test caching behavior when the discovery cache is enabled.
   *
   * @covers ::getLibraryDefinitions
   */
  public function testCacheEnabled() {
    $this->markTestSkipped('Caching in the Library service is no longer supported.');
  }

  /**
   * Test caching behavior when the discovery cache is enabled.
   *
   * @covers ::getLibraryDefinitions
   */
  public function testCacheDisabled() {
    $this->markTestSkipped('Caching in the Library service is no longer supported.');
  }

}
