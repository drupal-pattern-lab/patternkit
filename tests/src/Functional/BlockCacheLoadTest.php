<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\node\Entity\Node;

/**
 * Testing to confirm loading of patterns from entity cache.
 *
 * @covers \Drupal\patternkit\Plugin\Block\PatternkitBlock
 * @group patternkit
 */
class BlockCacheLoadTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
  ];

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

    // Set the cached pattern entity to use the provided template content.
    $patternEntity->setTemplate($template);
    $patternEntity->setNewRevision(FALSE);
    $patternEntity->save();
  }

  /**
   * Test the update process for an outdated block schema.
   */
  public function testBlockCacheLoad() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
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

    // Alter the saved template to tell where it was loaded from.
    $pattern_id = $component->get('configuration')['pattern'];
    $this->setCachedPatternTemplate($pattern_id, 'This is a cached template.');

    // Force a clear of the render cache to prevent cached results.
    // @todo Remove this once cache tag clearing is working properly.
    \Drupal::service('cache.render')->invalidateAll();

    // Confirm the page is loading the cached template.
    $this->drupalGet('node/1');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('This is a cached template.');
  }

}
