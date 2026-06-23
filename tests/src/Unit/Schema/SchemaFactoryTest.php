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
   * Test schema creation with a provided context.
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
    $factory->clearCache();
    $afterClear = $factory->createInstance($schemaJson);

    $this->assertNotSame($cached, $afterClear, 'After clearCache(), createInstance must return a new Schema instance.');
  }

  /**
   * Two SchemaFactory instances must not share a compiled Schema object.
   *
   * Simulates two PHP-FPM requests handled by the same worker process. Before
   * the fix, static $schemasCache is shared across all SchemaFactory instances:
   * both instances return the same compiled Schema object (same pointer). After
   * the fix, each instance holds its own independent cache and compiles a fresh
   * Schema, so the two objects are distinct.
   *
   * This is the structural invariant that prevents setFormat(NULL) mutations
   * (applied by TokenValidationDataPreProcessor during request 1) from
   * contaminating schema state seen by request 2.
   *
   * Uses a real ContextBuilder rather than a Prophecy mock because
   * createInstance() mutates the Context (sets schemasCache) and a Prophecy
   * stub returns the same object on every call, which couples the two factory
   * instances through the shared Context reference.
   */
  public function testSecondFactoryInstanceDoesNotShareCacheWithFirst(): void {
    $schemaJson = '{"type": "object", "properties": {"url": {"type": "string", "format": "hostname"}}}';

    $instanceA = new SchemaFactory(new ContextBuilder());
    $schemaA = $instanceA->createInstance($schemaJson);

    $instanceB = new SchemaFactory(new ContextBuilder());
    $schemaB = $instanceB->createInstance($schemaJson);

    $this->assertNotSame($schemaA, $schemaB,
      'A second SchemaFactory instance must not return the same cached Schema object as the first.'
    );
  }

}
