<?php

namespace Drupal\Tests\patternkit\Unit\Plugin\PatternLibrary;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternFormBuilderInterface;
use Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig;
use Drupal\Tests\patternkit\Traits\AssertArraySubsetTrait;
use Drupal\Tests\UnitTestCase;
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->twigEnvironment = $this->prophesize(TwigEnvironment::class);
    $this->twigEnvironment->isDebug()->willReturn(FALSE);

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
      $this->prophesize(SerializationInterface::class)->reveal(),
      $this->prophesize(PatternFormBuilderInterface::class)->reveal(),
      [],
      'test',
      []
    );
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

    $result = $this->plugin->render([$patternProphecy->reveal()]);

    $element = $result[0];

    // Confirm the render array structure is returned as expected.
    $this->assertArrayHasKey('#type', $element);
    $this->assertEquals('inline_template', $element['#type']);
    $this->assertArrayHasKey('#template', $element);
    $this->assertEquals($template, $element['#template']);
    $this->assertArrayHasKey('#context', $element);

    // Check that the context contains the config values.
    $this->assertArrayHasKey('example', $element['#context']);
    $this->assertEquals('config', $element['#context']['example']);
    $this->assertArrayHasKey('nested', $element['#context']);
    $this->assertEquals(['values'], $element['#context']['nested']);

    // Check that the pattern object is also in the context.
    $this->assertArrayHasKey('pattern', $element['#context']);
    $this->assertSame($patternProphecy->reveal(), $element['#context']['pattern']);
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
   * Test twig environment service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $testTwigEnvironment;

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
   * Get the test twig environment service.
   *
   * @return \Drupal\Core\Template\TwigEnvironment
   *   The twig environment service.
   */
  public function getTwigEnvironment(): TwigEnvironment {
    return $this->testTwigEnvironment;
  }

}
