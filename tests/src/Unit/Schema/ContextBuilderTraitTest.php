<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\ContextBuilderTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for ContextBuilderTrait.
 */
#[Group('patternkit')]
#[CoversClass(ContextBuilderTrait::class)]
class ContextBuilderTraitTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The patternkit context builder service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\ContextBuilder>
   */
  protected ObjectProphecy $contextBuilder;

  /**
   * The patternkit schema factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\SchemaFactory>
   */
  protected ObjectProphecy $schemaFactory;

  /**
   * The class instance using the trait being tested.
   *
   * @var \Drupal\Tests\patternkit\Unit\Schema\ContextBuilderTraitClass
   */
  protected ContextBuilderTraitClass $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->contextBuilder = $this->prophesize(ContextBuilder::class);

    $this->sut = new ContextBuilderTraitClass();
  }

  public function testSetContextBuilder() {
    // The service should not be pulled from the container if it was already
    // explicitly set.
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('patternkit.schema.context_builder')->shouldNotBeCalled();
    \Drupal::setContainer($container->reveal());

    $contextBuilder = $this->contextBuilder->reveal();
    $this->sut->setContextBuilder($contextBuilder);

    $this->assertSame($contextBuilder, $this->sut->contextBuilder());
  }

  public function testContextBuilder() {
    $contextBuilder = $this->contextBuilder->reveal();

    // If the service has not been explicitly set, it should be pulled from
    // the container.
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('patternkit.schema.context_builder')
      ->willReturn($contextBuilder)->shouldBeCalledOnce();
    \Drupal::setContainer($container->reveal());

    $this->assertSame($contextBuilder, $this->sut->contextBuilder());
  }

}

/**
 * A test-only class instance using the trait to be tested.
 *
 * @internal
 */
class ContextBuilderTraitClass {

  use ContextBuilderTrait {
    setContextBuilder as public;
    contextBuilder as public;
  }

}
