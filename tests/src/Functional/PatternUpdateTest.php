<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\node\Entity\Node;
use Drupal\patternkit\PatternRepository;

/**
 * E2E testing for the Patternkit pattern update process.
 *
 * @group patternkit
 */
class PatternUpdateTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
  ];

  /**
   * The pattern repository service.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->patternRepository = $this->container->get('patternkit.pattern.repository');

    // Invalidate caches on pattern updates to simplify testing.
    $this->config('patternkit.settings')
      ->set('entity_cache_tag_invalidation', TRUE)->save();
  }

  /**
   * Update a pattern entity revision to have a new schema value.
   *
   * @param int $revision_id
   *   The pattern entity revision id to alter.
   * @param string $schema
   *   The schema content to update the pattern entity to use.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  protected function setCachedPatternSchema(int $revision_id, string $schema) {
    /** @var \Drupal\patternkit\Entity\Pattern $patternEntity */
    $patternEntity = $this->patternStorage->loadRevision($revision_id);

    // Set the cached pattern entity to use the old schema version.
    $patternEntity->setSchema($schema);
    $patternEntity->setNewRevision(FALSE);
    $patternEntity->save();
  }

  /**
   * Update a pattern entity revision to have a new template value.
   *
   * @param int $revision_id
   *   The pattern entity revision id to alter.
   * @param string $template
   *   The template content to update the pattern entity to use.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  protected function setCachedPatternTemplate(int $revision_id, string $template) {
    /** @var \Drupal\patternkit\Entity\Pattern $patternEntity */
    $patternEntity = $this->patternStorage->loadRevision($revision_id);

    // Set the cached pattern entity to use the old schema version.
    $patternEntity->setTemplate($template);
    $patternEntity->setNewRevision(FALSE);
    $patternEntity->save();
  }

  /**
   * Test the update process for an outdated block schema.
   */
  public function testSchemaUpdate() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'configure any layout',
      'bypass node access',
    ]);
    $this->drupalLogin($account);

    // Override the Node layout and place a patternkit block.
    $pattern_name = '[Patternkit] Example with Reference';
    $configuration = [
      'text' => 'Pattern block title',
      'nested_reference' => [
        'formatted_text' => 'Pattern block body',
        'image_url' => '',
        'hidden' => 'Hidden text',
      ],
    ];

    $this->drupalGet('node/1/layout');
    $page->clickLink('Add block');
    $assert->linkExists($pattern_name);
    $page->clickLink($pattern_name);

    // Confirm the block doesn't initially prompt for updating.
    $assert->pageTextNotContains('Update pattern schema and template');

    // Fill in the hidden fields manually since fillField() won't find hidden
    // fields.
    $page->find('css', '#schema_instance_config')
      ->setValue(json_encode($configuration));
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Get the UUID of the component.
    $components = Node::load(1)
      ->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);
    $uuid = key($components);

    // Confirm the form doesn't contain an update notification.
    // The full-page block config URL is used intentionally here: this test
    // validates PHP-only behavior (schema update detection) without JavaScript.
    $block_form_url = 'layout_builder/update/block/overrides/node.1/0/content/' . $uuid;
    $this->drupalGet($block_form_url);
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Update pattern schema and template');

    // Alter the saved schema to trigger the update process.
    $pattern_id = $component->get('configuration')['pattern'];
    $this->setCachedPatternSchema($pattern_id, '{}');

    // Confirm the form shows the update notification.
    $this->drupalGet($block_form_url);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Update pattern schema and template');
    $page->pressButton('Update pattern');
    $assert->pageTextContains('Successfully updated the pattern schema and template');
    $page->pressButton('Update');

    // The update notification should no longer display for this block.
    $this->drupalGet($block_form_url);
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Update pattern schema and template');
  }

  /**
   * Test the pattern update process and revisioning behavior.
   */
  public function testPatternUpdateRevisioning() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'configure any layout',
      'bypass node access',
    ]);
    $this->drupalLogin($account);

    // Place a pattern block on the first page.
    $this->drupalGet('node/1/layout');
    $page->clickLink('Add block');
    $assert->linkExists('[Patternkit] Example');
    $page->clickLink('[Patternkit] Example');
    // Fill in the hidden fields manually since fillField() won't find hidden
    // fields.
    $page->find('css', '#schema_instance_config')
      ->setValue(json_encode([
        'text' => 'Pattern block title',
        'formatted_text' => 'Pattern block body',
        'image_url' => '',
        'hidden' => 'Hidden text',
      ]));
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    $this->drupalGet('node/1');
    $assert->pageTextContains('Pattern block body');

    // Create a second page for testing.
    $this->drupalCreateNode([
      'type' => 'bundle_with_layout_enabled',
      'title' => 'Second node title',
      'body' => [
        [
          'value' => 'Test node body.',
        ],
      ],
    ]);

    // Place a block with the same pattern on the second page.
    $this->drupalGet('node/2/layout');
    $page->clickLink('Add block');
    $assert->linkExists('[Patternkit] Example');
    $page->clickLink('[Patternkit] Example');
    // Fill in the hidden fields manually since fillField() won't find hidden
    // fields.
    $page->find('css', '#schema_instance_config')
      ->setValue(json_encode([
        'text' => 'Pattern block title',
        'formatted_text' => 'Pattern block body',
        'image_url' => '',
        'hidden' => 'Hidden text',
      ]));
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    $this->drupalGet('node/2');
    $assert->pageTextContains('Pattern block body');

    // Get the pattern revision ID of the component.
    $node_1 = Node::load(1);
    $components = $node_1->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);
    $component_1_uuid = $component->getUuid();
    $component_config = $component->get('configuration');
    $pattern_revision_1 = $component_config['pattern'];

    // Get the pattern revision ID of the second component.
    $node_2 = Node::load(2);
    $components = $node_2->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);
    $component_2_uuid = $component->getUuid();
    $component_config = $component->get('configuration');
    $pattern_revision_2 = $component_config['pattern'];

    // Since the content of the pattern didn't change, the same revision should
    // be used in both instances.
    $this->assertEquals($pattern_revision_1, $pattern_revision_2);

    // Update the template value for the second revision.
    $this->setCachedPatternTemplate($pattern_revision_2, 'MY REPLACED TEMPLATE');

    // Expect to see the updated content on the second node.
    $this->drupalGet('node/2');
    $assert->pageTextContains('MY REPLACED TEMPLATE');

    // Since we updated the revision used in both places it should be visible
    // on both pages.
    $this->drupalGet('node/1');
    $assert->pageTextContains('MY REPLACED TEMPLATE');

    // Update the pattern revision to load from disk on the first node.
    // The full-page block config URL is used intentionally here: this test
    // validates PHP-only behavior (pattern revisioning) without JavaScript.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $component_1_uuid);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Update pattern schema and template');
    $page->pressButton('Update pattern');
    $assert->pageTextContains('Successfully updated the pattern schema and template');
    $page->pressButton('Update');
    $page->pressButton('Save layout');

    // Since we updated the pattern on the first node, the manually updated
    // template content should no longer be visible.
    $this->drupalGet('node/1');
    $assert->pageTextNotContains('MY REPLACED TEMPLATE');
    $assert->pageTextContains('Sample twig template.');

    // Since we only updated the pattern on one node, the other instance should
    // still be using the manually updated revision.
    $this->drupalGet('node/2');
    $assert->pageTextContains('MY REPLACED TEMPLATE');
    $assert->pageTextNotContains('Sample twig template.');

    // Update the pattern revision to load from disk on the second node.
    $this->drupalGet('layout_builder/update/block/overrides/node.2/0/content/' . $component_2_uuid);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Update pattern schema and template');
    $page->pressButton('Update pattern');
    $assert->pageTextContains('Successfully updated the pattern schema and template');
    $page->pressButton('Update');
    $page->pressButton('Save layout');

    // The second node should now be updated and use the correct template.
    $this->drupalGet('node/2');
    $assert->pageTextNotContains('MY REPLACED TEMPLATE');
    $assert->pageTextContains('Sample twig template.');

    // Get the pattern revision ID of the component.
    $node_1 = Node::load(1);
    $components = $node_1->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);
    $component_config = $component->get('configuration');
    $pattern_revision_1 = $component_config['pattern'];

    // Get the pattern revision ID of the second component.
    $node_2 = Node::load(2);
    $components = $node_2->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);
    $component_config = $component->get('configuration');
    $pattern_revision_2 = $component_config['pattern'];

    // Both instances should be using the same pattern revision now.
    $this->assertEquals($pattern_revision_1, $pattern_revision_2);
  }

}
