<?php

namespace Drupal\patternkit\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;

/**
 * Defines a Drupal patternkit (patternkit://) stream wrapper class.
 *
 * Provides support for storing temporarily accessible files with the Drupal
 * file interface.
 */
class PatternkitStream extends LocalStream {

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL_HIDDEN;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Patternkit patterns');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Patternkit files for parsing and rendering.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return file_directory_temp();
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return Url::fromRoute('system.patternkit', [], ['absolute' => TRUE, 'query' => ['file' => $path]])->toString();
  }

}
