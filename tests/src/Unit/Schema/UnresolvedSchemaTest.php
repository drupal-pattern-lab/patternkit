<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\patternkit\Schema\UnresolvedSchema;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Exception\ObjectException;
use Swaggest\JsonSchema\RemoteRefProvider;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

/**
 * Unit tests for the UnresolvedSchema class.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Schema\UnresolvedSchema
 * @uses \Swaggest\JsonSchema\Schema
 * @uses \Swaggest\JsonSchema\Context
 */
class UnresolvedSchemaTest extends UnitTestCase {

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
   * A JSON object schema to resolve the reference to.
   *
   * @var string
   */
  protected static string $referencedSchemaJson = <<<JSON
      {
        "type": "object",
        "properties": {
          "required_property": {
            "type": "string"
          }
        },
        "required": [
          "required_property"
        ],
        "additionalProperties": false
      }
      JSON;

  /**
   * The mocked reference provider.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Swaggest\JsonSchema\RemoteRefProvider>
   */
  protected ObjectProphecy $refProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->refProvider = $this->prophesize(RemoteRefProvider::class);
  }

  /**
   * Test schema import behavior without resolving references.
   */
  public function testImport(): UnresolvedSchema {
    $this->refProvider->getSchemaData('@my/example/reference')
      ->willReturn((object) [])
      ->shouldNotBeCalled();

    $context = new Context();
    $context->setRemoteRefProvider($this->refProvider->reveal());
    $context->dereference = FALSE;

    $schema_data = static::decodeJson(static::$schemaWithReferenceJson);

    $schema = UnresolvedSchema::import($schema_data, $context);

    $schema_output = json_encode($schema, JSON_PRETTY_PRINT);
    $this->assertStringContainsString('@my\/example\/reference', $schema_output);

    return $schema;
  }

  /**
   * Test various import scenarios to confirm behavior.
   *
   * @dataProvider providerImportScenarios
   */
  public function testImportScenarios(string $json): void {
    $schema_data = static::decodeJson($json);
    $schema = UnresolvedSchema::import($schema_data);
    $this->assertJsonStringEqualsJsonString($json, json_encode($schema));
  }

  /**
   * Data provider for testImportScenarios().
   */
  public static function providerImportScenarios(): array {
    $cases = [];

    $cases['ref only object'] = [
      '{ "$ref": "@my/pattern/reference" }',
    ];

    $json = <<<JSON
      {
        "type": "object",
        "properties": {
          "reference": {
            "\$ref": "@my/pattern/reference"
          }
        }
      }
      JSON;
    $cases['ref object property'] = [$json];

    $json = <<<JSON
      {
        "type": "object",
        "properties": {
          "reference": {
            "\$ref": "#/definitions/local_reference"
          }
        },
        "definitions": {
          "local_reference": {
            "type": "string"
          }
        }
      }
      JSON;
    $cases['with resolved references'] = [$json];

    return $cases;
  }

  /**
   * Test schema resolution after the initial import.
   *
   * @depends testImport
   */
  public function testResolve(UnresolvedSchema $schema): SchemaContract {
    $this->refProvider->getSchemaData('@my/example/reference')
      ->willReturn(static::decodeJson(static::$referencedSchemaJson))
      ->shouldBeCalled();

    $context = new Context();
    $context->setRemoteRefProvider($this->refProvider->reveal());
    $context->dereference = TRUE;

    $resolvedSchema = $schema->resolve($context);
    $export = json_encode($resolvedSchema, JSON_PRETTY_PRINT);

    $this->assertStringNotContainsString('@my\/example\/reference', $export);
    $this->assertNotInstanceOf(UnresolvedSchema::class, $resolvedSchema);

    return $resolvedSchema;
  }

  /**
   * Test validation behavior after an unresolved schema is resolved.
   *
   * @depends testResolve
   * @coversNothing
   */
  public function testResolvedValidation(SchemaContract $schema): void {
    $valid_data = <<<JSON
      {
        "reference_property": {
          "required_property": "this is required"
        }
      }
      JSON;

    $invalid_data = <<<JSON
      {
        "reference_property": {
          "required_property": "my string",
          "some_extra_property": "this should fail"
        }
      }
      JSON;

    // This should succeed.
    $data = static::decodeJson($valid_data);
    $result = $schema->out($data);
    $this->assertEquals($data, $result);

    // This should fail.
    $this->expectException(ObjectException::class);
    $this->expectExceptionMessage("Additional properties not allowed");

    $data = static::decodeJson($invalid_data);
    $schema->out($data);
  }

  /**
   * Test side effects from using the import method.
   */
  public function testImportSideEffects(): void {
    // Confirm state before execution.
    $defaultSchema = Schema::schema();
    $refProperty = $defaultSchema->getProperties()->ref;
    $defaultFormat = $refProperty->format;
    $this->assertNotNull($defaultFormat);

    $context = new Context();
    $context->dereference = FALSE;
    $schema = UnresolvedSchema::import(static::decodeJson(static::$schemaWithReferenceJson), $context);

    // Confirm reference-handling behaved as expected.
    $export = json_encode($schema, JSON_PRETTY_PRINT);
    $this->assertStringContainsString('@my\/example\/reference', $export);

    // Confirm stat was restored after import.
    $defaultSchema = Schema::schema();
    $refProperty = $defaultSchema->getProperties()->ref;
    $defaultFormat = $refProperty->format;
    $this->assertNotNull($defaultFormat);
  }

  /**
   * Test serialization to confirm loaded references remain unresolved.
   */
  public function testJsonSerialize(): void {
    $json = <<<JSON
      {
        "type": "object",
        "properties": {
          "reference": {
            "\$ref": "#/definitions/local_reference"
          }
        },
        "definitions": {
          "local_reference": {
            "type": "string"
          }
        }
      }
    JSON;

    $context = new Context();

    // Enable dereferencing to load the local reference in place.
    $context->dereference = TRUE;

    $schema = UnresolvedSchema::import(static::decodeJson($json), $context);

    $this->assertJsonStringEqualsJsonString($json, json_encode($schema, JSON_PRETTY_PRINT));
  }

}
