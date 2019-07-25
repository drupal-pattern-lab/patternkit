<?php

namespace Drupal\patternkit\PatternLibraryParser;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\patternkit\Pattern;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibraryJSONParserTrait;
use Drupal\patternkit\PatternLibraryParserBase;

/**
 * Parses a Twig pattern library collection into usable metadata.
 */
class TwigPatternLibraryParser extends PatternLibraryParserBase {
  use PatternLibraryJSONParserTrait;

  /**
   * TwigPatternLibraryParser constructor.
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
   * Fetches all assets for a pattern.
   *
   * @param \Drupal\patternkit\Pattern $pattern
   *   The pattern to use for asset retrieval.
   * @param \Drupal\patternkit\PatternEditorConfig $config
   *   The configuration object to use for provisioning the pattern.
   *
   * @return \Drupal\patternkit\Pattern
   *   The pattern parameter with updated asset references.
   */
  public function fetchPatternAssets(Pattern $pattern,
    PatternEditorConfig $config) :Pattern {
    // @todo Add support for twig lib attachments such as JS and images.
    $pattern->attachments = array();
    return $pattern;
  }

  /**
   * Parses a given library file and allows modules and themes to alter it.
   *
   * This method sets the parsed information onto the library property.
   *
   * Library information is parsed from *.libraries.yml files; see
   * editor.libraries.yml for an example. Each entry starts with a machine name
   * and defines the following elements:
   * - patterns: A list of pattern libraries and subtypes to include. Each
   *   subtype is keyed by the subtype path.
   * @code
   *   patterns:
   *     path/atoms: {type: twig, category: atoms}
   *     path/molecules: {type: twig, category: molecules}
   *     path/organisms: {}
   * @endcode
   * - dependencies: A list of libraries this library depends on.
   * - version: The library version. The string "VERSION" can be used to mean
   *   the current Drupal core version.
   * - header: By default, JavaScript files are included in the footer. If the
   *   script must be included in the header (along with all its dependencies),
   *   set this to true. Defaults to false.
   * - minified: If the file is already minified, set this to true to avoid
   *   minifying it again. Defaults to false.
   * - remote: If the library is a third-party script, this provides the
   *   repository URL for reference.
   * - license: If the remote property is set, the license information is
   *   required. It has 3 properties:
   *   - name: The human-readable name of the license.
   *   - url: The URL of the license file/information for the version of the
   *     library used.
   *   - gpl-compatible: A Boolean for whether this library is GPL compatible.
   *
   * See https://www.drupal.org/node/2274843#define-library for more
   * information.
   *
   * @param string $library
   *   The name of the library that was registered.
   * @param string $path
   *   The relative path to the extension.
   * @param array $library_data
   *   An optional set of additional data to add to each pattern.
   *
   * @return array
   *   An array of parsed library data.
   *
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *   Thrown when a parser exception was thrown.
   */
  public function parseLibraryInfo($library, $path, array $library_data = []): array {
    if (!file_exists($path)) {
      throw new InvalidLibraryFileException("Path $path does not exist.");
    }
    $metadata = [];
    foreach (self::discoverComponents($path, ['json', 'twig']) as $name => $data) {
      if (empty($data['twig']) || !file_exists($data['twig'])) {
        continue;
      }
      // If the component has a json file, create the pattern from it.
      $category = $library_data['category'] ?? 'default';
      if (!empty($data['json']) && $file_contents = file_get_contents($data['json'])) {
        $pattern = $this->createPattern($name, (array) $this->serializer::decode($file_contents) + $library_data);
        $pattern_path = trim(substr($data['json'], strlen($path), -strlen('.json')), '/\\');
        $category_guess = $library_data['category'] ?? strstr($pattern_path, DIRECTORY_SEPARATOR, TRUE);
        $pattern->category = $pattern->category ?? $category_guess;
      }
      else {
        // Create the pattern from defaults.
        // @todo Have this cleverly infer defaults from the template.
        $pattern = $this->createPattern($name,
          [
            '$schema'    => 'http =>//json-schema.org/draft-04/schema#',
            'category'   => $category,
            'title'      => $name,
            'type'       => 'object',
            'format'     => 'grid',
            'name'       => $name,
            'properties' => (object) [],
            'required'   => [],
          ] + $library_data
        );
      }
      $pattern->filename = trim(substr($data['twig'], strlen($path)), '/\\');
      $pattern_path = substr($pattern->filename, 0, -strlen('.twig'));
      $pattern->template = file_get_contents($data['twig']);
      // URL is redundant for the twig based components, so we use it to
      // store namespace.
      $pattern->url = $library;
      // @todo add default of library version fallback to extension version.
      $pattern->version = $pattern->version ?? 'VERSION';
      $metadata["@$library/$pattern_path"] = $pattern;
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
