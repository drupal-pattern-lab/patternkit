<?php

namespace Drupal\patternkit\Schema;

use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

/**
 * A schema implementation to process schemas without resolving reference links.
 *
 * For more detail on the need for this as a custom class, see the
 * ::disableAutomaticReferences method.
 *
 * Following import of a schema using this class, references may be resolved
 * into a completed schema using the ::resolve() method as follows:
 *
 * @code
 * $unresolvedSchema = UnresolvedSchema::import(json_decode($schemaJson));
 * $resolvedSchema = $unresolvedSchema->resolve();
 * @endcode
 *
 * @see https://github.com/swaggest/php-json-schema/issues/145
 * @see \Drupal\patternkit\Schema\UnresolvedSchema::disableAutomaticDereferencing()
 * @see \Drupal\patternkit\Schema\UnresolvedSchema::resolve()
 *
 * @todo Remove this class once swaggest/php-json-schema#145 is resolved.
 */
class UnresolvedSchema extends Schema {

  /**
   * Import data into the schema.
   *
   * If $options->dereference is set to FALSE, per the default, references will
   * not be resolved.
   *
   * @param mixed $data
   *   Schema data to be imported.
   * @param \Swaggest\JsonSchema\Context $options
   *   Processing options for the import operation.
   *
   * @return static|mixed
   *   The imported JSON schema object.
   *
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public static function import($data, Context $options = NULL) {
    if ($options === NULL) {
      $options = new Context();
    }

    // Override class mapping to ensure result objects are an instance of this
    // class.
    $options->objectItemClassMapping[Schema::className()] = UnresolvedSchema::className();

    if (!$options->dereference) {
      $reset = static::disableAutomaticDereferencing();
    }

    $result = parent::import($data, $options);

    if (isset($reset)) {
      $reset();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function setUpProperties($properties, Schema $ownerSchema) {
    parent::setUpProperties($properties, $ownerSchema);

    // Unset the format to disable automatic imports in single-level objects.
    $properties->ref->format = NULL;
  }

  /**
   * A utility function to prevent automatic resolution of schema references.
   *
   * Due to the issue documented in
   * @link https://github.com/swaggest/php-json-schema/issues/145 swaggest/php-json-schema#145 @endlink,
   * the dereference option is ignored and reference links are automatically
   * resolved. This function temporarily alters the schema definition driving
   * that decision, and returns a callback function to restore the change after
   * operations have completed.
   *
   * If the callback function is not executed, further schema import operations
   * within the same request, not just using this class, will not resolve
   * references.
   *
   * @return callable
   *   A reset function to restore default schema handling for reference links.
   *   No arguments are required for the callback function.
   *
   * @see https://github.com/swaggest/php-json-schema/issues/145
   */
  protected static function disableAutomaticDereferencing(): callable {
    $schema = Schema::schema();
    $refProperty = $schema->getProperties()->ref;
    $originalFormat = $refProperty->format;
    $refProperty->format = NULL;

    return function () use ($originalFormat) {
      $schema = Schema::schema();
      $refProperty = $schema->getProperties()->ref;
      $refProperty->format = $originalFormat;
    };
  }

  /**
   * Reprocess the imported schema to resolve schema references.
   *
   * @param \Swaggest\JsonSchema\Context|null $options
   *   Contextual options to influence schema processing.
   *
   * @return \Swaggest\JsonSchema\SchemaContract
   *   The resulting schema after import.
   *
   * @throws \Swaggest\JsonSchema\Exception
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public function resolve(Context $options = NULL): SchemaContract {
    if ($options === NULL) {
      $options = new Context();
    }

    $options->dereference = TRUE;

    // Re-encode the JSON to ensure serialization is completed recursively.
    $schema_data = json_encode($this);

    return Schema::import(json_decode($schema_data), $options);
  }

  /**
   * Alter serialization to return references instead of resolving them.
   *
   * When a section of the schema is encountered that was resolved from a
   * reference, return the reference instead of continuing to traverse into it
   * and serialize the content of it in place. This allows for references to
   * leverage locally bundled contents and avoid succumbing to circular
   * references.
   *
   * @return object
   *   The object representation of the interpreted schema content.
   */
  public function jsonSerialize(): object {
    if (is_array($refs = $this->getFromRefs())) {
      return (object) [
        '$ref' => $refs[0],
      ];
    }
    else {
      return parent::jsonSerialize();
    }
  }

}
