<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Drupal\node\Entity\Node;
use Drupal\Tests\patternkit\Traits\CKEditor5IntegrationTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * End-to-end testing for patternkit block CKEditor integration.
 */
#[Group('patternkit')]
class CKEditorIntegrationTest extends PatternkitBrowserTestBase {

  use CKEditor5IntegrationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'ckeditor5',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a text format and associate CKEditor.
    $this->configureCkeditorFormat();
  }

  /**
   * Test the end-to-end workflow of placing a new pattern on a Node layout.
   */
  public function testCkeditorVisibility(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example';

    // Log in as an editor with access to update layouts.
    $this->drupalLogin($this->editorUser);

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $editor_form = $this->waitForJsonEditorForm();

    // Wait for CKEditor to load.
    $this->waitForEditor();

    // Fill in basic JSON Editor fields.
    $editor_form->fillField('root[text]', 'Pattern block title');
    $editor_form->fillField('root[hidden]', 'My hidden text');

    // Confirm regular fields are populating into the schema config field.
    $config = $page->find('css', '#schema_instance_config')->getValue();
    $this->assertStringContainsString('Pattern block title', $config, 'Title field value was not populated into schema config values.');
    $this->assertStringContainsString('My hidden text', $config, 'Hidden text field value was not populated into schema config values.');

    // Fill in the CKEditor field.
    $wysiwyg_value = '<p>My <strong>rich text</strong> <em>value</em>.</p>';
    $this->setEditorValue('root.formatted_text', $wysiwyg_value);

    // Confirm the value is propagated into the form and schema config fields.
    $this->assertEquals($wysiwyg_value, $this->getEditorValue('root.formatted_text'), 'CKEditor value was not updated.');
    $this->triggerCkeditorChange('root.formatted_text');

    // Wait for the CKEditor value to be propagated to the JSON Editor field.
    $field = $this->waitForFieldValueChange('root[formatted_text]');

    // Confirm the value was assigned into the JSON Editor field.
    $this->assertEquals($wysiwyg_value, $field->getValue(), 'CKEditor value was not populated into JSON Editor field.');

    // Confirm the editor value was saved into the form's schema configuration.
    $config = $page->find('css', '#schema_instance_config')->getValue();
    $this->assertStringContainsString($wysiwyg_value, $config, 'Editor value was not populated into schema config values.');

    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->pageTextContains('Pattern block title');
    $assert->pageTextContains('My rich text value.');

    // Get block ID to return to edit form.
    $uuid = $this->getLastLayoutBlockUuid(Node::load(1));

    // Confirm all configuration content is persisted when editing the block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $this->waitForJsonEditorForm();
    $this->waitForEditor();

    $assert->fieldValueEquals('root[text]', 'Pattern block title');
    $assert->fieldValueEquals('root[hidden]', 'My hidden text');

    // Confirm text values in CKEditor fields.
    $value = $this->getEditorValue('root.formatted_text');
    $this->assertEquals($wysiwyg_value, $value);
  }

  /**
   * Test persistence of configured CKEditor content.
   */
  public function testCkeditorPersistence(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example with multiple filtered content blocks';

    // Log in as an editor with access to update layouts.
    $this->drupalLogin($this->editorUser);

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $editor_form = $this->waitForJsonEditorForm();

    // Wait for CKEditor to load.
    $this->waitForEditor();

    // Confirm the CKEditor interface exists.
    $assert->elementExists('css', 'div[data-schemapath="root.formatted_text_restricted"] .ck-editor');
    $assert->elementExists('css', 'div[data-schemapath="root.formatted_text_free"] .ck-editor');

    // Fill in JSON Editor fields.
    $editor_form->fillField('root[text]', 'Pattern block title');
    $editor_form->fillField('root[hidden]', 'My hidden text');

    // Fill in the first CKEditor field.
    $restricted_value = '<p>My <strong>restricted text</strong> <em>value</em>.</p>';
    $this->setEditorValue('root.formatted_text_restricted', $restricted_value);
    $this->triggerCkeditorChange('root.formatted_text_restricted');

    // Fill in the second CKEditor field.
    $free_value = '<p>My <strong>free text</strong> <em>value</em>.</p>';
    $this->setEditorValue('root.formatted_text_free', $free_value);
    $this->triggerCkeditorChange('root.formatted_text_free');

    // Wait for the field values to be updated.
    $this->waitForFieldValueChange('root[formatted_text_restricted]');
    $this->waitForFieldValueChange('root[formatted_text_free]');

    // Confirm the schema config field updated and contains CKEditor values.
    $config = $page->find('css', '#schema_instance_config')->getValue();
    $this->assertStringContainsString($restricted_value, $config);
    $this->assertStringContainsString($free_value, $config);

    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->pageTextContains('Pattern block title');
    $assert->pageTextContains('My restricted text value.');
    $assert->pageTextContains('My free text value.');

    // Get component ID to return to edit form.
    $uuid = $this->getLastLayoutBlockUuid(Node::load(1));

    // Confirm all configuration content is persisted when editing the block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $this->waitForJsonEditorForm();
    $this->waitForEditor();

    $assert->fieldValueEquals('root[text]', 'Pattern block title');
    $assert->fieldValueEquals('root[hidden]', 'My hidden text');

    // Confirm text values in CKEditor fields.
    $value_1 = $this->getEditorValue('root.formatted_text_restricted');
    $value_2 = $this->getEditorValue('root.formatted_text_free');
    $this->assertEquals($restricted_value, $value_1);
    $this->assertEquals($free_value, $value_2);
  }

  /**
   * Test saving when CKEditor is used in source editing mode.
   */
  public function testCkeditorSourceEditingMode(): void {
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

    // Wait for CKEditor to load.
    $this->waitForEditor();

    // Get the original config value to watch for changes.
    $config_field = $page->find('css', '#schema_instance_config');
    $original_config = $config_field->getValue();

    // Set initial content in the editor.
    $content = $page->find('css', '.ck-content');
    $content->setValue('My test content');

    // Confirm content and trigger editor changes. When entering the content
    // traditionally in the editor, manually triggering the change event
    // would not be needed. Unfortunately, the web driver doesn't trigger the
    // event when setting the data value, so we have to trigger it manually for
    // tests.
    $this->assertEquals('<p>My test content</p>', $this->getEditorDataAsHtmlString(), "Didn't find the expected content in the editor.");
    $schema_path = 'root.formatted_text';
    $this->triggerCkeditorChange($schema_path);

    $changed_config = $this->waitForConfigChange($original_config);
    $this->assertNotFalse($changed_config, 'A configuration change was not detected.');
    $this->assertStringContainsString('<p>My test content</p>', $changed_config);

    // Change into source editing mode and alter the content.
    $this->pressSourceButton();
    $source_edited_content = '<p>My <em>edited</em> test content</p>';
    $page->find('css', '.ck-source-editing-area textarea')
      ->setValue($source_edited_content);

    // Change back from source mode to trigger updates.
    $this->pressSourceButton();
    $this->assertEquals($source_edited_content, $this->getEditorDataAsHtmlString());

    // Confirm the source-edited content made it into the serialized config.
    $changed_config = $this->waitForConfigChange($changed_config);
    $this->assertStringContainsString($source_edited_content, $changed_config);

    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->pageTextContains('My edited test content');
  }

  /**
   * Test saving when CKEditor is left in source editing mode.
   */
  public function testCkeditorSourceEditingModeSaving(): void {
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

    // Wait for CKEditor to load.
    $this->waitForEditor();

    // Get the original config value to watch for changes.
    $config_field = $page->find('css', '#schema_instance_config');
    $original_config = $config_field->getValue();

    // Set initial content in the editor.
    $content = $page->find('css', '.ck-content');
    $content->setValue('My test content');

    // Confirm content and trigger editor changes. When entering the content
    // traditionally in the editor, manually triggering the change event
    // would not be needed. Unfortunately, the web driver doesn't trigger the
    // event when setting the data value, so we have to trigger it manually for
    // tests.
    $this->assertEquals('<p>My test content</p>', $this->getEditorDataAsHtmlString(), "Didn't find the expected content in the editor.");
    $schema_path = 'root.formatted_text';
    $this->triggerCkeditorChange($schema_path);

    $changed_config = $this->waitForConfigChange($original_config);
    $this->assertNotFalse($changed_config, 'A configuration change was not detected.');
    $this->assertStringContainsString('<p>My test content</p>', $changed_config);

    // Change into source editing mode and alter the content.
    $this->pressSourceButton();
    $source_edited_content = '<p>My <em>edited</em> test content</p>';
    $page->find('css', '.ck-source-editing-area textarea')
      ->setValue($source_edited_content);

    // Save the block without changing back out of source editing mode.
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->pageTextContains('My edited test content');
  }

}
