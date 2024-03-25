<?php

namespace Drupal\patternkit\BulkOperations\Worker;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Utility\Error;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\patternkit\BulkOperations\LayoutUpdateBatchManager;
use Drupal\patternkit\Plugin\QueueWorker\LayoutUpdateQueueWorker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Batch worker for discovering and queuing layouts for update.
 */
class LayoutDiscoveryBatchWorker implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Revision handling mode with no revision restrictions specified.
   */
  const REVISION_MODE_DEFAULT = 'default';

  /**
   * Revision handling mode where all known revisions are processed.
   */
  const REVISION_MODE_ALL = 'all';

  /**
   * Revision handling mode where only the latest revision is processed.
   */
  const REVISION_MODE_LATEST = 'latest';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The queue factory service for loading necessary queues.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * The storage handler for the entity type being processed.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $entityStorage;

  /**
   * The storage handler entity displays.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $displayStorage;

  /**
   * The loaded entity type being processed.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected EntityTypeInterface $entityType;

  /**
   * The queue instance for storing and loading tasks.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected QueueInterface $queue;

  /**
   * The logger channel for outputting progress messaging.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Raw option values provided to the batch worker.
   *
   * @var array
   */
  protected array $options;

  /**
   * Whether queued items should be immediately processed.
   *
   * @var bool
   */
  protected bool $runQueue;

  /**
   * The revision processing mode selected for this process.
   *
   * @var string
   *
   * @see self::REVISION_MODE_DEFAULT
   * @see self::REVISION_MODE_LATEST
   * @see self::REVISION_MODE_ALL
   */
  protected string $revisionMode;

  /**
   * Whether revisions should be processed.
   *
   * @var bool
   */
  protected bool $shouldProcessRevisions;

  /**
   * Parsed filter values for this process.
   *
   * @var array{entity_type?: string, bundle?: string, display_mode?: string, library?: string, pattern?: string}
   */
  protected array $filters;

  /**
   * An associative array of display filter properties from filter options.
   *
   * @var array{targetEntityType: string, bundle?: string, mode?: string}
   */
  protected array $displayFilters;

  /**
   * Constructs a layout discovery batch worker instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger_channel
   *   A logger channel for logging messages.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    QueueFactory $queue_factory,
    LoggerChannelInterface $logger_channel,
    TranslationInterface $translation
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
    $this->logger = $logger_channel;
    $this->stringTranslation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('queue'),
      $container->get('logger.channel.patternkit_batch'),
      $container->get('string_translation'),
    );
  }

  /**
   * Get the plugin identifier for the queue to reference.
   *
   * @return string
   *   The plugin ID for the queue to be processed.
   */
  public function getQueueId(): string {
    return LayoutUpdateQueueWorker::QUEUE_ID;
  }

  /**
   * Batch worker to queue all updatable displays for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id, for example 'node'.
   * @param int $batch_size
   *   The batch size.
   * @param array{revisions: string, run_queue: bool, filter: array{entity_type?: string, bundle?: string, display_mode?: string, library?: string, pattern?: string}} $options
   *   An array of options to influence batching behavior and filter processed
   *   blocks.
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   Batch context. It may be an array, or implementing \ArrayObject in the
   *   case of Drush.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see self::generateBatch()
   * @see self::entityTypeBatchFinished()
   */
  public function execute(string $entity_type_id, int $batch_size, array $options, &$context): void {
    $this->entityStorage = $this->entityTypeManager->getStorage($entity_type_id);
    $this->entityType = $this->entityTypeManager->getDefinition($entity_type_id);
    $this->queue = $this->queueFactory->get($this->getQueueId());

    // Parse batching options.
    $this->parseOptions($options);

    $translatable = $this->entityType->isTranslatable();

    // First pass, populate the sandbox.
    if (empty($context['sandbox']['total'])) {
      $this->initializeSandbox($context);
    }

    if ($context['sandbox']['total'] > 0) {
      try {
        $entity_key = $this->shouldProcessRevisions ? $this->entityType->getKey('revision') : $this->entityType->getKey('id');

        $ids = $this->getEntityQuery()
          ->condition($entity_key, $context['sandbox']['current_item'], '>')
          ->sort($entity_key)
          ->range(0, $batch_size)
          ->execute();

        // Load this batch of entities to test and ensure they are applicable
        // for each overridden display.
        $entities = $this->entityStorage->loadMultiple($ids);

        foreach ($ids as $revision_id => $entity_id) {
          // Queue processing for each overridable display.
          /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
          foreach ($context['sandbox']['overridable_displays'] as $display) {
            $entity = $entities[$entity_id];

            // Only queue for processing if the current entity is applicable
            // for this display.
            if ($display->getTargetBundle() == $entity->bundle()) {
              // Identify all available translations for processing.
              if ($translatable && $entity instanceof TranslatableInterface) {
                // Only record the language codes.
                $languages = array_keys($entity->getTranslationLanguages());
              }
              else {
                $languages = [LanguageInterface::LANGCODE_NOT_SPECIFIED];
              }

              // Queue a task for each translation.
              foreach ($languages as $language) {
                $queue_item = $this->createQueueItem($display, $language, $entity_id, ($this->shouldProcessRevisions ? $revision_id : NULL));
                $context['results']['discovered_displays'][$queue_item['display_id']] = $queue_item;
                $this->queue->createItem($queue_item);
              }
            }
          }

          $context['sandbox']['progress']++;
          $context['sandbox']['current_item'] = $this->shouldProcessRevisions ? $revision_id : $entity_id;
        }
      }
      catch (\Exception $e) {
        $variables = Error::decodeException($e);
        $this->logger->error('%type: @message in %function (line %line of %file).', $variables);
      }
    }

    if ($context['sandbox']['progress'] < $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
    }
    else {
      $context['finished'] = 1;

      if ($this->runQueue) {
        // Queue follow-up operations. This has to be done prior to the batch
        // operation being completed, otherwise the next item set in the batch
        // queue is not fetched and batch operations are finished early.
        $this->setQueueRunnerBatch();
      }
    }

    $context['message'] = $this->progressMessage($context);
  }

  /**
   * Generate the current progress message for batch processing.
   *
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   Batch context. It may be an array, or implementing \ArrayObject in the
   *   case of Drush.
   *
   * @return string
   *   The progress message to be displayed.
   */
  protected function progressMessage($context): string {
    return $this->t('Queueing Patternkit layout updates for entity type "@entity_type": @current of @total', [
      '@entity_type' => $this->entityType->id(),
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Initialize the batch context for processing.
   *
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   The batch context array to be initialized.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see self::execute()
   */
  protected function initializeSandbox(&$context): void {
    $this->displayStorage = $this->entityTypeManager->getStorage('entity_view_display');

    $context['sandbox']['progress'] = 0;
    $context['results']['entity_type_id'] = $this->entityType->id();
    $context['results']['entity_type_label'] = $this->entityType->getLabel();
    $context['results']['discovered_displays'] = [];

    // Load displays for the entity type.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface[] $displays */
    $displays = $this->displayStorage->loadByProperties($this->displayFilters);

    // Identify layout-enabled displays for updating.
    $context['sandbox']['overridable_displays'] = [];
    foreach ($displays as $display_id => $display) {
      if ($display instanceof LayoutBuilderEntityViewDisplay && $display->isLayoutBuilderEnabled()) {
        // Queue the default layout for this display.
        $queue_item = $this->createQueueItem($display);
        $context['results']['discovered_displays'][$display_id] = $queue_item;
        $this->queue->createItem($queue_item);

        // Record any overridable displays for later processing.
        if ($display->isOverridable()) {
          $context['sandbox']['overridable_displays'][$display_id] = $display;
        }
      }
    }

    // Prepare to iterate over entities to process overridden displays.
    if (!empty($context['sandbox']['overridable_displays'])) {
      $query = $this->getEntityQuery();
      $context['sandbox']['total'] = (int) $query->count()->execute();

      // Setup sandbox variables and queuing.
      $context['sandbox']['current_item'] = 0;
      $context['finished'] = 0;
    }
    else {
      // No additional processing is needed, so mark everything as finished.
      $context['sandbox']['total'] = 0;
      $context['sandbox']['progress'] = 1;
      $context['finished'] = 1;
    }
  }

  /**
   * Prepare an entity query for loading entity revisions to process.
   *
   * Based on the active revision mode, the returned query will be preconfigured
   * with expected options.
   *
   * NB. Bundle filters are not applied to the query here. Instead, the list of
   * entities ever queried and loaded altogether is restricted by the displays
   * identified for processing. These filters are parsed in
   * @link \Drupal\patternkit\BulkOperations\Worker\LayoutDiscoveryBatchWorker::parseFilters @endlink
   * and limit the selection of displays ever processed preventing entities of
   * non-matching bundles from being queried.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A configured entity query ready for further customization and execution
   *   as needed.
   *
   * @see self::parseFilters()
   */
  protected function getEntityQuery(): QueryInterface {
    $query = $this->entityStorage->getQuery()
      ->accessCheck(FALSE)
      // Only get results with stored layout overrides.
      ->condition(OverridesSectionStorage::FIELD_NAME, NULL, 'IS NOT NULL');

    // Stop here if no special handling is needed for revisions.
    if ($this->revisionMode == static::REVISION_MODE_DEFAULT || !$this->entityType->isRevisionable()) {
      return $query;
    }

    switch ($this->revisionMode) {
      case static::REVISION_MODE_ALL:
        $query->allRevisions();
        break;

      case static::REVISION_MODE_LATEST:
        $query->latestRevision();
        break;

      default:
        throw new \InvalidArgumentException(sprintf('Unknown revision mode "%s".', $this->revisionMode));
    }

    return $query;
  }

  /**
   * Prepare a queue item for an entity display to be processed.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display entity being queued for updating.
   * @param string|null $language
   *   (optional) The language code for the entity display to be processed.
   * @param int|null $entity_id
   *   (optional) The id for a specific entity to update if applicable.
   * @param int|null $revision_id
   *   (optional) The revision id for a specific entity to update if
   *   applicable.
   *
   * @return array
   *   A prepared array of display data for queued processing.
   */
  public function createQueueItem(EntityViewDisplayInterface $display, ?string $language = NULL, ?int $entity_id = NULL, ?int $revision_id = NULL): array {
    // Assemble a unique display ID for each item.
    if (empty($entity_id)) {
      // If no entity ID is provided, this is the default display for a bundle.
      $layout_plugin = 'defaults';
      $display_id = $display->id();
    }
    else {
      $layout_plugin = 'overrides';
      $display_id = implode('.', [
        $layout_plugin,
        $display->getTargetEntityTypeId(),
        $display->getTargetBundle(),
        $display->getMode(),
        $language,
        $entity_id . (empty($revision_id) ? '' : ":$revision_id"),
      ]);
    }

    return [
      'display_id' => $display_id,
      'mode' => $display->getMode(),
      'layout_plugin' => $layout_plugin,
      'entity_type' => $display->getTargetEntityTypeId(),
      'entity_bundle' => $display->getTargetBundle(),
      'language' => $language,
      'entity_id' => $entity_id,
      'entity_revision_id' => $revision_id,
      'filter' => $this->filters,
    ];
  }

  /**
   * Register a new batch to execute all queued items.
   *
   * Queue follow-up operations. This has to be done prior to the batch
   * operation being completed, otherwise the next item set in the batch queue
   * is not fetched and batch operations are finished early.
   */
  protected function setQueueRunnerBatch(): void {
    $update_batch = LayoutUpdateBatchManager::generateBatch();
    batch_set($update_batch);
  }

  /**
   * Parse and save option values for processing.
   *
   * @param array $options
   *   All option values passed in for execution.
   */
  protected function parseOptions(array $options): void {
    $this->options = $options;
    $this->runQueue = $options['run_queue'];
    $this->revisionMode = $options['revisions'];
    $this->shouldProcessRevisions = $this->revisionMode !== static::REVISION_MODE_DEFAULT &&
      $this->entityType->isRevisionable();

    $this->parseFilters($options['filter']);
  }

  /**
   * Parse and save filter values for processing.
   *
   * @param array $filters
   *   Filter options for processing.
   */
  protected function parseFilters(array $filters): void {
    $this->filters = $filters;

    // Assemble filters to load targeted display modes.
    $this->displayFilters = [
      'targetEntityType' => $this->entityType->id(),
    ];
    if ($bundle_filter = $filters['bundle']) {
      $this->displayFilters['bundle'] = $bundle_filter;
    }
    if ($display_mode_filter = $filters['display_mode']) {
      $this->displayFilters['mode'] = $display_mode_filter;
    }
  }

}
