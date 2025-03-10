<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\patternkit\Form\PatternkitSettingsForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E testing for the Patternkit settings forms.
 */
#[Group('patternkit')]
#[CoversClass(PatternkitSettingsForm::class)]
class PatternkitSettingsTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
  ];

  /**
   * Test that the settings form loads as expected.
   */
  public function testPatternkitSettingsForm() {
    $account = $this->drupalCreateUser(['administer patternkit']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/user-interface/patternkit');

    $assert = $this->assertSession();

    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Unable to load Patternkit libraries list. Check the logs for more information.');
    $assert->pageTextNotContains('Settings are unavailable when Pattern libraries fail to load to prevent config errors.');
  }

}
