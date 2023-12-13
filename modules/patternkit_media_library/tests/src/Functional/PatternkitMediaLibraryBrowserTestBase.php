<?php

namespace Drupal\Tests\patternkit_media_library\Functional;

use Drupal\Tests\patternkit\Functional\PatternkitBrowserTestBase;

/**
 * Base test class for Patternkit Media Library functional tests.
 *
 * @group patternkit
 * @group patternkit_media_library
 */
abstract class PatternkitMediaLibraryBrowserTestBase extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_media_library',
    'media_library',
  ];

}
