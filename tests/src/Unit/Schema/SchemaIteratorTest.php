<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\patternkit\Traits\SchemaFixtureTrait;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\SchemaIterator;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;

/**
 * Test the SchemaIterator class implementation.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\SchemaIterator
 * @uses \Drupal\patternkit\Schema\SchemaHelper
 * @group patternkit
 */
class SchemaIteratorTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;
  use SchemaFixtureTrait;

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
        "hidden": {
          "title": "hidden",
          "type": "string"
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

    $this->context = new Context();
  }

  /**
   * @covers ::getPropertySchema
   * @covers ::hasChildren
   * @covers ::getChildren
   * @covers ::next
   * @covers ::rewind
   * @covers ::seek
   * @uses \Drupal\patternkit\Schema\SchemaIterator::__construct
   */
  public function testIteration() {
    $values = [
      'text' => 'test',
      'image' => [
        'image_url' => 'abc123',
      ],
      'breakpoints' => [
        'xs',
        'large',
      ],
    ];

    $schema = Schema::import(static::decodeJson($this->flatSchema));
    $iterator = new SchemaIterator($schema, $values);

    // Test the first string property and value.
    $this->assertEquals('text', $iterator->key());
    $this->assertEquals($values['text'], $iterator->current());
    $this->assertEquals(FALSE, $iterator->hasChildren());
    $propertySchema = $iterator->getPropertySchema();
    $this->assertEquals('string', $propertySchema->type);

    // Test the next image object property.
    $iterator->next();
    $this->assertEquals('image', $iterator->key());
    $this->assertEquals($values['image'], $iterator->current());
    $this->assertEquals(TRUE, $iterator->hasChildren());
    $propertySchema = $iterator->getPropertySchema();
    $this->assertEquals('object', $propertySchema->type);

    // Test iteration over the child properties.
    $subIterator = $iterator->getChildren();
    $this->assertEquals('image_url', $subIterator->key());
    $this->assertEquals($values['image']['image_url'], $subIterator->current());
    $this->assertEquals(FALSE, $subIterator->hasChildren());
    $propertySchema = $subIterator->getPropertySchema();
    $this->assertEquals('string', $propertySchema->type);

    // Expect the next property to NOT be 'hidden' since no value is assigned.
    $iterator->next();
    $this->assertNotEquals('hidden', $iterator->key());

    // Test the expected breakpoints array property.
    $this->assertEquals('breakpoints', $iterator->key());
    $this->assertEquals($values['breakpoints'], $iterator->current());
    $this->assertEquals(TRUE, $iterator->hasChildren());
    $propertySchema = $iterator->getPropertySchema();
    $this->assertEquals('array', $propertySchema->type);

    // Test iteration over the child properties.
    $subIterator = $iterator->getChildren();
    $this->assertEquals('0', $subIterator->key());
    $this->assertEquals($values['breakpoints'][0], $subIterator->current());
    $this->assertEquals(FALSE, $subIterator->hasChildren());
    $propertySchema = $subIterator->getPropertySchema();
    $this->assertEquals('string', $propertySchema->type);
    $this->assertEquals('Abbreviated sizes', $propertySchema->title);

    $subIterator->next();
    $this->assertEquals('1', $subIterator->key());
    $this->assertEquals($values['breakpoints'][1], $subIterator->current());
    $this->assertEquals(FALSE, $subIterator->hasChildren());
    $propertySchema = $subIterator->getPropertySchema();
    $this->assertEquals('string', $propertySchema->type);
    $this->assertEquals('Explicit sizes', $propertySchema->title);

    $iterator->rewind();
    $this->assertEquals('text', $iterator->key());
    $this->assertEquals($values['text'], $iterator->current());
    $this->assertEquals($schema->getProperties()->text, $iterator->getPropertySchema());

    $iterator->seek(2);
    $this->assertEquals('breakpoints', $iterator->key());
    $this->assertEquals($values['breakpoints'], $iterator->current());
    $this->assertEquals($schema->getProperties()->breakpoints, $iterator->getPropertySchema());
  }

  /**
   * @covers ::getSchema
   * @uses \Drupal\patternkit\Schema\SchemaIterator::__construct
   */
  public function testGetSchema() {
    $values = [
      'text' => 'test',
      'image' => [
        'image_url' => 'abc123',
      ],
      'breakpoints' => [
        'xs',
        'large',
      ],
    ];

    $schema = Schema::import(static::decodeJson($this->flatSchema));
    $iterator = new SchemaIterator($schema, $values);

    $this->assertEquals($schema, $iterator->getSchema());

    $subSchema = $schema->getProperties()->image;
    $iterator = new SchemaIterator($subSchema, $values['image']);

    $this->assertEquals($subSchema, $iterator->getSchema());
  }

  /**
   * @covers ::hasChildren
   * @uses \Drupal\patternkit\Schema\SchemaIterator::__construct
   * @uses \Drupal\patternkit\Schema\SchemaIterator::getPropertySchema
   * @uses \Drupal\patternkit\Schema\SchemaIterator::next
   */
  public function testHasChildren() {
    $values = [
      'text' => 'test',
      'image' => [
        'image_url' => 'abc123',
      ],
      'breakpoints' => [
        'xs',
        'large',
      ],
    ];

    $schema = Schema::import(static::decodeJson($this->flatSchema));
    $iterator = new SchemaIterator($schema, $values);

    $this->assertEquals('test', $iterator->current());
    $this->assertFalse($iterator->hasChildren());

    $iterator->next();
    $this->assertEquals($values['image'], $iterator->current());
    $this->assertTrue($iterator->hasChildren());

    $iterator->next();
    $this->assertEquals($values['breakpoints'], $iterator->current());
    $this->assertTrue($iterator->hasChildren());
  }

  /**
   * @covers ::getChildren
   * @covers ::__construct
   * @uses \Drupal\patternkit\Schema\SchemaIterator::getPropertySchema
   * @uses \Drupal\patternkit\Schema\SchemaIterator::next
   * @uses \Drupal\patternkit\Schema\SchemaIterator::getSchema
   * @uses \Drupal\patternkit\Schema\SchemaIterator::hasChildren()
   */
  public function testGetChildren() {
    $values = [
      'text' => 'test',
      'image' => [
        'image_url' => 'abc123',
      ],
      'breakpoints' => [
        'xs',
        'large',
      ],
    ];

    $schema = Schema::import(static::decodeJson($this->flatSchema));
    $iterator = new SchemaIterator($schema, $values);

    $this->assertEquals('test', $iterator->current());
    $this->assertFalse($iterator->hasChildren());
    $this->assertNull($iterator->getChildren());

    $iterator->next();
    $this->assertEquals($values['image'], $iterator->current());
    $childIterator = $iterator->getChildren();
    $this->assertInstanceOf(SchemaIterator::class, $childIterator);
    $this->assertNotEquals($iterator, $childIterator);
    $this->assertEquals($schema->getProperties()->image, $childIterator->getSchema());

    $iterator->next();
    $this->assertEquals($values['breakpoints'], $iterator->current());
    $this->assertTrue($iterator->hasChildren());
  }

  /**
   * @covers ::getPropertySchema
   * @covers ::<public>
   * @uses \Drupal\patternkit\Schema\SchemaIterator::__construct
   */
  public function testGetPropertySchema() {
    $schema_json = $this->loadFixture('schemas/composed_component.json');

    $values = (object) [
      'references_array' => [
        1,
        'two',
        (object) [
          "number" => "three",
        ],
      ],
      'ambiguous_object' => (object) [
        'text' => 'my text',
        'number' => 4,
      ],
    ];

    $schema = Schema::import(static::decodeJson($schema_json));
    $iterator = new SchemaIterator($schema, $values);

    $this->assertEquals('references_array', $iterator->key());
    $this->assertEquals($schema->getProperties()->references_array, $iterator->getPropertySchema());

    // Test iteration into array elements.
    $this->assertTrue($iterator->hasChildren());
    $this->assertInstanceOf(SchemaIterator::class, $subIterator = $iterator->getChildren());

    $this->assertEquals($schema->getProperties()->references_array, $subSchema = $subIterator->getSchema());

    $this->assertEquals($values->references_array[0], $subIterator->current());
    $this->assertEquals($subSchema->items->anyOf[1], $subIterator->getPropertySchema());

    $subIterator->next();
    $this->assertEquals($values->references_array[1], $subIterator->current());
    $this->assertEquals($subSchema->items->anyOf[0], $subIterator->getPropertySchema());

    $subIterator->next();
    $this->assertEquals($values->references_array[2], $subIterator->current());
    $this->assertEquals($subSchema->items->anyOf[2], $subIterator->getPropertySchema());

    // Move on to the next top-level element.
    $iterator->next();
    $this->assertEquals('ambiguous_object', $iterator->key());
    $this->assertEquals($schema->getProperties()->ambiguous_object, $iterator->getPropertySchema());
  }

}
