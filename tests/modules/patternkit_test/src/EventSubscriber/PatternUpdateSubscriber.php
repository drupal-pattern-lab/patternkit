<?php

namespace Drupal\patternkit_test\EventSubscriber;

use Drupal\patternkit\Event\PatternUpdateEvent;
use Drupal\patternkit\PatternkitEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber for the patternkit pattern update process.
 *
 * @see \Drupal\patternkit\PatternkitEvents
 * @see \Drupal\patternkit\Event\PatternUpdateEvent
 */
class PatternUpdateSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PatternkitEvents::PATTERN_UPDATE] = ['onPatternUpdate', 200];

    return $events;
  }

  /**
   * React to the pattern update process and log alter text values.
   *
   * @param \Drupal\patternkit\Event\PatternUpdateEvent $event
   *   The pattern update event containing all update information.
   */
  public function onPatternUpdate(PatternUpdateEvent $event): void {
    $original_pattern = $event->getOriginalPattern();

    // Abort here if this isn't an applicable pattern.
    if ($original_pattern->getAssetId() !== '@patternkit/atoms/example/src/example') {
      return;
    }
    $content = $event->getContent();

    // Append a string to the 'text' value of a pattern's content if it exists
    // for validation after a pattern update.
    if (isset($content['text'])) {
      $content['text'] .= ' (Altered)';
      $event->setContent($content);
    }
  }

}
