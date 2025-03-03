<?php

namespace Drupal\patternkit;

/**
 * Defines events for the patternkit module.
 *
 * @see \Drupal\patternkit\Event\PatternUpdateEvent
 * @see \Drupal\patternkit\Event\PatternRenderFailureEvent
 */
final class PatternkitEvents {

  /**
   * Name of the event fired when a component's pattern entity is updated.
   *
   * This event allows modules to react to pattern version updates and alter
   * the configured content as needed to match new schema requirements.
   *
   * @Event
   *
   * @see \Drupal\patternkit\Event\PatternUpdateEvent
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::updatePattern()
   *
   * @var string
   */
  const PATTERN_UPDATE = 'patternkit.pattern_update';

  /**
   * Name of the event fired when a pattern fails to render.
   *
   * This event allows modules to react to exceptions during the render process
   * of a Pattern instance.
   *
   * @Event
   *
   * @see \Drupal\patternkit\Event\PatternRenderFailureEvent
   * @see \Drupal\patternkit\EventSubscriber\RenderFailureErrorDisplaySubscriber
   * @see \Drupal\patternkit\Element\Pattern
   * @see \Drupal\patternkit\Element\PatternError
   *
   * @var string
   */
  const RENDER_FAILURE = 'patternkit.render_failure';

}
