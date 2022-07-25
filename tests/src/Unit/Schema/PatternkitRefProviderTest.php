<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\patternkit\Schema\PatternkitRefProvider;
use Drupal\Tests\UnitTestCase;

/**
 * Test the PatternkitRefProvider class.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\PatternkitRefProvider
 * @group patternkit
 */
class PatternkitRefProviderTest extends UnitTestCase {

  /**
   * The reference provider being tested.
   *
   * @var \Drupal\patternkit\Schema\PatternkitRefProvider
   */
  protected PatternkitRefProvider $provider;

  /**
   * A testing schema with a property using a reference.
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
  "\$schema": "http://json-schema.org/draft-04/schema#",
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
  "\$schema": "http://json-schema.org/draft-04/schema#",
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
  "\$schema": "http://json-schema.org/draft-04/schema#",
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
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->provider = new TestPatternkitRefProvider();

    // Register simple schemas for references.
    $this->provider->registerPatternSchema('@patternkit/refs/text', $this->simpleTextSchemaJson);
    $this->provider->registerPatternSchema('@patternkit/refs/number', $this->simpleNumberSchemaJson);
    $this->provider->registerPatternSchema('@patternkit/refs/object', $this->simpleObjectSchemaJson);
  }

  /**
   * @covers ::getSchemaData
   * @dataProvider providerGetSchemaData
   */
  public function testGetSchemaData(string $url, $expected) {
    $this->assertEquals($expected, $this->provider->getSchemaData($url));
  }

  /**
   * Data provider for ::testGetSchemaData.
   */
  public function providerGetSchemaData(): array {
    $cases = [];

    // Test loading simple schemas directly.
    $cases['simple_text_schema'] = [
      '/api/patternkit/patternkit/refs/text?asset=schema',
      json_decode($this->simpleTextSchemaJson),
    ];
    $cases['simple_number_schema'] = [
      '/api/patternkit/patternkit/refs/number?asset=schema',
      json_decode($this->simpleNumberSchemaJson),
    ];
    $cases['simple_object_schema'] = [
      '/api/patternkit/patternkit/refs/object?asset=schema',
      json_decode($this->simpleObjectSchemaJson),
    ];

    // Test loading a schema with a local path defined.
    // Expected behavior is that the local path is ignored at this point.
    $cases['object_with_path'] = [
      '/api/patternkit/patternkit/refs/object?asset=schema#/properties/text',
      json_decode($this->simpleObjectSchemaJson),
    ];
    $cases['object_with_invalid_path'] = [
      '/api/patternkit/patternkit/refs/object?asset=schema#/properties/undefined_property',
      json_decode($this->simpleObjectSchemaJson),
    ];

    // Test loading an unregistered pattern.
    $cases['unregistered_patternkit_pattern'] = [
      '/api/patternkit/patternkit/refs/undefined?asset=schema',
      FALSE,
    ];
    $cases['unregistered_external_pattern'] = [
      '/external/refs/undefined',
      FALSE,
    ];

    return $cases;
  }

  /**
   * Test parsing of normalized schema references.
   *
   * @covers ::parseNormalizedSchemaReference
   * @dataProvider providerParseNormalizedSchemaReference
   */
  public function testParseNormalizedSchemaReference(string $ref, $expected) {
    $actual = $this->provider->parseNormalizedSchemaReference($ref);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testParseNormalizedSchemaReference.
   */
  public function providerParseNormalizedSchemaReference(): array {
    $cases = [];

    $normalized = '/api/patternkit/patternkit/atoms/example/src/example?asset=schema';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'path' => '',
    ];
    $cases['example'] = [$normalized, $expected];

    $normalized = '/api/patternkit/patternkit/atoms/example_filtered/src/example_filtered?asset=schema';
    $expected = [
      'asset_id' => '@patternkit/atoms/example_filtered/src/example_filtered',
      'path' => '',
    ];
    $cases['example_filtered'] = [$normalized, $expected];

    $normalized = '/api/patternkit/patternkit/atoms/example_ref/src/example_ref?asset=schema';
    $expected = [
      'asset_id' => '@patternkit/atoms/example_ref/src/example_ref',
      'path' => '',
    ];
    $cases['example_ref'] = [$normalized, $expected];

    $normalized = '/api/patternkit/patternkit/atoms/example/src/example?asset=schema#/properties/text';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'path' => '/properties/text',
    ];
    $cases['property_path'] = [$normalized, $expected];

    $normalized = '/invalid/path';
    $cases['invalid_reference'] = [$normalized, FALSE];

    return $cases;
  }

  /**
   * Test parsing of namespaced schema references.
   *
   * @covers ::parseNamespacedSchemaReference
   * @dataProvider providerParseNamespacedSchemaReference
   */
  public function testParseNamespacedSchemaReference(string $ref, $expected) {
    $actual = $this->provider->parseNamespacedSchemaReference($ref);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testParseNamespacedSchemaReference.
   */
  public function providerParseNamespacedSchemaReference(): array {
    $cases = [];

    $ref = '@patternkit/atoms/example/src/example';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'path' => '',
    ];
    $cases['example'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_filtered/src/example_filtered';
    $expected = [
      'asset_id' => '@patternkit/atoms/example_filtered/src/example_filtered',
      'path' => '',
    ];
    $cases['example_filtered'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_ref/src/example_ref';
    $expected = [
      'asset_id' => '@patternkit/atoms/example_ref/src/example_ref',
      'path' => '',
    ];
    $cases['example_ref'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example/src/example#/properties/text';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'path' => '/properties/text',
    ];
    $cases['property_path'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_ref/../example/src/example.json';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'path' => '',
    ];
    $cases['relative_ref'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_ref/../example/src/example.json#/properties/text';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'path' => '/properties/text',
    ];
    $cases['relative_ref_with_path'] = [$ref, $expected];

    return $cases;
  }

}
