<?php

declare(strict_types=1);

namespace Drupal\patternkit;

use Drupal\patternkit\Entity\PatternInterface;

/**
 * A common interface for services building a pattern form.
 */
interface PatternFormBuilderInterface {

  /**
   * Build the editor form for the provided pattern entity.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern entity for the form.
   * @param array $settings
   *   Settings to configure the behavior of the JSONEditor form.
   *
   * @return array
   *   A render array for the editor form.
   */
  public function buildEditorForm(PatternInterface $pattern, array $settings = []): array;

}
