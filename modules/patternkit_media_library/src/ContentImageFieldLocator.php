<?php

namespace Drupal\patternkit_media_library;

use Drupal\Component\Utility\NestedArray;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Schema\SchemaWalkerFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Service for locating image fields with missing alt text in pattern content.
 */
class ContentImageFieldLocator implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Constructs a ContentImageFieldLocator instance.
   *
   * @param \Drupal\patternkit\Schema\SchemaWalkerFactory $schemaWalkerFactory
   *   The schema walker factory service.
   */
  public function __construct(
    protected SchemaWalkerFactory $schemaWalkerFactory,
  ) {
  }

  /**
   * Find image fields with missing alt text in pattern content.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern entity containing the schema.
   * @param array $content
   *   The pattern content to traverse.
   *
   * @return array
   *   Array of field information with format:
   *   [
   *     [
   *       'path' => 'hero.image.src',
   *       'url' => 'http://...',
   *       'alt_path' => 'hero.image.alt',
   *     ],
   *     ...
   *   ]
   *
   * @throws \Drupal\patternkit\Exception\SchemaReferenceException
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   * @throws \Drupal\patternkit\Exception\SchemaException
   */
  public function findImageFieldsWithMissingAlt(PatternInterface $pattern, array $content): array {
    $fields = [];

    // Get the pattern schema as JSON.
    $schema_json = $pattern->getSchema();

    // Create a schema walker to traverse the content.
    $schemaWalker = $this->schemaWalkerFactory->createFromString($schema_json, $content);

    // Iterate over all values in the content.
    foreach ($schemaWalker as $key => $value) {
      $propertySchema = $schemaWalker->getPropertySchema();

      // Build the current path by traversing the depth levels.
      $current_path = $this->buildCurrentPath($schemaWalker);

      // Check if this is an image field with a URL value.
      if ($propertySchema !== NULL && $this->isImageField($propertySchema, $key, $value)) {
        // Check if there's a corresponding alt field that's empty or missing.
        if ($this->isMissingAltText($content, $current_path)) {
          // Get the alt field path.
          $alt_path = $this->getAltPath($current_path);

          $fields[] = [
            'path' => $current_path,
            'url' => $value,
            'alt_path' => $alt_path,
          ];
        }
      }
    }

    return $fields;
  }

  /**
   * Build the current path from the schema walker's depth and keys.
   *
   * @param \Drupal\patternkit\Schema\SchemaWalker $schemaWalker
   *   The schema walker instance.
   *
   * @return string
   *   The dot-separated path (e.g., 'hero.image.src').
   */
  protected function buildCurrentPath($schemaWalker): string {
    $path_parts = [];

    // Get the current depth.
    $depth = $schemaWalker->getDepth();

    // Traverse from root to current depth to build the path.
    for ($level = 0; $level <= $depth; $level++) {
      $subIterator = $schemaWalker->getSubIterator($level);
      if ($subIterator !== NULL) {
        $path_parts[] = $subIterator->key();
      }
    }

    return implode('.', $path_parts);
  }

  /**
   * Check if a property schema represents an image field with a URL value.
   *
   * @param \Swaggest\JsonSchema\SchemaContract $propertySchema
   *   The property schema.
   * @param string|int $key
   *   The current key.
   * @param mixed $value
   *   The current value.
   *
   * @return bool
   *   TRUE if this is an image field with a URL, FALSE otherwise.
   */
  protected function isImageField($propertySchema, $key, $value): bool {
    // Must be a string value.
    if (!is_string($value) || empty($value)) {
      return FALSE;
    }

    // Must be a URL (starts with / or http).
    if (!str_starts_with($value, '/') && !str_starts_with($value, 'http')) {
      return FALSE;
    }

    // Check if the schema has format: "image".
    if (isset($propertySchema->format) && $propertySchema->format === 'image') {
      return TRUE;
    }

    // Also check if the field name is "src" (for image object structures).
    if ($key === 'src' && isset($propertySchema->type) && $propertySchema->type === 'string') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check if a field path has missing or empty alt text.
   *
   * @param array $content
   *   The content array.
   * @param string $path
   *   The path to the src field (e.g., 'hero.image.src').
   *
   * @return bool
   *   TRUE if the alt field is missing or empty, FALSE otherwise.
   */
  protected function isMissingAltText(array $content, string $path): bool {
    // Get the alt field path by replacing .src with .alt.
    $alt_path = $this->getAltPath($path);

    if ($alt_path === NULL) {
      return FALSE;
    }

    // Navigate to the alt field value.
    $alt_value = $this->getValueByPath($content, $alt_path);

    // Alt is missing if it's NULL or an empty string.
    return $alt_value === NULL || $alt_value === '';
  }

  /**
   * Get the alt field path from a src field path.
   *
   * @param string $path
   *   The src field path (e.g., 'hero.image.src' or 'image_url').
   *
   * @return string|null
   *   The alt field path (e.g., 'hero.image.alt' or 'image_alt')
   *   or NULL if not applicable.
   */
  protected function getAltPath(string $path): ?string {
    // Check if the path ends with .src.
    if (str_ends_with($path, '.src')) {
      return substr($path, 0, -4) . '.alt';
    }

    // Check if the path is just 'src' (at root level).
    if ($path === 'src') {
      return 'alt';
    }

    // Handle flat image fields like 'image_url' -> 'image_alt'.
    // Replace common image field suffixes with _alt.
    $image_suffixes = ['_url', '_src', '_uri'];
    foreach ($image_suffixes as $suffix) {
      if (str_ends_with($path, $suffix)) {
        return substr($path, 0, -strlen($suffix)) . '_alt';
      }
    }

    // If it's a simple field name (no dots), assume alt is field_alt.
    if (!str_contains($path, '.')) {
      return $path . '_alt';
    }

    return NULL;
  }

  /**
   * Get the base path (without .src or other suffixes).
   *
   * @param string $path
   *   The full path (e.g., 'hero.image.src' or 'image_url').
   *
   * @return string
   *   The base path (e.g., 'hero.image' or 'image').
   */
  protected function getBasePath(string $path): string {
    if (str_ends_with($path, '.src')) {
      return substr($path, 0, -4);
    }

    // Handle flat image fields like 'image_url' -> 'image'.
    $image_suffixes = ['_url', '_src', '_uri'];
    foreach ($image_suffixes as $suffix) {
      if (str_ends_with($path, $suffix)) {
        return substr($path, 0, -strlen($suffix));
      }
    }

    return $path;
  }

  /**
   * Get a value from an array by dot-separated path.
   *
   * @param array $array
   *   The array to search.
   * @param string $path
   *   The dot-separated path (e.g., 'hero.image.alt').
   *
   * @return mixed|null
   *   The value at the path, or NULL if not found.
   */
  protected function getValueByPath(array $array, string $path): mixed {
    $parents = explode('.', $path);
    $key_exists = NULL;
    $value = NestedArray::getValue($array, $parents, $key_exists);

    return $key_exists ? $value : NULL;
  }

  /**
   * Set a value in an array by dot-separated path.
   *
   * @param array &$array
   *   The array to modify (passed by reference).
   * @param string $path
   *   The dot-separated path (e.g., 'hero.image.alt').
   * @param mixed $value
   *   The value to set.
   */
  public function setValueByPath(array &$array, string $path, mixed $value): void {
    $parents = explode('.', $path);
    NestedArray::setValue($array, $parents, $value);
  }

}
