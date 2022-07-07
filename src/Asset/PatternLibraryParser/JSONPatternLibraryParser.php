<?php

namespace Drupal\patternkit\Asset\PatternLibraryParser;

use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryJSONParserTrait;
use Drupal\patternkit\Asset\PatternLibraryParserBase;

/**
 * Parses a Twig pattern library collection into usable metadata.
 */
class JSONPatternLibraryParser extends PatternLibraryParserBase {

  use PatternLibraryJSONParserTrait;

  /**
   * {@inheritdoc}
   */
  public function parsePatternLibraryInfo(PatternLibrary $library, string $path): array {
    if (!file_exists($path)) {
      throw new InvalidLibraryFileException("Path $path does not exist.");
    }
    $metadata = [];
    $info = $library->getPatternInfo();
    if ($path[0] === '/') {
      // Trim the root path plus the trailing slash.
      $relative_path = substr($path, strlen($this->root . '/'));
    }
    else {
      $relative_path = $path;
    }
    $pattern_info = $info[$relative_path];
    foreach (self::discoverComponents($path, ['json']) as $name => $data) {
      if (empty($data['json']) || !file_exists($data['json'])) {
        continue;
      }
      $category = $pattern_info['category'] ?? 'default';
      $library_defaults = [
        '$schema' => 'https://json-schema.org/schema#',
        'assets' => ['schema' => $data['json']],
        'category' => $category,
        'title' => $name,
        'type' => 'object',
        'format' => 'grid',
        'library' => $library->id(),
        'libraryPluginId' => $pattern_info['plugin'],
        'license' => $library->license ?? [],
        'name' => $name,
        'properties' => (object) [],
        'required' => [],
        'version' => $library->version ?? '',
      ];
      if (!empty($data['json']) && $file_contents = file_get_contents($data['json'])) {
        $pattern = $this->createPattern($name, (array) $this->serializer::decode($file_contents) + $library_defaults);
        $pattern_path = trim(substr($data['json'], strlen($path), -strlen('.json')), '/\\');
        $category_guess = $library->category ?? strstr($pattern_path, DIRECTORY_SEPARATOR, TRUE);
        $pattern->category = $pattern->get('category')
          ->getValue() ?? $category_guess;
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
      $pattern->version ??= 'VERSION';
      $metadata[$pattern->getPath()] = $pattern;
    }

    foreach ($metadata as $pattern_type => $pattern) {
      // Replace any $ref links with relative paths.
      $schema = json_decode($pattern->getSchema(), TRUE);
      $schema = static::schemaDereference(
        $schema,
        $pattern
      );
      $pattern->setSchema($schema);
      $metadata[$pattern_type] = $pattern->toArray();
    }
    return $metadata;
  }

}
