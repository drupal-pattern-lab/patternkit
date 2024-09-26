<?php

namespace Drupal\Tests\patternkit\Unit\Schema\DataPreProcessor;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\RemoteRef\Preloaded;
use Swaggest\JsonSchema\Schema;

/**
 * Unit tests for the ObjectCoercionDataPreProcessor.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
 * @uses \Swaggest\JsonSchema\Schema
 * @uses \Swaggest\JsonSchema\Context
 * @group patternkit
 */
class ObjectCoercionDataPreProcessorTest extends UnitTestCase {

  use JsonDecodeTrait;

  /**
   * The ObjectCoercionDataPreProcessor instance being tested.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
   */
  protected ObjectCoercionDataPreProcessor $dataPreProcessor;

  /**
   * A prepared schema context including the observer as a data preprocessor.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $context;

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
   * A testing schema with an ambiguous object property.
   *
   * This schema provides a nested value object that could be left empty, but
   * if config values are mismanaged in PHP encoding, then an empty array may
   * be provided instead of an empty object.
   *
   * @var string
   */
  protected static string $ambiguousObjectSchemaJson = <<<JSON
    {
      "title": "Ambiguous property",
      "type": "object",
      "properties": {
        "nested_value": {
          "type": "object",
          "properties": {
            "optional_value": {
              "type": "string"
            }
          }
        }
      }
    }
    JSON;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Register simple schemas for references.
    $refProvider = new Preloaded();
    $refProvider->setSchemaData('/api/patternkit/patternkit/refs/text?asset=schema', static::decodeJson(static::$simpleTextSchemaJson));
    $refProvider->setSchemaData('/api/patternkit/patternkit/refs/number?asset=schema', static::decodeJson(static::$simpleNumberSchemaJson));
    $refProvider->setSchemaData('/api/patternkit/patternkit/refs/object?asset=schema', static::decodeJson(static::$simpleObjectSchemaJson));
    $refProvider->setSchemaData('/api/patternkit/patternkit/refs/array_ref?asset=schema', static::decodeJson(static::$arrayRefSchemaJson));

    $this->dataPreProcessor = new ObjectCoercionDataPreProcessor();
    $this->context = new Context($refProvider);
    $this->context->setDataPreProcessor($this->dataPreProcessor);
  }

  /**
   * @covers ::process
   * @dataProvider providerProcess
   */
  public function testProcess($data, Schema $schema, $expected) {
    $result = $this->dataPreProcessor->process($data, $schema);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testProcess().
   */
  public static function providerProcess(): array {
    $cases = [];

    $objectSchemaJson = <<<JSON
      {
        "type": "object",
        "properties": {
          "text": {
            "type": "string"
          }
        }
      }
      JSON;
    $objectSchema = Schema::import(static::decodeJson($objectSchemaJson));
    $cases[] = [
      (object) [
        'text' => 'my text',
      ],
      $objectSchema,
      (object) [
        'text' => 'my text',
      ],
    ];

    $cases[] = [
      [
        'text' => 'my text',
      ],
      $objectSchema,
      (object) [
        'text' => 'my text',
      ],
    ];

    $arraySchemaJson = <<<JSON
      {
        "type": "array",
        "items": {
          "text": {
            "type": "string"
          }
        }
      }
      JSON;
    $arraySchema = Schema::import(static::decodeJson($arraySchemaJson));
    $cases[] = [
      (object) [
        'text' => 'my text',
      ],
      $arraySchema,
      (object) [
        'text' => 'my text',
      ],
    ];

    $cases[] = [
      [
        'value1',
        'value2',
      ],
      $arraySchema,
      [
        'value1',
        'value2',
      ],
    ];

    return $cases;
  }

  /**
   * @covers ::process
   * @dataProvider providerSchemaValidation
   */
  public function testSchemaValidation(string $schema, $value) {
    $schema = Schema::import(static::decodeJson($schema), $this->context);

    // Decode the values for validation if a string was given.
    if (is_string($value)) {
      $value = static::decodeJson($value);
    }
    $result = $schema->out($value, $this->context);

    $this->assertNotNull($result);
  }

  /**
   * Data provider for testProcess().
   */
  public static function providerSchemaValidation(): array {
    $cases = [];

    $cases['simple_object'] = [
      static::$simpleObjectSchemaJson,
      '{ "text": "my text" }',
    ];

    // Test validation with empty objects that may get cast as empty arrays.
    $ambiguous_object_config = '{"nested_value": {}}';
    $cases['ambiguous_object'] = [
      static::$ambiguousObjectSchemaJson,
      static::decodeJson($ambiguous_object_config),
    ];
    $cases['ambiguous_object_associative'] = [
      static::$ambiguousObjectSchemaJson,
      static::decodeJson($ambiguous_object_config, JSON_OBJECT_AS_ARRAY),
    ];

    // Test validation with empty objects through a reference.
    $ambiguous_ref_object_config = '{"object_reference": {} }';
    $cases['ambiguous_object_reference'] = [
      static::$expandedRefSchemaJson,
      static::decodeJson($ambiguous_ref_object_config),
    ];
    $cases['ambiguous_object_reference_associative'] = [
      static::$expandedRefSchemaJson,
      static::decodeJson($ambiguous_ref_object_config, JSON_OBJECT_AS_ARRAY),
    ];

    $nested_ref_object_config = <<<JSON
      {
        "nested_value": {
          "optional_value": "My text"
        },
        "ref_value": {
          "references": [
            "my text value",
            {},
            {
              "text": "another text value"
            }
          ]
        }
      }
      JSON;
    $cases['nested_object_reference'] = [
      static::$expandedRefSchemaJson,
      static::decodeJson($nested_ref_object_config),
    ];
    $cases['nested_object_reference_associative'] = [
      static::$expandedRefSchemaJson,
      static::decodeJson($nested_ref_object_config, JSON_OBJECT_AS_ARRAY),
    ];

    return $cases;
  }

}
