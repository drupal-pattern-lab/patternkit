<?php

namespace Drupal\Tests\patternkit_usage_tracking\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * A helper trait for testing to make assertions on pattern usage tracking.
 */
trait PatternUsageAssertionTrait {

  /**
   * Storage handler for pattern_usage content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternUsageStorage;

  /**
   * Assert a pattern usage was recorded with the provided data.
   *
   * @param string $source_entity_type
   *   The entity type ID for the tracked source entity.
   * @param int $entity_id
   *   The entity ID for the tracked source entity.
   * @param string $pattern_name
   *   The tracked pattern name to search for.
   * @param array{source_revision_id?: int, source_langcode?: string, path?: string, component_uuid?: string} $additional_properties
   *   (Optional) An associative array of additional properties to filter usage
   *   results by. Options for these filters include:
   *   - source_revision_id: The revision ID of the source entity.
   *   - source_langcode: A language code for the source entity.
   *   - path: A path within a layout to the recorded pattern.
   *   - component_uuid: The associated UUID of the layout component.
   * @param string $message
   *   (Optional) A message to display on failed assertions.
   */
  protected function assertTrackedPatternUsage(string $source_entity_type, int $entity_id, string $pattern_name, array $additional_properties = [], string $message = ''): void {
    $records = $this->queryUsageByProperties($source_entity_type, $entity_id, $pattern_name, $additional_properties);

    $this->assertNotEmpty($records, $message);
  }

  /**
   * Assert a pattern usage was recorded for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to find related usage records for.
   * @param string $pattern_name
   *   The tracked pattern name to search for.
   * @param array{source_revision_id?: int, source_langcode?: string, path?: string, component_uuid?: string} $additional_properties
   *   (Optional) An associative array of additional properties to filter usage
   *   results by. Options for these filters include:
   *   - source_revision_id: The revision ID of the source entity.
   *   - source_langcode: A language code for the source entity.
   *   - path: A path within a layout to the recorded pattern.
   *   - component_uuid: The associated UUID of the layout component.
   * @param string $message
   *   (Optional) A message to display on failed assertions.
   */
  protected function assertTrackedPatternUsageByEntity(EntityInterface $entity, string $pattern_name, array $additional_properties = [], string $message = ''): void {
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    $this->assertTrackedPatternUsage($entity_type, $entity_id, $pattern_name, $additional_properties, $message);
  }

  /**
   * Assert a pattern usage was not recorded with the provided data.
   *
   * @param string $source_entity_type
   *   The entity type ID for the tracked source entity.
   * @param int $entity_id
   *   The entity ID for the tracked source entity.
   * @param string $pattern_name
   *   The tracked pattern name to search for.
   * @param array{source_revision_id?: int, source_langcode?: string, path?: string, component_uuid?: string} $additional_properties
   *   (Optional) An associative array of additional properties to filter usage
   *   results by. Options for these filters include:
   *   - source_revision_id: The revision ID of the source entity.
   *   - source_langcode: A language code for the source entity.
   *   - path: A path within a layout to the recorded pattern.
   *   - component_uuid: The associated UUID of the layout component.
   * @param string $message
   *   (Optional) A message to display on failed assertions.
   */
  protected function assertNotTrackedPatternUsage(string $source_entity_type, int $entity_id, string $pattern_name, array $additional_properties = [], string $message = ''): void {
    $records = $this->queryUsageByProperties($source_entity_type, $entity_id, $pattern_name, $additional_properties);

    $this->assertEmpty($records, $message);
  }

  /**
   * Assert a pattern usage was not recorded for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to find related usage records for.
   * @param string $pattern_name
   *   The tracked pattern name to search for.
   * @param array{source_revision_id?: int, source_langcode?: string, path?: string, component_uuid?: string} $additional_properties
   *   (Optional) An associative array of additional properties to filter usage
   *   results by. Options for these filters include:
   *   - source_revision_id: The revision ID of the source entity.
   *   - source_langcode: A language code for the source entity.
   *   - path: A path within a layout to the recorded pattern.
   *   - component_uuid: The associated UUID of the layout component.
   * @param string $message
   *   (Optional) A message to display on failed assertions.
   */
  protected function assertNotTrackedPatternUsageByEntity(EntityInterface $entity, string $pattern_name, array $additional_properties = [], string $message = ''): void {
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    $this->assertNotTrackedPatternUsage($entity_type, $entity_id, $pattern_name, $additional_properties, $message);
  }

  /**
   * Load stored pattern usage records using provided filter values.
   *
   * @param string $source_entity_type
   *   The entity type ID for the tracked source entity.
   * @param int $entity_id
   *   The entity ID for the tracked source entity.
   * @param string $pattern_name
   *   The tracked pattern name to search for.
   * @param array{source_revision_id?: int, source_langcode?: string, path?: string, component_uuid?: string} $additional_properties
   *   (Optional) An associative array of additional properties to filter usage
   *   results by. Options for these filters include:
   *   - source_revision_id: The revision ID of the source entity.
   *   - source_langcode: A language code for the source entity.
   *   - path: A path within a layout to the recorded pattern.
   *   - component_uuid: The associated UUID of the layout component.
   *
   * @return \Drupal\patternkit_usage_tracking\Entity\PatternUsage[]
   *   An array of loaded pattern usage records matching provided filter values.
   */
  private function queryUsageByProperties(string $source_entity_type, int $entity_id, string $pattern_name, array $additional_properties = []): array {
    $properties = [
      'source_entity_type' => $source_entity_type,
      'source_id' => $entity_id,
      'pattern_name' => $pattern_name,
    ] + $additional_properties;

    /** @var \Drupal\patternkit_usage_tracking\Entity\PatternUsage[] $results */
    $results = $this->patternUsageStorage->loadByProperties($properties);
    return $results;
  }

}
