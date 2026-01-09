<?php

namespace Drupal\patternkit\BulkOperations\Worker;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\patternkit\Plugin\QueueWorker\LayoutQueueWorkerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all batch workers performing operations on queued layouts.
 */
abstract class LayoutOperationsBatchWorkerBase implements ContainerInjectionInterface, LayoutOperationsBatchWorkerInterface {

  use StringTranslationTrait;

  /**
   * The queue factory service for loading necessary queues.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * The queue instance for storing and loading tasks.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected QueueInterface $queue;

  /**
   * The queue worker plugin manager service.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected QueueWorkerManagerInterface $queueWorkerManager;

  /**
   * The logger channel for outputting progress messaging.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a layout processing batch worker instance.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_worker_manager
   *   The queue worker plugin manager.
   * @param \Psr\Log\LoggerInterface $logger_channel
   *   A logger channel for logging messages.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(
    QueueFactory $queue_factory,
    QueueWorkerManagerInterface $queue_worker_manager,
    LoggerInterface $logger_channel,
    TranslationInterface $translation,
  ) {
    $this->queueFactory = $queue_factory;
    $this->queueWorkerManager = $queue_worker_manager;
    $this->logger = $logger_channel;
    $this->stringTranslation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('logger.channel.patternkit_batch'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: array<string, mixed>, finished: int|float, message: string} $context
   *   Batch context. It may be an array, or implementing \ArrayObject in the
   *   case of Drush.
   *
   * @param-out array{sandbox: array{progress?: int|0, total?: int, current_item?: int|mixed}, results: array<string|mixed>, finished: int|float, message: string} $context
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function execute(&$context): void {
    $this->queue = $this->queueFactory->get($this->getQueueId());

    if (empty($context['sandbox']['total'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['total'] = $this->queue->numberOfItems();
    }

    if ($context['sandbox']['total'] > 0) {
      // Load the queue worker and claim a queue item for processing.
      $worker = $this->getQueueWorkerPlugin();
      $worker->setContext($context);
      $item = $this->queue->claimItem();

      try {
        // @todo Handle scenario when a queue item fails to load.
        if (is_object($item)) {
          $worker->processItem($item->data);
          $this->queue->deleteItem($item);
          $context['sandbox']['progress']++;
        }
      }
      catch (\Exception $exception) {
        // In case of an exception, log it and leave the item in the queue to be
        // processed again later.
        $this->logger->error($exception->getMessage());
      }
    }

    if ($context['sandbox']['progress'] < $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
    }
    else {
      $context['finished'] = 1;
    }

    $context['message'] = $this->getBatchProgressMessage($context);
  }

  /**
   * Get a progress message to display during batch operations.
   *
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   Batch context. It may be an array, or implementing \ArrayObject in the
   *   case of Drush.
   *
   * @return string
   *   A progress message to display during batch operations.
   */
  protected function getBatchProgressMessage($context): string {
    return $this->t('Processing queued Patternkit layouts: @current of @total', [
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Get the queue worker plugin for use.
   *
   * @return \Drupal\patternkit\Plugin\QueueWorker\LayoutQueueWorkerInterface
   *   The loaded queue worker plugin to run operations.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getQueueWorkerPlugin(): LayoutQueueWorkerInterface {
    /** @var \Drupal\patternkit\Plugin\QueueWorker\LayoutQueueWorkerInterface $plugin*/
    $plugin = $this->queueWorkerManager->createInstance($this->getQueueId());
    return $plugin;
  }

}
