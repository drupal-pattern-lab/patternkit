<?php

namespace Drupal\patternkit\EventSubscriber;

use Drupal\patternkit\Event\PatternRenderFailureEvent;
use Drupal\patternkit\PatternkitEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber to display error output when patterns fail to render.
 *
 * @see \Drupal\patternkit\PatternkitEvents::RENDER_FAILURE
 * @see \Drupal\patternkit\Event\PatternRenderFailureEvent
 */
class RenderFailureErrorDisplaySubscriber implements EventSubscriberInterface {

  /**
   * The event priority set for this event subscriber.
   *
   * The is intentionally exposed as a constant for easy reference by other
   * subscribers if they need to ensure they are prioritized higher than this
   * subscriber.
   */
  const EVENT_PRIORITY = -500;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set a low priority to encourage this as the last executed subscriber to
    // handle failures that weren't otherwise addressed.
    $events[PatternkitEvents::RENDER_FAILURE] = ['onRenderFailure', static::EVENT_PRIORITY];

    return $events;
  }

  /**
   * Replace the render element with a 'pattern_error' render element.
   *
   * If the render failure has not yet been handled, replace the failed render
   * element with an alternative 'pattern_error' render element supporting
   * better error display and debugging ability.
   *
   * @param \Drupal\patternkit\Event\PatternRenderFailureEvent $event
   *   The render failure event.
   *
   * @see \Drupal\patternkit\Element\PatternError
   */
  public function onRenderFailure(PatternRenderFailureEvent $event) {
    // Only make changes if the failure has not yet been handled.
    if (!$event->isHandled()) {
      $originalElement = $event->getOriginalElement();

      // Replace the pattern output with an error element for more
      // sophisticated output handling.
      // @see \Drupal\patternkit\Element\PatternError
      $elements = [];
      $elements['error'] = [
        '#type' => 'pattern_error',
        '#pattern' => $originalElement['#pattern'] ?? NULL,
        '#config' => $originalElement['#config'] ?? [],
        '#context' => $originalElement['#context'] ?? [],
        '#exception' => $event->getException(),
      ];

      $event->setElement($elements)
        ->isHandled(TRUE);
    }
  }

}
