<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\LibraryInterface;

/**
 * Test pattern library functionality.
 *
 * @group patternkit
 */
class PatternLibraryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'patternkit_example',
    'text',
  ];

  /**
   * Test that example module patterns are discovered successfully.
   */
  public function testExamplePatternsAreDiscovered() {
    /** @var \Drupal\patternkit\Asset\LibraryInterface $library */
    $library = $this->container->get('patternkit.asset.library');
    $this->assertNotNull($library);
    $this->assertInstanceOf(LibraryInterface::class, $library);

    $pattern_definitions = $library->getAssets();
    $this->assertCount(3, $pattern_definitions);
  }

}
