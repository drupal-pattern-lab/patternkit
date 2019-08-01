<?php

namespace Drupal\patternkit\PatternLibraryParser;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\patternkit\PatternLibraryJSONParserTrait;
use Drupal\patternkit\PatternLibraryParserBase;

/**
 * Parses a Twig pattern library collection into usable metadata.
 */
class JSONPatternLibraryParser extends PatternLibraryParserBase {
  use PatternLibraryJSONParserTrait;

  /**
   * Attaches required services.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Serializes and de-serializes data.
   * @param string $root
   *   The application root path.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Allows modules to alter library parsing.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Allows themes to alter library parsing.
   */
  public function __construct(
    SerializationInterface $serializer,
    $root,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager) {

    $this->serializer = $serializer;
    parent::__construct($root, $module_handler, $theme_manager);
  }

  /**
   * Parses a given library file and allows modules and themes to alter it.
   *
   * @inheritDoc
   */
  public function parsePatternLibraryInfo(array $library, $path): array {
    if (!file_exists($path)) {
      throw new InvalidLibraryFileException("Path $path does not exist.");
    }
    $metadata = [];
    foreach (self::discoverComponents($path, ['json']) as $name => $data) {
      if (empty($data['json']) || !file_exists($data['json'])) {
        continue;
      }
      $category = $library['category'] ?? 'default';
      $library_defaults = [
        '$schema'    => 'http =>//json-schema.org/draft-04/schema#',
        'category'   => $category,
        'title'      => $name,
        'type'       => 'object',
        'format'     => 'grid',
        'license'    => $library['license'] ?? [],
        'name'       => $name,
        'properties' => (object) [],
        'required'   => [],
        'version'    => $library['version'] ?? '',
      ];
      if (!empty($data['json']) && $file_contents = file_get_contents($data['json'])) {
        $pattern = $this->createPattern($name, (array) $this->serializer::decode($file_contents) + $library_defaults);
        $pattern_path = trim(substr($data['json'], strlen($path), -strlen('.json')), '/\\');
        $category_guess = $library['category'] ?? strstr($pattern_path, DIRECTORY_SEPARATOR, TRUE);
        $pattern->category = $pattern->category ?? $category_guess;
      }
      else {
        // Create the pattern from defaults.
        // @todo Have this cleverly infer defaults from the template.
        $pattern = $this->createPattern($name, $library_defaults);
      }
      $pattern->filename = trim(substr($data['json'], strlen($path)), '/\\');
      $pattern->path = substr($pattern->filename, 0, -strlen('.json'));
      // @todo Set $pattern->url to the actual URL of the pattern.
      // @todo Add default of library version fallback to extension version.
      $pattern->version = $pattern->version ?? 'VERSION';
      $metadata['@' . $library['name'] . '/' . $pattern->path] = $pattern;
    }

    foreach ($metadata as $pattern_type => $pattern) {
      // Replace any $ref links with relative paths.
      if (!isset($pattern->schema['properties'])) {
        continue;
      }
      $pattern->schema['properties'] = static::schemaDereference(
        $pattern->schema['properties'],
        $metadata
      );
      $metadata[$pattern_type] = $pattern;
    }
    return $metadata;
  }

}
