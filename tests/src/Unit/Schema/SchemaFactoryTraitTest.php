<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\patternkit\Schema\SchemaFactoryTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for SchemaFactoryTrait.
 */
#[Group('patternkit')]
#[CoversClass(SchemaFactoryTrait::class)]
class SchemaFactoryTraitTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The patternkit schema factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\SchemaFactory>
   */
  protected ObjectProphecy $schemaFactory;

  /**
   * The class instance using the trait being tested.
   *
   * @var \Drupal\Tests\patternkit\Unit\Schema\SchemaFactoryTraitClass
   */
  protected SchemaFactoryTraitClass $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->schemaFactory = $this->prophesize(SchemaFactory::class);

    $this->sut = new SchemaFactoryTraitClass();
  }

  public function testSetSchemaFactory() {
    // The service should not be pulled from the container if it was already
    // explicitly set.
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('patternkit.schema.schema_factory')->shouldNotBeCalled();
    \Drupal::setContainer($container->reveal());

    $schemaFactory = $this->schemaFactory->reveal();
    $this->sut->setSchemaFactory($schemaFactory);

    $this->assertSame($schemaFactory, $this->sut->schemaFactory());
  }

  public function testSchemaFactory() {
    $schemaFactory = $this->schemaFactory->reveal();

    // If the service has not been explicitly set, it should be pulled from
    // the container.
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('patternkit.schema.schema_factory')
      ->willReturn($schemaFactory)->shouldBeCalledOnce();
    \Drupal::setContainer($container->reveal());

    $this->assertSame($schemaFactory, $this->sut->schemaFactory());
  }

}

/**
 * A test-only class instance using the trait to be tested.
 *
 * @internal
 */
class SchemaFactoryTraitClass {

  use SchemaFactoryTrait {
    setSchemaFactory as public;
    schemaFactory as public;
  }

}
