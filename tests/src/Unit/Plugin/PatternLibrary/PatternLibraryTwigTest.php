<?php

namespace Drupal\Tests\patternkit\Unit\Plugin\PatternLibrary;

use Drupal\Core\Template\TwigEnvironment;
use Drupal\patternkit\Entity\PatternInterface;
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
   * The partially-mocked plugin being tested.
   *
   * @var \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig&\PHPUnit\Framework\MockObject\MockObject
   */
  protected PatternLibraryTwig $plugin;

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
  }

  /**
   * Test the render function.
   */
  public function testRender(): void {
    $this->plugin = $this->createPartialMock(PatternLibraryTwig::class, [
      'getTwigEnvironment',
      't',
    ]);
    $this->plugin->method('getTwigEnvironment')->willReturn($this->twigEnvironment->reveal());
    $this->plugin->method('t')->willReturnArgument(0);

    $template = 'My test template';
    $config = [
      'example' => 'config',
      'nested' => ['values'],
    ];
    $patternProphecy = $this->getPatternMock($template, $config);

    $result = $this->plugin->render([$patternProphecy->reveal()]);

    $element = $result[0];

    // Confirm the template render array is returned as expected.
    $this->assertArraySubset([
      '#type' => 'inline_template',
      '#template' => $template,
    ], $element);

    // Confirm provided config is included as context to the template.
    $this->assertArraySubset($config, $element['#context']);

    // Confirm the pattern is also available to the template.
    $this->assertArrayHasKey('pattern', $element['#context']);
    $this->assertEquals($patternProphecy->reveal(), $element['#context']['pattern']);
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
