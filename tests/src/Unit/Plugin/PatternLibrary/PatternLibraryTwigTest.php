<?php

namespace Drupal\Tests\patternkit\Unit\Plugin\PatternLibrary;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternFormBuilderInterface;
use Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig;
use Drupal\Tests\patternkit\Traits\AssertArraySubsetTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Unit tests for the PatternLibraryTwig library plugin.
 *
 * @group patternkit
 * @covers \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig
 */
class PatternLibraryTwigTest extends UnitTestCase {

  use AssertArraySubsetTrait;
  use ProphecyTrait;

  /**
   * The plugin being tested.
   *
   * @var \Drupal\Tests\patternkit\Unit\Plugin\PatternLibrary\TestablePatternLibraryTwig
   */
  protected TestablePatternLibraryTwig $plugin;

  /**
   * The stubbed twig environment service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Template\TwigEnvironment>
   */
  protected ObjectProphecy $twigEnvironment;

  /**
   * The stubbed renderer service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Render\RendererInterface>
   */
  protected ObjectProphecy $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->twigEnvironment = $this->prophesize(TwigEnvironment::class);
    $this->twigEnvironment->isDebug()->willReturn(FALSE);

    $this->renderer = $this->prophesize(RendererInterface::class);

    // Create a mock config factory that returns a valid config.
    $configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $configFactory->get('patternkit.settings')->willReturn($config->reveal());

    // Create a simple instance of our testable class with
    // required dependencies.
    $this->plugin = new TestablePatternLibraryTwig(
      '/test/root',
      $this->prophesize(PatternLibraryParserInterface::class)->reveal(),
      $configFactory->reveal(),
      $this->renderer->reveal(),
      $this->prophesize(SerializationInterface::class)->reveal(),
      $this->prophesize(PatternFormBuilderInterface::class)->reveal(),
      [],
      'test',
      []
    );
    $this->plugin->setTestRenderer($this->renderer->reveal());
    $this->plugin->setTestTwigEnvironment($this->twigEnvironment->reveal());
  }

  /**
   * Test the render function.
   */
  public function testRender(): void {
    $template = 'My test template';
    $config = [
      'example' => 'config',
      'nested' => ['values'],
    ];
    $patternProphecy = $this->getPatternMock($template, $config);

    // Mock the renderer to return a simple string.
    $this->renderer
      ->render(Argument::type('array'))
      ->willReturn('<div>Rendered output</div>');

    $result = $this->plugin->render([$patternProphecy->reveal()]);

    $element = $result[0];

    // Confirm the markup is returned as expected.
    $this->assertArrayHasKey('#markup', $element);
    $this->assertEquals('<div>Rendered output</div>', $element['#markup']);

    // Verify the renderer was called with the expected render array.
    $this->renderer
      ->render(Argument::that(function ($renderArray) use ($template) {
        return $renderArray['#type'] === 'inline_template' &&
          $renderArray['#template'] === $template &&
          isset($renderArray['#context']['example']) &&
          $renderArray['#context']['example'] === 'config';
      }))
      ->shouldHaveBeenCalledOnce();
  }

  /**
   * Get a mocked pattern entity for testing.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>
   *   A mocked pattern entity for testing.
   */
  protected function getPatternMock(string $template, array $config): ObjectProphecy {
    $pattern = $this->prophesize(PatternInterface::class);

    $pattern->getTemplate()->willReturn($template);
    $pattern->config = $config;

    return $pattern;
  }

}

/**
 * Test double for PatternLibraryTwig that overrides problematic methods.
 *
 * @internal
 */
class TestablePatternLibraryTwig extends PatternLibraryTwig {
  /**
   * Test root path.
   *
   * @var string
   */
  protected $testRoot = '/test/root';

  /**
   * Test renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $testRenderer;

  /**
   * Test twig environment service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $testTwigEnvironment;

  /**
   * Set the test renderer service.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function setTestRenderer($renderer) {
    $this->testRenderer = $renderer;
  }

  /**
   * Set the test twig environment service.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $twigEnvironment
   *   The twig environment service.
   */
  public function setTestTwigEnvironment($twigEnvironment) {
    $this->testTwigEnvironment = $twigEnvironment;
  }

  /**
   * Get the test root path.
   *
   * @return string
   *   The root path.
   */
  protected function getRoot(): string {
    return $this->testRoot;
  }

  /**
   * Get the test renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer service.
   */
  protected function renderer(): RendererInterface {
    return $this->testRenderer;
  }

  /**
   * Get the test twig environment service.
   *
   * @return \Drupal\Core\Template\TwigEnvironment
   *   The twig environment service.
   */
  public function getTwigEnvironment(): TwigEnvironment {
    return $this->testTwigEnvironment;
  }

}
