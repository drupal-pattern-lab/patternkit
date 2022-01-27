<?php

namespace Drupal\patternkit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationHandler;

/**
 * Defines the translation handler for custom blocks.
 */
class PatternTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  protected function entityFormTitle(EntityInterface $entity) {
    return $this->t('Edit @title', ['@title' => $entity->label()]);
  }

}
