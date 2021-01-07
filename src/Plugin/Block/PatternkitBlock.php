<?php

namespace Drupal\patternkit\Plugin\Block;

use Drupal\Component\Plugin\Context\Context;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\entity\PatternInterface;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\Asset\LibraryInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that will display a Pattern from a Library with context.
 *
 * @Block(
 *   id = "patternkit_block",
 *   admin_label = @Translation("Patternkit block"),
 *   category = @Translation("Patternkit Reusable"),
 *   deriver = "Drupal\patternkit\Plugin\Derivative\PatternkitBlock"
 * )
 *
 * @todo Remove JSON Serializer dependencies from this class.
 * ...so that the Editor operates off of the pattern library plugin instead.
 */
class PatternkitBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Controls the block cache.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * Allows adding all current contexts to the block plugin.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Loads and saves the attached patternkit data content entity.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Loads pattern information.
   *
   * @var \Drupal\patternkit\Asset\LibraryInterface
   */
  protected $library;

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
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition): PatternkitBlock {

    /** @var \Drupal\Core\Block\BlockManagerInterface $block_plugin_manager */
    $block_plugin_manager = $container->get('plugin.manager.block');
    /** @var \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler */
    $context_handler = $container->get('context.handler');
    /** @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository */
    $context_repository = $container->get('context.repository');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    /** @var \Drupal\patternkit\Asset\LibraryInterface $library */
    $library = $container->get('patternkit.asset.library');
    /** @var \Drupal\patternkit\PatternLibraryPluginManager $pattern_plugin_manager */
    $pattern_plugin_manager = $container->get('plugin.manager.library.pattern');
    /** @var \Drupal\Component\Serialization\SerializationInterface $serializer */
    $serializer = $container->get('serialization.json');
    /** @var \Drupal\Core\Utility\Token $token */
    $token = $container->get('token');
    return new static(
      $block_plugin_manager,
      $configuration,
      $context_handler,
      $context_repository,
      $entity_type_manager,
      $library,
      $pattern_plugin_manager,
      $plugin_id,
      $plugin_definition,
      $serializer,
      $token);
  }

  /**
   * Overrides \Drupal\Component\Plugin\ContextAwarePluginBase::__construct().
   *
   * Adds services to allow Patternkit to load and display library patterns.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block manager service.
   * @param array $configuration
   *   Default configuration for the block plugin instance.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler allows mapping provided contexts to definitions.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The repository of available contexts for the current route.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\patternkit\Asset\LibraryInterface $library
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
    array $configuration,
    ContextHandlerInterface $context_handler,
    ContextRepositoryInterface $context_repository,
    EntityTypeManagerInterface $entity_type_manager,
    LibraryInterface $library,
    PatternLibraryPluginManager $pattern_plugin_manager,
    $plugin_id,
    array $plugin_definition,
    SerializationInterface $serializer,
    Token $token) {

    $this->blockManager = $block_manager;
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->library = $library;
    $this->patternLibraryPluginManager = $pattern_plugin_manager;
    $this->serializer = $serializer;
    $this->token = $token;

    // Assigns contexts based on the convention that token base names are identical
    // to context name root keys.
    // @todo Find a more robust way to map these via token info.
    // @todo Handle multiple contexts from the same context root key.
    // @see \Drupal\layout_builder\Form\ConfigureBlockFormBase::doBuildForm
    $tokens = $this->token->getInfo()['tokens'];
    foreach ($this->contextRepository->getAvailableContexts() as $name => $context) {
      $id = trim(substr($name, 0, strpos($name, '.')), '@');
      if (array_key_exists($id, $tokens)) {
        $plugin_definition['context_definitions'][$id] = $context->getContextDefinition()
          ->setRequired(FALSE);
        $configuration['context'][$id] = $context->getContextValue();
      }
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return AjaxResponse
   */
  public function ajaxUpdatePattern(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
    return $response;
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
    $plugin = $this->getPluginDefinition();
    /** @var \Drupal\patternkit\entity\PatternInterface $pattern */
    try {
      $pattern = $form_state->get('pattern');
    }
    catch (\Exception $exception) {
      $pattern = NULL;
    }
    try {
      if (!$pattern && isset($configuration['pattern'])) {
        $pattern = \Drupal::entityTypeManager()->getStorage('patternkit_pattern')->loadRevision($configuration['pattern']);
      }
      elseif (!$pattern) {
        $pattern = is_array($plugin['pattern']) ? Pattern::create($plugin['pattern']) : $plugin['pattern'];
      }
      $pattern_id = $pattern->getAssetId();
    }
    catch (\Exception $exception) {
      \Drupal::messenger()->addError($this->t('Unable to load the pattern @pattern. Check the logs for more info.', ['@pattern' => $pattern_id ?? $plugin['pattern']]));
      return ['#markup' => $this->t('Unable to edit a Patternkit block when the pattern fails to load.')];
    }
    $form_state->set('pattern', $pattern);

    unset($form['schema_desc'], $form['schema_update']);
    if ($form_state->get('schema_updated')) {
      $form['schema_desc'] = [
        '#markup' => $this->t('Successfully updated the pattern schema and template to @version.',
          [ '@version' => $pattern->getVersion() ]
        ),
      ];
    }
    try {
      $base_pattern = Pattern::create($this->library->getLibraryAsset($pattern_id));
      if ($base_pattern->getHash() !== $pattern->getHash()) {
        $form['schema_desc'] = [
          '#markup' => $this->t('Update pattern schema and template from @old_version to @version:',
            [ '@old_version' => $pattern->getVersion(),
              '@version' => $base_pattern->getVersion()]
            ),
        ];
        $form['schema_update'] = [
          '#ajax' => ['callback' => [ $this, 'ajaxUpdatePattern']],
          '#type' => 'submit',
          '#value' => $this->t('Update pattern'),
        ];
        $form_state->set('base_pattern', $base_pattern);
      }
    }
    catch (\Exception $exception) {
      /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
      $logger = \Drupal::service('logger.channel.patternkit');
      $logger->info($this->t('Unable to show update UI for pattern in filesystem: ') . $pattern_id);
    }

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

    $this->tokenForm($form, $form_state);

    // Add version as hidden field.
    $form['version'] = [
      '#type'  => 'hidden',
      '#value' => $configuration['version'] ?? $pattern->getVersion(),
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
   * Overrides blockSubmit to handle saving of PatternkitBlocks and Patterns.
   *
   * Note that this method takes the form structure and form state for the full
   * block configuration form as arguments, not just the elements defined in
   * BlockPluginInterface::blockForm().
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Block\BlockPluginInterface::blockForm()
   * @see \Drupal\Core\Block\BlockPluginInterface::blockValidate()
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $block_storage */
    $block_storage = $this->entityTypeManager->getStorage('patternkit_block');
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

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $pattern_storage */
    $pattern_storage = $this->entityTypeManager->getStorage('patternkit_pattern');
    $pattern_id = \Drupal\patternkit\Plugin\Derivative\PatternkitBlock::derivativeToAssetId($this->getDerivativeId());
    /** @var PatternInterface $pattern */
    $pattern = $form_state->get('pattern') ?? $this->library->getLibraryAsset($pattern_id);
    $pattern_cache = $pattern_storage->loadByProperties(['library' => $pattern->getLibrary(), 'path' => $pattern->getPath()]);
    /** @var PatternInterface $pattern_loaded */
    $pattern_loaded = end($pattern_cache);
    if (!empty($pattern_loaded)) {
      if ($pattern_loaded->getHash() !== $pattern->getHash()) {
        $pattern->setNewRevision();
        $pattern->isDefaultRevision(TRUE);
      }
      else {
        $pattern = $pattern_loaded;
      }
    }
    $pattern->save();

    // We shouldn't save the preset context, which is from a bug caused by a
    // redundant set in \Drupal\Core\Block\BlockBase::setConfiguration.
    // @see https://www.drupal.org/project/drupal/issues/3154986
    unset($configuration['context']);
    $updated_config = [
      'instance_uuid' => $patternkit_block->uuid(),
      'label_display' => FALSE,
      'pattern' => $pattern->getRevisionId(),
      'patternkit_block_id' => $patternkit_block->id(),
      'presentation_style' => $form_state->getValue('presentation_style'),
      'version' => $form_state->getValue('version'),
    ];
    $this->setConfiguration($updated_config + $configuration);

    // Block cache is not updated unless we specifically ask to clear it.
    $this->blockManager->clearCachedDefinitions();

    parent::blockSubmit($form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($submit = $form_state->getTriggeringElement() ?? FALSE) {
      if (!empty($submit['#value']) && $submit['#value']->getUntranslatedString() === 'Update pattern') {
        $base_pattern = $form_state->get('base_pattern');
        /** @var Pattern $pattern */
        $pattern = $form_state->get('pattern');
        $pattern->setSchema($base_pattern->getSchema());
        $pattern->setTemplate(($base_pattern->getTemplate()));
        $pattern->setVersion($base_pattern->getVersion());
        $form_state->set('pattern', $pattern);
        $form_state->set('schema_updated', TRUE);
        $form_state->setRebuild(TRUE);
      }
    }
    parent::blockValidate($form, $form_state);
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

    // If an instance configuration provides a UUID, use it. If not, we should
    // not cache this item because the uuid will be different each time.
    $instance_id = $configuration['instance_uuid'] ?? $pattern_id;

    // Load module specific config.
    /** @var \Drupal\patternkit\entity\PatternInterface $pattern */
    if (!empty($configuration['pattern'])) {
      $pattern_storage = $this->entityTypeManager->getStorage('patternkit_pattern');
      $pattern = $pattern_storage->loadRevision($configuration['pattern']);
    }
    else {
      $pattern = $this->library->getLibraryAsset($pattern_id);
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

    // Pull the dependencies and configuration.
    $data = $patternkit_block->get('data')->getValue();
    $config = $this->serializer::decode(reset($data)['value']);
    $bubbleable_metadata = new BubbleableMetadata();
    array_walk_recursive($config, function (&$value, $key) use($context, $bubbleable_metadata) {
      $value = $this->token->replace(
        $value,
        $context,
        [],
        $bubbleable_metadata
      );
    });
    $pattern->config = $config;

    // @todo Revisit twig default hard-coding.
    $pattern_plugin = $pattern->getLibraryPluginId();
    $library_plugin_id = !empty($pattern_plugin) ? $pattern_plugin : 'twig';
    /** @var \Drupal\patternkit\PatternLibraryPluginInterface $library_plugin */
    $library_plugin = $this->patternLibraryPluginManager->createInstance($library_plugin_id);
    $elements = $library_plugin->render([$pattern]);

    // @todo Merge attachment JS and dependencies.

    $elements['#attached'] = $config['pkdata']['attachments'] ?? [];
    $bubbleable_metadata->applyTo($elements);

    return $elements;
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

  /**
   * @param bool $prepared
   * @param array $types
   *
   * @return array|bool
   *
   * @see \Drupal\views\Plugin\views\PluginBase
   */
  public function getAvailableTokens($prepared = FALSE, array $types = []) {
    $info = $this->token->getInfo();
    // Site tokens should always be available.
    $types += ['site' => 'site'];
    $available = array_intersect_key($info['tokens'], $types);

    // @todo Remove when #2759967 is resolved.
    // @see https://www.drupal.org/project/token/issues/2759967
    unset($available['node']['layout_builder__layout']);

    // Construct the token string for each token.
    if ($prepared) {
      $prepared = [];
      foreach ($available as $type => $tokens) {
        foreach (array_keys($tokens) as $token) {
          $prepared[$type][] = "[$type:$token]";
        }
      }

      return $prepared;
    }

    return $available;
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @see \Drupal\views\Plugin\views\PluginBase
   */
  public function tokenForm(&$form, FormStateInterface $form_state) {
    $token_items = [];
    $types = [];
    // Layout builder does not provide its context to the plugin constructor.
    // Workaround: we pull Layout Builder's gathered_contexts if present.
    // The block module layouts provides all gathered_contexts as required,
    // so we need to set empty context to '' to display them without errors.
    // @todo Remove after https://www.drupal.org/project/drupal/issues/3154986
    $contexts = [];
    $layout_contexts = $form_state->getTemporaryValue('gathered_contexts');
    foreach ($this->getContextDefinitions() as $name => $definition) {
      $matching_contexts = $this->contextHandler->getMatchingContexts($layout_contexts, $definition);
      $contexts[$name] = array_pop($matching_contexts);
    }
    $this->context = $contexts + $this->context;
    foreach ($this->context as $type => $context) {
      if ($context === NULL) {
        continue;
      }
      $types += ["$type" => "$type"];
      if (!$context->hasContextValue()) {
        $this->context[$type] = new Context($context->getContextDefinition(), '');
      }
    }
    foreach ($this->getAvailableTokens(FALSE, $types) as $type => $tokens) {
      $item = [
        '#markup' => $type,
        'children' => [],
      ];
      foreach ($tokens as $name => $info) {
        $description = $info['description'] ?? '';
        $example = $this->token->replace("[$type:$name]", $this->getContextValues());
        $item['children'][$name] = "[$type:$name]" . ' - ' . $info['name'] . ': '
          . $description
          . " \"" . Xss::filter(substr($example, 0, 255)) ."\"";
      }

      $token_items[$type] = $item;
    }

    $form['global_tokens'] = [
      '#type' => 'details',
      '#title' => $this->t('Available token replacements'),
    ];
    $form['global_tokens']['list'] = [
      '#theme' => 'item_list',
      '#items' => $token_items,
      '#attributes' => [
        'class' => ['global-tokens'],
      ],
    ];
  }
}
