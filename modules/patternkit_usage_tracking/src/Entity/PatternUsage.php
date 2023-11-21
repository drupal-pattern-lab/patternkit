<?php

namespace Drupal\patternkit_usage_tracking\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Webmozart\Assert\Assert;

/**
 * Defines the pattern_usage entity.
 *
 * @ContentEntityType(
 *   id = "pattern_usage",
 *   label = @Translation("Pattern usage"),
 *   base_table = "pattern_usage",
 *   translatable = FALSE,
 *   revisionable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "views_data" = "Drupal\patternkit_usage_tracking\PatternUsageViewsData",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 * )
 */
class PatternUsage extends ContentEntityBase {

  /**
   * A separator character splitting layout paths from content paths.
   */
  const PATH_SEPARATOR = ':';

  /**
   * A separator splitting reference relationships in content paths.
   */
  const NESTING_SEPARATOR = '=>';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['source_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source entity type'))
      ->setDescription(t('The type of the source entity.'))
      ->setRequired(TRUE);

    $fields['source_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Source entity ID'))
      ->setDescription(t('The ID of the source entity.'))
      ->setRequired(TRUE);

    $fields['source_revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Source entity revision ID'))
      ->setDescription(t('The revision ID of the source entity.'))
      ->setRequired(TRUE);

    $fields['source_langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Entity language code'))
      ->setDescription(t('The langcode of the source entity.'))
      ->setRequired(TRUE);

    $fields['pattern_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pattern asset name'))
      ->setDescription(t('The asset name of the pattern being tracked.'))
      ->setRequired(TRUE);

    $fields['path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pattern location path'))
      ->setDescription(t('A path to the pattern usage location within a layout and/or parent pattern.'))
      ->setRequired(TRUE);

    // Note: The 'uuid' field item type cannot be used here since it enforces a
    // uniqueness requirement on the value that is not usable in a foreign-key
    // context like this.
    $fields['component_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Component UUID'))
      ->setDescription(t('The UUID of the containing component.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSettings([
        'max_length' => 128,
        'is_ascii' => TRUE,
        'case_sensitive' => FALSE,
      ])
      ->setDefaultValueCallback(static::class . '::getComponentUuidDefaultValue');

    return $fields;
  }

  /**
   * Get the layout path from a usage record.
   *
   * @return string
   *   The path within a layout to the containing component for this record in
   *   the format `<section_id>.<component_uuid>`.
   */
  public function getLayoutPath(): string {
    $path = $this->get('path')->value;
    return strstr($path, (string) static::PATH_SEPARATOR, TRUE) ?: $path;
  }

  /**
   * Test if a record tracks a pattern nested in another component's content.
   *
   * @return bool
   *   TRUE if the usage path points to a location within a component's content,
   *   FALSE otherwise.
   */
  public function isNested(): bool {
    $path = $this->get('path')->value;

    // Test whether the path contains a separator splitting pattern references.
    return str_contains($path, static::NESTING_SEPARATOR);
  }

  /**
   * Get the content path for a usage record if one is available.
   *
   * Un-nested patterns will return only the top-level pattern name, but nested
   * patterns will include the top-level pattern and the referenced pattern
   * separated by '=>'.
   *
   * @return string
   *   The content path for the usage record or NULL if one is not defined.
   *   Example: @patternkit/atoms/example_ref/src/example_ref=>@patternkit/atoms/example/src/example
   */
  public function getContentPath(): string {
    $path = $this->get('path')->value;

    [, $content_path] = explode(static::PATH_SEPARATOR, $path, 2);
    return $content_path;
  }

  /**
   * Split a usage path into component parts.
   *
   * @return array{section: numeric-string, uuid: string, content_path: string, top_pattern: string, nested_pattern?: string}
   *   An associative array of the component parts of the entity's usage path
   *   value. The returned array will contain the following keys:
   *   - section: The section identifier.
   *   - uuid: The top-level component's UUID.
   *   - content_path: The pattern reference path within content. This will
   *     always include the top-level pattern, but in the case of nested
   *     references it may also include a reference to the sub-pattern.
   *   - top_pattern: The pattern name of the top-level pattern.
   *   - nested_pattern: If available, the name of the nested pattern being
   *     referenced.
   */
  public function parseUsagePath(): array {
    $path = $this->get('path')->value;

    $matches = [];
    preg_match("/^(?'section'\d+)\.(?'uuid'[a-zA-Z\d-]+):(?'content_path'(?'top_pattern'[^=\s]+)(=>(?'nested_pattern'\S+))?)$/", $path, $matches);

    // Filter only the keyed matches to return.
    $match_keys = [
      'section',
      'uuid',
      'content_path',
      'top_pattern',
      'nested_pattern',
    ];
    return array_intersect_key($matches, array_flip($match_keys));
  }

  /**
   * Set a path value on the entity.
   *
   * Note: Since the path contains the top-level component's UUID which should
   * not change, this method assumes the new path will also contain the same
   * UUID value. This will help to keep data consistency with the read-only
   * value set in the 'component_uuid' field.
   *
   * @param string $path
   *   The path value to set on the entity.
   *
   * @throws \Webmozart\Assert\InvalidArgumentException
   */
  public function setPath(string $path): void {
    Assert::contains($path, $this->get('component_uuid')->value, 'The UUID of a component should not be changed.');
    $this->set('path', $path);
  }

  /**
   * Parse the component UUID value from the layout path.
   *
   * This method is used to parse the component UUID from the initialized
   * layout path and set it as the default value of the entity on creation.
   *
   * @param \Drupal\patternkit_usage_tracking\Entity\PatternUsage $entity
   *   The entity being created.
   * @param \Drupal\Core\Field\BaseFieldDefinition $fieldDefinition
   *   The field definition for the 'component_uuid' field.
   *
   * @return string
   *   The UUID value for assignment as the default value.
   *
   * @see self::baseFieldDefinitions()
   *
   * @internal
   */
  public static function getComponentUuidDefaultValue(PatternUsage $entity, BaseFieldDefinition $fieldDefinition): string {
    [, $uuid] = explode('.', $entity->getLayoutPath());
    return $uuid;
  }

}
