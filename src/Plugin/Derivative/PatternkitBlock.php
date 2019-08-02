<?php

namespace Drupal\patternkit\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\patternkit\PatternkitLibraryDiscoveryInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PatternkitBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Logs to the patternkit channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

    /** @var \Drupal\Core\Entity\EntityStorageInterface */
  protected $patternkitStorage;

  /** @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface */
  protected $libraryDiscovery;

  /**
   * PatternkitBlock constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Generates and provides log channels.
   * @param \Drupal\patternkit\PatternkitLibraryDiscoveryInterface $pattern_discovery
   *   Provides a list of pattern libraries with metadata.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Loads and saves entities to storage.
   */
  public function __construct(LoggerChannelInterface $logger, PatternkitLibraryDiscoveryInterface $pattern_discovery, EntityStorageInterface $storage) {
    $this->logger = $logger;
    $this->libraryDiscovery = $pattern_discovery;
    $this->patternkitStorage = $storage;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function create(ContainerInterface $container, $base_plugin_id): PatternkitBlock {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');
    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $logger = $container->get('logger.channel.patternkit');
    /** @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface $pattern_discovery */
    $pattern_discovery = $container->get('patternkit.library.discovery');
    return new static(
      $logger,
      $pattern_discovery,
      $entity_manager->getStorage('patternkit_block'));
  }

  /**
   * Provides block definitions from pattern libraries and reusable patterns.
   *
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_definition): array {
    // Reset the discovered definitions.
    $this->derivatives = [];
    $patterns = [];
    // @todo: Add support for ContextDefinition('entity:node') etc.
    try {
      /** @var \Drupal\patternkit\Pattern[] $patterns */
      $patterns = $this->libraryDiscovery->getAssets();
    }
    catch (Exception $exception) {
      $this->logger->error('Error loading patterns for derivative blocks: @message', ['@message' => $exception->getMessage()]);
    }
    foreach ($patterns as $pattern_id => $pattern) {
      $this->derivatives[$pattern_id] = [
        'category'    => t(
          'Patternkit:@lib/@category',
          [
            '@lib'     => $pattern->library ?? 'patternkit',
            '@category' => $pattern->category ?? 'default',
          ]
        ),
        'admin_label' => t(
          '[Patternkit] @pattern',
          [
            '@pattern' => $pattern->getLabel(),
          ]
        ),
        'pattern' => $pattern,
      ];
      $this->derivatives[$pattern_id] += $base_definition;
    }

    $patternkit_blocks = $this->patternkitStorage->loadByProperties(['reusable' => TRUE]);
    foreach ($patternkit_blocks as $patternkit_block) {
      $this->derivatives[$patternkit_block->uuid()] = $base_definition;
      $this->derivatives[$patternkit_block->uuid()]['admin_label'] = $patternkit_block->label();
      $this->derivatives[$patternkit_block->uuid()]['config_dependencies']['content'] = [
        $patternkit_block->getConfigDependencyName(),
      ];
    }
    return parent::getDerivativeDefinitions($base_definition);
  }

}
