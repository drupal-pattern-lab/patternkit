<?php

namespace Drupal\patternkit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\patternkit\PatternkitLibraryDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PatternkitSettingsForm extends ConfigFormBase {

  /** @var string */
  public const SETTINGS = 'patternkit.settings';

  /** @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface */
  protected $patternLibraryDiscovery;

  public function __construct(ConfigFactoryInterface $config_factory, PatternkitLibraryDiscoveryInterface $pattern_discovery) {
    $this->patternLibraryDiscovery = $pattern_discovery;
    parent::__construct($config_factory);
  }

  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    /** @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface $pattern_discovery */
    $pattern_discovery = $container->get('patternkit.library.discovery');
    return new static($config_factory, $pattern_discovery);
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) :array {
    $config = $this->config(static::SETTINGS);
    $libraries = $this->patternLibraryDiscovery->getLibraries();
    $library_options = array();
    $library_values = array();
    foreach ($libraries as $lib_title => $library) {
      $lib_desc = $library['description'] ?? $lib_title;
      if (!empty($library['patterns'])) {
        $library_values[] = $lib_title;
        $lib_desc = t('@title (@count patterns)', array(
          '@title' => $lib_title,
          '@count' => count($library['patterns']),
        ));
      }
      $library_options[$lib_title] = $lib_desc;
    }
    $form['patternkit_libraries'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Enabled Patternkit Libraries'),
      '#options' => $library_options,
      '#default_value' => $library_values,
      '#disabled' => TRUE,
    );

    $form['patternkit_cache_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the Patternkit Library Cache'),
      '#default_value' => $config->get('patternkit_cache_enabled'),
    );

    $form['patternkit_render_cache'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the Patternkit Disk Render Cache'),
      '#default_value' => $config->get('patternkit_render_cache'),
    );

    $form['patternkit_default_module_ttl'] = array(
      '#type' => 'textfield',
      '#title' => t('Patternkit Default Pattern TTL (in ms)'),
      '#default_value' => $config->get('patternkit_default_module_ttl'),
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
    return 'patternkit_config';
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(static::SETTINGS);
    if ($form_state['values']['patternkit_cache_enabled']
      && !$config->get('patternkit_cache_enabled')) {
      $libraries = $this->patternLibraryDiscovery->getLibraries();
      foreach ($libraries as $library) {
        $library->getCachedMetadata(NULL, TRUE);
      }
    }

    $this->messenger()->addStatus($this->t('Rebuilt Patternkit Library Cache.'));
    parent::submitForm($form, $form_state);
  }

}
