<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Drupal\node\Entity\Node;

/**
 * End-to-end testing for patternkit block placement in layout builder layouts.
 */
class LayoutBuilderBlockTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
    'patternkit_example',
  ];

  /**
   * Test the end-to-end workflow of placing a new pattern on a Node layout.
   */
  public function testOverrideBlockPlacement(): void {
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

    // Fill in JSON Editor fields.
    $page->fillField('root[text]', 'Pattern block title');
    $page->fillField('root[hidden]', 'My hidden text');
    $page->fillField('root[formatted_text]', 'Pattern block body');

    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->pageTextContains('Pattern block title');
    $assert->pageTextContains('Pattern block body');
  }

  /**
   * Test persistence of block configuration.
   */
  public function testOverrideBlockPersistence(): void {
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

    // Fill in JSON Editor fields.
    $page->fillField('root[text]', 'Pattern block title');
    $page->fillField('root[hidden]', 'My hidden text');
    $page->fillField('root[formatted_text]', 'Pattern block body');

    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->pageTextContains('Pattern block title');
    $assert->pageTextContains('Pattern block body');

    // Get block ID to return to edit form.
    $uuid = $this->getLastLayoutBlockUuid(Node::load(1));

    // Confirm all configuration content is persisted when editing the block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $assert->waitForField('root[text]');

    $assert->fieldValueEquals('root[text]', 'Pattern block title');
    $assert->fieldValueEquals('root[hidden]', 'My hidden text');
    $assert->fieldValueEquals('root[formatted_text]', 'Pattern block body');
  }

}
