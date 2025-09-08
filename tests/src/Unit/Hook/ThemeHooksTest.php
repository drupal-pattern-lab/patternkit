<?php

namespace Drupal\Tests\patternkit\Unit\Hook;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\patternkit\Hook\ThemeHooks;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for the ThemeHooks service.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Hook\ThemeHooks
 */
class ThemeHooksTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The key-value expirable factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface>
   */
  protected ObjectProphecy $keyValueExpirableFactory;

  /**
   * The language manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Language\LanguageManagerInterface>
   */
  protected ObjectProphecy $languageManager;

  /**
   * The logger service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Logger\LoggerChannelInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The container.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Symfony\Component\DependencyInjection\ContainerInterface>
   */
  protected ObjectProphecy $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->keyValueExpirableFactory = $this->prophesize(KeyValueExpirableFactoryInterface::class);
    $this->languageManager = $this->prophesize(LanguageManagerInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $this->container = $this->prophesize(ContainerInterface::class);
  }

  /**
   * Creates a test subject with mocked dependencies.
   */
  protected function createTestSubject(): ThemeHooks {
    return new ThemeHooks(
      $this->keyValueExpirableFactory->reveal(),
      $this->languageManager->reveal()
    );
  }

  /**
   * Tests linkAlter with a valid patternkit block URL.
   */
  public function testLinkAlterWithValidPatternkitBlock(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL for a patternkit block.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('layout_builder.add_block');
    $url->getRouteParameters()->willReturn(['plugin_id' => 'patternkit_block_example']);

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => [],
        ],
      ],
    ];

    $themeHooks->linkAlter($variables);

    $this->assertContains('patternkit-add-block', $variables['options']['attributes']['class']);
    $this->assertEquals('{"width": 900}', $variables['options']['attributes']['data-dialog-options']);
  }

  /**
   * Tests linkAlter with a non-patternkit block URL.
   */
  public function testLinkAlterWithNonPatternkitBlock(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL for a non-patternkit block.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('layout_builder.add_block');
    $url->getRouteParameters()->willReturn(['plugin_id' => 'other_block_type']);

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => [],
        ],
      ],
    ];

    $originalClass = $variables['options']['attributes']['class'];
    $originalDialogOptions = $variables['options']['attributes']['data-dialog-options'] ?? NULL;

    $themeHooks->linkAlter($variables);

    // Should not be modified.
    $this->assertEquals($originalClass, $variables['options']['attributes']['class']);
    $this->assertEquals($originalDialogOptions, $variables['options']['attributes']['data-dialog-options'] ?? NULL);
  }

  /**
   * Tests linkAlter with a non-routed URL (external link).
   */
  public function testLinkAlterWithExternalUrl(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock external URL.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(FALSE);

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => [],
        ],
      ],
    ];

    $originalClass = $variables['options']['attributes']['class'];
    $originalDialogOptions = $variables['options']['attributes']['data-dialog-options'] ?? NULL;

    $themeHooks->linkAlter($variables);

    // Should not be modified.
    $this->assertEquals($originalClass, $variables['options']['attributes']['class']);
    $this->assertEquals($originalDialogOptions, $variables['options']['attributes']['data-dialog-options'] ?? NULL);
  }

  /**
   * Tests linkAlter with an external URL causing UnexpectedValueException.
   */
  public function testLinkAlterWithExternalUrlCausingException(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock external URL that would throw an exception when
    // getRouteName() is called.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(FALSE);

    // Even though isRouted() returns FALSE, if getRouteName() is called,
    // it would throw an exception.
    $url->getRouteName()->willThrow(new \UnexpectedValueException('https://www.drupal.org has no corresponding route.'));

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => [],
        ],
      ],
    ];

    $originalClass = $variables['options']['attributes']['class'];
    $originalDialogOptions = $variables['options']['attributes']['data-dialog-options'] ?? NULL;

    // This should not throw an exception and should log the error.
    $themeHooks->linkAlter($variables);

    // Should not be modified.
    $this->assertEquals($originalClass, $variables['options']['attributes']['class']);
    $this->assertEquals($originalDialogOptions, $variables['options']['attributes']['data-dialog-options'] ?? NULL);
  }

  /**
   * Tests linkAlter with a different route.
   */
  public function testLinkAlterWithDifferentRoute(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL for a different route.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('node.add');

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => [],
        ],
      ],
    ];

    $originalClass = $variables['options']['attributes']['class'];
    $originalDialogOptions = $variables['options']['attributes']['data-dialog-options'] ?? NULL;

    $themeHooks->linkAlter($variables);

    // Should not be modified.
    $this->assertEquals($originalClass, $variables['options']['attributes']['class']);
    $this->assertEquals($originalDialogOptions, $variables['options']['attributes']['data-dialog-options'] ?? NULL);
  }

  /**
   * Tests linkAlter with missing plugin_id parameter.
   */
  public function testLinkAlterWithMissingPluginId(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL without plugin_id parameter.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('layout_builder.add_block');
    $url->getRouteParameters()->willReturn([]);

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => [],
        ],
      ],
    ];

    $originalClass = $variables['options']['attributes']['class'];
    $originalDialogOptions = $variables['options']['attributes']['data-dialog-options'] ?? NULL;

    // This should not throw an exception and should log the error.
    $themeHooks->linkAlter($variables);

    // Should not be modified.
    $this->assertEquals($originalClass, $variables['options']['attributes']['class']);
    $this->assertEquals($originalDialogOptions, $variables['options']['attributes']['data-dialog-options'] ?? NULL);
  }

  /**
   * Tests linkAlter with empty plugin_id parameter.
   */
  public function testLinkAlterWithEmptyPluginId(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL with empty plugin_id parameter.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('layout_builder.add_block');
    $url->getRouteParameters()->willReturn(['plugin_id' => '']);

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => [],
        ],
      ],
    ];

    $originalClass = $variables['options']['attributes']['class'];
    $originalDialogOptions = $variables['options']['attributes']['data-dialog-options'] ?? NULL;

    $themeHooks->linkAlter($variables);

    // Should not be modified.
    $this->assertEquals($originalClass, $variables['options']['attributes']['class']);
    $this->assertEquals($originalDialogOptions, $variables['options']['attributes']['data-dialog-options'] ?? NULL);
  }

  /**
   * Tests linkAlter with UnexpectedValueException from getRouteName().
   */
  public function testLinkAlterWithRouteNameException(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL that throws an exception.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willThrow(new \UnexpectedValueException('Route not found'));

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => [],
        ],
      ],
    ];

    $originalClass = $variables['options']['attributes']['class'];
    $originalDialogOptions = $variables['options']['attributes']['data-dialog-options'] ?? NULL;

    // Should not throw an exception and should log the error.
    $themeHooks->linkAlter($variables);

    // Should not be modified.
    $this->assertEquals($originalClass, $variables['options']['attributes']['class']);
    $this->assertEquals($originalDialogOptions, $variables['options']['attributes']['data-dialog-options'] ?? NULL);
  }

  /**
   * Tests linkAlter with UnexpectedValueException from getRouteParameters().
   */
  public function testLinkAlterWithRouteParametersException(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL that throws an exception on getRouteParameters.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('layout_builder.add_block');
    $url->getRouteParameters()->willThrow(new \UnexpectedValueException('Parameters not found'));

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => [],
        ],
      ],
    ];

    $originalClass = $variables['options']['attributes']['class'];
    $originalDialogOptions = $variables['options']['attributes']['data-dialog-options'] ?? NULL;

    // Should not throw an exception and should log the error.
    $themeHooks->linkAlter($variables);

    // Should not be modified.
    $this->assertEquals($originalClass, $variables['options']['attributes']['class']);
    $this->assertEquals($originalDialogOptions, $variables['options']['attributes']['data-dialog-options'] ?? NULL);
  }

  /**
   * Tests linkAlter with existing attributes.
   */
  public function testLinkAlterWithExistingAttributes(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL for a patternkit block.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('layout_builder.add_block');
    $url->getRouteParameters()->willReturn(['plugin_id' => 'patternkit_block_example']);

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          'class' => ['existing-class', 'another-class'],
          'data-dialog-options' => '{"width": 500}',
        ],
      ],
    ];

    $themeHooks->linkAlter($variables);

    $this->assertContains('existing-class', $variables['options']['attributes']['class']);
    $this->assertContains('another-class', $variables['options']['attributes']['class']);
    $this->assertContains('patternkit-add-block', $variables['options']['attributes']['class']);
    $this->assertEquals('{"width": 900}', $variables['options']['attributes']['data-dialog-options']);
  }

  /**
   * Tests linkAlter with missing options structure.
   */
  public function testLinkAlterWithMissingOptions(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL for a patternkit block.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('layout_builder.add_block');
    $url->getRouteParameters()->willReturn(['plugin_id' => 'patternkit_block_example']);

    $variables = [
      'url' => $url->reveal(),
      // Missing options structure.
    ];

    // Should not throw an exception.
    $themeHooks->linkAlter($variables);

    // Should create the options structure.
    $this->assertArrayHasKey('options', $variables);
    $this->assertArrayHasKey('attributes', $variables['options']);
    $this->assertContains('patternkit-add-block', $variables['options']['attributes']['class']);
    $this->assertEquals('{"width": 900}', $variables['options']['attributes']['data-dialog-options']);
  }

  /**
   * Tests linkAlter with missing attributes structure.
   */
  public function testLinkAlterWithMissingAttributes(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL for a patternkit block.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('layout_builder.add_block');
    $url->getRouteParameters()->willReturn(['plugin_id' => 'patternkit_block_example']);

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        // Missing attributes structure.
      ],
    ];

    // Should not throw an exception.
    $themeHooks->linkAlter($variables);

    // Should create the attributes structure.
    $this->assertArrayHasKey('attributes', $variables['options']);
    $this->assertContains('patternkit-add-block', $variables['options']['attributes']['class']);
    $this->assertEquals('{"width": 900}', $variables['options']['attributes']['data-dialog-options']);
  }

  /**
   * Tests linkAlter with missing class array.
   */
  public function testLinkAlterWithMissingClassArray(): void {
    $themeHooks = $this->createTestSubject();
    $themeHooks->setLogger($this->logger->reveal());

    // Create a mock URL for a patternkit block.
    $url = $this->prophesize(Url::class);
    $url->isRouted()->willReturn(TRUE);
    $url->getRouteName()->willReturn('layout_builder.add_block');
    $url->getRouteParameters()->willReturn(['plugin_id' => 'patternkit_block_example']);

    $variables = [
      'url' => $url->reveal(),
      'options' => [
        'attributes' => [
          // Missing class array.
        ],
      ],
    ];

    // Should not throw an exception.
    $themeHooks->linkAlter($variables);

    // Should create the class array.
    $this->assertArrayHasKey('class', $variables['options']['attributes']);
    $this->assertIsArray($variables['options']['attributes']['class']);
    $this->assertContains('patternkit-add-block', $variables['options']['attributes']['class']);
    $this->assertEquals('{"width": 900}', $variables['options']['attributes']['data-dialog-options']);
  }

  /**
   * Tests the create method.
   */
  public function testCreate(): void {
    $this->container->get('keyvalue.expirable')
      ->willReturn($this->keyValueExpirableFactory->reveal());
    $this->container->get('language_manager')
      ->willReturn($this->languageManager->reveal());
    $this->container->get('logger.channel.patternkit')
      ->willReturn($this->logger->reveal());

    $themeHooks = ThemeHooks::create($this->container->reveal());

    $this->assertInstanceOf(ThemeHooks::class, $themeHooks);
  }

}
