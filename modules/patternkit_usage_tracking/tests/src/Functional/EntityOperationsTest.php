<?php

namespace Drupal\Tests\patternkit_usage_tracking\Functional;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\patternkit\Functional\PatternkitBrowserTestBase;
use Drupal\Tests\patternkit_usage_tracking\Traits\PatternUsageAssertionTrait;

/**
 * Tests for validating usage tracking during entity operations.
 *
 * @group patternkit
 * @group patternkit_usage_tracking
 *
 * @covers \Drupal\patternkit_usage_tracking\PatternUsageEntityOperations
 * @covers \Drupal\patternkit_usage_tracking\LayoutUsageTracker
 */
class EntityOperationsTest extends PatternkitBrowserTestBase {

  use LayoutBuilderContextTrait;
  use PatternUsageAssertionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'patternkit_usage_tracking',
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
  }

  /**
   * Test pattern discovery during an update operation.
   *
   * @covers \patternkit_usage_tracking_entity_update()
   * @covers \Drupal\patternkit_usage_tracking\UsageRevisionHelper
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
    $node->save();
    $new_revision_id = $node->getRevisionId();
    $this->assertNotEquals($original_revision_id, $new_revision_id);

    // Expect the top-level pattern usages to be discovered.
    $this->assertTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $new_revision_id,
      'path' => "0.$simple_block__uuid:$pattern_name",
    ], "Expected to find recorded usage of pattern \"$pattern_name\".");
    $this->assertTrackedPatternUsageByEntity($node, $pattern_with_reference, [
      'source_revision_id' => $new_revision_id,
      'path' => "0.$nesting_block__uuid:$pattern_with_reference",
    ], "Expected to find recorded usage of pattern \"$pattern_with_reference\".");

    // Confirm nested pattern usages was discovered. Multiple usages of the same
    // pattern will only result in a single record.
    $this->assertTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $new_revision_id,
      'path' => $path = "0.$nesting_block__uuid:$pattern_with_reference=>$pattern_name",
    ], "Expected to find recorded usage of nested pattern \"$pattern_name\" at path \"$path\".");

    // Confirm no records with the prior revision ID are still in place.
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $original_revision_id,
    ], "Expected not to find recorded usage of pattern \"$pattern_name\" for original revision.");
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_with_reference, [
      'source_revision_id' => $original_revision_id,
    ], "Expected not to find recorded usage of pattern \"$pattern_with_reference\" for original revision.");
  }

  /**
   * Test detection of deleted pattern usages.
   *
   * @covers \patternkit_usage_tracking_entity_update()
   * @covers \Drupal\patternkit_usage_tracking\UsageRevisionHelper
   *
   * @depends testLayoutUpdate
   */
  public function testUsageDeletion(): void {
    $pattern_name = '@patternkit/atoms/example/src/example';
    $pattern_with_reference = '@patternkit/atoms/example_ref/src/example_ref';

    $node = $this->nodeStorage->load(1);
    $this->assertInstanceOf(Node::class, $node);
    $layout = $this->mockLayout($node);

    // Confirm pattern usages were discovered and recorded.
    $this->assertTrackedPatternUsageByEntity($node, $pattern_name, [], "Expected to find recorded usage of pattern \"$pattern_name\".");
    $this->assertTrackedPatternUsageByEntity($node, $pattern_with_reference, [], "Expected to find recorded usage of pattern \"$pattern_with_reference\".");

    // Update the node and remove references.
    $section = $layout->getSection(0);
    $components = $section->getComponents();
    $keys = array_keys($components);
    $section->removeComponent(end($keys));
    $layout->save();

    $node->setNewRevision();
    $node->save();

    // Validate removed usages were detected.
    $this->assertTrackedPatternUsageByEntity($node, $pattern_name, [
      'source_revision_id' => $node->getRevisionId(),
    ], "Expected to find recorded usage of pattern \"$pattern_name\".");
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_with_reference, [
      'source_revision_id' => $node->getRevisionId(),
    ], "Did not expect to find recorded usage of pattern \"$pattern_with_reference\".");
  }

  /**
   * Create a layout with mocked pattern data components.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to create a layout for.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The created and saved layout.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function mockLayout(ContentEntityInterface $entity): SectionStorageInterface {
    // Outline mocked layout data for testing.
    $layout_data = [
      0 => [
        'uuid_1' => [
          'pattern_name' => '@patternkit/atoms/example/src/example',
          'text' => 'Pattern block title',
          'formatted_text' => 'Pattern block body',
          'image_url' => '',
          'hidden' => 'Hidden text',
        ],
        'uuid_2' => [
          'pattern_name' => '@patternkit/atoms/example_ref/src/example_ref',
          'text' => 'Pattern block title',
          'nested_reference' => [
            'text' => 'Nested block title',
            'formatted_text' => 'Nested block body',
            'image_url' => '',
            'hidden' => 'Hidden text',
          ],
        ],
      ],
    ];

    // Create and save an overridden layout.
    $this->createLayout($entity, $layout_data);
    $entity->save();

    // Reload the layout to ensure the active instance is returned.
    return $this->getSectionStorageForEntity($entity);
  }

  /**
   * Create an overridden layout from scaffolded content data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to create a layout for.
   * @param array<int, array<string, mixed>> $content_data
   *   An array of data representing a layout to be instantiated. Top-level
   *   items are keyed by section ID, and should contain references to either
   *   an array of pattern component data or an instantiated section component.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The instantiated layout with populated content.
   */
  protected function createLayout(ContentEntityInterface $entity, array $content_data): SectionStorageInterface {
    // Load the default layout for reference.
    $default_layout = $this->getSectionStorageForEntity($entity);

    // Assemble contexts for creating an overridden layout.
    $contexts = $this->getPopulatedContexts($default_layout);
    $contexts['entity'] = EntityContext::fromEntity($entity);

    // Load an overridden layout to work with.
    $layout = $this->sectionStorageManager->load('overrides', $contexts);
    $this->assertNotNull($layout);

    // Create each section from the content data.
    foreach ($content_data as $section_id => $components) {
      $section = new Section('layout_onecol');

      // Populate components into each section.
      foreach ($components as $component_data) {
        $component = $this->createComponent($component_data);
        $section->appendComponent($component);
      }

      $layout->insertSection($section_id, $section);
    }

    return $layout;
  }

  /**
   * Instantiate a section component for use in a layout.
   *
   * @param array{pattern_name?: string}|\Drupal\layout_builder\SectionComponent $component_data
   *   An array of configuration data for a pattern component or an instantiated
   *   SectionComponent. Pattern component data is required to contain a
   *   "pattern_name" key containing the asset name of the pattern to be used.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   The instantiated section component.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  protected function createComponent($component_data): SectionComponent {
    $component = NULL;

    // Return the component immediately if one was already created as part of
    // provided data.
    if ($component_data instanceof SectionComponent) {
      return $component_data;
    }

    // Assume an array contains pattern data to be created as a block.
    if (is_array($component_data)) {
      assert(isset($component_data['pattern_name']), 'Mocked component data is expected to contain a specified "pattern_name" key.');
      $pattern_name = $component_data['pattern_name'];
      unset($component_data['pattern_name']);

      $block = $this->createPatternBlock($pattern_name, $component_data);
      $config = $block->getConfiguration();
      $uuid = $block->getBlockEntity()->uuid();
      $component = new SectionComponent($uuid, 'content', $config);
    }

    // Fail if no section component was created.
    // @phpstan-ignore-next-line
    if ($component === NULL) {
      throw new \RuntimeException('Failed to create layout component.');
    }

    return $component;
  }

  /**
   * Test usage removal when an entity is deleted.
   *
   * @covers \patternkit_usage_tracking_entity_predelete()
   * @covers \Drupal\patternkit_usage_tracking\UsageRevisionHelper
   */
  public function testEntityDeletion(): void {
    $pattern_name = '@patternkit/atoms/example/src/example';
    $pattern_with_reference = '@patternkit/atoms/example_ref/src/example_ref';

    $node = $this->nodeStorage->load(1);
    $this->assertInstanceOf(Node::class, $node);
    $this->mockLayout($node);

    // Confirm pattern usages were discovered and recorded.
    $this->assertTrackedPatternUsageByEntity($node, $pattern_name, [], "Expected to find recorded usage of pattern \"$pattern_name\".");
    $this->assertTrackedPatternUsageByEntity($node, $pattern_with_reference, [], "Expected to find recorded usage of pattern \"$pattern_with_reference\".");

    // Delete the node.
    $node->delete();

    // Confirm related pattern usages were deleted.
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_name, [], "Expected to find no recorded usage of pattern \"$pattern_name\".");
    $this->assertNotTrackedPatternUsageByEntity($node, $pattern_with_reference, [], "Expected to find no recorded usage of pattern \"$pattern_with_reference\".");
  }

}
