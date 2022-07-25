<?php

namespace Drupal\patternkit\Form;

use Drupal\Core\Cache\CacheCollectorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\patternkit\Asset\LibraryNamespaceResolverInterface;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The patternkit module settings configuration form.
 */
class PatternkitSettingsForm extends ConfigFormBase {

  /**
   * The configuration identifier for patternkit settings.
   *
   * @var string
   */
  const SETTINGS = 'patternkit.settings';

  /**
   * The library namespace resolver service for loading library definitions.
   *
   * @var \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface
   */
  protected LibraryNamespaceResolverInterface $libraryNamespaceResolver;

  /**
   * The pattern discovery service for loading patterns.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $patternDiscovery;

  /**
   * Constructor for PatternkitSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface $libraryNamespaceResolver
   *   The library namespace resolver service.
   * @param \Drupal\patternkit\Asset\PatternDiscoveryInterface $patternDiscovery
   *   The pattern discovery service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LibraryNamespaceResolverInterface $libraryNamespaceResolver,
    PatternDiscoveryInterface $patternDiscovery
  ) {
    $this->libraryNamespaceResolver = $libraryNamespaceResolver;
    $this->patternDiscovery = $patternDiscovery;

    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('patternkit.library.namespace_resolver'),
      $container->get('patternkit.pattern.discovery'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);
    try {
      $libraries = $this->libraryNamespaceResolver->getLibraryDefinitions();
      $patterns = $this->patternDiscovery->getPatternDefinitions();
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
    foreach ($libraries as $namespace => $library) {
      if (empty($library['patterns'])) {
        continue;
      }

      $lib_id = $library['id'];
      $lib_desc = $library['description'] ?? $lib_id;
      $lib_patterns = $patterns[$library['namespace']];
      $lib_desc = t('@title (@count patterns)', [
        '@title' => $lib_desc,
        '@count' => count($lib_patterns),
      ]);

      $form['patternkit_libraries'][$lib_id]['description'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="library"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{{ description }}</div>{% endif %}</div>',
        '#context' => [
          'title' => $lib_desc,
        ],
      ];
      if (!empty($library['description'])) {
        $form['patternkit_libraries'][$lib_id]['description']['#context']['description'] = $library['description'];
      }
      $form['patternkit_libraries'][$lib_id]['enabled'] = [
        '#title' => $this->t('Library Enabled'),
        '#title_display' => 'invisible',
        '#wrapper_attributes' => ['class' => ['checkbox']],
        '#type' => 'checkbox',
        '#default_value' => $library_options[$lib_id]['enabled'] ?? 1,
        '#attributes' => [
          'class' => [
            'lib-' . $lib_id,
            'js-lib-' . $lib_id,
          ],
        ],
      ];
      $form['patternkit_libraries'][$lib_id]['visible'] = [
        '#title' => $this->t('Library Visible in Lists'),
        '#title_display' => 'invisible',
        '#wrapper_attributes' => ['class' => ['checkbox']],
        '#type' => 'checkbox',
        '#default_value' => $library_options[$lib_id]['visible'] ?? 1,
        '#attributes' => [
          'class' => [
            'lib-' . $lib_id,
            'js-lib-' . $lib_id,
          ],
        ],
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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'patternkit_config';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(static::SETTINGS);
    $config
      ->set('patternkit_libraries', $form_state->getValue('patternkit_libraries'))
      ->set('patternkit_cache_enabled', $form_state->getValue('patternkit_cache_enabled'))
      ->set('patternkit_render_cache', $form_state->getValue('patternkit_render_cache'))
      ->save();
    if ($form_state->getValue('patternkit_cache_enabled')
      && !$config->get('patternkit_cache_enabled')) {
      $this->library->clearCachedDefinitions();
    }
    $libraries = $this->libraryNamespaceResolver->getLibraryDefinitions();
    $count = count($libraries);
    $this->messenger()->addStatus($this->t('Rebuilt Patternkit Library Cache with @count libraries.', ['@count' => $count]));

    parent::submitForm($form, $form_state);
  }

  /**
   * Rebuild pattern and library discovery caches.
   */
  protected function rebuildCaches(): void {
    if ($this->libraryNamespaceResolver instanceof CacheCollectorInterface) {
      $this->libraryNamespaceResolver->clear();
    }
    $this->patternDiscovery->clearCachedDefinitions();
  }

}
