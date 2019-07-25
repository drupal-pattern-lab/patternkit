<?php

namespace Drupal\patternkit;

/**
 * Provides methods for parsing JSON schema.
 */
trait PatternLibraryJSONParserTrait {

  /**
   * Serializes and de-serializes data.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $serializer;

  /**
   * Parses schema properties for $ref and updates their location.
   *
   * @param object $properties
   *   The properties to parse.
   * @param array &$metadata
   *   The library metadata for looking up referenced patterns.
   *
   * @return object
   *   The updated schema properties object.
   */
  public static function schemaDereference($properties, array &$metadata) {
    foreach ($properties as $property => $value) {
      if (!is_scalar($value)) {
        $new_value = static::schemaDereference($value, $metadata);
        if (is_object($properties)) {
          $properties->{$property} = $new_value;
        }
        if (is_array($properties)) {
          $properties[$property] = $new_value;
        }
        continue;
      }
      if ($property !== '$ref') {
        continue;
      }
      $pattern = strstr($value, '.json', TRUE);
      $ref = substr($value, strlen("$pattern.json"));
      if (!isset($metadata[$pattern])) {
        unset($properties[$property]);
        continue;
      }
      $properties[$property] = $metadata[$pattern]->url . $ref;
    }
    return $properties;
  }

}
