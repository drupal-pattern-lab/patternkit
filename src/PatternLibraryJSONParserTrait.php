<?php

namespace Drupal\patternkit;

use Drupal\patternkit\Entity\PatternInterface;

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
   * Fetches a JSON Schema from a file URI.
   *
   * @param string $file_uri
   *   URI of the JSON file to decode.
   *
   * @return mixed
   *   Truthy mixed data if the contents are successfully decoded.
   */
  public function fetchJsonSchemaAsset($file_uri) {
    return $this->serializer::decode(file_get_contents($file_uri));
  }

  /**
   * Parses schema properties for $ref and updates their location.
   *
   * @param object $properties
   *   The properties to parse.
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   Pattern for retrieving schema data and pathing.
   *
   * @return object
   *   The updated schema properties object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @example JSON Schema technically allows the following valid refs:
   *   - URI "example.json" "../../example.json" "./example.json"
   *   - URL "https://json-schema.org/person.schema.json"
   *   - JSON Pointer "#/definitions/example" "example.json#/example"
   *   Additionally we support Twig namespaces:
   *   - Twig NS "@mylibrary/example" "@mylibrary/category/example.json"
   *   - ... and a combination of some of the above.
   *   - "@mylibrary/category/example.json#/definitions/example"
   *   - "./../../../test.json"
   */
  public static function schemaDereference($properties, PatternInterface $pattern) {
    $ext = '.json';
    $ext_len = strlen($ext);
    /** @var \Drupal\patternkit\Asset\Library $library */
    $library = \Drupal::service('patternkit.asset.library');
    foreach ($properties as $property => $value) {
      if (!is_scalar($value)) {
        $new_value = static::schemaDereference($value, $pattern);
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
      if (strpos($value, '#/') === 0) {
        continue;
      }
      /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_manager */
      $stream_manager = \Drupal::service('stream_wrapper_manager');
      if ($stream_manager->isValidScheme($stream_manager->getViaUri($value))) {
        continue;
      }
      $path = $value;
      if (strpos($value, '@') === 0) {
        $library_namespace = strstr($value, '/', TRUE);
        $path = substr($value, strlen("$library_namespace/"));
      }
      $ref = '';
      if (strpos($path, '#/') !== FALSE) {
        $ref = explode('#/', $path);
        $path = reset($ref);
        $ref = !empty($ref[1]) ? '#/' . $ref[1] : '';
      }
      $library_name = $pattern->getLibrary();
      // Since schema paths can be relative, we loop through each possible
      // pattern library path to locate the schema, with overrides allowed.
      if (strpos($path, './') !== FALSE) {
        $pattern_library = $library->getLibraryDefinitions()[ltrim($library_name, '@')];
        $library_path = $pattern_library->getExtension()
          ->getPath();
        $realpath = $path;
        foreach ($pattern_library->getPatternInfo() as $info) {
          if (!isset($info['data'])) {
            continue;
          }
          $library_path = $info['data'] ?? $library_path;
          $realpath = realpath($library_path . '/' . $path) ?? $realpath;
        }
        $path = substr(str_replace('\\', '/', $realpath), strlen(\Drupal::root() . '/' . $library_path . '/'));
      }
      $path_no_ext = strripos($path, $ext) === strlen($path) - $ext_len ? substr($path, 0, -$ext_len) : $path;
      $path_encoded = [];
      foreach (explode('/', $path_no_ext) as $path_parts) {
        $path_encoded[] = urlencode($path_parts);
      }
      $path_encoded = implode('/', $path_encoded);
      $properties[$property] = '/api/patternkit/'
        . urlencode(ltrim($library_namespace ?? $library_name, '@'))
        . '/'
        . $path_encoded
        . '?asset=schema'
        . $ref;
    }
    return $properties;
  }

}
