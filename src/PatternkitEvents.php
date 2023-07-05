<?php

namespace Drupal\patternkit;

/**
 * Defines events for the patternkit module.
 *
 * @see \Drupal\patternkit\Event\PatternUpdateEvent
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

}
