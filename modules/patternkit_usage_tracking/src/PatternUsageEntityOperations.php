<?php

namespace Drupal\patternkit_usage_tracking;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events related to Pattern usage.
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 */
class PatternUsageEntityOperations implements ContainerInjectionInterface {

  /**
   * The layout usage tracker service.
   *
   * @var \Drupal\patternkit_usage_tracking\LayoutUsageTracker
   */
  protected LayoutUsageTracker $layoutUsageTracker;

  /**
   * Constructs a new PatternUsageEntityOperations object.
   *
   * @param \Drupal\patternkit_usage_tracking\LayoutUsageTracker $layout_usage_tracker
   *   The layout usage tracker service.
   */
  public function __construct(LayoutUsageTracker $layout_usage_tracker) {
    $this->layoutUsageTracker = $layout_usage_tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pattern_usage.layout_tracker')
    );
  }

  /**
   * Handle insertion of a new entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being inserted.
   *
   * @see \patternkit_usage_tracking_entity_insert()
   */
  public function handleInsert(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityCreation($entity);
  }

  /**
   * Handle an entity being updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being updated.
   *
   * @see \patternkit_usage_tracking_entity_update()
   */
  public function handleUpdate(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityUpdate($entity);
  }

  /**
   * Handle the deletion of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being deleted.
   *
   * @see \patternkit_usage_tracking_entity_predelete()
   */
  public function handlePreDelete(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityDelete($entity, 'entity');
  }

  /**
   * Handle the deletion of an entity translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity translation being deleted.
   *
   * @see \patternkit_usage_tracking_entity_translation_delete()
   */
  public function handleTranslationDelete(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityDelete($entity, 'translation');
  }

  /**
   * Handle the deletion of an entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity revision being deleted.
   *
   * @see \patternkit_usage_tracking_entity_revision_delete()
   */
  public function handleRevisionDelete(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityDelete($entity, 'revision');
  }

}
