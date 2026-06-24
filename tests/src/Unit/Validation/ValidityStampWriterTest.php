<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Unit\Validation;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\patternkit\Validation\ValidityStampWriter;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for ValidityStampWriter.
 *
 * @coversDefaultClass \Drupal\patternkit\Validation\ValidityStampWriter
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampWriterTest extends UnitTestCase {

  /**
   * Fixed request time returned by mocked TimeInterface.
   */
  private const MOCK_REQUEST_TIME = 1700000100;

  /**
   * First merge for a (block id, langcode) inserts via merge fields + execute.
   */
  public function testWriteStampInsertsNewRow(): void {
    $merge = $this->createMock(Merge::class);
    $merge->method('key')->willReturnSelf();
    $merge->expects($this->once())->method('fields')->with($this->callback(function (array $fields): bool {
      return ($fields['validated_at'] ?? NULL) === self::MOCK_REQUEST_TIME;
    }))->willReturnSelf();
    $merge->expects($this->once())->method('execute');

    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())
      ->method('merge')
      ->with('patternkit_block_validity')
      ->willReturn($merge);

    $writer = $this->createValidityStampWriter($connection);
    $writer->writeStamp(7, 'en', 11, 22, TRUE, FALSE);
  }

  /**
   * Second write uses merge upsert (same mock expectations as first insert).
   */
  public function testWriteStampUpdatesExistingRow(): void {
    $merge = $this->createMock(Merge::class);
    $merge->method('key')->willReturnSelf();
    $merge->expects($this->exactly(2))->method('fields')->willReturnSelf();
    $merge->expects($this->exactly(2))->method('execute');

    $connection = $this->createMock(Connection::class);
    $connection->method('merge')->willReturn($merge);

    $writer = $this->createValidityStampWriter($connection);
    $writer->writeStamp(7, 'en', 11, 22, TRUE, FALSE);
    $writer->writeStamp(7, 'en', 12, 23, FALSE, TRUE);
  }

  /**
   * Different langcodes imply distinct merge keys.
   */
  public function testWriteStampSeparatePerLanguage(): void {
    $merge = $this->createMock(Merge::class);
    $merge->method('key')->willReturnSelf();
    $merge->expects($this->exactly(2))->method('fields')->willReturnSelf();
    $merge->expects($this->exactly(2))->method('execute');

    $connection = $this->createMock(Connection::class);
    $connection->method('merge')->willReturn($merge);

    $writer = $this->createValidityStampWriter($connection);
    $writer->writeStamp(7, 'en', 1, 2, TRUE, FALSE);
    $writer->writeStamp(7, 'fr', 1, 2, TRUE, FALSE);
  }

  /**
   * Delete with langcode adds one extra condition.
   */
  public function testDeleteStampRemovesRow(): void {
    $delete = $this->createMock(Delete::class);
    $conditions = [];
    $delete->expects($this->exactly(2))->method('condition')->willReturnCallback(function ($field, $value) use (&$conditions, $delete) {
      $conditions[] = [$field, $value];
      return $delete;
    });
    $delete->expects($this->once())->method('execute');

    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())
      ->method('delete')
      ->with('patternkit_block_validity')
      ->willReturn($delete);

    $writer = $this->createValidityStampWriter($connection);
    $writer->deleteStamp(9, 'de');
    $this->assertSame([
      ['block_entity_id', 9],
      ['langcode', 'de'],
    ], $conditions);
  }

  /**
   * Delete without langcode only filters block_entity_id.
   */
  public function testDeleteStampAllTranslations(): void {
    $delete = $this->createMock(Delete::class);
    $delete->expects($this->once())
      ->method('condition')
      ->with('block_entity_id', 9)
      ->willReturnSelf();
    $delete->expects($this->once())->method('execute');

    $connection = $this->createMock(Connection::class);
    $connection->method('delete')->willReturn($delete);

    $writer = $this->createValidityStampWriter($connection);
    $writer->deleteStamp(9);
  }

  /**
   * Delete on missing row should not throw (execute still runs).
   */
  public function testDeleteStampNoErrorWhenAbsent(): void {
    $delete = $this->createMock(Delete::class);
    $delete->method('condition')->willReturnSelf();
    $delete->expects($this->once())->method('execute');

    $connection = $this->createMock(Connection::class);
    $connection->method('delete')->willReturn($delete);

    $writer = $this->createValidityStampWriter($connection);
    $writer->deleteStamp(99999, 'zz');
  }

  /**
   * GetStamp returns associative row when fetchAssoc succeeds.
   */
  public function testGetStampReturnsRecord(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->expects($this->once())->method('fetchAssoc')->willReturn([
      'block_entity_id' => '3',
      'langcode' => 'en',
      'is_valid' => '1',
    ]);

    $select = $this->createMock(SelectInterface::class);
    $select->expects($this->once())->method('fields')->with('v')->willReturnSelf();
    $select->expects($this->exactly(2))->method('condition')->willReturnSelf();
    $select->expects($this->once())->method('range')->with(0, 1)->willReturnSelf();
    $select->expects($this->once())->method('execute')->willReturn($statement);

    $connection = $this->createMock(Connection::class);
    $connection->expects($this->once())
      ->method('select')
      ->with('patternkit_block_validity', 'v')
      ->willReturn($select);

    $writer = $this->createValidityStampWriter($connection);
    $row = $writer->getStamp(3, 'en');
    $this->assertIsArray($row);
    $this->assertSame('1', $row['is_valid']);
  }

  /**
   * GetStamp returns NULL when no row.
   */
  public function testGetStampReturnsNullWhenAbsent(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->expects($this->once())->method('fetchAssoc')->willReturn(FALSE);

    $select = $this->createMock(SelectInterface::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('range')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $connection = $this->createMock(Connection::class);
    $connection->method('select')->willReturn($select);

    $writer = $this->createValidityStampWriter($connection);
    $this->assertNull($writer->getStamp(3, 'en'));
  }

  /**
   * Unstamped query returns rows from fetchAll mapping.
   */
  public function testGetUnstampedBlockTranslationsReturnsUnstamped(): void {
    // @todo Restore StatementInterface mock with FetchAs::Associative when
    //   Drupal 10.x support is dropped. On Drupal 11.x, fetchAll() is typed
    //   as ?FetchAs so a PHPUnit mock rejects the \PDO::FETCH_ASSOC int used
    //   in production. A plain object avoids the type check on both versions.
    //   https://www.drupal.org/project/patternkit/issues/3597150
    $statement = new class {

      public function fetchAll(mixed $mode = NULL): array {
        return [['block_entity_id' => '5', 'langcode' => 'en']];
      }

    };

    $select = $this->createMock(SelectInterface::class);
    $select->method('addField')->willReturnSelf();
    $select->method('leftJoin')->willReturnSelf();
    $select->method('isNull')->willReturnSelf();
    $select->method('orderBy')->willReturnSelf();
    $select->method('range')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $connection = $this->createMock(Connection::class);
    $connection->method('select')->with('patternkit_field_data', 'fd')->willReturn($select);

    $writer = $this->createValidityStampWriter($connection);
    $rows = $writer->getUnstampedBlockTranslations(50);
    $this->assertCount(1, $rows);
    $this->assertSame(5, $rows[0]['block_entity_id']);
    $this->assertSame('en', $rows[0]['langcode']);
  }

  /**
   * Limit is passed to range(0, limit).
   */
  public function testGetUnstampedBlockTranslationsRespectsLimit(): void {
    $statement = new class {

      public function fetchAll(mixed $mode = NULL): array {
        return [];
      }

    };

    $select = $this->createMock(SelectInterface::class);
    $select->method('addField')->willReturnSelf();
    $select->method('leftJoin')->willReturnSelf();
    $select->method('isNull')->willReturnSelf();
    $select->method('orderBy')->willReturnSelf();
    $select->expects($this->once())->method('range')->with(0, 3)->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $connection = $this->createMock(Connection::class);
    $connection->method('select')->willReturn($select);

    $writer = $this->createValidityStampWriter($connection);
    $this->assertSame([], $writer->getUnstampedBlockTranslations(3));
  }

  /**
   * Empty fetchAll becomes empty return list.
   */
  public function testGetUnstampedBlockTranslationsReturnsEmptyWhenAllStamped(): void {
    $statement = new class {

      public function fetchAll(mixed $mode = NULL): array {
        return [];
      }

    };

    $select = $this->createMock(SelectInterface::class);
    $select->method('addField')->willReturnSelf();
    $select->method('leftJoin')->willReturnSelf();
    $select->method('isNull')->willReturnSelf();
    $select->method('orderBy')->willReturnSelf();
    $select->method('range')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $connection = $this->createMock(Connection::class);
    $connection->method('select')->willReturn($select);

    $writer = $this->createValidityStampWriter($connection);
    $this->assertSame([], $writer->getUnstampedBlockTranslations(50));
  }

  /**
   * Excluding stamped rows is expressed via left join + isNull in SQL builder.
   */
  public function testGetUnstampedBlockTranslationsExcludesStamped(): void {
    $statement = new class {

      public function fetchAll(mixed $mode = NULL): array {
        return [];
      }

    };

    $select = $this->createMock(SelectInterface::class);
    $select->method('addField')->willReturnSelf();
    $select->expects($this->once())->method('leftJoin')->willReturnSelf();
    $select->expects($this->once())->method('isNull')->with('v.block_entity_id')->willReturnSelf();
    $select->expects($this->once())
      ->method('condition')
      ->with('fd.status', 1)
      ->willReturnSelf();
    $select->method('orderBy')->willReturnSelf();
    $select->method('range')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $connection = $this->createMock(Connection::class);
    $connection->method('select')->willReturn($select);

    $writer = $this->createValidityStampWriter($connection);
    $writer->getUnstampedBlockTranslations(50);
  }

  /**
   * Builds the writer with a mocked clock frozen to MOCK_REQUEST_TIME.
   */
  protected function createValidityStampWriter(Connection $connection): ValidityStampWriter {
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(self::MOCK_REQUEST_TIME);
    return new ValidityStampWriter($connection, $time);
  }

}
