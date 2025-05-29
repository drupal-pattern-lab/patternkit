<?php

namespace Drupal\Tests\patternkit\Unit\Asset;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionList;
use Drupal\patternkit\Asset\LibraryNamespaceResolverInterface;
use Drupal\patternkit\Asset\PatternDiscoveryLoader;
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
 * Tests for the PatternDiscoveryLoader class.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternDiscoveryLoader
 */
class PatternDiscoveryLoaderTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * A mock library namespace resolver.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Asset\LibraryNamespaceResolverInterface>
   */
  protected ObjectProphecy $libraryNamespaceResolver;

  /**
   * A mock pattern library plugin manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternLibraryPluginManager>
   */
  protected ObjectProphecy $patternLibraryPluginManager;

  /**
   * A mock logger.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * A mock serializer.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Symfony\Component\Serializer\Serializer>
   */
  protected ObjectProphecy $serializer;

  /**
   * The loader service being tested.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryLoader
   */
  protected PatternDiscoveryLoader $loader;

  /**
   * A boilerplate library definition for use in tests.
   *
   * @var array<string, string|mixed>
   */
  protected static array $libraryDefinition = [
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
   * @var array<string, array<string, string>>
   *
   * @see \Drupal\Tests\patternkit\Unit\Asset\PatternDiscoveryLoaderTest::$mockPatternDiscovery
   */
  protected static array $mockPatternData = [
    // cspell:ignore abcd, efg, hijk, lmnop
    'my_pattern' => ['abcd' => 'efg'],
    'my_second_pattern' => ['hijk' => 'lmnop'],
  ];

  /**
   * Mock pattern discovery data for the default plugin data.
   *
   * @var array<string, array<string, string>>
   *
   * @see \Drupal\Tests\patternkit\Unit\Asset\PatternDiscoveryLoaderTest::$mockPatternData
   */
  protected static array $mockPatternDiscovery = [
    '@example/my_pattern' => ['abcd' => 'efg'],
    '@example/my_second_pattern' => ['hijk' => 'lmnop'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->libraryNamespaceResolver = $this->prophesize(LibraryNamespaceResolverInterface::class);
    $this->patternLibraryPluginManager = $this->prophesize(PatternLibraryPluginManager::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->serializer = $this->prophesize(Serializer::class);

    $this->loader = new PatternDiscoveryLoader(
      $this->libraryNamespaceResolver->reveal(),
      $this->patternLibraryPluginManager->reveal(),
      $this->serializer->reveal(),
    );
    $this->loader->setLogger($this->logger->reveal());

    $extension = $this->prophesize(Extension::class);
    $extensionList = $this->prophesize(ExtensionList::class);
    $extensionList->get('patternkit_example')->willReturn($extension->reveal());

    $container = new ContainerBuilder();
    $container->set('extension.list.module', $extensionList->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Configure mocked services with default expectations for a successful load.
   *
   * @param array{library_definition?: array<string, string|mixed>, plugin_id?: string, plugin_data?: array<string, array<string, string>>} $overrides
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
    $library_definition = $overrides['library_definition'] ?? static::$libraryDefinition;
    $plugin_id = $overrides['plugin_id'] ?? 'twig';
    $plugin_data = $overrides['plugin_data'] ?? static::$mockPatternData;

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
   * Test getting patterns by namespace.
   *
   * @dataProvider providerGetPatternsByNamespace
   */
  public function testGetPatternsByNamespace(
    array $library_definition,
    array $plugin_data,
    $expected,
    ?callable $expectations_callback = NULL,
  ): void {
    // Use the callback to set up case-specific expectations if provided.
    if ($expectations_callback) {
      call_user_func($expectations_callback, $this);
    }

    $this->setDefaultExpectations([
      'library_definition' => $library_definition,
      'plugin_data' => $plugin_data,
    ]);

    $actual = $this->loader->getPatternsByNamespace('@example');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testGetPatternsByNamespace.
   */
  public static function providerGetPatternsByNamespace(): array {
    $cases = [];

    $cases['no patterns found'] = [
      static::$libraryDefinition,
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
      static::$libraryDefinition,
      static::$mockPatternData,
      static::$mockPatternDiscovery,
    ];

    $libraryDefinition = static::$libraryDefinition;
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

  public function testPluginException(): void {
    // Override the pattern definition to look for an unavailable plugin. This
    // should cause an exception to be thrown.
    $library_definition = static::$libraryDefinition;
    $library_definition['patterns']['my/pattern/path']['plugin'] = 'missing_plugin';

    $this->setDefaultExpectations([
      'library_definition' => $library_definition,
    ]);

    $exception_message = $this->getRandomGenerator()->sentences(5);

    $this->patternLibraryPluginManager->createInstance('missing_plugin')
      ->willThrow(new PluginException($exception_message))->shouldBeCalledOnce();

    $this->expectException(InvalidLibraryFileException::class);
    $this->expectExceptionMessage("Error loading pattern libraries via missing_plugin: ");
    $this->expectExceptionMessage($exception_message);

    $this->loader->getPatternsByNamespace('@example');
  }

  public function testGetLibraryPluginNoPlugin(): void {
    // Mock discovered plugin definitions for confirmation in log message.
    $library_plugin_definitions = [
      'all' => [],
      'library' => [],
      'plugins' => [],
    ];
    $this->patternLibraryPluginManager->getDefinitions()
      ->willReturn($library_plugin_definitions);

    // Unset the plugin definition to trigger the expected warning.
    $library_definition = static::$libraryDefinition;
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

    $actual = $this->loader->getPatternsByNamespace('@example');
    $this->assertEquals(static::$mockPatternDiscovery, $actual);
  }

  /**
   * Test behavior if no library version is defined.
   *
   * @coversNothing
   */
  public function testWithoutLibraryVersion(): void {
    // Unset the library version.
    $library_definition = static::$libraryDefinition;
    unset($library_definition['version']);

    $this->setDefaultExpectations([
      'library_definition' => $library_definition,
    ]);

    $actual = $this->loader->getPatternsByNamespace('@example');
    $this->assertEquals(static::$mockPatternDiscovery, $actual);
  }

}
