<?php

namespace Drupal\Tests\patternkit\Traits;

/**
 * A helper trait to consistently decode JSON strings throughout test classes.
 *
 * This trait is only intended for use in tests.
 */
trait JsonDecodeTrait {

  /**
   * Encapsulate the process for decoding provided JSON values.
   *
   * @param string $schema
   *   The JSON string value to be decoded.
   * @param bool $associative
   *   Whether to cast JSON objects as arrays when decoding.
   *
   * @return mixed
   *   The decoded JSON value.
   *
   * @throws \JsonException
   */
  protected function decodeJson(string $schema, bool $associative = FALSE) {
    return json_decode($schema, $associative, 512, JSON_THROW_ON_ERROR);
  }

}
