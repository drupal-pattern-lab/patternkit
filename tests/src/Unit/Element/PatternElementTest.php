<?php

namespace Drupal\Tests\patternkit\Unit\Element;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\patternkit\Element\Pattern;
use Drupal\patternkit\Entity\Pattern as EntityPattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Event\PatternRenderFailureEvent;
use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternFieldProcessorPluginManager;
use Drupal\patternkit\PatternkitEvents;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\PatternRepository;
use Drupal\Tests\Core\Render\RendererTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Test functionality for the Pattern render element.
 *
 * @coversDefaultClass \Drupal\patternkit\Element\Pattern
 * @covers ::__construct
 * @covers ::create
 * @group patternkit
 */
class PatternElementTest extends RendererTestBase {

  /**
   * The mocked pattern library plugin manager.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager&\PHPUnit\Framework\MockObject\MockObject
   */
  protected PatternLibraryPluginManager $patternLibraryPluginManager;

  /**
   * The mocked pattern field processor plugin manager.
   *
   * @var \Drupal\patternkit\PatternFieldProcessorPluginManager&\PHPUnit\Framework\MockObject\MockObject
   */
  protected PatternFieldProcessorPluginManager $patternFieldProcessorPluginManager;

  /**
   * A mocked pattern library plugin.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected PatternLibraryPluginInterface $libraryPlugin;

  /**
   * A mocked pattern repository service.
   *
   * @var \Drupal\patternkit\PatternRepository&\PHPUnit\Framework\MockObject\MockObject
   */
  protected PatternRepository $patternRepository;

  /**
   * A mocked event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface&\PHPUnit\Framework\MockObject\MockObject
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
   * @var \Drupal\patternkit\Element\Pattern
   */
  protected Pattern $patternElement;

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

    $this->patternRepository = $this->createMock(PatternRepository::class);

    // Mock the event dispatcher and return the dispatched event instance
    // automatically.
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->eventDispatcher->method('dispatch')
      ->with($this->isInstanceOf(PatternRenderFailureEvent::class), PatternkitEvents::RENDER_FAILURE)
      ->willReturnArgument(0);

    // Mock the string translation service.
    $this->translation = $this->getStringTranslationStub();

    // Register mocked services into the container.
    $this->container = \Drupal::getContainer();
    $this->container->set('plugin.manager.library.pattern', $this->patternLibraryPluginManager);
    $this->container->set('plugin.manager.pattern_field_processor', $this->patternFieldProcessorPluginManager);
    $this->container->set('patternkit.pattern.repository', $this->patternRepository);
    $this->container->set('event_dispatcher', $this->eventDispatcher);
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
   * @covers ::loadPattern
   */
  public function testLoadPattern() {
    // Scenario 1: Load a known pattern from string name.
    $patternMock = $this->createMock(PatternInterface::class);
    $expectedElement = [
      '#type' => 'pattern',
      '#pattern' => $patternMock,
    ];

    $this->patternRepository->method('getPattern')
      ->with('@my/pattern')
      ->willReturn($patternMock);

    // Expect the pattern element with the loaded pattern to be returned.
    $this->assertEquals($expectedElement, $this->patternElement->loadPattern([
      '#type' => 'pattern',
      '#pattern' => '@my/pattern',
    ]));

    // Scenario 2: Attempt to load an unknown pattern from a string name.
    $exception = new UnknownPatternException('My expected exception');
    $this->patternRepository->method('getPattern')
      ->with('@unknown/pattern')
      ->willThrowException($exception);

    // Expect the exception to remain unhandled and get thrown.
    // NB. In kernel testing the display for the pattern_error element can be
    // verified since event dispatching is now required for it to be replaced.
    $this->expectException(UnknownPatternException::class);
    $this->patternElement->loadPattern([
      '#type' => 'pattern',
      '#pattern' => '@unknown/pattern',
    ]);
  }

  /**
   * @covers ::preRenderPatternElement
   * @covers ::getPatternLibraryPlugin
   *
   * @dataProvider providerPreRenderPatternElement()
   */
  public function testPreRenderPatternElement(array $element) {
    $patternMock = $this->getPatternMock();
    $element['#pattern'] = $patternMock;

    $returnString = $this->getRandomGenerator()->string();
    $returnValue = [
      '#markup' => $returnString,
    ];
    $this->libraryPlugin->expects($this->once())
      ->method('render')
      ->with($this->callback(function ($arg) use ($patternMock): bool {
        // Confirm the mock pattern was the first array element passed in.
        return $arg[0] == $patternMock;
      }))
      ->willReturn($returnValue);

    $result = $this->patternElement->preRenderPatternElement($element);

    // @todo Restore this back to rendering once double-escaping in the renderer
    //   is resolved.
    // Sometimes, rendering with special characters gets HTML encoded causing
    // inconsistent test failures unrelated to the actual code functionality
    // being tested.
    $this->assertEquals($returnString, $result['#markup']);

    // Confirm cache metadata persisted through.
    if (isset($element['#cache'])) {
      // Ensure keys for basic metadata exist for comparison since they'll be
      // stubbed out as empty arrays if not present.
      $this->assertEquals($element['#cache'] + [
        'contexts' => [],
        'tags' => [],
        'max-age' => [],
      ], $result['#cache']);
    }

    // Confirm any child elements persisted through.
    // Child elements would be any array keys not beginning with a '#'.
    $child_elements = array_filter($element, fn($key) => !str_starts_with($key, '#'), ARRAY_FILTER_USE_KEY);
    foreach ($child_elements as $key => $value) {
      $this->assertEquals($value, $result[$key]);
    }
  }

  /**
   * Data provider for testPreRenderPatternElement().
   */
  public static function providerPreRenderPatternElement() {
    return [
      'basic' => [
        [
          '#type' => 'pattern',
          '#config' => [],
          '#context' => [],
        ],
      ],
      'basic cache metadata' => [
        [
          '#type' => 'pattern',
          '#config' => [],
          '#context' => [],
          // Add cache and metadata to test persistence through to output.
          '#cache' => [
            'contexts' => [],
            'tags' => ['my', 'cache', 'tags'],
            'max-age' => '123',
          ],
        ],
      ],
      'cache bin' => [
        [
          '#type' => 'pattern',
          '#config' => [],
          '#context' => [],
          // Add cache and context values to test persistence through to output.
          '#cache' => [
            'bin' => 'patternkit_test',
          ],
        ],
      ],
      'cache keys' => [
        [
          '#type' => 'pattern',
          '#config' => [],
          '#context' => [],
          // Add cache and context values to test persistence through to output.
          '#cache' => [
            'keys' => ['pattern:1', 'pattern:2'],
            'contexts' => [],
            'tags' => ['my', 'cache', 'tags'],
            'max-age' => '123',
          ],
        ],
      ],
      'child element' => [
        [
          '#type' => 'pattern',
          '#config' => [],
          '#context' => [],
          // Add a child element to be persisted as well.
          'some_child' => [
            '#type' => 'pattern_child',
          ],
        ],
      ],
    ];
  }

  /**
   * Test that config values are passed for plugin processing.
   *
   * @covers ::preRenderPatternElement
   * @covers ::getPatternLibraryPlugin
   */
  public function testPreRenderProcessesConfigValues() {
    // Create a unique config set for confirmation in the mock.
    $config = [
      'test_value' => $this->getRandomGenerator()->string(),
    ];

    // Mock a pattern for rendering.
    $patternMock = $this->getPatternMock();
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
    $this->assertArrayHasKey('#markup', $output);
    $this->assertInstanceOf(TranslatableMarkup::class, $output['#markup']);
    $this->assertEquals('This content is unavailable.', $output['#markup']->getUntranslatedString());
  }

  /**
   * Test pattern pre-rendering with an exception during processing.
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
    $patternMock = $this->getPatternMock();
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

    // Expect to receive a basic error message to display.
    // NB. More robust error-handling with the pattern_error element can be
    // tested with kernel testing since that is now replaced using event
    // dispatching.
    $this->assertArrayHasKey('#markup', $output);
    $this->assertInstanceOf(TranslatableMarkup::class, $output['#markup']);
    $this->assertEquals('This content is unavailable.', $output['#markup']->getUntranslatedString());
  }

  /**
   * Get a mocked pattern entity for testing.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   A mocked pattern entity for testing.
   */
  protected function getPatternMock(): PatternInterface {
    // Mock the implementation class instead of the interface to sidestep
    // assignment to dynamic property deprecation warnings for instance
    // variables declared on the class.
    $patternMock = $this->createMock(EntityPattern::class);
    $patternMock->expects($this->once())
      ->method('getCacheTags')
      ->willReturn([]);
    $patternMock->expects($this->once())
      ->method('getCacheMaxAge')
      ->willReturn([]);
    $patternMock->expects($this->once())
      ->method('getCacheContexts')
      ->willReturn([]);

    return $patternMock;
  }

}
