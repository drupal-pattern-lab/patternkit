<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\patternkit\Exception\SchemaValidationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\patternkit\Element\Pattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternFieldProcessorPluginManager;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\Tests\Core\Render\RendererTestBase;

/**
 * Test functionality for the Pattern render element.
 *
 * @coversDefaultClass \Drupal\patternkit\Element\Pattern
 * @group patternkit
 */
class PatternElementTest extends RendererTestBase {

  /**
   * The mocked pattern library plugin manager.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager|null
   */
  protected ?PatternLibraryPluginManager $patternLibraryPluginManager;

  /**
   * The mocked pattern field processor plugin manager.
   *
   * @var \Drupal\patternkit\PatternFieldProcessorPluginManager|null
   */
  protected ?PatternFieldProcessorPluginManager $patternFieldProcessorPluginManager;

  /**
   * A mocked pattern library plugin.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginInterface|null
   */
  protected ?PatternLibraryPluginInterface $libraryPlugin;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|null
   */
  protected ?TranslationInterface $translation;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|null
   */
  protected ?ContainerInterface $container;

  /**
   * The instantiated pattern element for testing.
   *
   * @var \Drupal\patternkit\Element\Pattern|null
   */
  protected ?Pattern $patternElement;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock a library plugin to use for rendering.
    $this->libraryPlugin = $this->createMock(PatternLibraryPluginInterface::class);

    // Mock pattern library plugin manager to return our mocked plugin instance.
    $this->patternLibraryPluginManager = $this->createMock(PatternLibraryPluginManager::class);
    $this->patternLibraryPluginManager->method('createInstance')
      ->willReturn($this->libraryPlugin);

    $this->patternFieldProcessorPluginManager = $this->createMock(PatternFieldProcessorPluginManager::class);

    // Mock the string translation service.
    $this->translation = $this->getStringTranslationStub();

    // Register mocked services into the container.
    $this->container = \Drupal::getContainer();
    $this->container->set('plugin.manager.library.pattern', $this->patternLibraryPluginManager);
    $this->container->set('plugin.manager.pattern_field_processor', $this->patternFieldProcessorPluginManager);
    $this->container->set('string_translation', $this->translation);

    $this->patternElement = Pattern::create($this->container, [], 'pattern', []);
  }

  /**
   * @covers ::getInfo
   */
  public function testGetInfo() {
    $patternElement = Pattern::create($this->container, [], 'test', 'test');
    $info = $patternElement->getInfo();
    $this->assertArrayHasKey('#pre_render', $info);
    $this->assertArrayHasKey('#pattern', $info);
    $this->assertArrayHasKey('#config', $info);
    $this->assertArrayHasKey('#context', $info);
  }

  /**
   * @covers ::preRenderPatternElement
   */
  public function testPreRenderPatternElement() {
    $patternMock = $this->createMock(PatternInterface::class);
    $element = [
      '#type' => 'pattern',
      '#pattern' => $patternMock,
      '#config' => [],
      '#context' => [],
    ];

    $returnString = $this->getRandomGenerator()->string();
    $returnValue = [
      '#markup' => $returnString,
    ];
    $this->libraryPlugin->expects($this->once())
      ->method('render')
      ->with($this->callback(function ($arg) use ($patternMock, $element) {
        return $arg[0] == $patternMock;
      }))
      ->willReturn($returnValue);

    $result = $this->patternElement->preRenderPatternElement($element);

    // @todo Restore this back to rendering once double-escaping in the renderer is resolved.
    // Sometimes, rendering with special characters gets HTML encoded causing
    // inconsistent test failures unrelated to the actual code functionality
    // being tested.
    $this->assertEquals($returnString, $result['#markup']);
  }

  /**
   * Test that config values are passed for plugin processing.
   *
   * @covers ::preRenderPatternElement
   * @covers ::preprocessConfigValues
   * @doesNotPerformAssertions
   */
  public function testPreRenderProcessesConfigValues() {
    // Create a unique config set for confirmation in the mock.
    $config = [
      'test_value' => $this->getRandomGenerator()->string(),
    ];

    // Mock a pattern for rendering.
    $patternMock = $this->createMock(PatternInterface::class);
    $pattern = [
      '#type' => 'pattern',
      '#pattern' => $patternMock,
      '#config' => $config,
      '#context' => [],
    ];

    // Expect the processor plugin manager to receive the config values.
    $this->patternFieldProcessorPluginManager
      ->expects($this->once())
      ->method('processSchemaValues')
      ->with($patternMock, $config);

    $this->patternElement->preRenderPatternElement($pattern);
  }

  /**
   * Test pattern prerendering without a pattern for input.
   *
   * @covers ::preRenderPatternElement
   */
  public function testPreRenderPatternElementWithoutPattern() {
    $pattern = [
      '#type' => 'pattern',
      '#pattern' => NULL,
      '#config' => [],
      '#context' => [],
    ];

    $output = $this->patternElement->preRenderPatternElement($pattern);

    // Expect error output.
    $this->assertEquals([
      '#markup' => 'Pattern unavailable.',
    ], $output);
  }

  /**
   * Test pattern prerendering with an exception during processing.
   *
   * @covers ::preRenderPatternElement
   */
  public function testPreRenderElementWithError() {
    // Prepare to throw an exception during processing to test error handling.
    $exception = new SchemaValidationException('Expected exception.');
    $this->patternFieldProcessorPluginManager->expects($this->once())
      ->method('processSchemaValues')
      ->willThrowException($exception);

    // Mock a pattern for rendering.
    $patternMock = $this->createMock(PatternInterface::class);
    $config = [
      'config_value' => $this->getRandomGenerator()->string(),
    ];
    $context = [
      'context_value' => $this->getRandomGenerator()->object(),
    ];
    $pattern = [
      '#type' => 'pattern',
      '#pattern' => $patternMock,
      '#config' => $config,
      '#context' => $context,
    ];

    $output = $this->patternElement->preRenderPatternElement($pattern);

    // Expect to receive an error render element back for rendering.
    $this->assertArrayHasKey('error', $output);
    $this->assertEquals('pattern_error', $output['error']['#type']);
    $this->assertEquals($patternMock, $output['error']['#pattern']);
    $this->assertEquals($config, $output['error']['#config']);
    $this->assertEquals($context, $output['error']['#context']);
    $this->assertEquals($exception, $output['error']['#exception']);
  }

}
