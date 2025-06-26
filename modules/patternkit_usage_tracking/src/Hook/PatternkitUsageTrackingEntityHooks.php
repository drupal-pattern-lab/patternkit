<?php

namespace Drupal\patternkit_usage_tracking\Hook;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\patternkit_usage_tracking\LayoutUsageTracker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hooks for Patternkit Usage Tracking entity operations.
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 */
class PatternkitUsageTrackingEntityHooks implements ContainerInjectionInterface {

  /**
   * Constructs a new PatternkitUsageTrackingEntityHooks object.
   *
   * @param \Drupal\patternkit_usage_tracking\LayoutUsageTracker $layoutUsageTracker
   *   The layout usage tracker service.
   */
  public function __construct(
    protected LayoutUsageTracker $layoutUsageTracker,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('pattern_usage.layout_tracker')
    );
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityCreation($entity);
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityUpdate($entity);
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPreDelete(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityDelete($entity, 'entity');
  }

  /**
   * Implements hook_entity_translation_delete().
   */
  #[Hook('entity_translation_delete')]
  public function entityTranslationDelete(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityDelete($entity, 'translation');
  }

  /**
   * Implements hook_entity_revision_delete().
   */
  #[Hook('entity_revision_delete')]
  public function entityRevisionDelete(EntityInterface $entity): void {
    $this->layoutUsageTracker->trackOnEntityDelete($entity, 'revision');
  }

}
