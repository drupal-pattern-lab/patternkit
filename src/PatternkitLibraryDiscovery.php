<?php

namespace Drupal\patternkit;

use Drupal\Core\Asset\LibraryDiscovery;

/**
 * Enhances core library handling.
 *
 * PatternkitLibraryDiscovery is meant to be an enhancement to Drupal Core
 * Library handling that allows for arbitrary libraries and loading via plugins.
 *
 * The existing library infrastructure is likely overcomplicated and could be
 * genericized and simplified.
 *
 * For example, the current library system hard-codes asset types CSS and JS
 * which leaves little room for pleasantly extending with additional asset types
 *  - it is also highly reliant on a rendering path using ['#attachments'] which
 * require the full AssetResolver pathway.
 *
 * {@inheritDoc}
 *
 * @TODO Put a lot more thought into this.
 */
class PatternkitLibraryDiscovery extends LibraryDiscovery implements PatternkitLibraryDiscoveryInterface {

  /**
   * Collects libraries in a cache-able manner and is destructible.
   *
   * @var \Drupal\patternkit\PatternLibraryCollector
   */
  protected $collector;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getAssets(): array {
    $assets = [];
    foreach ($this->getLibraries() as $library) {
      $patterns = $library['patterns'] ?? [];
      foreach ($patterns as $id => $pattern) {
        $assets[$id] = $pattern;
      }
    }
    return $assets;
  }

  /**
   * {@inheritDoc}
   */
  public function getLibraryAsset($key) {
    $library = $this->collector->get(substr($key, 0, strpos($key, '.')));
    return $library['patterns'][$key] ?? NULL;
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getLibraries(): array {
    if (!empty($this->libraryDefinitions)) {
      return $this->libraryDefinitions;
    }
    return $this->collector->getLibraryDefinitions();
  }

}
