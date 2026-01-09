<?php

namespace Drupal\Tests\patternkit\Traits;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * A helper trait to test cache data.
 *
 * This trait is only intended for use in tests.
 */
trait CacheTestTrait {

  /**
   * The page cache back-end service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private CacheBackendInterface $pageCache;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * Get the page cache service.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The loaded page cache service.
   */
  protected function pageCache(): CacheBackendInterface {
    if (!isset($this->pageCache)) {
      $this->pageCache = $this->container->get('cache.page');
    }

    return $this->pageCache;
  }

  /**
   * Get the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  protected function database(): Connection {
    if (!isset($this->database)) {
      $this->database = $this->container->get('database');
    }

    return $this->database;
  }

  /**
   * Get cache tags from the rendered Node entry.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node to load a page view cache entry for.
   *
   * @return string[]
   *   An array of all the cache tags associated with the node's page cache
   *   entry.
   */
  protected function getPageCacheTags(NodeInterface $node): array {
    // Construct the URL string directly, matching the pattern used in
    // BlockTest.php.
    $url_string = Url::fromUri('entity:node/' . $node->id(), ['absolute' => TRUE])->toString();
    // Ensure we have a string, not an array.
    if (!is_string($url_string)) {
      throw new \RuntimeException('URL::toString() returned a non-string value: ' . gettype($url_string));
    }
    $cid_parts = [
      $url_string,
      '',
    ];
    $cid = implode(':', $cid_parts);
    $cache_entry = $this->pageCache()->get($cid);

    if ($cache_entry === FALSE) {
      throw new \RuntimeException("Cache entry not found for CID: $cid. Ensure the page has been cached by visiting it first.");
    }

    if (!isset($cache_entry->tags)) {
      return [];
    }

    // Ensure tags is an array.
    if (!is_array($cache_entry->tags)) {
      throw new \RuntimeException('Cache entry tags is not an array: ' . gettype($cache_entry->tags));
    }

    return $cache_entry->tags;
  }

  /**
   * Get a list of recorded cache tag invalidations.
   *
   * @param bool $reset
   *   (Optional) A flag for whether the invalidations list should be truncated.
   *   By default, the table will be emptied after fetching all results.
   *
   * @return string[]
   *   An array of all cache tags that have been invalidated since the last
   *   reset.
   */
  protected function getCacheInvalidations(bool $reset = TRUE): array {
    $query = $this->database()->select('cachetags');
    $query->addField('cachetags', 'tag');

    $result = $query->execute();
    $invalidations = $result->fetchCol();

    if ($reset) {
      $this->database()->truncate('cachetags')->execute();
    }

    return $invalidations;
  }

  /**
   * Update data for a cached pattern entity without triggering the Entity API.
   *
   * This method enables field values for a 'patternkit_pattern' entity revision
   * to be updated directly in the database rather than through the Entity API
   * in order to avoid triggering Entity API save and update operations
   * including cache invalidations.
   *
   * Note: Not all fields are revisionable and present on the pattern revisions
   * table. Fields for the default revision of the pattern entity (the 'pattern'
   * table), will only be updated if the provided revision ID matches the
   * default revision ID recorded there.
   *
   * @param int $revision_id
   *   The revision ID for the patternkit_pattern entity to be updated.
   * @param array $fields
   *   An associative array of field values to be updated keyed by the field
   *   name. Keys should be limited to only the properties found on the
   *   patternkit_pattern entity.
   *
   * @return int
   *   The combined number of records updated across the entity and revisions
   *   table.
   */
  protected function updateCachedPatternData(int $revision_id, array $fields): int {
    // Limit to only fields on the revisions table.
    $revision_fields = array_intersect_key($fields, array_flip([
      'hash',
      'schema',
      'template',
      'version',
    ]));

    if (count($revision_fields)) {
      $revisions_updated = $this->database()->update('pattern_revision')
        ->condition('revision', $revision_id)
        ->fields($fields)
        ->execute();
    }

    $defaults_updated = $this->database()->update('pattern')
      ->condition('revision', $revision_id)
      ->fields($fields)
      ->execute();

    return $defaults_updated + ($revisions_updated ?? 0);
  }

  /**
   * Assert that all provided tags were invalidated since the last reset.
   *
   * @param string[] $tags
   *   An array of cache tags expected to have been invalidated.
   * @param bool $reset
   *   (Optional) Whether the list of cache tag invalidations should be
   *   truncated after checking. This is passed directly to
   *   @link \Drupal\Tests\patternkit\FunctionalJavascript\LayoutBuilderCacheInvalidationTest::getCacheInvalidations() @endlink
   *   to influence this behavior. By default, the list will be emptied after
   *   checking.
   *
   * @see \Drupal\Tests\patternkit\FunctionalJavascript\LayoutBuilderCacheInvalidationTest::assertCacheTagsNotInvalidated()
   * @see \Drupal\Tests\patternkit\FunctionalJavascript\LayoutBuilderCacheInvalidationTest::getCacheInvalidations()
   */
  protected function assertCacheTagsInvalidated(array $tags, bool $reset = TRUE): void {
    $invalidations = $this->getCacheInvalidations($reset);
    try {
      foreach ($tags as $tag) {
        $this->assertContains($tag, $invalidations, "The cache tag '$tag' should have been invalidated, but it wasn't.");
      }
    }
    catch (ExpectationFailedException $exception) {
      // Print out the list of invalidated tags to assist debugging.
      print_r($invalidations);
      throw $exception;
    }
  }

  /**
   * Assert that all provided tags were not invalidated since the last reset.
   *
   * @param string[] $tags
   *   An array of cache tags expected not to have been invalidated.
   * @param bool $reset
   *   (Optional) Whether the list of cache tag invalidations should be
   *   truncated after checking. This is passed directly to
   *   @link \Drupal\Tests\patternkit\FunctionalJavascript\LayoutBuilderCacheInvalidationTest::getCacheInvalidations() @endlink
   *   to influence this behavior. By default, the list will be emptied after
   *   checking.
   *
   * @see \Drupal\Tests\patternkit\FunctionalJavascript\LayoutBuilderCacheInvalidationTest::assertCacheTagsInvalidated()
   * @see \Drupal\Tests\patternkit\FunctionalJavascript\LayoutBuilderCacheInvalidationTest::getCacheInvalidations()
   */
  protected function assertCacheTagsNotInvalidated(array $tags, bool $reset = TRUE): void {
    $invalidations = $this->getCacheInvalidations($reset);
    try {
      foreach ($tags as $tag) {
        $this->assertNotContains($tag, $invalidations, "The cache tag '$tag' should not have been invalidated.");
      }
    }
    catch (ExpectationFailedException $exception) {
      // Print out the list of invalidated tags to assist debugging.
      print_r($invalidations);
      throw $exception;
    }
  }

}
