<?php

namespace Drupal\patternkit\Entity;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the custom block entity class.
 *
 * @ContentEntityType(
 *   id = "patternkit_block",
 *   label = @Translation("Patternkit block"),
 *   label_collection = @Translation("Patternkit blocks"),
 *   label_singular = @Translation("Patternkit block"),
 *   label_plural = @Translation("Patternkit blocks"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Patternkit block",
 *     plural = "@count Patternkit blocks",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\patternkit\PatternAccessControlHandler",
 *     "list_builder" = "Drupal\patternkit\PatternListBuilder",
 *     "view_builder" = "Drupal\patternkit\PatternViewBuilder",
 *     "views_data" = "Drupal\patternkit\PatternViewsData",
 *     "form" = {
 *       "add" = "Drupal\patternkit\Form\PatternForm",
 *       "edit" = "Drupal\patternkit\Form\PatternForm",
 *       "delete" = "Drupal\patternkit\Form\PatternDeleteForm",
 *       "default" = "Drupal\patternkit\Form\PatternForm"
 *     },
 *     "translation" = "Drupal\patternkit\PatternTranslationHandler"
 *   },
 *   admin_permission = "administer blocks",
 *   base_table = "patternkit",
 *   revision_table = "patternkit_revision",
 *   data_table = "patternkit_field_data",
 *   revision_data_table = "patternkit_field_revision",
 *   show_revision_ui = TRUE,
 *   links = {
 *     "canonical" = "/block/{pattern}",
 *     "delete-form" = "/block/{pattern}/delete",
 *     "edit-form" = "/block/{pattern}",
 *     "collection" = "/admin/structure/block/patternkit",
 *     "create" = "/block",
 *   },
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "info",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *     "data" = "data",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log"
 *   },
 *   render_cache = FALSE,
 * )
 *
 * Note that render caching of block_content entities is disabled because they
 * are always rendered as blocks, and blocks already have their own render
 * caching.
 * See https://www.drupal.org/node/2284917#comment-9132521 for more information.
 */
class PatternkitBlock extends BlockContent {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = EditorialContentEntityBase::baseFieldDefinitions($entity_type);

    $fields['id']->setLabel(t('Custom block ID'))
      ->setDescription(t('The custom block ID.'));

    $fields['uuid']->setDescription(t('The custom block UUID.'));

    $fields['revision_id']->setDescription(t('The revision ID.'));

    $fields['langcode']->setDescription(t('The custom block language code.'));

    $fields['revision_log']->setDescription(t('The log entry explaining the changes in this revision.'));

    $fields['info'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Block description'))
      ->setDescription(t('A brief description of your block.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->addConstraint('UniqueField', []);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the custom block was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['data'] = BaseFieldDefinition::create('serialized_data')
      ->setLabel(t('Data'))
      ->setDescription(t('The patternkit block configuration data and content.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['reusable'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Reusable'))
      ->setDescription(t('A boolean indicating whether this block is reusable.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setDefaultValue(TRUE);

    return $fields;
  }

}
