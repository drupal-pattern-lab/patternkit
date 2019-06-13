<?php

namespace Drupal\patternkit;

use Drupal\Core\Asset\LibraryDiscoveryInterface;

interface PatternkitLibraryDiscoveryInterface extends LibraryDiscoveryInterface {

  /**
   * Returns an array of library assets keyed by library and file.
   *
   * @return array
   *   An array of library assets.
   */
  public function getAssets(): array;

  /**
   * Returns a specific asset.
   *
   * This is a replacement function for using an
   * \Drupal\Core\Asset\AssetResolverInterface.
   *
   * @param string $key
   *   The asset key.
   *
   * @return mixed
   */
  public function getLibraryAsset($key);

  /**
   * @return \Drupal\patternkit\Pattern[]
   */
  public function getLibraries(): array;
}
