<?php

namespace Drupal\patternkit\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Context\ContextDefinition;

class PatternkitBlock extends DeriverBase {

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    // @TODO: Replace with LoadPatterns service.
    $patterns = [
      'pattern1' => [
        'admin_label' => 'Some pattern (a)',
        'context' => [
          'node' => new ContextDefinition("entity:node"),
        ]
      ],
      'pattern2' => [
        'admin_label' => 'Some pattern (b)',
        'context' => [
          'user' => new ContextDefinition("entity:user"),
        ]
      ],
      'pattern3' => [
        'admin_label' => 'Some pattern (c)',
      ],
    ];

    // @TODO: Extend the base plugin definition.

    foreach ($patterns as $idx => $pattern) {
      $patternLibrary = 'PFE';
      $patternGroup   = 'atoms';
      $patternName    = $pattern['admin_label'];

      $this->derivatives[$idx] = $base_plugin_definition;
      $this->derivatives[$idx] += $pattern;
      $this->derivatives[$idx] += [
        'category'    => t(
          'Patternkit:@lib/@group',
          [
            '@lib'     => $patternLibrary,
            '@group'   => $patternGroup,
            '@pattern' => $patternName,
          ]
        ),
        'admin_label' => t(
          '[Patternkit] @pattern',
          [
            '@pattern' => $pattern['admin_label'],
          ]
        ),
      ];


    }

    return $this->derivatives;
  }
}
