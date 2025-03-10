<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Drupal\patternkit\Entity\Pattern;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\Tests\patternkit\Traits\CacheTestTrait;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

/**
 * End-to-end testing for patternkit cache invalidation in layout builder.
 */
#[Group('patternkit')]
#[CoversClass(Pattern::class)]
class LayoutBuilderCacheInvalidationTest extends PatternkitBrowserTestBase {

  use CacheTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
  ];

  /**
   * The default pattern ID to use for plugin instantiations.
   *
   * @const string
   *
   * @see \Drupal\Tests\patternkit\Unit\Plugin\Block\PatternkitBlockTest::getTestSubject()
   */
  const DEFAULT_PATTERN_ID = '@patternkit/atoms/example/src/example';

  /**
   * The Node storage handler.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected NodeStorageInterface $nodeStorage;

  /**
   * The first Node created for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node1;

  /**
   * The second Node created for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node2;

  /**
   * The patternkit block plugin for the pattern block placed on Node 1.
   *
   * @var \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  protected PatternkitBlock $plugin1;

  /**
   * The patternkit block plugin for the pattern block placed on Node 2.
   *
   * @var \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  protected PatternkitBlock $plugin2;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->nodeStorage = $this->container->get('entity_type.manager')
      ->getStorage('node');

    // Prepare two Nodes for testing.
    $this->node1 = $this->nodeStorage->load(1);
    $this->node2 = $this->drupalCreateNode([
      'type' => 'bundle_with_layout_enabled',
      'title' => 'Secondary node',
      'body' => [
        [
          'value' => 'Test node body.',
        ],
      ],
    ]);

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Log in as an editor with access to update layouts.
    $this->drupalLogin($this->editorUser);

    // Place an example pattern block on each node.
    $this->plugin1 = $this->placeExampleBlock($this->node1, [
      'text' => 'Node 1 block',
      'hidden' => 'hidden text',
      'formatted_text' => 'Node 1 body',
    ]);
    $this->plugin2 = $this->placeExampleBlock($this->node2, [
      'text' => 'Node 2 block',
      'hidden' => 'hidden text',
      'formatted_text' => 'Node 2 body',
    ]);

    // The page cache only works for anonymous users.
    $this->drupalLogout();
  }

  /**
   * Confirm expected cache tags are present from each block.
   */
  public function testCacheTagPresence(): void {
    $assert = $this->assertSession();

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->pageTextContains('Node 1 block');
    $assert->pageTextContains('Node 1 body');

    $expected_cache_tags = [
      'node:1',
      'pattern:' . self::DEFAULT_PATTERN_ID,
      'patternkit_pattern:1',
      'patternkit_block:1',
    ];
    $cache_tags = $this->getPageCacheTags($this->node1);
    foreach ($expected_cache_tags as $tag) {
      $this->assertContains($tag, $cache_tags, 'Block 1 did not include the expected cache tags.');
    }

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/2');
    $assert->pageTextContains('Node 2 block');
    $assert->pageTextContains('Node 2 body');

    $expected_cache_tags = [
      'node:2',
      'pattern:' . self::DEFAULT_PATTERN_ID,
      'patternkit_pattern:1',
      'patternkit_block:2',
    ];
    $cache_tags = $this->getPageCacheTags($this->node2);
    foreach ($expected_cache_tags as $tag) {
      $this->assertContains($tag, $cache_tags, 'Block 2 did not include the expected cache tags.');
    }
  }

  /**
   * Test cache invalidation during block edit workflows.
   */
  #[Depends('testCacheTagPresence')]
  public function testCacheInvalidation(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Log in as an editor with access to update layouts.
    $this->drupalLogin($this->editorUser);

    // Truncate the list of cache tag invalidations to track new invalidations.
    $invalidations = $this->getCacheInvalidations(TRUE);
    $this->assertNotEmpty($invalidations);

    $this->assertEmpty($this->getCacheInvalidations(), 'Invalidations should have been truncated.');

    // Edit the pattern block on Node 1.
    $this->drupalGet('node/1/layout');
    $assert->waitForElementVisible('css', static::PATTERN_BLOCK_LOCATOR);
    $pattern_block = $page->findAll('css', static::PATTERN_BLOCK_LOCATOR)[0];
    $uuid = $pattern_block->getAttribute('data-layout-block-uuid');
    $block_css_locator = static::PATTERN_BLOCK_LOCATOR . "[data-layout-block-uuid=\"$uuid\"]";
    $this->configurePatternBlock(
      [
        'text' => 'Node 1 block',
        'hidden' => 'hidden text',
        'formatted_text' => 'Node 1 body',
      ],
      [
        'text' => 'Node 1 block (Updated)',
        'hidden' => 'hidden text',
        'formatted_text' => 'Node 1 body (Updated)',
      ], $block_css_locator);

    // Confirm the Pattern and Block and Node were not invalidated yet.
    $this->assertCacheTagsNotInvalidated([
      'patternkit_block:1',
      'patternkit_pattern:1',
      'pattern:' . self::DEFAULT_PATTERN_ID,
      'node:1',
    ]);

    $this->assertSaveLayout();

    // Confirm the expected tags after saving an updated block were invalidated.
    $this->assertCacheTagsInvalidated([
      'patternkit_block:1',
      'node:1',
    ], FALSE);

    // Confirm the unaffected tags were not invalidated by mistake.
    $this->assertCacheTagsNotInvalidated([
      'patternkit_block:2',
      'patternkit_pattern:1',
      'pattern:' . self::DEFAULT_PATTERN_ID,
      'node:2',
    ]);
  }

  /**
   * Test cache tag invalidation when a pattern is updated.
   *
   * @param bool $withEntityTagInvalidation
   *   The setting assigned to entity_cache_tag_invalidation.
   */
  #[Depends('testCacheTagPresence')]
  #[DataProvider('providerPatternUpdateInvalidation')]
  public function testPatternUpdateInvalidation(bool $withEntityTagInvalidation): void {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    // Set the cache tag invalidation config value.
    $this->config(PatternkitSettingsForm::SETTINGS)
      ->set('entity_cache_tag_invalidation', $withEntityTagInvalidation)
      ->save();

    // Get the revision ID for the pattern entity used on Node 2.
    $pattern_revision_id = $this->plugin2->getConfiguration()['pattern'];

    // Expect this to be the same pattern entity referenced on Node 1.
    $this->assertSame($pattern_revision_id, $this->plugin1->getConfiguration()['pattern']);

    // Clear the cache tag invalidations to track new changes.
    $this->getCacheInvalidations(TRUE);

    // Change the hash and template for the pattern revision being used to
    // enable the update process.
    $updated_template = 'UPDATED TEMPLATE: ' . $this->getRandomGenerator()->sentences(3);
    $num_updated = $this->updateCachedPatternData($pattern_revision_id, [
      'hash' => $this->getRandomGenerator()->string(32),
      'template' => $updated_template,
    ]);
    $this->assertEquals(2, $num_updated, 'The revision and default pattern entries should have both been updated.');

    // Confirm the updated template is represented on both Nodes.
    $this->drupalGet('node/1');
    $assert->pageTextContains($updated_template);
    $this->drupalGet('node/2');
    $assert->pageTextContains($updated_template);

    // Log in to make layout updates.
    $this->drupalLogin($this->editorUser);

    // Edit the pattern on Node 2.
    $this->drupalGet('node/2/layout');
    $assert->waitForElementVisible('css', static::PATTERN_BLOCK_LOCATOR);
    /** @var \Behat\Mink\Element\NodeElement $pattern_block */
    $pattern_block = $page->findAll('css', static::PATTERN_BLOCK_LOCATOR)[0];
    $uuid = $pattern_block->getAttribute('data-layout-block-uuid');
    $block_css_locator = static::PATTERN_BLOCK_LOCATOR . "[data-layout-block-uuid=\"$uuid\"]";
    $this->clickContextualLink($block_css_locator, 'Configure');
    $assert->waitForElement('css', 'form.layout-builder-configure-block');

    // The update prompt should be present.
    $assert->pageTextContains('Update pattern schema and template');
    $page->pressButton('Update pattern');
    $assert->assertWaitOnAjaxRequest();
    $assert->pageTextContains('Successfully updated the pattern schema and template');

    // Identify cache tags specific to the pattern entity.
    $pattern_entity_tags = [
      'patternkit_pattern:1',
      'pattern:' . static::DEFAULT_PATTERN_ID,
    ];

    // At this point, no cache tags should have been invalidated.
    $this->assertCacheTagsNotInvalidated([
      'node:2',
      'patternkit_block:2',
    ] + $pattern_entity_tags);

    // Save the block and check cache tags again. Cache invalidations should
    // still not be applied until the layout itself is saved.
    $page->pressButton('Update');
    $this->assertDialogClosedAndTextVisible('Sample twig template.');
    $this->assertCacheTagsNotInvalidated([
      'node:2',
      'patternkit_block:2',
    ] + $pattern_entity_tags);

    // Save the layout and confirm cache invalidations.
    $this->assertSaveLayout();
    $this->assertCacheTagsInvalidated([
      'node:2',
      'patternkit_block:2',
    ], FALSE);
    $this->assertCacheTagsNotInvalidated([
      'node:1',
      'patternkit_block:1',
    ], FALSE);

    if ($withEntityTagInvalidation) {
      $this->assertCacheTagsInvalidated($pattern_entity_tags);
    }
    else {
      $this->assertCacheTagsNotInvalidated($pattern_entity_tags);
    }

    // Confirm Node 1 still shows the manually altered revision of the pattern.
    $this->drupalGet('node/1');
    $assert->pageTextContains($updated_template);

    // Confirm Node 2 shows the updated template output.
    $this->drupalGet('node/2');
    $assert->pageTextContains('Sample twig template.');
  }

  /**
   * Data provider for testPatternUpdateInvalidation().
   */
  public static function providerPatternUpdateInvalidation(): array {
    return [
      'with entity tag invalidation' => [TRUE],
      'without entity tag invalidation' => [FALSE],
    ];
  }

  /**
   * Place a patternkit example block on an overridden layout for a given Node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node entity to place the block on.
   * @param array $content
   *   An associative array of configuration values for the example pattern
   *   block configuration form.
   *
   * @return \Drupal\patternkit\Plugin\Block\PatternkitBlock
   *   The block plugin for the created Patternkit Block.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function placeExampleBlock(NodeInterface $node, array $content): PatternkitBlock {
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example';
    $nid = $node->id();

    // Override the Node layout and place a patternkit block.
    $this->drupalGet("node/$nid/layout");
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $form = $this->waitForJsonEditorForm();

    // Fill in JSON Editor fields.
    $form->fillField('root[text]', $content['text']);
    $form->fillField('root[hidden]', $content['hidden']);
    $form->fillField('root[formatted_text]', $content['formatted_text']);

    $page->pressButton('Add block');
    $this->assertSaveLayout();

    // Load the latest revision of the Node after saving the layout updates.
    $latest_revision_id = $this->nodeStorage->getLatestRevisionId($node->id());
    $node = $this->nodeStorage->loadRevision($latest_revision_id);

    $sections = $this->getEntitySections($node);
    $components = $sections[0]->getComponents();
    /** @var \Drupal\layout_builder\SectionComponent $component */
    $component = end($components);
    /** @var \Drupal\patternkit\Plugin\Block\PatternkitBlock $plugin */
    $plugin = $component->getPlugin();

    return $plugin;
  }

}
