<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\patternkit\Schema\PatternkitRefProvider;
use Drupal\patternkit\Schema\SchemaWalker;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;

/**
 * Test functionality for the Schema Walker iterator class.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\SchemaWalker
 * @covers \Drupal\patternkit\Schema\SchemaIterator
 * @uses \Drupal\patternkit\Schema\SchemaHelper
 * @group patternkit
 */
class SchemaWalkerTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * A mocked reference provider for loading schemas from.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\PatternkitRefProvider>
   */
  protected ObjectProphecy $refProvider;

  /**
   * A testing schema with a single reference property.
   *
   * @var string
   */
  protected static string $simpleRefSchemaJson = <<<JSON
    {
      "\$schema": "http://json-schema.org/draft-04/schema#",
      "category": "atom",
      "title": "Example reference property",
      "type": "object",
      "format": "grid",
      "properties": {
        "text_reference": {
          "\$ref": "/api/patternkit/patternkit/refs/text?asset=schema"
        }
      }
    }
    JSON;

  /**
   * A testing schema with multiple properties using a reference.
   *
   * @var string
   */
  protected static string $expandedRefSchemaJson = <<<JSON
    {
      "\$schema": "http://json-schema.org/draft-04/schema#",
      "category": "atom",
      "title": "Example reference property",
      "type": "object",
      "format": "grid",
      "properties": {
        "text_reference": {
          "\$ref": "/api/patternkit/patternkit/refs/text?asset=schema"
        },
        "number_reference": {
          "\$ref": "/api/patternkit/patternkit/refs/number?asset=schema"
        },
        "object_reference": {
          "\$ref": "/api/patternkit/patternkit/refs/object?asset=schema"
        },
        "reference_with_path": {
          "\$ref": "/api/patternkit/patternkit/refs/object?asset=schema#/properties/text"
        }
      }
    }
    JSON;

  /**
   * A testing schema with an array of anyOf references.
   *
   * @var string
   */
  protected static string $arrayRefSchemaJson = <<<JSON
    {
      "\$schema": "http://json-schema.org/draft-04/schema#",
      "category": "atom",
      "title": "Array Example With References",
      "type": "object",
      "properties": {
        "references": {
          "title": "Example reference items",
          "type": "array",
          "items": {
            "anyOf": [
              { "\$ref": "/api/patternkit/patternkit/refs/text?asset=schema" },
              { "\$ref": "/api/patternkit/patternkit/refs/number?asset=schema" },
              { "\$ref": "/api/patternkit/patternkit/refs/object?asset=schema" }
            ]
          }
        }
      }
    }
    JSON;

  /**
   * A testing schema with a basic text property.
   *
   * @var string
   */
  protected static string $simpleTextSchemaJson = <<<JSON
    {
      "title": "Text property",
      "type": "string"
    }
    JSON;

  /**
   * A testing schema with a basic number property.
   *
   * @var string
   */
  protected static string $simpleNumberSchemaJson = <<<JSON
    {
      "title": "Number property",
      "type": "number"
    }
    JSON;

  /**
   * A testing schema with a nested object property.
   *
   * @var string
   */
  protected static string $simpleObjectSchemaJson = <<<JSON
    {
      "title": "Object property",
      "type": "object",
      "properties": {
        "text": {
          "type": "string"
        }
      }
    }
    JSON;

  /**
   * Test iteration over a schema without references.
   *
   * @covers ::__construct
   */
  public function testBasicIteration(): void {
    $flat_schema_json = <<<JSON
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
                  "title": "",
                  "type": "string",
                  "enum": [
                    "",
                    "xxs",
                    "xs",
                    "sm",
                    "md",
                    "lg"
                  ]
                }
              ]
            }
          }
        }
      }
      JSON;

    $values = [
      'text' => 'test',
      'image' => [
        'image_url' => 'abc123',
      ],
      'deeper' => [
        'nested' => [
          'deepest' => '231abc',
        ],
      ],
      'breakpoints' => [
        'xs',
        'lg',
      ],
    ];

    // The sequence of expected items to be iterated over with
    // each item being the key and then the value for comparison.
    $expected_sequence = [
      ['text', $values['text']],
      ['image', $values['image']],
      ['image_url', $values['image']['image_url']],
      ['deeper', $values['deeper']],
      ['breakpoints', $values['breakpoints']],
      ['0', $values['breakpoints'][0]],
      ['1', $values['breakpoints'][1]],
    ];

    $schema = Schema::import(json_decode($flat_schema_json));

    $walker = new SchemaWalker($schema, $values);

    $expectation_index = 0;
    foreach ($walker as $key => $value) {
      [$expected_key, $expected_value] = $expected_sequence[$expectation_index];

      $this->assertEquals($expected_key, $key);
      $this->assertEquals($expected_value, $value);

      $expectation_index++;
    }
  }

  /**
   * Test iteration with referenced schemas.
   *
   * @covers ::__construct
   * @uses \Swaggest\JsonSchema\Context
   * @uses \Swaggest\JsonSchema\Schema
   */
  public function testIteratingReferences(): void {
    $this->refProvider = $this->prophesize(PatternkitRefProvider::class);

    // Register simple schemas for references.
    $this->refProvider->getSchemaData('/api/patternkit/patternkit/refs/text?asset=schema')
      ->willReturn(static::decodeJson(static::$simpleTextSchemaJson));
    $this->refProvider->getSchemaData('/api/patternkit/patternkit/refs/number?asset=schema')
      ->willReturn(static::decodeJson(static::$simpleNumberSchemaJson));
    $this->refProvider->getSchemaData('/api/patternkit/patternkit/refs/object?asset=schema')
      ->willReturn(static::decodeJson(static::$simpleObjectSchemaJson));

    $values = [
      'text_reference' => $this->getRandomGenerator()->string(),
      'number_reference' => rand(),
      'object_reference' => [
        'text' => $this->getRandomGenerator()->string(),
      ],
      'reference_with_path' => $this->getRandomGenerator()->string(),
    ];

    // The sequence of expected items to be iterated over with
    // each item being the key and then the value for comparison.
    $expected_sequence = [
      ['text_reference', $values['text_reference']],
      ['number_reference', $values['number_reference']],
      ['object_reference', $values['object_reference']],
      ['text', $values['object_reference']['text']],
      ['reference_with_path', $values['reference_with_path']],
    ];

    // Prepare a context using the test reference provider.
    $context = new Context($this->refProvider->reveal());
    $schema = Schema::import(json_decode(static::$expandedRefSchemaJson), $context);
    $walker = new SchemaWalker($schema, $values);

    $expectation_index = 0;
    foreach ($walker as $key => $value) {
      [$expected_key, $expected_value] = $expected_sequence[$expectation_index];

      $this->assertEquals($expected_key, $key);
      $this->assertEquals($expected_value, $value);

      $expectation_index++;
    }
  }

}
