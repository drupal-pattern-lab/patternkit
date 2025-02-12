<?php

namespace Drupal\Tests\patternkit\EventSubscriber;

use Drupal\patternkit\Event\PatternUpdateEvent;
use Drupal\patternkit\EventSubscriber\PatternUpdateEventSubscriberBase;
use Drupal\patternkit\PatternkitEvents;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests for the PatternUpdateEventSubscriberBase class.
 *
 * @coversDefaultClass \Drupal\patternkit\EventSubscriber\PatternUpdateEventSubscriberBase
 *
 * @group patternkit
 */
class PatternUpdateEventSubscriberBaseTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * A mocked PatternUpdateEvent instance for triggering subscriber events.
   *
   * @var \Drupal\patternkit\Event\PatternUpdateEvent
   */
  protected PatternUpdateEvent $event;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->event = $this->prophesize(PatternUpdateEvent::class)
      ->reveal();
  }

  /**
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents(): void {
    $events = PatternUpdateEventSubscriberBase::getSubscribedEvents();
    $this->assertArrayHasKey(PatternkitEvents::PATTERN_UPDATE, $events);

    $callbacks = $events[PatternkitEvents::PATTERN_UPDATE];
    $this->assertEquals(['onPatternUpdate', PatternUpdateEventSubscriberBase::UPDATE_PRIORITY], $callbacks);
  }

  /**
   * Test onPatternUpdate when an event is applicable.
   *
   * @covers ::onPatternUpdate
   */
  public function testOnPatternUpdateIsApplicable(): void {
    $subscriber = $this->getMockBuilder(PatternUpdateEventSubscriberBase::class)
      ->onlyMethods(['doUpdate', 'isApplicable'])
      ->getMock();

    // Indicate the event is applicable.
    $subscriber->expects($this->once())
      ->method('isApplicable')
      ->with($this->event)
      ->willReturn(TRUE);

    // Expect the doUpdate() method to be called for applicable events.
    $subscriber->expects($this->once())
      ->method('doUpdate')
      ->with($this->event);

    // Trigger the callback as if from the event dispatcher.
    $subscriber->onPatternUpdate($this->event);
  }

  /**
   * Test onPatternUpdate when an event is not applicable.
   *
   * @covers ::onPatternUpdate
   */
  public function testOnPatternUpdateNotApplicable(): void {
    $subscriber = $this->getMockBuilder(PatternUpdateEventSubscriberBase::class)
      ->onlyMethods(['doUpdate', 'isApplicable'])
      ->getMock();

    // Indicate the event is not applicable.
    $subscriber->expects($this->once())
      ->method('isApplicable')
      ->with($this->event)
      ->willReturn(FALSE);

    // Expect the doUpdate() method to never be called.
    $subscriber->expects($this->never())
      ->method('doUpdate');

    // Trigger the callback as if from the event dispatcher.
    $subscriber->onPatternUpdate($this->event);
  }

}
