<?php

namespace Drupal\Tests\patternkit\Unit\Asset;

use Drupal\patternkit\Asset\PatternDependencyResolver;

/**
 * An overridden PatternDependencyResolver allowing dependencies to be set.
 */
class TestPatternDependencyResolver extends PatternDependencyResolver {

  /**
   * Set dependencies for pattern dependency resolution.
   *
   * @param array $dependencies
   *   An associative array of pattern dependencies keyed by pattern ID.
   */
  public function setDependencies(array $dependencies): void {
    $this->patternDependencies = $dependencies;
  }

}
