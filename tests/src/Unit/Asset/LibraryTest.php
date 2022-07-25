<?php

namespace Drupal\Tests\patternkit\Unit\Asset;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\patternkit\Asset\LibraryInterface;
use Drupal\patternkit\Asset\LibraryNamespaceResolverInterface;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test the PatternKit asset library implementation.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\Library
 * @group patternkit
 * @group legacy
 */
class LibraryTest extends UnitTestCase {

  /**
   * The test version of the Library service.
   *
   * @var \Drupal\Tests\patternkit\Unit\Asset\TestLibrary
   */
  protected LibraryInterface $library;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * The mocked config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The mocked pattern library plugin manager service.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager
   */
  protected PatternLibraryPluginManager $libraryPluginManager;

  /**
   * The mocked lock backend service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected LockBackendInterface $lock;

  /**
   * The mocked module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The mocked theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * The mocked active theme service.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected ActiveTheme $activeTheme;

  /**
   * The mocked streamwrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * The Drupal dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * The library namespace resolver service.
   *
   * @var \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface
   */
  protected LibraryNamespaceResolverInterface $libraryNamespaceResolver;

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $patternDiscovery;

  /**
   * Mock library definition as would be returned from LibraryNamespaceResolver.
   *
   * @var array
   */
  protected array $libraryDefinition = [
    'id' => 'patternkit',
    'extension' => 'patternkit',
    'extensionType' => 'module',
    'extensionPath' => 'patternkit',
    'namespace' => '@patternkit',
    'patterns' => [
      'patternkit/lib/patternkit/src' => [
        'data' => 'patternkit/lib/patternkit/src',
        'plugin' => 'twig',
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the active theme for fetching theme overrides.
    $this->activeTheme = $this->createMock(ActiveTheme::class);
    $this->activeTheme->method('getLibrariesOverride')
      ->willReturn([]);

    // Mock injection dependencies.
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->configFactory = $this->getConfigFactoryStub([
      PatternkitSettingsForm::SETTINGS => [
        'patternkit_cache_enabled' => FALSE,
      ],
    ]);
    $this->libraryPluginManager = $this->createMock(PatternLibraryPluginManager::class);
    $this->lock = $this->createMock(LockBackendInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->themeManager = $this->createMock(ThemeManagerInterface::class);
    $this->themeManager->method('getActiveTheme')
      ->willReturn($this->activeTheme);
    $this->patternDiscovery = $this->createMock(PatternDiscoveryInterface::class);
    $this->libraryNamespaceResolver = $this->createMock(LibraryNamespaceResolverInterface::class);

    $this->library = new TestLibrary(
      $this->cache,
      $this->configFactory,
      $this->lock,
      $this->patternDiscovery,
      $this->libraryNamespaceResolver
    );

    // Prepare and mock services called from the container.
    $this->streamWrapperManager = new StreamWrapperManager();

    $this->container = new ContainerBuilder();
    $this->container->set('stream_wrapper_manager', $this->streamWrapperManager);
    \Drupal::setContainer($this->container);
  }

  /**
   * @covers ::buildByExtension
   */
  public function testBuildByExtension() {
    $this->libraryNamespaceResolver->expects($this->once())
      ->method('getLibrariesByExtension')
      ->with('patternkit')
      ->willReturn([
        '@patternkit' => $this->libraryDefinition,
      ]);

    $actual = $this->library->buildByExtension('module', 'patternkit');
    $this->assertIsArray($actual);
    $this->assertArrayHasKey('patternkit', $actual);
    $this->assertInstanceOf(PatternLibrary::class, $actual['patternkit']);

    $pattern_info = reset($actual['patternkit']->patterns);
    $this->assertEquals('twig', $pattern_info['plugin']);
    $this->assertEquals('patternkit/lib/patternkit/src', $pattern_info['data']);
  }

  /**
   * @covers ::getLibraries
   *
   * @todo Expand mocking to simulate multiple extensions and libraries.
   */
  public function testGetLibraries() {
    $this->libraryNamespaceResolver->expects($this->once())
      ->method('getLibraryDefinitions')
      ->with()
      ->willReturn([
        '@patternkit' => $this->libraryDefinition,
      ]);

    $actual = $this->library->getLibraries();
    $this->assertIsArray($actual);
    $this->assertNotEmpty($actual);
    $this->assertArrayHasKey('patternkit', $actual);
    $this->assertInstanceOf(PatternLibrary::class, $actual['patternkit']);
  }

}

