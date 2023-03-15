<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\node\Entity\Node;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;

/**
 * Testing to confirm bypass of the entity cache for blocks if enabled.
 *
 * @covers \Drupal\patternkit\Plugin\Block\PatternkitBlock
 * @group patternkit
 */
class BlockCacheBypassTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
    'patternkit_example',
    'patternkit_debug_test',
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
   * Test the cache bypass behavior for a patternkit block.
   */
  public function testBlockPatternCacheBypass() {
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
    $components = $this->getSectionStorageForEntity(Node::load(1))
      ->getSection(0)
      ->getComponents();
    /** @var \Drupal\layout_builder\SectionComponent $component */
    $component = end($components);
    $plugin = $component->getPlugin();
    $this->assertInstanceOf(PatternkitBlock::class, $plugin);

    // Alter the saved template to tell where it was loaded from.
    $pattern_id = $plugin->getConfiguration()['pattern'];
    $this->setCachedPatternTemplate($pattern_id, 'This is a cached template.');

    // Force a clear of the render cache to prevent cached results.
    // @todo Remove this once cache tag clearing is working properly.
    \Drupal::service('cache.render')->invalidateAll();

    // Confirm the page is not loading the cached template.
    $this->drupalGet('node/1');
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('This is a cached template.');
  }

}
