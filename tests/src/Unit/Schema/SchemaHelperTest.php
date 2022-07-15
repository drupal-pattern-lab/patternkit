<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\patternkit\Schema\SchemaHelper;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\patternkit\Traits\SchemaHelperTestTrait;
use Drupal\Tests\UnitTestCase;
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
  use SchemaHelperTestTrait;

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

    $container = new ContainerBuilder();
    $this->setUpSchemaFactory($container);
    \Drupal::setContainer($container);
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
  }

  /**
   * @covers ::getCompositionSchema
   * @covers ::isCompositionSchema
   */
  public function testGetCompositionSchemaWithDataCasting() {
    $schema = Schema::import($this->decodeJson($this->flatSchema));

    $propertySchema = $schema->getProperties()->nested_items->items;
    $this->assertTrue(SchemaHelper::isCompositionSchema($propertySchema), 'The property schema was not recognized as a composition schema.');

    // A simple string value should validate and return the expected schema.
    $result = SchemaHelper::getCompositionSchema($propertySchema, 'My test string');
    $this->assertEquals($propertySchema->anyOf[1], $result, 'The expected composition rule was not identified.');

    // It should successfully validate when provided with an object.
    $result = SchemaHelper::getCompositionSchema($propertySchema, $this->decodeJson('{ }'));
    $this->assertEquals($propertySchema->anyOf[0], $result, 'The expected composition rule was not identified.');

    // If flexible casting is not supported this will fail validation when
    // provided with an object cast as an array.
    $result = SchemaHelper::getCompositionSchema($propertySchema, $this->decodeJson('{ }', JSON_OBJECT_AS_ARRAY));
    $this->assertEquals($propertySchema->anyOf[0], $result, 'The expected composition rule was not identified.');
  }

}
