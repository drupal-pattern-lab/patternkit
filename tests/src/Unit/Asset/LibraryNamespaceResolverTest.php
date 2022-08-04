<?php

namespace Drupal\Tests\patternkit\Unit\Asset;

use Drupal\patternkit\Asset\LibraryNamespaceResolver;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the LibraryNamespaceResolver class.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\LibraryNamespaceResolver
 * @group patternkit
 */
class LibraryNamespaceResolverTest extends UnitTestCase {

  /**
   * @covers ::getLibraryFromNamespace
   * @group legacy
   */
  public function testLibraryDeprecationWarning() {
    $namespaceResolver = $this->getMockBuilder(LibraryNamespaceResolver::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $namespaceResolver->method('get')
      ->with('@my_namespace')
      ->willReturn([
        'extension' => 'my_module',
        'deprecated' => 'The %library_id% library is deprecated.',
      ]);

    $this->expectDeprecation('The my_module/my_namespace library is deprecated.');
    $namespaceResolver->getLibraryFromNamespace('@my_namespace');
  }

}
