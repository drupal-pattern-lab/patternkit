<?php

namespace Drupal\patternkit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for configuring the REST Library Plugin.
 */
class PatternLibraryRESTForm extends ConfigFormBase {

  /**
   * Settings identifier.
   *
   * @var string
   */
  public const SETTINGS = 'patternkit.settings';

  /**
   * Implements buildForm().
   *
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) :array {
    $config = $this->config(static::SETTINGS);

    $form['patternkit_pl_host'] = array(
      '#type' => 'textfield',
      '#title' => t('PatternLab Host Web Address'),
      '#description' => t('Enter the website address of the PatternLab host REST endpoint.'),
      '#default_value' => $config->get('patternkit_pl_host'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() :array {
    return [static::SETTINGS];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() :string {
    return 'patternkit_rest_editor_config';
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(static::SETTINGS)
      ->set('patternkit_pl_host', $form_state->getValue('patternkit_pl_host'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
