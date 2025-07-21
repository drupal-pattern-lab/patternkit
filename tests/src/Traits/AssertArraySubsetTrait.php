<?php

namespace Drupal\Tests\patternkit\Traits;

/**
 * A helper trait to test array subset values.
 *
 * This trait is only intended for use in tests.
 *
 * @internal
 */
trait AssertArraySubsetTrait {

  /**
   * Test the presence of all expected values within an array.
   *
   * @param array $expected
   *   The associative array to test for existence within the target array.
   * @param array $actual
   *   The target array to be tested.
   */
  protected function assertArraySubset(array $expected, array $actual): void {
    foreach ($expected as $key => $value) {
      $this->assertArrayHasKey($key, $actual);
      $this->assertEquals($value, $actual[$key]);
    }
  }

}
