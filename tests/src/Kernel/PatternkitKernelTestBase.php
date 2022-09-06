<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base test class for Patternkit kernel tests.
 */
abstract class PatternkitKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'text',
  ];

}
