<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternkitEnvironmentAwareInterface;
use Drupal\patternkit\PatternkitEnvironmentAwareTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for PatternkitEnvironmentTrait.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\PatternkitEnvironmentAwareTrait
 */
class PatternkitEnvironmentAwareTraitTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The patternkit environment service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternkitEnvironment>
   */
  protected ObjectProphecy $environment;

  /**
   * The class instance using the trait being tested.
   *
   * @var \Drupal\patternkit\PatternkitEnvironmentAwareInterface
   */
  protected PatternkitEnvironmentAwareInterface $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->environment = $this->prophesize(PatternkitEnvironment::class);

    $this->sut = new PatternkitEnvironmentAwareTraitClass();
  }

  public function testSetPatternkitEnvironment() {
    // The environment should not be pulled from the container if it was already
    // explicitly set.
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('patternkit.environment')->shouldNotBeCalled();
    \Drupal::setContainer($container->reveal());

    $environment = $this->environment->reveal();
    $this->sut->setPatternkitEnvironment($environment);

    $this->assertSame($environment, $this->sut->patternkitEnvironment());
  }

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

    $this->assertSame($environment, $this->sut->patternkitEnvironment());
  }

  public function testIsDebug() {
    $this->environment->isDebug()
      ->willReturn(TRUE)
      ->shouldBeCalledOnce();

    $this->sut->setPatternkitEnvironment($this->environment->reveal());

    $this->assertTrue($this->sut->isDebug());
  }

  public function testGetEnvironmentFeature() {
    $this->environment->getFeatureOption('configured_option', FALSE)
      ->willReturn(TRUE)
      ->shouldBeCalledOnce();
    $this->environment->getFeatureOption('unknown_option', FALSE)
      ->willReturn(FALSE)
      ->shouldBeCalledOnce();

    $this->sut->setPatternkitEnvironment($this->environment->reveal());

    $this->assertTrue($this->sut->getEnvironmentFeature('configured_option', FALSE));
    $this->assertFalse($this->sut->getEnvironmentFeature('unknown_option', FALSE));
  }

}

/**
 * A test-only class instance using the trait to be tested.
 *
 * @internal
 */
class PatternkitEnvironmentAwareTraitClass implements PatternkitEnvironmentAwareInterface {

  use PatternkitEnvironmentAwareTrait;

}
