<?php

namespace Drupal\patternkit\Plugin\Block;

use Drupal\Component\Plugin\Context\Context;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock as EntityPatternkitBlock;
use Drupal\patternkit\Event\PatternUpdateEvent;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternkitEnvironmentAwareInterface;
use Drupal\patternkit\PatternkitEnvironmentAwareTrait;
use Drupal\patternkit\PatternkitEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a block that will display a Pattern from a Library with context.
 *
 * @Block(
 *   id = "patternkit_block",
 *   admin_label = @Translation("Patternkit block"),
 *   category = "Patternkit Reusable",
 *   deriver = "Drupal\patternkit\Plugin\Derivative\PatternkitBlock"
 * )
 *
 * @todo Remove JSON Serializer dependencies from this class so the editor
 *   operates off of the pattern library plugin instead.
 */
class PatternkitBlock extends BlockBase implements ContainerFactoryPluginInterface, PatternkitEnvironmentAwareInterface {

  use PatternkitEnvironmentAwareTrait;

  /**
   * The Patternkit Block entity storing content for this plugin instance.
   *
   * @var \Drupal\patternkit\Entity\PatternkitBlock
   */
  protected EntityPatternkitBlock $blockEntity;

  /**
   * The Pattern entity referenced by this plugin instance.
   *
   * @var \Drupal\patternkit\Entity\PatternInterface
   */
  protected PatternInterface $patternEntity;

  /**
   * The Pattern entity as loaded from the file system.
   *
   * This is most commonly used as reference for a comparison to determine if
   * the cached version of the pattern for this block needs to be updated.
   *
   * @var \Drupal\patternkit\Entity\PatternInterface
   */
  protected PatternInterface $basePattern;

  /**
   * Overrides \Drupal\Component\Plugin\ContextAwarePluginBase::__construct().
   *
   * Adds services to allow Patternkit to load and display library patterns.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   Block manager service.
   * @param array $configuration
   *   Default configuration for the block plugin instance.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   *   The ContextHandler allows mapping provided contexts to definitions.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The repository of available contexts for the current route.
   * @param \Drupal\Core\Entity\RevisionableStorageInterface $patternStorage
   *   Entity storage for the patternkit_pattern entity.
   * @param \Drupal\Core\Entity\RevisionableStorageInterface $blockStorage
   *   Entity storage for the patternkit_block entity.
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service.
   * @param \Drupal\patternkit\PatternLibraryPluginManager $patternLibraryPluginManager
   *   Pattern library plugin manager service.
   * @param string $plugin_id
   *   Plugin ID.
   * @param array $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Serialization service.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel for logging messages.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  public function __construct(
    protected BlockManagerInterface $blockManager,
    array $configuration,
    protected ContextHandlerInterface $contextHandler,
    protected ContextRepositoryInterface $contextRepository,
    protected RevisionableStorageInterface $patternStorage,
    protected RevisionableStorageInterface $blockStorage,
    protected PatternRepository $patternRepository,
    protected PatternLibraryPluginManager $patternLibraryPluginManager,
    string $plugin_id,
    array $plugin_definition,
    protected SerializationInterface $serializer,
    protected Token $token,
    protected LanguageManagerInterface $languageManager,
    protected EventDispatcherInterface $eventDispatcher,
    protected LoggerInterface $logger,
  ) {
    // Assign token contexts into the plugin definition.
    // @todo Move this into a lazy-load method to happen when needed instead.
    $this->prepareTokenContexts($plugin_definition);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    $entity_type_manager = $container->get('entity_type.manager');

    $plugin = new static(
      $container->get('plugin.manager.block'),
      $configuration,
      $container->get('context.handler'),
      $container->get('context.repository'),
      $entity_type_manager->getStorage('patternkit_pattern'),
      $entity_type_manager->getStorage('patternkit_block'),
      $container->get('patternkit.pattern.repository'),
      $container->get('plugin.manager.library.pattern'),
      $plugin_id,
      $plugin_definition,
      $container->get('serialization.json'),
      $container->get('token'),
      $container->get('language_manager'),
      $container->get('event_dispatcher'),
      $container->get('logger.channel.patternkit'),
    );

    $plugin->setPatternkitEnvironment($container->get('patternkit.environment'));

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = [
      'presentation_style' => 'html',
      'label_display' => FALSE,
      'version' => NULL,
    ];
    return $configuration + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   - string id => $this->getPluginId(),
   *   - string label => '',
   *   - string provider => $this->pluginDefinition['provider'],
   *   - int label_display => BlockPluginInterface::BLOCK_LABEL_VISIBLE,
   *   - int patternkit_block_id => non-zero id,
   *   - int patternkit_block_rid => non-zero rid,
   *   - string instance_uuid => universally unique id string,
   *   - boolean reusable => TRUE if the patternkit block is reusable,
   *   - int pattern => The revision ID of the pattern entity being used,
   *   - string presentation_style => PatternLibrary plugin-based config string,
   *   - string version => library version used when caching the pattern,
   *   - array context => an array of Context objects provided to the block,
   *   - array fields => the form data configured via the pattern schema,
   *
   * @see baseConfigurationDefaults()
   *
   * @codeCoverageIgnore
   */
  public function getConfiguration(): array { // phpcs:ignore
    return parent::getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
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
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form += parent::blockForm($form, $form_state);

    // Load configuration saved in the form state if there is any. This might
    // happen when the pattern is updated and the form gets rebuilt.
    if ($saved_config = $form_state->get('configuration')) {
      $this->setConfiguration($saved_config);
    }
    $configuration = $this->getConfiguration();

    try {
      // Attempt to load from the form state first in case a pattern entity
      // update set a new version there.
      $pattern = $this->patternEntity = $form_state->get('pattern') ?? $this->getPatternEntity();
    }
    catch (PluginException | UnknownPatternException $exception) {
      $this->logger->error('Unable to load pattern for Patternkit block.');
      return [
        '#markup' => $this->t('Unable to edit a Patternkit block when the pattern fails to load.'),
      ];
    }
    $form_state->set('pattern', $pattern);

    // @todo Add handling for load failures.
    $patternkit_block = $this->getBlockEntity();
    $form_state->set('block_entity', $patternkit_block);
    $configuration['patternkit_block_rid'] = $patternkit_block->getLoadedRevisionId();

    $form_id = $form_state->getBuildInfo()['form_id'];
    $is_block_form = ($form_id === 'block_form');

    // If no block entity has been created yet, default to reusable for blocks
    // being created in the block library, but disable it by default for blocks
    // in layout builder and elsewhere.
    $configuration['reusable'] = $patternkit_block->isNew() ? $is_block_form : $patternkit_block->isReusable();

    // Warn about accidental changes to reusable blocks.
    if (isset($configuration['reusable']) && $configuration['reusable']) {
      $form['messages'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [$this->t('This block is reusable! Any changes made will be applied globally.')],
        ],
      ];
    }

    $form['reusable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reusable'),
      '#default_value' => $configuration['reusable'] ?? FALSE,
      '#description' => $this->t('Set to make the pattern selectable in the block library and usable on other layouts. This option is irreversible.'),
      // Disable changing the value if it's already reusable.
      '#disabled' => $configuration['reusable'] ?? FALSE,
    ];

    // Adds in missing descriptions for the Drupal Core context fields.
    if (isset($form['context_mapping'])) {
      $form['context_mapping_description']['#markup'] = $this->t('Add context tokens to your pattern by selecting a source for the context token mapping.');
    }

    // Handle pattern updates if needed.
    $this->promptForPatternUpdates($form, $form_state);

    // @todo Re-enable the other formats like JSON and webcomponent.
    $form['presentation_style'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Presentation style'),
      '#options'       => [
        'html'         => $this->t('HTML inline'),
      ],
      '#default_value' => $configuration['presentation_style'],
    ];

    $this->tokenForm($form, $form_state);

    // Add version as hidden field.
    $form['version'] = [
      '#type'  => 'hidden',
      '#value' => $configuration['version'] ?? $pattern->getVersion(),
    ];

    $this->editorForm($form, $form_state);

    // Ensure any expanded configuration data is recorded. This may happen when
    // an existing block was loaded that didn't have the revision ID stored or
    // some similar scenario.
    $this->setConfiguration($configuration);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    if ($submit = $form_state->getTriggeringElement() ?? FALSE) {
      if (!empty($submit['#value']) && $submit['#value']->getUntranslatedString() === 'Update pattern') {
        $this->handleBlockFormUpdate($form_state);
      }
    }
    parent::blockValidate($form, $form_state);
  }

  /**
   * Handle operations for a triggered pattern update operation from the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The submitted form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function handleBlockFormUpdate(FormStateInterface $form_state): void {
    /** @var \Drupal\patternkit\Entity\PatternInterface $base_pattern */
    $base_pattern = $form_state->get('base_pattern') ?? $this->getBasePattern();
    /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
    $pattern = $form_state->get('pattern') ?? $this->getPatternEntity();

    $pattern = $this->updatePattern($pattern, $base_pattern);

    $form_state->set('pattern', $pattern);
    $form_state->set('schema_updated', TRUE);
    $form_state->set('configuration', $this->getConfiguration());
    $form_state->setRebuild(TRUE);
  }

  /**
   * Update the pattern in use to match the provided base pattern.
   *
   * If the hash between the provided pattern instances differs, a new pattern
   * revision will be created unless an existing revision is successfully loaded
   * with the targeted hash.
   *
   * During the update process, a pattern update event will also be triggered
   * for other modules to react to as needed.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface|null $pattern
   *   (optional) The active pattern instance.
   * @param \Drupal\patternkit\Entity\PatternInterface|null $base_pattern
   *   (optional) The base pattern to be updated from.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface|null
   *   The updated pattern revision or null if no update was necessary.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   *
   * @see \Drupal\patternkit\Event\PatternUpdateEvent
   */
  public function updatePattern(?PatternInterface $pattern = NULL, ?PatternInterface $base_pattern = NULL): ?PatternInterface {
    $pattern ??= $this->getPatternEntity();
    $base_pattern ??= $this->getBasePattern();

    // If a base pattern is provided we assume it is always for the same pattern
    // asset.
    assert($pattern->getAssetId() === $base_pattern->getAssetId());

    // Stop here if no update is available.
    if ($pattern->getHash() == $base_pattern->getHash()) {
      return NULL;
    }

    // Make a copy of the pattern before applying updates for inclusion in
    // event dispatching.
    $original = clone $pattern;
    $updated_pattern = $this->getUpdatedPatternRevision($pattern, $base_pattern);

    $this->configuration['version'] = $updated_pattern->getVersion();
    $this->configuration['pattern'] = $updated_pattern->getRevisionId();

    // Dispatch an update event to allow other modules to react to pattern
    // updates and alter block content accordingly.
    $event = $this->getUpdateEvent($original, $updated_pattern);
    $this->eventDispatcher->dispatch($event, PatternkitEvents::PATTERN_UPDATE);

    // Capture content updates from the event if changes were made.
    if ($event->isAltered()) {
      $updated_content = $event->getContent();
      $serialized = json_encode($updated_content, JSON_THROW_ON_ERROR);
      $this->updateBlockEntity(['data' => $serialized]);
    }

    return $updated_pattern;
  }

  /**
   * Get a Pattern revision ready for saving with the newest hash.
   *
   * If an existing revision with the same hash is found, this revision is
   * returned. If not, a new revision is created and saved, but it is not set as
   * the default revision until the entire layout is saved at which time the
   * revision will be re-saved as the default revision and cache tags for the
   * pattern entity and the pattern asset name will be invalidated.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The Pattern entity already loaded and being used.
   * @param \Drupal\patternkit\Entity\PatternInterface $base_pattern
   *   The base pattern to update from.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   Either a loaded pattern revision with the new hash if one already existed
   *   or the current pattern instance with all values updated and ready to be
   *   saved.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\patternkit\Entity\Pattern::getCacheTagsToInvalidate()
   * @see self::cachePatternEntity()
   */
  protected function getUpdatedPatternRevision(PatternInterface $pattern, PatternInterface $base_pattern): PatternInterface {
    // Stop early if the hashes already match.
    if ($pattern->getHash() === $base_pattern->getHash()) {
      return $pattern;
    }

    // Attempt to load an existing revision with the newest hash before creating
    // a new one.
    $existing_revisions = $this->patternStorage->getQuery()
      ->accessCheck(FALSE)
      ->latestRevision()
      ->condition('hash', $base_pattern->getHash())
      ->execute();

    // Load the existing revision or save a new one.
    if ($existing_revisions) {
      $pattern = $this->patternStorage->loadRevision(key($existing_revisions));
      assert($pattern instanceof PatternInterface);
    }
    else {
      $pattern->updateFromPattern($base_pattern);
      $pattern->setNewRevision();
      // Don't set as the default revision until the layout is saved to avoid
      // triggering cache clears.
      $pattern->isDefaultRevision(FALSE);
      $pattern->save();
    }

    return $pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    // Create or update the block entity.
    $this->updateBlockEntity($form_state->getValues(), $form_state->get('block_entity'));

    // Ensure any updates to the Pattern revision are captured in configuration.
    $pattern = $form_state->get('pattern') ?? $this->getPatternEntity();
    $this->configuration['pattern'] = $pattern->getRevisionId();
    $this->configuration['version'] = $pattern->getVersion();

    // We shouldn't save the preset context, which is from a bug caused by a
    // redundant set in \Drupal\Core\Block\BlockBase::setConfiguration.
    // @see https://www.drupal.org/project/drupal/issues/3154986
    unset($this->configuration['context']);

    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function build(): array {
    try {
      $pattern = $this->getPatternEntity();
      $block = $this->getBlockEntity();

      $element = [
        '#type' => 'pattern',
        '#pattern' => $pattern,
        '#config' => $block->getData(),
        '#context' => $this->getContextValues(),
      ];
    }
    catch (\JsonException | EntityStorageException $exception) {
      $element = [
        '#type' => 'pattern_error',
        '#pattern' => $pattern ?? $this->getPatternId(),
        // Get the raw value for debugging output.
        '#config' => !empty($block) ? $block->get('data')->value : [],
        '#context' => $this->getContextValues(),
        '#exception' => $exception,
      ];
    }

    // Capture cacheable metadata from dependencies.
    $metadata = new BubbleableMetadata();
    if (!empty($pattern)) {
      $metadata->addCacheableDependency($pattern);
    }
    if (!empty($block)) {
      $metadata->addCacheableDependency($block);
    }
    $metadata->applyTo($element);

    return $element;
  }

  /**
   * Add prompting and support for updating patterns to new versions.
   *
   * @param array $form
   *   The form array to be modified. This array is modified directly.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state for the form being prepared and modified.
   *
   * @see self::blockValidate()
   */
  protected function promptForPatternUpdates(array &$form, FormStateInterface $form_state): void {
    $pattern = $this->getPatternEntity();

    // Display prompts for updating the cached schema if applicable.
    unset($form['schema_desc'], $form['schema_update']);

    // Display success confirmations if an update was already triggered.
    if ($form_state->get('schema_updated')) {
      $success_message = $this->t('Successfully updated the pattern schema and template to the latest version.');

      // Include version numbers in the message for context if available.
      if ($pattern->getVersion()) {
        $success_message = $this->t('Successfully updated the pattern schema and template to @version.',
          ['@version' => $pattern->getVersion()]
        );
      }
      $form['schema_desc'] = ['#markup' => $success_message];
    }

    try {
      // Attempt to update the pattern if the saved hash differs from the base
      // pattern's version.
      if ($this->isPatternUpdateAvailable()) {
        $update_prompt = $this->t('Update pattern schema and template to the latest version.');

        // Include version numbers in the prompt for context if available.
        $base_pattern = $this->getBasePattern();
        if ($pattern->getVersion() && $base_pattern->getVersion()) {
          $update_prompt = $this->t('Update pattern schema and template from @old_version to @version.', [
            '@old_version' => $pattern->getVersion(),
            '@version' => $base_pattern->getVersion(),
          ]);
        }

        $form['schema_desc'] = ['#markup' => $update_prompt];
        $form['schema_update'] = [
          '#ajax' => ['callback' => [$this, 'ajaxUpdatePattern']],
          '#type' => 'submit',
          '#value' => $this->t('Update pattern'),
        ];
        $form_state->set('base_pattern', $base_pattern);
      }
    }
    catch (\Exception $exception) {
      $this->logger->info(
        $this->t('Unable to show update UI for pattern in filesystem: @id',
          ['@id' => $pattern->getAssetId()])
      );
    }
  }

  /**
   * Add token data to the block form.
   *
   * @param array $form
   *   The form being built.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state for the form being built.
   *
   * @see \Drupal\views\Plugin\views\PluginBase
   */
  protected function tokenForm(array &$form, FormStateInterface $form_state): void {
    $token_items = [];
    $types = [];
    // Layout builder does not provide its context to the plugin constructor.
    // Workaround: we pull Layout Builder's gathered_contexts if present.
    // The block module layouts provides all gathered_contexts as required,
    // so we need to set empty context to '' to display them without errors.
    // @todo Remove after https://www.drupal.org/project/drupal/issues/3154986
    $contexts = [];

    $layout_contexts = $form_state->getTemporaryValue('gathered_contexts');
    if (!$layout_contexts) {
      return;
    }
    foreach ($this->getContextDefinitions() as $name => $definition) {
      $matching_contexts = $this->contextHandler->getMatchingContexts($layout_contexts, $definition);
      $match = array_pop($matching_contexts);
      if ($match !== NULL) {
        $contexts[$name] = $match;
      }
    }
    // @phpstan-ignore assign.propertyType
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
        try {
          $example = $this->token->replace("[$type:$name]", $this->getContextValues());
        }
        catch (\Exception $ignored) {
          // Ignore this exception and skip displaying an example for this
          // token.
          $example = '';
        }

        $label = sprintf('[%s:%s] - %s: %s', $type, $name, $info['name'], $info['description'] ?? '');
        if ($example !== '') {
          $label .= ' "' . Xss::filter(substr($example, 0, 255)) . '"';
        }
        $item['children'][$name] = $label;
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

  /**
   * Returns an array of available token replacements.
   *
   * @param bool $prepared
   *   Whether to return the raw token info for each token or an array of
   *   prepared tokens for each type. E.g. "[view:name]".
   * @param array $types
   *   An array of additional token types to return, defaults to 'site' and
   *   'view'.
   *
   * @return array
   *   An array of available token replacement info or tokens, grouped by type.
   *
   * @see \Drupal\views\Plugin\views\PluginBase
   */
  protected function getAvailableTokens(bool $prepared = FALSE, array $types = []): array {
    $info = $this->token->getInfo();
    // Site tokens should always be available.
    $types += ['site' => 'site'];
    $available = array_intersect_key($info['tokens'], $types);

    // @todo Remove when #2759967 is resolved.
    // @see https://www.drupal.org/project/token/issues/2759967
    unset($available['node']['layout_builder__layout']);

    // Construct the token string for each token.
    if ($prepared) {
      $prepared_tokens = [];
      foreach ($available as $type => $tokens) {
        foreach (array_keys($tokens) as $token) {
          $prepared_tokens[$type][] = "[$type:$token]";
        }
      }
      return $prepared_tokens;
    }

    return $available;
  }

  /**
   * Add the editor form for the pattern being used.
   *
   * @param array $form
   *   The form array to be modified. This array is modified directly.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state for the form being prepared and modified.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function editorForm(array &$form, FormStateInterface $form_state): void {
    if ($block_entity = $form_state->get('block_entity')) {
      $data = $block_entity->data->value ?? '{}';
    }

    $pattern = $form_state->get('pattern');
    $form['configuration'] = [
      '#type' => 'jsoneditor_form',
      '#pattern_entity' => $pattern,
      '#data' => $data ?? '{}',
    ];
  }

  /**
   * AJAX response to replace a pattern in a PatternkitBlock form.
   *
   * @param array $form
   *   PatternkitBlock form Drupal render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The relevant Drupal FormState for the form render array.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to update the current pattern based on the form.
   */
  public function ajaxUpdatePattern(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
    return $response;
  }

  /**
   * Save a pattern into the database to cache pattern metadata.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The saved pattern entity revision.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::getUpdatedPatternRevision()
   */
  public function cachePatternEntity(): PatternInterface {
    $pattern = $this->getPatternEntity();

    $shouldSavePattern = FALSE;

    // Load the latest version of the pattern from the database.
    $cached_pattern = $this->getCachedPatternEntity();

    if ($cached_pattern === NULL) {
      // No cached pattern exists, so the prepared pattern should be saved.
      $shouldSavePattern = TRUE;
    }
    else {
      // Test whether the loaded Pattern is newer and the cached version should
      // be updated.
      $shouldUpdatePattern = $cached_pattern->getHash() !== $pattern->getHash();

      if ($shouldUpdatePattern || $pattern->isNew()) {
        $shouldSavePattern = $shouldUpdatePattern;

        // Map changes onto the pattern loaded from the cache for updating.
        $cached_pattern->updateFromPattern($pattern);
        $pattern = $this->patternEntity = $cached_pattern;

        if ($shouldSavePattern) {
          $pattern->setNewRevision();
          $pattern->isDefaultRevision(TRUE);
        }
      }
      // If the pattern was updated during editing, a new revision was saved and
      // referenced, but it was not set as the default revision to avoid
      // invalidating cache tags prematurely. Now that the layout is being
      // saved, we can update the new revision to be the default as the latest
      // version of the pattern.
      elseif (!$pattern->isDefaultRevision() && $pattern->isLatestRevision()) {
        $shouldSavePattern = TRUE;
        $pattern->isDefaultRevision(TRUE);
      }
    }

    if ($shouldSavePattern) {
      $pattern->save();
    }

    // Always set the pattern revision ID to handle scenarios where the cached
    // pattern may have been loaded from the repository and configuration
    // doesn't contain a reference yet.
    $this->setConfigurationValue('pattern', $pattern->getRevisionId());

    return $pattern;
  }

  /**
   * Create or update an existing patternkit block with provided values.
   *
   * This method captures changes submitted for a patternkit block entity and
   * stores them as a serialized value in configuration to be saved later.
   * Attempting to save the entity at this stage results in premature cache
   * invalidations.
   *
   * @param array $values
   *   An array of values to update on the block entity.
   * @param \Drupal\patternkit\Entity\PatternkitBlock|null $block_entity
   *   (Optional) The patternkit block entity to be updated. If one is not
   *   provided, attempts will be made to load it from existing configuration.
   *
   * @return \Drupal\patternkit\Entity\PatternkitBlock
   *   The updated or created patternkit block entity.
   *
   * @see \Drupal\patternkit\PatternkitBlockEntityOperations::handlePreSave()
   */
  protected function updateBlockEntity(array $values, ?EntityPatternkitBlock $block_entity = NULL): EntityPatternkitBlock {
    $block_entity ??= $this->getBlockEntity();
    $fields = array_flip(array_keys($block_entity->getFields()));

    // Assign field values into the block entity.
    foreach ($values as $key => $value) {
      if (isset($fields[$key])) {
        $block_entity->set($key, $value);
      }
      // Map additional keys that may differ from the storage structure.
      else {
        switch ($key) {
          case 'configuration':
            $block_entity->set('data', $value);
            break;

          case 'label':
            $block_entity->set('info', $value);
            break;
        }
      }
    }

    // Assign additional contextual values into the entity.
    $block_entity->set('pattern_id', $this->getPatternId());

    // Serialize the updated block instance to be loaded and saved later.
    $this->configuration['block_serialized'] = serialize($block_entity);

    return $block_entity;
  }

  /**
   * Saves the patternkit_block entity for this plugin.
   *
   * @param bool $new_revision
   *   Whether to create new revision, if the block was modified.
   * @param bool $duplicate_block
   *   Whether to duplicate the "patternkit_block" entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::updateBlockEntity()
   *   Unsaved edits to block entity values may be saved in a serialized value
   *   until the parent entity is saved.
   * @see \Drupal\patternkit\PatternkitBlockEntityOperations::handlePreSave()
   */
  public function saveBlockContent(bool $new_revision = FALSE, bool $duplicate_block = FALSE): void {
    /** @var \Drupal\patternkit\Entity\PatternkitBlock|null $block */
    $block = NULL;

    // Load the unsaved changes if the block was updated.
    // NB. We're not loading with ::getBlockEntity to maintain more clear
    // control over the flow of where the block is loaded from to better manage
    // duplication if needed.
    if (!empty($this->configuration['block_serialized'])) {
      $block = unserialize($this->configuration['block_serialized'], [
        'allowed_classes' => [
          EntityPatternkitBlock::class,
        ],
      ]);
      assert($block instanceof EntityPatternkitBlock);
    }

    if ($duplicate_block) {
      if (empty($block) && !empty($this->configuration['patternkit_block_rid'])) {
        // @todo Determine if translation handling needs to be added here.
        $block = $this->blockStorage->loadRevision($this->configuration['patternkit_block_rid']);
      }
      if ($block instanceof EntityPatternkitBlock) {
        $block = $block->createDuplicate();
      }
    }

    if ($block instanceof EntityPatternkitBlock) {
      // Since the custom block is only set if it was unserialized, the flag
      // will only affect blocks which were modified or serialized originally.
      if ($new_revision) {
        $block->setNewRevision();
      }
      $block->save();
      $this->configuration['patternkit_block_rid'] = $block->getRevisionId();
      $this->configuration['block_serialized'] = NULL;

      // Block cache is not updated unless we specifically ask to clear it.
      if (method_exists($this->blockManager, 'clearCachedDefinitions')) {
        $this->blockManager->clearCachedDefinitions();
      }
    }
  }

  /**
   * Get the pattern asset name for this block.
   *
   * @return string
   *   The pattern asset name.
   */
  public function getPatternId(): string {
    return $this->pluginDefinition['pattern']->getAssetId();
  }

  /**
   * Get the Pattern entity used by this block.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface|null
   *   The Pattern entity supporting this block or NULL if one is not defined.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\patternkit\Exception\UnknownPatternException
   */
  public function getPatternEntity(): ?PatternInterface {
    // Return the loaded pattern entity immediately if it's already available.
    if (isset($this->patternEntity)) {
      return $this->patternEntity;
    }

    $configuration = $this->getConfiguration();

    // Determine whether the option to always load the latest pattern data from
    // the filesystem is enabled and bypass the database if so.
    $shouldBypassDatabase = $this->getEnvironmentFeature(PatternkitEnvironment::FEATURE_BYPASS_DATABASE_CACHE, FALSE);
    if (!$shouldBypassDatabase && !empty($configuration['pattern'])) {
      /** @var \Drupal\patternkit\Entity\PatternInterface|null $pattern */
      $pattern = $this->patternStorage->loadRevision($configuration['pattern']);

      if ($pattern === NULL) {
        $this->logger->warning('Unable to load pattern revision %revision_id for block plugin %plugin_id.', [
          '%revision_id' => $configuration['pattern'],
          '%plugin_id' => $this->getPluginId(),
        ]);
      }
    }

    // Load the pattern entity directly if a revision could not be loaded.
    if (empty($pattern)) {
      $pattern = $this->patternRepository->getPattern($this->getPatternId(), TRUE);
    }

    return $this->patternEntity = $pattern;
  }

  /**
   * Load the correct patternkit block entity revision.
   *
   * @return \Drupal\patternkit\Entity\PatternkitBlock
   *   The loaded patternkit block entity revision. If one is not already
   *   created, a new one will be stubbed out and returned.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Throws exception if a referenced block entity could not be loaded.
   */
  public function getBlockEntity(): EntityPatternkitBlock {
    // Return the loaded block entity immediately if it's already available.
    if (isset($this->blockEntity)) {
      return $this->blockEntity;
    }

    $configuration = $this->getConfiguration();

    // Load the serialized version first if an unsaved edit has stored changes
    // in that value.
    if (isset($configuration['block_serialized'])) {
      // Return this one immediately since it's the working copy.
      return $this->blockEntity = unserialize($this->configuration['block_serialized'], [
        'allowed_classes' => [
          EntityPatternkitBlock::class,
        ],
      ]);
    }
    // Load the defined revision if available.
    elseif (isset($configuration['patternkit_block_rid'])
      && (int) $configuration['patternkit_block_rid'] > 0) {
      $block = $this->blockStorage->loadRevision($configuration['patternkit_block_rid']);
    }
    // Fall back to loading the current default revision of the referenced
    // pattern entity if a revision was not specified.
    // @todo Remove storage and loading by block ID.
    elseif (isset($configuration['patternkit_block_id'])
      && (int) $configuration['patternkit_block_id'] > 0) {
      $block = $this->blockStorage->load($configuration['patternkit_block_id']);
    }
    // Stub out a new entity if one hasn't been created yet.
    else {
      $block = $this->blockStorage->create($this->defaultConfiguration());
    }

    // Fail if we get to this point and still don't have a block loaded.
    if ($block === NULL) {
      throw new EntityStorageException('Unable to load Patternkit Block entity.');
    }

    // Ensure the correct translation is loaded if a language code is defined.
    if (
      !empty($configuration['langcode']) &&
      $block instanceof TranslatableInterface &&
      $block->hasTranslation(($configuration['langcode']))
    ) {
      $block = $block->getTranslation($configuration['langcode']);
    }

    assert($block instanceof EntityPatternkitBlock);
    return $this->blockEntity = $block;
  }

  /**
   * Get the base pattern loaded from the file system for this plugin.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The loaded base pattern entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBasePattern(): PatternInterface {
    // Return the loaded pattern entity immediately if it's already available.
    if (isset($this->basePattern)) {
      return $this->basePattern;
    }

    $pattern = $this->getPatternEntity();
    return $this->patternRepository->getPattern($pattern->getAssetId(), TRUE);
  }

  /**
   * Load the database cached version of the pattern for updating.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface|null
   *   The pattern entity loaded from the database or NULL if one was not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCachedPatternEntity(): ?PatternInterface {
    $configuration = $this->getConfiguration();

    // Attempt to load the revision referenced in configuration first.
    if (isset($configuration['pattern'])) {
      $cached_pattern = $this->patternStorage->loadRevision($configuration['pattern']);
    }
    // Attempt to load an existing database version of the same pattern.
    else {
      $pattern = $this->getPatternEntity();

      $pattern_cache = $this->patternStorage->loadByProperties([
        'library' => $pattern->getLibrary(),
        'path' => $pattern->getPath(),
      ]);
      $cached_pattern = $pattern_cache ? end($pattern_cache) : NULL;
    }

    // @phpstan-ignore return.type
    return $cached_pattern;
  }

  /**
   * Load the patternkit library plugin for a given pattern.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern entity to load the library plugin for.
   *
   * @return \Drupal\patternkit\PatternLibraryPluginInterface
   *   The loaded library plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getLibraryPlugin(PatternInterface $pattern): PatternLibraryPluginInterface {
    $library_id = $pattern->getLibraryPluginId();
    /** @var \Drupal\patternkit\PatternLibraryPluginInterface $plugin */
    $plugin = $this->patternLibraryPluginManager->createInstance($library_id);

    return $plugin;
  }

  /**
   * Test whether an update to the pattern data being used is available.
   *
   * If the 'bypass_database_cache' service parameter is enabled, prompts to
   * update pattern entities will be ignored and not shown.
   *
   * @return bool
   *   TRUE if an update is available. FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\patternkit\Exception\UnknownPatternException
   */
  protected function isPatternUpdateAvailable(): bool {
    // Don't prompt for pattern updates if we're not loading from the database.
    $shouldBypassDatabase = $this->getEnvironmentFeature('bypass_database_cache', FALSE);
    if ($shouldBypassDatabase) {
      return FALSE;
    }

    $updateIsAvailable = FALSE;

    $pattern = $this->getPatternEntity();
    $base_pattern = $this->getBasePattern();

    // Attempt to update the pattern if the saved hash differs from the base
    // pattern's version.
    if ($base_pattern->getHash() !== $pattern->getHash()) {
      $updateIsAvailable = TRUE;
    }

    return $updateIsAvailable;
  }

  /**
   * Assign token contexts into the plugin definition.
   *
   * Assigns contexts based on the convention that token base names are
   * identical to context name root keys.
   *
   * @param array $plugin_definition
   *   The existing plugin definition to be updated. The context values of this
   *   parameter are modified directly.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *
   * @see \Drupal\layout_builder\Form\ConfigureBlockFormBase::doBuildForm()
   *
   * @todo Find a more robust way to map these via token info.
   * @todo Handle multiple contexts from the same context root key.
   */
  protected function prepareTokenContexts(array &$plugin_definition): void {
    $tokens = $this->token->getInfo()['tokens'];
    foreach ($this->contextRepository->getAvailableContexts() as $name => $context) {
      $id = trim(substr($name, 0, strpos($name, '.')), '@');
      if (array_key_exists($id, $tokens)) {
        $plugin_definition['context_definitions'][$id] = $context
          ->getContextDefinition()
          ->setRequired(FALSE);
        $this->setContext($id, $context);
      }
    }
  }

  /**
   * A wrapper function for decoding serialized JSON content.
   *
   * @param string $value
   *   The encoded JSON string to be decoded.
   *
   * @return mixed
   *   The decoded value of the JSON string.
   *
   * @codeCoverageIgnore
   */
  protected function decode(string $value) {
    return $this->serializer::decode($value);
  }

  /**
   * Get a Pattern update event for dispatching.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $original
   *   The pattern entity being updated.
   * @param \Drupal\patternkit\Entity\PatternInterface $updated_pattern
   *   The updated pattern entity.
   *
   * @return \Drupal\patternkit\Event\PatternUpdateEvent
   *   A pattern update event ready for dispatching.
   *
   * @codeCoverageIgnore
   */
  protected function getUpdateEvent(PatternInterface $original, PatternInterface $updated_pattern): PatternUpdateEvent {
    return new PatternUpdateEvent($this, $original, $updated_pattern);
  }

}
