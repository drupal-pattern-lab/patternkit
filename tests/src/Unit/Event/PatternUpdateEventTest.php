<?php

namespace Drupal\Tests\patternkit\Event;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Event\PatternUpdateEvent;
use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests for the PatternUpdateEvent class.
 *
 * @coversDefaultClass \Drupal\patternkit\Event\PatternUpdateEvent
 *
 * @group patternkit
 */
class PatternUpdateEventTest extends UnitTestCase {

  use ProphecyTrait;
  use PatternkitMockHelperTrait;

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
   * Seed data for assignment as original pattern block data.
   *
   * @var array|string[]
   */
  protected array $unchangedData = [
    'unchanged' => 'data',
  ];

  /**
   * Seed data for assignment as updated pattern block data.
   *
   * @var array|string[]
   */
  protected array $updatedData = [
    'updated' => 'data',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->oldPatternProphecy = $this->getMockPatternEntity([
      'name' => 'Old Pattern Name',
      'asset_id' => '@patternkit/old/pattern',
    ]);
    $this->newPatternProphecy = $this->getMockPatternEntity([
      'name' => 'New Pattern Name',
      'asset_id' => '@patternkit/new/pattern',
    ]);

    $this->patternkitBlockProphecy = $this->getMockPatternkitBlockPlugin([
      'block' => [
        'data' => $this->unchangedData,
      ],
      'pattern' => $this->newPatternProphecy->reveal(),
      'base_pattern' => $this->oldPatternProphecy->reveal(),
      'pattern_id' => '@patternkit/new/pattern',
    ]);
  }

  /**
   * @covers ::isAltered
   * @covers ::__construct
   * @covers ::setContent
   */
  public function testIsAltered() {
    $event = $this->getEvent();

    // Initially, the content should not be altered.
    $this->assertFalse($event->isAltered());

    // Alter the content and check again.
    $event->setContent($this->updatedData);
    $this->assertTrue($event->isAltered());
  }

  /**
   * @covers ::isValid
   * @covers ::__construct
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent::getContent()
   */
  public function testIsValid() {
    // Create an event that will pass validation.
    $validEvent = $this->getEvent(shouldFailValidation: FALSE);

    // Confirm the initial call and subsequent calls all return TRUE.
    $this->assertTrue($validEvent->isValid());
    $this->assertTrue($validEvent->isValid());

    // Create an event that will fail validation.
    $invalidEvent = $this->getEvent(shouldFailValidation: TRUE);

    // Confirm the initial call and subsequent calls all return TRUE.
    $this->assertFalse($invalidEvent->isValid());
    $this->assertFalse($invalidEvent->isValid());
  }

  /**
   * @covers ::getOriginalPattern
   * @covers ::__construct
   */
  public function testGetOriginalPattern() {
    $event = $this->getEvent();

    $this->assertSame($this->oldPatternProphecy->reveal(), $event->getOriginalPattern());
  }

  /**
   * @covers ::getOriginalPatternAssetId
   * @covers ::__construct
   */
  public function testGetOriginalPatternAssetId() {
    $event = $this->getEvent();

    $this->assertEquals('@patternkit/old/pattern', $event->getOriginalPatternAssetId());
  }

  /**
   * @covers ::setContent
   * @covers ::__construct
   * @covers \Drupal\patternkit\Event\PatternUpdateEvent
   */
  public function testSetContentException() {
    // Enforce a content validation failure for testing.
    $event = $this->getEvent(shouldFailValidation: TRUE);

    // Validate the initial content.
    $this->assertEquals($this->unchangedData, $event->getContent());

    // Invalid content should throw an exception.
    $this->expectException(SchemaValidationException::class);
    $event->setContent(['invalid' => 'content']);
  }

  /**
   * @covers ::getPlugin
   * @covers ::__construct
   */
  public function testGetPlugin() {
    $event = $this->getEvent();

    $this->assertSame($this->patternkitBlockProphecy->reveal(), $event->getPlugin());
  }

  /**
   * @covers ::getPattern
   * @covers ::__construct
   */
  public function testGetPattern() {
    $event = $this->getEvent();

    $this->assertSame($this->newPatternProphecy->reveal(), $event->getPattern());
  }

  /**
   * @covers ::getPatternName
   * @covers ::__construct
   */
  public function testGetPatternName() {
    $event = $this->getEvent();

    $this->assertEquals('New Pattern Name', $event->getPatternName());
  }

  /**
   * @covers ::getOriginalPatternName
   * @covers ::__construct
   */
  public function testGetOriginalPatternName() {
    $event = $this->getEvent();

    $this->assertEquals('Old Pattern Name', $event->getOriginalPatternName());
  }

  /**
   * @covers ::getPatternAssetId
   * @covers ::__construct
   */
  public function testGetPatternAssetId() {
    $event = $this->getEvent();

    $this->assertEquals('@patternkit/new/pattern', $event->getPatternAssetId());
  }

  /**
   * @covers ::getContent
   * @covers ::__construct
   */
  public function testGetContent() {
    $event = $this->getEvent();

    $this->assertEquals($this->unchangedData, $event->getContent());
  }

  /**
   * @covers ::getOriginalContent
   * @covers ::getContent
   * @covers ::setContent
   * @covers ::isAltered
   * @covers ::__construct
   */
  public function testContentUpdating() {
    $event = $this->getEvent();

    // Confirm both the current and original content are as expected.
    $this->assertEquals($this->unchangedData, $event->getContent());
    $this->assertEquals($this->unchangedData, $event->getOriginalContent());

    // Change the content.
    $result = $event->setContent($this->updatedData);

    // Confirm setContent() returned the event object for chaining.
    $this->assertSame($event, $result);

    // Confirm it is flagged as having been altered.
    $this->assertTrue($event->isAltered());

    // Confirm each type of content is returned as expected.
    $this->assertEquals($this->updatedData, $event->getContent());
    $this->assertEquals($this->unchangedData, $event->getOriginalContent());
  }

  /**
   * Get a new PatternUpdateEvent instance for testing.
   *
   * @param bool $shouldFailValidation
   *   (Optional) A boolean value determining whether schema validation should
   *   succeed or fail and throw an exception. Defaults to FALSE, resulting in
   *   schema validation always passing.
   *
   * @return \Drupal\patternkit\Event\PatternUpdateEvent
   *   The instantiated PatternUpdateEvent object with configured prophecies
   *   provided.
   */
  protected function getEvent(bool $shouldFailValidation = FALSE): PatternUpdateEvent {
    return new TestPatternUpdateEvent(
      $this->patternkitBlockProphecy->reveal(),
      $this->oldPatternProphecy->reveal(),
      $this->newPatternProphecy->reveal(),
      $shouldFailValidation,
    );
  }

}

/**
 * A test class for bypassing schema validation during unit tests.
 *
 * Only intended for use in testing.
 *
 * @internal
 */
class TestPatternUpdateEvent extends PatternUpdateEvent {

  /**
   * Creates a test update event instance with configurable validation behavior.
   *
   * @param \Drupal\patternkit\Plugin\Block\PatternkitBlock $plugin
   *   The patternkit block plugin for the component being updated.
   * @param \Drupal\patternkit\Entity\PatternInterface $oldPattern
   *   The original pattern instance being updated.
   * @param \Drupal\patternkit\Entity\PatternInterface $newPattern
   *   The new pattern instance being updated to.
   * @param bool $shouldFailValidation
   *   (Optional) A boolean value determining whether schema validation should
   *   succeed or fail and throw an exception. Defaults to FALSE, resulting in
   *   schema validation always passing.
   */
  public function __construct(
    PatternkitBlock $plugin,
    PatternInterface $oldPattern,
    PatternInterface $newPattern,
    public readonly bool $shouldFailValidation = FALSE,
  ) {
    parent::__construct($plugin, $oldPattern, $newPattern);
  }

  /**
   * {@inheritdoc}
   */
  public function validateContent(array $content): bool {
    // Track an exception if validation should fail.
    if ($this->shouldFailValidation) {
      $this->exceptions[] = new SchemaValidationException();
      return FALSE;
    }

    return TRUE;
  }

}
