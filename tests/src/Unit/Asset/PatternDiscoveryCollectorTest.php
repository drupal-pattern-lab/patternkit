<?php

namespace Drupal\Tests\patternkit\Unit\Asset;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\patternkit\Asset\LibraryNamespaceResolverInterface;
use Drupal\patternkit\Asset\PatternDiscoveryCollector;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Tests for the PatternDiscoveryCollector class.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternDiscoveryCollector
 * @uses \Drupal\patternkit\PatternkitEnvironmentAwareTrait
 * @group patternkit
 */
class PatternDiscoveryCollectorTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * A cache backend mock.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $cache;

  /**
   * A lock backend mock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $lock;

  /**
   * A mock library namespace resolver.
   *
   * @var \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $libraryNamespaceResolver;

  /**
   * A mock pattern library plugin manager.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternLibraryPluginManager;

  /**
   * A mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $logger;

  /**
   * A mock serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $serializer;

  /**
   * A mock patternkit environment.
   *
   * @var \Drupal\patternkit\PatternkitEnvironment|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternkitEnvironment;

  /**
   * A boilerplate library definition for use in tests.
   *
   * @var array
   */
  protected array $libraryDefinition = [
    'id' => 'example',
    'extension' => 'patternkit_example',
    'extensionType' => 'module',
    'extensionPath' => '',
    'namespace' => '@example',
    'version' => 'VERSION',
    'patterns' => [
      'my/pattern/path' => [
        'plugin' => 'twig',
        'data' => 'my/pattern/path',
      ],
    ],
  ];

  /**
   * Mock pattern data for return from plugin discovery.
   *
   * @var array
   *
   * @see \Drupal\Tests\patternkit\Unit\Asset\PatternDiscoveryCollectorTest::$mockPatternDiscovery
   */
  protected array $mockPatternData = [
    'my_pattern' => ['abcd' => 'efg'],
    'my_second_pattern' => ['hijk' => 'lmnop'],
  ];

  /**
   * Mock pattern discovery data for the default plugin data.
   *
   * @var array
   *
   * @see \Drupal\Tests\patternkit\Unit\Asset\PatternDiscoveryCollectorTest::$mockPatternData
   */
  protected array $mockPatternDiscovery = [
    '@example/my_pattern' => ['abcd' => 'efg'],
    '@example/my_second_pattern' => ['hijk' => 'lmnop'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $this->lock = $this->prophesize(LockBackendInterface::class);
    $this->libraryNamespaceResolver = $this->prophesize(LibraryNamespaceResolverInterface::class);
    $this->patternLibraryPluginManager = $this->prophesize(PatternLibraryPluginManager::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->serializer = $this->prophesize(Serializer::class);
    $this->patternkitEnvironment = $this->prophesize(PatternkitEnvironment::class);

    $extension = $this->prophesize(Extension::class);
    $extensionList = $this->prophesize(ExtensionList::class);
    $extensionList->get('patternkit_example')->willReturn($extension->reveal());

    $container = new ContainerBuilder();
    $container->set('extension.list.module', $extensionList->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Get an instance of the collector for testing with mocks injected.
   *
   * @return \Drupal\patternkit\Asset\PatternDiscoveryCollector
   *   The collector instance for testing with configured mocks injected.
   */
  protected function getCollector(): PatternDiscoveryCollector {
    $collector = new PatternDiscoveryCollector(
      $this->cache->reveal(),
      $this->lock->reveal(),
      $this->libraryNamespaceResolver->reveal(),
      $this->patternLibraryPluginManager->reveal(),
      $this->logger->reveal(),
      $this->serializer->reveal(),
    );

    $collector->setPatternkitEnvironment($this->patternkitEnvironment->reveal());

    return $collector;
  }

  /**
   * Configure mocked services with default expectations for a successful load.
   *
   * @param array $overrides
   *   (optional) An associative array of specific overrides for default mocking
   *   to prepare for a test case. Without any overrides provided, default data
   *   on this classes instance variables will be used. To override any of these
   *   behaviors, one or more of the following values may be provided:
   *   - library_definition: An array of library definition data to be returned
   *     from the library namespace resolver.
   *   - plugin_id: The id of the library plugin to be used.
   *   - plugin_data: An associative array of discovered pattern data to be
   *     returned from the plugin.
   */
  protected function setDefaultExpectations(array $overrides = []): void {
    $library_definition = $overrides['library_definition'] ?? $this->libraryDefinition;
    $plugin_id = $overrides['plugin_id'] ?? 'twig';
    $plugin_data = $overrides['plugin_data'] ?? $this->mockPatternData;

    $this->libraryNamespaceResolver
      ->getLibraryFromNamespace('@example')
      ->willReturn($library_definition);

    $plugin = $this->prophesize(PatternLibraryPluginInterface::class);
    $plugin->getPluginId()->willReturn($plugin_id);
    $plugin->getMetadata(Argument::cetera())->willReturn($plugin_data);

    $this->patternLibraryPluginManager
      ->createInstance($plugin_id)
      ->willReturn($plugin->reveal());

    $extension = $this->prophesize(Extension::class);

    $pattern_library = $this->prophesize(PatternLibrary::class);
    $pattern_library->getPatternInfo()->willReturn([]);
    $pattern_library->setPatternInfo(Argument::type('array'))->will(function () use ($pattern_library) {
      return $pattern_library->reveal();
    });
    $pattern_library->getExtension()->willReturn($extension->reveal());

    $this->serializer->denormalize($library_definition, PatternLibrary::class)
      ->willReturn($pattern_library->reveal())
      ->shouldBeCalledOnce();
  }

  /**
   * @covers ::getPatternsByNamespace
   * @covers ::__construct
   * @covers ::getLibraryObject
   * @covers ::lazyLoadCache
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::getLibraryPlugin()
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::resolveCacheMiss()
   * @uses \Drupal\patternkit\PatternkitEnvironmentAwareTrait
   * @dataProvider providerGetPatternsByNamespace
   */
  public function testGetPatternsByNamespace(
    array $library_definition,
    array $plugin_data,
    $expected,
    ?callable $expectations_callback = NULL
  ) {
    // Use the callback to set up case-specific expectations if provided.
    if ($expectations_callback) {
      call_user_func($expectations_callback, $this);
    }

    $this->setDefaultExpectations([
      'library_definition' => $library_definition,
      'plugin_data' => $plugin_data,
    ]);

    $collector = $this->getCollector();
    $actual = $collector->get('@example');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testGetPatternsByNamespace.
   */
  public function providerGetPatternsByNamespace() {
    $cases = [];

    $cases['no patterns found'] = [
      $this->libraryDefinition,
      // No pattern data discovered.
      [],
      // No pattern results returned.
      [],
      function (self $self): void {
        $self->logger->info("No patterns found in %library library for path %path.", [
          '%library' => '@example',
          '%path' => 'my/pattern/path',
        ])
          ->shouldBeCalledOnce();
      },
    ];

    $cases['some patterns discovered'] = [
      $this->libraryDefinition,
      $this->mockPatternData,
      $this->mockPatternDiscovery,
    ];

    $libraryDefinition = $this->libraryDefinition;
    unset($libraryDefinition['patterns']['my/pattern/path']['data']);
    $cases['no path set'] = [
      $libraryDefinition,
      [],
      [],
      function (self $self) use ($libraryDefinition): void {
        $self->logger->info("No path set for %library under the patterns key. Data:<br /><pre>@data</pre>", [
          '%library' => '@example',
          '@data' => print_r($libraryDefinition['patterns']['my/pattern/path'], TRUE),
        ])
          ->shouldBeCalledOnce();
      },
    ];

    return $cases;
  }

  /**
   * @covers ::getPatternsByNamespace
   * @covers ::getLibraryPlugin
   * @covers ::lazyLoadCache
   * @uses \Drupal\patternkit\PatternkitEnvironmentAwareTrait
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::resolveCacheMiss()
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::getLibraryObject()
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::__construct
   */
  public function testPluginExceptionWithFallback() {
    // Override the pattern definition to look for a more specific plugin that
    // is unavailable. It should fall back to the known 'twig' plugin and follow
    // expected behavior from there on.
    $library_definition = $this->libraryDefinition;
    $library_definition['patterns']['my/pattern/path']['plugin'] = 'twig.unknown';

    $this->setDefaultExpectations([
      'library_definition' => $library_definition,
    ]);

    $this->patternLibraryPluginManager->createInstance('twig.unknown')
      ->willThrow(new PluginException('Unknown plugin'))->shouldBeCalledOnce();

    $collector = $this->getCollector();
    $actual = $collector->get('@example');
    $this->assertEquals($this->mockPatternDiscovery, $actual);
  }

  /**
   * @covers ::getPatternsByNamespace
   * @covers ::getLibraryPlugin
   * @covers ::lazyLoadCache
   * @uses \Drupal\patternkit\PatternkitEnvironmentAwareTrait
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::resolveCacheMiss()
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::getLibraryObject()
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::__construct
   */
  public function testPluginExceptionWithoutFallback() {
    // Override the pattern definition to look for a more specific plugin that
    // is unavailable. It should fall back to the known 'twig' plugin and follow
    // expected behavior from there on.
    $library_definition = $this->libraryDefinition;
    $library_definition['patterns']['my/pattern/path']['plugin'] = 'missing_plugin.unknown';

    $this->setDefaultExpectations([
      'library_definition' => $library_definition,
    ]);

    $exception_message = $this->getRandomGenerator()->sentences(5);

    $this->patternLibraryPluginManager->createInstance('missing_plugin.unknown')
      ->willThrow(new PluginException($exception_message))->shouldBeCalledOnce();
    $this->patternLibraryPluginManager->createInstance('missing_plugin')
      ->willThrow(new PluginException($exception_message))->shouldBeCalledOnce();

    $this->expectException(InvalidLibraryFileException::class);
    $this->expectExceptionMessage("Error loading pattern libraries via missing_plugin: ");
    $this->expectExceptionMessage($exception_message, 'Expected the thrown exception message to contain the original exception message.');

    $collector = $this->getCollector();
    $collector->get('@example');
  }

  /**
   * @covers ::getPatternsByNamespace
   * @covers ::getLibraryPlugin
   * @covers ::lazyLoadCache
   * @uses \Drupal\patternkit\PatternkitEnvironmentAwareTrait
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::resolveCacheMiss()
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::getLibraryObject()
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::__construct
   */
  public function testPluginExceptionWithoutFallback2() {
    // Override the pattern definition to look for an unavailable plugin. This
    // should cause an exception to be thrown.
    $library_definition = $this->libraryDefinition;
    $library_definition['patterns']['my/pattern/path']['plugin'] = 'missing_plugin';

    $this->setDefaultExpectations([
      'library_definition' => $library_definition,
    ]);

    $exception_message = $this->getRandomGenerator()->sentences(5);

    $this->patternLibraryPluginManager->createInstance('missing_plugin')
      ->willThrow(new PluginException($exception_message))->shouldBeCalledOnce();

    $this->expectException(InvalidLibraryFileException::class);
    $this->expectExceptionMessage("Error loading pattern libraries via missing_plugin: ");
    $this->expectExceptionMessage($exception_message, 'Expected the thrown exception message to contain the original exception message.');

    $collector = $this->getCollector();
    $collector->get('@example');
  }

  /**
   * @covers ::getLibraryPlugin
   * @covers ::getPatternsByNamespace
   * @covers ::__construct
   * @covers ::getLibraryObject
   * @covers ::lazyLoadCache
   * @uses \Drupal\patternkit\PatternkitEnvironmentAwareTrait
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector::resolveCacheMiss
   */
  public function testGetLibraryPluginNoPlugin() {
    // Mock discovered plugin definitions for confirmation in log message.
    $library_plugin_definitions = [
      'all' => [],
      'library' => [],
      'plugins' => [],
    ];
    $this->patternLibraryPluginManager->getSortedDefinitions()
      ->willReturn($library_plugin_definitions);

    // Unset the plugin definition to trigger the expected warning.
    $library_definition = $this->libraryDefinition;
    unset($library_definition['patterns']['my/pattern/path']['plugin']);

    $this->setDefaultExpectations([
      'library_definition' => $library_definition,
    ]);

    // Expect the warning to be logged since no plugin is defined for the
    // library pattern data.
    $this->logger->notice("No 'plugin' key set for the '%library' pattern library, defaulting to '%default'."
      . " Recommend setting the key to one of the available plugins: %plugins.",
      [
        '%library' => '@example',
        '%default' => 'twig',
        '%plugins' => implode(', ', array_keys($library_plugin_definitions)),
      ])
      ->shouldBeCalledOnce();

    $collector = $this->getCollector();
    $actual = $collector->get('@example');
    $this->assertEquals($this->mockPatternDiscovery, $actual);
  }

  /**
   * Test behavior if no library version is defined.
   *
   * @coversNothing
   */
  public function testWithoutLibraryVersion() {
    // Unset the library version.
    $library_definition = $this->libraryDefinition;
    unset($library_definition['version']);

    $this->setDefaultExpectations([
      'library_definition' => $library_definition,
    ]);

    $collector = $this->getCollector();
    $actual = $collector->get('@example');
    $this->assertEquals($this->mockPatternDiscovery, $actual);
  }

  /**
   * @covers ::resolveCacheMiss
   * @uses \Drupal\patternkit\Asset\PatternDiscoveryCollector
   */
  public function testResolveCacheMiss() {
    $this->setDefaultExpectations();

    // Force a cache miss with no cache results.
    $this->cache->get(PatternDiscoveryCollector::PERSISTENT_CACHE_ID)
      ->willReturn(NULL)->shouldBeCalledOnce();

    $this->cache->get(PatternDiscoveryCollector::PERSISTENT_CACHE_ID, FALSE)
      ->shouldBeCalled();

    // Expect the cache to set the new data with all cache tags on destruction.
    $this->cache->set(
      PatternDiscoveryCollector::PERSISTENT_CACHE_ID,
      [
        '@example' => $this->mockPatternDiscovery,
      ],
      Cache::PERMANENT,
      PatternDiscoveryCollector::CACHE_TAGS,
    )->shouldBeCalledOnce();

    // During the update process, the lock will need to be acquired and
    // released.
    $this->lock->acquire('patternkit.patterns:Drupal\Core\Cache\CacheCollector')
      ->willReturn(TRUE);
    $this->lock->release('patternkit.patterns:Drupal\Core\Cache\CacheCollector')
      ->willReturn();

    $collector = $this->getCollector();
    $actual = $collector->get('@example');
    $this->assertEquals($this->mockPatternDiscovery, $actual);

    // Trigger the cache update.
    $collector->destruct();
  }

  /**
   * Test cache handling with the bypass option enabled.
   *
   * @covers ::lazyLoadCache
   * @covers ::updateCache
   * @covers ::getLibraryObject
   * @covers ::getLibraryPlugin
   * @covers ::getPatternsByNamespace
   * @covers ::resolveCacheMiss
   * @covers ::__construct
   */
  public function testCacheBypass() {
    $this->setDefaultExpectations();

    $this->patternkitEnvironment->getFeatureOption('bypass_pattern_cache', FALSE)
      ->willReturn(TRUE)
      ->shouldBeCalledTimes(2);

    $this->cache->get(Argument::cetera())->shouldNotBeCalled();
    $this->cache->set(Argument::cetera())->shouldNotBeCalled();

    $collector = $this->getCollector();

    // Fetch a value which should trigger a cache load.
    $collector->get('@example');

    // Trigger destruction which should case a cache update.
    $collector->destruct();
  }

  /**
   * Test cache handling with the bypass option disabled.
   *
   * @covers ::lazyLoadCache
   * @covers ::updateCache
   * @covers ::getLibraryObject
   * @covers ::getLibraryPlugin
   * @covers ::getPatternsByNamespace
   * @covers ::resolveCacheMiss
   * @covers ::__construct
   */
  public function testCacheBypassDisabled() {
    $this->setDefaultExpectations();

    $this->patternkitEnvironment->getFeatureOption('bypass_pattern_cache', FALSE)
      ->willReturn(FALSE)
      ->shouldBeCalledTimes(2);

    $this->cache->get(Argument::cetera())->shouldBeCalled();
    $this->cache->set(Argument::cetera())->shouldBeCalled();

    $this->lock->acquire(Argument::cetera())->willReturn(TRUE);
    $this->lock->release(Argument::cetera())->willReturn(TRUE);

    $collector = $this->getCollector();

    // Fetch a value which should trigger a cache load.
    $collector->get('@example');

    // Trigger destruction which should case a cache update.
    $collector->destruct();
  }

}
