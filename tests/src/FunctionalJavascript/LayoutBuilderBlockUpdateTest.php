<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Drupal\node\Entity\Node;
use Drupal\Tests\patternkit\Traits\PatternDatabaseAlterationTrait;

/**
 * End-to-end testing for patternkit block placement in layout builder layouts.
 *
 * @group patternkit
 */
class LayoutBuilderBlockUpdateTest extends PatternkitBrowserTestBase {

  use PatternDatabaseAlterationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'patternkit_test',
  ];

  /**
   * Test the editor workflow updating a pattern schema in a block form.
   *
   * @see \Drupal\patternkit_test\EventSubscriber\PatternUpdateSubscriber
   */
  public function testBlockUpdateAlteration(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example';

    // Log in as an editor with access to update layouts.
    $this->drupalLogin($this->editorUser);

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Fill in JSON Editor fields.
    $page->fillField('root[text]', 'Pattern block title');
    $page->fillField('root[hidden]', 'My hidden text');
    $page->fillField('root[formatted_text]', 'Pattern block body');

    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Get the UUID of the component.
    $components = Node::load(1)
      ->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);

    // Alter the saved schema to trigger the update process.
    $pattern_id = $component->get('configuration')['pattern'];
    $this->setCachedPatternSchema($pattern_id, '{}');

    // Navigate to the edit form again.
    $this->drupalGet('node/1/layout');
    $this->clickContextualLink(static::PATTERN_BLOCK_LOCATOR, 'Configure');

    // Wait for the first JSON Editor field to be visible.
    $this->waitForJsonEditorForm();

    // Confirm the form shows the update notification.
    $assert->pageTextContains('Update pattern schema and template');
    $page->pressButton('Update pattern');
    $assert->assertWaitOnAjaxRequest();
    $assert->pageTextContains('Successfully updated the pattern schema and template');

    // Confirm the text field was altered by the update subscriber.
    $textValue = $page->findField('root[text]')->getValue();
    $this->assertStringContainsString('Altered', $textValue);

    // Save the updates.
    $page->pressButton('Update');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->pageTextContains('Pattern block title (Altered)');
    $assert->pageTextContains('Pattern block body');
  }

  /**
   * Test that schema fields refresh in-place after clicking "Update pattern".
   *
   * This reproduces a stale-settings scenario where the block form rebuilds
   * after a pattern update, but the JSON Editor still renders the old schema.
   */
  public function testBlockUpdateRefreshesSchemaFields(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example';
    // Log in as an editor with access to update layouts.
    $this->drupalLogin($this->editorUser);

    // Place an Example block using the current schema.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);
    $this->waitForJsonEditorForm();
    $page->fillField('root[text]', 'Pattern block title');
    $page->fillField('root[formatted_text]', 'Pattern block body');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Find the saved block component and pattern revision in use.
    $components = Node::load(1)
      ->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);
    $pattern_id = $component->get('configuration')['pattern'];

    // Force the stored revision to use a schema variant with renamed labels.
    // This allows us to verify that clicking "Update pattern" refreshes the
    // JSON Editor schema in-place.
    $legacy_schema = json_encode([
      '$schema' => 'http://json-schema.org/draft-04/schema#',
      'type' => 'object',
      'properties' => [
        'text' => ['title' => 'Legacy text', 'type' => 'string'],
        'formatted_text' => ['title' => 'Legacy formatted text', 'type' => 'string', 'format' => 'html'],
        'image_url' => ['title' => 'Legacy image URL', 'type' => 'string', 'format' => 'image'],
        'hidden' => ['title' => 'Legacy hidden', 'type' => 'string'],
      ],
    ], JSON_THROW_ON_ERROR);
    assert(is_string($legacy_schema));
    $this->setCachedPatternSchema($pattern_id, $legacy_schema);

    // Open the block config. Before clicking update, field labels should match
    // the legacy schema revision.
    $this->drupalGet('node/1/layout');
    $this->clickContextualLink(static::PATTERN_BLOCK_LOCATOR, 'Configure');
    $this->waitForJsonEditorForm();
    $assert->pageTextContains('Update pattern schema and template');
    $this->assertNotNull(
      $page->findField('Legacy text'),
      'Legacy schema label should be visible before clicking "Update pattern".'
    );

    // After update, rebuilt editor should use labels from the current schema.
    $page->pressButton('Update pattern');
    $assert->assertWaitOnAjaxRequest();
    $assert->pageTextContains('Successfully updated the pattern schema and template');
    $this->waitForJsonEditorForm();
    $this->assertNotNull(
      $page->findField('Text'),
      'Current schema label should be visible after clicking "Update pattern".'
    );
    $this->assertNull(
      $page->findField('Legacy text'),
      'Legacy schema label should no longer be visible after update.'
    );
  }

  /**
   * Test that update-pattern still works when serialized config is malformed.
   */
  public function testUpdatePatternWithMalformedConfigurationJson(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example';
    $this->drupalLogin($this->editorUser);

    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);
    $this->waitForJsonEditorForm();
    $page->fillField('root[text]', 'Original title');
    $page->fillField('root[formatted_text]', 'Body text');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    $components = Node::load(1)
      ->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);
    $pattern_id = $component->get('configuration')['pattern'];
    $this->setCachedPatternSchema($pattern_id, '{}');

    $this->drupalGet('node/1/layout');
    $this->clickContextualLink(static::PATTERN_BLOCK_LOCATOR, 'Configure');
    $this->waitForJsonEditorForm();

    // Simulate malformed serialized configuration payload submission.
    $this->getSession()->executeScript(<<<'JS'
const config = document.querySelector('#schema_instance_config');
if (config) {
  config.value = '{"text":"Broken"';
  config.dispatchEvent(new Event('change', { bubbles: true }));
}
JS
    );

    $assert->pageTextContains('Update pattern schema and template');
    $page->pressButton('Update pattern');
    $assert->assertWaitOnAjaxRequest();
    $assert->pageTextContains('Successfully updated the pattern schema and template');
    $assert->pageTextContains('Review content values below');

    // Confirm subscriber-altered draft content is still reflected after update.
    $textValue = $page->findField('root[text]')->getValue();
    $this->assertStringContainsString('Altered', $textValue);
  }

  /**
   * Test that update-pattern draft changes are not persisted after cancel.
   */
  public function testUpdatePatternCancelDoesNotPersistDraftChanges(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example';
    $this->drupalLogin($this->editorUser);

    // Place a baseline block and persist initial values.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);
    $this->waitForJsonEditorForm();
    $page->fillField('root[text]', 'Original title');
    $page->fillField('root[formatted_text]', 'Body text');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Force update prompt visibility.
    $components = Node::load(1)
      ->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);
    $pattern_id = $component->get('configuration')['pattern'];
    $this->setCachedPatternSchema($pattern_id, '{}');

    // Trigger update action and confirm draft content was transformed.
    $this->drupalGet('node/1/layout');
    $this->clickContextualLink(static::PATTERN_BLOCK_LOCATOR, 'Configure');
    $this->waitForJsonEditorForm();
    $assert->pageTextContains('Update pattern schema and template');
    $page->pressButton('Update pattern');
    $assert->assertWaitOnAjaxRequest();
    $assert->pageTextContains('Successfully updated the pattern schema and template');
    $assert->pageTextContains('Review content values below');
    $textValue = $page->findField('root[text]')->getValue();
    $this->assertStringContainsString('Altered', $textValue);

    // Cancel by navigating away from edit dialog without saving.
    $this->drupalGet('node/1/layout');
    $this->clickContextualLink(static::PATTERN_BLOCK_LOCATOR, 'Configure');
    $this->waitForJsonEditorForm();

    // Confirm transformed draft content was not persisted.
    $textValue = $page->findField('root[text]')->getValue();
    $this->assertStringNotContainsString('Altered', $textValue);
    $this->assertStringContainsString('Original title', $textValue);
  }

}
