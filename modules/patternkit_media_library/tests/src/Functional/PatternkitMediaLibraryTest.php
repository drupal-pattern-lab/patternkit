<?php

namespace Drupal\Tests\patternkit_media_library\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * Basic E2E testing for Patternkit Media Library.
 */
#[Group('patternkit')]
#[Group('patternkit_media_library')]
class PatternkitMediaLibraryTest extends PatternkitMediaLibraryBrowserTestBase {

  /**
   * Verify successful page load after installation.
   */
  public function testSiteInstall() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('Test node title');
    $this->assertSession()->pageTextContains('Test node body');
  }

}
