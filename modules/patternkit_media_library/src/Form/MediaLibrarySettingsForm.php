<?php

namespace Drupal\patternkit_media_library\Form;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
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
   * The entity bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);

    $form->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $form->entityDisplayRepository = $container->get('entity_display.repository');

    return $form;
  }

  /**
   * Implements buildForm().
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) :array {
    $config = $this->config(static::SETTINGS);

    $media_options = [];
    $media_types = $this->entityTypeBundleInfo->getBundleInfo('media');
    foreach ($media_types as $bundle => $info) {
      $media_options[$bundle] = $info['label'];
    }

    $form['use_styles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use bundled styles for the media library modal window'),
      '#description' => $this->t('If checked, then Patternkit will load some styles for
        the media library modal window. <br />This feature relies on templates
        provided by <a href="https://www.drupal.org/project/media_library_theme_reset">
        Media Library Theme Reset</a>, so enable that module if you want to use
        these styles.'),
      '#default_value' => $config->get('use_styles') ?? FALSE,
    ];

    $view_modes = $this->entityDisplayRepository->getViewModes('media');
    $display_options = [];
    foreach ($view_modes as $id => $mode) {
      $display_options[$id] = $mode['label'];
    }

    $form['media_display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Media display mode'),
      '#description' => $this->t('Select the display mode to use for selected media entities in patterns.'),
      '#options' => $display_options,
      '#default_value' => $config->get('media_display_mode') ?? '',
    ];

    $form['media_mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Media type configuration'),
      '#description' => $this->t('Map media types to the formats they may be used with.'),
      '#open' => TRUE,
    ];

    $form['media_mapping']['image_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Image media'),
      '#description' => $this->t('Select media types to allow for image fields.'),
      '#options' => $media_options,
      '#default_value' => $config->get('media_mapping.image_types') ?? [],
    ];

    $form['media_mapping']['video_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Video media'),
      '#description' => $this->t('Select media types to allow for video fields.'),
      '#options' => $media_options,
      '#default_value' => $config->get('media_mapping.video_types') ?? [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() :array {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() :string {
    return 'patternkit_media_library_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_values = $form_state->getValues();
    $config = $this->config(self::SETTINGS);

    $config->set('use_styles', (bool) $form_values['use_styles']);
    $config->set('media_mapping.image_types', array_keys(array_filter($form_values['image_types'])));
    $config->set('media_mapping.video_types', array_keys(array_filter($form_values['video_types'])));
    $config->set('media_display_mode', $form_values['media_display_mode']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
