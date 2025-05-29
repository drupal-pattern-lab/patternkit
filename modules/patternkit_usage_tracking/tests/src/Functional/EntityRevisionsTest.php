<?php

namespace Drupal\Tests\patternkit_usage_tracking\Functional;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\node\Entity\Node;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\patternkit\Functional\PatternkitBrowserTestBase;
use Drupal\Tests\patternkit_usage_tracking\Traits\PatternUsageAssertionTrait;

/**
 * Tests for validating usage tracking during entity operations with revisions.
 *
 * @group patternkit
 * @group patternkit_usage_tracking
 * @covers \Drupal\patternkit_usage_tracking\PatternUsageEntityOperations
 * @covers \Drupal\patternkit_usage_tracking\LayoutUsageTracker
 * @covers \Drupal\patternkit_usage_tracking\UsageRevisionHelper
 * @coversFunction patternkit_usage_tracking_entity_update
 */
class EntityRevisionsTest extends PatternkitBrowserTestBase {

  use LayoutBuilderContextTrait;
  use PatternUsageAssertionTrait;
  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'patternkit_usage_tracking',

    // Enable content moderation workflows for revision testing.
    'workflows',
    'content_moderation',
  ];

  /**
   * Storage handler for pattern_usage content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternUsageStorage;

  /**
   * Storage handler for node content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $nodeStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->patternUsageStorage = $entity_type_manager->getStorage('pattern_usage');
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->sectionStorageManager = $this->container->get('plugin.manager.layout_builder.section_storage');

    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'bundle_with_layout_enabled');
  }

  /**
   * Test pattern discovery during an update operation.
   */
  public function testLayoutUpdate(): void {
    // Place an example block in the layout.
    $pattern_name = '@patternkit/atoms/example/src/example';
    $block = $this->createPatternBlock($pattern_name, [
      'text' => 'Pattern block title',
      'formatted_text' => 'Pattern block body',
      'image_url' => '',
      'hidden' => 'Hidden text',
    ]);
    $simple_block__uuid = $block->getBlockEntity()->uuid();

    $node = $this->nodeStorage->load(1);
    $this->assertInstanceOf(Node::class, $node);
    $this->placePatternBlockInLayout($node, $block);
    $node->set('moderation_state', 'published');
    $node->save();
    $original_revision_id = $node->getRevisionId();

    $this->assertTrackedPatternUsageByEntity($node, $pattern_name);

    // Create a new block to be placed in the updated revision.
    $pattern_with_reference = '@patternkit/atoms/example_ref/src/example_ref';
    $block = $this->createPatternBlock($pattern_with_reference, [
      'text' => 'Pattern with reference',
      'nested_reference' => [
        'text' => 'Nested block title',
        'formatted_text' => 'Nested pattern block body',
        'image_url' => '',
        'hidden' => 'Hidden text',
      ],
    ]);
    $nesting_block__uuid = $block->getBlockEntity()->uuid();

    // Place the new block in the layout and save a new revision.
    $this->placePatternBlockInLayout($node, $block);
    $node->setNewRevision();
    $node->set('moderation_state', 'draft');
    $node->save();
    $new_revision_id = $node->getRevisionId();
    $this->assertNotEquals($original_revision_id, $new_revision_id);

    // Expect the original block from the published revision to be tracked.
    $this->assertTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $original_revision_id,
      'path' => "0.$simple_block__uuid:$pattern_name",
    ], "Expected to find recorded usage of pattern \"$pattern_name\".");

    // Expect the new block and nested references on the draft revision to not
    // be tracked.
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_with_reference, [
      'source_revision_id' => $new_revision_id,
    ], "Expected to find no recorded usage of pattern \"$pattern_with_reference\" on draft revision.");
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $new_revision_id,
    ], "Expected to find no recorded usage of nested pattern \"$pattern_name\" on draft revision.");

    // Publish the new revision to expose additional patterns. Records for
    // previously discovered revisions should be either updated or removed.
    $node->set('moderation_state', 'published');
    $node->save();
    $published_revision_id = $node->getRevisionId();

    // Expect to find usage records for all patterns on newly published
    // revision.
    $this->assertTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $published_revision_id,
      'path' => "0.$simple_block__uuid:$pattern_name",
    ], "Expected to find recorded usage of pattern \"$pattern_name\" on newly published revision.");
    $this->assertTrackedPatternUsageByEntity($node, $pattern_with_reference, [
      'source_revision_id' => $published_revision_id,
    ], "Expected to find recorded usage of pattern \"$pattern_with_reference\" on newly published revision.");
    $this->assertTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $published_revision_id,
      'path' => $path = "0.$nesting_block__uuid:$pattern_with_reference=>$pattern_name",
    ], "Expected to find recorded usage of nested pattern \"$pattern_name\" at path \"$path\".");

    // Expect to find no usage records for older revisions.
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $original_revision_id,
    ], "Expected not to find recorded usage of pattern \"$pattern_name\" for original revision.");
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $new_revision_id,
    ], "Expected not to find recorded usage of pattern \"$pattern_name\" for draft revision.");
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_with_reference, [
      'source_revision_id' => $new_revision_id,
    ], "Expected not to find recorded usage of pattern \"$pattern_with_reference\" for draft revision.");
  }

}
