<?php

declare(strict_types=1);

namespace Drupal\patternkit\Validation;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Persists and queries patternkit block validity stamps.
 */
final class ValidityStampWriter implements ValidityStampWriterInterface {

  /**
   * Constructs a new ValidityStampWriter.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected Connection $connection,
    protected TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
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
  ): void {
    $validatedAt = $this->time->getRequestTime();
    $merge = $this->connection->merge('patternkit_block_validity')
      ->key('block_entity_id', $blockEntityId)
      ->key('langcode', $langcode)
      ->fields([
        'block_revision_id' => $blockRevisionId,
        'pattern_revision_id' => $patternRevisionId,
        'is_valid' => $isValid ? 1 : 0,
        'has_tokens' => $hasTokens ? 1 : 0,
        'validated_at' => $validatedAt,
        'stamp_source' => $source,
        'component_uuid' => $componentUuid,
      ]);
    $merge->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteStamp(int $blockEntityId, ?string $langcode = NULL): void {
    $query = $this->connection->delete('patternkit_block_validity')
      ->condition('block_entity_id', $blockEntityId);
    if ($langcode !== NULL) {
      $query->condition('langcode', $langcode);
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getStamp(int $blockEntityId, string $langcode): ?array {
    $select = $this->connection->select('patternkit_block_validity', 'v')
      ->fields('v')
      ->condition('block_entity_id', $blockEntityId)
      ->condition('langcode', $langcode)
      ->range(0, 1);
    $row = $select->execute()->fetchAssoc();
    return $row !== FALSE ? $row : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnstampedBlockTranslations(int $limit = 50): array {
    $query = $this->connection->select('patternkit_field_data', 'fd');
    $query->addField('fd', 'id', 'block_entity_id');
    $query->addField('fd', 'langcode', 'langcode');
    $query->leftJoin(
      'patternkit_block_validity',
      'v',
      'fd.id = v.block_entity_id AND fd.langcode = v.langcode'
    );
    $query->isNull('v.block_entity_id');
    // Limit to published rows (patternkit_field_data.status).
    $query->condition('fd.status', 1);
    $query->orderBy('fd.id');
    $query->orderBy('fd.langcode');
    $query->range(0, $limit);

    // @todo Replace \PDO::FETCH_ASSOC with FetchAs::Associative when Drupal
    //   10.x support is dropped. FetchAs was introduced in Drupal 11.2.
    //   https://www.drupal.org/project/patternkit/issues/3597150
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $out = [];
    foreach ($results as $row) {
      $out[] = [
        'block_entity_id' => (int) $row['block_entity_id'],
        'langcode' => (string) $row['langcode'],
      ];
    }
    return $out;
  }

  /**
   * {@inheritdoc}
   */
  public function getStaleStampBlockTranslations(int $limit = 50, int $offset = 0): array {
    // Fetch stamped candidates joined with their recorded stamp revision and
    // pattern_id. Pattern ID is stored as '@<library>/<path>' on the block.
    //
    // Current default revisions are resolved in PHP rather than via a
    // CONCAT() expression join: CONCAT() is MySQL-only (SQLite uses ||) and
    // prevents index use on the pattern table.
    //
    // The $offset parameter enables cursor-based pagination across cron runs
    // so the full stamped population is cycled through even when most
    // candidates in any given window are unresolvable or fresh.
    $query = $this->connection->select('patternkit_field_data', 'fd');
    $query->addField('fd', 'id', 'block_entity_id');
    $query->addField('fd', 'langcode', 'langcode');
    $query->addField('fd', 'pattern_id', 'pattern_id');
    $query->join(
      'patternkit_block_validity',
      'v',
      'fd.id = v.block_entity_id AND fd.langcode = v.langcode'
    );
    $query->addField('v', 'pattern_revision_id', 'stamp_revision_id');
    $query->condition('fd.status', 1);
    $query->orderBy('fd.id');
    $query->orderBy('fd.langcode');
    $query->range($offset, $limit);

    // @todo Replace \PDO::FETCH_ASSOC with FetchAs::Associative when Drupal
    //   10.x support is dropped. FetchAs was introduced in Drupal 11.2.
    //   https://www.drupal.org/project/patternkit/issues/3597150
    $candidates = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    if (empty($candidates)) {
      return ['stale' => [], 'unresolvable' => [], 'candidates_examined' => 0];
    }

    // Decompose pattern_id values from the candidate set into (library, path)
    // pairs so the pattern revision query can be scoped to only the patterns
    // actually referenced by this batch. Loading the full pattern table on
    // every cron run scales with the total pattern count rather than the
    // candidate window size; filtering here keeps the query proportional to
    // the batch.
    //
    // A CONCAT() join is avoided because CONCAT() is MySQL-only (SQLite uses
    // ||) and prevents index use. The decomposition is done in PHP and the
    // results filtered via OR condition groups instead.
    // Decompose pattern_ids into unique (library, path) pairs. Deduplication
    // is done here so the OR condition group contains one branch per distinct
    // pattern, not one per candidate block (many blocks may share a pattern).
    $libraryPathPairs = [];
    foreach ($candidates as $row) {
      $patternId = (string) $row['pattern_id'];
      if (!str_starts_with($patternId, '@')) {
        continue;
      }
      $withoutAt = substr($patternId, 1);
      $slashPos = strpos($withoutAt, '/');
      if ($slashPos === FALSE) {
        continue;
      }
      $library = substr($withoutAt, 0, $slashPos);
      $path = substr($withoutAt, $slashPos + 1);
      $libraryPathPairs[$library . '/' . $path] = [
        'library' => $library,
        'path' => $path,
      ];
    }
    $libraryPathPairs = array_values($libraryPathPairs);

    // Build a map of pattern_id → current default revision ID from the DB.
    // Querying the pattern table directly bypasses entity cache layers, which
    // may hold stale revision pointers between cron runs.
    $patternRevisions = [];
    if (!empty($libraryPathPairs)) {
      $patternQuery = $this->connection->select('pattern', 'p');
      $patternQuery->addField('p', 'library', 'library');
      $patternQuery->addField('p', 'path', 'path');
      $patternQuery->addField('p', 'revision', 'current_revision');
      $orGroup = $patternQuery->orConditionGroup();
      foreach ($libraryPathPairs as $pair) {
        $andGroup = $patternQuery->andConditionGroup();
        $andGroup->condition('p.library', $pair['library']);
        $andGroup->condition('p.path', $pair['path']);
        $orGroup->condition($andGroup);
      }
      $patternQuery->condition($orGroup);
      // @todo Replace \PDO::FETCH_ASSOC with FetchAs::Associative when Drupal
      //   10.x support is dropped. FetchAs was introduced in Drupal 11.2.
      //   https://www.drupal.org/project/patternkit/issues/3597150
      $patternRows = $patternQuery->execute()->fetchAll(\PDO::FETCH_ASSOC);
      foreach ($patternRows as $pRow) {
        $key = '@' . $pRow['library'] . '/' . $pRow['path'];
        $patternRevisions[$key] = (int) $pRow['current_revision'];
      }
    }

    $stale = [];
    $unresolvable = [];
    foreach ($candidates as $row) {
      $patternId = (string) $row['pattern_id'];
      if (!isset($patternRevisions[$patternId])) {
        if (count($unresolvable) < $limit) {
          $unresolvable[] = [
            'block_entity_id' => (int) $row['block_entity_id'],
            'langcode' => (string) $row['langcode'],
            'pattern_id' => $patternId,
          ];
        }
        continue;
      }
      if ((int) $row['stamp_revision_id'] !== $patternRevisions[$patternId]) {
        $stale[] = [
          'block_entity_id' => (int) $row['block_entity_id'],
          'langcode' => (string) $row['langcode'],
        ];
        if (count($stale) >= $limit) {
          break;
        }
      }
    }
    return [
      'stale' => $stale,
      'unresolvable' => $unresolvable,
      'candidates_examined' => count($candidates),
    ];
  }

}
