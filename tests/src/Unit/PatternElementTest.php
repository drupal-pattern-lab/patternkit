<?php

namespace Drupal\Tests\patternkit\Unit;

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
   * @var \Drupal\patternkit\PatternLibraryPluginManager
   */
  protected $patternLibraryPluginManager;

  /**
   * The mocked pattern field processor plugin manager.
   *
   * @var \Drupal\patternkit\PatternFieldProcessorPluginManager
   */
  protected $patternFieldProcessorPluginManager;

  /**
   * A mocked pattern library plugin.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginInterface
   */
  protected $libraryPlugin;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translation;

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
    $container = \Drupal::getContainer();
    $container->set('plugin.manager.library.pattern', $this->patternLibraryPluginManager);
    $container->set('plugin.manager.pattern_field_processor', $this->patternFieldProcessorPluginManager);
    $container->set('string_translation', $this->translation);
  }

  /**
   * @covers ::getInfo
   */
  public function testGetInfo() {
    $patternElement = new Pattern([], 'test', 'test');
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

    $result = Pattern::preRenderPatternElement($element);

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

    Pattern::preRenderPatternElement($pattern);
  }

  /**
   * Test pattern prerendering with invalid input.
   *
   * @covers ::preRenderPatternElement
   */
  public function testPreRenderPatternElementError() {
    $pattern = [
      '#type' => 'pattern',
      '#pattern' => NULL,
      '#config' => [],
      '#context' => [],
    ];

    $output = Pattern::preRenderPatternElement($pattern);

    // Expect error output.
    $this->assertEquals([
      '#markup' => 'Pattern unavailable.',
    ], $output);
  }

}
