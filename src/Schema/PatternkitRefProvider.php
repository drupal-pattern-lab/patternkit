<?php

namespace Drupal\patternkit\Schema;

use Drupal\patternkit\Asset\LibraryNamespaceResolverInterface;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Exception\SchemaReferenceException;
use Swaggest\JsonSchema\RemoteRefProvider;
use Webmozart\PathUtil\Path;

/**
 * A reference provider implementation to load Patternkit schema references.
 */
class PatternkitRefProvider implements RemoteRefProvider {

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $patternDiscovery;

  /**
   * The library namespace resolver service.
   *
   * @var \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface
   */
  protected LibraryNamespaceResolverInterface $libraryNamespaceResolver;

  /**
   * Creates a new PatternkitRefProvider instance.
   *
   * @param \Drupal\patternkit\Asset\PatternDiscoveryInterface $patternDiscovery
   *   The pattern discovery service.
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface $libraryNamespaceResolver
   *   The library namespace resolver service.
   */
  public function __construct(PatternDiscoveryInterface $patternDiscovery, LibraryNamespaceResolverInterface $libraryNamespaceResolver) {
    $this->patternDiscovery = $patternDiscovery;
    $this->libraryNamespaceResolver = $libraryNamespaceResolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaData($url) {
    // @todo Replace with str_starts_with() once a polyfill dependency is in place.
    if (strpos($url, '/api/patternkit/') === 0) {
      $parsed = $this->parseNormalizedSchemaReference($url);
    }
    elseif ($url[0] === '@') {
      $parsed = $this->parseNamespacedSchemaReference($url);
    }
    else {
      return FALSE;
    }

    if (is_array($parsed)) {
      try {
        $schema = $this->getPatternSchema($parsed['asset_id']);
        return json_decode($schema);
      }
      catch (SchemaReferenceException $schemaReferenceException) {
        return FALSE;
      }
    }
    else {
      // Unable to parse the schema reference.
      return FALSE;
    }
  }

  /**
   * Load and parse a referenced schema.
   *
   * @param string $asset_id
   *   A namespaced library asset identifier.
   *
   * @return string|null
   *   The string representation of the pattern's JSON schema if found. NULL if
   *   the schema could not be found or loaded.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\patternkit\Exception\SchemaReferenceException
   *   Throws an exception if a reference cannot be loaded successfully.
   *
   * @see \Drupal\patternkit\PatternLibraryJSONParserTrait::schemaDereference()
   *
   * @todo Refactor this schema loading into a separate DI utility class.
   */
  protected function getPatternSchema(string $asset_id): ?string {
    $asset = $this->patternDiscovery->getPatternDefinition($asset_id);
    if (is_null($asset)) {
      throw new SchemaReferenceException('Unknown component referenced in pattern schema: ' . $asset_id);
    }

    // Load the pattern to load the schema.
    // @todo Refactor this process to avoid having to instantiate the pattern.
    $pattern = Pattern::create($asset);
    return $pattern->getSchema();
  }

  /**
   * Parse normalized schema references to identify a matching library asset.
   *
   * @param string $ref
   *   The reference to be parsed. This is expected to be a path to the API
   *   controller to load the pattern schema. This is likely formatted like,
   *   '/api/patternkit/my/pattern/name?asset=schema'.
   *
   * @return array|false
   *   An array containing the keys listed below or FALSE if it could not be
   *   parsed:
   *   - asset_id: The name of the library asset.
   *   - path: Any reference path within the schema if included.
   *
   * @see \Drupal\patternkit\PatternLibraryJSONParserTrait::schemaDereference()
   */
  public function parseNormalizedSchemaReference(string $ref) {
    $matches = [];

    if (preg_match('&/api/patternkit/(.*)\?asset=schema#?(.*)&', $ref, $matches)) {
      $asset_id = '@' . urldecode($matches[1]);
      $path = $matches[2];
    }
    else {
      return FALSE;
    }

    return [
      'asset_id' => $asset_id,
      'path' => $path,
    ];
  }

  /**
   * Parse a namespaced schema references to identify a matching library asset.
   *
   * @param string $ref
   *   The reference to be parsed. This is expected to follow the format of
   *   '@namespace/path/to/pattern'.
   *
   * @return array|false
   *   An array containing the keys listed below or FALSE if it could not be
   *   parsed:
   *   - asset_id: The name of the library asset.
   *   - path: Any reference path within the schema if included.
   *
   * @see \Drupal\patternkit\PatternLibraryJSONParserTrait::schemaDereference()
   */
  public function parseNamespacedSchemaReference(string $ref) {
    assert($ref[0] === '@', 'Namespaced pattern references are expected to start with an "@".');

    $matches = [];
    if (preg_match('&^(?<namespace>@[^/]+)/(?<path>[^#]+)(#(?<pointer>.+))?$&', $ref, $matches)) {
      // Since pattern references may sometimes contain relative pathing, this
      // will need to be resolved to a direct path before it will match an
      // indexed pattern identifier.
      if (str_contains($matches['path'], './')) {
        // @todo Replace webmozart/path-util dependency with symfony/filesystem once symfony dependency is updated.
        $matches['path'] = Path::canonicalize($matches['path']);
      }

      // If the reference points directly to a JSON file, we'll need to trim
      // that from the end of it to match the pattern identifier.
      if (strpos($matches['path'], '.json') !== FALSE) {
        $matches['path'] = substr($matches['path'], 0, -strlen('.json'));
      }
    }
    else {
      return FALSE;
    }

    return [
      'asset_id' => "${matches['namespace']}/${matches['path']}",
      'path' => $matches['pointer'] ?? '',
    ];
  }

}
