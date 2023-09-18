<?php

namespace Drupal\patternkit\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\UpdateHelper;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;

/**
 * Commands for working with the dev branch of Patternkit.
 */
class PatternkitCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $patternDiscovery;

  /**
   * The pattern repository service.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * The patternkit update helper service.
   *
   * @var \Drupal\patternkit\UpdateHelper
   */
  protected UpdateHelper $updateHelper;

  /**
   * The root path of the Drupal installation.
   *
   * @var string
   */
  protected string $root;

  /**
   * Constructs the Patternkit Drush Commands functionality.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\patternkit\Asset\PatternDiscoveryInterface $patternDiscovery
   *   The pattern discovery service.
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator service.
   * @param \Drupal\patternkit\UpdateHelper $updateHelper
   *   The patternkit update helper service.
   * @param string $root
   *   The root path of the Drupal installation.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    PatternDiscoveryInterface $patternDiscovery,
    PatternRepository $patternRepository,
    CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    UpdateHelper $updateHelper,
    string $root
  ) {
    parent::__construct();

    $this->entityTypeManager = $entityTypeManager;
    $this->patternDiscovery = $patternDiscovery;
    $this->patternRepository = $patternRepository;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
    $this->updateHelper = $updateHelper;
    $this->root = $root;
  }

  /**
   * Update all patterns in a library.
   *
   * @param string|null $library_name
   *   (optional) The name of a library to limit updates to.
   *
   * @option string $filter
   *   A string concatenation of entity type, bundle, and display mode for
   *   updates to be limited to. Example: 'node.article.default'. Fewer items
   *   may also be provided to filter. For example, only an entity and a bundle
   *   may be provided ('node.article') or just an entity type ('node').
   * @option string $pattern
   *   A specific pattern name to be updated. When using this option, only one
   *   pattern name may be specified per execution.
   *   Example: '@patternkit/atoms/example/src/example'.
   *
   * @return string
   *   A confirmation message for completion.
   *
   * @command patternkit:libUpdate
   * @aliases pklu, patternkit-lib-update
   * @format string
   *
   * @usage drush pklu @patternkit
   *   Update all @patternkit library patterns in all blocks and layouts.
   * @usage drush pklu --filter=node.article
   *   Update all patterns on the Node Article content type and overrides.
   * @usage drush pklu --pattern='@patternkit/atoms/example/src/example'
   *   Update all blocks using the patternkit example pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drush\Exceptions\CommandFailedException
   */
  public function libUpdate(string $library_name = NULL, $options = ['filter' => self::REQ, 'pattern' => self::REQ]): string {
    // Fail early if the library name filter isn't prefixed.
    if ($library_name !== NULL && !str_starts_with($library_name, '@')) {
      throw new CommandFailedException('When using a library name filter, the library name should always include the "@" prefix. For example: "@patternkit"');
    }

    // Assemble filters array from options.
    $filter = [
      'library' => $library_name,
    ];

    if ($options['filter']) {
      $filters = explode('.', $options['filter'], 3);
      $filter['entity_type'] = $filters[0] ?? NULL;
      $filter['bundle'] = $filters[1] ?? NULL;
      $filter['display_mode'] = $filters[2] ?? NULL;
    }

    if ($options['pattern']) {
      $filter['pattern'] = $options['pattern'];
    }

    $this->updateHelper->updateAllPatternComponents($filter);

    /** @var \Drupal\Core\Block\BlockManager $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_manager->clearCachedDefinitions();
    $this->entityTypeManager->clearCachedDefinitions();

    return $this->t('Completed running Patternkit library updates.');
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
    $pattern = $this->getPattern($pattern_id);

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

  /**
   * Invalidate caches for patternkit cache data.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   A status message for Drush to display.
   *
   * @command patternkit:cache:clear
   * @aliases pkcc
   *
   * @format string
   */
  public function clearCache(): string {
    $tags = ['patternkit_patterns', 'patternkit_libraries'];

    $this->cacheTagsInvalidator->invalidateTags($tags);

    return $this->t('Invalidated all patternkit pattern and library cache entries.');
  }

  /**
   * Update invalid asset paths in stored Pattern entities.
   *
   * This resolves an issue where pattern assets like the schema or twig files
   * cannot be loaded from saved Pattern entities after a pattern library has
   * been moved to a new file path.
   *
   * @command patternkit:update-asset-paths
   */
  public function updateAssetPaths(): int {
    $pattern_storage = $this->entityTypeManager->getStorage('patternkit_pattern');
    $loaded_patterns = $pattern_storage->loadMultiple();

    $is_verbose = $this->io()->isVerbose();

    // Track updates and failures for final reporting.
    $updated = 0;
    $errors = 0;

    /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
    foreach ($loaded_patterns as $pattern) {
      // Get asset data cached in the database entry.
      $assets = $pattern->getAssets();

      // Test each asset for availability.
      $needs_update = FALSE;
      foreach ($assets as $type => $path) {
        if (str_starts_with($path, $this->root)) {
          $needs_update = TRUE;
        }
        else {
          $path = $this->root . '/' . $path;
        }

        if (!file_exists($path)) {
          if ($is_verbose) {
            $warning = dt('Unable to load "@type" asset from "@path" for pattern id @id.', [
              '@type' => $type,
              '@path' => $path,
              '@id' => $pattern->id(),
            ]);
            $this->logger()->warning($warning);
          }

          // Since we've identified that some assets were unavailable we can
          // stop testing and move on to updating.
          $needs_update = TRUE;
          break;
        }
      }

      if ($needs_update) {
        try {
          // Load the latest discovered version of the pattern.
          $source_pattern = $this->patternRepository->getPattern($pattern->getAssetId());

          // Update the asset paths for the saved entity.
          $pattern->setAssets($source_pattern->getAssets());
          $pattern->save();
          $updated++;

          $notice = dt('Updated cached asset paths for pattern ID "@id".', [
            '@id' => $pattern->id(),
          ]);
          $this->logger()->notice($notice);
        }
        catch (\Exception $exception) {
          $error = dt('Failed to update asset paths for cached pattern ID "@id": @message', [
            '@id' => $pattern->id(),
            '@message' => $exception->getMessage(),
          ]);
          $this->logger()->error($error);
          $errors++;
        }
      }
    }

    $summary = dt('Completed processing of @count cached patterns with @updates and @errors.', [
      '@count' => count($loaded_patterns),
      '@updates' => $this->formatPlural($updated, '1 update', '@count updates'),
      '@errors' => $this->formatPlural($errors, '1 error', '@count errors'),
    ]);
    $this->io()->writeln($summary);

    return ($errors > 0) ? self::EXIT_FAILURE_WITH_CLARITY : self::EXIT_SUCCESS;
  }

  /**
   * Load a pattern instance.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern to be loaded.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The loaded pattern instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getPattern(string $pattern_id): PatternInterface {
    return $this->patternRepository->getPattern($pattern_id);
  }

}
