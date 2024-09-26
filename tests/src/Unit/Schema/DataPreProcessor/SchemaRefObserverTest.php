<?php

namespace Drupal\Tests\patternkit\Unit\Schema\DataPreProcessor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver;
use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\patternkit\Schema\UnresolvedSchema;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\RemoteRef\Preloaded;
use Swaggest\JsonSchema\Schema;

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
  protected static string $schemaWithReferenceJson = <<<JSON
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
   * A mocked context builder service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\ContextBuilder>
   */
  protected ObjectProphecy $contextBuilder;

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

    $this->contextBuilder = $this->prophesize(ContextBuilder::class);
    $this->contextBuilder->getDefaultContext()->willReturn(new Context());

    $builder = new ContainerBuilder();
    $builder->set('patternkit.schema.context_builder', $this->contextBuilder->reveal());
    \Drupal::setContainer($builder);
  }

  /**
   * Test reference discovery in given JSON schemas.
   *
   * @covers ::process
   * @covers ::processImport
   * @covers ::getReferences
   * @covers ::trackReference
   * @covers ::__construct
   * @dataProvider providerGetReferences
   */
  public function testGetReferences(string $schema_json, array $expected_references): void {
    $schema_data = static::decodeJson($schema_json);
    UnresolvedSchema::import($schema_data, $this->context);

    $references = $this->observer->getReferences();

    // Confirm all URLs were discovered.
    $this->assertEquals(array_keys($expected_references), array_keys($references));

    // Compare path expectations.
    $this->assertEquals($expected_references, $references);
  }

  /**
   * Data provider for testGetReferences().
   */
  public static function providerGetReferences(): array {
    $cases = [];

    $cases['one reference'] = [
      static::$schemaWithReferenceJson,
      [
        '@my/example/reference',
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
        '@patternkit/atoms/example_ref/../example/src/example.json#/properties/text',
        '@patternkit/atoms/example_ref/../example/src/example.json',
      ],
    ];

    return $cases;
  }

  /**
   * Test discovery of references in an array of anyOf references.
   *
   * @covers ::process
   * @covers ::processImport
   * @covers ::getReferences
   * @covers ::getAssetFromReference
   * @covers ::trackReference
   * @covers ::__construct
   * @uses \Drupal\patternkit\Schema\UnresolvedSchema
   */
  public function testGetReferencesAnyOf(): SchemaRefObserver {
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
    $schema_data = static::decodeJson($schema_json);
    UnresolvedSchema::import($schema_data, $this->context);

    $this->assertEquals([
      '@my/namespaced/reference',
      '@my/namespaced/reference#/with/path',
      '/api/patternkit/my/normalized/reference',
      '/api/patternkit/my/normalized/reference#/with/path',
      '/api/patternkit/my/invalid/reference',
      '/api/patternkit/my/invalid/reference#/with/path',
      '#/my/internal/reference',
      'https://my/external/reference',
    ], $this->observer->getReferences());

    return $this->observer;
  }

  /**
   * Test discovery of external references during schema import.
   *
   * @covers ::process
   * @covers ::processImport
   * @covers ::getExternalReferences
   * @covers ::getAssetFromReference
   * @covers ::__construct
   * @uses \Drupal\patternkit\Schema\UnresolvedSchema
   *
   * @depends testGetReferencesAnyOf
   * @dataProvider providerGetExternalReferencesDuringImport
   */
  public function testGetExternalReferencesDuringImport($exclusions, $expected, SchemaRefObserver $observer): void {
    $references = $observer->getExternalReferences($exclusions);

    // Test only the returned keys since import doesn't capture paths currently.
    $this->assertEqualsCanonicalizing($expected, $references);
  }

  /**
   * Data provider for testGetExternalReferencesDuringImport().
   */
  public static function providerGetExternalReferencesDuringImport(): array {
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
   * Test discovery of external references during schema import.
   *
   * Include unresolved references in results.
   *
   * @covers ::process
   * @covers ::processImport
   * @covers ::getExternalReferences
   * @covers ::getAssetFromReference
   * @covers ::__construct
   * @uses \Drupal\patternkit\Schema\UnresolvedSchema
   *
   * @depends testGetReferencesAnyOf
   * @dataProvider providerGetExternalReferencesDuringImportWithUnresolvedReferences
   */
  public function testGetExternalReferencesDuringImportWithUnresolvedReferences($exclusions, $expected, SchemaRefObserver $observer): void {
    $references = $observer->getExternalReferences($exclusions, TRUE);

    // Test only the returned keys since import doesn't capture paths currently.
    $this->assertEqualsCanonicalizing($expected, $references);
  }

  /**
   * Data provider.
   */
  public static function providerGetExternalReferencesDuringImportWithUnresolvedReferences(): array {
    $cases = [];

    // Confirm results with no exclusions provided.
    $cases['no exclusions'] = [
      [],
      [
        '@my/namespaced/reference',
        '@my/normalized/reference',
        '#/my/internal/reference',
        '/api/patternkit/my/invalid/reference',
        '/api/patternkit/my/invalid/reference#/with/path',
        'https://my/external/reference',
      ],
    ];

    // Confirm list of references with a single exclusion.
    $cases['single exclusion'] = [
      [
        '@my/namespaced/reference',
      ],
      [
        '@my/normalized/reference',
        '#/my/internal/reference',
        '/api/patternkit/my/invalid/reference',
        '/api/patternkit/my/invalid/reference#/with/path',
        'https://my/external/reference',
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
        '#/my/internal/reference',
        '/api/patternkit/my/invalid/reference',
        '/api/patternkit/my/invalid/reference#/with/path',
        'https://my/external/reference',
      ],
    ];

    // Confirm list of references with all resolved results excluded.
    $cases['full exclusion'] = [
      [
        '@my/normalized/reference',
        '@my/namespaced/reference',
      ],
      [
        '#/my/internal/reference',
        '/api/patternkit/my/invalid/reference',
        '/api/patternkit/my/invalid/reference#/with/path',
        'https://my/external/reference',
      ],
    ];

    return $cases;
  }

  /**
   * Test behavior with no references discovered.
   *
   * @covers ::process
   * @covers ::processImport
   * @covers ::getReferences
   * @covers ::getExternalReferences
   * @covers ::__construct
   */
  public function testNoDiscoveredReferences(): void {
    UnresolvedSchema::import((object) [], $this->context);

    $this->assertEquals([], $this->observer->getReferences());
    $this->assertEquals([], $this->observer->getExternalReferences());
    $this->assertEquals([], $this->observer->getExternalReferences(['@my/missing/reference']));
  }

  /**
   * Test detection of pattern references within content.
   *
   * @covers ::process
   * @covers ::processImport
   * @covers ::processValidation
   * @covers ::resetReferences
   * @covers ::getReferences
   * @covers ::getAssetFromReference
   * @covers ::trackReference
   * @covers ::__construct
   * @uses \Swaggest\JsonSchema\RemoteRef\Preloaded
   * @uses \Swaggest\JsonSchema\Schema
   * @uses \Drupal\patternkit\Schema\SchemaHelper
   */
  public function testUsageDetection(): void {
    // Prepare a referenced schema.
    $ref_provider = new Preloaded();
    $ref_provider->setSchemaData('@my/example/reference', json_decode(<<<JSON
      {
        "type": "object",
        "properties": {
          "something": {
            "type": "string"
          }
        }
      }
      JSON));
    $this->context->setRemoteRefProvider($ref_provider);

    // Import the schema for validation usage.
    $schema_data = static::decodeJson(static::$schemaWithReferenceJson);
    $schema = Schema::import($schema_data, $this->context);

    // Reset all references discovered during import.
    $this->observer->resetReferences();

    // Validate some content to scan for usages.
    $content = <<<JSON
      {
        "unknown_property": "some value",
        "reference_property": {
          "something": "else"
        }
      }
      JSON;
    $schema->out(static::decodeJson($content), $this->context);

    $this->assertEquals(['@my/example/reference'],
      $this->observer->getReferences());
  }

}
