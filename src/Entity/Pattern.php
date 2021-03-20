<?php

namespace Drupal\patternkit\Entity;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Pattern entity type which stores pattern metadata in Drupal.
 *
 * @ContentEntityType(
 *   id = "patternkit_pattern",
 *   label = @Translation("Pattern"),
 *   label_collection = @Translation("Pattern Libraries"),
 *   label_plural =@Translation("Patterns"),
 *   label_singular = @Translation("Pattern"),
 *   label_count = @PluralTranslation(
 *     singular = "@count patterns",
 *     plural = "@count custom blocks"
 *   ),
 *   admin_permission = "administer blocks",
 *   base_table = "pattern",
 *   revision_table = "pattern_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "hash" = "hash",
 *     "assets" = "assets",
 *     "category" = "category",
 *     "label" = "name",
 *     "library" = "library",
 *     "libraryPluginId" = "libraryPluginId",
 *     "path" = "path",
 *     "revision" = "revision",
 *     "schema" = "schema",
 *     "template" = "template",
 *     "uuid" = "uuid",
 *     "version" = "version",
 *   },
 *   revision_metadata_keys = {
 *    "revision_default" = "revision_default",
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\patternkit\Entity\PatternRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   links = {
 *     "canonical" = "/patternkit/{patternkit_pattern}",
 *     "collection" = "/admin/structure/block/patternkit",
 *   },
 *   translatable = FALSE
 * )
 *
 * @package Drupal\patternkit\Entity
 */
class Pattern extends ContentEntityBase implements PatternInterface {

  /**
   * Constructs a new Pattern object, without permanently saving it.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   *
   * @return static
   *   The Pattern object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function create(array $values = []) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('patternkit_pattern');
    /** @var Pattern $pattern */
    $pattern = $storage->create($values);
    return $pattern;
  }

  /**
   * @inheritDoc
   *
   * @see \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setCardinality(1);
    $fields['name'] = BaseFieldDefinition::create('text')
      ->setLabel(t('Name'))
      ->setCardinality(1);
    $fields['assets'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Assets'))
      ->setDefaultValue([]);
    $fields['category'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Category'))
      ->setCardinality(1)
      ->setDefaultValue('default');
    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('description'))
      ->setCardinality(1)
      ->setDefaultValue('');
    $fields['library'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Library'))
      ->setCardinality(1);
    $fields['libraryPluginId'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Library Plugin ID'))
      ->setCardinality(1);
    $fields['path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Path'))
      ->setCardinality(1);
    $fields['schema'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Schema'))
      ->setCardinality(1);
    $fields['template'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Template'))
      ->setCardinality(1);
    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version'))
      ->setCardinality(1);
    return $fields;
  }

  /**
   * Provides an asset ID in library/path namespace format.
   *
   * @return string
   *   ID string in the format:
   *     @code @library.name/path/to/pattern @endcode
   */
  public function getAssetId() {
    return '@' . $this->getLibrary() . '/' . $this->getPath();
  }

  /**
   * Map fields do not have main properties, so the entire field must be used.
   *
   * Assets must have unique keys, so the array is returned collapsed.
   *
   * Asset groups can be used for override purposes, as they will replace lower
   * weighted assets.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|mixed
   */
  public function getAssets() {
    return array_merge([], ...$this->get('assets')->getValue());
  }

  public function getCategory() {
    return $this->getEntityKey('category');
  }

  public function getDescription() {
    return $this->getEntityKey('description');
  }

  public function getHash() {
    return $this->getEntityKey('hash') ?? $this->computeHash();
  }

  public function getLibrary() {
    return $this->getEntityKey('library');
  }

  public function getLibraryPluginId() {
    return $this->getEntityKey('libraryPluginId');
  }

  public function getName() {
    return $this->label();
  }

  public function getPath() {
    return $this->getEntityKey('path');
  }

  public function getSchema() {
    $schema = $this->getEntityKey('schema');
    if (!isset($schema)) {
      $this->fetchAssets();
      $schema = $this->getAssets()['schema'] ?? [];
      $this->setSchema($schema);
    }
    return $schema;
  }

  public function getTemplate() {
    $template = $this->getEntityKey('template');
    if (!isset($template)) {
      $this->fetchAssets();
      $template = $this->getAssets()['template'] ?? [];
      $this->setTemplate($template);
    }
    return $template;
  }

  public function getVersion() {
    return $this->getEntityKey('version');
  }

  /**
   * Calls all getters for lazy-loaded values.
   *
   * @return int
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @todo Should be a way to hook into ContentEntity lazy loading instead.
   */
  public function save() {
    $this->toArray();
    $this->computeHash();
    return parent::save();
  }

  public function setAssets($assets) {
    return $this->set('assets', $assets);
  }

  public function setLibraryPluginId($id) {
    return $this->set('libraryPluginId', $id);
  }

  public function setSchema($schema) {
    $value = $this->set('schema', $schema);
    $this->set('hash', NULL);
    return $value;
  }

  public function setTemplate($template) {
    $value = $this->set('template', $template);
    $this->set('hash', NULL);
    return $value;
  }

  public function setVersion($version) {
    return $this->set('version', $version);
  }

  public function toArray() {
    foreach ($this->getEntityType()->getKeys() as $key => $value) {
      $getter = 'get' . ucfirst($key);
      if ($value === NULL && method_exists($this, $getter)) {
        $this->entityKeys[$key] = $this->$getter();
      }
    }
    return $this->entityKeys + parent::toArray();
  }

  protected function computeHash() {
    $hash = md5($this->getPath() . $this->getSchema() . $this->getTemplate());
    $this->set('hash', $hash);
    return $hash;
  }

  /**
   * Fetches the assets for the pattern based on the library plugin.
   *
   * Schema and Template are always stored as assets when fetched.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function fetchAssets() {
    $plugin_id = $this->getLibraryPluginId();
    /** @var \Drupal\patternkit\PatternLibraryPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.library.pattern');
    /** @var \Drupal\patternkit\PatternLibraryPluginInterface $plugin */
    try {
      $plugin = $plugin_manager->createInstance($plugin_id);
    }
    catch (PluginException $exception) {
      // Allow plugin fall-backs of type 'base_plugin.override_plugin'.
      $plugin_id = strstr($plugin_id, '.', TRUE);
      if (empty($plugin_id)) {
        throw new PluginNotFoundException($plugin_id);
      }
      $plugin = $plugin_manager->createInstance($plugin_id);
    }
    $this->setAssets($plugin->fetchAssets($this));
    $assets = $this->getAssets();
    $this->setTemplate($assets['template']);
    $this->setSchema($assets['schema']);
  }
}
