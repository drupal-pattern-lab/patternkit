<?php

namespace Drupal\Tests\patternkit\Unit\Schema\DataPreProcessor;

use Drupal\Core\Utility\Token;
use Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Schema;

/**
 * Test the TokenValidationDataPreProcessor class.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor
 */
class TokenValidationDataPreProcessorTest extends UnitTestCase {

  use ProphecyTrait;
  use JsonDecodeTrait;

  /**
   * The DataPreProcessor being tested.
   *
   * @var \Swaggest\JsonSchema\DataPreProcessor
   */
  protected DataPreProcessor $dataPreProcessor;

  /**
   * A prepared Schema context with the DataPreProcessor for testing.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $context;

  /**
   * A testing schema with a nested object property.
   *
   * @var string
   */
  protected string $simpleObjectSchemaJson = <<<JSON
    {
      "title": "Object property",
      "type": "object",
      "properties": {
        "string_property": {
          "type": "string"
        }
      }
    }
    JSON;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $token = $this->prophesize(Token::class);
    $token->scan(Argument::containingString('[FAKE:TOKEN]'))
      ->willReturn(['FAKE' => ['TOKEN' => '[FAKE:TOKEN]']]);
    $token->scan(Argument::not(Argument::containingString('[FAKE:TOKEN]')))
      ->willReturn([]);

    $this->dataPreProcessor = new TokenValidationDataPreProcessor($token->reveal());

    $this->context = new Context();
    $this->context->setDataPreProcessor($this->dataPreProcessor);
  }

  /**
   * Test processing data with a schema.
   *
   * @dataProvider providerProcess
   */
  public function testProcess($data, string $schema_json, bool $import, bool $schema_should_change): void {
    $schema = Schema::import(static::decodeJson($schema_json));

    $original_schema = clone $schema;

    $actual = $this->dataPreProcessor->process($data, $schema, $import);

    // The data should never be changed by this processor.
    $this->assertEquals($data, $actual);

    // Test whether the schema was expected to be changed.
    if ($schema_should_change) {
      $this->assertNotEquals($original_schema, $schema);
    }
    else {
      $this->assertEquals($original_schema, $schema);
    }

  }

  /**
   * Data provider for testProcess.
   *
   * @return array
   *   An array of test cases.
   */
  public static function providerProcess(): array {
    $cases = [];

    $schema_json = '{ "type": "string" }';

    // String properties without a format should be unaffected.
    $cases['string_without_format_without_token'] = [
      "my_string",
      $schema_json,
      FALSE,
      FALSE,
    ];

    // String properties without a format should be unaffected even if they
    // contain a token.
    $cases['string_without_format_with_token'] = [
      "[FAKE:TOKEN]",
      $schema_json,
      FALSE,
      FALSE,
    ];

    $schema_json = '{ "type": "string", "format": "uri" }';

    // String properties with a format should be unaffected if they don't
    // contain a token.
    $cases['string_with_format_without_token'] = [
      "my_string",
      $schema_json,
      FALSE,
      FALSE,
    ];

    // String properties with a format and a token should be altered.
    $cases['string_with_format_with_token'] = [
      "[FAKE:TOKEN]",
      $schema_json,
      FALSE,
      TRUE,
    ];

    return $cases;
  }

}
