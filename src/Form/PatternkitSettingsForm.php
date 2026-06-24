<?php

namespace Drupal\patternkit\Form;

use Drupal\Core\Cache\CacheCollectorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
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
   * Constructor for PatternkitSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface $libraryNamespaceResolver
   *   The library namespace resolver service.
   * @param \Drupal\patternkit\Asset\PatternDiscoveryInterface $patternDiscovery
   *   The pattern discovery service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state management service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    protected LibraryNamespaceResolverInterface $libraryNamespaceResolver,
    protected PatternDiscoveryInterface $patternDiscovery,
    protected StateInterface $state,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('patternkit.library.namespace_resolver'),
      $container->get('patternkit.pattern.discovery'),
      $container->get('state'),
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
      $this->messenger()->addMessage($this->t('Unable to load Patternkit libraries list. Check the logs for more information.'), 'error');
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
    foreach ($libraries as $library) {
      if (empty($library['patterns'])) {
        continue;
      }

      $lib_id = $library['id'];
      $lib_desc = $library['description'] ?? $lib_id;
      $lib_patterns = $patterns[$library['namespace']];
      $lib_desc = $this->t('@title (@count patterns)', [
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

    $form['advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => TRUE,
    ];

    $form['advanced_settings']['entity_cache_tag_invalidation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Entity cache tag invalidation'),
      '#description' => $this->t('If enabled, Pattern entity cache tags will be invalidated on pattern updates.'),
      '#default_value' => $config->get('entity_cache_tag_invalidation') ?? FALSE,
    ];

    $form['advanced_settings']['unknown_schema_logging_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unknown schema logging enabled'),
      '#description' => $this->t('If enabled, warnings will be logged when unknown schemas are encountered during Pattern processing.'),
      '#default_value' => $this->state->get('patternkit.unknown_schema_logging_enabled', TRUE),
    ];

    $form['advanced_settings']['render_validation_on_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent saving invalid pattern content'),
      '#description' => $this->t('Prevents saving Patternkit content that fails server-side schema validation. Disable temporarily to unblock editorial workflows over invalid content. Note: the JSON Editor client validates independently and may still block save attempts before they reach the server; disabling this toggle does not bypass client-side validation.'),
      '#default_value' => $config->get('render_validation_on_save') ?? TRUE,
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
      ->set('entity_cache_tag_invalidation', $form_state->getValue('entity_cache_tag_invalidation'))
      ->set('render_validation_on_save', $form_state->getValue('render_validation_on_save'))
      ->save();

    // Store the log suppression setting in state instead of config, so it may
    // be managed independently per deployment environment.
    $this->state->set('patternkit.unknown_schema_logging_enabled',
      $form_state->getValue('unknown_schema_logging_enabled', TRUE));

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
