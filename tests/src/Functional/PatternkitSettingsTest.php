<?php

namespace Drupal\Tests\patternkit\Functional;

/**
 * E2E testing for the Patternkit settings forms.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Form\PatternkitSettingsForm
 */
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
    $assert->pageTextContains('Prevent saving invalid pattern content');

    // The checkbox should be checked by default on a clean install.
    $assert->checkboxChecked('render_validation_on_save');

    // Submitting with the checkbox unchecked should persist FALSE to config.
    $this->submitForm(['render_validation_on_save' => FALSE], 'Save configuration');
    $this->assertFalse(\Drupal::config('patternkit.settings')->get('render_validation_on_save'));
  }

}
