<?php

namespace Drupal\patternkit\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Display all instances for 'PatternkitBlock' block plugin.
 *
 * @Block(
 *   id = "patternkit_block",
 *   admin_label = @Translation("Patternkit block"),
 *   category="Patternkit",
 *   deriver = "Drupal\patternkit\Plugin\Derivative\PatternkitBlock"
 * )
 */
class PatternkitBlock extends BlockBase {
  /**
   * Build the content for PatternKit block.
   */
  public function build() {
    $block_id = $this->getDerivativeId();
    return array(
      '#markup' => $block_id,
    );
  }
}
