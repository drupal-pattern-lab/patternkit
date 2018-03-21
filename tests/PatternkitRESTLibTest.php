<?php

namespace Patternkit\Tests;

use PatternkitRESTDrupalCachedLib;

/**
 * @coversDefaultClass \RESTLib.
 */
class PatternkitRESTLibTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers __construct().
   *
   * @wip
   */
  public function testConstructor() {
    $lib = new PatternkitRESTDrupalCachedLib();

    $this->assertNotEmpty($lib, 'The new lib was empty');
  }

}
