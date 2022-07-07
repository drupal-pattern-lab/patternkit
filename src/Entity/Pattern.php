<?php

namespace Drupal\patternkit\Entity;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
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
   *   (Optional) An array of values to set, keyed by property name.
   *
   * @return static
   *   The Pattern instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function create(array $values = []): self {
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('patternkit_pattern');
    /** @var \Drupal\patternkit\Entity\Pattern $pattern */
    $pattern = $storage->create($values);

    return $pattern;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
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
   * {@inheritdoc}
   */
  public function getAssetId(): string {
    return '@' . $this->getLibrary() . '/' . $this->getPath();
  }

  /**
   * {@inheritdoc}
   *
   * Map fields do not have main properties, so the entire field must be used.
   *
   * Assets must have unique keys, so the array is returned collapsed.
   *
   * Asset groups can be used for override purposes, as they will replace lower
   * weighted assets.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|mixed
   *   A collection of all assets for the pattern instance.
   */
  public function getAssets(): array {
    return array_merge([], ...$this->get('assets')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory(): string {
    return $this->getEntityKey('category');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->getEntityKey('description');
  }

  /**
   * {@inheritdoc}
   */
  public function getHash(): string {
    return $this->getEntityKey('hash') ?? $this->computeHash();
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary(): string {
    return $this->getEntityKey('library');
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryPluginId(): ?string {
    return $this->getEntityKey('libraryPluginId');
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(): string {
    return $this->getEntityKey('path');
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema(): string {
    $schema = $this->getEntityKey('schema');
    if (!isset($schema)) {
      $this->fetchAssets();
    }
    return $this->getEntityKey('schema');
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate(): string {
    $template = $this->getEntityKey('template');
    if (!isset($template)) {
      $this->fetchAssets();
    }
    return $this->getEntityKey('template');
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion(): string {
    return $this->getEntityKey('version');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Should be a way to hook into ContentEntity lazy loading instead.
   */
  public function save(): int {
    $this->toArray();
    $this->computeHash();
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function setAssets(array $assets): self {
    return $this->set('assets', $assets);
  }

  /**
   * {@inheritdoc}
   */
  public function setLibraryPluginId(string $id): self {
    return $this->set('libraryPluginId', $id);
  }

  /**
   * {@inheritdoc}
   */
  public function setSchema($schema): self {
    assert(is_string($schema) || is_array($schema), 'The schema value is expected to be either a serialized JSON string or an array for serialization.');

    // Serialize the schema value if we were provided with an array.
    if (is_array($schema)) {
      // Encode the provided schema value and throw an exception if it fails for
      // any reason.
      $schema = json_encode($schema, JSON_THROW_ON_ERROR);
    }
    $value = $this->set('schema', $schema);
    $this->set('hash', NULL);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTemplate(string $template): self {
    $value = $this->set('template', $template);
    $this->set('hash', NULL);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVersion(string $version): self {
    return $this->set('version', $version);
  }

  /**
   * {@inheritdoc}
   */
  public function toArray(): array {
    foreach ($this->getEntityType()->getKeys() as $key => $value) {
      $getter = 'get' . ucfirst($key);
      if ($value === NULL && method_exists($this, $getter)) {
        $this->entityKeys[$key] = $this->$getter();
      }
    }
    return $this->entityKeys + parent::toArray();
  }

  /**
   * Compute the hash for this pattern.
   *
   * @return string
   *   The computed hash for this pattern.
   */
  protected function computeHash(): string {
    $hash = md5($this->getPath() . $this->getSchema() . $this->getTemplate());
    $this->set('hash', $hash);

    return $hash;
  }

  /**
   * Fetches the assets for the pattern based on the library plugin.
   *
   * Schema and Template are always stored as assets when fetched.
   *
   * @return array
   *   Assets for the pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function fetchAssets(): array {
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
        throw new PluginNotFoundException($plugin_id,
          "Unable to fetch assets for pattern " . $this->getPath());
      }
      $plugin = $plugin_manager->createInstance($plugin_id);
    }
    $assets = $plugin->fetchAssets($this);
    $this->setTemplate($assets['template'] ?? '');
    $this->setSchema($assets['schema'] ?? '');

    return $assets;
  }

}
