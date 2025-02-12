<?php

namespace Drupal\patternkit\EventSubscriber;

use Drupal\patternkit\Event\PatternUpdateEvent;
use Drupal\patternkit\PatternkitEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A base event subscriber class to extend when subscribing to pattern updates.
 *
 * @see \Drupal\patternkit\PatternkitEvents::PATTERN_UPDATE
 * @see \Drupal\patternkit\Event\PatternUpdateEvent
 */
abstract class PatternUpdateEventSubscriberBase implements EventSubscriberInterface {

  /**
   * The event subscriber priority for responding to the pattern update event.
   *
   * This may be overridden by inheriting classes to customize the order
   * subscribers are executed during an update event.
   */
  const UPDATE_PRIORITY = 200;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[PatternkitEvents::PATTERN_UPDATE] = [
      'onPatternUpdate',
      static::UPDATE_PRIORITY,
    ];

    return $events;
  }

  /**
   * React to the pattern update process and log alter text values.
   *
   * Unless further customization is needed, inheriting classes should override
   * the related `isApplicable()` and `doUpdate()` methods instead of this one.
   *
   * @param \Drupal\patternkit\Event\PatternUpdateEvent $event
   *   The pattern update event containing all update information.
   *
   * @see static::isApplicable()
   * @see static::doUpdate()
   */
  public function onPatternUpdate(PatternUpdateEvent $event): void {
    if ($this->isApplicable($event)) {
      $this->doUpdate($event);
    }
  }

  /**
   * Test whether an update is applicable for the current event.
   *
   * Inheriting classes should override this method to apply their own
   * conditional logic to determine if their subscriber should be applied to
   * the specific pattern update in progress.
   *
   * @param \Drupal\patternkit\Event\PatternUpdateEvent $event
   *   The pattern update event in progress.
   *
   * @return bool
   *   Returns true if this update subscriber is applicable for the current
   *   event. Returns false otherwise.
   *
   * @see static::onPatternUpdate()
   */
  abstract public function isApplicable(PatternUpdateEvent $event): bool;

  /**
   * Implement update behavior for the active pattern update event.
   *
   * Inheriting classes should override this method to apply their own
   * update logic and applicable changes. This method assumes applicability of
   * the event has already been confirmed with the `isApplicable()` method.
   *
   * @param \Drupal\patternkit\Event\PatternUpdateEvent $event
   *   The pattern update event in progress.
   *
   * @see static::onPatternUpdate()
   * @see static::isApplicable()
   */
  abstract protected function doUpdate(PatternUpdateEvent $event): void;

}
