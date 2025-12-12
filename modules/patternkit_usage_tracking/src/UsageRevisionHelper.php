<?php

namespace Drupal\patternkit_usage_tracking;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\Utility\PatternDetector;
use Drupal\patternkit_usage_tracking\Entity\PatternUsage;

/**
 * Helper service to assist with updating tracked usage records on revisions.
 *
 * @see \Drupal\patternkit_usage_tracking\LayoutUsageTracker
 */
class UsageRevisionHelper {

  use PatternLayoutDiscoveryTrait;
  use PatternLayoutUsageInsertTrait;

  /**
   * The track config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Service constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The EntityFieldManager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\patternkit_usage_tracking\PatternUsageManager $trackingStorage
   *   The pattern usage tracking storage.
   * @param \Drupal\patternkit\LayoutHelper $layoutHelper
   *   The patternkit layout helper service.
   * @param \Drupal\patternkit\Utility\PatternDetector $patternDetector
   *   The pattern detector service.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $sectionStorageManager
   *   The section storage manager service.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly EntityFieldManagerInterface $entityFieldManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected PatternUsageManager $trackingStorage,
    protected LayoutHelper $layoutHelper,
    protected PatternDetector $patternDetector,
    SectionStorageManagerInterface $sectionStorageManager,
  ) {
    // Assign sectionStorageManager to the property defined by
    // LayoutEntityHelperTrait (via PatternLayoutDiscoveryTrait).
    $this->sectionStorageManager = $sectionStorageManager;
  }

  /**
   * Test whether an entity should be tracked.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being tested.
   *
   * @return bool
   *   TRUE if the entity should be tracked, FALSE otherwise.
   */
  public function shouldTrackEntity(ContentEntityInterface $entity): bool {
    // Don't track changes if no layout is defined or this is not the default
    // revision.
    return $this->isLayoutCompatibleEntity($entity) &&
      $entity->getEntityType()->isRevisionable() &&
      $entity->isDefaultRevision();
  }

  /**
   * Track changes in pattern usages when an entity is updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being updated.
   */
  public function trackOnEntityUpdate(EntityInterface $entity): void {
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }

    // Abort if this entity or revision is not something we should track changes
    // for.
    if (!$this->shouldTrackEntity($entity)) {
      return;
    }

    // Get patterns from the layout components.
    $edited_patterns = $this->getPatternsByLayoutComponentByEntity($entity);

    // Load previously tracked patterns for comparison and revision.
    $saved_patterns = $this->loadPreviouslyTrackedPatterns($entity);

    $insert_component = $this->curryCallback([$this, 'insertComponent'], $entity);
    $delete_component = $this->curryCallback([$this, 'deleteComponent'], $entity);
    $update_component = $this->curryCallback([$this, 'updateComponent'], $entity);

    // Track changes in top-level components.
    static::trackChanges(
      $saved_patterns,
      $edited_patterns,
      $insert_component,
      $delete_component,
      $update_component
    );

    // Update revision IDs for records on other translations that may have been
    // affected.
    $this->updateRelatedTranslations($entity);
  }

  /**
   * Load all tracked pattern usages for the entity.
   *
   * This method loads all tracked pattern usages for an entity and nests the
   * records in a structure roughly parallel to the one produced during the
   * discovery of pattern usages via
   * @link \Drupal\patternkit_usage_tracking\PatternLayoutDiscoveryTrait::identifyPatternsByComponent @endlink.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to load tracked usage records for.
   *
   * @return array<string, array{uuid: string, layout_path: string, pattern_id: string, nested: array<string, \Drupal\patternkit_usage_tracking\Entity\PatternUsage>, record: \Drupal\patternkit_usage_tracking\Entity\PatternUsage}>
   *   An array of all saved pattern usage records sorted into record items
   *   keyed by their containing component's UUID. Each item includes the
   *   following keys:
   *   - uuid: The UUID of the containing component.
   *   - layout_path: The path to the component within the layout in the form
   *     `<section_delta>.<uuid>:<pattern_id>.
   *   - pattern_id: The asset name of the pattern contained in the component.
   *   - nested: An array of all loaded pattern usage records nested within the
   *     component. Each nested record is keyed by the ID of the nested pattern.
   *   - record: The usage record for the top-level pattern of the component.
   */
  public function loadPreviouslyTrackedPatterns(ContentEntityInterface $entity): array {
    $saved_records = $this->trackingStorage->loadByEntity($entity);

    // Organize records for comparison.
    $saved_patterns = [];
    foreach ($saved_records as $record) {
      $uuid = $record->get('component_uuid')->value;
      $pattern_id = $record->get('pattern_name')->value;

      if ($record->isNested()) {
        assert(empty($saved_patterns[$uuid]['nested'][$pattern_id]), 'Unexpectedly found multiple nested pattern records for the same pattern ID.');
        $saved_patterns[$uuid]['nested'][$pattern_id] = $record;
      }
      else {
        $saved_patterns[$uuid]['uuid'] = $uuid;
        $saved_patterns[$uuid]['layout_path'] = $record->getLayoutPath();
        $saved_patterns[$uuid]['pattern_id'] = $pattern_id;
        $saved_patterns[$uuid]['nested'] ??= [];
        $saved_patterns[$uuid]['record'] = $record;
      }
    }
    return $saved_patterns;
  }

  /**
   * Apply callbacks for insertions, deletions, and updates between arrays.
   *
   * Compare two arrays by key and apply callback functions on each item based
   * on what changed between the array version. If a key is present in the new
   * array, but not in the old one, this is considered an insertion and the
   * insert callback is triggered. If a key is present in the old array, but not
   * the new one, this is considered a deletion and the delete callback is
   * triggered. If a key is present in both arrays and an update callback is
   * provided, the update callback is called with both items.
   *
   * @param array $original
   *   The array to be considered as the original version for comparison. This
   *   should be an associative array since the keys are being compared.
   * @param array $new
   *   The array to be considered as the new version for comparison. This should
   *   be an associative array since the keys are being compared.
   * @param callable $insert_callback
   *   A callback to be executed on items with a key present in the new array,
   *   but not the original. The callback will receive the following parameters:
   *   - The key of the value that was present in the new array, but not the old
   *     one.
   *   - The value of the entry present in the new array, but not the old one.
   * @param callable $delete_callback
   *   A callback to be executed on items with a key present in the old array,
   *   but not the new one. The callback will receive the following parameters:
   *   - The key of the value that was present in the old array, but not the new
   *     one.
   *   - The value of the entry present in the old array, but not the new one.
   * @param callable|null $update_callback
   *   (optional) A callback to be executed on items with a key present in both
   *   arrays. The callback will receive the following parameters:
   *   - The key present in both arrays.
   *   - The value of the entry from the original array.
   *   - The value of the entry from the new array.
   */
  public static function trackChanges(
    array $original,
    array $new,
    callable $insert_callback,
    callable $delete_callback,
    ?callable $update_callback = NULL,
  ): void {
    $insertions = array_diff_key($new, $original);
    foreach ($insertions as $key => $inserted) {
      call_user_func($insert_callback, $key, $inserted);
    }

    $deletions = array_diff_key($original, $new);
    foreach ($deletions as $key => $deleted) {
      call_user_func($delete_callback, $key, $deleted);
    }

    if ($update_callback) {
      $persistence = array_intersect_key($original, $new);
      foreach ($persistence as $key => $persisted) {
        call_user_func($update_callback, $key, $persisted, $new[$key]);
      }
    }
  }

  /**
   * Delete all usage records related to a component.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity containing the layout and component being deleted.
   * @param string $uuid
   *   The UUID for the layout component being deleted.
   */
  protected function deleteComponent(ContentEntityInterface $entity, string $uuid): void {
    $this->trackingStorage->deleteByEntityAndUuid($entity, $uuid);
  }

  /**
   * Update pattern usage records for existing components.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity containing the layout and component being updated.
   * @param string $uuid
   *   The UUID for the layout component being updated.
   * @param array{uuid: string, layout_path: string, pattern_id: string, nested: array<string, \Drupal\patternkit_usage_tracking\Entity\PatternUsage>, record: \Drupal\patternkit_usage_tracking\Entity\PatternUsage} $saved_component
   *   An associative array of previously saved usage records for the component
   *   being updated. Usage data is organized within the following keys:
   *   - uuid: The UUID of the containing component.
   *   - layout_path: The path to the component within the layout in the form
   *     `<section_delta>.<uuid>:<pattern_id>`.
   *   - pattern_id: The asset name of the pattern contained in the component.
   *   - nested: An array of all loaded pattern usage records nested within the
   *     component. Each nested record is keyed by the ID of the nested pattern.
   *   - record: The usage record for the top-level pattern of the component.
   * @param array{uuid: string, layout_path: string, pattern_id: string, nested: string[]} $edited_component
   *   An associative array of discovered pattern usages within the component
   *   being updated. The array organizes discovered usage data within the
   *   following keys:
   *   - uuid: The UUID of the containing component.
   *   - layout_path: The path to the component within the layout in the form
   *     `<section_delta>.<uuid>:<pattern_id>`.
   *   - pattern_id: The asset name of the pattern contained in the component.
   *   - nested: An array of the pattern IDs for all pattern usages detected
   *     within the content.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateComponent(ContentEntityInterface $entity, string $uuid, array $saved_component, array $edited_component): void {
    $record = &$saved_component['record'];

    // Did the layout path change, such as moving a block to a new section?
    if ($saved_component['layout_path'] !== $edited_component['layout_path']) {
      // Update the saved record to use the new layout path.
      $record->setPath($edited_component['layout_path']);
    }

    // Update the record to reflect the new revision.
    $record->set('source_revision_id', $entity->getRevisionId());
    $record->save();

    $layout_path = $edited_component['layout_path'];

    $insert_nested_pattern = $this->curryCallback([$this, 'insertNestedPattern'], $entity, $layout_path);
    $delete_nested_pattern = [$this, 'deleteNestedPattern'];
    $update_nested_pattern = $this->curryCallback([
      $this,
      'updateNestedPattern',
    ], $entity, $layout_path);

    // Track changes in nested patterns.
    static::trackChanges(
      $saved_component['nested'],
      array_flip($edited_component['nested']),
      $insert_nested_pattern,
      $delete_nested_pattern,
      $update_nested_pattern
    );
  }

  /**
   * Delete nested pattern usage records.
   *
   * @param string $nested_pattern_id
   *   The asset identifier for the nested pattern being deleted.
   * @param \Drupal\patternkit_usage_tracking\Entity\PatternUsage $saved_record
   *   The saved usage record for the previously saved nested pattern.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function deleteNestedPattern(string $nested_pattern_id, PatternUsage $saved_record): void {
    $saved_record->delete();
  }

  /**
   * Update usage records for a pattern nested within content.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity containing the layout and component for the pattern usage.
   * @param string $layout_path
   *   The path to the containing top-level component within the layout.
   * @param string $nested_pattern_id
   *   The asset identifier for the nested pattern being recorded.
   * @param \Drupal\patternkit_usage_tracking\Entity\PatternUsage $saved_record
   *   The loaded usage record to be updated.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateNestedPattern(ContentEntityInterface $entity, string $layout_path, string $nested_pattern_id, PatternUsage $saved_record): void {
    // Update the record to reflect the new revision ID.
    $saved_record->set('source_revision_id', $entity->getRevisionId());

    $usage_path = $layout_path . PatternUsage::NESTING_SEPARATOR . $nested_pattern_id;
    $saved_record->setPath($usage_path);
    $saved_record->save();
  }

  /**
   * Create an anonymous function with provided arguments prefixed.
   *
   * Create a curried callback with provided arguments prefixed to the function
   * call, so they are not needed in the context of later calls.
   *
   * @param callable $callback
   *   A callback function to be curried.
   * @param mixed ...$curried_arguments
   *   Arguments to be prefixed on the callback method call.
   *
   * @return callable
   *   An anonymous function calling the callable with all curried arguments
   *   followed by the arguments given to the method invocation.
   */
  protected function curryCallback(callable $callback, ...$curried_arguments): callable {
    return function (...$arguments) use ($callback, $curried_arguments) {
      return call_user_func($callback, ...$curried_arguments, ...$arguments);
    };
  }

  /**
   * Update usage records on related translations to point to new revision IDs.
   *
   * When the content of an entity translation is updated, the active revision
   * ID for all translations may have been changed leading to usage records in
   * layouts on those translations no longer pointing to the latest revision. In
   * this scenario, the revision IDs referenced in the related usage records
   * will need to be updated as well.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being updated with the affected translation loaded.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @todo Review interaction with moderation workflows and changes to revision
   *   IDs across translations.
   */
  protected function updateRelatedTranslations(ContentEntityInterface $entity): void {
    if ($entity->isTranslatable()) {
      $updated_language = $entity->language();
      $all_translations = array_keys($entity->getTranslationLanguages());
      $affected_translations = array_diff($all_translations, [$updated_language->getId()]);

      // Identify the new revision ID all existing records should be updated to
      // reference.
      $updated_revision_id = $entity->getRevisionId();

      foreach ($affected_translations as $langcode) {
        // Load existing usage records for the affected translation.
        $translated_entity = $entity->getTranslation($langcode);
        $records = $this->trackingStorage->loadByEntity($translated_entity);

        // Update the revision ID for each record.
        foreach ($records as $record) {
          $record->set('source_revision_id', $updated_revision_id);
          $record->save();
        }
      }
    }
  }

}
