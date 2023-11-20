<?php

namespace Drupal\patternkit;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\EntityInterface;

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
