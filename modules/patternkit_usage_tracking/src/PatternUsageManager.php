<?php

namespace Drupal\patternkit_usage_tracking;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper service to manage the storage for pattern_usage entities.
 *
 * @see \Drupal\patternkit_usage_tracking\Entity\PatternUsage
 */
class PatternUsageManager {

  /**
   * The entity storage handler for pattern_usage entities.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected ContentEntityStorageInterface $storage;

  /**
   * Constructs a new PatternUsageManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected readonly EntityTypeManagerInterface $entityTypeManager) {}

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

    // Identify the most recent source revision ID for usage records on this
    // entity to ensure updates are captured even if the most recent entity
    // revision was not the default published revision the stored records may
    // be referencing.
    $revision_id = $this->getLastRecordedRevisionIdByEntity($entity);

    if ($revision_id !== NULL) {
      $filters = [
        'source_entity_type' => $entity->getEntityTypeId(),
        'source_id' => $entity->id(),
        'source_revision_id' => $revision_id,
      ];

      if ($entity->isTranslatable()) {
        $language = $entity->language();
        $filters['source_langcode'] = $language->getId();
      }

      return $storage->loadByProperties($filters);
    }
    else {
      return [];
    }
  }

  /**
   * Identify the most recent revision ID for a source entity in usage records.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The source entity to find usage records for. The loaded translation will
   *   be identified to determine the specific language to load as well.
   *
   * @return int|null
   *   The most recent revision ID for the source entity found in usage records,
   *   or NULL if no usage records for the source entity and translation
   *   language are found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLastRecordedRevisionIdByEntity(ContentEntityInterface $entity): ?int {
    $storage = $this->storage();

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('source_entity_type', $entity->getEntityTypeId())
      ->condition('source_langcode', $entity->language()->getId())
      ->sort('source_revision_id', 'DESC')
      ->range(0, 1);

    $results = $query->execute();

    if (!empty($results)) {
      /** @var \Drupal\patternkit_usage_tracking\Entity\PatternUsage|null $record */
      $record = $storage->load(reset($results));
      $revision_id = ($record !== NULL) ? $record->get('source_revision_id')->value : NULL;
    }

    return $revision_id ?? NULL;
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
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   The pattern usage storage handler.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function storage(): ContentEntityStorageInterface {
    if (!isset($this->storage)) {
      $this->storage = $this->entityTypeManager->getStorage('pattern_usage');
    }

    return $this->storage;
  }

}
