<?php

namespace Drupal\patternkit\Asset\PatternLibraryParser;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\patternkit\Asset\PatternLibraryParserBase;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\MissingSchemaException;
use Drupal\patternkit\Exception\MissingTemplateException;
use Drupal\patternkit\Exception\PatternAssetException;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryJSONParserTrait;
use Swaggest\JsonSchema\Exception as JsonSchemaException;

/**
 * Parses a Twig pattern library collection into usable metadata.
 */
class TwigPatternLibraryParser extends PatternLibraryParserBase {

  use PatternLibraryJSONParserTrait;

  /**
   * Returns a new Patternkit Pattern.
   *
   * @param string $name
   *   The name of the pattern.
   * @param array|object $data
   *   The optional data for the pattern.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   A fully-instantiated Patternkit Pattern.
   */
  public function createPattern(string $name, $data): PatternInterface {
    // Pattern schemas contain values needed for Pattern fields.
    $values = ['name' => $data['title'] ?? $name];
    return $this->patternStorage->create($values + $data);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\patternkit\Exception\SchemaException
   * @throws \Drupal\patternkit\Exception\MissingTemplateException
   * @throws \Drupal\patternkit\Exception\MissingSchemaException
   */
  public function fetchPatternAssets(
    PatternInterface $pattern,
    PatternEditorConfig $config = NULL
  ): array {
    // @todo Add support for twig lib attachments such as JS and images.
    $assets = parent::fetchPatternAssets($pattern, $config);

    // Test to ensure necessary assets were found.
    if (empty($assets['twig'])) {
      throw new MissingTemplateException(
        sprintf('Unable to find a suitable Twig template for pattern "%s"', $pattern->getAssetId()));
    }
    if (empty($assets['json'])) {
      throw new MissingSchemaException(
        sprintf('Unable to find a suitable schema for pattern "%s"',
          $pattern->getAssetId()));
    }

    $assets['template'] = $assets['twig'];
    $assets['schema'] = $assets['json'];

    try {
      // Replace any $ref links with relative paths.
      $assets['schema'] = $this->normalizeSchemaReferences($assets['schema']);

      // Identify and record all pattern reference dependencies.
      $dependencies = $this->getSchemaDependencies($assets['schema'], $pattern->getAssetId());
      $pattern->setDependencies($dependencies);
    }
    catch (JsonSchemaException $e) {
      throw new SchemaException(
        sprintf('Unable to consume the schema for pattern %s: %s', $pattern->getAssetId(), $e->getMessage()),
        $e->getCode(),
        $e,
      );
    }
    catch (\JsonException $e) {
      throw new SchemaException(
        sprintf('Unable to decode the schema for pattern %s: %s', $pattern->getAssetId(), $e->getMessage()),
        $e->getCode(),
        $e,
      );
    }

    return $assets;
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
   *
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

    $exceptions = [];
    $pattern_info = $info[$relative_path];
    foreach (self::discoverComponents($path, ['json', 'twig']) as $name => $data) {
      if (empty($data['twig']) || !file_exists($data['twig'])) {
        continue;
      }
      $pattern_path = trim(substr($data['twig'], strlen($path), -strlen('.twig')), '/\\');
      // If the component has a JSON file, create the pattern from it.
      $defaults = [
        'assets'          => ['twig' => $data['twig']],
        'category'        => $pattern_info['category'] ?? 'default',
        'library'         => $library->id(),
        'libraryPluginId' => 'twig',
        'name'            => $name,
        'path'            => $pattern_path,
        'version'         => $library->version ?? 'VERSION',
      ];
      if (!empty($data['json']) && $file_contents = file_get_contents($data['json'])) {
        $category_guess = $pattern_info['category'] ?? strstr($pattern_path, DIRECTORY_SEPARATOR, TRUE);
        $defaults['category'] = $category_guess ?? $defaults['category'];
        $defaults['assets']['json'] = $data['json'];
        $pattern = $this->createPattern($name, (array) $this->serializer::decode($file_contents) + $defaults);
      }
      // Otherwise create the pattern from defaults.
      // @todo Have this cleverly infer defaults from the template.
      else {
        $pattern = $this->createPattern($name, $defaults);
      }

      try {
        // Process assets and capture them in the cached pattern definition.
        $pattern->fetchAssets();
      }
      catch (PluginNotFoundException | PluginException $e) {
        // Record the exception and don't include the pattern in results.
        $exceptions[$pattern->getAssetId()] = [
          $e,
          'Unable to load plugin "%plugin" for pattern "%pattern": @exception',
          [
            '%plugin' => $pattern->getLibraryPluginId(),
            '%pattern' => $pattern->getAssetId(),
            '@exception' => $e->getMessage(),
          ],
        ];
        continue;
      }
      catch (PatternAssetException | SchemaException $e) {
        // Record the exception and don't include the pattern in results.
        $exceptions[$pattern->getAssetId()] = [
          $e,
          '@message<br><pre>@trace</pre>',
          [
            '@message' => $e->getMessage(),
            '@trace' => $e->getTraceAsString(),
          ],
        ];
        continue;
      }
      catch (\JsonException $e) {
        // Record the exception and don't include the pattern in results.
        $exceptions[$pattern->getAssetId()] = [
          $e,
          'Unable to parse schema content for "%pattern": @exception',
          [
            '%pattern' => $pattern->getAssetId(),
            '@exception' => $e->getMessage(),
          ],
        ];
        continue;
      }

      $metadata[$pattern_path] = $pattern->toArray();
    }

    if ($exceptions) {
      $this->logParserExceptions($library->id(), $exceptions);
    }

    return $metadata;
  }

}
