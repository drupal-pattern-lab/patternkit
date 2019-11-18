<?php

namespace Drupal\patternkit\Plugin\Block;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Drupal\patternkit\Pattern;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternkitLibraryDiscoveryInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Display all instances for 'PatternkitBlock' block plugin.
 *
 * @Block(
 *   id = "patternkit_block",
 *   admin_label = @Translation("Patternkit block"),
 *   category = @Translation("Patternkit Reusable"),
 *   deriver = "Drupal\patternkit\Plugin\Derivative\PatternkitBlock"
 * )
 */
class PatternkitBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Controls the block cache.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * Controls loading and setting cache data by key.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Stores patternkit configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Loads and saves the attached patternkit data content entity.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Loads pattern information.
   *
   * @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface
   */
  protected $patternDiscovery;

  /**
   * Loads plugins for parsing and rendering patterns.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager
   */
  protected $patternLibraryPluginManager;

  /**
   * Encodes and decodes configuration for storage.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * Parses and replaces Drupal tokens.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Overrides \Drupal\Component\Plugin\ContextAwarePluginBase::__construct().
   *
   * Adds services to allow Patternkit to load and display library patterns.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory for managing module config.
   * @param array $configuration
   *   Default configuration for the block plugin instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\patternkit\PatternkitLibraryDiscoveryInterface $pattern_discovery
   *   Pattern discovery service.
   * @param \Drupal\patternkit\PatternLibraryPluginManager $pattern_plugin_manager
   *   Pattern library plugin manager service.
   * @param string $plugin_id
   *   Plugin ID.
   * @param array $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Serialization service.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   */
  public function __construct(
    BlockManagerInterface $block_manager,
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory,
    array $configuration,
    EntityTypeManagerInterface $entity_type_manager,
    PatternkitLibraryDiscoveryInterface $pattern_discovery,
    PatternLibraryPluginManager $pattern_plugin_manager,
    $plugin_id,
    array $plugin_definition,
    SerializationInterface $serializer,
    Token $token) {

    $this->blockManager = $block_manager;
    $this->cache = $cache;
    $this->config = $config_factory->get(PatternkitSettingsForm::SETTINGS);
    $this->entityTypeManager = $entity_type_manager;
    $this->patternDiscovery = $pattern_discovery;
    $this->patternLibraryPluginManager = $pattern_plugin_manager;
    $this->serializer = $serializer;
    $this->token = $token;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Provides a patternkit specific config form.
   *
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form += parent::blockForm($form, $form_state);
    $configuration = $this->getConfiguration();
    $pattern_id = $configuration['id'];
    $plugin = $this->getPluginDefinition();
    /** @var \Drupal\patternkit\Pattern $pattern */
    try {
      $pattern = $plugin['pattern'] ?? $this->patternDiscovery->getLibraryAsset($pattern_id);
    }
    catch (\Exception $exception) {
      \Drupal::messenger()->addError($this->t('Unable to load the pattern @pattern. Check the logs for more info.', ['@pattern' => $pattern_id]));
      return ['#markup' => $this->t('Unable to edit a Patternkit block when the pattern fails to load.')];
    }
    $form_state->set('pattern', $pattern);

    $form['reusable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reusable'),
      '#default_value' => $configuration['reusable'] ?? FALSE,
    ];

    // @TODO: Re-enable the other formats like JSON and webcomponent.
    $form['presentation_style'] = [
      '#type'          => 'select',
      '#title'         => 'Presentation style',
      '#options'       => [
        'html'         => 'HTML inline',
      ],
      '#default_value' => $configuration['presentation_style'],
    ];

    // Add version as hidden field.
    $form['version'] = [
      '#type'  => 'hidden',
      '#value' => $configuration['version'] ?? $pattern->version,
    ];

    $editor_config = NULL;
    if (!empty($configuration['patternkit_block_id'])) {
      /** @var \Drupal\patternkit\Entity\PatternkitBlock $block_entity */
      $block_entity = $this->entityTypeManager->getStorage('patternkit_block')->load($configuration['patternkit_block_id']);
      $block_data_field = $block_entity->get('data')->getValue();
      $block_data = reset($block_data_field)['value'] ?? '';
      $configuration['fields'] = $this->serializer::decode($block_data);
      $editor_config = new PatternEditorConfig($configuration);
    }

    // Add Instance config (if exists) as hidden field.
    $form['instance_config'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'schema_instance_config',
      ],
      '#default_value' => $block_data ?? '',
    ];

    /** @var \Drupal\patternkit\PatternLibraryPluginInterface $library */
    $library = $this->patternLibraryPluginManager->createInstance($pattern->getLibraryPluginId());
    $editor = $library->getEditor($pattern, $editor_config);
    if (!is_array($editor)) {
      $editor = array(
        '#type' => 'inline_template',
        '#template' => $editor,
      );
    }
    $form['configuration'] = $editor;

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $block_storage */
    $block_storage = $this->entityTypeManager->getStorage('patternkit_block');
    $configuration = $this->getConfiguration();
    $pattern = $form_state->get('pattern') ?? $this->patternDiscovery->getLibraryAsset($this->configuration['id']);

    $values = [
      'data' => $form_state->getValue('instance_config'),
      'info' => $form_state->getValue('label'),
      'reusable' => $form_state->getValue('reusable'),
      'published' => TRUE,
    ];
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $patternkit_block */
    if (isset($configuration['patternkit_block_id'])
      && (int) $configuration['patternkit_block_id'] > 0
      && $patternkit_block = $block_storage->load($configuration['patternkit_block_id'])) {
      $patternkit_block->setPublished($values['published']);
      foreach (array_keys($patternkit_block->getFields()) as $key) {
        if (isset($values[$key])) {
          $patternkit_block->set($key, $values[$key]);
        }
      }
    }
    else {
      $patternkit_block = $block_storage->create($values);
    }
    $patternkit_block->save();

    // @todo Evaluate where version should be stored, entity or block config.
    $updated_config = [
      'instance_uuid' => $patternkit_block->uuid(),
      'label_display' => FALSE,
      'pattern' => $this->serializer::encode($pattern),
      'patternkit_block_id' => $patternkit_block->id(),
      'presentation_style' => $form_state->getValue('presentation_style'),
      'version' => $form_state->getValue('version'),
    ];
    $this->setConfiguration($updated_config + $configuration);
    // Invalidate the block cache to update custom block-based derivatives.
    $this->blockManager->clearCachedDefinitions();
    // Invalidate custom cache.
    // @todo Replace with cache_backend->set().
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $context = $this->getContextValues();
    $configuration = $this->getConfiguration();
    $pattern_id = $this->getDerivativeId();
    $instance_id = $configuration['instance_uuid'] ?? $pattern_id;
    $cid = "patternkit:{$pattern_id}:{$instance_id}:";
    $cid .= md5(
      $this->serializer::encode([
        \Drupal::currentUser()->isAuthenticated(),
        $context,
        $configuration,
        $language->getId(),
      ])
    );
    $this->cache->delete($cid);
    parent::blockSubmit($form, $form_state);
  }

  /**
   * Build the content for PatternKit block.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function build() {
    $block_storage = $this->entityTypeManager->getStorage('patternkit_block');
    $pattern_id = $this->getDerivativeId();
    $configuration = $this->getConfiguration();
    $context = $this->getContextValues();
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $patternkit_block */
    $patternkit_block = $block_storage->load($configuration['patternkit_block_id']);
    $base_dependencies = [];

    // Flag if a module block should be cached or not.
    $cacheable = $this->config->get('patternkit_render_cache');

    // If an instance configuration provides a UUID, use it. If not, we should
    // not cache this item because the uuid will be different each time.
    $instance_id = $configuration['instance_uuid'] ?? $pattern_id;

    // Create the cache key to be used for this object. Note that we are relying
    // on code elsewhere to clear this cache on modification. The md5 against
    // context is because context can change independently of the instance.
    // Need to be able to cache for all contexts of a specific config. We add a
    // logged-in check to prevent cached admin links from appearing in frontend.
    // It also mitigates the difference between esi delivery when logged in vs
    // not.
    // @todo Replace with getCacheTags() and cache_backend->get().
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $cid = "patternkit:{$pattern_id}:{$instance_id}:";
    $cid .= md5(
      $this->serializer::encode([
        \Drupal::currentUser()->isAuthenticated(),
        $context,
        $configuration,
        $language->getId(),
      ])
    );

    // Load module specific config.
    /** @var \Drupal\patternkit\Pattern $pattern */
    if (!empty($configuration['pattern'])) {
      $pattern_data = $this->serializer::decode($configuration['pattern']);
      $pattern_schema = $pattern_data + $pattern_data['schema'];
      unset($pattern_schema['schema']);
      $pattern = new Pattern($pattern_schema['name'], $pattern_schema);
    }
    else {
      $pattern = $this->patternDiscovery->getLibraryAsset($pattern_id);
    }
    static $is_processed;

    // Initialize our static (if necessary).
    if ($is_processed === NULL) {
      $is_processed = [];
    }

    // If we've already processed this module, don't add it to active again.
    // This array is used by the frontend to bootstrap, we want each
    // module to appear only once in this array.
    if (!isset($is_processed[$pattern_id])) {
      // Add to bootstrap list.
      $base_dependencies['js'][] = array(
        'patternkit' => array(
          'active' => array(
            $pattern_id => array(
              $instance_id,
            ),
          ),
        ),
        'type' => 'setting',
      );
      $is_processed[$pattern_id] = 1;
    }

    // If the item is cache-able, fetch it and return it.
    if ($cacheable) {
      // Attempt to fetch the cached block.
      $cached = $this->cache->get($cid);
      if ($cached !== FALSE) {

        // Set flag to let other modules know content is patternkit cache.
        $cached->patternkit = 1;

        return $cached->data;
      }
    }

    // Pull the dependencies and configuration.
    $data = $patternkit_block->get('data')->getValue();
    $config = $this->serializer::decode(reset($data)['value']);
    $pattern->config = $config;

    // @todo Revisit twig default hard-coding.
    $pattern_plugin = $pattern->getLibraryPluginId();
    $library_plugin_id = !empty($pattern_plugin) ? $pattern_plugin : 'twig';
    /** @var \Drupal\patternkit\PatternLibraryPluginInterface $library_plugin */
    $library_plugin = $this->patternLibraryPluginManager->createInstance($library_plugin_id);
    $elements = $library_plugin->render([$pattern]);

    // @todo If context is available, tokenize context instead of the template.
    // This is mostly useful for twig-based libraries.
    $markup = '';
    foreach ($elements as $element) {
      // Replace context tokens.
      $element = $this->token->replace(
        $element,
        $context
      );

      // Replace core tokens in the body (if any).
      $markup .= $this->token->replace(
        $element,
        array(
          'patternkit' => array(
            'PatternkitPattern' => $pattern_id,
            'instance_id' => $instance_id,
          ),
        )
      );
    }

    // @todo Merge attachment JS and dependencies.
    // Parse TTL and add to params.
    if (!empty($pattern->ttl)) {
      $ttl = $pattern->ttl;
    }
    else {
      // Default ttl to module setting (usually 30 days).
      $ttl = $this->config->get('patternkit_default_module_ttl');
    }

    $content = [
      'pattern'   => [
        '#type' => 'inline_template',
        '#template' => $markup,
      ],
      '#attached' => $config['pkdata']['attachments'] ?? [],
    ];
    // Save to the cache bin (if caching is enabled).
    if ($cacheable) {
      $this->cache->set($cid, $content, time() + $ttl, ['cache_patternkit']);
    }

    return $content;
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // Remove the title override fields.
    unset($form['label_display']);
    $form['label']['#states'] = [
      'visible' => [
        ':input[name="settings[reusable]"]' => ['checked' => TRUE],
      ],
    ];
    return $form;
  }

  /**
   * Returns a new PatternkitBlock instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The global project container.
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin id.
   * @param array $plugin_definition
   *   Plugin definition.
   *
   * @return \Drupal\patternkit\Plugin\Block\PatternkitBlock
   *   Patternkit block plugin.
   *
   * @todo Eval usefulness of cache.default vs cache.render vs none.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition): PatternkitBlock {

    /** @var \Drupal\Core\Block\BlockManagerInterface $block_plugin_manager */
    $block_plugin_manager = $container->get('plugin.manager.block');
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache */
    $cache = $container->get('cache.default');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    /** @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface $pattern_discovery */
    $pattern_discovery = $container->get('patternkit.library.discovery');
    /** @var \Drupal\patternkit\PatternLibraryPluginManager $pattern_plugin_manager */
    $pattern_plugin_manager = $container->get('plugin.manager.library.pattern');
    /** @var \Drupal\Component\Serialization\SerializationInterface $serializer */
    $serializer = $container->get('serialization.json');
    /** @var \Drupal\Core\Utility\Token $token */
    $token = $container->get('token');
    return new static(
      $block_plugin_manager,
      $cache,
      $config_factory,
      $configuration,
      $entity_type_manager,
      $pattern_discovery,
      $pattern_plugin_manager,
      $plugin_id,
      $plugin_definition,
      $serializer,
      $token);
  }

  /**
   * Adds additional defaults.
   *
   * @return array
   *   Configuration defaults array.
   */
  public function defaultConfiguration(): array {
    $configuration = [
      'presentation_style' => 'html',
      'version' => NULL,
    ];
    return $configuration + parent::defaultConfiguration();
  }

}
