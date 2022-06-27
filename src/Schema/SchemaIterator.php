<?php

namespace Drupal\patternkit\Schema;

use Swaggest\JsonSchema\SchemaContract;

/**
 * A recursive iterator implementation for traversing schemas and value trees.
 */
class SchemaIterator extends \RecursiveArrayIterator {

  /**
   * The schema definition for the values being traversed.
   *
   * @var \Swaggest\JsonSchema\SchemaContract
   */
  protected SchemaContract $schema;

  /**
   * The schema definition for the current property in iteration.
   *
   * @var \Swaggest\JsonSchema\SchemaContract|null
   */
  protected ?SchemaContract $propertySchema;

  /**
   * Whether the schema uses composition elements requiring more processing.
   *
   * @var bool
   */
  protected bool $isComposition;

  /**
   * Create a new SchemaIterator instance.
   *
   * @param \Swaggest\JsonSchema\SchemaContract $schema
   *   The schema definition for the values to be traversed.
   * @param array|object $values
   *   The collection of values to be traversed alongside the given schema.
   * @param int $flags
   *   Flags to influence how values for traversal should be handled. For
   *   details on options, view the inherited class constants.
   */
  public function __construct(SchemaContract $schema, $values, int $flags = 0) {
    parent::__construct($values, $flags);

    $this->schema = $schema;

    // Determine if this schema is using composition rules requiring testing
    // of values against multiple possible constraints.
    $this->isComposition = SchemaHelper::isCompositionSchema($schema);
  }

  /**
   * Get the schema being iterated.
   *
   * @return \Swaggest\JsonSchema\SchemaContract
   *   The schema being iterated.
   */
  public function getSchema(): SchemaContract {
    return $this->schema;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Expand to allow traversal into additional properties without schemas.
   */
  public function hasChildren(): bool {
    $result = FALSE;

    if (is_iterable($this->current())) {
      if ($this->getPropertySchema() !== NULL) {
        $result = TRUE;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren(): SchemaIterator {
    return new SchemaIterator($this->getPropertySchema(), $this->current());
  }

  /**
   * Get the schema definition for the current property in traversal.
   *
   * @return \Swaggest\JsonSchema\SchemaContract|null
   *   The loaded schema for the current property or NULL if one was unable to
   *   be identified.
   */
  public function getPropertySchema(): ?SchemaContract {
    // Return the current property schema if it's already been evaluated.
    if (isset($this->propertySchema)) {
      return $this->propertySchema;
    }

    if ($this->isComposition) {
      // Get the composed constraint for the current value.
      $this->propertySchema = SchemaHelper::getCompositionSchema($this->schema, $this->current());
    }
    else {
      // Try to access object properties first.
      if (in_array($this->key(), $this->schema->getPropertyNames())) {
        $this->propertySchema = $this->schema->getProperties()->{$this->key()};
      }
      elseif ($this->schema->type == 'array') {
        // Stop here if there is no supported specification for array items.
        if (!isset($this->schema->items) || $this->schema->items === FALSE) {
          // We don't currently handle any other array specifications aside from
          // item constraints. In this case, return the constraints for the
          // whole array.
          return $this->schema;
        }

        $itemSchema = $this->schema->items;

        // Is this a simple definition for all items?
        if (isset($itemSchema->type)) {
          $this->propertySchema = $itemSchema;
        }
        // Are the items a composition of constraints?
        elseif (SchemaHelper::isCompositionSchema($itemSchema)) {
          // Get the composed constraint for the current value.
          $this->propertySchema = SchemaHelper::getCompositionSchema($itemSchema, $this->current());
        }
      }
    }

    return $this->propertySchema ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    // Reset the property schema before accessing a new value.
    unset($this->propertySchema);

    parent::next();
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    // Reset the property schema before accessing a new value.
    unset($this->propertySchema);

    parent::rewind();
  }

  /**
   * {@inheritdoc}
   */
  public function seek($offset) {
    // Reset the property schema before accessing a new value.
    unset($this->propertySchema);

    parent::seek($offset);
  }

}
