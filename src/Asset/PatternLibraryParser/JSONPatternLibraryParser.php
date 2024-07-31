<?php

namespace Drupal\patternkit\Asset\PatternLibraryParser;

use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\patternkit\Asset\PatternLibraryParserBase;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\MissingSchemaException;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryJSONParserTrait;
use Swaggest\JsonSchema\Exception as JsonSchemaException;
use Webmozart\Assert\Assert;

/**
 * Parses a Twig pattern library collection into usable metadata.
 */
class JSONPatternLibraryParser extends PatternLibraryParserBase {

  use PatternLibraryJSONParserTrait;

  /**
   * The plugin ID for this parser plugin to be referenced from patterns.
   */
  protected const PLUGIN_ID = 'json';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\patternkit\Exception\MissingSchemaException
   * @throws \Drupal\patternkit\Exception\SchemaException
   */
  public function fetchPatternAssets(PatternInterface $pattern): array {
    $assets = parent::fetchPatternAssets($pattern);

    // Test to make sure necessary assets were discovered.
    if (empty($assets['json']) || !$this->isJson($assets['json'])) {
      throw new MissingSchemaException(
        sprintf('Unable to find a suitable schema for pattern "%s". JSON asset identified as "%s".',
          $pattern->getAssetId(), $assets['json'] ?? ''));
    }

    // Remap file extensions to purpose-based keys.
    $assets['schema'] = $assets['json'];

    try {
      // Process the schema.
      $assets['schema'] = $this->processSchema($assets['schema'], $pattern);
    }
    catch (JsonSchemaException $e) {
      throw new SchemaException(
        sprintf('Unable to consume the schema for pattern "%s": %s', $pattern->getAssetId(), $e->getMessage()),
        $e->getCode(),
        $e,
      );
    }
    catch (\JsonException $e) {
      throw new SchemaException(
        sprintf('Unable to decode the schema for pattern "%s": %s', $pattern->getAssetId(), $e->getMessage()),
        $e->getCode(),
        $e,
      );
    }

    return $assets;
  }

  /**
   * {@inheritdoc}
   */
  public function parsePatternLibraryInfo(PatternLibrary $library, string $path): array {
    Assert::false(str_starts_with($path, $this->root), 'The library path is expected to be relative to the web root.');

    if (!file_exists("$this->root/$path")) {
      throw new InvalidLibraryFileException("Path $path does not exist.");
    }

    // Collect metadata for all discovered components.
    $metadata = [];

    // Attempt to load existing pattern data from the library.
    $info = $library->getPatternInfo();
    if (!isset($info[$path]) && $this->isDebug()) {
      $this->logger->debug('No pattern information found for library "@library" at "@path".', [
        '@library' => $library->id(),
        '@path' => $path,
      ]);
    }

    // Collect exceptions throughout processing for logging at the end.
    $exceptions = [];

    // Prepare pattern value defaults for all items in this library.
    $library_defaults = $this->getLibraryDefaults($library, $path);

    // Process all discovered components.
    foreach (self::discoverComponents("$this->root/$path", $this->getRequiredFileExtensions($library)) as $component_name => $files) {
      if ($pattern = $this->processComponent($library, $path, $library_defaults, $component_name, $files, $exceptions)) {
        $metadata[$pattern->getPath()] = $pattern->toArray();
      }
    }

    if ($exceptions) {
      $this->logParserExceptions($library->id(), $exceptions);
    }

    return $metadata;
  }

  /**
   * Process individually discovered component assets into a Pattern entity.
   *
   * @param \Drupal\patternkit\PatternLibrary $library
   *   The library information object.
   * @param string $library_path
   *   The library path being processed. This is typically a path relative to
   *   the Drupal web root.
   * @param array $library_defaults
   *   Default property values common to all patterns discovered within the
   *   library being processed.
   * @param string $component_name
   *   The name of the component being processed. This is usually the basename
   *   of the files that were discovered.
   * @param array $files
   *   An associative array of discovered assets. Each value is the full file
   *   path from the system root to the file, and each is keyed by the file
   *   extension.
   * @param array $exceptions
   *   An array to capture exception data in during processing. This is passed
   *   by reference to enable capturing of exceptions and storage for later
   *   review without breaking control flow.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface|null
   *   An instantiated Pattern entity from the discovered assets, or NULL if
   *   the component could not be created into a Pattern.
   */
  protected function processComponent(PatternLibrary $library, string $library_path, array $library_defaults, string $component_name, array $files, array &$exceptions): ?PatternInterface {
    // A json file is required for this parser. Stop here if one isn't found.
    if (empty($files['json']) || !file_exists($files['json'])) {
      return NULL;
    }

    // Prepare pattern instance defaults.
    $defaults = $this->getPatternDefaults($library, $library_path, $component_name, $files);
    $defaults += $library_defaults;

    if (empty($defaults['category'])) {
      $defaults['category'] = $this->getSuggestedPatternCategory($library, $library_path, $defaults);
    }

    // Attempt to load the pattern with all assets resolved.
    return $this->loadPattern($component_name, $defaults, $exceptions);
  }

  /**
   * {@inheritdoc}
   */
  protected function getPatternDefaults(PatternLibrary $library, string $library_path, string $component_name, array $files): array {
    Assert::keyExists($files, 'json', 'All components are expected to have a JSON file.');

    $defaults = parent::getPatternDefaults($library, $library_path, $component_name, $files);

    // Remove the library path and extension to form the pattern path.
    $pattern_path = trim(substr($files['json'], strlen("$this->root/$library_path/"), -strlen('.json')), '/\\');
    $defaults['path'] = $pattern_path;

    // Identify the path to the directory containing a component's assets.
    $asset_path = trim("$library_path/$pattern_path", '/\\');

    // Record the JSON asset path.
    $defaults['assets']['json'] = "$asset_path.json";

    // Prioritize values from the JSON schema over prepared defaults.
    if ($file_contents = file_get_contents($files['json'])) {
      $schema_content = (array) $this->serializer::decode($file_contents);

      // Whitelist which schema values we pay attention to at this stage.
      $schema_values = array_intersect_key($schema_content, array_flip([
        'category',
        'name',
        'title',
        'description',
        'version',
      ]));

      $defaults = $schema_values + $defaults;
    }

    return $defaults;
  }

  /**
   * Get a list of required files for components using this parser.
   *
   * @param \Drupal\patternkit\PatternLibrary $library
   *   The pattern library data object.
   *
   * @return string[]
   *   A string array of file extensions required by each component.
   */
  protected function getRequiredFileExtensions(PatternLibrary $library): array {
    return ['json'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): string {
    return static::PLUGIN_ID;
  }

}
