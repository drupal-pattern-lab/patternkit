<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternkitEnvironmentAwareTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for PatternkitEnvironmentTrait.
 *
 * @coversDefaultClass \Drupal\patternkit\PatternkitEnvironmentAwareTrait
 * @group patternkit
 */
class PatternkitEnvironmentAwareTraitTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The patternkit environment service.
   *
   * @var \Drupal\patternkit\PatternkitEnvironment|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $environment;

  /**
   * The mocked object using the trait being tested.
   *
   * @var \Drupal\patternkit\PatternkitEnvironmentAwareTrait|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->environment = $this->prophesize(PatternkitEnvironment::class);

    $this->mock = $this->getMockForTrait(PatternkitEnvironmentAwareTrait::class);
  }

  /**
   * @covers ::setPatternkitEnvironment
   * @covers ::patternkitEnvironment
   */
  public function testSetPatternkitEnvironment() {
    // The environment should not be pulled from the container if it was already
    // explicitly set.
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('patternkit.environment')->shouldNotBeCalled();
    \Drupal::setContainer($container->reveal());

    $environment = $this->environment->reveal();
    $this->mock->setPatternkitEnvironment($environment);

    $this->assertSame($environment, $this->mock->patternkitEnvironment());
  }

  /**
   * @covers ::patternkitEnvironment
   */
  public function testPatternkitEnvironment() {
    $environment = $this->environment->reveal();

    // If the environment has not been explicitly set, it should be pulled from
    // the container.
    // The environment should not be pulled from the container if it was already
    // explicitly set.
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('patternkit.environment')
      ->willReturn($environment)->shouldBeCalledOnce();
    \Drupal::setContainer($container->reveal());

    $this->assertSame($environment, $this->mock->patternkitEnvironment());
  }

  /**
   * @covers ::isDebug
   * @covers ::patternkitEnvironment
   * @covers ::setPatternkitEnvironment
   */
  public function testIsDebug() {
    $this->environment->isDebug()
      ->willReturn(TRUE)
      ->shouldBeCalledOnce();

    $this->mock->setPatternkitEnvironment($this->environment->reveal());

    $this->assertTrue($this->mock->isDebug());
  }

  /**
   * @covers ::getEnvironmentFeature
   * @covers ::patternkitEnvironment
   * @covers ::setPatternkitEnvironment
   */
  public function testGetEnvironmentFeature() {
    $this->environment->getFeatureOption('configured_option', FALSE)
      ->willReturn(TRUE)
      ->shouldBeCalledOnce();
    $this->environment->getFeatureOption('unknown_option', FALSE)
      ->willReturn(FALSE)
      ->shouldBeCalledOnce();

    $this->mock->setPatternkitEnvironment($this->environment->reveal());

    $this->assertTrue($this->mock->getEnvironmentFeature('configured_option', FALSE));
    $this->assertFalse($this->mock->getEnvironmentFeature('unknown_option', FALSE));
  }

}
