<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Drupal\node\Entity\Node;
use Drupal\Tests\patternkit\Traits\PatternDatabaseAlterationTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * End-to-end testing for patternkit block placement in layout builder layouts.
 */
#[Group('patternkit')]
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

}
