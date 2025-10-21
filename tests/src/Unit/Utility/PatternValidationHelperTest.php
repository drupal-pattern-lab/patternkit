<?php

namespace Drupal\Tests\patternkit\Unit\Utility;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\patternkit\Utility\PatternValidationHelper;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

/**
 * Unit tests for PatternValidationHelper.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Utility\PatternValidationHelper
 * @uses \Drupal\patternkit\PatternValidationTrait
 * @uses \Drupal\patternkit\Schema\ContextBuilderTrait
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
 * @uses \Drupal\patternkit\Schema\SchemaFactoryTrait
 * @uses \Swaggest\JsonSchema\Context
 */
class PatternValidationHelperTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * The patternkit context builder service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\ContextBuilder>
   */
  protected ObjectProphecy $contextBuilder;

  /**
   * The patternkit schema factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Schema\SchemaFactory>
   */
  protected ObjectProphecy $schemaFactory;

  /**
   * A pattern stub for use as a vehicle to provide schema information.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>
   */
  protected ObjectProphecy $pattern;

  /**
   * The pattern validation helper service being tested.
   *
   * @var \Drupal\patternkit\Utility\PatternValidationHelper
   */
  protected PatternValidationHelper $validationHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->contextBuilder = $this->prophesize(ContextBuilder::class);
    $this->schemaFactory = $this->prophesize(SchemaFactory::class);

    // Configure a default context to be used.
    $context = new Context();
    $context->tolerateStrings = TRUE;
    $context->setDataPreProcessor(new ObjectCoercionDataPreProcessor());

    // Mock a pattern for passing information into validation.
    $this->pattern = $this->prophesize(PatternInterface::class);

    // Return this default context from the context builder.
    $this->contextBuilder->getDefaultContext()->willReturn($context);

    // Return a schema instance for provided strings.
    $decode = [$this, 'decodeJson'];
    $this->schemaFactory->createInstance(Argument::type('string'))
      ->will(function (array $args) use ($decode, $context): SchemaContract {
        $schema_string = $args[0];
        $schema_data = call_user_func_array($decode, [$schema_string]);

        return Schema::import($schema_data, $context);
      });

    $this->validationHelper = new PatternValidationHelper(
      $this->contextBuilder->reveal(),
      $this->schemaFactory->reveal()
    );
  }

  /**
   * Test that the service can be instantiated and has the expected methods.
   */
  public function testServiceInstantiation(): void {
    $this->assertInstanceOf(PatternValidationHelper::class, $this->validationHelper);
    $this->assertTrue(method_exists($this->validationHelper, 'validateContent'));
    $this->assertTrue(method_exists($this->validationHelper, 'validatePattern'));
    $this->assertTrue(method_exists($this->validationHelper, 'hasExceptions'));
    $this->assertTrue(method_exists($this->validationHelper, 'getExceptions'));
    $this->assertTrue(method_exists($this->validationHelper, 'clearExceptions'));
  }

  /**
   * Test validation of valid content against a simple schema.
   */
  public function testValidateValidContent(): void {
    $schema = '{"type": "object", "properties": {"text": {"type": "string"}}, "required": ["text"]}';
    $content = '{"text": "Hello World"}';

    $result = $this->validationHelper->validateContent($schema, $content);
    $this->assertTrue($result);
    $this->assertFalse($this->validationHelper->hasExceptions());
  }

  /**
   * Test validation of invalid content against a schema.
   */
  public function testValidateInvalidContent(): void {
    $schema = '{"type": "object", "properties": {"text": {"type": "string"}}, "required": ["text"]}';
    // Invalid: number instead of string.
    $content = '{"text": 123}';

    $result = $this->validationHelper->validateContent($schema, $content);
    $this->assertFalse($result);
    $this->assertTrue($this->validationHelper->hasExceptions());
    $this->assertNotEmpty($this->validationHelper->getExceptions());
  }

  /**
   * Test validation of content against a pattern's schema.
   */
  public function testValidatePattern(): void {
    $schema = '{"type": "object", "properties": {"text": {"type": "string"}}, "required": ["text"]}';
    $content = ['text' => 'Hello World'];

    $this->pattern->getSchema()->willReturn($schema);

    $result = $this->validationHelper->validatePattern($this->pattern->reveal(), $content);
    $this->assertTrue($result);
    $this->assertFalse($this->validationHelper->hasExceptions());
  }

  /**
   * Test validation of invalid content against a pattern's schema.
   */
  public function testValidateInvalidPattern(): void {
    $schema = '{"type": "object", "properties": {"text": {"type": "string"}}, "required": ["text"]}';
    // Invalid: number instead of string.
    $content = ['text' => 123];

    $this->pattern->getSchema()->willReturn($schema);

    $result = $this->validationHelper->validatePattern($this->pattern->reveal(), $content);
    $this->assertFalse($result);
    $this->assertTrue($this->validationHelper->hasExceptions());
    $this->assertNotEmpty($this->validationHelper->getExceptions());
  }

  /**
   * Test clearing exceptions.
   */
  public function testClearExceptions(): void {
    $schema = '{"type": "object", "properties": {"text": {"type": "string"}}, "required": ["text"]}';
    // Invalid content.
    $content = '{"text": 123}';

    // First validation should fail and create exceptions.
    $this->validationHelper->validateContent($schema, $content);
    $this->assertTrue($this->validationHelper->hasExceptions());

    // Clear exceptions.
    $this->validationHelper->clearExceptions();
    $this->assertFalse($this->validationHelper->hasExceptions());
    $this->assertEmpty($this->validationHelper->getExceptions());
  }

}
