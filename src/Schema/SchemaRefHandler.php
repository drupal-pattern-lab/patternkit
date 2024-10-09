<?php

namespace Drupal\patternkit\Schema;

use Symfony\Component\Filesystem\Path;
use Webmozart\Assert\Assert;

/**
 * A service for consolidated manipulation of schema references.
 */
class SchemaRefHandler {

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
   *   - asset_id: The namespaced identifier for the library asset.
   *   - namespace: The namespace of the library the pattern belongs to.
   *   - path: The path within the namespace to the pattern.
   *   - pointer: Any reference path within the schema if included.
   *
   * @see \Drupal\patternkit\PatternLibraryJSONParserTrait::schemaDereference()
   */
  public function parseNormalizedSchemaReference(string $ref) {
    $matches = [];

    if (preg_match('&/api/patternkit/(?<namespace>[^/]+)/(?<path>[^#?]+)\?asset=schema#?(?<pointer>.*)$&', $ref, $matches)) {
      // If the reference points directly to a JSON file, we'll need to trim
      // that from the end of it to match the pattern identifier.
      if (substr($matches['path'], -5) === '.json') {
        $matches['path'] = substr($matches['path'], 0, -strlen('.json'));
      }
    }
    else {
      return FALSE;
    }

    return [
      'asset_id' => "@{$matches['namespace']}/{$matches['path']}",
      'namespace' => "@{$matches['namespace']}",
      'path' => $matches['path'],
      'pointer' => $matches['pointer'] ?? '',
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
   *   - asset_id: The namespaced identifier for the library asset.
   *   - namespace: The namespace of the library the pattern belongs to.
   *   - path: The path within the namespace to the pattern.
   *   - pointer: Any reference path within the schema if included.
   *
   * @throws \Webmozart\Assert\InvalidArgumentException
   *   Throws an exception if the reference doesn't start with an '@'.
   *
   * @see \Drupal\patternkit\PatternLibraryJSONParserTrait::schemaDereference()
   */
  public function parseNamespacedSchemaReference(string $ref) {
    Assert::startsWith($ref, '@', 'Namespaced pattern references are expected to start with an "@".');

    $matches = [];
    if (preg_match('&^(?<namespace>@[^/#]+)/(?<path>[^#]+)(#(?<pointer>.+))?$&', $ref, $matches)) {
      // Since pattern references may sometimes contain relative pathing, this
      // will need to be resolved to a direct path before it will match an
      // indexed pattern identifier.
      if (str_contains($matches['path'], './')) {
        $matches['path'] = Path::canonicalize($matches['path']);
      }

      // If the reference points directly to a JSON file, we'll need to trim
      // that from the end of it to match the pattern identifier.
      if (substr($matches['path'], -5) === '.json') {
        $matches['path'] = substr($matches['path'], 0, -strlen('.json'));
      }
    }
    else {
      return FALSE;
    }

    return [
      'asset_id' => "{$matches['namespace']}/{$matches['path']}",
      'namespace' => $matches['namespace'],
      'path' => $matches['path'],
      'pointer' => $matches['pointer'] ?? '',
    ];
  }

  /**
   * Normalize a namespaced schema reference to pass through the API controller.
   *
   * @param string $ref
   *   The namespaced schema reference to be normalized.
   *
   * @return string|false
   *   The normalized URL reference for the schema passing through the API
   *   controller.
   *
   * @throws \Webmozart\Assert\InvalidArgumentException
   *   Throws an exception if the reference doesn't start with an '@'.
   *
   * @see \Drupal\patternkit\Controller\PatternkitController::apiPattern()
   * @see \Drupal\patternkit\Controller\PatternkitController::apiPatternSchema()
   */
  public function normalizeNamespacedSchemaReference(string $ref) {
    $parsed = $this->parseNamespacedSchemaReference($ref);

    // Stop here if we couldn't parse the reference.
    if ($parsed === FALSE) {
      return FALSE;
    }

    $namespace = ltrim($parsed['namespace'], '@');
    $reference = "/api/patternkit/$namespace/{$parsed['path']}?asset=schema";

    if ($parsed['pointer']) {
      $reference .= "#{$parsed['pointer']}";
    }

    return $reference;
  }

}
