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
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\patternkit\Asset\PatternDependencyResolver;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base test class for testing PatternLibraryParser implementations.
 *
 * @group patternkit
 *
 * @todo Use a mocked serializer instead of injecting a real instance.
 */
abstract class PatternLibraryParserTestBase extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $moduleHandler;

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $themeManager;

  /**
   * A serializer service.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $serializer;

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $streamWrapperManager;

  /**
   * The library directory file finder service.
   *
   * @var \Drupal\Core\Asset\LibrariesDirectoryFileFinder|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $librariesDirectoryFileFinder;

  /**
   * The extension path resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $extensionPathResolver;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $entityTypeManager;

  /**
   * The pattern storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternStorage;

  /**
   * The schema data preprocessor factory service.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessorFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $dataPreProcessorFactory;

  /**
   * The pattern dependency resolver service.
   *
   * @var \Drupal\patternkit\Asset\PatternDependencyResolver|ObjectProphecy
   */
  protected ObjectProphecy $patternDependencyResolver;

  /**
   * The patternkit logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $logger;

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
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
  }

  /**
   * Get an instantiated parser plugin with all mocked dependencies.
   *
   * @return \Drupal\patternkit\Asset\PatternLibraryParserInterface
   *   An instantiated parser plugin with all mocked dependencies.
   */
  protected function getParserInstance(): PatternLibraryParserInterface {
    $this->entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    $testClass = $this->getTestClass();
    return new $testClass(
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
    );
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
      $this->assertEqualsCanonicalizing($metadata, $metadata_subset, "Didn't find the expected metadata for $pattern.");
    }
  }

}
