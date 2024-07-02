<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\SchemaHelper;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;

/**
 * Tests the SchemaHelper utility class integration with contexts.
 *
 * Tests the SchemaHelper utility class with expected behaviors from configured
 * context components.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\SchemaHelper
 * @group patternkit
 */
class SchemaHelperIntegrationTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * A prophecy mock for the context builder service.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $contextBuilder;

  /**
   * A default schema context to use for schema operations.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $context;

  /**
   * A basic JSON schema with various property types, but no references.
   *
   * @var string
   */
  protected string $flatSchema = <<<JSON
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
        "breakpoints": {
          "title": "Breakpoints",
          "type": "array",
          "items": {
            "anyOf": [
              {
                "title": "Abbreviated sizes",
                "type": "string",
                "enum": [
                  "",
                  "xxs",
                  "xs",
                  "sm",
                  "md",
                  "lg"
                ]
              },
              {
                "title": "Explicit sizes",
                "type": "string",
                "enum": [
                  "extra-extra-small",
                  "extra-small",
                  "small",
                  "medium",
                  "large"
                ]
              }
            ]
          }
        },
        "nested_items": {
          "title": "Nested items of various types",
          "type": "array",
          "items": {
            "anyOf": [
              {
                "title": "Simple object",
                "type": "object"
              },
              {
                "title": "Simple string",
                "type": "string"
              }
            ]
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

    $this->contextBuilder = $this->prophesize(ContextBuilder::class);
    // Return a callback to get the currently configured context value, so it
    // may be created and configured independently by each test.
    $this->contextBuilder->getDefaultContext()->will([$this, 'getContext']);

    $container = new ContainerBuilder();
    $container->set('patternkit.schema.context_builder', $this->contextBuilder->reveal());
    \Drupal::setContainer($container);

    // Initialize to an empty context for tests that don't set anything up
    // explicitly for their use case.
    $this->context = new Context();
  }

  /**
   * A helper function to get the context configured for a specific test.
   *
   * @return \Swaggest\JsonSchema\Context
   *   The schema context as configured by the currently executing test.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * @covers ::getCompositionSchema
   * @covers ::getConstraintFromObject
   * @covers ::manuallyTestConstraints
   * @covers \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
   */
  public function testGetCompositionSchemaWithDataCoercion() {
    $this->context = new Context();
    $this->context->setDataPreProcessor(new ObjectCoercionDataPreProcessor());

    $schema = Schema::import(static::decodeJson($this->flatSchema));
    $propertySchema = $schema->getProperties()->nested_items->items;

    // A simple string value should validate and return the expected schema.
    $result = SchemaHelper::getCompositionSchema($propertySchema, 'My test string');
    $this->assertEquals($propertySchema->anyOf[1], $result, 'The expected composition rule was not identified.');

    // It should successfully validate when provided with an object.
    $result = SchemaHelper::getCompositionSchema($propertySchema, static::decodeJson('{ }'));
    $this->assertEquals($propertySchema->anyOf[0], $result, 'The expected composition rule was not identified.');

    // If flexible casting is not supported this will fail validation when
    // provided with an object cast as an array.
    $result = SchemaHelper::getCompositionSchema($propertySchema, static::decodeJson('{ }', JSON_OBJECT_AS_ARRAY));
    $this->assertEquals($propertySchema->anyOf[0], $result, 'The expected composition rule was not identified.');
  }

}
