<?php

namespace Drupal\patternkit\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\patternkit\PatternkitLibraryDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PatternkitBlock extends DeriverBase implements ContainerDeriverInterface {

  /** @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface */
  protected $patternLibraryDiscovery;

  /** @var \Drupal\Core\Entity\EntityStorageInterface */
  protected $patternkitStorage;

  /**
   * PatternkitBlock constructor.
   *
   * @param \Drupal\patternkit\PatternkitLibraryDiscoveryInterface $pattern_discovery
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   */
  public function __construct(PatternkitLibraryDiscoveryInterface $pattern_discovery, EntityStorageInterface $storage) {
    $this->patternLibraryDiscovery = $pattern_discovery;
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
    /** @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface $pattern_discovery */
    $pattern_discovery = $container->get('patternkit.library.discovery');
    return new static(
      $pattern_discovery,
      $entity_manager->getStorage('patternkit_block'));
  }

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    // Reset the discovered definitions.
    $this->derivatives = [];
    // @todo: Add support for ContextDefinition('entity:node') etc.
    /** @var \Drupal\patternkit\Pattern[] $patterns */
    $patterns = $this->patternLibraryDiscovery->getAssets();
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
      $this->derivatives[$pattern_id] += $base_plugin_definition;
    }

    $patternkit_blocks = $this->patternkitStorage->loadByProperties(['reusable' => TRUE]);
    foreach ($patternkit_blocks as $patternkit_block) {
      $this->derivatives[$patternkit_block->uuid()] = $base_plugin_definition;
      $this->derivatives[$patternkit_block->uuid()]['admin_label'] = $patternkit_block->label();
      $this->derivatives[$patternkit_block->uuid()]['config_dependencies']['content'] = [
        $patternkit_block->getConfigDependencyName(),
      ];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
