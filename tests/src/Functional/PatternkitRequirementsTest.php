<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests hook_requirements for patternkit validation enforcement.
 *
 * @group patternkit
 */
class PatternkitRequirementsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['patternkit'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that no requirement warning appears when toggle is ON (default).
   */
  public function testNoWarningWhenEnforcementEnabled(): void {
    // Default config: render_validation_on_save is TRUE (or unset -> TRUE).
    $requirements = patternkit_requirements('runtime');
    $this->assertArrayNotHasKey('patternkit_validation_enforcement', $requirements);
  }

  /**
   * Tests that a warning requirement appears when enforcement is disabled.
   */
  public function testWarningWhenEnforcementDisabled(): void {
    $this->config('patternkit.settings')
      ->set('render_validation_on_save', FALSE)
      ->save();

    $requirements = patternkit_requirements('runtime');
    $this->assertArrayHasKey('patternkit_validation_enforcement', $requirements);
    $this->assertEquals(REQUIREMENT_WARNING, $requirements['patternkit_validation_enforcement']['severity']);
    $this->assertNotEmpty($requirements['patternkit_validation_enforcement']['description']);
  }

  /**
   * Tests that non-runtime phases return an empty requirements array.
   */
  public function testNonRuntimePhaseReturnsEmpty(): void {
    $requirements = patternkit_requirements('install');
    $this->assertEmpty($requirements);

    $requirements = patternkit_requirements('update');
    $this->assertEmpty($requirements);
  }

  /**
   * Tests that the status report page shows the warning when toggle is OFF.
   */
  public function testStatusReportShowsWarning(): void {
    $this->config('patternkit.settings')
      ->set('render_validation_on_save', FALSE)
      ->save();

    $admin = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin);
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('Patternkit validation enforcement');
    $this->assertSession()->pageTextContains('Patternkit validation enforcement is disabled');
  }

}
