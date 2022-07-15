<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Test functionality for the Schema Factory service.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\SchemaFactory
 * @group patternkit
 */
class SchemaFactoryTest extends UnitTestCase {

  use JsonDecodeTrait;

  /**
   * A reference provider for loading schemas from.
   *
   * @var \Drupal\Tests\patternkit\Unit\Schema\TestPatternkitRefProvider
   */
  protected TestPatternkitRefProvider $refProvider;

  /**
   * The schema factory service being tested.
   *
   * @var \Drupal\patternkit\Schema\SchemaFactory
   */
  protected SchemaFactory $factory;

  /**
   * A testing schema with a single reference property.
   *
   * @var string
   */
  protected string $simpleRefSchemaJson = <<<JSON
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
  protected string $expandedRefSchemaJson = <<<JSON
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
  protected string $arrayRefSchemaJson = <<<JSON
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
  protected string $simpleTextSchemaJson = <<<JSON
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
  protected string $simpleNumberSchemaJson = <<<JSON
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
  protected string $simpleObjectSchemaJson = <<<JSON
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
  protected string $ambiguousObjectSchemaJson = <<<JSON
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
   * A testing schema with nested objects and value types through references.
   *
   * This schema provides a nested value object that could be left empty, but
   * if config values are mismanaged in PHP encoding, then an empty array may
   * be provided instead of an empty object.
   *
   * @var string
   */
  protected string $nestedObjectRefSchema = <<<JSON
    {
      "title": "Ambiguous property",
      "type": "object",
      "properties": {
        "nested_value": {
          "type": "object",
          "properties": {
            "optional_value": {
              "type": "string"
            },
            "ref_value": {
              "\$ref": "/api/patternkit/patternkit/refs/array_ref?asset=schema"
            }
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

    $this->refProvider = new TestPatternkitRefProvider();
    $this->factory = new SchemaFactory($this->refProvider);

    // Register simple schemas for references.
    $this->refProvider->registerPatternSchema('@patternkit/refs/text', $this->simpleTextSchemaJson);
    $this->refProvider->registerPatternSchema('@patternkit/refs/number', $this->simpleNumberSchemaJson);
    $this->refProvider->registerPatternSchema('@patternkit/refs/object', $this->simpleObjectSchemaJson);
    $this->refProvider->registerPatternSchema('@patternkit/refs/array_ref', $this->arrayRefSchemaJson);
  }

  /**
   * Test instantiation of schemas.
   *
   * @dataProvider providerSchemaCreation
   */
  public function testSchemaCreation(string $schema, $value) {
    $schema = $this->factory->createInstance($schema);

    // Decode the values for validation if a string was given.
    if (is_string($value)) {
      $value = $this->decodeJson($value);
    }
    $result = $schema->in($value, $this->factory->getDefaultContext());

    $this->assertNotNull($result);
  }

  /**
   * Data provider for testSchemaCreation.
   */
  public function providerSchemaCreation() {
    $cases = [];

    $cases['simple_object'] = [
      $this->simpleObjectSchemaJson,
      '{ "text": "my text" }',
    ];

    // Test validation with empty objects that may get cast as empty arrays.
    $ambiguous_object_config = '{"nested_value": {}}';
    $cases['ambiguous_object'] = [
      $this->ambiguousObjectSchemaJson,
      $this->decodeJson($ambiguous_object_config),
    ];
    $cases['ambiguous_object_associative'] = [
      $this->ambiguousObjectSchemaJson,
      $this->decodeJson($ambiguous_object_config, JSON_OBJECT_AS_ARRAY),
    ];

    // Test validation with empty objects through a reference.
    $ambiguous_ref_object_config = '{"object_reference": {} }';
    $cases['ambiguous_object_reference'] = [
      $this->expandedRefSchemaJson,
      $this->decodeJson($ambiguous_ref_object_config),
    ];
    $cases['ambiguous_object_reference_associative'] = [
      $this->expandedRefSchemaJson,
      $this->decodeJson($ambiguous_ref_object_config, JSON_OBJECT_AS_ARRAY),
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
      $this->expandedRefSchemaJson,
      $this->decodeJson($nested_ref_object_config),
    ];
    $cases['nested_object_reference_associative'] = [
      $this->expandedRefSchemaJson,
      $this->decodeJson($nested_ref_object_config, JSON_OBJECT_AS_ARRAY),
    ];

    return $cases;
  }

}
