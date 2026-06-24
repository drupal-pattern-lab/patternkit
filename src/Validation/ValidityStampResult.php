<?php

declare(strict_types=1);

namespace Drupal\patternkit\Validation;

/**
 * Validation decision metadata produced during presave for a patternkit_block.
 *
 * Carries the validation result and block identity from presave evaluation
 * to the post-save hook boundary where stamps are written.
 *
 * @internal
 */
final class ValidityStampResult {

  /**
   * Constructs a ValidityStampResult object.
   *
   * @param bool $isValid
   *   Whether the block content passed schema validation.
   * @param int $blockEntityId
   *   The patternkit_block entity ID.
   * @param string $langcode
   *   The block language code.
   * @param int $blockRevisionId
   *   The patternkit_block revision ID.
   * @param int $patternRevisionId
   *   The pattern revision ID.
   */
  public function __construct(
    public readonly bool $isValid,
    public readonly int $blockEntityId,
    public readonly string $langcode,
    public readonly int $blockRevisionId,
    public readonly int $patternRevisionId,
  ) {}

}
