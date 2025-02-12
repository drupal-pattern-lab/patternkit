<?php

namespace Drupal\Tests\patternkit\Traits;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\patternkit\Entity\Pattern;

/**
 * A helper trait to alter Pattern database content during testing.
 *
 * The methods of this trait are focused on accessing the stored data for
 * Patterns directly in the database and altering it in order to force scenarios
 * that may need to be tested. Most notably, this can be helpful for forcing a
 * pattern to be detected as having an update available.
 *
 * This trait is only intended for use in tests.
 */
trait PatternDatabaseAlterationTrait {

  /**
   * Storage handler for patternkit_pattern content.
   *
   * @var \Drupal\Core\Entity\RevisionableStorageInterface|\Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * Update a pattern entity revision to have a new schema value.
   *
   * @param int $revision_id
   *   The pattern entity revision id to alter.
   * @param string $schema
   *   The schema content to update the pattern entity to use.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  protected function setCachedPatternSchema(int $revision_id, string $schema) {
    $patternEntity = $this->patternStorage()->loadRevision($revision_id);
    assert($patternEntity instanceof Pattern);

    // Set the cached pattern entity to use the old schema version.
    $patternEntity->setSchema($schema);
    $patternEntity->setNewRevision(FALSE);
    $patternEntity->save();
  }

  /**
   * Update a pattern entity revision to have a new template value.
   *
   * @param int $revision_id
   *   The pattern entity revision id to alter.
   * @param string $template
   *   The template content to update the pattern entity to use.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  protected function setCachedPatternTemplate(int $revision_id, string $template) {
    $patternEntity = $this->patternStorage()->loadRevision($revision_id);
    assert($patternEntity instanceof Pattern);

    // Set the cached pattern entity to use the old schema version.
    $patternEntity->setTemplate($template);
    $patternEntity->setNewRevision(FALSE);
    $patternEntity->save();
  }

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
   * Get the pattern entity storage handler.
   *
   * @return \Drupal\Core\Entity\RevisionableStorageInterface
   *   The pattern entity storage handler.
   */
  protected function patternStorage(): RevisionableStorageInterface {
    if (!isset($this->patternStorage)) {
      $entity_type_manager = $this->container->get('entity_type.manager');
      // @phpstan-ignore assign.propertyType
      $this->patternStorage = $entity_type_manager->getStorage('patternkit_pattern');
    }

    assert($this->patternStorage instanceof RevisionableStorageInterface);
    return $this->patternStorage;
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

}
