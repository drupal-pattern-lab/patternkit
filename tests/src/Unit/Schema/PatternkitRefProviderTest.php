<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\patternkit\Asset\LibraryNamespaceResolverInterface;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Schema\PatternkitRefProvider;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Test the PatternkitRefProvider class.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\PatternkitRefProvider
 * @uses \Drupal\patternkit\Schema\PatternkitRefProvider::__construct()
 * @group patternkit
 */
class PatternkitRefProviderTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * The reference provider being tested.
   *
   * @var \Drupal\patternkit\Schema\PatternkitRefProvider
   */
  protected PatternkitRefProvider $provider;

  /**
   * The mocked pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $patternDiscovery;

  /**
   * The mocked entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * Mocked entity storage for the patternkit_pattern entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $patternStorage;

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

    // Mock dependencies.
    $this->patternDiscovery = $this->prophesize(PatternDiscoveryInterface::class);
    $this->patternStorage = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    // Prepare pattern definitions for test usage.
    $this->patternDiscovery->getPatternDefinition('@patternkit/refs/text')
      ->willReturn([
        'pattern_id' => '@patternkit/refs/text',
        'schema' => $this->simpleTextSchemaJson,
      ]);
    $this->patternDiscovery->getPatternDefinition('@patternkit/refs/number')
      ->willReturn([
        'pattern_id' => '@patternkit/refs/number',
        'schema' => $this->simpleNumberSchemaJson,
      ]);
    $this->patternDiscovery->getPatternDefinition('@patternkit/refs/object')
      ->willReturn([
        'pattern_id' => '@patternkit/refs/object',
        'schema' => $this->simpleObjectSchemaJson,
      ]);
    $this->patternDiscovery->getPatternDefinition('@patternkit/refs/invalid')
      ->willReturn([
        'pattern_id' => '@patternkit/refs/invalid',
        'schema' => '[ Some invalid JSON }',
      ]);
    $this->patternDiscovery->getPatternDefinition('@patternkit/refs/undefined')
      ->willReturn(NULL);

    // Prepare pattern creation to produce mocked pattern entities.
    $this->mockPatternCreation();

    // Instantiate the provider for default usage. Tests that need more mocked
    // customization may configure the mocks and re-run this method.
    $this->provider = $this->getRefProvider();
  }

  /**
   * Get the reference provider instance with mock dependencies injected.
   *
   * @return \Drupal\patternkit\Schema\PatternkitRefProvider
   *   The reference provider instance with mock dependencies injected.
   */
  protected function getRefProvider(): PatternkitRefProvider {
    // @todo Remove unused LibraryNamespaceResolver dependency.
    $libraryNamespaceResolver = $this->prophesize(LibraryNamespaceResolverInterface::class);

    // Wait to mock this method to ensure the patternStorage service was
    // prepared ahead of time.
    $this->entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    return new PatternkitRefProvider(
      $this->patternDiscovery->reveal(),
      $libraryNamespaceResolver->reveal(),
      $this->entityTypeManager->reveal(),
    );
  }

  /**
   * A helper function to mock pattern creation using pattern storage.
   *
   * This prepares the create() method on the pattern entity storage mock to
   * accept an associative array containing the following keys and return a
   * mocked Pattern entity with the data for return in related getter methods.
   * - pattern_id: The namespaced pattern ID.
   * - schema: A JSON string value to be returned from getSchema().
   */
  protected function mockPatternCreation(): void {
    $pattern = $this->prophesize(PatternInterface::class);

    $this->patternStorage->create(
      Argument::allOf(
        Argument::withKey('pattern_id'),
        Argument::withKey('schema'),
      )
    )->will(
      function ($args) use ($pattern) {
        // Customize the pattern-specific prophecy.
        $pattern->getSchema()->willReturn($args[0]['schema']);

        // Reveal and return the mocked pattern.
        return $pattern->reveal();
      }
    );
  }

  /**
   * @covers ::getSchemaData
   * @covers ::__construct
   * @covers ::patternStorage
   * @covers ::getPatternSchema
   * @uses \Drupal\patternkit\Schema\PatternkitRefProvider::parseNamespacedSchemaReference()
   * @uses \Drupal\patternkit\Schema\PatternkitRefProvider::parseNormalizedSchemaReference()
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
      $this->decodeJson($this->simpleTextSchemaJson),
    ];
    $cases['simple_text_schema_namespaced'] = [
      '@patternkit/refs/text',
      $this->decodeJson($this->simpleTextSchemaJson),
    ];
    $cases['simple_number_schema'] = [
      '/api/patternkit/patternkit/refs/number?asset=schema',
      $this->decodeJson($this->simpleNumberSchemaJson),
    ];
    $cases['simple_number_schema_namespaced'] = [
      '@patternkit/refs/number',
      $this->decodeJson($this->simpleNumberSchemaJson),
    ];
    $cases['simple_object_schema'] = [
      '/api/patternkit/patternkit/refs/object?asset=schema',
      $this->decodeJson($this->simpleObjectSchemaJson),
    ];
    $cases['simple_object_schema_namespaced'] = [
      '@patternkit/refs/object',
      $this->decodeJson($this->simpleObjectSchemaJson),
    ];

    // Test loading a schema with a local path defined.
    // Expected behavior is that the local path is ignored at this point.
    $cases['object_with_path'] = [
      '/api/patternkit/patternkit/refs/object?asset=schema#/properties/text',
      $this->decodeJson($this->simpleObjectSchemaJson),
    ];
    $cases['object_with_path_namespaced'] = [
      '@patternkit/refs/object#/properties/text',
      $this->decodeJson($this->simpleObjectSchemaJson),
    ];
    $cases['object_with_invalid_path'] = [
      '/api/patternkit/patternkit/refs/object?asset=schema#/properties/undefined_property',
      $this->decodeJson($this->simpleObjectSchemaJson),
    ];
    $cases['object_with_invalid_path_namespaced'] = [
      '@patternkit/refs/object#/properties/undefined_property',
      $this->decodeJson($this->simpleObjectSchemaJson),
    ];

    // Test loading an unregistered pattern.
    $cases['unregistered_patternkit_pattern'] = [
      '/api/patternkit/patternkit/refs/undefined?asset=schema',
      FALSE,
    ];
    $cases['unregistered_patternkit_pattern_namespaced'] = [
      '@patternkit/refs/undefined',
      FALSE,
    ];
    $cases['unregistered_external_pattern'] = [
      '/external/refs/undefined',
      FALSE,
    ];

    // Test loading a schema with invalid JSON content.
    $cases['invalid_patternkit_pattern'] = [
      '/api/patternkit/patternkit/refs/invalid?asset=schema',
      FALSE,
    ];
    $cases['invalid_patternkit_pattern_namespaced'] = [
      '@patternkit/refs/invalid',
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

    $ref = '@unknown#/properties/text';
    $cases['invalid_reference'] = [$ref, FALSE];

    return $cases;
  }

}
