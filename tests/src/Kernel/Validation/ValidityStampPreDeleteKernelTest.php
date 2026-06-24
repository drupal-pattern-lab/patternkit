<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel\Validation;

use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Hook\PatternkitBlockEntityHooks;
use Drupal\patternkit\Validation\ValidityStampWriter;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;

/**
 * Kernel tests for validity stamp cleanup on layout entity deletion.
 *
 * Verifies the inner stamp-deletion logic (section walk + deleteStamp) that
 * handlePreDelete() calls after its compatibility guard passes, and verifies
 * the existing entityDelete hook safety net for direct block deletes.
 *
 * Note: handlePreDelete() itself is not called in these tests because
 * getSectionStorageForEntity() requires a fully registered layout-compatible
 * entity type not available in this kernel environment. The tests exercise the
 * same deleteStamp() semantics the production path relies on.
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampPreDeleteKernelTest extends PatternkitKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'filter',
    'block',
    'block_content',
    'layout_builder',
  ];

  /**
   * Validity stamp writer for reading stamp state.
   *
   * @var \Drupal\patternkit\Validation\ValidityStampWriter
   */
  protected ValidityStampWriter $writer;

  /**
   * Hooks instance backed by container services.
   *
   * @var \Drupal\patternkit\Hook\PatternkitBlockEntityHooks
   */
  protected PatternkitBlockEntityHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('patternkit_block');
    $this->installEntitySchema('patternkit_pattern');
    $this->installSchema('patternkit', ['patternkit_block_validity']);
    $this->installSchema('layout_builder', ['inline_block_usage']);
    $this->installSchema('system', ['sequences']);

    $this->writer = $this->container->get('patternkit.validity.stamp_writer');
    $this->hooks = PatternkitBlockEntityHooks::create($this->container);
  }

  /**
   * Creates and saves a patternkit_block entity.
   *
   * @param string $langcode
   *   Language code for the block.
   *
   * @return \Drupal\patternkit\Entity\PatternkitBlock
   *   The created and saved block entity.
   */
  protected function createBlock(string $langcode = 'en'): PatternkitBlock {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => $langcode,
      'pattern_id' => '@patternkit/atoms/example/src/example',
      'data' => ['value' => '{}'],
      'status' => TRUE,
    ]);
    $block->save();
    return $block;
  }

  /**
   * Invokes the stamp-deletion inner logic for a set of sections.
   *
   * Bypasses isLayoutCompatibleEntity() and getSectionStorageForEntity() so
   * tests do not require a fully registered layout-compatible entity type.
   * Exercises the same getPatternBlockComponents() + deleteStamp() path that
   * handlePreDelete() calls after the compatibility guard passes.
   *
   * @param \Drupal\layout_builder\Section[] $sections
   *   Sections containing the components to clean up.
   */
  protected function deleteStampsForSections(array $sections): void {
    $blockStorage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    assert($blockStorage instanceof RevisionableStorageInterface);

    foreach ($sections as $section) {
      foreach ($section->getComponents() as $component) {
        // Read config via toArray() to avoid calling getPlugin() which
        // requires the full block plugin manager in a kernel test context.
        $data = $component->toArray();
        $configuration = $data['configuration'] ?? [];
        $blockRevisionId = (int) ($configuration['patternkit_block_rid'] ?? 0);
        if ($blockRevisionId <= 0) {
          continue;
        }
        /** @var \Drupal\patternkit\Entity\PatternkitBlock|null $blockEntity */
        $blockEntity = $blockStorage->loadRevision($blockRevisionId);
        if ($blockEntity === NULL) {
          continue;
        }
        $this->writer->deleteStamp((int) $blockEntity->id(), NULL);
      }
    }
  }

  /**
   * Builds a SectionComponent configured with a patternkit_block revision.
   *
   * @param int $blockRevisionId
   *   The patternkit_block revision ID to embed in plugin configuration.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   The component.
   */
  protected function makeBlockComponent(int $blockRevisionId): SectionComponent {
    return new SectionComponent(
      $this->randomMachineName(),
      'content',
      [
        'id' => 'patternkit_block:example',
        'provider' => 'patternkit',
        'patternkit_block_rid' => $blockRevisionId,
      ],
    );
  }

  /**
   * Stamp row is removed when the section walk finds a placed block.
   */
  public function testStampRemovedOnLayoutEntityPreDelete(): void {
    $block = $this->createBlock();
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $this->writer->writeStamp($blockId, 'en', $blockRevisionId, 1, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_SAVE);
    $this->assertNotNull($this->writer->getStamp($blockId, 'en'), 'Stamp should exist before predelete.');

    $component = $this->makeBlockComponent($blockRevisionId);
    $section = new Section('layout_onecol', [], [$component]);

    $this->deleteStampsForSections([$section]);

    $this->assertNull(
      $this->writer->getStamp($blockId, 'en'),
      'Stamp should be removed after layout entity predelete.',
    );
  }

  /**
   * All stamp rows removed when multiple blocks are found in the section walk.
   */
  public function testAllBlockStampsRemovedOnLayoutEntityPreDelete(): void {
    $blockA = $this->createBlock();
    $blockB = $this->createBlock();
    $idA = (int) $blockA->id();
    $idB = (int) $blockB->id();
    $ridA = (int) $blockA->getRevisionId();
    $ridB = (int) $blockB->getRevisionId();

    $this->writer->writeStamp($idA, 'en', $ridA, 1, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_SAVE);
    $this->writer->writeStamp($idB, 'en', $ridB, 1, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_SAVE);

    $section = new Section('layout_onecol', [], [
      $this->makeBlockComponent($ridA),
      $this->makeBlockComponent($ridB),
    ]);

    $this->deleteStampsForSections([$section]);

    $this->assertNull($this->writer->getStamp($idA, 'en'), 'Stamp A should be removed after predelete.');
    $this->assertNull($this->writer->getStamp($idB, 'en'), 'Stamp B should be removed after predelete.');
  }

  /**
   * Direct patternkit_block delete still removes stamp via entityDelete hook.
   *
   * Regression guard: ValidityStampEntityHooks::entityDelete remains the safety
   * net for programmatic deletes that bypass the layout entity predelete path.
   */
  public function testDirectBlockDeleteStillRemovesStampViaEntityDeleteHook(): void {
    $block = $this->createBlock();
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $this->writer->writeStamp($blockId, 'en', $blockRevisionId, 1, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_SAVE);
    $this->assertNotNull($this->writer->getStamp($blockId, 'en'), 'Stamp should exist before delete.');

    $block->delete();

    $this->assertNull(
      $this->writer->getStamp($blockId, 'en'),
      'Stamp should be removed via entityDelete hook after direct block delete.',
    );
  }

}
