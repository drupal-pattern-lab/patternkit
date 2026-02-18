<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\SchemaContract;

/**
 * Unit tests for the SchemaFactory service.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Schema\SchemaFactory
 * @uses \Swaggest\JsonSchema\Schema
 */
class SchemaFactoryTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The schema factory service being tested.
   *
   * @var \Drupal\patternkit\Schema\SchemaFactory
   */
  protected SchemaFactory $factory;

  /**
   * The context builder service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\ContextBuilder>
   */
  protected ObjectProphecy $contextBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    SchemaFactory::clearCache();
    $this->contextBuilder = $this->prophesize(ContextBuilder::class);
  }

  /**
   * A helper function to reveal mocks and get the schema factory for tests.
   *
   * This is helpful to defer instantiation of the mocks and allow preparation
   * of them in individual tests.
   *
   * @return \Drupal\patternkit\Schema\SchemaFactory
   *   A SchemaFactory instance for testing with revealed mocks injected.
   */
  protected function getSchemaFactory(): SchemaFactory {
    return new SchemaFactory($this->contextBuilder->reveal());
  }

  /**
   * Test schema creation without a provided context.
   */
  public function testCreateInstanceWithoutContext() {
    $this->contextBuilder->getDefaultContext()
      ->willReturn(new Context())
      ->shouldBeCalledOnce();
    $this->contextBuilder->configureContextDefaults()->shouldNotBeCalled();

    $schema = $this->getSchemaFactory()
      ->createInstance('{ "test": "value" }');

    $this->assertInstanceOf(SchemaContract::class, $schema);
  }

  /**
   * Test schema creation without a provided context.
   */
  public function testCreateInstanceWithContext() {
    $context = new Context();

    $this->contextBuilder->configureContextDefaults(Argument::is($context))
      ->willReturn($context)
      ->shouldBeCalledOnce();
    $this->contextBuilder->getDefaultContext()->shouldNotBeCalled();

    $schema = $this->getSchemaFactory()
      ->createInstance('{ "test": "value" }', $context);

    $this->assertInstanceOf(SchemaContract::class, $schema);
  }

  /**
   * Same schema string returns the same compiled schema instance.
   */
  public function testSameSchemaReturnsSameInstance(): void {
    $schemaJson = '{"type": "object", "properties": {"foo": {"type": "string"}}}';
    $this->contextBuilder->getDefaultContext()
      ->willReturn(new Context());
    $this->contextBuilder->configureContextDefaults()->shouldNotBeCalled();

    $factory = $this->getSchemaFactory();
    $first = $factory->createInstance($schemaJson);
    $second = $factory->createInstance($schemaJson);

    $this->assertSame($first, $second, 'Identical schema strings must return the same Schema instance.');
  }

  /**
   * Different schema strings return different compiled schema instances.
   */
  public function testDifferentSchemaStringsReturnDifferentInstances(): void {
    $schema1 = '{"type": "object", "properties": {"a": {"type": "string"}}}';
    $schema2 = '{"type": "object", "properties": {"b": {"type": "number"}}}';
    $this->contextBuilder->getDefaultContext()
      ->willReturn(new Context());
    $this->contextBuilder->configureContextDefaults()->shouldNotBeCalled();

    $factory = $this->getSchemaFactory();
    $first = $factory->createInstance($schema1);
    $second = $factory->createInstance($schema2);

    $this->assertNotSame($first, $second, 'Different schema strings must return different Schema instances.');
  }

  /**
   * Clears the cache so the next createInstance compiles a new schema.
   */
  public function testClearCacheClearsCache(): void {
    $schemaJson = '{"type": "object"}';
    $this->contextBuilder->getDefaultContext()
      ->willReturn(new Context());
    $this->contextBuilder->configureContextDefaults()->shouldNotBeCalled();

    $factory = $this->getSchemaFactory();
    $cached = $factory->createInstance($schemaJson);
    SchemaFactory::clearCache();
    $afterClear = $factory->createInstance($schemaJson);

    $this->assertNotSame($cached, $afterClear, 'After clearCache(), createInstance must return a new Schema instance.');
  }

}
