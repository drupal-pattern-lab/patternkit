<?php

namespace Drupal\patternkit\FormElement;

use Drupal\config_translation\FormElement\FormElementBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Language\LanguageInterface;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;

/**
 * Form element for translation of Pattern kit JSON.
 */
class PatternkitJson extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  protected function getTranslationElement(LanguageInterface $translation_language, $source_config, $translation_config): array {
    $form = [];
    $configuration = $source_config;
    $configuration['patternkit_block_id'] = $translation_config['patternkit_block_id'] ?? $source_config['patternkit_block_id'];

    $configuration['patternkit_block_rid'] = $translation_config['patternkit_block_rid'] ?? $source_config['patternkit_block_rid'];
    $configuration['langcode'] = $translation_language->getId();

    $container = \Drupal::getContainer();
    $patternBlock = PatternkitBlock::create($container, $configuration, 'patternkit_block', []);
    // Form state not used in this method, but is required for the block form.
    $form_state = new FormState();

    $form = $patternBlock->blockForm($form, $form_state);
    $form['langcode'] = [
      '#type' => 'hidden',
      '#default_value' => $translation_language->getId(),
    ];
    $form['patternkit_block_rid'] = [
      '#type' => 'hidden',
      '#default_value' => $configuration['patternkit_block_rid'],
    ];

    $form['patternkit_block_id'] = [
      '#type' => 'hidden',
      '#default_value' => $configuration['patternkit_block_id'],
    ];

    $form['instance_config']['#default_value'] = $translation_config['instance_config'];

    return $form;
  }

}
