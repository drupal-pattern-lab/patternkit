<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the PatternLibraryPluginManager service.
 *
 * @coversDefaultClass \Drupal\patternkit\PatternLibraryPluginManager
 * @group patternkit
 */
class PatternLibraryPluginManagerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The cache backend service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Cache\CacheBackendInterface>
   */
  protected ObjectProphecy $cacheBackend;

  /**
   * The module handler service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Extension\ModuleHandlerInterface>
   */
  protected ObjectProphecy $moduleHandler;

  /**
   * The theme handler service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Extension\ThemeHandlerInterface>
   */
  protected ObjectProphecy $themeHandler;

  /**
   * The patternkit logger channel.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The plugin manager service being tested.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager
   */
  protected PatternLibraryPluginManager $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->themeHandler = $this->prophesize(ThemeHandlerInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
  }

  /**
   * Get the plugin manager instance for testing with all mocks injected.
   *
   * @return \Drupal\patternkit\PatternLibraryPluginManager
   *   The instantiated plugin manager service for testing.
   */
  protected function getPluginManager(iterable $namespaces = []): PatternLibraryPluginManager {
    $this->pluginManager = new PatternLibraryPluginManager(
      (is_array($namespaces) ? new \ArrayIterator($namespaces) : $namespaces),
      $this->cacheBackend->reveal(),
      $this->moduleHandler->reveal(),
      $this->themeHandler->reveal(),
    );
    $this->pluginManager->setLogger($this->logger->reveal());

    return $this->pluginManager;
  }

  /**
   * Test fallback plugin ID generation in expected scenarios.
   *
   * @param string $expected
   *   The expected result.
   * @param string $pluginId
   *   The plugin ID to generate a fallback for.
   *
   * @covers ::getFallbackPluginId
   *
   * @dataProvider providerGetFallbackPluginId()
   */
  public function testGetFallbackPluginId(string $expected, string $pluginId): void {
    $pluginManager = $this->getPluginManager();
    $this->assertEquals($expected, $pluginManager->getFallbackPluginId($pluginId));
  }

  /**
   * Data provider for testGetFallbackPluginId().
   */
  public static function providerGetFallbackPluginId(): array {
    return [
      ['very.specific', 'very.specific.plugin_id'],
      ['very', 'very.specific'],
    ];
  }

  /**
   * Test getFallbackPluginId() behavior with no successful result available.
   *
   * @covers ::getFallbackPluginId()
   */
  public function testGetFallbackPluginIdFailure(): void {
    $pluginManager = $this->getPluginManager();

    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage("Plugin ID 'my_plugin_id' was not found.");

    $pluginManager->getFallbackPluginId('my_plugin_id');
  }

  /**
   * Test valid calls to createInstance().
   *
   * @covers ::createInstance
   */
  public function testCreateInstance(): void {
    $pluginManager = $this->getPluginManager();

    // PluginManagerBase::createInstance() looks for a factory object and then
    // calls createInstance() on it. So we have to mock a factory object.
    $factory = $this->prophesize(FactoryInterface::class);
    $factory_ref = new \ReflectionProperty($pluginManager, 'factory');
    $factory_ref->setValue($pluginManager, $factory->reveal());

    $plugin = $this->prophesize(PatternLibraryPluginInterface::class);
    $factory->createInstance('valid', ['my' => 'config'])
      ->shouldBeCalledOnce()
      ->willReturn($plugin->reveal());

    $actual = $pluginManager->createInstance('valid', ['my' => 'config']);
    $this->assertSame($plugin->reveal(), $actual);
  }

  /**
   * Test calls to createInstance() requiring loading of fallback plugins.
   *
   * @covers ::createInstance
   * @covers ::handlePluginNotFound
   * @covers ::getFallbackPluginId
   */
  public function testCreateInstanceWithFallback(): void {
    $pluginManager = $this->getPluginManager();

    // PluginManagerBase::createInstance() looks for a factory object and then
    // calls createInstance() on it. So we have to mock a factory object.
    $factory = $this->prophesize(FactoryInterface::class);
    $factory_ref = new \ReflectionProperty($pluginManager, 'factory');
    $factory_ref->setValue($pluginManager, $factory->reveal());

    // Setup to validate and mock sequential calls.
    $expected_plugin_ids = [
      'very.specific.plugin_id',
      'very.specific',
      'very',
    ];
    $plugin = $this->prophesize(PatternLibraryPluginInterface::class);
    $self = $this;
    $factory->createInstance(Argument::type('string'), ['my' => 'config'])
      ->will(function ($args) use (&$expected_plugin_ids, $plugin, $self) {
        [$plugin_id, $configuration] = $args;
        $self->assertEquals(array_shift($expected_plugin_ids), $plugin_id);
        $self->assertEquals(['my' => 'config'], $configuration);

        // Throw exceptions until the least-specific plugin ID is called.
        if (!empty($expected_plugin_ids)) {
          throw new PluginNotFoundException($plugin_id);
        }
        else {
          return $plugin->reveal();
        }
      })
      ->shouldBeCalledTimes(3);

    // Expect a warning for each of the first 2 plugins not being found.
    $this->logger->warning(Argument::type('string'), Argument::type('array'))
      ->shouldBeCalledTimes(2);

    // Call the method for testing.
    $actual = $pluginManager->createInstance('very.specific.plugin_id', ['my' => 'config']);

    // Check factory predictions first for more output clarity in the case of a
    // failure.
    $factory->checkProphecyMethodsPredictions();
    $this->assertSame($plugin->reveal(), $actual);
  }

  /**
   * Test calls to createInstance() with fallbacks and eventual failure.
   *
   * @covers ::createInstance
   * @covers ::handlePluginNotFound
   * @covers ::getFallbackPluginId
   */
  public function testCreateInstanceWithFallbackFailure(): void {
    $pluginManager = $this->getPluginManager();

    // PluginManagerBase::createInstance() looks for a factory object and then
    // calls createInstance() on it. So we have to mock a factory object.
    $factory = $this->prophesize(FactoryInterface::class);
    $factory_ref = new \ReflectionProperty($pluginManager, 'factory');
    $factory_ref->setValue($pluginManager, $factory->reveal());

    // Setup to validate and mock sequential calls.
    $expected_plugin_ids = [
      'very.specific.plugin_id',
      'very.specific',
      'very',
    ];
    $factory->createInstance(Argument::in($expected_plugin_ids), ['my' => 'config'])
      ->willThrow(PluginNotFoundException::class)
      ->shouldBeCalledTimes(3);

    // Expect a warning for each of the first 3 plugins not being found.
    $this->logger->warning(Argument::type('string'), Argument::type('array'))
      ->shouldBeCalledTimes(3);

    // Expect an exception to be thrown when it fails.
    $this->expectException(PluginNotFoundException::class);

    // Call the method for testing.
    $pluginManager->createInstance('very.specific.plugin_id', ['my' => 'config']);
  }

}
