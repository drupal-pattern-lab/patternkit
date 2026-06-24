<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel\Validation;

use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;

/**
 * Tests the patternkit_block_validity schema and patternkit_update_10311().
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampTableTest extends PatternkitKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('patternkit', ['patternkit_block_validity']);
  }

  /**
   * Asserts expected columns and indexes exist for patternkit_block_validity.
   */
  public function testPatternkitBlockValidityTableSchema(): void {
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->tableExists('patternkit_block_validity'));

    foreach ([
      'block_entity_id',
      'langcode',
      'block_revision_id',
      'pattern_revision_id',
      'is_valid',
      'has_tokens',
      'validated_at',
      'stamp_source',
      'component_uuid',
    ] as $column) {
      $this->assertTrue(
        $schema->fieldExists('patternkit_block_validity', $column),
        sprintf('Column %s exists.', $column)
      );
    }

    $this->assertTrue($schema->indexExists('patternkit_block_validity', 'block_validity_is_valid'));
    $this->assertTrue($schema->indexExists('patternkit_block_validity', 'block_validity_pattern_revision'));
    $this->assertTrue($schema->indexExists('patternkit_block_validity', 'block_validity_component_uuid'));
  }

  /**
   * Tests patternkit_update_10311() creates the table and tolerates re-runs.
   */
  public function testUpdate10311CreatesTableIdempotent(): void {
    $schema = \Drupal::database()->schema();
    $schema->dropTable('patternkit_block_validity');
    $this->assertFalse($schema->tableExists('patternkit_block_validity'));

    patternkit_update_10311();
    $this->assertTrue($schema->tableExists('patternkit_block_validity'));

    patternkit_update_10311();
    $this->assertTrue($schema->tableExists('patternkit_block_validity'));
  }

  /**
   * Tests SQL defaults when inserting only required (non-default) columns.
   */
  public function testPatternkitBlockValidityTableColumnDefaults(): void {
    $connection = \Drupal::database();
    $connection->insert('patternkit_block_validity')
      ->fields([
        'block_entity_id' => 90001,
        'langcode' => 'en',
        'block_revision_id' => 1,
        'pattern_revision_id' => 2,
        'validated_at' => 1700000000,
      ])
      ->execute();

    $row = $connection->select('patternkit_block_validity', 'v')
      ->fields('v')
      ->condition('block_entity_id', 90001)
      ->execute()
      ->fetchAssoc();
    $this->assertNotFalse($row);
    $this->assertSame('0', $row['is_valid']);
    $this->assertSame('0', $row['has_tokens']);
    $this->assertSame('save', $row['stamp_source']);
    $this->assertNull($row['component_uuid']);
  }

  /**
   * Tests that hook_uninstall removes the validity stamp table.
   */
  public function testUninstallRemovesPatternkitBlockValidityTable(): void {
    $schema = \Drupal::database()->schema();
    $this->assertTrue($schema->tableExists('patternkit_block_validity'));

    patternkit_uninstall(FALSE);

    $this->assertFalse($schema->tableExists('patternkit_block_validity'));
  }

}
