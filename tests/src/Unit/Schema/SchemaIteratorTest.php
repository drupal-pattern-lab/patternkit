<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\patternkit\Schema\SchemaIterator;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\patternkit\Traits\SchemaHelperTestTrait;
use Drupal\Tests\UnitTestCase;
use Swaggest\JsonSchema\Schema;

/**
 * Test the SchemaIterator class implementation.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\SchemaIterator
 * @group patternkit
 */
class SchemaIteratorTest extends UnitTestCase {

  use JsonDecodeTrait;
  use SchemaHelperTestTrait;

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
        "hidden": {
          "title": "hidden",
          "type": "string"
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
        }
      }
    }
    JSON;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $this->setUpSchemaFactory($container);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getPropertySchema
   */
  public function testGetPropertySchema() {
    $values = [
      'text' => 'test',
      'image' => [
        'image_url' => 'abc123',
      ],
      'breakpoints' => [
        'xs',
        'large',
      ],
    ];

    $schema = Schema::import($this->decodeJson($this->flatSchema));
    $iterator = new SchemaIterator($schema, $values);

    // Test the first string property and value.
    $this->assertEquals('text', $iterator->key());
    $this->assertEquals($values['text'], $iterator->current());
    $this->assertEquals(FALSE, $iterator->hasChildren());
    $propertySchema = $iterator->getPropertySchema();
    $this->assertEquals('string', $propertySchema->type);

    // Test the next image object property.
    $iterator->next();
    $this->assertEquals('image', $iterator->key());
    $this->assertEquals($values['image'], $iterator->current());
    $this->assertEquals(TRUE, $iterator->hasChildren());
    $propertySchema = $iterator->getPropertySchema();
    $this->assertEquals('object', $propertySchema->type);

    // Test iteration over the child properties.
    $subIterator = $iterator->getChildren();
    $this->assertEquals('image_url', $subIterator->key());
    $this->assertEquals($values['image']['image_url'], $subIterator->current());
    $this->assertEquals(FALSE, $subIterator->hasChildren());
    $propertySchema = $subIterator->getPropertySchema();
    $this->assertEquals('string', $propertySchema->type);

    // Expect the next property to NOT be 'hidden' since no value is assigned.
    $iterator->next();
    $this->assertNotEquals('hidden', $iterator->key());

    // Test the expected breakpoints array property.
    $this->assertEquals('breakpoints', $iterator->key());
    $this->assertEquals($values['breakpoints'], $iterator->current());
    $this->assertEquals(TRUE, $iterator->hasChildren());
    $propertySchema = $iterator->getPropertySchema();
    $this->assertEquals('array', $propertySchema->type);

    // Test iteration over the child properties.
    $subIterator = $iterator->getChildren();
    $this->assertEquals('0', $subIterator->key());
    $this->assertEquals($values['breakpoints'][0], $subIterator->current());
    $this->assertEquals(FALSE, $subIterator->hasChildren());
    $propertySchema = $subIterator->getPropertySchema();
    $this->assertEquals('string', $propertySchema->type);
    $this->assertEquals('Abbreviated sizes', $propertySchema->title);

    $subIterator->next();
    $this->assertEquals('1', $subIterator->key());
    $this->assertEquals($values['breakpoints'][1], $subIterator->current());
    $this->assertEquals(FALSE, $subIterator->hasChildren());
    $propertySchema = $subIterator->getPropertySchema();
    $this->assertEquals('string', $propertySchema->type);
    $this->assertEquals('Explicit sizes', $propertySchema->title);
  }

}
