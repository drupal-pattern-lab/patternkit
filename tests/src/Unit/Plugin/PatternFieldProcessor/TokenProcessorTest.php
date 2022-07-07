<?php

namespace Drupal\Tests\patternkit\Unit\Plugin\PatternFieldProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\patternkit\Plugin\PatternFieldProcessor\TokenProcessor;
use Drupal\Tests\UnitTestCase;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

/**
 * Tests for the TokenProcessor plugin.
 *
 * @coversDefaultClass \Drupal\patternkit\Plugin\PatternFieldProcessor\TokenProcessor
 * @group patternkit
 */
class TokenProcessorTest extends UnitTestCase {

  /**
   * The token processing service for parsing and loading token values.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * The twig environment service for rendering twig output.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected TwigEnvironment $twig;

  /**
   * The TokenProcessor plugin instance for testing.
   *
   * @var \Drupal\patternkit\Plugin\PatternFieldProcessor\TokenProcessor
   */
  protected TokenProcessor $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->token = $this->createMock(Token::class);
    $this->twig = $this->createMock(TwigEnvironment::class);

    // Instantiate the plugin for testing.
    $this->plugin = new TokenProcessor([], 'token', [],
      $this->token, $this->twig);
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
  public function providerApplies() {
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

    $cases['plain_text_field'] = [$properties->text, TRUE];

    $cases['object_property'] = [$properties->image, FALSE];

    $cases['nested_string_field'] = [
      $properties->image->getProperties()->image_url,
      TRUE,
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
  "title": "Example with a basic text property",
  "type": "object",
  "properties": {
    "text": {
      "title": "Text",
      "type": "string",
      "options": {
        "grid_columns": 4
      }
    }
  }
}
JSON;
    $schema = Schema::import(json_decode($schema_json));

    $value = 'My [node:title] string';
    $randomTitle = $this->getRandomGenerator()->name();
    $node = $this->createMock(NodeInterface::class);
    $node->title = $randomTitle;

    // Mock the token discovery.
    $this->token->expects($this->once())
      ->method('scan')
      ->willReturn([
        'node' => [
          'title' => '[node:title]',
        ],
      ]);
    $this->token->expects($this->once())
      ->method('generate')
      ->willReturn([
        '[node:title]' => $randomTitle,
      ]);

    // Mock twig rendering.
    // @todo Remove this once token replacement is rewritten.
    $this->markAsRisky();
    $this->twig->expects($this->once())
      ->method('renderInline')
      ->with("My {{nodetitle}} string", ['nodetitle' => $randomTitle])
      ->willReturn("My $randomTitle string");

    $result = $this->plugin->apply($schema, $value, ['node' => $node], new BubbleableMetadata());
    $this->assertIsString($result);
    $this->assertEquals("My $randomTitle string", $result);
  }

}
