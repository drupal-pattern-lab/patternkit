<?php

namespace Drupal\patternkit_usage_tracking;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\Utility\PatternDetector;

/**
 * A service for identifying and tracking patterns within entity layouts.
 */
class LayoutUsageTracker {

  use PatternLayoutDiscoveryTrait;
  use PatternLayoutUsageInsertTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The track config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The pattern usage revision helper service.
   *
   * @var \Drupal\patternkit_usage_tracking\UsageRevisionHelper
   */
  protected UsageRevisionHelper $revisionHelper;

  /**
   * Create a new instance of a LayoutUsageDetector.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The EntityFieldManager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\patternkit_usage_tracking\PatternUsageManager $tracking_storage
   *   The pattern usage tracking storage.
   * @param \Drupal\patternkit\LayoutHelper $layout_helper
   *   The patternkit layout helper service.
   * @param \Drupal\patternkit\Utility\PatternDetector $pattern_detector
   *   The pattern detector service.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   *   The section storage manager service.
   * @param \Drupal\patternkit_usage_tracking\UsageRevisionHelper $revision_helper
   *   The pattern usage revision helper service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ConfigFactoryInterface $config_factory,
    PatternUsageManager $tracking_storage,
    LayoutHelper $layout_helper,
    PatternDetector $pattern_detector,
    SectionStorageManagerInterface $section_storage_manager,
    UsageRevisionHelper $revision_helper,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->configFactory = $config_factory;
    $this->trackingStorage = $tracking_storage;
    $this->layoutHelper = $layout_helper;
    $this->patternDetector = $pattern_detector;
    $this->sectionStorageManager = $section_storage_manager;
    $this->revisionHelper = $revision_helper;
  }

  /**
   * Test whether an entity should be tracked by this plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to potentially be tracked.
   *
   * @return bool
   *   TRUE if the entity should be tracked, FALSE otherwise.
   */
  public function shouldTrackEntity(EntityInterface $entity): bool {
    return ($entity instanceof ContentEntityInterface &&
      $this->isLayoutCompatibleEntity($entity));
  }

  /**
   * Determine whether pattern usages in all revisions should be tracked.
   *
   * If usages for all revisions are to be maintained, every updated revision
   * may be treated as a new record and tracked independently with on concern
   * for changes between revisions. If, instead, only the latest revision should
   * be tracked, usage records for previous revisions need to be updated to
   * reflect the new updates.
   *
   * @return bool
   *   TRUE if all revisions should be tracked, FALSE otherwise.
   *
   * @todo Implement configuration lookup for revision behavior.
   */
  public function shouldTrackAllRevisions(): bool {
    return FALSE;
  }

  /**
   * Track patterns within an entity's layout after initial creation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The created entity.
   *
   * @return int
   *   Returns the number of patterns detected and tracked within the entity
   *   layout.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function trackOnEntityCreation(EntityInterface $entity): int {
    // Don't track if this entity doesn't have an overridden layout.
    if (!$this->shouldTrackEntity($entity)) {
      return 0;
    }

    // Get patterns from the layout components.
    assert($entity instanceof ContentEntityInterface);
    $patterns = $this->getPatternsByLayoutComponentByEntity($entity);

    // Iterate through all the components to process patterns discovered within
    // each of them.
    $num_tracked = 0;
    foreach ($patterns as $record) {
      $this->insertComponent($entity, $record['uuid'], $record);
      $num_tracked++;
    }

    return $num_tracked;
  }

  /**
   * Track changes to patterns within an entity's layout on update.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being updated.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function trackOnEntityUpdate(EntityInterface $entity): void {
    // If we're tracking all revisions, use the new update as a new entity to
    // create all new records.
    if ($this->shouldTrackAllRevisions()) {
      $this->trackOnEntityCreation($entity);
    }
    else {
      $this->revisionHelper->trackOnEntityUpdate($entity);
    }
  }

  /**
   * Update tracked pattern usages when an entity is deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being deleted.
   * @param string $type
   *   An indicator of what type of deletion event is happening. This could be a
   *   'revision', 'translation', or 'entity' deletion.
   *
   * @todo Add support for only deleting a revision if all revisions are
   *   being tracked.
   */
  public function trackOnEntityDelete(EntityInterface $entity, string $type) {
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }

    switch ($type) {
      case 'translation':
        $this->trackingStorage->deleteByTranslation($entity);
        break;

      case 'revision':
        if ($entity->isDefaultRevision()) {
          $this->trackingStorage->deleteByEntity($entity);
        }
        break;

      default:
        $this->trackingStorage->deleteByEntity($entity);
        break;
    }
  }

}
