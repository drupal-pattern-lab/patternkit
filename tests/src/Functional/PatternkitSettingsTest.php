<?php

namespace Drupal\Tests\patternkit\Functional;

/**
 * E2E testing for the Patternkit settings forms.
 *
 * @coversDefaultClass \Drupal\patternkit\Form\PatternkitSettingsForm
 * @group patternkit
 */
class PatternkitSettingsTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
    'patternkit_example',
  ];

  /**
   * Test that the settings form loads as expected.
   */
  public function testPatternkitSettingsForm() {
    $account = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/user-interface/patternkit');

    $assert = $this->assertSession();

    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Unable to load Patternkit libraries list. Check the logs for more information.');
    $assert->pageTextNotContains('Settings are unavailable when Pattern libraries fail to load to prevent config errors.');
  }

}
