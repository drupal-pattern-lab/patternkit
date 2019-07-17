<?php

namespace Drupal\patternkit;

use Drupal\Core\Asset\LibraryDiscoveryInterface;

/**
 * Allows for implementations of a Pattern Library Discovery service.
 */
interface PatternkitLibraryDiscoveryInterface extends LibraryDiscoveryInterface {

  /**
   * Returns an array of library assets keyed by library and file.
   *
   * @return \Drupal\patternkit\Pattern[]
   *   An array of library assets.
   *
   * @throws \Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
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
   * @return mixed|null
   *   The specific asset requested or NULL if not found.
   *
   * @throws \Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   */
  public function getLibraryAsset($key);

  /**
   * Returns an array of all libraries.
   *
   * @return array
   *   All libraries as definitions, keyed by library name.
   */
  public function getLibraries(): array;

}
