<?php

declare(strict_types=1);

namespace Drupal\patternkit\Validation;

use Drupal\Core\Entity\EntityInterface;

/**
 * Request-scoped store for pending validity stamp writes.
 *
 * Carries presave validation decision metadata to the post-save hook boundary
 * so stamps are written only after parent entity persistence is confirmed.
 *
 * @internal
 */
final class ValidityStampPendingStore {

  /**
   * Pending stamp entries keyed by "{blockEntityId}:{langcode}".
   *
   * @var array<string, array<string, mixed>>
   */
  private array $pending = [];

  /**
   * Adds a pending stamp entry for a block component.
   *
   * @param int $blockEntityId
   *   The patternkit_block entity ID.
   * @param string $langcode
   *   The block language code.
   * @param int $blockRevisionId
   *   The patternkit_block revision ID.
   * @param int $patternRevisionId
   *   The pattern revision ID.
   * @param bool $isValid
   *   Whether the block content passed schema validation.
   * @param bool $hasTokens
   *   Whether the block data contains token patterns.
   * @param string $stampSource
   *   The stamp origin identifier.
   * @param string|null $componentUuid
   *   The Layout Builder SectionComponent UUID, or NULL for config blocks.
   */
  public function add(
    int $blockEntityId,
    string $langcode,
    int $blockRevisionId,
    int $patternRevisionId,
    bool $isValid,
    bool $hasTokens,
    string $stampSource,
    ?string $componentUuid,
  ): void {
    $key = $blockEntityId . ':' . $langcode;
    $this->pending[$key] = [
      'blockEntityId' => $blockEntityId,
      'langcode' => $langcode,
      'blockRevisionId' => $blockRevisionId,
      'patternRevisionId' => $patternRevisionId,
      'isValid' => $isValid,
      'hasTokens' => $hasTokens,
      'stampSource' => $stampSource,
      'componentUuid' => $componentUuid,
    ];
  }

  /**
   * Flushes and returns all pending stamp entries.
   *
   * The entity parameter is not used for scoping. All pending entries are
   * returned and cleared regardless of which entity triggered the flush. This
   * is intentional: entries are keyed by patternkit_block entity ID and
   * langcode, which are unique per block, so data correctness is preserved
   * even when multiple Layout Builder-compatible parent entities are saved in
   * the same request. The semantic guarantee (stamps written only after their
   * own parent entity persists) holds for typical single-entity saves; for
   * bulk operations it is accepted that entries may flush on a sibling
   * entity's post-save hook rather than their own.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity being saved. Reserved for future per-entity scoping.
   *
   * @return array<string, array<string, mixed>>
   *   All pending stamp entries, keyed by "{blockEntityId}:{langcode}".
   */
  public function flushForEntity(EntityInterface $entity): array {
    $entries = $this->pending;
    $this->pending = [];
    return $entries;
  }

  /**
   * Clears all pending entries without flushing.
   */
  public function clear(): void {
    $this->pending = [];
  }

}
