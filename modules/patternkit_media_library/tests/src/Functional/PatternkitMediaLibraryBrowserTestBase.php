<?php

namespace Drupal\Tests\patternkit_media_library\Functional;

use Drupal\Tests\patternkit\Functional\PatternkitBrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Base test class for Patternkit Media Library functional tests.
 */
#[Group('patternkit')]
#[Group('patternkit_media_library')]
abstract class PatternkitMediaLibraryBrowserTestBase extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_media_library',
    'media_library',
  ];

}
