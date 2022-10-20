<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\patternkit\Traits\CKEditorIntegrationTestTrait;

/**
 * End-to-end testing for patternkit block CKEditor integration.
 *
 * @group patternkit
 */
class CKEditorIntegrationTest extends PatternkitBrowserTestBase {

  use CKEditorIntegrationTestTrait;

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
    'patternkit_example',
    'ckeditor',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a text format and associate CKEditor.
    FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ])->save();
    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ])->save();

    // Enable CKEditor integration.
    $config = $this->config('patternkit.settings');
    $config->set('patternkit_json_editor_wysiwyg', 'ckeditor');
    $config->set('patternkit_json_editor_ckeditor_toolbar', 'filtered_html');
    $config->save();
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
    $page->clickLink('Add block');

    // Wait for the block list to load before selecting a pattern to insert.
    $assert->waitForLink($pattern_name)->click();

    // Wait for the JSON Editor form to load.
    $assert->waitForField('root[text]');

    // Wait for CKEditor to load.
    $this->waitForEditor('editor1');

    // Fill in JSON Editor fields.
    $page->fillField('root[text]', 'Pattern block title');
    $page->fillField('root[hidden]', 'My hidden text');

    // Fill in the CKEditor field.
    $wysiwyg_value = '<p>My <strong>rich text</strong> <em>value</em>.</p>';
    $this->setEditorValue('root.formatted_text', $wysiwyg_value);

    $config = $page->find('css', '#schema_instance_config')->getValue();
    $this->assertStringContainsString($wysiwyg_value, $config);

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
    $assert->waitForField('root[text]');
    $this->waitForEditor('editor1');

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
    $page->clickLink('Add block');

    // Wait for the block list to load before selecting a pattern to insert.
    $assert->waitForLink($pattern_name)->click();

    // Wait for the JSON Editor form to load.
    $assert->waitForField('root[text]');

    // Wait for CKEditor to load.
    $this->waitForEditor('editor1');

    // Confirm the CKEditor interface exists.
    $assert->elementExists('css', '#cke_editor1');
    $assert->elementExists('css', '#cke_editor2');

    // Fill in JSON Editor fields.
    $page->fillField('root[text]', 'Pattern block title');
    $page->fillField('root[hidden]', 'My hidden text');

    // Fill in the first CKEditor field.
    $restricted_value = '<p>My <strong>restricted text</strong> <em>value</em>.</p>';
    $this->setEditorValue('root.formatted_text_restricted', $restricted_value);

    // Fill in the second CKEditor field.
    $free_value = '<p>My <strong>free text</strong> <em>value</em>.</p>';
    $this->setEditorValue('root.formatted_text_free', $free_value);

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
    $assert->waitForField('root[text]');
    $this->waitForEditor('editor1');

    $assert->fieldValueEquals('root[text]', 'Pattern block title');
    $assert->fieldValueEquals('root[hidden]', 'My hidden text');

    // Confirm text values in CKEditor fields.
    $value_1 = $this->getEditorValue('root.formatted_text_restricted');
    $value_2 = $this->getEditorValue('root.formatted_text_free');
    $this->assertEquals($restricted_value, $value_1);
    $this->assertEquals($free_value, $value_2);
  }

}
