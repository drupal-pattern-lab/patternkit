<?php

namespace Drupal\Tests\patternkit\Traits;

/**
 * A helper trait to load schemas from fixture files.
 *
 * This trait is only intended for use in tests.
 */
trait SchemaFixtureTrait {

  /**
   * Load file content from a fixture file.
   *
   * @param string $path
   *   The path to a file within the 'tests/fixtures' directory to be loaded.
   * @param string|null $module_path
   *   (optional) The path to the module to load fixtures from. If not provided,
   *   the path to the patternkit module is assumed.
   *
   * @return false|string
   *   Returns the contents of the fixture file or FALSE if it was unable to
   *   read.
   */
  public function loadFixture(string $path, ?string $module_path = NULL) {
    $module_path ??= __DIR__ . '/../../..';
    $fixture_path = $module_path . '/tests/fixtures/';

    return file_get_contents($fixture_path . $path);
  }

}
