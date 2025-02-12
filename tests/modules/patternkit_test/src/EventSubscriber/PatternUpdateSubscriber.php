<?php

namespace Drupal\patternkit_test\EventSubscriber;

use Drupal\patternkit\Event\PatternUpdateEvent;
use Drupal\patternkit\EventSubscriber\PatternUpdateEventSubscriberBase;

/**
 * An event subscriber for testing the patternkit pattern update event system.
 *
 * @see \Drupal\patternkit\PatternkitEvents
 * @see \Drupal\patternkit\Event\PatternUpdateEvent
 */
class PatternUpdateSubscriber extends PatternUpdateEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable(PatternUpdateEvent $event): bool {
    $original_pattern = $event->getOriginalPattern();
    return $original_pattern->getAssetId() == '@patternkit/atoms/example/src/example';
  }

  /**
   * {@inheritdoc}
   */
  public function doUpdate(PatternUpdateEvent $event): void {
    $content = $event->getContent();
    // Append a string to the 'text' value of a pattern's content if it exists
    // for validation after a pattern update.
    if (isset($content['text'])) {
      $content['text'] .= ' (Altered)';
      $event->setContent($content);
    }
  }

}
