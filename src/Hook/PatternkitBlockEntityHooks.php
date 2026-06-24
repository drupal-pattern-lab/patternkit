<?php

declare(strict_types=1);

namespace Drupal\patternkit\Hook;

use Drupal\block\BlockInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Utility\Token;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\patternkit\Entity\PatternkitBlock as PatternkitBlockEntity;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\patternkit\Utility\PatternValidationHelper;
use Drupal\patternkit\Validation\ValidityStampPendingStore;
use Drupal\patternkit\Validation\ValidityStampResult;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events related to Patternkit Blocks.
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 *
 * @todo Implement usage tracking as seen in \Drupal\layout_builder\InlineBlockEntityOperations.
 * @todo Remove unused block entities on save.
 */
class PatternkitBlockEntityHooks implements ContainerInjectionInterface, LoggerAwareInterface {

  use LayoutEntityHelperTrait;
  use LoggerAwareTrait;

  /**
   * Entity storage for patternkit_pattern entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * Entity storage for patternkit_block entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $blockStorage;

  /**
   * Local tracking for whether the layout_builder module is enabled.
   *
   * @var bool
   */
  protected bool $layoutBuilderEnabled;

  /**
   * Constructs a new PatternkitBlockEntityHooks object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\patternkit\Validation\ValidityStampWriterInterface $stampWriter
   *   The validity stamp writer service.
   * @param \Drupal\patternkit\Utility\PatternValidationHelper $validationHelper
   *   The pattern validation helper service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\patternkit\Validation\ValidityStampPendingStore $pendingStore
   *   Request-scoped store for deferred validity stamp writes.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface|null $sectionStorageManager
   *   The section storage manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected ValidityStampWriterInterface $stampWriter,
    protected PatternValidationHelper $validationHelper,
    protected Token $token,
    protected ConfigFactoryInterface $configFactory,
    protected ValidityStampPendingStore $pendingStore,
    #[Autowire(service: 'plugin.manager.layout_builder.section_storage')]
    ?SectionStorageManagerInterface $sectionStorageManager = NULL,
  ) {
    $this->patternStorage = $entityTypeManager->getStorage('patternkit_pattern');
    $this->blockStorage = $entityTypeManager->getStorage('patternkit_block');
    // Only assign sectionStorageManager if provided (Layout Builder is
    // installed). The property is defined by LayoutEntityHelperTrait and is
    // non-nullable, so we must only assign when the service is available.
    if ($sectionStorageManager !== NULL) {
      $this->sectionStorageManager = $sectionStorageManager;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('patternkit.validity.stamp_writer'),
      $container->get('patternkit.helper.validation'),
      $container->get('token'),
      $container->get('config.factory'),
      $container->get('patternkit.validity.pending_store'),
      $container->get('plugin.manager.layout_builder.section_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
    );
    $instance->setLogger($container->get('logger.channel.patternkit'));
    return $instance;
  }

  /**
   * Handles saving a parent entity and ensures serialized settings are saved.
   *
   * During the Patternkit block editing process, updates to a block's
   * configuration may not be fully saved, and instead may be recorded into a
   * "block_serialized" value for storage until the parent entity is ready for
   * these updates. These operations will ensure these serialized settings are
   * properly saved into the block configuration and related components.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::updateBlockEntity()
   * @see \patternkit_entity_presave()
   * @see \Drupal\layout_builder\InlineBlockEntityOperations::handlePreSave()
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::blockSubmit()
   *
   * @todo Add removal handling for deleted blocks.
   * @todo Add usage tracking for block components and patterns.
   */
  #[Hook('entity_presave')]
  public function handlePreSave(EntityInterface $entity): void {
    if ($entity instanceof BlockInterface) {
      $this->handlePreSaveForBlocks($entity);
    }
    elseif ($this->isLayoutBuilderEnabled() && $this->isLayoutCompatibleEntity($entity)) {
      $this->handlePreSaveForLayouts($entity);
    }
  }

  /**
   * Flushes deferred validity stamps after a layout-compatible entity saves.
   *
   * Layout Builder block components defer stamp writes during presave so stamps
   * are persisted only after the parent entity save completes successfully.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The saved entity.
   */
  #[Hook('entity_insert')]
  #[Hook('entity_update')]
  public function handlePostSave(EntityInterface $entity): void {
    if ($this->isLayoutBuilderEnabled() && $this->isLayoutCompatibleEntity($entity)) {
      $this->flushPendingStamps($entity);
    }
  }

  /**
   * Deletes validity stamps for Patternkit blocks when a layout entity is gone.
   *
   * Drupal core does not synchronously delete patternkit_block entities when
   * their parent node is deleted. Inline block cleanup is deferred to cron
   * via inline_block_usage. Walking the layout sections at predelete time is
   * the only synchronous path to remove stamps before the entity is gone.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being deleted.
   */
  #[Hook('entity_predelete')]
  public function handlePreDelete(EntityInterface $entity): void {
    if (!$this->isLayoutBuilderEnabled()) {
      return;
    }
    $sectionStorage = $this->getSectionStorageForEntity($entity);
    if ($sectionStorage === NULL) {
      return;
    }
    $blockStorage = $this->blockStorage;
    assert($blockStorage instanceof RevisionableStorageInterface);
    foreach ($this->getPatternBlockComponents($sectionStorage->getSections()) as $component) {
      /** @var \Drupal\patternkit\Plugin\Block\PatternkitBlock $plugin */
      $plugin = $component->getPlugin();
      $configuration = $plugin->getConfiguration();
      $blockRevisionId = (int) ($configuration['patternkit_block_rid'] ?? 0);
      if ($blockRevisionId <= 0) {
        continue;
      }
      /** @var \Drupal\patternkit\Entity\PatternkitBlock|null $blockEntity */
      $blockEntity = $blockStorage->loadRevision($blockRevisionId);
      if ($blockEntity === NULL) {
        continue;
      }
      // NULL langcode removes all translation rows for this block entity.
      $this->stampWriter->deleteStamp((int) $blockEntity->id(), NULL);
    }
  }

  /**
   * Handle pre-save operations for config block entities.
   *
   * @param \Drupal\block\BlockInterface $block
   *   The block entity being saved.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function handlePreSaveForBlocks(BlockInterface $block): void {
    $plugin = $block->getPlugin();

    // Stop here if the block being saved isn't a Patternkit block.
    if (!($plugin instanceof PatternkitBlock)) {
      return;
    }

    $this->saveBlockComponent($block, TRUE, FALSE);
  }

  /**
   * Handle pre-save operations for a layout-compatible entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The layout-compatible entity being saved.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function handlePreSaveForLayouts(EntityInterface $entity): void {
    $duplicate_blocks = FALSE;
    $parentLangcode = $entity->language()->getId();
    $skipStamps = $this->shouldSkipStamps($entity);

    $storage = $this->getSectionStorageForEntity($entity);
    if ($sections = $storage->getSections()) {
      if ($this->originalEntityUsesDefaultStorage($entity)) {
        // This is a new override from a default and the blocks need to be
        // duplicated.
        $duplicate_blocks = TRUE;
      }
      // Since multiple parent entity revisions may reference common block
      // revisions, when a block is modified, it must always result in the
      // creation of a new block revision.
      $new_revision = $entity instanceof RevisionableInterface;
      foreach ($this->getPatternBlockComponents($sections) as $component) {
        $this->saveBlockComponent($component, $new_revision, $duplicate_blocks, $skipStamps, $parentLangcode);
      }
    }

    // @todo Implement removal of unused entities.
    // $this->removeUnusedForEntityOnSave($entity);
  }

  /**
   * Determines whether stamp writes and enforcement should be skipped.
   *
   * Stamps are only meaningful for published, default-revision content.
   * Two cases require skipping:
   *
   * 1. Non-default revisions (e.g. draft revisions of already-published nodes):
   *    saveBlockContent() always creates the new patternkit_block revision as
   *    the default revision, so the block-level isDefaultRevision() guard in
   *    saveBlockComponent() does not catch the parent-entity draft case.
   *
   * 2. Initial entity create with Content Moderation in Draft state: the first
   *    revision is the default revision even though the content is unpublished,
   *    so isDefaultRevision() alone does not catch this case.
   *
   * Hook ordering note: the isPublished() check reads from the in-flight
   * entity object. This is correct because Content Moderation's
   * hook_entity_presave (ContentModerationHooks::entityPresave) calls
   * setUnpublished() before this hook fires. Hook execution order follows
   * the module dependency graph weight: content_moderation and patternkit
   * have no mutual dependency, so they receive equal graph weight and are
   * ordered alphabetically — content_moderation fires before patternkit.
   * If module weight is changed via hook_module_implements_alter() or a
   * #[ReorderHook] attribute (the Drupal 11 equivalent, used by the Workspaces
   * module), this assumption must be revisited.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity being presaved.
   *
   * @return bool
   *   TRUE if stamp writes and enforcement should be skipped.
   */
  private function shouldSkipStamps(EntityInterface $entity): bool {
    $isDraftRevision = $entity instanceof RevisionableInterface
      && !$entity->isDefaultRevision();
    $isUnpublished = $entity instanceof EntityPublishedInterface
      && !$entity->isPublished();
    return $isDraftRevision || $isUnpublished;
  }

  /**
   * Gets components that have Patternkit Block plugins.
   *
   * @param \Drupal\layout_builder\Section[] $sections
   *   The layout sections.
   *
   * @return \Drupal\layout_builder\SectionComponent[]
   *   The components that contain Patternkit Block plugins.
   */
  protected function getPatternBlockComponents(array $sections): array {
    $block_components = [];
    foreach ($sections as $section) {
      foreach ($section->getComponents() as $component) {
        $plugin = $component->getPlugin();
        if ($plugin instanceof DerivativeInspectionInterface && $plugin->getBaseId() === 'patternkit_block') {
          $block_components[] = $component;
        }
      }
    }
    return $block_components;
  }

  /**
   * Save updates for a block instance or inline block.
   *
   * @param \Drupal\block\BlockInterface|\Drupal\layout_builder\SectionComponent $component
   *   The block config entity or layout section component being saved.
   * @param bool $new_revision
   *   Whether a new revision of the block should be created when modified.
   * @param bool $duplicate_blocks
   *   Whether the blocks should be duplicated.
   * @param bool $skipStamps
   *   When TRUE, skips validation, enforcement, and stamp write. Set to TRUE
   *   by handlePreSaveForLayouts() when the parent entity is a non-default
   *   (draft) revision so invalid drafts are not blocked and no stamp is
   *   written until the revision is promoted to published/default.
   * @param string|null $parentLangcode
   *   The active translation langcode of the parent entity. When provided,
   *   this overrides the block entity's own langcode for stamp keying so
   *   translated layout saves produce correctly-keyed stamp rows.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function saveBlockComponent(BlockInterface|SectionComponent $component, bool $new_revision, bool $duplicate_blocks, bool $skipStamps = FALSE, ?string $parentLangcode = NULL): void {
    /** @var \Drupal\patternkit\Plugin\Block\PatternkitBlock $plugin */
    $plugin = $component->getPlugin();
    $plugin->cachePatternEntity();
    $plugin->saveBlockContent($new_revision, $duplicate_blocks);
    $post_save_configuration = $plugin->getConfiguration();
    // @todo Record in usage tracking.
    if (!$skipStamps) {
      // Resolve the validation decision independently from stamp persistence.
      // Track A: validate and enforce before stamp write so enforcement cannot
      // be bypassed by a stamp-write failure.
      [$validationResult, $blockEntity] = $this->resolveValidationState($component, $post_save_configuration, $parentLangcode);
      if ($validationResult !== NULL) {
        $enforce = $this->configFactory->get('patternkit.settings')->get('render_validation_on_save') ?? TRUE;
        $this->enforceValidationPolicy(
          $validationResult->isValid,
          $validationResult->blockEntityId,
          $validationResult->langcode,
          $enforce,
        );
        // Track B: stamp write is observational-only; failures do not affect
        // the enforcement decision already made above.
        $this->writeValidityStamp($component, $validationResult, $blockEntity);
      }
    }

    if ($component instanceof BlockInterface) {
      $component->set('settings', $post_save_configuration);
    }
    else {
      $component->setConfiguration($post_save_configuration);
    }
  }

  /**
   * Resolves the block entity context and validation result for a component.
   *
   * Loads the block revision, validates content against the pattern schema, and
   * returns both a result object and the loaded entity. Returns [NULL, NULL]
   * when the block context is not eligible for stamping (missing revision ID,
   * non-default revision, or missing pattern revision ID). The entity is
   * returned alongside the result so callers can pass it directly to
   * writeValidityStamp() without a redundant second loadRevision() call.
   *
   * @param \Drupal\block\BlockInterface|\Drupal\layout_builder\SectionComponent $component
   *   The saved component.
   * @param array<string, mixed> $configuration
   *   The post-save plugin configuration.
   * @param string|null $parentLangcode
   *   The active translation langcode of the parent entity. Overrides the
   *   block entity's own langcode for stamp keying. Falls back to the block
   *   entity langcode when NULL (config block path, where no parent
   *   translation context is available).
   *
   * @return array{0: \Drupal\patternkit\Validation\ValidityStampResult|null, 1: \Drupal\patternkit\Entity\PatternkitBlock|null}
   *   Tuple of [result, blockEntity]; both NULL when eligibility checks fail.
   */
  protected function resolveValidationState(BlockInterface|SectionComponent $component, array $configuration, ?string $parentLangcode = NULL): array {
    $blockRevisionId = (int) ($configuration['patternkit_block_rid'] ?? 0);
    if ($blockRevisionId <= 0) {
      return [NULL, NULL];
    }

    $blockStorage = $this->blockStorage;
    assert($blockStorage instanceof RevisionableStorageInterface);
    /** @var \Drupal\patternkit\Entity\PatternkitBlock|null $blockEntity */
    $blockEntity = $blockStorage->loadRevision($blockRevisionId);
    if ($blockEntity === NULL || !$blockEntity->isDefaultRevision()) {
      return [NULL, NULL];
    }

    $blockEntityId = (int) $blockEntity->id();
    // Use the parent entity's active translation langcode when available so
    // that translated layout saves produce stamp rows keyed to the correct
    // langcode. patternkit_block entities carry the default language langcode
    // regardless of which translation is being saved, so deriving langcode
    // from the block entity produces wrong-langcode rows in all multi-language
    // paths.
    $langcode = $parentLangcode ?? $blockEntity->language()->getId();
    $patternRevisionId = (int) ($configuration['pattern'] ?? 0);

    // cachePatternEntity() always sets configuration['pattern'] before
    // saveBlockComponent() reads the post-save config, so a missing or zero
    // pattern revision ID on a live default-revision block is unexpected.
    if ($patternRevisionId <= 0) {
      $this->logger?->warning(
        'Cannot resolve validation state for block @id (@langcode): pattern revision ID missing from configuration.',
        ['@id' => $blockEntityId, '@langcode' => $langcode],
      );
      return [NULL, NULL];
    }

    $isValid = $this->isBlockValid($patternRevisionId, $blockEntity);
    if (!$isValid) {
      $this->logger?->debug(
        'Validity stamp is_valid=0 for block @id (@langcode): @messages',
        [
          '@id' => $blockEntityId,
          '@langcode' => $langcode,
          '@messages' => implode('; ', array_map(
            fn($e) => $e->getMessage(),
            $this->validationHelper->getExceptions(),
          )),
        ],
      );
    }

    return [
      new ValidityStampResult(
        $isValid,
        $blockEntityId,
        $langcode,
        $blockRevisionId,
        $patternRevisionId,
      ),
      $blockEntity,
    ];
  }

  /**
   * Writes or defers a validity stamp for a saved patternkit block component.
   *
   * SectionComponent saves defer stamp persistence to the post-save hook
   * boundary via the pending store. Config BlockInterface saves write inline
   * because no parent entity post-save hook applies.
   *
   * Stamp writes are observational-only. Failures are logged but do not affect
   * save blocking. Enforcement decisions are made before this is called.
   *
   * @param \Drupal\block\BlockInterface|\Drupal\layout_builder\SectionComponent $component
   *   The saved component.
   * @param \Drupal\patternkit\Validation\ValidityStampResult $validationResult
   *   The pre-computed validation result from resolveValidationState().
   * @param \Drupal\patternkit\Entity\PatternkitBlock|null $blockEntity
   *   The already-loaded block entity from resolveValidationState(), used for
   *   token scanning to avoid a redundant loadRevision() call.
   */
  protected function writeValidityStamp(BlockInterface|SectionComponent $component, ValidityStampResult $validationResult, ?PatternkitBlockEntity $blockEntity = NULL): void {
    $componentUuid = $component instanceof SectionComponent
      ? $component->getUuid()
      : NULL;
    $hasTokens = $blockEntity instanceof PatternkitBlockEntity
      ? $this->blockHasTokens($blockEntity)
      : FALSE;

    if ($component instanceof SectionComponent) {
      $this->pendingStore->add(
        $validationResult->blockEntityId,
        $validationResult->langcode,
        $validationResult->blockRevisionId,
        $validationResult->patternRevisionId,
        $validationResult->isValid,
        $hasTokens,
        ValidityStampWriterInterface::STAMP_SOURCE_SAVE,
        $componentUuid,
      );
      return;
    }

    try {
      $this->stampWriter->writeStamp(
        $validationResult->blockEntityId,
        $validationResult->langcode,
        $validationResult->blockRevisionId,
        $validationResult->patternRevisionId,
        $validationResult->isValid,
        $hasTokens,
        ValidityStampWriterInterface::STAMP_SOURCE_SAVE,
        $componentUuid,
      );
    }
    catch (\Throwable $e) {
      // Stamp writes are observational; do not abort the entity save.
      $this->logger?->error(
        'Failed to write validity stamp for block @id (@langcode): @message',
        [
          '@id' => $validationResult->blockEntityId,
          '@langcode' => $validationResult->langcode,
          '@message' => $e->getMessage(),
        ]
      );
    }
  }

  /**
   * Flushes all pending validity stamp entries for the current request.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity that triggered the flush.
   */
  protected function flushPendingStamps(EntityInterface $entity): void {
    foreach ($this->pendingStore->flushForEntity($entity) as $entry) {
      $this->writeValidityStampFromEntry($entry);
    }
  }

  /**
   * Writes a validity stamp from a pending store entry.
   *
   * @param array<string, mixed> $entry
   *   Pending stamp entry from ValidityStampPendingStore.
   */
  protected function writeValidityStampFromEntry(array $entry): void {
    try {
      $this->stampWriter->writeStamp(
        $entry['blockEntityId'],
        $entry['langcode'],
        $entry['blockRevisionId'],
        $entry['patternRevisionId'],
        $entry['isValid'],
        $entry['hasTokens'],
        $entry['stampSource'],
        $entry['componentUuid'],
      );
    }
    catch (\Throwable $e) {
      $this->logger?->error(
        'Failed to write validity stamp for block @id (@langcode): @message',
        [
          '@id' => $entry['blockEntityId'],
          '@langcode' => $entry['langcode'],
          '@message' => $e->getMessage(),
        ]
      );
    }
  }

  /**
   * Enforces the render_validation_on_save policy before stamp write.
   *
   * Called from saveBlockComponent() after resolveValidationState() and before
   * writeValidityStamp(), so enforcement cannot be bypassed by a stamp failure.
   *
   * @param bool $isValid
   *   Whether the block content passed schema validation.
   * @param int $blockEntityId
   *   The patternkit_block entity ID.
   * @param string $langcode
   *   The block language code.
   * @param bool|null $enforce
   *   Whether save-time enforcement is enabled. When NULL, read from config.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When enforcement is enabled and validation failed.
   */
  protected function enforceValidationPolicy(bool $isValid, int $blockEntityId, string $langcode, ?bool $enforce = NULL): void {
    if ($isValid) {
      return;
    }

    if ($enforce === NULL) {
      $enforce = $this->configFactory->get('patternkit.settings')->get('render_validation_on_save') ?? TRUE;
    }
    if (!$enforce) {
      return;
    }

    $this->logger?->warning(
      'Blocked save of block @id (@langcode): content failed schema validation.',
      ['@id' => $blockEntityId, '@langcode' => $langcode],
    );
    throw new EntityStorageException(sprintf(
      'Pattern content failed validation and could not be saved for block %d (%s). Please fix invalid fields and retry. If a temporary bypass is needed, contact a site administrator.',
      $blockEntityId,
      $langcode,
    ));
  }

  /**
   * Validates block content against the pinned pattern schema.
   *
   * @param int $patternRevisionId
   *   The pinned pattern revision ID.
   * @param \Drupal\patternkit\Entity\PatternkitBlock $blockEntity
   *   The saved block entity revision to validate.
   *
   * @return bool
   *   TRUE if the content validates; FALSE if there are schema exceptions.
   */
  protected function isBlockValid(int $patternRevisionId, PatternkitBlockEntity $blockEntity): bool {
    if ($patternRevisionId <= 0) {
      return FALSE;
    }
    $patternStorage = $this->patternStorage;
    assert($patternStorage instanceof RevisionableStorageInterface);
    /** @var \Drupal\patternkit\Entity\PatternInterface|null $pattern */
    $pattern = $patternStorage->loadRevision($patternRevisionId);
    if ($pattern === NULL) {
      return FALSE;
    }
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($pattern, $blockEntity->getData());
    return !$this->validationHelper->hasExceptions();
  }

  /**
   * Scans block entity data for token patterns.
   *
   * @param \Drupal\patternkit\Entity\PatternkitBlock $blockEntity
   *   The saved block entity revision to scan.
   *
   * @return bool
   *   TRUE if any token patterns are found in the data.
   */
  protected function blockHasTokens(PatternkitBlockEntity $blockEntity): bool {
    // Use the raw JSON string via ->value rather than getData() + re-encoding
    // to avoid a decode/re-encode round-trip. Token::scan() operates on strings
    // and [type:name] placeholders appear literally inside JSON string values.
    $dataString = $blockEntity->get('data')->value ?? '';
    $tokens = $this->token->scan($dataString);
    return !empty($tokens);
  }

  /**
   * Set the section storage manager service to be used.
   *
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $sectionStorageManager
   *   The section storage manager service to be used.
   *
   * @internal
   * @codeCoverageIgnore
   */
  public function setSectionStorageManager(SectionStorageManagerInterface $sectionStorageManager): void {
    $this->sectionStorageManager = $sectionStorageManager;
  }

  /**
   * Test whether the layout_builder module is enabled.
   *
   * @return bool
   *   True if the layout_builder module is enabled, false otherwise.
   */
  protected function isLayoutBuilderEnabled(): bool {
    if (!isset($this->layoutBuilderEnabled)) {
      $this->layoutBuilderEnabled = $this->moduleHandler->moduleExists('layout_builder');
    }

    return $this->layoutBuilderEnabled;
  }

}
