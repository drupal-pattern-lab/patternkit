<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\LibraryNamespaceResolver;

/**
 * Test the LibraryNamespaceResolver service.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Asset\LibraryNamespaceResolver
 * @uses \Drupal\patternkit\EventSubscriber\RenderFailureErrorDisplaySubscriber
 *
 * @todo Expand to test handling of externally sourced patterns.
 */
class LibraryNamespaceResolverTest extends KernelTestBase implements ServiceModifierInterface {

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
   * @var \Drupal\Tests\patternkit\Kernel\Asset\TestLibraryNamespaceResolver
   */
  protected TestLibraryNamespaceResolver $resolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->resolver = $this->container->get('patternkit.library.namespace_resolver');
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override the service definition to use our extended test class.
    $definition = $container->getDefinition('patternkit.library.namespace_resolver');
    $definition->setClass(TestLibraryNamespaceResolver::class);
    $container->setDefinition('patternkit.library.namespace_resolver', $definition);
  }

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
      //phpcs:ignore Drupal.Files.LineLength.TooLong
      // $this->assertEquals('', $library['patterns'][$relative_path]['version']);
    }
  }

  /**
   * Test to confirm persistence of discovered data in the cache.
   */
  public function testLibraryCachePersistence() {
    // Ensure the cache is starting empty.
    $cache = $this->container->get('cache.patternkit');
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
   */
  public function testLibraryCacheLoading() {
    // Prepare dummy data to seed the cache with.
    $cache_seed = [
      '@patternkit' => (array) $this->getRandomGenerator()->object(),
    ];

    // Seed the cache data.
    $cache = $this->container->get('cache.patternkit');
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
   */
  public function testCacheMissLoading() {
    // Seed the cache with incomplete dummy data.
    $cache_seed = [
      '@unknown' => (array) $this->getRandomGenerator()->object(),
    ];
    $cache = $this->container->get('cache.patternkit');
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
   */
  public function testCacheMissResults() {
    // Ensure the cache is starting empty.
    $cache = $this->container->get('cache.patternkit');
    $cache->delete(LibraryNamespaceResolver::PERSISTENT_CACHE_ID);

    // Expect a valid library to return successfully.
    $library = $this->resolver->getLibraryFromNamespace('@patternkit');
    $this->assertEquals('patternkit', $library['id']);
    $this->assertEquals('patternkit_example', $library['extension']);

    // Expect an unknown library to return NULL.
    $this->assertNull($this->resolver->getLibraryFromNamespace('@unknown'));
  }

  /**
   * Test the discovery process to ensure repeated invalidation isn't triggered.
   */
  public function testCacheInvalidationsDuringBuild(): void {
    // Mock a functional cache backend to monitor for invalidation calls.
    $cache = $this->getMockBuilder(MemoryBackend::class)
      ->setConstructorArgs([$this->container->get(TimeInterface::class)])
      ->onlyMethods([
        'invalidate',
      ])
      ->getMock();
    $this->resolver->setCache($cache);

    // Expect the invalidation to be called, at most, one time during a single
    // cache build.
    $cache->expects($this->atMost(1))
      ->method('invalidate')
      ->with(LibraryNamespaceResolver::PERSISTENT_CACHE_ID);

    // Trigger discovery to monitor cache behavior.
    $this->resolver->getLibraryDefinitions();
  }

}

/**
 * An overridden service to extend test access and manipulation.
 *
 * @internal
 */
class TestLibraryNamespaceResolver extends LibraryNamespaceResolver {

  /**
   * Set the cache backend to be used by the service.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service to be used.
   */
  public function setCache(CacheBackendInterface $cache): void {
    $this->cache = $cache;
  }

}
