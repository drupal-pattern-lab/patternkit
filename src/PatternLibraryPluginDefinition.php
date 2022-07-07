<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\Definition\DerivablePluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionTrait;

/**
 * Provides an implementation of a definition and its metadata.
 */
class PatternLibraryPluginDefinition extends PluginDefinition implements DerivablePluginDefinitionInterface, DependentPluginDefinitionInterface {

  use DependentPluginDefinitionTrait;

  /**
   * The name of the deriver of this definition, if any.
   *
   * @var string|null
   */
  protected ?string $deriver = NULL;

  /**
   * The human-readable name.
   *
   * @var string|null
   */
  protected ?string $label = NULL;

  /**
   * An optional description.
   *
   * @var string|null
   */
  protected ?string $description = NULL;

  /**
   * The human-readable category.
   *
   * @var string|null
   */
  protected ?string $category = NULL;

  /**
   * Any additional properties and values.
   *
   * @var array
   */
  protected array $additional = [];

  /**
   * Constructor.
   *
   * @param array $definition
   *   Provide an array of values from the annotation.
   */
  public function __construct(array $definition) {
    foreach ($definition as $property => $value) {
      $this->set($property, $value);
    }
  }

  /**
   * Gets any arbitrary property.
   *
   * @param string $property
   *   The property to retrieve.
   *
   * @return mixed
   *   The value for that property, or NULL if the property does not exist.
   */
  public function get(string $property) {
    if (property_exists($this, $property)) {
      $value = $this->{$property} ?? NULL;
    }
    else {
      $value = $this->additional[$property] ?? NULL;
    }
    return $value;
  }

  /**
   * Sets a value to an arbitrary property.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function set(string $property, $value): self {
    if (property_exists($this, $property)) {
      $this->{$property} = $value;
    }
    else {
      $this->additional[$property] = $value;
    }
    return $this;
  }

  /**
   * Gets the human-readable name of the definition.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The human-readable name of the definition.
   */
  public function getLabel(): ?string {
    return $this->label;
  }

  /**
   * Sets the human-readable name of the definition.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the definition.
   *
   * @return $this
   */
  public function setLabel($label): self {
    $this->label = $label;
    return $this;
  }

  /**
   * Gets the description of the definition.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The description of the definition.
   */
  public function getDescription(): ?string {
    return $this->description;
  }

  /**
   * Sets the description of the definition.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The description of the definition.
   *
   * @return $this
   */
  public function setDescription($description): self {
    $this->description = $description;
    return $this;
  }

  /**
   * Gets the human-readable category of the definition.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The human-readable category of the definition.
   */
  public function getCategory(): ?string {
    return $this->category;
  }

  /**
   * Sets the human-readable category of the definition.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $category
   *   The human-readable category of the definition.
   *
   * @return $this
   */
  public function setCategory($category): self {
    $this->category = $category;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeriver(): ?string {
    return $this->deriver;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeriver($deriver): self {
    $this->deriver = $deriver;
    return $this;
  }

}
