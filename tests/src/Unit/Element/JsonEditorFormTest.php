<?php

namespace Drupal\Tests\patternkit\Unit\Element;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\patternkit\Element\JsonEditorForm;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\PatternRepository;
use Drupal\Tests\Core\Render\RendererTestBase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for the JsonEditorForm render element.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Element\JsonEditorForm
 */
class JsonEditorFormTest extends RendererTestBase {

  use ArraySubsetAsserts;
  use ProphecyTrait;

  /**
   * The mocked pattern library plugin manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternLibraryPluginManager>
   */
  protected ObjectProphecy $patternLibraryPluginManager;

  /**
   * A mocked pattern library plugin.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternLibraryPluginInterface>
   */
  protected ObjectProphecy $libraryPlugin;

  /**
   * A mocked pattern repository service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternRepository>
   */
  protected ObjectProphecy $patternRepository;

  /**
   * A mocked config factory service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ConfigFactoryInterface
   */
  protected MockObject|ConfigFactoryInterface $configFactory;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected TranslationInterface $translation;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * The instantiated pattern element for testing.
   *
   * @var \Drupal\patternkit\Element\JsonEditorForm
   */
  protected JsonEditorForm $element;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock a library plugin to use for rendering.
    $this->libraryPlugin = $this->prophesize(PatternLibraryPluginInterface::class);

    // Mock pattern library plugin manager to return our mocked plugin instance.
    $this->patternLibraryPluginManager = $this->prophesize(PatternLibraryPluginManager::class);
    $this->patternLibraryPluginManager->createInstance(Argument::type('string'))
      ->willReturn($this->libraryPlugin);

    $this->patternRepository = $this->prophesize(PatternRepository::class);

    $this->configFactory = $this->getConfigFactoryStub();

    // Mock the string translation service.
    $this->translation = $this->getStringTranslationStub();

    // Register mocked services into the container.
    $this->container = \Drupal::getContainer();
    $this->container->set('plugin.manager.library.pattern', $this->patternLibraryPluginManager->reveal());
    $this->container->set('patternkit.pattern.repository', $this->patternRepository->reveal());
    $this->container->set('config.factory', $this->configFactory);
    $this->container->set('string_translation', $this->translation);

    $this->element = JsonEditorForm::create($this->container, [], 'jsoneditor_form', []);
  }

  public function testGetInfo() {
    $info = $this->element->getInfo();
    $this->assertArrayHasKey('#pre_render', $info);
    $this->assertArrayHasKey('#process', $info);
    $this->assertArrayHasKey('#pattern_name', $info);
    $this->assertArrayHasKey('#config', $info);
    $this->assertArrayHasKey('#theme_wrappers', $info);
  }

  public function testLoadPatternFromName() {
    // Prepare the element to be pre-rendered.
    $pattern_name = 'my_pattern';
    $render_element = [
      '#type' => 'jsoneditor_form',
      '#pattern_name' => $pattern_name,
    ];

    // Mock the pattern to be loaded and return it.
    $schema = 'My schema value';
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getSchema()->willReturn($schema);
    $this->patternRepository->getPattern($pattern_name)
      ->shouldBeCalledOnce()
      ->willReturn($pattern->reveal());

    // Run the prerender method for testing.
    $result = $this->element->loadPattern($render_element);

    // Expect the pattern to be loaded into the returned result.
    $this->assertArrayHasKey('#pattern_entity', $result);
    $this->assertSame($result['#pattern_entity'], $pattern->reveal());

    // Expect the schema to be loaded.
    $this->assertArrayHasKey('#schema', $result);
    $this->assertEquals($schema, $result['#schema']);
  }

  /**
   * Test behavior when a loaded Pattern entity is passed into the element.
   */
  public function testLoadPatternWithPresetPattern() {
    // Mock the pattern to be passed into the element.
    $schema = 'My schema value';
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getSchema()->willReturn($schema);

    // Prepare the element to be pre-rendered.
    $render_element = [
      '#type' => 'jsoneditor_form',
      '#pattern_entity' => $pattern->reveal(),
    ];

    // Since the pattern entity is already assigned, it should not be loaded.
    $this->patternRepository->getPattern(Argument::any())
      ->shouldNotBeCalled();

    // Run the prerender method for testing.
    $result = $this->element->loadPattern($render_element);

    // Expect the pattern to be loaded into the returned result.
    $this->assertArrayHasKey('#pattern_entity', $result);
    $this->assertSame($result['#pattern_entity'], $pattern->reveal());

    // Expect the schema to be loaded.
    $this->assertArrayHasKey('#schema', $result);
    $this->assertEquals($schema, $result['#schema']);
  }

  /**
   * Test behavior with no pattern specified.
   */
  public function testLoadPatternWithNoPattern() {
    // Prepare the element to be pre-rendered.
    $render_element = [
      '#type' => 'jsoneditor_form',
    ];

    // Since no pattern is specified, none should be loaded.
    $this->patternRepository->getPattern(Argument::any())
      ->shouldNotBeCalled();

    // Run the prerender method for testing.
    $result = $this->element->loadPattern($render_element);

    // Expect an error element to be returned.
    $this->assertArrayHasKey('error', $result);
    $this->assertArrayHasKey('#type', $result['error']);
    $this->assertArrayHasKey('#pattern', $result['error']);
    $this->assertArrayHasKey('#exception', $result['error']);
    $this->assertEquals('pattern_error', $result['error']['#type']);
    $this->assertEquals('', $result['error']['#pattern']);
    $this->assertInstanceOf(UnknownPatternException::class, $result['error']['#exception']);
  }

  /**
   * Test behavior with an unknown pattern specified.
   */
  public function testLoadPatternWithUnknownPattern() {
    $random = $this->getRandomGenerator();

    // Prepare the element to be pre-rendered.
    $render_element = [
      '#type' => 'jsoneditor_form',
      '#pattern_name' => 'unknown_pattern',
      '#config' => (array) $random->object(),
      '#context' => (array) $random->object(),
    ];

    // Throw an exception when the unknown pattern is requested.
    $exception_message = 'IDK what you want.';
    $exception = new UnknownPatternException($exception_message);
    $this->patternRepository->getPattern($render_element['#pattern_name'])
      ->shouldBeCalledOnce()
      ->willThrow($exception);

    // Run the prerender method for testing.
    $result = $this->element->loadPattern($render_element);

    // Expect an error element to be returned.
    $this->assertArrayHasKey('error', $result);
    $this->assertEquals([
      '#type' => 'pattern_error',
      '#pattern' => $render_element['#pattern_name'],
      '#config' => $render_element['#config'],
      '#context' => $render_element['#context'],
      '#exception' => $exception,
    ], $result['error']);

    // Expect the existing render element content to be returned.
    $this->assertArraySubset($render_element, $result);
  }

  public function testBuildEditorForm() {
    $random = $this->getRandomGenerator();

    // Mock the pattern entity to be processed.
    $pattern = $this->prophesize(PatternInterface::class);

    // Prepare the element to be pre-rendered.
    $render_element = [
      '#type' => 'jsoneditor_form',
      '#pattern_entity' => $pattern->reveal(),
      '#schema' => $random->string(50),
      '#config' => (array) $random->object(),
    ];

    // Prepare the editor array to be returned.
    $editor = (array) $random->object();

    // Mock the pattern library plugin to be used.
    $library_plugin = $this->prophesize(PatternLibraryPluginInterface::class);
    $library_plugin->getPatternEditor($pattern->reveal(), Argument::allOf(
        Argument::type('array'),
        Argument::withEntry('schemaJson', $render_element['#schema']),
        Argument::withEntry('startingJson', '{}'),
        Argument::withEntry('schemaJson', $render_element['#schema'])
    ))
      ->shouldBeCalledOnce()
      ->willReturn($editor);
    $this->patternLibraryPluginManager->getPluginForPattern($pattern->reveal())
      ->shouldBeCalledOnce()
      ->willReturn($library_plugin->reveal());

    // Run the prerender method for testing.
    $result = $this->element->buildEditorForm($render_element);

    $this->assertArraySubset($render_element, $result);
    $this->assertArrayHasKey('editor', $result);
    $this->assertEquals($editor, $result['editor']);
  }

  public function testProcessEditorForm() {
    $random = $this->getRandomGenerator();

    // Prepare the element to be pre-rendered.
    $render_element = [
      '#type' => 'jsoneditor_form',
      '#pattern_name' => 'my_pattern',
      '#schema' => $random->string(50),
      '#data' => $random->string(50),
      '#config' => (array) $random->object(),
      '#parents' => [$random->string(), $random->string()],
    ];

    // Clone the render element to test without alteration by reference.
    $element_copy = $render_element;

    // Mock additional arguments for the method.
    $form_state = $this->prophesize(FormStateInterface::class);
    $form = [];

    // Run the process method for testing.
    $result = $this->element->processEditorForm($element_copy, $form_state->reveal(), $form);

    // Expect all pre-existing values to be persisted.
    $this->assertArraySubset($render_element, $result);

    // Expect a new instance_config value to be created.
    $instance_config = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'schema_instance_config',
      ],
      '#default_value' => $render_element['#data'],
      '#parents' => $render_element['#parents'],
    ];
    $this->assertArrayHasKey('instance_config', $result);
    $this->assertEquals($instance_config, $result['instance_config']);
  }

}
