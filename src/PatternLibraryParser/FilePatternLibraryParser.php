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
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

/**
 * Parses a File pattern library collection into usable metadata.
 */
class FilePatternLibraryParser extends PatternLibraryParserBase {
  use PatternLibraryJSONParserTrait;

  /**
   * FilePatternLibraryParser constructor.
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
   * Returns an array of file components grouped by file basename and extension.
   *
   * @param string $path
   *   The fully-qualified path to discover component files.
   * @param array $filter
   *   An optional filter of file extensions to search for.
   *
   * @return array
   *   Array of file components.
   *   [basename][extension] = filename.
   */
  public static function discoverComponents($path, array $filter = []): array {
    $components = [];
    $rdit = new RecursiveDirectoryIterator($path, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO);
    /** @var \SplFileInfo $file */
    foreach (new \RecursiveIteratorIterator($rdit) as $file) {
      // Skip directories and non-files.
      if (!$file->isFile()) {
        continue;
      }
      $file_path = $file->getPath();

      // Skip tests folders.
      if (strpos($file_path, '/tests') !== FALSE) {
        continue;
      }

      // Get the file extension for the file.
      $file_ext = $file->getExtension();
      if (!in_array(strtolower($file_ext), $filter, TRUE)) {
        continue;
      }

      // We use file_basename as a unique key, it is required that the
      // JSON and twig file share this basename.
      $file_basename = $file->getBasename('.' . $file_ext);

      // Build an array of all the filenames of interest, keyed by name.
      $components[$file_basename][$file_ext] = "$file_path/$file_basename.$file_ext";
    }
    return $components;
  }

  /**
   * Fetches all assets for a pattern.
   *
   * {@inheritDoc}
   */
  public function fetchPatternAssets(Pattern $pattern,
    PatternEditorConfig $config): Pattern {
    // @todo Add support for File lib attachments such as JS and images.
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
   *     path/atoms: {type: File, category: atoms}
   *     path/molecules: {type: File, category: molecules}
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
   * @param array $library
   *   The data of the library that was registered.
   * @param string $path
   *   The relative path to the extension.
   *
   * @return array
   *   An array of parsed library data.
   *
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *   Thrown when a parser exception was thrown.
   */
  public function parsePatternLibraryInfo(array $library, $path): array {
    if (!file_exists($path)) {
      throw new InvalidLibraryFileException("Path $path does not exist.");
    }
    $metadata = [];
    // @todo Grab the extension from the plugin.
    $type = strtok($library['plugin'], '.');
    if ($type !== 'file') {
      return [];
    }
    $ext = strtok('.');
    foreach (self::discoverComponents($path, [$ext]) as $name => $data) {
      if (empty($data[$ext]) || !file_exists($data[$ext])) {
        continue;
      }
      // If the component has a json file, create the pattern from it.
      $category = $library['category'] ?? 'default';
      $library_defaults = [
        '$schema'    => NULL,
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
      // Create the pattern from defaults.
      // @todo Have this cleverly infer defaults from the template.
      $pattern = $this->createPattern($name, $library_defaults);
      $pattern->filename = trim(substr($data[$ext], strlen($path)), '/\\');
      $pattern->path = substr($pattern->filename, 0, -strlen('.' . $ext));
      $pattern->template = file_get_contents($data[$ext]);
      // URL is redundant for the File based components, so we use it to
      // store namespace.
      $pattern->url = $library['name'];
      // @todo add default of library version fallback to extension version.
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
