<?php

namespace Drupal\Tests\patternkit\Unit\Schema\DataPreProcessor;

use Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver;
use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\patternkit\Schema\UnresolvedSchema;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;

/**
 * Unit tests for the SchemaRefObserver data preprocessor.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver
 * @uses \Drupal\patternkit\Schema\UnresolvedSchema
 * @uses \Swaggest\JsonSchema\Schema
 * @uses \Swaggest\JsonSchema\Context
 * @group patternkit
 */
class SchemaRefObserverTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * A JSON schema for testing reference resolution.
   *
   * @var string
   */
  protected string $schemaWithReferenceJson = <<<JSON
    {
      "\$schema": "http://json-schema.org/draft-04/schema#",
      "category": "test",
      "title": "Schema with reference",
      "type": "object",
      "properties": {
        "reference_property": {
          "\$ref": "@my/example/reference"
        }
      }
    }
    JSON;

  /**
   * The SchemaRefObserver instance being tested.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver
   */
  protected SchemaRefObserver $observer;

  /**
   * A prepared schema context including the observer as a data preprocessor.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $context;

  /**
   * The mocked schema handler service.
   *
   * @var \Drupal\patternkit\Schema\SchemaRefHandler|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $schemaRefHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->schemaRefHandler = $this->prophesize(SchemaRefHandler::class);

    $this->observer = new SchemaRefObserver($this->schemaRefHandler->reveal());
    $this->context = new Context();
    $this->context->setDataPreProcessor($this->observer);
  }

  /**
   * Test reference discovery in given JSON schemas.
   *
   * @covers ::process
   * @covers ::getReferences
   * @covers ::__construct
   * @dataProvider providerGetReferences
   */
  public function testGetReferences(string $schema_json, array $expected_references) {
    $schema_data = $this->decodeJson($schema_json);
    UnresolvedSchema::import($schema_data, $this->context);

    $references = $this->observer->getReferences();

    // Confirm all URLs were discovered.
    $this->assertEquals(array_keys($expected_references), array_keys($references));

    // Compare path expectations.
    $this->assertEquals($expected_references, $references);
  }

  /**
   * Data provider for testGetReferences.
   */
  public function providerGetReferences() {
    $cases = [];

    $cases['one reference'] = [
      $this->schemaWithReferenceJson,
      [
        '@my/example/reference' => [NULL],
      ],
    ];

    $example_lib_path = __DIR__ . '/../../../../modules/patternkit_test/lib/';

    $cases['@patternkit/atoms/example/src/example'] = [
      file_get_contents($example_lib_path . 'patternkit/src/atoms/example/src/example.json'),
      [],
    ];

    $cases['@patternkit/atoms/example_filtered/src/example_filtered'] = [
      file_get_contents($example_lib_path . 'patternkit/src/atoms/example_filtered/src/example_filtered.json'),
      [],
    ];

    $cases['@patternkit/atoms/example_ref/src/example_ref'] = [
      file_get_contents($example_lib_path . 'patternkit/src/atoms/example_ref/src/example_ref.json'),
      [
        '@patternkit/atoms/example_ref/../example/src/example.json#/properties/text' => [NULL],
        '@patternkit/atoms/example_ref/../example/src/example.json' => [NULL],
      ],
    ];

    return $cases;
  }

  /**
   * Test discovery of references in an array of anyOf references.
   *
   * @covers ::process
   * @covers ::getReferences
   * @covers ::getAssetFromReference
   * @covers ::__construct
   * @uses \Drupal\patternkit\Schema\UnresolvedSchema
   *
   * @todo Test with pointers when they are resolved.
   */
  public function testGetReferencesAnyOf() {
    $schema_json = <<<JSON
      {
        "type": "array",
        "items": {
          "anyOf": [
            {
              "\$ref": "@my/namespaced/reference"
            },
            {
              "\$ref": "@my/namespaced/reference#/with/path"
            },
            {
              "\$ref": "/api/patternkit/my/normalized/reference"
            },
            {
              "\$ref": "/api/patternkit/my/normalized/reference#/with/path"
            },
            {
              "\$ref": "/api/patternkit/my/invalid/reference"
            },
            {
              "\$ref": "/api/patternkit/my/invalid/reference#/with/path"
            },
            {
              "\$ref": "#/my/internal/reference"
            },
            {
              "\$ref": "https://my/external/reference"
            }
          ]
        }
      }
      JSON;

    // Add handlers for various references included in JSON content.
    $this->schemaRefHandler->parseNamespacedSchemaReference(
      Argument::containingString('@my/namespaced/reference'))
      ->willReturn(['asset_id' => '@my/namespaced/reference']);
    $this->schemaRefHandler->parseNamespacedSchemaReference(
      Argument::containingString('@my/invalid/reference'))
      ->willReturn(FALSE);
    $this->schemaRefHandler->parseNormalizedSchemaReference(
      Argument::containingString('/api/patternkit/my/normalized/reference'))
      ->willReturn(['asset_id' => '@my/normalized/reference']);
    $this->schemaRefHandler->parseNormalizedSchemaReference(
      Argument::containingString('/api/patternkit/my/invalid/reference'))
      ->willReturn(FALSE);

    // Prepare the context with the newly configured schema handler mock.
    $this->observer = new SchemaRefObserver($this->schemaRefHandler->reveal());
    $this->context->setDataPreProcessor($this->observer);

    // Run the import to collect observed references for testing.
    $schema_data = $this->decodeJson($schema_json);
    UnresolvedSchema::import($schema_data, $this->context);

    $this->assertEquals([
      '@my/namespaced/reference' => [
        // '#->properties:items->anyOf[0]->properties:anyOf->items[0]:0',
        NULL,
      ],
      '@my/namespaced/reference#/with/path' => [
        // '#->properties:items->anyOf[0]->properties:anyOf->items[1]:1',
        NULL,
      ],
      '/api/patternkit/my/normalized/reference' => [
        // '#->properties:items->anyOf[0]->properties:anyOf->items[2]:2',
        NULL,
      ],
      '/api/patternkit/my/normalized/reference#/with/path' => [
        // '#->properties:items->anyOf[0]->properties:anyOf->items[3]:3',
        NULL,
      ],
      '/api/patternkit/my/invalid/reference' => [
        // '#->properties:items->anyOf[0]->properties:anyOf->items[4]:4',
        NULL,
      ],
      '/api/patternkit/my/invalid/reference#/with/path' => [
        // '#->properties:items->anyOf[0]->properties:anyOf->items[5]:5',
        NULL,
      ],
      '#/my/internal/reference' => [
        // '#->properties:items->anyOf[0]->properties:anyOf->items[6]:6',
        NULL,
      ],
      'https://my/external/reference' => [
        // '#->properties:items->anyOf[0]->properties:anyOf->items[7]:7',
        NULL,
      ],
    ], $this->observer->getReferences());

    return $this->observer;
  }

  /**
   * Test discovery of external references.
   *
   * @covers ::process
   * @covers ::getExternalReferences
   * @covers ::getAssetFromReference
   * @covers ::__construct
   * @uses \Drupal\patternkit\Schema\UnresolvedSchema
   *
   * @depends testGetReferencesAnyOf
   * @dataProvider providerGetExternalReferences
   */
  public function testGetExternalReferences($exclusions, $expected, SchemaRefObserver $observer) {
    $references = $observer->getExternalReferences($exclusions);
    $this->assertEqualsCanonicalizing($expected, $references);
  }

  /**
   * Data provider for testGetExternalReferences().
   */
  public function providerGetExternalReferences() {
    $cases = [];

    // Confirm results with no exclusions provided.
    $cases['no exclusions'] = [
      [],
      [
        '@my/namespaced/reference',
        '@my/normalized/reference',
      ],
    ];

    // Confirm list of references with a single exclusion.
    $cases['single exclusion'] = [
      [
        '@my/namespaced/reference',
      ],
      [
        '@my/normalized/reference',
      ],
    ];

    // Confirm list of references with an exclusion that isn't present.
    $cases['missing exclusion'] = [
      [
        '@my/other/reference',
      ],
      [
        '@my/normalized/reference',
        '@my/namespaced/reference',
      ],
    ];

    // Confirm list of references with all results excluded.
    $cases['full exclusion'] = [
      [
        '@my/normalized/reference',
        '@my/namespaced/reference',
      ],
      [],
    ];

    return $cases;
  }

  /**
   * Test behavior with no references discovered.
   *
   * @covers ::process
   * @covers ::getReferences
   * @covers ::getExternalReferences
   * @covers ::__construct
   */
  public function testNoDiscoveredReferences() {
    UnresolvedSchema::import((object) [], $this->context);

    $this->assertEquals([], $this->observer->getReferences());
    $this->assertEquals([], $this->observer->getExternalReferences());
    $this->assertEquals([], $this->observer->getExternalReferences(['@my/missing/reference']));
  }

}
