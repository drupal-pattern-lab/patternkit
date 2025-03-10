<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Schema\PatternkitRefProvider;
use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * Test the PatternkitRefProvider class.
 */
#[Group('patternkit')]
#[CoversClass(PatternkitRefProvider::class)]
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
   * The pattern repository service for loading patterns.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternRepository>
   */
  protected ObjectProphecy $patternRepository;

  /**
   * The mocked SchemaRefHandler service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\SchemaRefHandler>
   */
  protected ObjectProphecy $schemaRefHandler;

  /**
   * The mocked patternkit logger channel.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * A testing schema with a property using a reference.
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
   * A testing schema with a reference to invalid content.
   *
   * @var string
   */
  protected static string $invalidRefSchemaJson = <<<JSON
    {
      "\$schema": "http://json-schema.org/draft-04/schema#",
      "category": "atom",
      "title": "Example reference to invalid content",
      "type": "object",
      "format": "grid",
      "properties": {
        "text_reference": {
          "\$ref": "/api/patternkit/patternkit/refs/invalid?asset=schema"
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
  protected static string $simpleNumberSchemaJson = <<<JSON
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
  protected static string $simpleObjectSchemaJson = <<<JSON
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

    $this->schemaRefHandler = $this->prophesize(SchemaRefHandler::class);
    $this->patternRepository = $this->prophesize(PatternRepository::class);
    $this->logger = $this->prophesize(LoggerInterface::class);

    // Register simple schemas for references.
    $this->createMockPattern('@patternkit/refs/text', static::$simpleTextSchemaJson);
    $this->createMockPattern('@patternkit/refs/number', static::$simpleNumberSchemaJson);
    $this->createMockPattern('@patternkit/refs/object', static::$simpleObjectSchemaJson);

    $this->schemaRefHandler->parseNamespacedSchemaReference(Argument::containingString('@patternkit/refs/text'))
      ->willReturn(['asset_id' => '@patternkit/refs/text']);
    $this->schemaRefHandler->parseNamespacedSchemaReference(Argument::containingString('@patternkit/refs/number'))
      ->willReturn(['asset_id' => '@patternkit/refs/number']);
    $this->schemaRefHandler->parseNamespacedSchemaReference(Argument::containingString('@patternkit/refs/object'))
      ->willReturn(['asset_id' => '@patternkit/refs/object']);

    $this->schemaRefHandler->parseNormalizedSchemaReference(Argument::containingString('/api/patternkit/patternkit/refs/text?asset=schema'))
      ->willReturn(['asset_id' => '@patternkit/refs/text']);
    $this->schemaRefHandler->parseNormalizedSchemaReference(Argument::containingString('/api/patternkit/patternkit/refs/number?asset=schema'))
      ->willReturn(['asset_id' => '@patternkit/refs/number']);
    $this->schemaRefHandler->parseNormalizedSchemaReference(Argument::containingString('/api/patternkit/patternkit/refs/object?asset=schema'))
      ->willReturn(['asset_id' => '@patternkit/refs/object']);

    // Add catch-alls to handle undefined patterns.
    $this->schemaRefHandler->parseNamespacedSchemaReference(Argument::type('string'))
      ->willReturn(FALSE);
    $this->schemaRefHandler->parseNormalizedSchemaReference(Argument::type('string'))
      ->willReturn(FALSE);

    $this->provider = new PatternkitRefProvider(
      $this->schemaRefHandler->reveal(),
      $this->logger->reveal(),
    );
    $this->provider->setPatternRepository($this->patternRepository->reveal());
  }

  #[DataProvider('providerGetSchemaData')]
  public function testGetSchemaData(string $url, $expected) {
    $this->assertEquals($expected, $this->provider->getSchemaData($url));
  }

  /**
   * Data provider for ::testGetSchemaData.
   */
  public static function providerGetSchemaData(): array {
    $cases = [];

    // Test loading simple schemas directly.
    $cases['simple_text_schema'] = [
      '/api/patternkit/patternkit/refs/text?asset=schema',
      static::decodeJson(static::$simpleTextSchemaJson),
    ];
    $cases['simple_text_schema_namespaced'] = [
      '@patternkit/refs/text',
      static::decodeJson(static::$simpleTextSchemaJson),
    ];
    $cases['simple_number_schema'] = [
      '/api/patternkit/patternkit/refs/number?asset=schema',
      static::decodeJson(static::$simpleNumberSchemaJson),
    ];
    $cases['simple_number_schema_namespaced'] = [
      '@patternkit/refs/number',
      static::decodeJson(static::$simpleNumberSchemaJson),
    ];
    $cases['simple_object_schema'] = [
      '/api/patternkit/patternkit/refs/object?asset=schema',
      static::decodeJson(static::$simpleObjectSchemaJson),
    ];
    $cases['simple_object_schema_namespaced'] = [
      '@patternkit/refs/object',
      static::decodeJson(static::$simpleObjectSchemaJson),
    ];

    // Test loading a schema with a local path defined.
    // Expected behavior is that the local path is ignored at this point.
    $cases['object_with_path'] = [
      '/api/patternkit/patternkit/refs/object?asset=schema#/properties/text',
      static::decodeJson(static::$simpleObjectSchemaJson),
    ];
    $cases['object_with_path_namespaced'] = [
      '@patternkit/refs/object#/properties/text',
      static::decodeJson(static::$simpleObjectSchemaJson),
    ];
    $cases['object_with_invalid_path'] = [
      '/api/patternkit/patternkit/refs/object?asset=schema#/properties/undefined_property',
      static::decodeJson(static::$simpleObjectSchemaJson),
    ];
    $cases['object_with_invalid_path_namespaced'] = [
      '@patternkit/refs/object#/properties/undefined_property',
      static::decodeJson(static::$simpleObjectSchemaJson),
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
   * Test fetching schema data with invalid JSON content.
   */
  public function testGetSchemaDataWithInvalidContent() {
    // Add test cases for returning invalid JSON.
    $this->schemaRefHandler->parseNamespacedSchemaReference('@patternkit/refs/invalid')
      ->willReturn(['asset_id' => '@patternkit/refs/invalid']);
    $this->schemaRefHandler->parseNormalizedSchemaReference(Argument::containingString('/api/patternkit/patternkit/refs/invalid?asset=schema'))
      ->willReturn(['asset_id' => '@patternkit/refs/invalid'])
      ->shouldBeCalledOnce();
    $this->createMockPattern('@patternkit/refs/invalid', '{ "invalid_json": abcdefg, }');

    $this->logger->error("Unable to decode JSON for pattern reference '/api/patternkit/patternkit/refs/invalid?asset=schema'.\nSyntax error")
      ->shouldBeCalledOnce();

    $provider = new PatternkitRefProvider(
      $this->schemaRefHandler->reveal(),
      $this->logger->reveal(),
    );
    $provider->setPatternRepository($this->patternRepository->reveal());

    $result = $provider->getSchemaData('/api/patternkit/patternkit/refs/invalid?asset=schema');

    $this->assertFalse($result);
  }

  /**
   * Prepare a pattern mock for retrieval from the pattern repository.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern to be retrieved.
   * @param string $schema
   *   The schema content to be returned from the pattern.
   */
  protected function createMockPattern(string $pattern_id, string $schema): void {
    $pattern = $this->prophesize(Pattern::class);

    $this->patternRepository->getPattern($pattern_id)
      ->will(function () use ($pattern, $schema) {
        $pattern->getSchema()->willReturn($schema);

        return $pattern->reveal();
      });
  }

}
