<?php

namespace Drupal\patternkit\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Defines a Drupal patternkit (patternkit://) stream wrapper class.
 *
 * Provides support for storing temporarily accessible files with the Drupal
 * file interface.
 *
 * @deprecated in patternkit:9.1.0-beta10 and is removed from patternkit:9.1.0.
 *    This should no longer be required for dependency attachment.
 * @see https://www.drupal.org/project/patternkit/issues/3386854
 * @internal
 */
class PatternkitStream extends LocalStream {

  use StringTranslationTrait;

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
    return $this->t('Patternkit patterns');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Patternkit files for parsing and rendering.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return \Drupal::service('file_system')->getTempDirectory();
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return Url::fromRoute('system.patternkit', [], [
      'absolute' => TRUE,
      'query' => ['file' => $path],
    ])->toString();
  }

}
