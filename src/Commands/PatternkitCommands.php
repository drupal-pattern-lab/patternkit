<?php

namespace Drupal\patternkit\Commands;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;

/**
 * Commands for working with the dev branch of Patternkit.
 */
class PatternkitCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * Constructs the Patternkit Drush Commands class.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator service.
   */
  public function __construct(
    CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {
    parent::__construct();

    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
  }

  /**
   * Invalidate caches for patternkit cache data.
   *
   * @command patternkit:cache:clear
   * @aliases pkcc
   */
  public function clearCache(): void {
    $tags = ['patternkit_patterns', 'patternkit_libraries'];

    $this->cacheTagsInvalidator->invalidateTags($tags);

    $this->logger()->success(
      $this->t('Invalidated all patternkit pattern and library cache entries.'));
  }

}
