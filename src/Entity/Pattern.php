<?php

namespace Drupal\patternkit\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\patternkit\Exception\PatternMismatchException;
use Drupal\patternkit\PatternLibraryPluginInterface;

/**
 * Defines the Pattern entity type which stores pattern metadata in Drupal.
 *
 * @ContentEntityType(
 *   id = "patternkit_pattern",
 *   label = @Translation("Pattern"),
 *   label_collection = @Translation("Pattern Libraries"),
 *   label_plural = @Translation("Patterns"),
 *   label_singular = @Translation("Pattern"),
 *   label_count = @PluralTranslation(
 *     singular = "@count pattern",
 *     plural = "@count patterns"
 *   ),
 *   admin_permission = "administer blocks",
 *   base_table = "pattern",
 *   revision_table = "pattern_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "assets" = "assets",
 *     "category" = "category",
 *     "label" = "name",
 *     "library" = "library",
 *     "libraryPluginId" = "libraryPluginId",
 *     "path" = "path",
 *     "revision" = "revision",
 *     "uuid" = "uuid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_default" = "revision_default",
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
 *   translatable = FALSE,
 * )
 *
 * @package Drupal\patternkit\Entity
 */
class Pattern extends ContentEntityBase implements PatternInterface {

  /**
   * A flag tracking whether schema dependencies have been bundled.
   *
   * @var bool
   */
  protected bool $bundled = FALSE;

  /**
   * An array of the current Pattern instance's configuration values.
   *
   * @var array
   */
  public array $config = [];

  /**
   * An array of the current Pattern instance's contexts.
   *
   * @var array
   */
  public array $context = [];

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
  public static function create(array $values = []): static {
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('patternkit_pattern');
    /** @var \Drupal\patternkit\Entity\Pattern $pattern */
    $pattern = $storage->create($values);

    // @phpstan-ignore return.type
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
      ->setCardinality(1)
      ->setRevisionable(TRUE);
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
      ->setCardinality(1)
      ->setRevisionable(TRUE);
    $fields['template'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Template'))
      ->setCardinality(1)
      ->setRevisionable(TRUE);
    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version'))
      ->setCardinality(1)
      ->setRevisionable(TRUE);
    $fields['dependencies'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dependencies'))
      ->setDescription(t('A collection of pattern reference dependencies detected within the Pattern.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

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
   * @return array
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
    return $this->getEntityKey('description') ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHash(): string {
    $item = $this->get('hash')->first();
    return $item ? $item->getValue()['value'] : $this->computeHash();
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
    $item = $this->get('schema')->first();
    if (empty($item)) {
      $this->fetchAssets();
      $item = $this->get('schema')->first();
    }
    return $item ? $item->getValue()['value'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate(): string {
    $item = $this->get('template')->first();
    if (empty($item)) {
      $this->fetchAssets();
      $item = $this->get('template')->first();
    }
    return $item ? $item->getValue()['value'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion(): string {
    $item = $this->get('version')->first();
    return $item ? $item->getValue()['value'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(): array {
    $values = $this->get('dependencies')->getValue() ?? [];
    return array_map(fn ($item) => $item['value'], $values);
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
  public function isBundled(): bool {
    return $this->bundled;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundled(bool $bundled = TRUE): void {
    $this->bundled = $bundled;
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
    assert(is_string($schema) || is_array($schema) || is_object($schema), 'The schema value is expected to be either a serialized JSON string or serializable array or object.');

    // Serialize the schema value if we were provided with an array.
    if (!is_string($schema)) {
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
  public function setDependencies(array $dependencies): self {
    return $this->set('dependencies', $dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function updateFromPattern(PatternInterface $pattern): self {
    if ($this->getAssetId() !== $pattern->getAssetId()) {
      $message = sprintf('Unable to update pattern %s with values from pattern %s.',
        $this->getAssetId(), $pattern->getAssetId());
      throw new PatternMismatchException($message);
    }

    // Set values from the source pattern.
    $this->setSchema($pattern->getSchema());
    $this->setTemplate($pattern->getTemplate());
    $this->setVersion($pattern->getVersion());
    $this->setDependencies($pattern->getDependencies());
    $this->setBundled($pattern->isBundled());

    return $this;
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
   * @throws \Drupal\patternkit\Exception\PatternAssetException
   * @throws \JsonException
   *
   * @internal
   * NOTE: This method is not supported for external use or backward-
   * compatibility commitments.
   */
  public function fetchAssets(): array {
    $plugin = $this->getLibraryPlugin();
    $assets = $plugin->fetchAssets($this);
    $this->setTemplate($assets['template'] ?? '');
    $this->setSchema($assets['schema'] ?? '');

    return $assets;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if (empty($this->cacheTags)) {
      $this->cacheTags[] = 'pattern:' . $this->getAssetId();
    }

    return parent::getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    if ($this->isNew() || !$this->isDefaultRevision()) {
      return [];
    }
    return [
      $this->entityTypeId . ':' . $this->id(),
      'pattern:' . $this->getAssetId(),
    ];
  }

  /**
   * Load the pattern library plugin used by this pattern.
   *
   * @return \Drupal\patternkit\PatternLibraryPluginInterface
   *   The loaded pattern library plugin for this pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLibraryPlugin(): PatternLibraryPluginInterface {
    /** @var \Drupal\patternkit\PatternLibraryPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.library.pattern');
    return $plugin_manager->getPluginForPattern($this);
  }

}
