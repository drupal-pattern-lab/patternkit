<?php

namespace Drupal\Tests\patternkit\Unit\Asset\PatternLibraryParser;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\patternkit\Asset\PatternDependencyResolver;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternkitEnvironmentAwareInterface;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use PHPUnit\Framework\MockObject\MockBuilder;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * A base test class for testing PatternLibraryParser implementations.
 *
 * @group patternkit
 *
 * @todo Use a mocked serializer instead of injecting a real instance.
 *
 * @template T of \Drupal\patternkit\Asset\PatternLibraryParserInterface
 */
abstract class PatternLibraryParserTestBase extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * The module handler service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Extension\ModuleHandlerInterface>
   */
  protected ObjectProphecy $moduleHandler;

  /**
   * The theme manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Theme\ThemeManagerInterface>
   */
  protected ObjectProphecy $themeManager;

  /**
   * A serializer service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Component\Serialization\SerializationInterface>
   */
  protected ObjectProphecy $serializer;

  /**
   * The stream wrapper manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\StreamWrapper\StreamWrapperManagerInterface>
   */
  protected ObjectProphecy $streamWrapperManager;

  /**
   * The library directory file finder service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Asset\LibrariesDirectoryFileFinder>
   */
  protected ObjectProphecy $librariesDirectoryFileFinder;

  /**
   * The extension path resolver service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Extension\ExtensionPathResolver>
   */
  protected ObjectProphecy $extensionPathResolver;

  /**
   * The entity type manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityTypeManagerInterface>
   */
  protected ObjectProphecy $entityTypeManager;

  /**
   * The pattern storage handler.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  protected ObjectProphecy $patternStorage;

  /**
   * The schema data preprocessor factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\DataPreProcessorFactory>
   */
  protected ObjectProphecy $dataPreProcessorFactory;

  /**
   * The pattern dependency resolver service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Asset\PatternDependencyResolver>
   */
  protected ObjectProphecy $patternDependencyResolver;

  /**
   * The patternkit logger channel.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The pattern environment service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternkitEnvironment>
   */
  protected ObjectProphecy $environment;

  /**
   * The component plugin manager service if it exists.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Theme\ComponentPluginManager>|null
   */
  protected ?ObjectProphecy $componentPluginManager;

  /**
   * The pattern library parser being tested.
   *
   * @var \Drupal\patternkit\Asset\PatternLibraryParserInterface
   */
  protected PatternLibraryParserInterface $parser;

  /**
   * The prepared service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * The root directory for the virtual filesystem for testing.
   *
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected vfsStreamDirectory $vfsRoot;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->vfsRoot = vfsStream::setup();

    $this->setUpParserDependencies();

    $this->container = new ContainerBuilder();
    $this->container->set('stream_wrapper_manager', $this->streamWrapperManager->reveal());
    \Drupal::setContainer($this->container);
  }

  /**
   * Create parser dependency prophecy mocks.
   */
  protected function setUpParserDependencies(): void {
    $this->serializer = $this->prophesize(SerializationInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->themeManager = $this->prophesize(ThemeManagerInterface::class);
    $this->streamWrapperManager = $this->prophesize(StreamWrapperManagerInterface::class);
    $this->librariesDirectoryFileFinder = $this->prophesize(LibrariesDirectoryFileFinder::class);
    $this->extensionPathResolver = $this->prophesize(ExtensionPathResolver::class);
    $this->patternStorage = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->dataPreProcessorFactory = $this->prophesize(DataPreProcessorFactory::class);
    $this->patternDependencyResolver = $this->prophesize(PatternDependencyResolver::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->environment = $this->prophesize(PatternkitEnvironment::class);
    $this->environment->isDebug()->willReturn(FALSE);
    $this->componentPluginManager = class_exists(ComponentPluginManager::class) ?
      $this->prophesize(ComponentPluginManager::class) : NULL;

    $this->entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());
  }

  /**
   * Get an instantiated parser plugin with all mocked dependencies.
   *
   * @return \Drupal\patternkit\Asset\PatternLibraryParserInterface
   *   An instantiated parser plugin with all mocked dependencies.
   *
   * @phpstan-return T
   */
  protected function getParserInstance(): PatternLibraryParserInterface {
    $testClass = $this->getTestClass();
    $parser = new $testClass(
      // @todo Replace real serializer with a mockable one.
      // $this->serializer->reveal(),
      new Json(),
      $this->vfsRoot->url(),
      $this->moduleHandler->reveal(),
      $this->themeManager->reveal(),
      $this->streamWrapperManager->reveal(),
      $this->librariesDirectoryFileFinder->reveal(),
      $this->extensionPathResolver->reveal(),
      $this->entityTypeManager->reveal(),
      $this->dataPreProcessorFactory->reveal(),
      $this->patternDependencyResolver->reveal(),
      $this->logger->reveal(),
      is_null($this->componentPluginManager) ? NULL : $this->componentPluginManager->reveal(),
    );

    if ($parser instanceof PatternkitEnvironmentAwareInterface) {
      $parser->setPatternkitEnvironment($this->environment->reveal());
    }

    return $parser;
  }

  /**
   * Build a partial mock for the parser being tested.
   *
   * @return \Drupal\patternkit\Asset\PatternLibraryParserInterface&\PHPUnit\Framework\MockObject\MockBuilder
   *   A partially configured mock builder for the test class with prepared
   *   dependencies configured as constructor args.
   *
   * @phpstan-return \PHPUnit\Framework\MockObject\MockBuilder<T>
   */
  protected function getPartialMock(): MockBuilder {
    return $this->getMockBuilder($this->getTestClass())
      ->setConstructorArgs([
        $this->serializer->reveal(),
        $this->vfsRoot->url(),
        $this->moduleHandler->reveal(),
        $this->themeManager->reveal(),
        $this->streamWrapperManager->reveal(),
        $this->librariesDirectoryFileFinder->reveal(),
        $this->extensionPathResolver->reveal(),
        $this->entityTypeManager->reveal(),
        $this->dataPreProcessorFactory->reveal(),
        $this->patternDependencyResolver->reveal(),
        $this->logger->reveal(),
        is_null($this->componentPluginManager) ? NULL : $this->componentPluginManager->reveal(),
      ]);
  }

  /**
   * Get the class name of the parser to be tested.
   *
   * @return string
   *   The class name of the parser to be tested.
   */
  abstract protected function getTestClass(): string;

  /**
   * Prepare a pattern mock for creation using the given path.
   *
   * @param string $path
   *   The path to the pattern as specified in the config array passed to the
   *   create() method.
   */
  protected function createMockPattern(string $path): void {
    $pattern = $this->prophesize(Pattern::class);

    // Mock pattern creation to return the provided configuration array when
    // converted back to an array.
    $this->patternStorage->create(Argument::withEntry('path', $path))
      ->will(function (array $args) use ($pattern) {
        // Return the configuration array originally passed into the create()
        // method for the pattern.
        $pattern->toArray()->willReturn($args[0]);

        // Return any assets included in the creation array or an empty array
        // if none were provided.
        $pattern->fetchAssets()->willReturn($args[0]['assets'] ?? []);

        // Return the given path when requested.
        $pattern->getPath()->willReturn($args[0]['path']);

        return $pattern->reveal();
      });
  }

  /**
   * Assert matching expectations in discovered library metadata.
   *
   * @param array $expected
   *   The expected metadata to be discovered. This should be an associative
   *   array of metadata keyed by pattern path. Each array will then be tested
   *   for matching keys to be tested.
   * @param array $actual
   *   The discovered metadata to be tested.
   * @param string[] $tested_keys
   *   An array of keys to be compared in each pattern's metadata. Any metadata
   *   keys not included in this array will be ignored.
   */
  protected function assertLibraryInfo(array $expected, array $actual, array $tested_keys): void {
    $this->assertIsArray($actual);

    // Confirm all the expected patterns were found.
    $this->assertEqualsCanonicalizing(array_keys($expected), array_keys($actual), 'Expected to find the same patterns in metadata.');

    // Compare for each discovered pattern.
    foreach ($expected as $pattern => $metadata) {
      // Get the subset of data we're testing for.
      $metadata_subset = array_intersect_key($actual[$pattern], array_flip($tested_keys));
      $this->assertEquals($metadata, $metadata_subset, "Didn't find the expected metadata for $pattern.");
    }
  }

}
