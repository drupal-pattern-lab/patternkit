<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\SchemaHelper;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

/**
 * Tests the SchemaHelper utility class.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\SchemaHelper
 * @group patternkit
 */
class SchemaHelperTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * A prophecy mock for the context builder service.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $contextBuilder;

  /**
   * A default schema context to use for schema operations.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $context;

  /**
   * A basic JSON schema with various property types, but no references.
   *
   * @var string
   */
  protected string $flatSchema = <<<JSON
    {
      "\$schema": "http://json-schema.org/draft-04/schema#",
      "category": "atom",
      "title": "Example",
      "type": "object",
      "format": "grid",
      "properties": {
        "text": {
          "title": "Text",
          "type": "string",
          "options": {
            "grid_columns": 4
          }
        },
        "formatted_text": {
          "title": "Formatted Text",
          "type": "string",
          "format": "html",
          "options": {
            "wysiwyg": true
          }
        },
        "image": {
          "title": "Image Object",
          "type": "object",
          "properties": {
            "image_url": {
              "title": "Image URL",
              "type": "string",
              "format": "image",
              "options": {
                "grid_columns": 6
              }
            }
          }
        },
        "breakpoints": {
          "title": "Breakpoints",
          "type": "array",
          "items": {
            "anyOf": [
              {
                "title": "Abbreviated sizes",
                "type": "string",
                "enum": [
                  "",
                  "xxs",
                  "xs",
                  "sm",
                  "md",
                  "lg"
                ]
              },
              {
                "title": "Explicit sizes",
                "type": "string",
                "enum": [
                  "extra-extra-small",
                  "extra-small",
                  "small",
                  "medium",
                  "large"
                ]
              }
            ]
          }
        },
        "nested_items": {
          "title": "Nested items of various types",
          "type": "array",
          "items": {
            "anyOf": [
              {
                "title": "Simple object",
                "type": "object"
              },
              {
                "title": "Simple string",
                "type": "string"
              }
            ]
          }
        }
      }
    }
    JSON;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->context = new Context();

    $this->contextBuilder = $this->prophesize(ContextBuilder::class);
    $this->contextBuilder->getDefaultContext()->willReturn($this->context);

    $container = new ContainerBuilder();
    $container->set('patternkit.schema.context_builder', $this->contextBuilder->reveal());
    \Drupal::setContainer($container);

    // Initialize to an empty context for tests that don't set anything up
    // explicitly for their use case.
    $this->context = new Context();
  }

  /**
   * @covers ::isCompositionSchema
   * @dataProvider providerIsCompositionSchema
   */
  public function testIsCompositionSchema(SchemaContract $schema, bool $expected) {
    $this->assertEquals($expected, SchemaHelper::isCompositionSchema($schema));
  }

  /**
   * Data provider for testIsCompositionSchema.
   */
  public function providerIsCompositionSchema(): array {
    $schema = Schema::import($this->decodeJson($this->flatSchema));

    $cases = [];

    $cases['top_object'] = [$schema, FALSE];
    $cases['string_property'] = [$schema->getProperties()->text, FALSE];
    $cases['formatted_text'] = [$schema->getProperties()->formatted_text, FALSE];
    $cases['object_property'] = [$schema->getProperties()->image, FALSE];
    $cases['array_property'] = [$schema->getProperties()->breakpoints, FALSE];
    $cases['array_items'] = [$schema->getProperties()->breakpoints->items, TRUE];
    $cases['nested_items'] = [
      $schema->getProperties()->nested_items->items,
      TRUE,
    ];

    return $cases;
  }

  /**
   * @covers ::getCompositionSchema
   * @covers ::getConstraintFromObject
   * @covers ::manuallyTestConstraints
   */
  public function testGetCompositionSchema() {
    $schema = Schema::import($this->decodeJson($this->flatSchema));
    $itemsSchema = $schema->getProperties()->breakpoints->items;

    $valueSchema = SchemaHelper::getCompositionSchema($itemsSchema, 'xs');

    $this->assertEquals('string', $valueSchema->type);
    $this->assertIsArray($valueSchema->enum);
    $this->assertEquals('Abbreviated sizes', $valueSchema->title);

    $valueSchema = SchemaHelper::getCompositionSchema($itemsSchema, 'large');

    $this->assertEquals('string', $valueSchema->type);
    $this->assertIsArray($valueSchema->enum);
    $this->assertEquals('Explicit sizes', $valueSchema->title);

    $nestedItemsSchema = $schema->getProperties()->nested_items->items;
    $data = (object) [
      'test_key' => 'my_test_value',
      'another_property' => 1234,
      'nested' => (object) [
        'abc' => 'def',
      ],
    ];

    $valueSchema = SchemaHelper::getCompositionSchema($nestedItemsSchema, $data);

    $this->assertEquals('object', $valueSchema->type);
    $this->assertEquals('Simple object', $valueSchema->title);
  }

  /**
   * Test expectations for throwing validation exceptions.
   *
   * @covers ::getCompositionSchema
   * @uses \Drupal\patternkit\Exception\SchemaException
   */
  public function testGetCompositionSchemaValidationException() {
    $exceptionMessage = 'Encountered intentional validation exception';
    $expectedException = new InvalidValue($exceptionMessage);
    $documentPath = '#->my->document->path';

    $schema = $this->prophesize(Schema::class);
    $schema->in('my_value', Argument::type(Context::class))
      ->willThrow($expectedException)
      ->shouldBeCalledOnce();
    $schema->getDocumentPath()->willReturn($documentPath)->shouldBeCalledOnce();

    $this->expectException(SchemaValidationException::class);
    $this->expectExceptionMessage($exceptionMessage, 'Expected the exception message to contain the original exception message.');
    $this->expectExceptionMessage($documentPath, 'Expected the exception message to contain the document path.');
    SchemaHelper::getCompositionSchema($schema->reveal(), 'my_value');
  }

}
