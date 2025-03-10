<?php

namespace Drupal\Tests\patternkit\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * End-to-end testing for placing and using a pattern block in default layouts.
 */
#[Group('patternkit')]
class DefaultLayoutTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'field_ui',
  ];

  /**
   * Verify successful creation and loading of a default layout.
   */
  public function testDefaultLayoutCreation() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'administer node display',
      'configure any layout',
      'bypass node access',
    ]);
    $this->drupalLogin($account);

    // Set the default the Node layout and place a patternkit block.
    $this->drupalGet('admin/structure/types/manage/bundle_with_layout_enabled/display');
    // Click the "Manage layout" button.
    $page->find('css', '#edit-manage-layout')->press();
    $page->clickLink('Add block');
    $assert->linkExists('[Patternkit] Example');
    $page->clickLink('[Patternkit] Example');

    // Fill in the hidden fields manually since fillField() won't find hidden
    // fields.
    $page->find('css', '#schema_instance_config')
      ->setValue(json_encode([
        'text' => '[node:title]',
        'formatted_text' => 'Pattern block body',
        'image_url' => '',
        'hidden' => 'Hidden text',
      ]));
    // Set the node for context to fill in the token.
    $page->selectFieldOption('settings[context_mapping][node]', 'layout_builder.entity');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm visibility on a Node page.
    $this->drupalGet('node/1');
    $assert->pageTextContains('Test node title');
    $assert->pageTextContains('Pattern block body');
    $assert->pageTextNotContains('[node:title]');
  }

}
