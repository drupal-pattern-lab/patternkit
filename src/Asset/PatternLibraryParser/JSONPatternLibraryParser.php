<?php

namespace Drupal\patternkit\Asset\PatternLibraryParser;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\MissingSchemaException;
use Drupal\patternkit\Exception\PatternAssetException;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryJSONParserTrait;
use Drupal\patternkit\Asset\PatternLibraryParserBase;
use Swaggest\JsonSchema\Exception as JsonSchemaException;

/**
 * Parses a Twig pattern library collection into usable metadata.
 */
class JSONPatternLibraryParser extends PatternLibraryParserBase {

  use PatternLibraryJSONParserTrait;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\patternkit\Exception\MissingSchemaException
   * @throws \Drupal\patternkit\Exception\SchemaException
   */
  public function fetchPatternAssets(
    PatternInterface $pattern,
    PatternEditorConfig $config = NULL
  ): array {
    // @todo Add support for twig lib attachments such as JS and images.
    $assets = parent::fetchPatternAssets($pattern, $config);

    // Test to make sure necessary assets were discovered.
    if (empty($assets['json'])) {
      throw new MissingSchemaException(
        sprintf('Unable to find a suitable schema for pattern "%s"',
          $pattern->getAssetId()));
    }

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

    $exceptions = [];
    $pattern_info = $info[$relative_path];
    foreach (self::discoverComponents($path, ['json']) as $name => $data) {
      if (empty($data['json']) || !file_exists($data['json'])) {
        continue;
      }
      $pattern_path = trim(substr($data['json'], strlen($path), -strlen('.json')), '/\\');

      $library_defaults = [
        '$schema' => 'https://json-schema.org/schema#',
        'assets' => ['schema' => $data['json']],
        'category' => $pattern_info['category'] ?? 'default',
        'title' => $name,
        'type' => 'object',
        'format' => 'grid',
        'library' => $library->id(),
        'libraryPluginId' => $pattern_info['plugin'],
        'license' => $library->license ?? [],
        'name' => $name,
        'path' => $pattern_path,
        'properties' => (object) [],
        'required' => [],
        'version' => $library->version ?? '',
      ];
      if (!empty($data['json']) && $file_contents = file_get_contents($data['json'])) {
        $category_guess = $library->category ?? strstr($pattern_path, DIRECTORY_SEPARATOR, TRUE);
        $library_defaults['category'] = $category_guess ?? $library_defaults['category'];
        $library_defaults['assets']['json'] = $data['json'];
        $pattern = $this->createPattern($name, (array) $this->serializer::decode($file_contents) + $library_defaults);
      }
      else {
        // Create the pattern from defaults.
        // @todo Have this cleverly infer defaults from the template.
        $pattern = $this->createPattern($name, $library_defaults);
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

      $pattern->filename = trim(substr($data['json'], strlen($path)), '/\\');
      // @todo Set $pattern->url to the actual URL of the pattern.
      // @todo Add default of library version fallback to extension version.
      $pattern->version ??= 'VERSION';

      $metadata[$pattern->getPath()] = $pattern->toArray();
    }

    if ($exceptions) {
      $this->logParserExceptions($library->id(), $exceptions);
    }

    return $metadata;
  }

}
