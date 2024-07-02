<?php

namespace Drupal\Tests\patternkit\Unit\Schema\DataPreProcessor;

use Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor;
use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\patternkit\Schema\UnresolvedSchema;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Swaggest\JsonSchema\Context;

/**
 * Unit tests for the RefNormalizerDataPreProcessor.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor
 * @uses \Drupal\patternkit\Schema\UnresolvedSchema
 * @uses \Swaggest\JsonSchema\Context
 * @group patternkit
 */
class RefNormalizerDataPreProcessorTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * The RefNormalizerDataPreProcessor instance being tested.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor
   */
  protected RefNormalizerDataPreProcessor $normalizer;

  /**
   * A prepared schema context including the observer as a data preprocessor.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $context;

  /**
   * The mocked SchemaRefHandler service.
   *
   * @var \Drupal\patternkit\Schema\SchemaRefHandler|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $schemaRefHandler;

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
        },
        "path_property": {
          "\$ref": "@my/example/reference#/properties/text"
        }
      }
    }
    JSON;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->schemaRefHandler = $this->prophesize(SchemaRefHandler::class);
    $this->context = new Context();
  }

  /**
   * A helper function to reveal mocks and prepare the schema context.
   *
   * This is helpful to defer instantation of the mocks and allow preparation
   * of them in individual tests.
   *
   * @return \Swaggest\JsonSchema\Context
   *   A prepared schema context with the data preprocessor assigned.
   */
  protected function getContext(): Context {
    $this->normalizer = new RefNormalizerDataPreProcessor($this->schemaRefHandler->reveal());

    $this->context ??= new Context();
    $this->context->setDataPreProcessor($this->normalizer);

    return $this->context;
  }

  /**
   * @covers ::process
   * @covers ::__construct
   */
  public function testProcess() {
    $this->schemaRefHandler->normalizeNamespacedSchemaReference('@my/example/reference')
      ->willReturn('/api/patternkit/my/example/reference?asset=schema')
      ->shouldBeCalledOnce();
    $this->schemaRefHandler->normalizeNamespacedSchemaReference('@my/example/reference#/properties/text')
      ->willReturn('/api/patternkit/my/example/reference?asset=schema#/properties/text')
      ->shouldBeCalledOnce();

    $context = $this->getContext();
    $schema = UnresolvedSchema::import(static::decodeJson($this->schemaWithReferenceJson), $context);
    $json = json_encode($schema, JSON_PRETTY_PRINT);

    $this->assertStringNotContainsString('@my\/example\/reference', $json);
    $this->assertStringContainsString('\/api\/patternkit\/my\/example\/reference?asset=schema', $json);
    $this->assertStringNotContainsString('@my\/example\/reference#\/properties\/text', $json);
    $this->assertStringContainsString('\/api\/patternkit\/my\/example\/reference?asset=schema#\/properties\/text', $json);
  }

}
