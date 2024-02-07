<?php

namespace Drupal\Tests\patternkit\Unit\Plugin\PatternFieldProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\patternkit\Plugin\PatternFieldProcessor\TokenProcessor;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
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
   * A mock config factory for accessing patternkit settings.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Config\ConfigFactoryInterface
   */
  protected MockObject|ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->token = $this->createMock(Token::class);
    $this->twig = $this->createMock(TwigEnvironment::class);
    $this->configFactory = $this->getConfigFactoryStub([
      'patternkit.settings' => [
        'patternkit_json_editor_token_clear' => TRUE,
      ],
    ]);

    // Instantiate the plugin for testing.
    $this->plugin = new TokenProcessor(
      [],
      'token',
      [],
      $this->configFactory->get('patternkit.settings'),
      $this->token,
      $this->twig);
  }

  /**
   * Tests applies method in various use cases.
   *
   * @covers ::applies
   * @covers ::__construct
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
   * @covers ::__construct
   */
  public function testApplyReplace() {
    $schema_json = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "title": "Example with a plain text property",
          "title": "Text",
          "type": "string",
          "format": "html"
      }
      JSON;
    $schema = Schema::import(json_decode($schema_json));

    $value = '<p>My [node:title] string</p>';
    $randomTitle = $this->getRandomGenerator()->name();
    $expected = str_replace('[node:title]', $randomTitle, $value);
    $node = $this->createMock(NodeInterface::class);
    $node->method('get')
      ->with('title')
      ->willReturn($randomTitle);

    // Ensure the replacePlain method is not invoked for html formatted strings.
    $this->token->expects($this->never())
      ->method('replacePlain');

    // Mock the token replacement with expected arguments.
    $this->token->expects($this->once())
      ->method('replace')
      ->with($value, ['node' => $node], ['clear' => TRUE], $this->isInstanceOf(BubbleableMetadata::class))
      ->willReturn($expected);

    $result = $this->plugin->apply($schema, $value, ['node' => $node], new BubbleableMetadata());
    $this->assertIsString($result);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::apply
   * @covers ::__construct
   */
  public function testApplyReplacePlain() {
    $schema_json = <<<JSON
      {
        "\$schema": "http://json-schema.org/draft-04/schema#",
        "title": "Example with a plain text property",
          "title": "Text",
          "type": "string"
      }
      JSON;
    $schema = Schema::import(json_decode($schema_json));

    $value = 'My [node:title] string';
    $randomTitle = $this->getRandomGenerator()->name();
    $expected = str_replace('[node:title]', $randomTitle, $value);
    $node = $this->createMock(NodeInterface::class);
    $node->method('get')
      ->with('title')
      ->willReturn($randomTitle);

    // Ensure the replace method is not invoked for plain text strings.
    $this->token->expects($this->never())
      ->method('replace');

    // Mock the token replacement with expected arguments.
    $this->token->expects($this->once())
      ->method('replacePlain')
      ->with($value, ['node' => $node], ['clear' => TRUE], $this->isInstanceOf(BubbleableMetadata::class))
      ->willReturn($expected);

    $result = $this->plugin->apply($schema, $value, ['node' => $node], new BubbleableMetadata());
    $this->assertIsString($result);
    $this->assertEquals($expected, $result);
  }

}
