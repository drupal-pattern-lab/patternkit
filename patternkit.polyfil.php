<?php

/**
 * @file
 * Polyfil functions for PHP version compatibility to be loaded if needed.
 */

if (!function_exists("array_is_list")) {

  /**
   * Test whether an array is associative or numerically indexed.
   *
   * @param array $array
   *   The array for testing.
   *
   * @return bool
   *   TRUE if all indexes are numeric and sequentially specified. FALSE if
   *   there are any non-numeric indices, or they are out of order.
   *
   * @see https://www.php.net/manual/en/function.array-is-list
   */
  function array_is_list(array $array): bool {
    $i = -1;
    foreach ($array as $k => $v) {
      ++$i;
      if ($k !== $i) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
