<?php

namespace Drupal\patternkit\Asset;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Asset\LibraryDiscoveryParser;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\PatternAssetException;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\PatternEditorConfig;
use Drupal\patternkit\PatternkitEnvironmentAwareInterface;
use Drupal\patternkit\PatternkitEnvironmentAwareTrait;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Webmozart\Assert\Assert;

/**
 * Base functionality for a pattern library parser.
 */
abstract class PatternLibraryParserBase extends LibraryDiscoveryParser implements PatternLibraryParserInterface, PatternkitEnvironmentAwareInterface {

  use StringTranslationTrait;
  use PatternkitEnvironmentAwareTrait;

  /**
   * A placeholder value for when a pattern library does not specify a version.
   */
  protected const VERSION_PLACEHOLDER = 'VERSION';

  /**
   * The default category to fall back to if one cannot be suggested.
   */
  protected const DEFAULT_CATEGORY = 'default';

  /**
   * Serializes and de-serializes data.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected SerializationInterface $serializer;

  /**
   * The entity storage handler for the patternkit_pattern entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * The schema data preprocessor factory service.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessorFactory
   */
  protected DataPreProcessorFactory $dataPreProcessorFactory;

  /**
   * The pattern dependency resolver service.
   *
   * @var \Drupal\patternkit\Asset\PatternDependencyResolver
   */
  protected PatternDependencyResolver $patternDependencyResolver;

  /**
   * The patternkit logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructor for PatternLibraryParser implementations.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Serializes and de-serializes data.
   * @param string $root
   *   The application root path.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Allow modules to alter library parsing.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Allow themes to alter library parsing.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper
   *   The stream wrapper manager.
   * @param \Drupal\Core\Asset\LibrariesDirectoryFileFinder $libraries_directory_file_finder
   *   The libraries directory file finder service.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\patternkit\Schema\DataPreProcessorFactory $dataPreProcessorFactory
   *   The schema data preprocessor factory service.
   * @param \Drupal\patternkit\Asset\PatternDependencyResolver $patternDependencyResolver
   *   The pattern dependency resolver service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The patternkit logger channel.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    SerializationInterface $serializer,
    string $root,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager,
    StreamWrapperManagerInterface $stream_wrapper,
    LibrariesDirectoryFileFinder $libraries_directory_file_finder,
    ExtensionPathResolver $extension_path_resolver,
    EntityTypeManagerInterface $entity_type_manager,
    DataPreProcessorFactory $dataPreProcessorFactory,
    PatternDependencyResolver $patternDependencyResolver,
    LoggerChannelInterface $logger
  ) {
    parent::__construct($root, $module_handler, $theme_manager, $stream_wrapper, $libraries_directory_file_finder, $extension_path_resolver);

    $this->serializer = $serializer;
    $this->patternStorage = $entity_type_manager->getStorage('patternkit_pattern');
    $this->dataPreProcessorFactory = $dataPreProcessorFactory;
    $this->patternDependencyResolver = $patternDependencyResolver;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function createPattern(string $name, $data): PatternInterface {
    // Pattern schemas contain values needed for Pattern fields.
    $values = ['name' => $data['title'] ?? $name];
    return $this->patternStorage->create($values + $data);
  }

  /**
   * Returns an array of file components grouped by file basename and extension.
   *
   * @param string $path
   *   The fully-qualified path to discover component files.
   * @param array $filter
   *   An optional filter of file extensions to search for.
   *
   * @return array
   *   Array of file components.
   *   [basename][extension] = filename.
   */
  public static function discoverComponents(string $path, array $filter = []): array {
    $components = [];
    $rdit = new RecursiveDirectoryIterator($path, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO);
    /** @var \SplFileInfo $file */
    foreach (new \RecursiveIteratorIterator($rdit) as $file) {
      // Skip directories and non-files.
      if (!$file->isFile()) {
        continue;
      }

      // Skip tests folders.
      if (stripos($file->getRelativePath(), '/tests') !== FALSE) {
        continue;
      }

      // Get the file extension for the file.
      $file_ext = $file->getExtension();
      if (!in_array(strtolower($file_ext), $filter, TRUE)) {
        continue;
      }

      // We use file_basename as a unique key, it is required that the
      // JSON and twig file share this basename.
      $file_basename = $file->getBasename('.' . $file_ext);

      // Build an array of all the filenames of interest, keyed by name.
      $file_path = $file->getPath();
      $components[$file_basename][$file_ext] = "$file_path/$file_basename.$file_ext";
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Add support for twig lib attachments such as JS and images.
   */
  public function fetchPatternAssets(PatternInterface $pattern, PatternEditorConfig $config = NULL): array {
    // If this is a pattern being assembled during discovery, the asset paths
    // should already be populated on the pattern entity from the initial
    // creation of it in ::parsePatternLibraryInfo().
    $assets = $pattern->getAssets();
    foreach ($assets as $asset => $data) {
      if (empty($data)) {
        continue;
      }

      $full_path = $this->root . '/' . $data;
      if (file_exists($full_path)) {
        $assets[$asset] = file_get_contents($full_path);
      }
      else {
        $this->logger->warning('Unable to load "@type" asset from "@location" for pattern "@pattern".', [
          '@type' => $asset,
          '@location' => $data,
          '@pattern' => $pattern->getAssetId(),
        ]);
      }
    }

    return $assets;
  }

  /**
   * Attempt to create a pattern instance from discovered data.
   *
   * Create a pattern instance from all discovered data and default values. In
   * the case of any failures, exceptions will be captured and recorded in the
   * provided $exceptions array for later logging.
   *
   * @param string $pattern_name
   *   The name of the pattern to be created.
   * @param array $defaults
   *   An associative array of default values for instantiating the pattern.
   * @param array $exceptions
   *   An associative array passed by reference for capturing and recording any
   *   exceptions for later logging via ::logParserExceptions().
   *
   * @return \Drupal\patternkit\Entity\PatternInterface|null
   *   The instantiated Pattern entity or NULL if an exception was encountered.
   *
   * @see self::logParserExceptions()
   */
  protected function loadPattern(string $pattern_name, array $defaults, array &$exceptions): ?PatternInterface {
    $success = FALSE;
    try {
      // Create the pattern from defaults.
      $pattern = $this->createPattern($pattern_name, $defaults);

      // Process assets and capture them in the cached pattern definition.
      $pattern->fetchAssets();

      // Track successful completion for the return value.
      $success = TRUE;
    }
    catch (PluginNotFoundException | PluginException $e) {
      // Record the exception and don't include the pattern in results.
      $exceptions[$pattern_name] = [
        $e,
        'Unable to load plugin "%plugin" for pattern "%pattern": @exception',
        [
          '%plugin' => isset($pattern) ? $pattern->getLibraryPluginId() : '',
          '%pattern' => $pattern_name,
          '@exception' => $e->getMessage(),
        ],
      ];
    }
    catch (PatternAssetException | SchemaException $e) {
      // Record the exception and don't include the pattern in results.
      $exceptions[$pattern_name] = [
        $e,
        '@message<br><pre>@trace</pre>',
        [
          '@message' => $e->getMessage(),
          '@trace' => $e->getTraceAsString(),
        ],
      ];
    }
    catch (\JsonException $e) {
      // Record the exception and don't include the pattern in results.
      $exceptions[$pattern_name] = [
        $e,
        'Unable to parse schema content for "%pattern": @exception',
        [
          '%pattern' => $pattern_name,
          '@exception' => $e->getMessage(),
        ],
      ];
    }

    return $success ? $pattern : NULL;
  }

  /**
   * Log encountered exceptions during library parsing and discovery.
   *
   * @param string $library
   *   The library being processed.
   * @param array $exceptions
   *   An array of exception data keyed by the pattern identifier being
   *   processed while the exception was encountered. Each entry should be an
   *   array of the following values:
   *   - The exception encountered.
   *   - A message for logging.
   *   - Any context values for logging.
   */
  protected function logParserExceptions(string $library, array $exceptions): void {
    Assert::allIsArray($exceptions, 'All exception values are expected to be an array.');
    Assert::allCount($exceptions, 3, 'All exception events are expected to have 3 elements.');

    // Log debug messages if debug mode is enabled. See patternkit.services.yml.
    $debug = $this->isDebug();

    // Log each of the exceptions.
    foreach ($exceptions as [$exception, $message, $context]) {
      switch (TRUE) {
        case $exception instanceof PluginNotFoundException:
        case $exception instanceof PluginException:
        case $exception instanceof SchemaException:
        case $exception instanceof \JsonException:
          $this->logger->warning($message, $context);
          break;

        case $exception instanceof PatternAssetException:
          if ($debug) {
            $this->logger->debug($message, $context);
          }
          break;

        default:
          $this->logger->warning($message, $context);
      }
    }

    if (($count = count($exceptions)) && $debug) {
      $this->logger->debug('Unable to load @count patterns from library @library: <br/>@patterns',
        [
          '@count' => $count,
          '@library' => $library,
          '@patterns' => implode(', ', array_keys($exceptions)),
        ]
      );
    }
  }

  /**
   * Get pattern default values general to the whole pattern library.
   *
   * @param \Drupal\patternkit\PatternLibrary $library
   *   The library information object.
   * @param string $library_path
   *   The library path being processed. This is typically a path relative to
   *   the Drupal web root.
   *
   * @return array
   *   An associative array of default values for all patterns in the library.
   */
  protected function getLibraryDefaults(PatternLibrary $library, string $library_path): array {
    $pattern_collections = $library->getPatternInfo();
    $collection_info = $pattern_collections[$library_path];

    // Prepare pattern value defaults for all items in this library.
    return [
      'category'        => $collection_info['category'] ?? '',
      'library'         => $library->id(),
      'libraryPluginId' => $this->getPluginId(),
      'version'         => $library->version ?: static::VERSION_PLACEHOLDER,
    ];
  }

  /**
   * Get default values for instantiation of a pattern being discovered.
   *
   * @param \Drupal\patternkit\PatternLibrary $library
   *   The pattern library data object.
   * @param string $library_path
   *   The path to the pattern library being processed. This should be a path
   *   relative to the Drupal web root and should not include a trailing slash.
   * @param string $component_name
   *   The name of the component being processed. This is usually the basename
   *   of the files that were discovered.
   * @param string[] $files
   *   An associative array of discovered assets. Each value is the full file
   *   path from the system root to the file, and each is keyed by the file
   *   extension.
   *
   * @return array
   *   An associative array of pattern values to use for instantiation.
   */
  protected function getPatternDefaults(PatternLibrary $library, string $library_path, string $component_name, array $files): array {
    // Prepare pattern instance defaults.
    return [
      'assets' => [],
      'name' => $component_name,
    ];
  }

  /**
   * Determine a pattern category from library information.
   *
   * @param \Drupal\patternkit\PatternLibrary $library
   *   The pattern library data object.
   * @param string $library_path
   *   The path to the library being processed.
   * @param array $pattern_values
   *   Pattern values prepared so far.
   *
   * @return string
   *   A suggested pattern category.
   */
  protected function getSuggestedPatternCategory(PatternLibrary $library, string $library_path, array $pattern_values): string {
    $pattern_collection = $library->getPatternInfo()[$library_path];

    if (!empty($pattern_collection['category'])) {
      return $pattern_collection['category'];
    }

    $pattern_path = $pattern_values['path'];
    $category_guess = strstr($pattern_path, DIRECTORY_SEPARATOR, TRUE);

    return $category_guess ?: self::DEFAULT_CATEGORY;
  }

}
