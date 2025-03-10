<?php

namespace Drupal\Tests\patternkit\Kernel\Loader;

use Drupal\patternkit\Loader\PatternLibraryLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the PatternLibraryLoader Twig loader service.
 */
#[Group('Patternkit')]
#[CoversClass(PatternLibraryLoader::class)]
class PatternLibraryLoaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'patternkit_example',
    'system',
    'text',
  ];

  /**
   * Test to confirm pattern namespaces are discovered and loaded.
   */
  public function testPatternNamespaceLoading() {
    $patternLoader = $this->container->get('twig.loader.patternlibrary');
    $namespaces = $patternLoader->getNamespaces();

    $this->assertIsArray($namespaces);
    $this->assertNotEmpty($namespaces, 'Pattern loader namespaces were not expected to be empty.');
    $this->assertContains('patternkit', $namespaces, 'A "patternkit" namespace was expected for registration.');
    $this->assertEquals(
      [$this->getModulePath('patternkit_example') . '/lib/patternkit/src'],
      $patternLoader->getPaths('patternkit'),
      'The path registered for the "patternkit" namespace did not match expectations.');
  }

}
