<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\node\Entity\Node;
use Drupal\Tests\patternkit\Traits\CKEditor5IntegrationTestTrait;

/**
 * End-to-end testing for patternkit block CKEditor integration.
 *
 * @group patternkit
 * @group legacy
 * @requires module ckeditor
 */
class CKEditorIntegrationTest extends PatternkitBrowserTestBase {

  use CKEditor5IntegrationTestTrait;

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
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

    // Wait for the CKEditor value to be propagated to the JSON Editor field.
    $field = $this->waitForFieldValueChange('root[formatted_text]');

    // Confirm the value was assigned into the JSON Editor field.
    $this->assertEquals($wysiwyg_value, $field->getValue(), 'CKEditor value was not populated into JSON Editor field.');

    // Confirm the editor value was saved into the form's schema configuration.
    $config = $page->find('css', '#schema_instance_config')->getValue();
    $this->assertStringContainsString($wysiwyg_value, $config, 'Editor value was not populated into schema config values.');

    $page->pressButton('Add block');
    $page->pressButton('Save layout');

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

    // Fill in the second CKEditor field.
    $free_value = '<p>My <strong>free text</strong> <em>value</em>.</p>';
    $this->setEditorValue('root.formatted_text_free', $free_value);

    // Wait for the field values to be updated.
    $this->waitForFieldValueChange('root[formatted_text_restricted]');
    $this->waitForFieldValueChange('root[formatted_text_free]');

    // Confirm the schema config field updated and contains CKEditor values.
    $config = $page->find('css', '#schema_instance_config')->getValue();
    $this->assertStringContainsString($restricted_value, $config);
    $this->assertStringContainsString($free_value, $config);

    $page->pressButton('Add block');
    $page->pressButton('Save layout');

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

}
