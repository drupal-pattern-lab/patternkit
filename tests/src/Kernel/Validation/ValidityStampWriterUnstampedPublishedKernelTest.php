<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel\Validation;

use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;

/**
 * Kernel coverage for published-only unstamped translation queries.
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampWriterUnstampedPublishedKernelTest extends PatternkitKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'text',
    'user',
    'system',
    'field',
    'filter',
    'block',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('patternkit_block');
    $this->installSchema('patternkit', ['patternkit_block_validity']);
  }

  /**
   * Creates and saves a patternkit_block with the given publication flag.
   *
   * @param bool $published
   *   Whether the block should be published.
   *
   * @return \Drupal\patternkit\Entity\PatternkitBlock
   *   The created patternkit_block entity.
   */
  protected function createSavedBlock(bool $published): PatternkitBlock {
    $storage = $this->container->get('entity_type.manager')->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => 'en',
      'pattern_id' => '@patternkit/atoms/example/src/example',
      'data' => ['value' => '{}'],
      'status' => $published,
    ]);
    $block->save();
    return $block;
  }

  /**
   * Omits unpublished translations from unstamped enqueue results.
   */
  public function testGetUnstampedBlockTranslationsOmitsUnpublished(): void {
    $published = $this->createSavedBlock(TRUE);
    $this->createSavedBlock(FALSE);

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $rows = $writer->getUnstampedBlockTranslations(10);

    $this->assertCount(1, $rows);
    $this->assertSame((int) $published->id(), $rows[0]['block_entity_id']);
    $this->assertSame('en', $rows[0]['langcode']);
  }

  /**
   * Returns stale-stamp rows when pattern default revision has advanced.
   */
  public function testGetStaleStampBlockTranslationsReturnsStaleRows(): void {
    $this->installEntitySchema('patternkit_pattern');
    $this->installSchema('system', ['sequences']);

    $db = $this->container->get('database');
    $uuid = \Drupal::service('uuid')->generate();
    $hash = md5('{}');

    $entityId = $db->insert('pattern')
      ->fields([
        'uuid' => $uuid,
        'hash' => $hash,
        'name__value' => 'Test',
        'assets' => serialize([]),
        'category' => 'test',
        'description' => '',
        'library' => 'patternkit',
        'libraryPluginId' => 'json',
        'path' => 'atoms/test/stale',
        'schema' => '{}',
        'template' => '',
        'version' => '1.0',
      ])
      ->execute();

    $rev1 = $db->insert('pattern_revision')
      ->fields([
        'id' => $entityId,
        'hash' => $hash,
        'schema' => '{}',
        'template' => '',
        'version' => '1.0',
        'revision_default' => 0,
      ])
      ->execute();

    $rev2 = $db->insert('pattern_revision')
      ->fields([
        'id' => $entityId,
        'hash' => $hash,
        'schema' => '{}',
        'template' => '',
        'version' => '1.0',
        'revision_default' => 1,
      ])
      ->execute();

    $db->update('pattern')
      ->fields(['revision' => $rev2])
      ->condition('id', $entityId)
      ->execute();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => 'en',
      'pattern_id' => '@patternkit/atoms/test/stale',
      'data' => ['value' => '{}'],
      'status' => TRUE,
    ]);
    $block->save();
    $blockId = (int) $block->id();

    // Write a stamp with rev1 (stale; current default is rev2).
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $writer->writeStamp($blockId, 'en', (int) $block->getRevisionId(), (int) $rev1, TRUE, FALSE, 'save');

    ['stale' => $stale] = $writer->getStaleStampBlockTranslations(50);

    $this->assertCount(1, $stale);
    $this->assertSame($blockId, $stale[0]['block_entity_id']);
    $this->assertSame('en', $stale[0]['langcode']);
  }

  /**
   * Does not return rows already stamped at the current default revision.
   */
  public function testGetStaleStampBlockTranslationsSkipsFreshRows(): void {
    $this->installEntitySchema('patternkit_pattern');
    $this->installSchema('system', ['sequences']);

    $db = $this->container->get('database');
    $uuid = \Drupal::service('uuid')->generate();
    $hash = md5('{}');

    $entityId = $db->insert('pattern')
      ->fields([
        'uuid' => $uuid,
        'hash' => $hash,
        'name__value' => 'Fresh',
        'assets' => serialize([]),
        'category' => 'test',
        'description' => '',
        'library' => 'patternkit',
        'libraryPluginId' => 'json',
        'path' => 'atoms/test/fresh',
        'schema' => '{}',
        'template' => '',
        'version' => '1.0',
      ])
      ->execute();

    $rev1 = $db->insert('pattern_revision')
      ->fields([
        'id' => $entityId,
        'hash' => $hash,
        'schema' => '{}',
        'template' => '',
        'version' => '1.0',
        'revision_default' => 1,
      ])
      ->execute();

    $db->update('pattern')
      ->fields(['revision' => $rev1])
      ->condition('id', $entityId)
      ->execute();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => 'en',
      'pattern_id' => '@patternkit/atoms/test/fresh',
      'data' => ['value' => '{}'],
      'status' => TRUE,
    ]);
    $block->save();
    $blockId = (int) $block->id();

    // Stamp at the current default revision (not stale).
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $writer->writeStamp($blockId, 'en', (int) $block->getRevisionId(), (int) $rev1, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_CRON);

    ['stale' => $stale, 'unresolvable' => $unresolvable] = $writer->getStaleStampBlockTranslations(50);

    $this->assertCount(0, $stale);
    $this->assertCount(0, $unresolvable);
  }

  /**
   * Returns blocks with unresolvable pattern refs in the unresolvable list.
   *
   * A block stamped with a pattern_id that no longer has a matching pattern
   * entity must appear in unresolvable, not stale, with all three identifying
   * fields present.
   */
  public function testGetStaleStampBlockTranslationsReturnsUnresolvableRows(): void {
    $this->installEntitySchema('patternkit_pattern');
    $this->installSchema('system', ['sequences']);

    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => 'en',
      'pattern_id' => '@patternkit/atoms/test/deleted',
      'data' => ['value' => '{}'],
      'status' => TRUE,
    ]);
    $block->save();
    $blockId = (int) $block->id();

    // Write a stamp referencing a pattern revision that has no pattern entity.
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $writer->writeStamp($blockId, 'en', (int) $block->getRevisionId(), 9999, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_CRON);

    ['stale' => $stale, 'unresolvable' => $unresolvable] = $writer->getStaleStampBlockTranslations(50);

    $this->assertCount(0, $stale);
    $this->assertCount(1, $unresolvable);
    $this->assertSame($blockId, $unresolvable[0]['block_entity_id']);
    $this->assertSame('en', $unresolvable[0]['langcode']);
    $this->assertSame('@patternkit/atoms/test/deleted', $unresolvable[0]['pattern_id']);
  }

}
