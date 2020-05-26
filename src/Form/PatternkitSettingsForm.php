<?php

namespace Drupal\patternkit\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\patternkit\PatternkitLibraryDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PatternkitSettingsForm extends ConfigFormBase {

  /** @var string */
  public const SETTINGS = 'patternkit.settings';

  /** @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface */
  protected $libraryDiscovery;

  public function __construct(ConfigFactoryInterface $config_factory, PatternkitLibraryDiscoveryInterface $pattern_discovery) {
    $this->libraryDiscovery = $pattern_discovery;
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
    try {
      $libraries = $this->libraryDiscovery->getLibraries();
    }
    catch (\Exception $exception) {
      $this->getLogger('patternkit')->error('Unable to load Patternkit libraries list: @message', ['@message' => $exception->getMessage()]);
      \Drupal::messenger()->addMessage(t('Unable to load Patternkit libraries list. Check the logs for more information.'), 'error');
      return [
        '#markup' => $this->t('Settings are unavailable when Pattern libraries fail to load to prevent config errors.'),
      ];
    }

    $form['patternkit_libraries'] = [
      '#type' => 'table',
      '#header' => [$this->t('Patternkit Library'),
        ['data' => $this->t('Enabled'), 'class' => ['checkbox']],
        ['data' => $this->t('Visible in Lists'), 'class' => ['checkbox']],
      ],
      '#attributes' => ['class' => ['libraries', 'js-libraries']],
      '#sticky' => TRUE,
    ];
    $library_options = $config->get('patternkit_libraries') ?? [];
    foreach ($libraries as $lib_title => $library) {
      $lib_desc = $library['description'] ?? $lib_title;
      if (!empty($library['patterns'])) {
        $lib_desc = t('@title (@count patterns)', [
          '@title' => $lib_title,
          '@count' => count($library['patterns']),
        ]);
      }
      $form['patternkit_libraries'][$lib_title]['description'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="library"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{{ description }}</div>{% endif %}</div>',
        '#context' => [
          'title' => $lib_desc,
        ],
      ];
      if (!empty($library['description'])) {
        $form['patternkit_libraries'][$lib_title]['description']['#context']['description'] = $library['description'];
      }
      $form['patternkit_libraries'][$lib_title]['enabled'] = [
        '#title' => $this->t('Library Enabled'),
        '#title_display' => 'invisible',
        '#wrapper_attributes' => ['class' => ['checkbox']],
        '#type' => 'checkbox',
        '#default_value' => $library_options[$lib_title]['enabled'] ?? 1,
        '#attributes' => ['class' => ['lib-' . $lib_title, 'js-lib-' . $lib_title]],
      ];
      $form['patternkit_libraries'][$lib_title]['visible'] = [
        '#title' => $this->t('Library Visible in Lists'),
        '#title_display' => 'invisible',
        '#wrapper_attributes' => ['class' => ['checkbox']],
        '#type' => 'checkbox',
        '#default_value' => $library_options[$lib_title]['visible'] ?? 1,
        '#attributes' => ['class' => ['lib-' . $lib_title, 'js-lib-' . $lib_title]],
      ];
    }

    $form['patternkit_cache_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Use the Patternkit Library Cache'),
      '#default_value' => $config->get('patternkit_cache_enabled'),
    ];

    $form['patternkit_render_cache'] = [
      '#type' => 'checkbox',
      '#title' => t('Use the Patternkit Disk Render Cache'),
      '#default_value' => $config->get('patternkit_render_cache'),
    ];

    $form['patternkit_default_module_ttl'] = [
      '#type' => 'textfield',
      '#title' => t('Patternkit Default Pattern TTL (in ms)'),
      '#default_value' => $config->get('patternkit_default_module_ttl'),
    ];

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
    $config = $this->config(static::SETTINGS)
      ->set('patternkit_libraries', $form_state->getValue('patternkit_libraries'))
      ->set('patternkit_cache_enabled', $form_state->getValue('patternkit_cache_enabled'))
      ->set('patternkit_render_cache', $form_state->getValue('patternkit_render_cache'))
      ->set('patternkit_default_module_ttl', $form_state->getValue('patternkit_default_module_ttl'))
      ->save();
    if ($form_state->getValue('patternkit_cache_enabled')
      && !$config->get('patternkit_cache_enabled')) {
      $this->libraryDiscovery->clearCachedDefinitions();
    }
    $libraries = $this->libraryDiscovery->getLibraries();
    $count = count($libraries);
    $this->messenger()->addStatus($this->t('Rebuilt Patternkit Library Cache with @count libraries.', ['@count' => $count]));
    parent::submitForm($form, $form_state);
  }

}
