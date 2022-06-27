<?php

namespace Drupal\patternkit\Schema;

use Swaggest\JsonSchema\SchemaContract;

/**
 * An iterator for traversing recursively through a SchemaIterator.
 *
 * @see \Drupal\patternkit\Schema\SchemaIterator
 */
class SchemaWalker extends \RecursiveIteratorIterator {

  /**
   * The schema for reference during traversal.
   *
   * @var \Swaggest\JsonSchema\SchemaContract
   */
  protected SchemaContract $schema;

  /**
   * Create a new SchemaWalker instance.
   *
   * @param \Swaggest\JsonSchema\SchemaContract $schema
   *   The schema for reference during traversal with values.
   * @param array|object $values
   *   The values to be used to traverse the schema.
   * @param int $mode
   *   Configuration options to influence how the values are traversed. For
   *   more details, review the inherited class constants.
   * @param int $flags
   *   Flags to manipulate handling of values being traversed. For more details,
   *   see class constants on \RecursiveArrayIterator.
   */
  public function __construct(SchemaContract $schema, $values, int $mode = self::SELF_FIRST, int $flags = 0) {
    $this->schema = $schema;

    $schemaIterator = new SchemaIterator($schema, $values, $flags);
    parent::__construct($schemaIterator, $mode, $flags);
  }

  /**
   * Get the top-level schema being traversed.
   *
   * @return \Swaggest\JsonSchema\SchemaContract
   *   The schema definition being traversed.
   */
  public function getSchema(): SchemaContract {
    return $this->schema;
  }

  /**
   * Get the schema for the current property in traversal.
   *
   * @return \Swaggest\JsonSchema\SchemaContract|null
   *   The loaded schema for the current property or NULL if one was unable to
   *   be identified.
   */
  public function getPropertySchema(): ?SchemaContract {
    /** @var \Drupal\patternkit\Schema\SchemaIterator $iterator */
    $iterator = $this->getSubIterator();

    if ($iterator) {
      return $iterator->getPropertySchema();
    }
    else {
      return NULL;
    }
  }

  /**
   * Set the current key to the given value and propagate it up the tree.
   *
   * @param mixed $value
   *   The value to be set at the current iteration key.
   */
  public function setCurrentValue($value): void {
    // Get the current depth and traverse back up the tree, saving the
    // modifications at each level along the way.
    $currentDepth = $this->getDepth();
    for ($subDepth = $currentDepth; $subDepth >= 0; $subDepth--) {
      // Get the current level iterator.
      /** @var \Drupal\patternkit\Schema\SchemaIterator $subIterator */
      $subIterator = $this->getSubIterator($subDepth);
      // If we are on the level we want to change, use the replacement ($value),
      // otherwise set the key to the parent iterator's value.
      $subIterator->offsetSet($subIterator->key(),
        ($subDepth === $currentDepth ? $value : $this->getSubIterator(($subDepth + 1))->getArrayCopy()));
    }
  }

}
