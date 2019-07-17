<?php

namespace Drupal\patternkit\PatternLibraryParser;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\patternkit\Pattern;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibraryParserBase;
use function dump;
use function file_exists;
use function get_call_stack;
use function kint_trace;
use function strlen;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

/**
 * Parses a Twig pattern library collection into usable metadata.
 */
class TwigPatternLibraryParser extends PatternLibraryParserBase {

  /** @var \Drupal\Component\Serialization\SerializationInterface */
  protected $serializer;

  /**
   * TwigPatternLibraryParser constructor.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Serialization service.
   *
   * {@inheritdoc}
   */
  public function __construct(SerializationInterface $serializer, $root, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager) {
    $this->serializer = $serializer;
    parent::__construct($root, $module_handler, $theme_manager);
  }

  /**
   * Returns a new Patternkit Pattern.
   *
   * @param $name
   *   The name of the pattern.
   * @param $schema
   *   The optional schema for the pattern.
   *
   * @return \Drupal\patternkit\Pattern
   */
  public function createPattern($name, $schema): Pattern {
    return new Pattern($name, $schema);
  }

  /**
   * Fetches all assets for a pattern.
   *
   * @param Pattern $pattern
   *   The pattern to use for asset retrieval.
   * @param PatternEditorConfig $config
   *   The configuration object to use for provisioning the pattern.
   *
   * @return Pattern
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
   *   @code
   *   patterns:
   *     library:
   *       atoms:
   *         path/atoms: {}
   *       molecules:
   *         path/molecules: {}
   *       organisms:
   *         path/organisms: {}
   *   @endcode
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
    $rdit = new RecursiveDirectoryIterator($path, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO);
    $filter = ['json', 'twig'];
    $metadata = [];
    $components = [];

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

    foreach ($components as $name => $data) {
      // If the component has a json file, create the pattern from it.
      if (!empty($data['json']) && $file_contents = file_get_contents($data['json'])) {
        $pattern = $this->createPattern($name, (array) $this->serializer::decode($file_contents) + $library_data);
        $pattern->name = $name;
        // URL is redundant for the twig based components, so we use it to
        // store namespace.
        $pattern->url = $library;
        // @todo add default of library version fallback to extension version.
        $pattern->version = $pattern->version ?? 'VERSION';
      }
      else {
        // Create the pattern from defaults.
        $pattern = $this->createPattern($name,
          [
            '$schema'    => 'http =>//json-schema.org/draft-04/schema#',
            'category'   => 'atom',
            'title'      => $name,
            'type'       => 'object',
            'format'     => 'grid',
            'name'       => $name,
            'properties' => (object) [],
            'required'   => [],
          ] + $library_data
        );
      }

      if (!empty($data['twig'])) {
        $twig_file = $data['twig'];
        if (file_exists($twig_file)) {
          $pattern->filename = trim(substr($twig_file, strlen($path)), '/\\');
          $pattern->template = file_get_contents($twig_file);
        }
      }

      $metadata[$name] = $pattern;
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
