<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\node\Entity\Node;
use Drupal\patternkit\PatternRepository;

/**
 * E2E testing for the Patternkit schema update process.
 *
 * @group patternkit
 */
class SchemaUpdateTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
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
   * Test the update process for an outdated block schema.
   */
  public function testSchemaUpdate() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'access administration pages',
      'configure any layout',
      'bypass node access',
    ]);
    $this->drupalLogin($account);

    // Override the Node layout and place a patternkit block.
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

    // Get the UUID of the component.
    $components = Node::load(1)
      ->get('layout_builder__layout')
      ->getSection(0)
      ->getComponents();
    $component = end($components);
    $uuid = key($components);

    // Confirm the form doesn't contain an update notification.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Update pattern schema and template from');

    // Alter the saved schema to trigger the update process.
    $pattern_id = $component->get('configuration')['pattern'];
    $this->setCachedPatternSchema($pattern_id, '{}');

    // Confirm the form shows the update notification.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Update pattern schema and template from');
    $page->pressButton('Update pattern');
    $assert->pageTextContains('Successfully updated the pattern schema and template to ' . \Drupal::VERSION);
    $page->pressButton('Update');

    // The update notification should no longer display for this block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Update pattern schema and template from');
  }

}
