<?php

declare(strict_types=1);

namespace Drupal\patternkit\Validation;

/**
 * Defines the contract for writing and deleting block validity stamps.
 */
interface ValidityStampWriterInterface {

  /**
   * Stamp source identifier for block-save–triggered writes.
   */
  const STAMP_SOURCE_SAVE = 'save';

  /**
   * Stamp source identifier for cron-scan–triggered writes.
   */
  const STAMP_SOURCE_CRON = 'cron_scan';

  /**
   * Writes or overwrites a validity stamp for one block translation.
   *
   * @param int $blockEntityId
   *   The patternkit_block entity ID.
   * @param string $langcode
   *   The language code.
   * @param int $blockRevisionId
   *   The block revision ID.
   * @param int $patternRevisionId
   *   The pattern revision ID.
   * @param bool $isValid
   *   Whether the block is valid.
   * @param bool $hasTokens
   *   Whether the block has tokens.
   * @param string $source
   *   The stamp origin; use STAMP_SOURCE_SAVE or STAMP_SOURCE_CRON.
   * @param string|null $componentUuid
   *   The Layout Builder SectionComponent UUID when available; NULL otherwise.
   */
  public function writeStamp(
    int $blockEntityId,
    string $langcode,
    int $blockRevisionId,
    int $patternRevisionId,
    bool $isValid,
    bool $hasTokens,
    string $source = self::STAMP_SOURCE_SAVE,
    ?string $componentUuid = NULL,
  ): void;

  /**
   * Deletes stamp row(s) for a block entity.
   *
   * @param int $blockEntityId
   *   The patternkit_block entity ID.
   * @param string|null $langcode
   *   If set, removes only that translation; if NULL, removes all languages.
   */
  public function deleteStamp(int $blockEntityId, ?string $langcode = NULL): void;

  /**
   * Loads one validity stamp row for a block translation, or NULL.
   *
   * @param int $blockEntityId
   *   The patternkit_block entity ID.
   * @param string $langcode
   *   The language code.
   *
   * @return array<string, mixed>|null
   *   Associative row from patternkit_block_validity, or NULL if absent.
   */
  public function getStamp(int $blockEntityId, string $langcode): ?array;

  /**
   * Returns unstamped published block translations for cron enqueue.
   *
   * @param int $limit
   *   Maximum number of rows to return.
   *
   * @return list<array{block_entity_id: int, langcode: string}>
   *   Up to $limit rows from patternkit_field_data lacking a validity stamp.
   */
  public function getUnstampedBlockTranslations(int $limit = 50): array;

  /**
   * Returns stamped block translations whose stamp is stale.
   *
   * A stamp is stale when its pattern_revision_id no longer matches the
   * current default revision of the pattern the block references.
   *
   * Blocks that reference a pattern_id with no corresponding pattern entity
   * are not enqueued, but are returned separately so callers can surface the
   * data integrity issue.
   *
   * The $offset parameter supports cursor-based pagination across cron runs.
   * Callers should advance the offset by the number of candidates examined
   * each run and reset it to 0 when the result is empty (indicating the full
   * table has been scanned) or when Phase 1 becomes active again.
   *
   * @param int $limit
   *   Maximum number of stale rows to return.
   * @param int $offset
   *   Row offset into the ordered candidate set; used to page through the
   *   full table across multiple cron runs.
   *
   * @return array{
   *   stale: list<array{block_entity_id: int, langcode: string}>,
   *   unresolvable: list<array{
   *     block_entity_id: int, langcode: string, pattern_id: string
   *   }>,
   *   candidates_examined: int
   *   }
   *   'stale' contains up to $limit rows ready to enqueue. 'unresolvable'
   *   contains up to $limit rows whose pattern_id could not be matched to any
   *   pattern entity. 'candidates_examined' is the number of rows fetched from
   *   the DB in this call, for use in advancing the caller's cursor.
   */
  public function getStaleStampBlockTranslations(int $limit = 50, int $offset = 0): array;

}
