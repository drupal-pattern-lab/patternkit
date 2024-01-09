<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Block placement and cache testing for patternkit blocks.
 *
 * @group patternkit
 */
class BlockTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
  ];

  /**
   * Tests that cache tags are properly set and bubbled up to the page cache.
   *
   * Verify that invalidation of these cache tags works:
   * - "patternkit_pattern:<pattern ID>"
   * - "patternkit_block:<block plugin ID>"
   */
  public function testBlockCacheTags(): void {
    // Place an example block in the layout.
    $pattern_name = '@patternkit/atoms/example/src/example';
    $block = $this->createPatternBlock($pattern_name, [
      'text' => 'Pattern block title',
      'formatted_text' => 'Pattern block body',
      'image_url' => '',
      'hidden' => 'Hidden text',
    ]);
    $block = $this->placePatternBlockInLayout(Node::load(1), $block);

    $config = $block->getConfiguration();
    $block_id = $config['patternkit_block_rid'];
    /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
    $pattern = $block->getPatternEntity();
    $pattern_id = $pattern->id();

    // The page cache only works for anonymous users.
    $this->drupalLogout();

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Prime the page cache.
    $this->drupalGet('node/1');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags in
    // the page cache.
    $this->drupalGet('node/1');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $cid_parts = [
      Url::fromUri('entity:node/1', ['absolute' => TRUE])
        ->toString(),
      '',
    ];
    $cid = implode(':', $cid_parts);
    $cache_entry = \Drupal::cache('page')->get($cid);
    $expected_cache_tags = [
      "pattern:$pattern_name",
      "patternkit_pattern:$pattern_id",
      "patternkit_block:$block_id",
    ];
    foreach ($expected_cache_tags as $tag) {
      $this->assertContains($tag, $cache_entry->tags);
    }

    // The pattern entity is modified; verify a cache miss.
    $pattern->setVersion(\Drupal::VERSION);
    $pattern->save();
    $this->drupalGet('node/1');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    // Now we should have a cache hit again.
    $this->drupalGet('node/1');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');

    // Place another pattern block; verify a cache miss.
    $new_pattern_name = '@patternkit/atoms/example_ref/src/example_ref';
    $new_block = $this->createPatternBlock($new_pattern_name, [
      'text' => 'Pattern block title',
      'nested_reference' => [
        'formatted_text' => 'Pattern block body',
        'image_url' => '',
        'hidden' => 'Hidden text',
      ],
    ]);
    $block = $this->placePatternBlockInLayout(Node::load(1), $new_block);

    // @todo Validate new block id/rid isn't coincidentally the same and passing.
    $config = $block->getConfiguration();
    $new_block_id = $config['patternkit_block_rid'];
    /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
    $new_pattern = $block->getPatternEntity();
    $new_pattern_id = $new_pattern->id();

    $this->drupalGet('node/1');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    // Verify a cache hit, but also the presence of the correct cache tags.
    $this->drupalGet('node/1');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $cache_entry = \Drupal::cache('page')->get($cid);
    $expected_cache_tags = [
      "pattern:$pattern_name",
      "patternkit_pattern:$pattern_id",
      "patternkit_block:$block_id",
      "pattern:$new_pattern_name",
      "patternkit_pattern:$new_pattern_id",
      "patternkit_block:$new_block_id",
    ];
    foreach ($expected_cache_tags as $tag) {
      $this->assertContains($tag, $cache_entry->tags);
    }

    // Now we should have a cache hit again.
    $this->drupalGet('node/1');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
  }

  /**
   * Test behavior when block content fails JSON decoding.
   *
   * @covers \Drupal\patternkit\Plugin\Block\PatternkitBlock::build()
   * @covers \Drupal\patternkit\Element\PatternError
   */
  public function testConfigDecodeException(): void {
    // Place an example block in the layout.
    $pattern_name = '@patternkit/atoms/example/src/example';
    $block = $this->createPatternBlock($pattern_name, [
      'text' => 'Pattern block title',
      'formatted_text' => 'Pattern block body',
      'image_url' => '',
      'hidden' => 'Hidden text',
    ]);
    $block = $this->placePatternBlockInLayout(Node::load(1), $block);

    // Override stored data with invalid data that won't decode.
    $block_entity = $block->getBlockEntity();
    $block_entity->set('data', 'INVALID DATA');
    $block_entity->save();

    // Expect the render failure to be caught and still allow the rest of the
    // page to render.
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Failed to render pattern Example (@patternkit/atoms/example/src/example).');
  }

  /**
   * Test behavior when block entity fails to load.
   *
   * @covers \Drupal\patternkit\Plugin\Block\PatternkitBlock::build()
   * @covers \Drupal\patternkit\Element\PatternError
   */
  public function testBlockLoadFailure(): void {
    // Place an example block in the layout.
    $pattern_name = '@patternkit/atoms/example/src/example';
    $block = $this->createPatternBlock($pattern_name, [
      'text' => 'Pattern block title',
      'formatted_text' => 'Pattern block body',
      'image_url' => '',
      'hidden' => 'Hidden text',
    ]);
    $block = $this->placePatternBlockInLayout(Node::load(1), $block);

    // Delete the block entity, so it won't load successfully.
    $block_entity = $block->getBlockEntity();
    $block_entity->delete();

    // Expect the render failure to be caught and still allow the rest of the
    // page to render.
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Failed to render pattern Example (@patternkit/atoms/example/src/example).');
  }

}
