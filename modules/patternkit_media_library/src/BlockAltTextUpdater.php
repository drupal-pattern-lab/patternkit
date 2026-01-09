<?php

namespace Drupal\patternkit_media_library;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Service for updating missing alt text in pattern blocks.
 */
class BlockAltTextUpdater implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Constructs a BlockAltTextUpdater instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\patternkit_media_library\ContentImageFieldLocator $fieldLocator
   *   The content image field locator service.
   * @param \Drupal\patternkit_media_library\MediaAttributeService $mediaAttributes
   *   The media attribute service.
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ContentImageFieldLocator $fieldLocator,
    protected MediaAttributeService $mediaAttributes,
    protected PatternRepository $patternRepository,
  ) {
  }

  /**
   * Process a single block to update missing alt text.
   *
   * @param \Drupal\patternkit\Entity\PatternkitBlock $block
   *   The block to process.
   * @param bool $dry_run
   *   Whether to perform a dry run (don't save changes).
   *
   * @return array
   *   An array with keys:
   *   - 'status': One of 'updated', 'skipped', or 'failed'.
   *   - 'fields_updated': Number of fields updated (0 if skipped/failed).
   *   - 'message': Optional message for dry run output.
   *   - 'error': Optional error message if failed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function processBlock(PatternkitBlock $block, bool $dry_run = FALSE): array {
    try {
      // Get the block data (content).
      $content = $block->getData();

      // Skip if no content.
      if (empty($content)) {
        return ['status' => 'skipped', 'fields_updated' => 0];
      }

      // Load the pattern entity.
      $pattern = $this->loadPatternForBlock($block);
      if (!$pattern) {
        return ['status' => 'skipped', 'fields_updated' => 0];
      }

      // Find image fields with missing alt text.
      $fields_to_update = $this->fieldLocator->findImageFieldsWithMissingAlt($pattern, $content);

      // Skip if no fields need updating.
      if (empty($fields_to_update)) {
        return ['status' => 'skipped', 'fields_updated' => 0];
      }

      // Update the content with alt text.
      $result = $this->updateContentAltText($content, $fields_to_update, $block->id(), $dry_run);

      // Save the block if changes were made (and not in dry-run mode).
      if ($result['changes_made'] && !$dry_run) {
        $this->saveBlockContent($block, $result['updated_content']);
      }

      return [
        'status' => $result['changes_made'] ? 'updated' : 'skipped',
        'fields_updated' => $result['field_count'],
        'messages' => $result['messages'] ?? [],
      ];
    }
    catch (\Exception $exception) {
      $this->logger->error(
        'Failed to update alt text for block @block_id: @message',
        [
          '@block_id' => $block->id(),
          '@message' => $exception->getMessage(),
        ]
      );

      return [
        'status' => 'failed',
        'fields_updated' => 0,
        'error' => $exception->getMessage(),
      ];
    }
  }

  /**
   * Load the pattern entity for a block.
   *
   * @param \Drupal\patternkit\Entity\PatternkitBlock $block
   *   The block entity.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface|null
   *   The pattern entity, or NULL if not found.
   */
  protected function loadPatternForBlock(PatternkitBlock $block): ?PatternInterface {
    $pattern_id = $block->getPattern();
    if (!$pattern_id) {
      return NULL;
    }

    // Check if pattern_id is a numeric entity ID or a derivative ID.
    // Derivative IDs start with '@', numeric IDs are just numbers.
    if (is_numeric($pattern_id)) {
      // Try loading directly from entity storage for numeric IDs.
      /** @var \Drupal\Core\Entity\EntityStorageInterface $pattern_storage */
      $pattern_storage = $this->entityTypeManager->getStorage('patternkit_pattern');
      /** @var \Drupal\patternkit\Entity\PatternInterface|null $pattern */
      $pattern = $pattern_storage->load($pattern_id);
      if ($pattern) {
        return $pattern;
      }
      // Pattern entity not found for numeric ID - return NULL without logging
      // as "unrecognized format" since the format is valid, just the entity
      // doesn't exist.
      return NULL;
    }
    elseif (str_starts_with($pattern_id, '@')) {
      // Use PatternRepository to load by pattern derivative ID.
      // The pattern_id stored in blocks can be a derivative ID (e.g.,
      // '@patternkit_test/image_attributes_test/image_attributes_test').
      try {
        return $this->patternRepository->getPattern($pattern_id, TRUE);
      }
      catch (UnknownPatternException $e) {
        $this->logger->warning(
          'Pattern not found for block @block_id: @pattern_id - @message',
          [
            '@block_id' => $block->id(),
            '@pattern_id' => $pattern_id,
            '@message' => $e->getMessage(),
          ]
        );
        return NULL;
      }
    }

    // If we get here, the pattern_id format is unrecognized.
    $this->logger->warning(
      'Unrecognized pattern_id format for block @block_id: @pattern_id',
      [
        '@block_id' => $block->id(),
        '@pattern_id' => $pattern_id,
      ]
    );
    return NULL;
  }

  /**
   * Update content array with alt text from media attributes.
   *
   * @param array $content
   *   The original content array.
   * @param array $fields_to_update
   *   Array of field information from ContentImageFieldLocator.
   * @param int|string $block_id
   *   The block ID (for dry run messages).
   * @param bool $dry_run
   *   Whether this is a dry run.
   *
   * @return array
   *   Array with keys:
   *   - 'updated_content': The updated content array.
   *   - 'changes_made': Whether any changes were made.
   *   - 'field_count': Number of fields updated.
   *   - 'messages': Array of dry run messages (if applicable).
   */
  protected function updateContentAltText(array $content, array $fields_to_update, int|string $block_id, bool $dry_run): array {
    $updated_content = $content;
    $changes_made = FALSE;
    $field_count = 0;
    $messages = [];

    foreach ($fields_to_update as $field_info) {
      $url = $field_info['url'];
      $alt_path = $field_info['alt_path'];

      // Get media attributes for this URL.
      $attributes = $this->mediaAttributes->getAttributesByUrl($url);

      // Update alt text if we got a value.
      if (isset($attributes['alt']) && !empty($attributes['alt'])) {

        if ($dry_run) {
          $messages[] = sprintf(
            '[Block %s] Would update %s with: %s',
            $block_id,
            $alt_path,
            $attributes['alt']
          );
        }
        else {
          $this->fieldLocator->setValueByPath($updated_content, $alt_path, $attributes['alt']);
        }

        $changes_made = TRUE;
        $field_count++;
      }
    }

    return [
      'updated_content' => $updated_content,
      'changes_made' => $changes_made,
      'field_count' => $field_count,
      'messages' => $messages,
    ];
  }

  /**
   * Save updated content to a block entity.
   *
   * @param \Drupal\patternkit\Entity\PatternkitBlock $block
   *   The block entity.
   * @param array $content
   *   The updated content array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  protected function saveBlockContent(PatternkitBlock $block, array $content): void {
    $serialized = json_encode($content, JSON_THROW_ON_ERROR);
    $block->set('data', $serialized);
    $block->setNewRevision(FALSE);
    $block->save();
  }

}
