<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\LibraryNamespaceResolver;

/**
 * Test the LibraryNamespaceResolver service.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\LibraryNamespaceResolver
 * @group patternkit
 */
class LibraryNamespaceResolverTest extends KernelTestBase {

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
   * The namespace resolver service being tested.
   *
   * @var \Drupal\patternkit\Asset\LibraryNamespaceResolver
   */
  protected $resolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->resolver = $this->container->get('patternkit.library.namespace_resolver');
  }

  /**
   * @covers ::getLibraryDefinitions
   *
   * @todo Expand to test handling of externally sourced patterns.
   */
  public function testGetLibraryDefinitions() {
    $definitions = $this->resolver->getLibraryDefinitions();

    $this->assertIsArray($definitions);
    $this->assertNotEmpty($definitions);
    $this->assertArrayHasKey('@patternkit', $definitions, "The 'patternkit' library provided by patternkit_example was not found.");

    // Load the library definition as discovered by core for comparison.
    $core_library_definition = $this->container->get('library.discovery')->getLibraryByName('patternkit_example', 'patternkit');

    $library = $definitions['@patternkit'];
    $this->assertIsArray($library);

    // Validate added metadata for the library definition.
    $this->assertEquals('patternkit', $library['id']);
    $this->assertEquals('patternkit_example', $library['extension']);
    $this->assertEquals('module', $library['extensionType']);
    $this->assertEquals($this->getModulePath('patternkit_example'), $library['extensionPath']);
    $this->assertEquals('@patternkit', $library['namespace']);

    // Validate pattern data normalization.
    $this->assertNotEquals($core_library_definition['patterns'], $library['patterns']);
    foreach ($core_library_definition['patterns'] as $source => $definition) {
      $relative_path = $this->getModulePath('patternkit_example') . '/' . $source;
      $this->assertArrayHasKey($relative_path, $library['patterns'], 'The key for the pattern definition was expected to be converted to a path relative to the Drupal root.');
      $this->assertEquals($relative_path, $library['patterns'][$relative_path]['data']);
      $this->assertEquals('directory', $library['patterns'][$relative_path]['type']);
      // @todo Add testing to confirm the version number is properly assigned.
      // $this->assertEquals('', $library['patterns'][$relative_path]['version']);
    }
  }

  /**
   * Test to confirm persistence of discovered data in the cache.
   *
   * @covers ::getLibraryDefinitions
   * @covers ::destruct
   */
  public function testLibraryCachePersistence() {
    // Ensure the cache is starting empty.
    $cache = $this->container->get('cache.discovery');
    $cache->delete(LibraryNamespaceResolver::PERSISTENT_CACHE_ID);

    // Load all library definitions which should prepare all data for caching.
    $definitions = $this->resolver->getLibraryDefinitions();

    // Trigger end of request clean-up which should persist the cache to test
    // the saved content.
    $this->resolver->destruct();

    $this->assertEquals($definitions, $cache->get(LibraryNamespaceResolver::PERSISTENT_CACHE_ID)->data);
  }

  /**
   * Test to confirm loading of cached library data.
   *
   * @covers ::getLibraryDefinitions
   * @covers ::destruct
   */
  public function testLibraryCacheLoading() {
    // Prepare dummy data to seed the cache with.
    $cache_seed = [
      '@patternkit' => (array) $this->getRandomGenerator()->object(),
    ];

    // Seed the cache data.
    $cache = $this->container->get('cache.discovery');
    $cache->set(LibraryNamespaceResolver::PERSISTENT_CACHE_ID, $cache_seed);

    $definitions = $this->resolver->getLibraryDefinitions();

    // The loaded definitions should be returned without full discovery.
    $this->assertEquals($cache_seed, $definitions, 'Returned library definitions did not match seeded cache data.');
  }

  /**
   * Test to confirm loading of library data with an incomplete cache seeded.
   *
   * This scenario may occur if only libraries from some extensions were loaded
   * and cached during a request, but a later request uses a new library that
   * isn't discovered and cached yet. In this case, the service should perform
   * a discovery to load all libraries from all enabled extensions.
   *
   * @covers ::getLibraryFromNamespace
   * @covers ::resolveCacheMiss
   * @covers ::destruct
   */
  public function testCacheMissLoading() {
    // Seed the cache with incomplete dummy data.
    $cache_seed = [
      '@unknown' => (array) $this->getRandomGenerator()->object(),
    ];
    $cache = $this->container->get('cache.discovery');
    $cache->set(LibraryNamespaceResolver::PERSISTENT_CACHE_ID, $cache_seed);

    // Expect the requested library to be returned successfully.
    $library = $this->resolver->getLibraryFromNamespace('@patternkit');
    $this->assertEquals('patternkit', $library['id']);
    $this->assertEquals('patternkit_example', $library['extension']);

    // Trigger end of request clean-up which should persist the cache to test
    // the saved content.
    $this->resolver->destruct();

    $cache_data = $cache->get(LibraryNamespaceResolver::PERSISTENT_CACHE_ID)->data;
    $this->assertEquals($library, $cache_data['@patternkit'], 'Cached data was expected to contain the requested library definition.');
    $this->assertGreaterThan(1, count($cache_data), 'Cache data was expected to contain additional discovered libraries.');
  }

  /**
   * Test to confirm handling of known and unknown namespace requests.
   *
   * @covers ::getLibraryFromNamespace
   */
  public function testCacheMissResults() {
    // Ensure the cache is starting empty.
    $cache = $this->container->get('cache.discovery');
    $cache->delete(LibraryNamespaceResolver::PERSISTENT_CACHE_ID);

    // Expect a valid library to return successfully.
    $library = $this->resolver->getLibraryFromNamespace('@patternkit');
    $this->assertEquals('patternkit', $library['id']);
    $this->assertEquals('patternkit_example', $library['extension']);

    // Expect an unknown library to return NULL.
    $this->assertNull($this->resolver->getLibraryFromNamespace('@unknown'));
  }

}
