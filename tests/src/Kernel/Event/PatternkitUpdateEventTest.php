<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\patternkit\Event\PatternUpdateEvent;
use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait;
use Drupal\Tests\patternkit\Traits\SchemaFixtureTrait;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test PatternUpdateEvent behavior with default contexts.
 *
 * @coversDefaultClass \Drupal\patternkit\Event\PatternUpdateEvent
 * @covers ::__construct
 * @covers \Drupal\patternkit\PatternValidationTrait::validateContent
 * @uses \Drupal\patternkit\PatternValidationTrait::schemaFactory
 * @uses \Drupal\patternkit\PatternValidationTrait::contextBuilder
 * @uses \Drupal\patternkit\Schema\ContextBuilder
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor
 * @uses \Drupal\patternkit\Schema\PatternkitRefProvider
 * @uses \Drupal\patternkit\Schema\SchemaFactory
 * @uses \Drupal\patternkit\Schema\SchemaFactoryTrait
 *
 * @group patternkit
 */
class PatternkitUpdateEventTest extends PatternkitKernelTestBase {

  use PatternkitMockHelperTrait;
  use ProphecyTrait;
  use SchemaFixtureTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['patternkit_example'];

  /**
   * The prophecy object for mocking the original pattern entity.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>
   */
  protected ObjectProphecy $oldPatternProphecy;

  /**
   * The prophecy object for mocking a pattern entity after potential altering.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>
   */
  protected ObjectProphecy $newPatternProphecy;

  /**
   * The prophecy object for a mocked patternkit block being updated.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Plugin\Block\PatternkitBlock>
   */
  protected ObjectProphecy $patternkitBlockProphecy;

  /**
   * The prophecy object for a mocked patternkit block entity.
   *
   * This entity is referenced by the block plugin and contains the block's
   * stored data.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternkitBlock>
   */
  protected ObjectProphecy $patternkitBlockEntityProphecy;

  /**
   * The class instance using the trait being tested.
   *
   * @var \Drupal\patternkit\Event\PatternUpdateEvent
   */
  protected PatternUpdateEvent $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->oldPatternProphecy = $this->getMockPatternEntity([
      'name' => 'Old Pattern Name',
      'asset_id' => '@patternkit/old/pattern',
    ]);
    $this->newPatternProphecy = $this->getMockPatternEntity([
      'name' => 'New Pattern Name',
      'asset_id' => '@patternkit/new/pattern',
    ]);

    $this->patternkitBlockEntityProphecy = $this->getMockPatternkitBlockEntity();

    $this->patternkitBlockProphecy = $this->getMockPatternkitBlockPlugin([
      'block' => $this->patternkitBlockEntityProphecy->reveal(),
      'pattern' => $this->newPatternProphecy->reveal(),
      'base_pattern' => $this->oldPatternProphecy->reveal(),
      'pattern_id' => '@patternkit/new/pattern',
    ]);
  }

  /**
   * Test validateContent() for expected behavior with provided data.
   *
   * @param array $content
   *   Content to be validated against the schema.
   * @param string $schemaFixture
   *   A path to a schema fixture for validation. See
   *   \Drupal\Tests\patternkit\Traits\SchemaFixtureTrait::loadFixture() for
   *   argument details.
   * @param array{'allowInvalid': bool, 'contentIsValid': bool} $scenarioConfig
   *   An associative array of test scenario configuration values as received
   *   from providerSetContent().
   *   - 'allowInvalid': (bool) The boolean option for the $allowInvalid
   *     argument to be passed to setContent().
   *   - 'contentIsValid': (bool) A boolean argument declaring whether the
   *     validation operation is expected to succeed or fail.
   *
   * @covers ::validateContent
   *
   * @dataProvider providerSetContent
   */
  public function testValidateContent(
    array $content,
    string $schemaFixture,
    array $scenarioConfig,
  ): void {
    // Parse the scenario config for local use.
    [
      'contentIsValid' => $contentIsValid,
    ] = $scenarioConfig;

    // Setup data fixtures.
    $this->setupSetContentTests($content, $schemaFixture);

    // Instantiate the update event for testing.
    $this->sut = $this->getEvent();

    $result = $this->sut->validateContent($content);
    $this->assertEquals($contentIsValid, $result);
  }

  /**
   * Test setContent() for expected behavior with provided data.
   *
   * @param array $content
   *   Content to be validated against the schema.
   * @param string $schemaFixture
   *   A path to a schema fixture for validation. See
   *   \Drupal\Tests\patternkit\Traits\SchemaFixtureTrait::loadFixture() for
   *   argument details.
   * @param array{'allowInvalid': bool, 'contentIsValid': bool} $scenarioConfig
   *   An associative array of test scenario configuration values as received
   *   from providerSetContent().
   *   - 'allowInvalid': (bool) The boolean option for the $allowInvalid
   *     argument to be passed to setContent().
   *   - 'contentIsValid': (bool) A boolean argument declaring whether the
   *     validation operation is expected to succeed or fail.
   *
   * @covers ::setContent
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent::validateContent()
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent::getExceptions()
   *
   * @dataProvider providerSetContent
   * @depends testValidateContent
   */
  public function testSetContent(
    array $content,
    string $schemaFixture,
    array $scenarioConfig,
  ): void {
    // Parse the scenario config for local use.
    [
      'allowInvalid' => $allowInvalid,
      'contentIsValid' => $contentIsValid,
    ] = $scenarioConfig;

    // Setup data fixtures.
    $this->setupSetContentTests($content, $schemaFixture);

    // Expect a thrown exception if the expected result is FALSE and invalid
    // content is not allowed.
    $expectExceptionThrown = !$contentIsValid && !$allowInvalid;
    if ($expectExceptionThrown) {
      $this->expectException(SchemaValidationException::class);
    }

    // Trigger the method.
    $result = $this->sut->setContent($content, $allowInvalid);

    // Confirm the method returned the event object for chaining.
    $this->assertEquals($this->sut, $result);
  }

  /**
   * Confirm exceptions were detected if content is invalid.
   *
   * @param array $content
   *   Content to be validated against the schema.
   * @param string $schemaFixture
   *   A path to a schema fixture for validation. See
   *   \Drupal\Tests\patternkit\Traits\SchemaFixtureTrait::loadFixture() for
   *   argument details.
   * @param array{'allowInvalid': bool, 'contentIsValid': bool} $scenarioConfig
   *   An associative array of test scenario configuration values as received
   *   from providerSetContent() and passed through testSetContent().
   *   - 'allowInvalid': (bool) The boolean option for the $allowInvalid
   *     argument to be passed to setContent().
   *   - 'contentIsValid': (bool) A boolean argument declaring whether the
   *     validation operation is expected to succeed or fail.
   *
   * @covers ::hasExceptions
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent::validateContent()
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent::setContent()
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent::getExceptions()
   *
   * @dataProvider providerSetContent
   * @depends testSetContent
   */
  public function testHasExceptions(
    array $content,
    string $schemaFixture,
    array $scenarioConfig,
  ): void {
    // Parse the scenario config for local use.
    [
      'allowInvalid' => $allowInvalid,
      'contentIsValid' => $contentIsValid,
    ] = $scenarioConfig;

    // Setup data fixtures.
    $this->setupSetContentTests($content, $schemaFixture);

    // Expect a thrown exception if the expected result is FALSE and invalid
    // content is not allowed.
    $expectExceptionThrown = !$contentIsValid && !$allowInvalid;
    if ($expectExceptionThrown) {
      $this->expectException(SchemaValidationException::class);
    }

    // Trigger the method.
    $this->sut->setContent($content, $allowInvalid);

    // If content is invalid, expect exceptions to have been recorded.
    $expectExceptions = !$contentIsValid && $allowInvalid;
    $this->assertEquals($expectExceptions, $this->sut->hasExceptions());
  }

  /**
   * Confirm expected exceptions were recorded.
   *
   * @param array $content
   *   Content to be validated against the schema.
   * @param string $schemaFixture
   *   A path to a schema fixture for validation. See
   *   \Drupal\Tests\patternkit\Traits\SchemaFixtureTrait::loadFixture() for
   *   argument details.
   * @param array{'allowInvalid': bool, 'contentIsValid': bool} $scenarioConfig
   *   An associative array of test scenario configuration values as received
   *   from providerSetContent() and passed through testSetContent().
   *   - 'allowInvalid': (bool) The boolean option for the $allowInvalid
   *     argument to be passed to setContent().
   *   - 'contentIsValid': (bool) A boolean argument declaring whether the
   *     validation operation is expected to succeed or fail.
   *
   * @covers ::getExceptions
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent::validateContent()
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent::setContent()
   *
   * @dataProvider providerSetContent
   * @depends testSetContent
   */
  public function testGetExceptions(
    array $content,
    string $schemaFixture,
    array $scenarioConfig,
  ): void {
    // Parse the scenario config for local use.
    [
      'allowInvalid' => $allowInvalid,
      'contentIsValid' => $contentIsValid,
    ] = $scenarioConfig;

    // Setup data fixtures.
    $this->setupSetContentTests($content, $schemaFixture);

    // Expect a thrown exception if the expected result is FALSE and invalid
    // content is not allowed.
    $expectExceptionThrown = !$contentIsValid && !$allowInvalid;
    if ($expectExceptionThrown) {
      $this->expectException(SchemaValidationException::class);
    }

    // Trigger the method.
    $this->sut->setContent($content, $allowInvalid);

    // An array should always be returned.
    $exceptions = $this->sut->getExceptions();
    $this->assertIsArray($exceptions);

    // If content is invalid, expect exceptions to have been recorded.
    $expectExceptions = !$contentIsValid && $allowInvalid;
    if (!$expectExceptionThrown && $expectExceptions) {
      $this->assertNotEmpty($exceptions);
      $this->assertContainsOnlyInstancesOf(SchemaValidationException::class, $exceptions);
      $this->assertCount(1, $exceptions);
    }
  }

  /**
   * Data provider for testSetContent().
   */
  public static function providerSetContent(): array {
    return [
      'valid content - allow invalid' => [
        // Valid schema data to test.
        [
          'text' => 'Example text',
        ],
        // The fixture to load.
        'schemas/required_content.json',
        [
          'allowInvalid' => TRUE,
          'contentIsValid' => TRUE,
        ],
      ],
      'valid content - disallow invalid' => [
        // Valid schema data to test.
        [
          'text' => 'Example text',
        ],
        // The fixture to load.
        'schemas/required_content.json',
        [
          'allowInvalid' => FALSE,
          'contentIsValid' => TRUE,
        ],
      ],
      'invalid content - allow invalid' => [
        // Invalid schema data missing required properties.
        [
          'optional_text' => 'Example text',
        ],
        // The fixture to load.
        'schemas/required_content.json',
        [
          'allowInvalid' => TRUE,
          'contentIsValid' => FALSE,
        ],
      ],
      'invalid content - disallow invalid' => [
        // Invalid schema data missing required properties.
        [
          'optional_text' => 'Example text',
        ],
        // The fixture to load.
        'schemas/required_content.json',
        [
          'allowInvalid' => FALSE,
          'contentIsValid' => FALSE,
        ],
      ],
    ];
  }

  /**
   * Setup testing fixtures for scenarios provided by providerSetContent().
   *
   * @param array $content
   *   Content to be validated against the schema.
   * @param string $schemaFixture
   *   A path to a schema fixture for validation. See
   *   \Drupal\Tests\patternkit\Traits\SchemaFixtureTrait::loadFixture() for
   *   argument details.
   *
   * @return \Drupal\patternkit\Event\PatternUpdateEvent
   *   The instantiated update event.
   */
  protected function setupSetContentTests(
    array $content,
    string $schemaFixture,
  ): PatternUpdateEvent {
    // Prepare the schema string to load from the pattern.
    $schema_string = $this->loadFixture($schemaFixture);
    $this->assertNotFalse($schema_string);
    $this->newPatternProphecy->getSchema()->willReturn($schema_string);

    // Prepare the content to be loaded from the block on event creation.
    $this->patternkitBlockEntityProphecy->getData()->willReturn($content);

    // Instantiate the update event for testing.
    return $this->sut = $this->getEvent();
  }

  /**
   * Instantiate an event with prophecy dependencies injected for testing.
   *
   * @return \Drupal\patternkit\Event\PatternUpdateEvent
   *   The instantiated update event.
   */
  protected function getEvent(): PatternUpdateEvent {
    return new PatternUpdateEvent(
      $this->patternkitBlockProphecy->reveal(),
      $this->oldPatternProphecy->reveal(),
      $this->newPatternProphecy->reveal(),
    );
  }

}
