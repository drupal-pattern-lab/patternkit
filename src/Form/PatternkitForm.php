<?php

namespace Drupal\patternkit\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the custom block edit forms.
 *
 * @internal
 */
class PatternkitForm extends ContentEntityForm {

  /**
   * The block content entity.
   *
   * @var \Drupal\patternkit\entity\PatternkitBlock
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $block = $this->entity;

    $form = parent::form($form, $form_state);

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit Patternkit block %label', ['%label' => $block->label()]);
    }
    // Override the default CSS class name, since the user-defined custom block
    // type name in 'TYPE-block-form' potentially clashes with third-party class
    // names.
    $form['#attributes']['class'][0] = 'block-' . Html::getClass($block->bundle()) . '-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    $block = $this->entity;

    $insert = $block->isNew();
    $block->setReusable();
    $block->save();
    $context = ['@type' => $block->bundle(), '%info' => $block->label()];
    $logger = $this->logger('patternkit');
    $t_args = ['@type' => $block->bundle(), '%info' => $block->label()];

    if ($insert) {
      $logger->notice('@type: added %info.', $context);
      $this->messenger()->addStatus($this->t('@type %info has been created.', $t_args));
    }
    else {
      $logger->notice('@type: updated %info.', $context);
      $this->messenger()->addStatus($this->t('@type %info has been updated.', $t_args));
    }

    if ($block->id()) {
      $form_state->setValue('id', $block->id());
      $form_state->set('id', $block->id());
      if ($insert) {
        if (!$theme = $block->getTheme()) {
          $theme = $this->config('system.theme')->get('default');
        }
        if (!$pattern = $block->getPattern()) {
          $block->setPattern($form_state->getValue('pattern'));
        }
        $form_state->setRedirect(
          'block.admin_add',
          [
            'plugin_id' => 'patternkit_block:' . $block->uuid(),
            'theme' => $theme,
          ]
        );
      }
      else {
        $form_state->setRedirectUrl($block->toUrl('collection'));
      }
    }
    else {
      // In the unlikely case something went wrong on save, the block will be
      // rebuilt and block form redisplayed.
      $this->messenger()->addError($this->t('The block could not be saved.'));
      $form_state->setRebuild();
    }
  }

}
