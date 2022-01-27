<?php

namespace Drupal\patternkit\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Pattern entity type which stores pattern metadata in Drupal.
 *
 * @package Drupal\patternkit\Entity
 */
interface PatternInterface extends ContentEntityInterface {

  public function getAssets();

  public function getHash();

  public function getLibrary();

  public function getLibraryPluginId();

  public function getPath();

  public function getSchema();

  public function getTemplate();

  public function setLibraryPluginId($id);

  public function setSchema($schema);

  public function setTemplate($template);

  public function getAssetId();

  public function getVersion();

}
