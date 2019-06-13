<?php

namespace Drupal\patternkit;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a default class for PatternLibrary plugins.
 */
abstract class PatternLibraryPluginDefault extends PluginBase implements PatternLibraryPluginInterface {

  /**
   * The plugin definition.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginDefinition
   */
  protected $pluginDefinition;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#settings'] = $this->getConfiguration();
    $build['#definition'] = $this->pluginDefinition;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
