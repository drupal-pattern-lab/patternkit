<?php

namespace Drupal\patternkit\Asset;

use Drupal\Core\Cache\CacheCollectorInterface;

/**
 * Discovers information for asset (CSS/JavaScript) libraries.
 *
 * Library information is statically cached. Libraries are keyed by extension
 * for several reasons:
 * - Libraries are not unique. Multiple extensions might ship with the same
 *   library in a different version or variant. This registry cannot (and does
 *   not attempt to) prevent library conflicts.
 * - Extensions implementing and thereby depending on a library that is
 *   registered by another extension can only rely on that extension's library.
 * - Two (or more) extensions can still register the same library and use it
 *   without conflicts in case the libraries are loaded on certain pages only.
 *
 * @deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0.
 *   Use \Drupal\patternkit\Asset\LibraryNamespaceResolver or
 *   \Drupal\patternkit\Asset\PatternDiscovery instead.
 * @see https://www.drupal.org/node/3295833
 * @see \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface
 * @see \Drupal\patternkit\Asset\PatternDiscoveryInterface
 */
interface LibraryInterface extends CacheCollectorInterface {

  /**
   * Clears static and persistent library definition caches.
   */
  public function clearCachedDefinitions(): void;

  /**
   * Returns an array of library assets keyed by library and file.
   *
   * @return array
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
  public function getLibraryAsset(string $key);

  /**
   * Returns an array of all libraries.
   *
   * @return \Drupal\patternkit\PatternLibrary[]
   *   All libraries as definitions, keyed by library name.
   */
  public function getLibraries(): array;

  /**
   * Gets all libraries defined by an extension.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   *
   * @return array
   *   An associative array of libraries registered by $extension is returned
   *   (which may be empty).
   *
   * @see self::getLibraryByName()
   */
  public function getLibrariesByExtension(string $extension): array;

  /**
   * Gets a single library defined by an extension by name.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $name
   *   The name of a registered library to retrieve.
   *
   * @return array|false
   *   The definition of the requested library, if $name was passed, and it
   *   exists, otherwise FALSE.
   */
  public function getLibraryByName(string $extension, string $name);

  /**
   * Returns the specified Patternkit module metadata.
   *
   * @return \Drupal\patternkit\PatternLibrary[]
   *   Array of metadata objects found or object if specific pattern requested.
   *   Keyed by library name in the format.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getLibraryDefinitions(): array;

}
