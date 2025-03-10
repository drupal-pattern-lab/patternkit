<?php

namespace Drupal\Tests\patternkit\Unit\Asset;

use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\patternkit\Asset\LibraryNamespaceResolver;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests for the LibraryNamespaceResolver class.
 */
#[Group('patternkit')]
#[Group('legacy')]
#[CoversClass(LibraryNamespaceResolver::class)]
class LibraryNamespaceResolverTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The dependency container for fetching extension list services.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Symfony\Component\DependencyInjection\ContainerInterface>
   */
  protected ObjectProphecy $container;

  /**
   * The library discovery service for loading library definitions.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Asset\LibraryDiscoveryInterface>
   */
  protected ObjectProphecy $libraryDiscovery;

  /**
   * The stream wrapper manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\StreamWrapper\StreamWrapperManagerInterface>
   */
  protected ObjectProphecy $streamWrapperManager;

  /**
   * The collection of loaded extensions to search for libraries.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected array $extensionList;

  /**
   * The cache backend.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Cache\CacheBackendInterface>
   */
  protected ObjectProphecy $cache;

  /**
   * The lock service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Lock\LockBackendInterface>
   */
  protected ObjectProphecy $lock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = $this->prophesize(ContainerInterface::class);
    $this->libraryDiscovery = $this->prophesize(LibraryDiscoveryInterface::class);
    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $this->lock = $this->prophesize(LockBackendInterface::class);
    $this->streamWrapperManager = $this->prophesize(StreamWrapperManagerInterface::class);
  }

  /**
   * A helper function to get the service for testing with mocks injected.
   *
   * This is a convenience method to allow for deferral of revealing mocks so
   * individual tests may configure them before they are created.
   *
   * @return \Drupal\patternkit\Asset\LibraryNamespaceResolver
   *   The test service instance with configured mocks injected.
   */
  protected function getLibraryNamespaceResolver(): LibraryNamespaceResolver {
    return new LibraryNamespaceResolver(
      $this->container->reveal(),
      $this->libraryDiscovery->reveal(),
      $this->cache->reveal(),
      $this->lock->reveal(),
      $this->streamWrapperManager->reveal(),
    );
  }

  public function testLibraryDeprecationWarning() {
    $namespaceResolver = $this->getMockBuilder(LibraryNamespaceResolver::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $namespaceResolver->method('get')
      ->with('@my_namespace')
      ->willReturn([
        'extension' => 'my_module',
        'deprecated' => 'The %library_id% library is deprecated.',
      ]);

    // Replace error handler to capture deprecation error and test it.
    // See https://github.com/sebastianbergmann/phpunit/issues/5062#issuecomment-1446620312
    set_error_handler(static function ($errno, $errstr): void {
      restore_error_handler();
      throw new \Exception($errstr, $errno);
    }, E_ALL);
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The my_module/my_namespace library is deprecated.');

    $namespaceResolver->getLibraryFromNamespace('@my_namespace');
  }

  /**
   * Test handling of library definitions without versions defined.
   */
  public function testNormalizeLibraryPatternDataWithoutVersions() {
    $library_data = [
      'my_library' => [
        'patterns' => [
          'my/module/path/patterns' => [
            'plugin' => 'twig',
            // 'data' => 'my/module/path/patterns',
          ],
        ],
      ],
    ];

    $this->libraryDiscovery->getLibrariesByExtension('my_module')
      ->willReturn($library_data);

    $namespaceResolver = $this->getMockBuilder(LibraryNamespaceResolver::class)
      ->setConstructorArgs([
        $this->container->reveal(),
        $this->libraryDiscovery->reveal(),
        $this->cache->reveal(),
        $this->lock->reveal(),
        $this->streamWrapperManager->reveal(),
      ])
      ->onlyMethods(['getExtensionList', 'fileValidUri'])
      ->getMock();

    $extension = $this->prophesize(Extension::class);
    $extension->getType()->willReturn('module');
    $extension->getPath()->willReturn('my/module/path');

    $namespaceResolver->method('getExtensionList')
      ->with()
      ->willReturn(['my_module' => $extension->reveal()]);

    $namespaceResolver->method('fileValidUri')
      ->willReturn(TRUE);

    $libraries = $namespaceResolver->getLibrariesByExtension('my_module');
    $this->assertEquals([
      '@my_library' => [
        'patterns' => [
          'my/module/path/patterns' => [
            'plugin' => 'twig',
            'data' => 'my/module/path/patterns',
            'type' => 'directory',
            'version' => \Drupal::VERSION,
          ],
        ],
        'id' => 'my_library',
        'extension' => 'my_module',
        'extensionType' => 'module',
        'extensionPath' => 'my/module/path',
        'namespace' => '@my_library',
      ],
    ], $libraries);
  }

}
