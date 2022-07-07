<?php

namespace Drupal\patternkit\Asset;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibrary;

/**
 * Methods for parsing library files to a Patternkit PatternLibrary.
 */
interface PatternLibraryParserInterface {

  /**
   * Returns a new Patternkit Pattern.
   *
   * @param string $name
   *   The name of the pattern.
   * @param array|object $schema
   *   The optional schema for the pattern.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   A fully-instantiated Patternkit Pattern.
   */
  public function createPattern(string $name, $schema): PatternInterface;

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
  public static function discoverComponents(string $path, array $filter = []): array;

  /**
   * Fetch and cache assets to render this pattern.
   *
   * @param string $subtype
   *   Pattern machine name.
   * @param object $config
   *   Configuration object for this instance of the pattern.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The patternkit object of interest.*/
  public static function fetchAssets(string $subtype, object $config): PatternInterface;

  /**
   * Fetch and cache assets to render this pattern.
   *
   * @param string $subtype
   *   Pattern machine name.
   * @param object $config
   *   Configuration object for this instance of the pattern.
   * @param object $pk_obj
   *   The patternkit pattern object to extend.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The patternkit object representing the pattern..*/
  public function fetchFragmentAssets(string $subtype, object $config, object &$pk_obj): PatternInterface;

  /**
   * Fetch a single asset from the patternkit platform.
   *
   * @param string $dir
   *   The path to put the file under.
   * @param string $asset_prefix
   *   A prefix to add to the asset.
   * @param string $asset_url
   *   Relative path of the asset to the pattern folder.
   *
   * @return bool|string
   *   FALSE on failure, stream path on success.
   */
  public static function fetchSingleAsset(string $dir, string $asset_prefix, string $asset_url);

  /**
   * Fetches all assets for a pattern.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern to use for asset retrieval.
   * @param \Drupal\patternkit\PatternEditorConfig|null $config
   *   The configuration object to use for provisioning the pattern.
   *
   * @return array
   *   The pattern assets keyed by path or type.
   */
  public function fetchPatternAssets(PatternInterface $pattern, PatternEditorConfig $config = NULL): array;

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
   * @param \Drupal\patternkit\PatternLibrary $library
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
  public function parsePatternLibraryInfo(PatternLibrary $library, string $path): array;

}
