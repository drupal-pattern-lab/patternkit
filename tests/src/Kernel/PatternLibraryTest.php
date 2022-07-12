<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\LibraryInterface;

/**
 * Test pattern library functionality.
 *
 * @group patternkit
 * @group legacy
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
    // @todo Remove this once the referenced deprecation is resolved.
    // @see https://www.drupal.org/node/3000490
    // @see https://www.drupal.org/project/patternkit/issues/3295521
    $this->expectDeprecation('ModuleHandlerInterface::implementsHook() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Instead you should use ModuleHandlerInterface::hasImplementations()  with the $modules argument. See https://www.drupal.org/node/3000490');

    /** @var \Drupal\patternkit\Asset\LibraryInterface $library */
    $library = $this->container->get('patternkit.asset.library');
    $this->assertNotNull($library);
    $this->assertInstanceOf(LibraryInterface::class, $library);

    $pattern_definitions = $library->getAssets();
    $this->assertCount(3, $pattern_definitions);
  }

}
