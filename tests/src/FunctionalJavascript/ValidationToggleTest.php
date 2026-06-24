<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\FunctionalJavascript;

/**
 * Tests toggle-aware client-side validation in the JSON Editor.
 *
 * @group patternkit
 */
class ValidationToggleTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
  ];

  /**
   * The display name of the test pattern in the block picker.
   */
  const EXAMPLE_PATTERN = '[Patternkit] Example';

  /**
   * Navigate to the node layout page as the editor user.
   */
  protected function goToLayout(): void {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/1/layout');
  }

  /**
   * Tests that toggle ON blocks submission with errors (blocking behavior).
   */
  public function testToggleOnBlocksSubmission(): void {
    $assert = $this->assertSession();

    // Ensure the toggle is ON (default).
    $this->config('patternkit.settings')
      ->set('render_validation_on_save', TRUE)
      ->save();

    $this->goToLayout();
    $this->addPatternBlock(static::EXAMPLE_PATTERN);
    $this->waitForJsonEditorForm();

    // Leave the required 'text' field empty and attempt to add the block.
    // When enforcement is ON, the JS beforeSubmit handler blocks the AJAX call
    // entirely (returns false), so no AJAX request is started. Wait for the
    // validation error display instead of assertWaitOnAjaxRequest().
    $this->getSession()->getPage()->pressButton('Add block');
    $assert->waitForElementVisible('css', '.errmsg, .invalid-feedback, .je-error');

    // The off-canvas should still be open (submission was blocked).
    $assert->elementExists('css', '#drupal-off-canvas');

    // No warning message should be visible (enforcement ON does not show one).
    $assert->elementNotExists('css', '[data-drupal-message-id="patternkit-validation-warning"]');

    // The button should NOT have been relabeled to 'with issues'.
    $button = $assert->elementExists('css', '[data-drupal-selector="edit-actions-submit"]');
    $this->assertStringNotContainsString('with issues', $button->getValue());
  }

  /**
   * Tests toggle OFF shows warning on first submit (warning-mode UX).
   *
   * Verifies the client-side warning-mode UX: when the toggle is OFF and there
   * are validation errors, the first submit enters warning state with a visible
   * message, CSS class, and relabeled button. The editor can then fix the
   * issues and submit normally.
   */
  public function testToggleOffShowsWarningOnFirstSubmit(): void {
    $assert = $this->assertSession();

    // Disable enforcement.
    $this->config('patternkit.settings')
      ->set('render_validation_on_save', FALSE)
      ->save();

    $this->goToLayout();
    $this->addPatternBlock(static::EXAMPLE_PATTERN);
    $this->waitForJsonEditorForm();

    // Leave the required 'text' field empty and attempt to add the block.
    // First submit with toggle OFF: the JS beforeSubmit handler blocks the AJAX
    // call (returns false) and enters warning state. Wait for the warning
    // message instead of assertWaitOnAjaxRequest().
    $this->getSession()->getPage()->pressButton('Add block');
    $assert->waitForElementVisible('css', '[data-drupal-message-id="patternkit-validation-warning"]');

    // First submit: off-canvas should still be open.
    $assert->elementExists('css', '#drupal-off-canvas');

    // Warning container class should be present.
    $assert->elementExists('css', '#patternkit-editor-target.patternkit-validation-warnings');

    // Button should now include "with issues" suffix.
    $button = $assert->elementExists('css', '[data-drupal-selector="edit-actions-submit"]');
    $this->assertStringContainsString('with issues', $button->getValue());

    // Fix the content so both client-side and server-side validation pass,
    // then submit. The change listener clears the warning state when errors
    // resolve, and the normal submit path succeeds.
    $this->getSession()->getPage()->fillField('root[text]', 'Now valid');
    $assert->assertNoElementAfterWait('css', '[data-drupal-message-id="patternkit-validation-warning"]');
    // Re-query to avoid a stale element reference after the DOM update.
    $button = $assert->elementExists('css', '[data-drupal-selector="edit-actions-submit"]');
    $this->getSession()->getPage()->pressButton($button->getValue());
    $assert->assertWaitOnAjaxRequest();

    // Off-canvas should close.
    $assert->assertNoElementAfterWait('css', '#drupal-off-canvas');

    $this->assertSaveLayout();
  }

  /**
   * Tests that valid content submits normally with toggle OFF (baseline).
   */
  public function testToggleOffValidContentNormalSave(): void {
    $assert = $this->assertSession();

    $this->config('patternkit.settings')
      ->set('render_validation_on_save', FALSE)
      ->save();

    $this->goToLayout();
    $this->addPatternBlock(static::EXAMPLE_PATTERN);
    $this->waitForJsonEditorForm();

    // Fill in valid content.
    $this->getSession()->getPage()->fillField('root[text]', 'Valid content');
    $this->getSession()->getPage()->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();

    // Off-canvas should close immediately (no warning).
    $assert->assertNoElementAfterWait('css', '#drupal-off-canvas');

    // No warning message should appear.
    $assert->elementNotExists('css', '[data-drupal-message-id="patternkit-validation-warning"]');

    $this->assertSaveLayout();
  }

  /**
   * Tests that fixing issues while in warning state clears the warning.
   */
  public function testToggleOffEditorFixesIssuesWarningClears(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->config('patternkit.settings')
      ->set('render_validation_on_save', FALSE)
      ->save();

    $this->goToLayout();
    $this->addPatternBlock(static::EXAMPLE_PATTERN);
    $this->waitForJsonEditorForm();

    // First submit with empty required field to trigger warning state.
    // The JS handler blocks the AJAX call and enters warning mode.
    $page->pressButton('Add block');
    $assert->waitForElementVisible('css', '[data-drupal-message-id="patternkit-validation-warning"]');
    $button = $assert->elementExists('css', '[data-drupal-selector="edit-actions-submit"]');
    $this->assertStringContainsString('with issues', $button->getValue());

    // Fix the issue by filling in the required field.
    $page->fillField('root[text]', 'Now valid');

    // Wait for warning to clear (change listener triggers revert).
    $assert->assertNoElementAfterWait('css', '[data-drupal-message-id="patternkit-validation-warning"]');

    // Re-query the button to avoid a stale element reference after DOM update.
    $button = $assert->elementExists('css', '[data-drupal-selector="edit-actions-submit"]');

    // Button should revert to its original label.
    $this->assertStringNotContainsString('with issues', $button->getValue());

    // Submit normally.
    $page->pressButton($button->getValue());
    $assert->assertWaitOnAjaxRequest();
    $assert->assertNoElementAfterWait('css', '#drupal-off-canvas');

    $this->assertSaveLayout();
  }

  /**
   * Tests that warning re-triggers after content is fixed then cleared.
   */
  public function testToggleOffWarningReTriggersAfterRevert(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->config('patternkit.settings')
      ->set('render_validation_on_save', FALSE)
      ->save();

    $this->goToLayout();
    $this->addPatternBlock(static::EXAMPLE_PATTERN);
    $this->waitForJsonEditorForm();

    // Trigger warning state. JS blocks the AJAX call on first submit.
    $page->pressButton('Add block');
    $assert->waitForElementVisible('css', '[data-drupal-message-id="patternkit-validation-warning"]');

    // Fix the issue so the warning clears.
    $page->fillField('root[text]', 'Fixed');
    $assert->assertNoElementAfterWait('css', '[data-drupal-message-id="patternkit-validation-warning"]');

    // Clear the field again to reintroduce the error.
    $page->fillField('root[text]', '');

    // Re-query the button after the DOM update; press by current label value.
    $button = $assert->elementExists('css', '[data-drupal-selector="edit-actions-submit"]');

    // Submit to re-trigger the warning. JS blocks AJAX again on first submit.
    $page->pressButton($button->getValue());
    $assert->waitForElementVisible('css', '[data-drupal-message-id="patternkit-validation-warning"]');

    $button = $assert->elementExists('css', '[data-drupal-selector="edit-actions-submit"]');
    $this->assertStringContainsString('with issues', $button->getValue());
  }

  /**
   * Toggle OFF: second submit bypasses JS gate and server allows the save.
   *
   * When enforcement is off, validatePatternConfig() skips setError() so the
   * form submission proceeds. The entity-hook layer records the stamp result
   * but does not block. The off-canvas closes and the block is added.
   */
  public function testToggleOffSecondSubmitBypassesSave(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->config('patternkit.settings')
      ->set('render_validation_on_save', FALSE)
      ->save();

    $this->goToLayout();
    $this->addPatternBlock(static::EXAMPLE_PATTERN);
    $this->waitForJsonEditorForm();

    // Leave the required text field empty to trigger validation errors.
    // First submit: JS blocks the AJAX call and enters warning state.
    $page->pressButton('Add block');
    $assert->waitForElementVisible('css', '[data-drupal-message-id="patternkit-validation-warning"]');
    $button = $assert->elementExists('css', '[data-drupal-selector="edit-actions-submit"]');
    $this->assertStringContainsString('with issues', $button->getValue());

    // Second submit with errors still present: JS allows the AJAX through and
    // the server skips blocking (toggle off), so the off-canvas closes.
    $page->pressButton($button->getValue());
    $assert->assertWaitOnAjaxRequest();
    $assert->assertNoElementAfterWait('css', '#drupal-off-canvas');

    $this->assertSaveLayout();
  }

}
