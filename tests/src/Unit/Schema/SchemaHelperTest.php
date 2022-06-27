<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\patternkit\Schema\SchemaHelper;
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
    }
  }
}
JSON;

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
    $schema = Schema::import(json_decode($this->flatSchema));

    $cases = [];

    $cases['top_object'] = [$schema, FALSE];
    $cases['string_property'] = [$schema->getProperties()->text, FALSE];
    $cases['formatted_text'] = [$schema->getProperties()->formatted_text, FALSE];
    $cases['object_property'] = [$schema->getProperties()->image, FALSE];
    $cases['array_property'] = [$schema->getProperties()->breakpoints, FALSE];
    $cases['array_items'] = [$schema->getProperties()->breakpoints->items, TRUE];

    return $cases;
  }

  /**
   * @covers ::getCompositionSchema
   */
  public function testGetCompositionSchema() {
    $schema = Schema::import(json_decode($this->flatSchema));
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

}
