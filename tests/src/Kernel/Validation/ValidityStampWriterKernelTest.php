<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel\Validation;

use Drupal\patternkit\Validation\ValidityStampWriter;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;

/**
 * Integration tests for patternkit.validity.stamp_writer.
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampWriterKernelTest extends PatternkitKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('patternkit', ['patternkit_block_validity']);
  }

  /**
   * Writer service is registered and injectable.
   */
  public function testStampWriterServiceAvailable(): void {
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $this->assertInstanceOf(ValidityStampWriter::class, $writer);
  }

  /**
   * Merge upsert persists all stamp columns for read-back via getStamp().
   */
  public function testWriteStampUpsertRoundtrip(): void {
    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $expectedValidatedAt = (string) $this->container->get('datetime.time')->getRequestTime();

    $writer->writeStamp(42, 'en', 100, 200, TRUE, FALSE, ValidityStampWriterInterface::STAMP_SOURCE_CRON, 'uuid-val');
    $row = $writer->getStamp(42, 'en');
    $this->assertNotNull($row);
    $this->assertSame($expectedValidatedAt, $row['validated_at']);
    $this->assertSame('42', $row['block_entity_id']);
    $this->assertSame('en', $row['langcode']);
    $this->assertSame('100', $row['block_revision_id']);
    $this->assertSame('200', $row['pattern_revision_id']);
    $this->assertSame('1', $row['is_valid']);
    $this->assertSame('0', $row['has_tokens']);
    $this->assertSame('cron_scan', $row['stamp_source']);
    $this->assertSame('uuid-val', $row['component_uuid']);

    $writer->writeStamp(42, 'en', 101, 201, FALSE, TRUE);
    $updated = $writer->getStamp(42, 'en');
    $this->assertNotNull($updated);
    $this->assertSame('101', $updated['block_revision_id']);
    $this->assertSame('0', $updated['is_valid']);
    $this->assertSame('1', $updated['has_tokens']);
  }

}
