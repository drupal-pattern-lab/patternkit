<?php

namespace Drupal\Tests\patternkit\UnitSchema;

use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Test the SchemaRefHandler class.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\SchemaRefHandler
 * @group patternkit
 */
class SchemaRefHandlerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->schemaRefHandler = new SchemaRefHandler();
  }

  /**
   * Test parsing of normalized schema references.
   *
   * @covers ::parseNormalizedSchemaReference
   * @uses \Drupal\patternkit\Schema\SchemaRefHandler::__construct
   * @dataProvider providerParseNormalizedSchemaReference
   */
  public function testParseNormalizedSchemaReference(string $ref, $expected) {
    $actual = $this->schemaRefHandler->parseNormalizedSchemaReference($ref);

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
      'namespace' => '@patternkit',
      'path' => 'atoms/example/src/example',
      'pointer' => '',
    ];
    $cases['example'] = [$normalized, $expected];

    $normalized = '/api/patternkit/patternkit/atoms/example_filtered/src/example_filtered?asset=schema';
    $expected = [
      'asset_id' => '@patternkit/atoms/example_filtered/src/example_filtered',
      'namespace' => '@patternkit',
      'path' => 'atoms/example_filtered/src/example_filtered',
      'pointer' => '',
    ];
    $cases['example_filtered'] = [$normalized, $expected];

    $normalized = '/api/patternkit/patternkit/atoms/example_ref/src/example_ref?asset=schema';
    $expected = [
      'asset_id' => '@patternkit/atoms/example_ref/src/example_ref',
      'namespace' => '@patternkit',
      'path' => 'atoms/example_ref/src/example_ref',
      'pointer' => '',
    ];
    $cases['example_ref'] = [$normalized, $expected];

    $normalized = '/api/patternkit/patternkit/atoms/example/src/example?asset=schema#/properties/text';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'namespace' => '@patternkit',
      'path' => 'atoms/example/src/example',
      'pointer' => '/properties/text',
    ];
    $cases['property_pointer'] = [$normalized, $expected];

    $normalized = '/api/patternkit/patternkit/atoms/example_ref/src/example_ref.json?asset=schema';
    $expected = [
      'asset_id' => '@patternkit/atoms/example_ref/src/example_ref',
      'namespace' => '@patternkit',
      'path' => 'atoms/example_ref/src/example_ref',
      'pointer' => '',
    ];
    $cases['json_extension_without_pointer'] = [$normalized, $expected];

    $normalized = '/api/patternkit/patternkit/atoms/example/src/example.json?asset=schema#/properties/text';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'namespace' => '@patternkit',
      'path' => 'atoms/example/src/example',
      'pointer' => '/properties/text',
    ];
    $cases['json_extension_with_pointer'] = [$normalized, $expected];

    $normalized = '/invalid/path';
    $cases['invalid_reference'] = [$normalized, FALSE];

    return $cases;
  }

  /**
   * Test parsing of namespaced schema references.
   *
   * @covers ::parseNamespacedSchemaReference
   * @uses \Drupal\patternkit\Schema\SchemaRefHandler::__construct
   * @dataProvider providerParseNamespacedSchemaReference
   */
  public function testParseNamespacedSchemaReference(string $ref, $expected) {
    $actual = $this->schemaRefHandler->parseNamespacedSchemaReference($ref);

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
      'namespace' => '@patternkit',
      'path' => 'atoms/example/src/example',
      'pointer' => '',
    ];
    $cases['example'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_filtered/src/example_filtered';
    $expected = [
      'asset_id' => '@patternkit/atoms/example_filtered/src/example_filtered',
      'namespace' => '@patternkit',
      'path' => 'atoms/example_filtered/src/example_filtered',
      'pointer' => '',
    ];
    $cases['example_filtered'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_ref/src/example_ref';
    $expected = [
      'asset_id' => '@patternkit/atoms/example_ref/src/example_ref',
      'namespace' => '@patternkit',
      'path' => 'atoms/example_ref/src/example_ref',
      'pointer' => '',
    ];
    $cases['example_ref'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example/src/example#/properties/text';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'namespace' => '@patternkit',
      'path' => 'atoms/example/src/example',
      'pointer' => '/properties/text',
    ];
    $cases['property_path'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_ref/../example/src/example.json';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'namespace' => '@patternkit',
      'path' => 'atoms/example/src/example',
      'pointer' => '',
    ];
    $cases['relative_ref'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_ref/../example/src/example.json#/properties/text';
    $expected = [
      'asset_id' => '@patternkit/atoms/example/src/example',
      'namespace' => '@patternkit',
      'path' => 'atoms/example/src/example',
      'pointer' => '/properties/text',
    ];
    $cases['relative_ref_with_path'] = [$ref, $expected];

    $ref = '@patternkit#/properties/text';
    $expected = FALSE;
    $cases['match_failure'] = [$ref, $expected];

    return $cases;
  }

  /**
   * Test normalization of namespaced schema references.
   *
   * @covers ::normalizeNamespacedSchemaReference
   * @uses \Drupal\patternkit\Schema\SchemaRefHandler::__construct
   * @uses \Drupal\patternkit\Schema\SchemaRefHandler::parseNamespacedSchemaReference()
   * @dataProvider providerNormalizeNamespacedSchemaReference
   */
  public function testNormalizeNamespacedSchemaReference(string $ref, $expected) {
    $actual = $this->schemaRefHandler->normalizeNamespacedSchemaReference($ref);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testNormalizeNamespacedSchemaReference.
   */
  public function providerNormalizeNamespacedSchemaReference(): array {
    $cases = [];

    $ref = '@patternkit/atoms/example/src/example';
    $expected = '/api/patternkit/patternkit/atoms/example/src/example?asset=schema';
    $cases['example'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_filtered/src/example_filtered';
    $expected = '/api/patternkit/patternkit/atoms/example_filtered/src/example_filtered?asset=schema';
    $cases['example_filtered'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_ref/src/example_ref';
    $expected = '/api/patternkit/patternkit/atoms/example_ref/src/example_ref?asset=schema';
    $cases['example_ref'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example/src/example#/properties/text';
    $expected = '/api/patternkit/patternkit/atoms/example/src/example?asset=schema#/properties/text';
    $cases['property_path'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_ref/../example/src/example.json';
    $expected = '/api/patternkit/patternkit/atoms/example/src/example?asset=schema';
    $cases['relative_ref'] = [$ref, $expected];

    $ref = '@patternkit/atoms/example_ref/../example/src/example.json#/properties/text';
    $expected = '/api/patternkit/patternkit/atoms/example/src/example?asset=schema#/properties/text';
    $cases['relative_ref_with_path'] = [$ref, $expected];

    $ref = '@patternkit#/properties/text';
    $expected = FALSE;
    $cases['match_failure'] = [$ref, $expected];

    return $cases;
  }

}
