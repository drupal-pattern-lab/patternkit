<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel\Validation;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Validation\ValidityStampWriter;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;

/**
 * Kernel tests for orphan stamp cleanup via entity delete hooks.
 *
 * Verifies that patternkit_entity_delete and
 * patternkit_entity_translation_delete hook wrappers correctly invoke
 * ValidityStampEntityHooks, which calls deleteStamp() to remove rows
 * from patternkit_block_validity.
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampOrphanCleanupKernelTest extends PatternkitKernelTestBase {

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
    'language',
  ];

  /**
   * Validity stamp writer service.
   *
   * Typed as the concrete class (not the interface) so that kernel tests can
   * call getStamp() for assertion purposes — the interface is write-only.
   *
   * @var \Drupal\patternkit\Validation\ValidityStampWriter
   */
  protected ValidityStampWriter $writer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('patternkit_block');
    $this->installEntitySchema('configurable_language');
    $this->installSchema('patternkit', ['patternkit_block_validity']);
    $this->installSchema('system', ['sequences']);
    $this->writer = $this->container->get('patternkit.validity.stamp_writer');
  }

  /**
   * Creates a saved patternkit_block entity.
   *
   * @param string $langcode
   *   The language code for the block.
   *
   * @return \Drupal\patternkit\Entity\PatternkitBlock
   *   The created and saved block entity.
   */
  protected function createBlock(string $langcode = 'en'): PatternkitBlock {
    $storage = $this->container->get('entity_type.manager')->getStorage('patternkit_block');
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
   * Stamp row is removed after block entity delete.
   *
   * Writes a stamp for a block, deletes the block entity, and asserts the
   * stamp row is gone. This exercises the full hook wiring:
   * patternkit_entity_delete → ValidityStampEntityHooks::entityDelete
   * → deleteStamp.
   */
  public function testStampRemovedOnEntityDelete(): void {
    $block = $this->createBlock();
    $blockId = (int) $block->id();

    $this->writer->writeStamp($blockId, 'en', 1, 2, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_SAVE);
    $this->assertNotNull($this->writer->getStamp($blockId, 'en'), 'Stamp should exist before delete.');

    $block->delete();

    $this->assertNull($this->writer->getStamp($blockId, 'en'), 'Stamp should be removed after entity delete.');
  }

  /**
   * Only the target-language stamp is removed on translation delete.
   *
   * Creates an EN block with an FR translation, writes stamps for both
   * languages, deletes the FR translation via the entity API, and asserts
   * only the FR stamp is removed while the EN stamp persists.
   *
   * Exercises the full hook wiring through Drupal's HookCollectorPass
   * (not the dead LegacyHook wrapper): real entity API delete →
   * hook_entity_translation_delete →
   * ValidityStampEntityHooks::entityTranslationDelete → deleteStamp.
   */
  public function testOnlyTargetTranslationStampRemovedOnTranslationDelete(): void {
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $block = $this->createBlock('en');
    $blockId = (int) $block->id();

    // Add a French translation and save so Drupal tracks it.
    $frTranslation = $block->addTranslation('fr', $block->toArray());
    $frTranslation->save();

    $this->writer->writeStamp($blockId, 'en', 1, 2, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_SAVE);
    $this->writer->writeStamp($blockId, 'fr', 1, 2, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_SAVE);

    $this->assertNotNull(
      $this->writer->getStamp($blockId, 'en'),
      'EN stamp should exist before translation delete.',
    );
    $this->assertNotNull(
      $this->writer->getStamp($blockId, 'fr'),
      'FR stamp should exist before translation delete.',
    );

    // Delete the FR translation via entity API so Drupal fires
    // hook_entity_translation_delete through HookCollectorPass dispatch.
    $block->removeTranslation('fr');
    $block->save();

    $this->assertNull(
      $this->writer->getStamp($blockId, 'fr'),
      'FR stamp should be removed after FR translation delete.',
    );
    $this->assertNotNull(
      $this->writer->getStamp($blockId, 'en'),
      'EN stamp should remain after FR translation delete.',
    );
  }

  /**
   * Deleting a block with no stamp row does not cause a database error.
   *
   * Verifies deleteStamp is idempotent — deleting a block that was never
   * stamped does not raise an error.
   */
  public function testDeleteWithNoStampIsIdempotent(): void {
    $block = $this->createBlock();
    $blockId = (int) $block->id();

    // No stamp written — delete must not error or create a spurious row.
    $block->delete();

    $this->assertNull(
      $this->writer->getStamp($blockId, 'en'),
      'Delete without prior stamp must not throw or create a spurious stamp row.',
    );
  }

  /**
   * All translation stamp rows for a block are removed on entity delete.
   *
   * Writes stamps for two language variants; deletes the block entity and
   * asserts both rows are gone.
   */
  public function testAllTranslationStampsRemovedOnEntityDelete(): void {
    $block = $this->createBlock('en');
    $blockId = (int) $block->id();

    $this->writer->writeStamp($blockId, 'en', 1, 2, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_SAVE);
    $this->writer->writeStamp($blockId, 'fr', 1, 2, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_SAVE);

    $block->delete();

    $this->assertNull($this->writer->getStamp($blockId, 'en'), 'EN stamp should be removed after entity delete.');
    $this->assertNull($this->writer->getStamp($blockId, 'fr'), 'FR stamp should be removed after entity delete.');
  }

}
