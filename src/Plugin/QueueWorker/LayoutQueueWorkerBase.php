<?php

namespace Drupal\patternkit\Plugin\QueueWorker;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Error;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\patternkit\LayoutHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base queue worker plugin for running queued Patternkit layout processes.
 *
 * A base worker plugin implementation to consume items from a queue and process
 * referenced layouts.
 */
abstract class LayoutQueueWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface, LayoutQueueWorkerInterface {

  use LayoutEntityHelperTrait;
  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The identifier for this queue worker's queue.
   */
  public const QUEUE_ID = 'patternkit_layout_updates';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The layout helper service.
   *
   * @var \Drupal\patternkit\LayoutHelper
   */
  protected LayoutHelper $layoutHelper;

  /**
   * The layout temporary storage repository service.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected LayoutTempstoreRepositoryInterface $layoutTempstoreRepository;

  /**
   * Batch context for sharing update progress.
   *
   * It may be an array, or implementing \ArrayObject in the case of Drush.
   *
   * @var array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string}
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    LayoutHelper $layout_helper,
    SectionStorageManagerInterface $section_storage_manager,
    LayoutTempstoreRepositoryInterface $layout_tempstore_repository,
    LoggerChannelInterface $logger,
    TranslationInterface $string_translation,
    MessengerInterface $messenger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->layoutHelper = $layout_helper;
    $this->sectionStorageManager = $section_storage_manager;
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
    $this->messenger = $messenger;

    $this->context = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('patternkit.helper.layout'),
      $container->get('plugin.manager.layout_builder.section_storage'),
      $container->get('layout_builder.tempstore_repository'),
      $container->get('logger.channel.patternkit_batch'),
      $container->get('string_translation'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    [
      'display_id' => $display_id,
      'entity_type' => $entity_type_id,
    ] = $data;

    // Prepare a result summary for the update process when running in batch
    // processing mode.
    $result = [
      'display_id' => $display_id,
      'blocks' => 0,
      'entities' => 0,
      'errors' => 0,
    ];

    $this->messenger()->addStatus($this->t('Updating patterns in layout ID "@id".', [
      '@id' => $data['display_id'],
    ]));

    try {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      // Load the display and entity, if available, for updating.
      if (isset($data['entity_id'])) {
        if ($entity = $this->getEntity($entity_type_id, $data['entity_id'], $data['entity_revision_id'] ?? NULL)) {
          assert($entity instanceof ContentEntityInterface);

          // Load the correct translation if one was specified.
          if (isset($data['language'])) {
            $langcode = $data['language'];
            $entity = $entity->getTranslation($langcode);
          }

          if ($section_storage = $this->getSectionStorageForEntity($entity)) {
            $this->processEntityLayout($section_storage, $entity, $data, $result);

            // Process any drafted layouts that may exist.
            $this->processTempstoreOverrideLayout($section_storage, $display_id, $data, $entity, $result);
          }
          else {
            $result['errors'] = 1;

            $load_revisions = isset($data['entity_revision_id']);
            $this->messenger()->addError($this->t('Failed to load layout for entity @type @identifier "@id".', [
              '@type' => $entity_type->getLabel(),
              '@identifier' => $load_revisions ? 'revision' : 'ID',
              '@id' => $load_revisions ? $data['entity_revision_id'] : $data['entity_id'],
            ]));
          }
        }
        else {
          $result['errors'] = 1;

          $load_revisions = isset($data['entity_revision_id']);
          $this->messenger()->addError($this->t('Failed to load entity @type @identifier "@id".', [
            '@type' => $entity_type->getLabel(),
            '@identifier' => $load_revisions ? 'revision' : 'ID',
            '@id' => $load_revisions ? $data['entity_revision_id'] : $data['entity_id'],
          ]));
        }
      }
      else {
        $display_storage = $this->entityTypeManager->getStorage('entity_view_display');
        $display = $display_storage->load($display_id);
        if ($section_storage = $this->getSectionStorageForEntity($display)) {
          $this->processDefaultLayout($section_storage, $data, $result);

          // Process any drafted layouts that may exist.
          $this->processTempstoreDefaultLayout($section_storage, $display_id, $data, $result);
        }
        else {
          $this->messenger()
            ->addError($this->t('Failed to load display for display ID "@id".',
              ['@id' => $display_id]));
          $result['errors'] = 1;
        }
      }
    }
    catch (\Exception $e) {
      $this->messenger()
        ->addError($this->t('Encountered error while processing display ID "@id".',
          ['@id' => $display_id]));
      $variables = Error::decodeException($e);
      $this->logger->error('%type: @message in %function (line %line of %file).', $variables);
      $result['errors'] = 1;
    }

    // Assign the result summary back into the batch context for use by a
    // calling batch process.
    $this->context['results'][] = $result;
  }

  /**
   * Load the targeted entity or entity revision.
   *
   * @param string $entity_type_id
   *   The entity type machine name.
   * @param int $entity_id
   *   The entity unique ID.
   * @param int|null $revision_id
   *   (Optional) An entity revision ID to load if the entity type is
   *   revisionable.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded entity or NULL if it could not be found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntity(string $entity_type_id, int $entity_id, ?int $revision_id = NULL): ?EntityInterface {
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);

    // Load the display and entity, if available, for updating.
    if ($revision_id !== NULL) {
      return $entity_storage->loadRevision($revision_id);
    }
    else {
      return $entity_storage->load($entity_id);
    }
  }

  /**
   * Set a context object for batch processing feedback.
   *
   * This context object is assigned by reference to enable sharing information
   * back to a calling batch process throughout execution.
   *
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   Batch context. It may be an array, or implementing \ArrayObject in the
   *   case of Drush.
   */
  public function setContext(&$context): void {
    $this->context = &$context;
  }

  /**
   * Gets the section storage for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode to be loaded.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface|null
   *   The section storage if found otherwise NULL.
   *
   * @see https://www.drupal.org/node/3008924
   * @see \Drupal\layout_builder\LayoutEntityHelperTrait::getSectionStorageForEntity()
   *
   * @todo Remove once https://www.drupal.org/node/3008924 is resolved.
   */
  protected function getSectionStorageForEntity(EntityInterface $entity, string $view_mode = 'default') {
    if ($entity instanceof LayoutEntityDisplayInterface) {
      $contexts['display'] = EntityContext::fromEntity($entity);
      $contexts['view_mode'] = new Context(new ContextDefinition('string'), $entity->getMode());
    }
    else {
      $contexts['entity'] = EntityContext::fromEntity($entity);
      if ($entity instanceof FieldableEntityInterface) {
        $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
        if ($display instanceof LayoutEntityDisplayInterface) {
          $contexts['display'] = EntityContext::fromEntity($display);
        }
        $contexts['view_mode'] = new Context(new ContextDefinition('string'), $view_mode);
      }
    }
    return $this->sectionStorageManager()->findByContext($contexts, new CacheableMetadata());
  }

  /**
   * Process unsaved layouts for overridden layouts.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The original layout to load unsaved versions for processing.
   * @param string $display_id
   *   The display ID used for the original layout being processed.
   * @param array $data
   *   Processing data for the overall operation.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for the layout being processed.
   * @param array $result
   *   Result data for the current layout being processed. This is passed by
   *   reference and may be altered directly.
   */
  protected function processTempstoreOverrideLayout(SectionStorageInterface $section_storage, string $display_id, array $data, ContentEntityInterface $entity, array &$result): void {
    if ($this->layoutTempstoreRepository->has($section_storage)) {
      $display_id = $data['display_id'] = 'temp.' . $display_id;
      $this->messenger()
        ->addStatus($this->t('Updating temporary layout for overridden layout ID "@id"', [
          '@id' => $display_id,
        ]));
      $temp_storage = $this->layoutTempstoreRepository->get($section_storage);

      // Capture results in a temporary array to prevent existing values
      // from being overridden.
      $temp_results = [];
      $this->processEntityLayout($temp_storage, $entity, $data, $temp_results);

      // Handle updates if changes were made to the temporary layout.
      if ($temp_results['entities'] > 0) {
        // Capture update statistics.
        $result['blocks'] += $temp_results['blocks'];
        $result['entities'] += $temp_results['entities'];

        // Update the saved temporary layout.
        $this->layoutTempstoreRepository->set($temp_storage);
      }
    }
  }

  /**
   * Process unsaved layouts for default layouts.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The original layout to load unsaved versions for processing.
   * @param string $display_id
   *   The display ID used for the original layout being processed.
   * @param array $data
   *   Processing data for the overall operation.
   * @param array $result
   *   Result data for the current layout being processed. This is passed by
   *   reference and may be altered directly.
   */
  protected function processTempstoreDefaultLayout(SectionStorageInterface $section_storage, string $display_id, array $data, array &$result): void {
    if ($this->layoutTempstoreRepository->has($section_storage)) {
      $display_id = $data['display_id'] = 'temp.' . $display_id;
      $this->messenger()
        ->addStatus($this->t('Updating temporary layout for default layout ID "@id"', [
          '@id' => $display_id,
        ]));
      $temp_storage = $this->layoutTempstoreRepository->get($section_storage);

      // Capture results in a temporary array to prevent existing values
      // from being overridden.
      $temp_results = [];
      $this->processDefaultLayout($temp_storage, $data, $temp_results);

      // Handle updates if changes were made to the temporary layout.
      if ($temp_results['entities'] > 0) {
        // Capture update statistics.
        $result['blocks'] += $temp_results['blocks'];
        $result['entities'] += $temp_results['entities'];

        // Update the saved temporary layout.
        $this->layoutTempstoreRepository->set($temp_storage);
      }
    }
  }

}
