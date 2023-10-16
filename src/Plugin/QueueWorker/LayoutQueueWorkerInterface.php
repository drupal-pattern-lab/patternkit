<?php

namespace Drupal\patternkit\Plugin\QueueWorker;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * A common interface for all Patternkit layout processor queue worker plugins.
 *
 * @see \Drupal\patternkit\Plugin\QueueWorker\LayoutQueueWorkerBase
 */
interface LayoutQueueWorkerInterface extends QueueWorkerInterface {

  /**
   * {@inheritdoc}
   *
   * @param array{display_id: string, layout_plugin: string, entity_type: string, entity_bundle: string, mode: string, filter: array, entity_id?: int, entity_revision_id?: int} $data
   *   The layout data to process. Entity ID and revision ID are only populated
   *   for entity-specific layouts. For entity-specific layouts, the entity ID
   *   should always be populated while the revision ID is only present if the
   *   entity type is revisionable.
   */
  public function processItem($data);

  /**
   * Process an entity-specific layout.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $storage
   *   The layout storage to be processed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for the layout being processed.
   * @param array $data
   *   Processing data for the overall operation.
   * @param array $result
   *   The results array for the queue item being processed. This value is
   *   passed by reference and altered directly.
   */
  public function processEntityLayout(SectionStorageInterface $storage, ContentEntityInterface $entity, array $data, array &$result): void;

  /**
   * Process a default layout for an entity type.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $storage
   *   The layout storage to be processed.
   * @param array $data
   *   Processing data for the overall operation.
   * @param array $result
   *   The results array for the queue item being processed. This value is
   *   passed by reference and altered directly.
   */
  public function processDefaultLayout(SectionStorageInterface $storage, array $data, array &$result): void;

  /**
   * Set a context object for batch processing feedback.
   *
   * This context object is assigned by reference to enable sharing information
   * back to a calling batch process throughout execution.
   *
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   Batch context. It may be an array, or implementing \ArrayObject in the
   *   case of Drush.
   */
  public function setContext(&$context): void;

}
