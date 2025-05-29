<?php

namespace Drupal\Tests\patternkit\Kernel\Element;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\Pattern as EntityPattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\SchemaReferenceException;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternFieldProcessorPluginManager;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;
use Drupal\Tests\patternkit\Traits\MetadataTestTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests for the Pattern Error element class.
 *
 * @group patternkit
 * @covers \Drupal\patternkit\Element\Pattern
 * @covers \Drupal\patternkit\Element\PatternError
 * @covers \Drupal\patternkit\EventSubscriber\RenderFailureErrorDisplaySubscriber
 */
class PatternErrorTest extends PatternkitKernelTestBase implements ServiceModifierInterface {

  use MetadataTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'system',
    'node',
    'user',
    'filter',
  ];

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $patternDiscovery;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * A mocked pattern field processor plugin manager service.
   *
   * This is replaced into the container in ::alter().
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternFieldProcessorPluginManager>
   *
   * @see self::alter()
   */
  protected ObjectProphecy $fieldProcessorPluginManager;

  /**
   * A mocked pattern library plugin manager service.
   *
   * This is replaced into the container in ::alter().
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager
   */
  protected PatternLibraryPluginManager $libraryPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Mock required services here.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->patternDiscovery = $this->container->get('patternkit.pattern.discovery');
    $this->libraryPluginManager = $this->container->get('plugin.manager.library.pattern');
    $this->renderer = $this->container->get('renderer');
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    // Mark the services as synthetic so the container doesn't attempt to
    // build them when requested.
    $definition = $container->getDefinition('plugin.manager.pattern_field_processor');
    $definition->setSynthetic(TRUE);
    $container->set('plugin.manager.pattern_field_processor', $definition);

    // Set the service instance directly to be returned upon request.
    $this->fieldProcessorPluginManager = $this->prophesize(PatternFieldProcessorPluginManager::class);
    $container->set('plugin.manager.pattern_field_processor', $this->fieldProcessorPluginManager->reveal());

    // Swap in an extended version of the plugin manager to allow setting plugin
    // instances to be returned.
    $definition = $container->getDefinition('plugin.manager.library.pattern');
    $definition->setClass(TestPatternLibraryPluginManger::class);
    $container->setDefinition('plugin.manager.library.pattern', $definition);
  }

  /**
   * Test when a pattern element is given an unknown pattern name.
   */
  public function testUnknownPatternStringError(): void {
    $pattern_id = '@patternkit/unknown/pattern';
    $element = [
      '#type' => 'pattern',
      '#pattern' => $pattern_id,
    ];

    $output = $this->renderer->renderRoot($element);

    // Expect the rendered output to produce an error message.
    $error_message = 'Failed to load pattern <em class="placeholder">@patternkit/unknown/pattern</em>.';
    $this->assertEquals($error_message, (string) $output);

    // Expect the pattern error element to be encapsulated in an 'error' key.
    $this->assertArrayHasKey('error', $element);
    $this->assertIsArray($element['error']);
    $error = $element['error'];

    // Expect the element to have been expanded out to the pattern_error element
    // and related metadata.
    $this->assertEquals('pattern_error', $error['#type']);
    $this->assertEquals($pattern_id, $error['#pattern']);
    $this->assertInstanceOf(UnknownPatternException::class, $error['#exception']);
    $this->assertArrayHasKey('message', $error);
    $this->assertEquals($error_message, (string) $error['message']['#markup']);

    // Expect the result to be uncacheable.
    $this->assertEquals($element['#cache']['max-age'], -1);
  }

  /**
   * Test when a pattern element encounters a schema processing error.
   */
  public function testPatternSchemaError(): void {
    $pattern_id = '@patternkit/atoms/example/src/example';
    $example_pattern = $this->loadPattern($pattern_id);
    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
    ];

    // Throw an exception that might be encountered during schema traversal.
    $this->fieldProcessorPluginManager->processSchemaValues(Argument::cetera())
      ->willThrow(SchemaReferenceException::class);

    $output = $this->renderer->renderRoot($element);

    // Expect the rendered output to produce an error message.
    $error_message = 'Failed to render pattern <em class="placeholder">Example</em> (<em class="placeholder">@patternkit/atoms/example/src/example</em>).';
    $this->assertEquals($error_message, (string) $output);

    // Expect the pattern error element to be encapsulated in an 'error' key.
    $this->assertArrayHasKey('error', $element);
    $this->assertIsArray($element['error']);
    $error = $element['error'];

    // Expect the element to have been expanded out to the pattern_error element
    // and related metadata.
    $this->assertEquals('pattern_error', $error['#type']);
    $this->assertEquals($example_pattern, $error['#pattern']);
    $this->assertInstanceOf(SchemaReferenceException::class, $error['#exception']);
    $this->assertArrayHasKey('message', $error);
    $this->assertEquals($error_message, (string) $error['message']['#markup']);

    // Expect the result to be uncacheable.
    $this->assertEquals($element['#cache']['max-age'], -1);
  }

  /**
   * Test when a pattern element encounters a plugin exception.
   */
  public function testPatternPluginError(): void {
    $pattern_id = '@patternkit/atoms/example/src/example';
    $example_pattern = $this->loadPattern($pattern_id);
    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
    ];

    $plugin = $this->prophesize(PatternLibraryPluginInterface::class);
    $plugin->render(Argument::cetera())->willThrow(PluginException::class);

    // Throw an exception that might be encountered during schema traversal.
    // @phpstan-ignore method.notFound
    $this->libraryPluginManager->setPlugin('twig', $plugin->reveal());

    $output = $this->renderer->renderRoot($element);

    // Expect the rendered output to produce an error message.
    $error_message = 'Failed to render pattern <em class="placeholder">Example</em> (<em class="placeholder">@patternkit/atoms/example/src/example</em>).';
    $this->assertEquals($error_message, (string) $output);

    // Expect the pattern error element to be encapsulated in an 'error' key.
    $this->assertArrayHasKey('error', $element);
    $this->assertIsArray($element['error']);
    $error = $element['error'];

    // Expect the element to have been expanded out to the pattern_error element
    // and related metadata.
    $this->assertEquals('pattern_error', $error['#type']);
    $this->assertEquals($example_pattern, $error['#pattern']);
    $this->assertInstanceOf(PluginException::class, $error['#exception']);
    $this->assertArrayHasKey('message', $error);
    $this->assertEquals($error_message, (string) $error['message']['#markup']);

    // Expect the result to be uncacheable.
    $this->assertEquals($element['#cache']['max-age'], -1);
  }

  /**
   * Create a new pattern instance of the specified pattern ID.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern ID to load.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The instantiated pattern entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadPattern(string $pattern_id): PatternInterface {
    $definition = $this->patternDiscovery->getPatternDefinition($pattern_id);
    return EntityPattern::create($definition);
  }

}

/**
 * A test-only service for the pattern library plugin manager.
 *
 * This class is only intended for use in tests.
 *
 * @internal
 */
class TestPatternLibraryPluginManger extends PatternLibraryPluginManager {

  /**
   * A map of plugin instances to return if defined.
   *
   * @var array<string, \Drupal\patternkit\PatternLibraryPluginInterface>
   *
   * @see self::setPlugin()
   */
  protected array $pluginMap = [];

  /**
   * Set a plugin instance to be returned upon request.
   *
   * @param string $pluginId
   *   The plugin id.
   * @param \Drupal\patternkit\PatternLibraryPluginInterface $plugin
   *   The plugin instance to be returned.
   */
  public function setPlugin(string $pluginId, PatternLibraryPluginInterface $plugin): void {
    $this->pluginMap[$pluginId] = $plugin;
  }

  /**
   * Creates a plugin instance based on the provided ID and configuration.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return object
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if (isset($this->pluginMap[$plugin_id])) {
      return $this->pluginMap[$plugin_id];
    }
    else {
      return parent::createInstance($plugin_id, $configuration);
    }
  }

}
