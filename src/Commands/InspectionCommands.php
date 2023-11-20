<?php

namespace Drupal\patternkit\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\patternkit\PatternRepository;
use Drush\Commands\DrushCommands;

/**
 * Commands for inspecting Patternkit information.
 */
class InspectionCommands extends DrushCommands {

  /**
   * The pattern repository service.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

  /**
   * Constructs the Patternkit Inspection Drush Commands class.
   *
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service.
   */
  public function __construct(PatternRepository $patternRepository) {
    parent::__construct();

    $this->patternRepository = $patternRepository;
  }

  /**
   * Get pattern info as discovered by the system.
   *
   * @param string $pattern_id
   *   The pattern identifier to view. For example,
   *   '@patternkit/atoms/example/src/example'.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *   The property data for the requested pattern.
   *
   * @command patternkit:pattern:info
   * @aliases pkinfo
   *
   * @usage drush pkinfo @patternkit/atoms/example/src/example
   *   Show pattern information for the example pattern.
   *
   * @format table
   * @field-labels
   *   id: Pattern ID
   *   name: Pattern name
   *   category: Category
   *   description: Description
   *   library: Library
   *   plugin: Library plugin
   *   path: Path
   *   version: Version
   */
  public function patternInfo(string $pattern_id): PropertyList {
    $pattern = $this->patternRepository->getPattern($pattern_id);

    $info = [
      'id' => $pattern->getAssetId(),
      'name' => $pattern->getName(),
      'category' => $pattern->getCategory(),
      'description' => $pattern->getDescription(),
      'library' => $pattern->getLibrary(),
      'plugin' => $pattern->getLibraryPluginId(),
      'path' => $pattern->getPath(),
      'version' => $pattern->getVersion(),
      // 'assets' => (string) $pattern->getAssets(),
    ];

    return new PropertyList($info);
  }

  /**
   * List all discovered patterns.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The structured output data.
   *
   * @command patternkit:pattern:list
   * @aliases pklist
   *
   * @usage drush pklist --filter=patternkit
   *   List all patterns in the patternkit library.
   * @usage drush pklist --filter='plugin=twig'
   *   List all patterns using the twig plugin.
   * @usage drush pklist --filter=patternkit --format=sections
   *   Show all available pattern information for patterns in the patternkit
   *   library.
   *
   * @list-delimiter :
   * @table-style compact
   * @field-labels
   *   id: Pattern ID
   *   name: Pattern name
   *   category: Category
   *   description: Description
   *   library: Library
   *   plugin: Library plugin
   *   path: Path
   *   version: Version
   * @default-table-fields id,plugin
   * @filter-default-field id
   */
  public function patternList(): RowsOfFields {
    $patterns = $this->patternRepository->getAllPatternNames();

    $list = [];
    foreach ($patterns as $pattern) {
      $list[$pattern] = $this->patternInfo($pattern)->getArrayCopy();
    }

    return new RowsOfFields($list);
  }

  /**
   * Get the schema discovered for a pattern.
   *
   * @param string $pattern_id
   *   The pattern identifier to view. For example,
   *   '@patternkit/atoms/example/src/example'.
   * @param bool $bundled
   *   (optional) Whether to load the schema with all dependencies bundled.
   *   Defaults to false and loads the unbundled pattern schema.
   *
   * @return string
   *   The schema content for the requested pattern.
   *
   * @command patternkit:pattern:schema
   * @aliases pkschema
   *
   * @option bundled Whether the bundled schema should be loaded.
   *
   * @usage drush pkschema @patternkit/atoms/example/src/example
   *   Get the JSON schema content for the example pattern.
   * @usage drush pkschema @patternkit/atoms/example/src/example | jq
   *   Format and filter a schema with jq.
   * @usage drush pkschema @patternkit/atoms/example/src/example --bundled
   *   Get the JSON schema content with all dependencies bundled.
   */
  public function patternSchema(string $pattern_id, bool $bundled = FALSE): string {
    $pattern = $this->patternRepository->getPattern($pattern_id, $bundled);
    $schema = json_decode($pattern->getSchema());

    // Re-encode for formatting.
    return json_encode($schema, JSON_PRETTY_PRINT);
  }

}
