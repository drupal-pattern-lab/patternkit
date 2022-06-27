<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\patternkit\Exception\SchemaReferenceException;
use Drupal\patternkit\Schema\PatternkitRefProvider;

/**
 * An extension of PatternkitRefProvider with method overrides for testing.
 */
class TestPatternkitRefProvider extends PatternkitRefProvider {

  /**
   * An array of registered pattern schemas keyed by asset ID.
   *
   * @var string[]
   */
  protected array $patternSchemas = [];

  /**
   * Overridden constructor to remove unnecessary dependencies during testing.
   *
   * @noinspection PhpMissingParentConstructorInspection
   */
  public function __construct() {
    $this->patternSchemas = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternSchema(string $asset_id): string {
    if (isset($this->patternSchemas[$asset_id])) {
      return $this->patternSchemas[$asset_id];
    }
    else {
      throw new SchemaReferenceException('Unknown component referenced in pattern schema: ' . $asset_id);
    }
  }

  /**
   * Register an asset and related schema for loading during tests.
   *
   * @param string $asset_id
   *   The namespaced asset identifier used when loading a pattern.
   * @param string $schema_json
   *   The schema JSON string to return for this pattern.
   */
  public function registerPatternSchema(string $asset_id, string $schema_json): void {
    $this->patternSchemas[$asset_id] = $schema_json;
  }

}
