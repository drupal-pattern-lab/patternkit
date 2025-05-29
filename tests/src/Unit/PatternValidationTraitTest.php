<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternValidationTrait;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\patternkit\Traits\SchemaFixtureTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

/**
 * Unit tests for PatternValidationTrait.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\PatternValidationTrait
 * @uses \Drupal\patternkit\Schema\ContextBuilderTrait
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
 * @uses \Drupal\patternkit\Schema\SchemaFactoryTrait
 * @uses \Swaggest\JsonSchema\Context
 */
class PatternValidationTraitTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;
  use SchemaFixtureTrait;

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
   * The class instance using the trait being tested.
   *
   * @var \Drupal\Tests\patternkit\Unit\PatternValidationTraitClass
   */
  protected PatternValidationTraitClass $sut;

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

    $this->sut = new PatternValidationTraitClass($this->contextBuilder->reveal(), $this->schemaFactory->reveal());
  }

  /**
   * Test validatePattern() for expected behavior with provided data.
   *
   * @param array $content
   *   Content to be validated against the schema.
   * @param string $schemaFixture
   *   A path to a schema fixture for validation. See
   *   \Drupal\Tests\patternkit\Traits\SchemaFixtureTrait::loadFixture() for
   *   argument details.
   * @param bool $expectedResult
   *   A boolean argument declaring whether validation should succeed or fail.
   *
   * @dataProvider providerValidatePattern
   */
  public function testValidatePattern(array $content, string $schemaFixture, bool $expectedResult): void {
    // Prepare the schema string to load from the pattern.
    $schema_string = $this->loadFixture($schemaFixture);
    $this->assertNotFalse($schema_string);
    $this->pattern->getSchema()->willReturn($schema_string);

    $result = $this->sut->validatePattern($this->pattern->reveal(), $content);
    $this->assertEquals($expectedResult, $result);
  }

  /**
   * Data provider for testValidatePattern().
   */
  public static function providerValidatePattern(): array {
    return [
      'valid content' => [
        // Valid schema data to test.
        [
          'text' => 'Example text',
        ],
        // The fixture to load.
        'schemas/required_content.json',
        // Expect it to validate successfully.
        TRUE,
      ],
      'invalid content' => [
        // Invalid schema data missing required properties.
        [
          'optional_text' => 'Example text',
        ],
        // The fixture to load.
        'schemas/required_content.json',
        // Expect it to validate successfully.
        FALSE,
      ],
    ];
  }

}

/**
 * A test-only class instance using the trait to be tested.
 *
 * @internal
 */
class PatternValidationTraitClass {

  use PatternValidationTrait {
    validatePattern as public;
  }

  /**
   * Create an instance of the test class with injected dependencies.
   *
   * @param \Drupal\patternkit\Schema\ContextBuilder $contextBuilder
   *   An injected context builder mock instance to use.
   * @param \Drupal\patternkit\Schema\SchemaFactory $schemaFactory
   *   An injected schema factory mock instance to use.
   */
  public function __construct(
    protected ContextBuilder $contextBuilder,
    protected SchemaFactory $schemaFactory,
  ) {}

}
