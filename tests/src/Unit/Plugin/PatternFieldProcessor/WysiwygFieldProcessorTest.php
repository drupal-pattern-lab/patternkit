<?php

namespace Drupal\Tests\patternkit\Unit\Plugin\PatternFieldProcessor;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\patternkit\Plugin\PatternFieldProcessor\WysiwygFieldProcessor;
use Drupal\Tests\UnitTestCase;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

/**
 * Tests for the WysiwygFieldProcessor plugin.
 *
 * @coversDefaultClass \Drupal\patternkit\Plugin\PatternFieldProcessor\WysiwygFieldProcessor
 * @group patternkit
 */
class WysiwygFieldProcessorTest extends UnitTestCase {

  /**
   * A configuration object for mocked patternkit settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settingsConfig;

  /**
   * The field processor plugin instance being tested.
   *
   * @var \Drupal\patternkit\Plugin\PatternFieldProcessor\WysiwygFieldProcessor
   */
  protected $plugin;

  /**
   * Mock text format for settings.
   *
   * @var string
   */
  protected $format = 'basic_html';

  /**
   * The mocked renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the settings values to be returned from configuration.
    $this->settingsConfig = $this->createMock(ImmutableConfig::class);
    $this->settingsConfig->method('get')
      ->with('patternkit_json_editor_ckeditor_toolbar')
      ->willReturn($this->format);

    // Mock the renderer for injection.
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->renderer->method('render')
      ->willReturnCallback(function ($element) {
        return $element['#text'];
      });

    // Instantiate the plugin for testing.
    $this->plugin = new WysiwygFieldProcessor([], 'wysiwyg', [],
      $this->settingsConfig, $this->renderer);
  }

  /**
   * Tests applies method in various use cases.
   *
   * @covers ::applies
   * @dataProvider providerApplies
   */
  public function testApplies(SchemaContract $propertySchema, bool $expected) {
    $this->assertEquals($expected, $this->plugin->applies($propertySchema));
  }

  /**
   * Data provider for the applies test.
   */
  public function providerApplies(): array {
    $schema_json = <<<JSON
{
  "\$schema": "http://json-schema.org/draft-04/schema#",
  "title": "Example with multiple property types",
  "type": "object",
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
    "amount": {
      "title": "Amount",
      "type": "number"
    },
    "breakpoints": {
      "title": "Breakpoints",
      "type": "array",
      "items": {
        "anyOf": [
          {
            "title": "",
            "type": "string",
            "enum": [
              "",
              "xxs",
              "xs",
              "sm",
              "md",
              "lg"
            ]
          }
        ]
      }
    }
  }
}
JSON;
    $schema = Schema::import(json_decode($schema_json));
    $properties = $schema->getProperties();

    $cases = [];

    $cases['full_object'] = [$schema, FALSE];

    $cases['html_wysiwyg_field'] = [$properties->formatted_text, TRUE];

    $cases['plain_text_field'] = [$properties->text, FALSE];

    $cases['object_property'] = [$properties->image, FALSE];

    $cases['nested_string_field'] = [
      $properties->image->getProperties()->image_url,
      FALSE,
    ];

    $cases['number_property'] = [$properties->amount, FALSE];

    $cases['array_property'] = [$properties->breakpoints, FALSE];

    return $cases;
  }

  /**
   * @covers ::apply
   */
  public function testApply() {
    $schema_json = <<<JSON
{
  "\$schema": "http://json-schema.org/draft-04/schema#",
  "title": "Example with a formatted text field",
  "type": "object",
  "properties": {
    "formatted_text": {
      "title": "Formatted text",
      "type": "string",
      "format": "html",
      "options": {
        "wysiwyg": true
      }
    }
  }
}
JSON;
    $schema = Schema::import(json_decode($schema_json));

    $value = $this->getRandomGenerator()->string();

    // Mock the render service call.
    // @todo Mock the processing for this better.
    $this->renderer->expects($this->once())
      ->method('render')
      ->with([
        '#type' => 'processed_text',
        '#text' => $value,
        '#format' => $this->format,
      ])
      ->willReturn($value);

    $metadata = new BubbleableMetadata();
    $result = $this->plugin->apply($schema, $value, [], $metadata);
    $this->assertIsString($result);
    $this->assertStringContainsString($value, $result);
  }

  /**
   * @covers ::getTextProfile
   */
  public function testGetTextProfile() {
    // See the mocked config value in ::setUp.
    $this->assertEquals('basic_html', $this->plugin->getTextProfile());
  }

}
