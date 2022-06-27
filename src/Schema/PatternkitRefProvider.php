<?php

namespace Drupal\patternkit\Schema;

use Drupal\patternkit\Asset\LibraryInterface;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Exception\SchemaReferenceException;
use Swaggest\JsonSchema\RemoteRefProvider;

/**
 * A reference provider implementation to load Patternkit schema references.
 */
class PatternkitRefProvider implements RemoteRefProvider {

  /**
   * The Patternkit library service for loading patterns.
   *
   * @var \Drupal\patternkit\Asset\LibraryInterface
   */
  protected LibraryInterface $library;

  /**
   * Creates a new PatternkitRefProvider instance.
   *
   * @param \Drupal\patternkit\Asset\LibraryInterface $library
   *   The Patternkit library service for loading patterns.
   */
  public function __construct(LibraryInterface $library) {
    $this->library = $library;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaData($url) {
    $parsed = $this->parseNormalizedSchemaReference($url);

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
    $asset = $this->library->getLibraryAsset($asset_id);
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
   *   The reference to be parsed.
   *
   * @return array|false
   *   An array containing the keys listed below or FALSE if it could not be
   *   parsed:
   *   - 'asset_id': The name of the library asset.
   *   - 'path': Any reference path within the schema if included.
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

}
