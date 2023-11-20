<?php

namespace Drupal\patternkit_usage_tracking;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper service to manage the storage for pattern_usage entities.
 *
 * @see \Drupal\patternkit_usage_tracking\Entity\PatternUsage
 */
class PatternUsageManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity storage handler for pattern_usage entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $storage;

  /**
   * Constructs a new PatternUsageManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * Register a pattern usage in an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The tracked entity.
   * @param string $pattern_name
   *   The name of the tracked pattern.
   * @param string $path
   *   (optional) The path to the component within a layout and/or nested
   *   containing pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function register(ContentEntityInterface $entity, string $pattern_name, string $path = ''): void {
    $storage = $this->storage();
    $usage = $storage->create([
      'source_entity_type' => $entity->getEntityTypeId(),
      'source_id' => $entity->id(),
      'source_revision_id' => $entity->getRevisionId(),
      'source_langcode' => $entity->language()->getId(),
      'pattern_name' => $pattern_name,
      'path' => $path,
    ]);
    $usage->save();
  }

  /**
   * Delete all records for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The deleted entity.
   */
  public function deleteByEntity(EntityInterface $entity): void {
    $storage = $this->storage();
    $records = $storage->loadByProperties([
      'source_entity_type' => $entity->getEntityTypeId(),
      'source_id' => $entity->id(),
    ]);
    $storage->delete($records);
  }

  /**
   * Delete all records for a given entity translation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The deleted entity translation.
   */
  public function deleteByTranslation(ContentEntityInterface $entity): void {
    $storage = $this->storage();
    $records = $storage->loadByProperties([
      'source_entity_type' => $entity->getEntityTypeId(),
      'source_id' => $entity->id(),
      'source_langcode' => $entity->language()->getId(),
    ]);
    $storage->delete($records);
  }

  /**
   * Delete all records for a given entity and component UUID.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The tracked entity.
   * @param string $uuid
   *   The UUID of the component being deleted.
   */
  public function deleteByEntityAndUuid(ContentEntityInterface $entity, string $uuid): void {
    $storage = $this->storage();

    $records = $storage->loadByProperties([
      'source_entity_type' => $entity->getEntityTypeId(),
      'source_id' => $entity->id(),
      'source_revision_id' => $entity->getRevisionId(),
      'source_langcode' => $entity->language()->getId(),
      'component_uuid' => $uuid,
    ]);

    $storage->delete($records);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTrackingInformation(): void {
    $storage = $this->storage();
    $records = $storage->loadMultiple();
    $storage->delete($records);
  }

  /**
   * Load all records for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The tracked entity.
   *
   * @return \Drupal\patternkit_usage_tracking\Entity\PatternUsage[]
   *   The tracking records.
   */
  public function loadByEntity(ContentEntityInterface $entity): array {
    $storage = $this->storage();

    // Get the revision ID for the version that was initially loaded instead of
    // the current revision ID since there will be no matches for a new revision
    // if an update just occurred.
    $revision_id = $entity->getLoadedRevisionId();

    return $storage->loadByProperties([
      'source_entity_type' => $entity->getEntityTypeId(),
      'source_id' => $entity->id(),
      'source_revision_id' => $revision_id,
    ]);
  }

  /**
   * Load all records for a given pattern.
   *
   * @param string $pattern_name
   *   The tracked pattern name.
   *
   * @return \Drupal\patternkit_usage_tracking\Entity\PatternUsage[]
   *   The tracking records.
   */
  public function loadByPattern(string $pattern_name): array {
    $storage = $this->storage();
    return $storage->loadByProperties([
      'pattern_name' => $pattern_name,
    ]);
  }

  /**
   * Load all records for a given entity and pattern name.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The tracked entity.
   * @param string $pattern_name
   *   The tracked pattern name.
   *
   * @return \Drupal\patternkit_usage_tracking\Entity\PatternUsage[]
   *   The tracking records.
   */
  public function loadByEntityAndPattern(ContentEntityInterface $entity, string $pattern_name): array {
    $storage = $this->storage();
    return $storage->loadByProperties([
      'source_entity_type' => $entity->getEntityTypeId(),
      'source_id' => $entity->id(),
      'pattern_name' => $pattern_name,
    ]);
  }

  /**
   * Lazy-load the pattern usage storage handler.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The pattern usage storage handler.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function storage(): EntityStorageInterface {
    if (!isset($this->storage)) {
      $this->storage = $this->entityTypeManager->getStorage('pattern_usage');
    }

    return $this->storage;
  }

}
