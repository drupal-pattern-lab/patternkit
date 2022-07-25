<?php

namespace Drupal\Tests\patternkit\Unit\Asset;

use Drupal\patternkit\Asset\Library;

/**
 * An override of Drupal\patternkit\Asset\Library for testing.
 */
class TestLibrary extends Library {

  /**
   * A mapping of path return values for drupalGetPath.
   *
   * @var array
   *
   * @see \Drupal\Tests\patternkit\Asset\TestLibrary::drupalGetPath()
   */
  protected $pathMap = [];

  /**
   * An override of the parent method to replace the global function call.
   *
   * The return value for this method may be set using setPathMapping().
   */
  protected function drupalGetPath($type, $name): string {
    return $this->pathMap[$type][$name];
  }

  /**
   * Set a map of return values for drupalGetPath based on passed arguments.
   *
   * @param array $map
   *   A two-dimensional array of return values keyed by:
   *   - $type
   *   - $name
   *
   * @return void
   */
  public function setPathMapping(array $map): void {
    $this->pathMap = $map;
  }

}
