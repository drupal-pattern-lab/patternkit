<?php

namespace Drupal\patternkit\Plugin\QueueWorker;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * A queue worker plugin for running queued Patternkit layout updates.
 *
 * A worker plugin to consume items from the "patternkit_layout_updates" queue
 * and update patternkit blocks within the referenced layouts.
 */
#[QueueWorker(
  id: 'patternkit_layout_updates',
  title: new TranslatableMarkup('Patternkit layout updates queue'),
  cron: ['time' => 30]
)]
class LayoutUpdateQueueWorker extends LayoutQueueWorkerBase {

  /**
   * The identifier for this queue worker's queue.
   */
  public const QUEUE_ID = 'patternkit_layout_updates';

  /**
   * {@inheritdoc}
   */
  public function processEntityLayout(SectionStorageInterface $storage, ContentEntityInterface $entity, array $data, array &$result): void {
    $blocks_updated = $this->updateLayoutPatterns($storage, $data['filter'] ?? []);
    $result['blocks'] = $blocks_updated;
    $result['entities'] = ($blocks_updated > 0) ? 1 : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefaultLayout(SectionStorageInterface $storage, array $data, array &$result): void {
    $blocks_updated = $this->updateLayoutPatterns($storage, $data['filter'] ?? []);
    $result['blocks'] = $blocks_updated;
    $result['entities'] = ($blocks_updated > 0) ? 1 : 0;
  }

  /**
   * Apply updates to patterns within the given layout.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $storage
   *   The layout to be updated.
   * @param array $filter
   *   An associative array of filter data.
   *
   * @return int
   *   The number of layout blocks updated during this operation.
   */
  public function updateLayoutPatterns(SectionStorageInterface $storage, array $filter = []): int {
    $this->logger->notice(sprintf('Updating patterns for layout ID "%s:%s".',
      $storage->getPluginId(), $storage->getStorageId()));

    $num_updated = 0;
    $update_callback = $this->layoutHelper->getUpdateCallback($num_updated, $filter, TRUE);

    $this->layoutHelper->applyToSectionComponents([$storage->getStorageId() => $storage], $update_callback);

    return $num_updated;
  }

}
