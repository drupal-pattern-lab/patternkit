<?php

namespace Drupal\patternkit_media_library\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for configuring Patternkit's media library integration.
 */
class MediaLibrarySettingsForm extends ConfigFormBase {

  /**
   * Settings identifier.
   *
   * @var string
   */
  public const SETTINGS = 'patternkit_media_library.settings';

  /**
   * Implements buildForm().
   *
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) :array {
    $config = $this->config(static::SETTINGS);

    $form['use_styles'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use bundled styles for the media library modal window'),
      '#description' => t('If checked, then Patternkit will load some styles for
        the media library modal window. <br />This feature relies on templates
        provided by <a href="https://www.drupal.org/project/media_library_theme_reset">
        Media Library Theme Reset</a>, so enable that module if you want to use
        these styles.'),
      '#default_value' => $config->get('use_styles') ?? FALSE,
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
    return 'patternkit_media_library_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $config = $this->config(self::SETTINGS);

    $config->set('use_styles', (bool) $form_values['use_styles']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
