<?php

namespace Drupal\patternkit_usage_tracking;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\patternkit_usage_tracking\Entity\PatternUsage;

/**
 * A trait for recording pattern usages within a layout.
 */
trait PatternLayoutUsageInsertTrait {

  /**
   * The pattern usage tracking storage.
   *
   * @var \Drupal\patternkit_usage_tracking\PatternUsageManager
   */
  protected PatternUsageManager $trackingStorage;

  /**
   * Insert usage records for patterns used by a new layout component.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity containing the layout and component being added.
   * @param string $uuid
   *   The UUID for the layout component being added.
   * @param array{uuid: string, layout_path: string, pattern_id: string, nested: string[]} $record
   *   An associative array of usage data discovered for the new component. Data
   *   is organized in the array with the following keys:
   *   - uuid: The UUID of the containing component.
   *   - layout_path: The path to the component within the layout in the form
   *     `<section_delta>.<uuid>:<pattern_id>`.
   *   - pattern_id: The asset name of the pattern contained in the component.
   *   - nested: An array of the names of all pattern usages detected within the
   *     content.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function insertComponent(ContentEntityInterface $entity, string $uuid, array $record): void {
    // Track the top-level pattern.
    $this->trackingStorage->register($entity, $record['pattern_id'], $record['layout_path']);

    // Track any nested patterns.
    foreach ($record['nested'] as $nested_pattern_id) {
      $this->insertNestedPattern($entity, $record['layout_path'], $nested_pattern_id);
    }
  }

  /**
   * Insert a usage record for an individual usage of a nested pattern.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity containing the layout and component for the pattern usage.
   * @param string $layout_path
   *   The path to the containing top-level component within the layout.
   * @param string $nested_pattern_id
   *   The asset identifier for the nested pattern being recorded.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function insertNestedPattern(ContentEntityInterface $entity, string $layout_path, string $nested_pattern_id): void {
    $usage_path = $layout_path . PatternUsage::NESTING_SEPARATOR . $nested_pattern_id;
    $this->trackingStorage->register($entity, $nested_pattern_id, $usage_path);
  }

}
