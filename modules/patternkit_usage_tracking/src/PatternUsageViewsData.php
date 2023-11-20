<?php

namespace Drupal\patternkit_usage_tracking;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the pattern usage entity type.
 */
class PatternUsageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Clone to a fake field for node-specific revision relationships.
    $data['pattern_usage']['node_revision_id'] = $data['pattern_usage']['source_revision_id'];
    $data['pattern_usage']['node_revision_id']['title'] = $this->t('Source node revision ID');
    $data['pattern_usage']['node_revision_id']['help'] = $this->t('The revision ID of the source node.');
    $data['pattern_usage']['node_revision_id']['entity field'] = 'source_revision_id';
    $data['pattern_usage']['node_revision_id']['relationship'] = [
      'field' => 'source_revision_id',
      'base' => 'node_field_revision',
      'base field' => 'vid',
      'id' => 'standard',
      'label' => $this->t('Source node revision'),
      'title' => $this->t('Source node revision'),
      // Restrict this relationship to only node-specific records.
      'extra' => [
        [
          'left_field' => 'source_entity_type',
          'value' => 'node',
        ],
      ],
    ];

    // Create a relationship to the active node data instead of all revisions
    // to allow display of only active content.
    $data['pattern_usage']['active_node_revision_id'] = $data['pattern_usage']['source_revision_id'];
    $data['pattern_usage']['active_node_revision_id']['title'] = $this->t('Active source node');
    $data['pattern_usage']['active_node_revision_id']['help'] = $this->t('The revision ID of the active source node.');
    $data['pattern_usage']['active_node_revision_id']['entity field'] = 'source_revision_id';
    $data['pattern_usage']['active_node_revision_id']['relationship'] = [
      'field' => 'source_revision_id',
      'base' => 'node_field_data',
      'base field' => 'vid',
      'id' => 'standard',
      'label' => $this->t('Active source node'),
      'title' => $this->t('Active source node'),
      // Restrict this relationship to only node-specific records.
      'extra' => [
        [
          'left_field' => 'source_entity_type',
          'value' => 'node',
        ],
      ],
    ];

    return $data;
  }

}
