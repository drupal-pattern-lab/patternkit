<?php

namespace Drupal\patternkit_usage_tracking\Plugin\QueueWorker;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\Plugin\QueueWorker\LayoutQueueWorkerBase;
use Drupal\patternkit_usage_tracking\LayoutUsageTracker;
use Drupal\patternkit_usage_tracking\PatternUsageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A queue worker plugin for processing queued layouts for pattern usages.
 *
 * A worker plugin to consume items from the "patternkit_usage_tracking" queue
 * and record pattern usages within the referenced layouts.
 *
 * @QueueWorker(
 *   id = "patternkit_usage_tracking",
 *   title = @Translation("Patternkit usage tracking queue"),
 *   cron = {"time" = 60}
 * )
 */
class PatternUsageQueueWorker extends LayoutQueueWorkerBase {

  /**
   * The identifier for this queue worker's queue.
   */
  public const QUEUE_ID = 'patternkit_usage_tracking';

  /**
   * The pattern usage manager service.
   *
   * @var \Drupal\patternkit_usage_tracking\PatternUsageManager
   */
  protected PatternUsageManager $usageManager;

  /**
   * The pattern layout usage tracker service.
   *
   * @var \Drupal\patternkit_usage_tracking\LayoutUsageTracker
   */
  protected LayoutUsageTracker $layoutUsageTracker;

  /**
   * Constructs a PatternUsageQueueWorker object.
   *
   * @param array<string, mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\patternkit\LayoutHelper $layout_helper
   *   The layout helper service.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   *   The section storage plugin manager service.
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel to be used.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\patternkit_usage_tracking\PatternUsageManager $pattern_usage_manager
   *   The pattern usage tracking manager service.
   * @param \Drupal\patternkit_usage_tracking\LayoutUsageTracker $layout_usage_tracker
   *   The layout usage tracker service.
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
    MessengerInterface $messenger,
    PatternUsageManager $pattern_usage_manager,
    LayoutUsageTracker $layout_usage_tracker,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $layout_helper,
      $section_storage_manager,
      $layout_tempstore_repository,
      $logger,
      $string_translation,
      $messenger
    );

    $this->usageManager = $pattern_usage_manager;
    $this->layoutUsageTracker = $layout_usage_tracker;
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
      $container->get('pattern_usage.manager'),
      $container->get('pattern_usage.layout_tracker'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processEntityLayout(SectionStorageInterface $storage, ContentEntityInterface $entity, array $data, array &$result): void {
    $this->logger->notice(sprintf('Tracking patterns for layout ID "%s:%s".',
      $storage->getPluginId(), $storage->getStorageId()));

    $result['num_tracked'] = $this->layoutUsageTracker->trackOnEntityCreation($entity);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Implement tracking for default layouts.
   */
  public function processDefaultLayout(SectionStorageInterface $storage, array $data, array &$result): void {
    $this->messenger()->addMessage('Tracking on default layouts is not supported yet.');
    $result['num_tracked'] = 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function processTempstoreDefaultLayout(SectionStorageInterface $section_storage, string $display_id, array $data, array &$result): void {
    // Ignore processing for unsaved layouts.
  }

  /**
   * {@inheritdoc}
   */
  protected function processTempstoreOverrideLayout(SectionStorageInterface $section_storage, string $display_id, array $data, ContentEntityInterface $entity, array &$result): void {
    // Ignore processing for unsaved layouts.
  }

}
