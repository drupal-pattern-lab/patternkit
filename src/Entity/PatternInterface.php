<?php

namespace Drupal\patternkit\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Pattern entity type which stores pattern metadata in Drupal.
 *
 * @package Drupal\patternkit\Entity
 */
interface PatternInterface extends ContentEntityInterface {

  /**
   * Get assets for the pattern instance.
   *
   * @return array
   *   A list of all assets for the pattern instance.
   */
  public function getAssets(): array;

  /**
   * Get the computed hash for this pattern.
   *
   * @return string
   *   The computed hash for this pattern.
   */
  public function getHash(): string;

  /**
   * Get the identifier for the library providing this pattern.
   *
   * @return string
   *   The identifier for the library providing this pattern.
   */
  public function getLibrary(): string;

  /**
   * Get the plugin ID for the library parser plugin providing this pattern.
   *
   * @return string|null
   *   The plugin ID for the library parser plugin providing this pattern.
   */
  public function getLibraryPluginId(): ?string;

  /**
   * Get the path to this pattern's discovered assets.
   *
   * @return string
   *   The path to this pattern's discovered assets.
   */
  public function getPath(): string;

  /**
   * Get the schema for this pattern.
   *
   * @return string
   *   The schema for this pattern.
   */
  public function getSchema(): string;

  /**
   * Get the template for this pattern.
   *
   * @return string
   *   The template for this pattern.
   */
  public function getTemplate(): string;

  /**
   * Provides an asset ID in library/path namespace format.
   *
   * @return string
   *   ID string in the format:
   *     @code @library.name/path/to/pattern @endcode
   */
  public function getAssetId(): string;

  /**
   * Get the version for this pattern instance.
   *
   * @return string
   *   The version for this pattern instance.
   */
  public function getVersion(): string;

  /**
   * Get the category for this pattern instance.
   *
   * @return string
   *   The category for this pattern instance.
   */
  public function getCategory(): string;

  /**
   * Get the description for this pattern instance.
   *
   * @return string
   *   The description for this pattern instance.
   */
  public function getDescription(): string;

  /**
   * Get the name value for this pattern instance.
   *
   * @return string
   *   The name assigned to this pattern instance.
   */
  public function getName(): string;

  /**
   * Set the library plugin ID for this pattern instance.
   *
   * @param string $id
   *   The library plugin ID.
   *
   * @return static
   */
  public function setLibraryPluginId(string $id): self;

  /**
   * Set the schema for this pattern instance.
   *
   * @param string|array $schema
   *   The schema value for this pattern instance. If provided as an array, it
   *   will be JSON encoded automatically.
   *
   * @return static
   *
   * @throws \JsonException
   *   Throws an exception if serialization of the provided value fails.
   */
  public function setSchema($schema): self;

  /**
   * Set the template value for this pattern instance.
   *
   * @param string $template
   *   The template value for this pattern instance.
   *
   * @return static
   */
  public function setTemplate(string $template): self;

  /**
   * Set the assets for this pattern instance.
   *
   * @param array $assets
   *   Assets for this pattern instance.
   *
   * @return static
   */
  public function setAssets(array $assets): self;

  /**
   * Set the version for this pattern instance.
   *
   * @param string $version
   *   The version value for this pattern instance.
   *
   * @return static
   */
  public function setVersion(string $version): self;

}
