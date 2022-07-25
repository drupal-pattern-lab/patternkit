<?php

namespace Drupal\patternkit\Asset;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * A cache collector implementation to load and cache pattern definitions.
 */
class PatternDiscoveryCollector extends CacheCollector {

  /**
   * The default library parser plugin to be used if not explicitly specified.
   *
   * @var string
   */
  const DEFAULT_LIBRARY_PLUGIN_ID = 'twig';

  /**
   * The cache ID for storing discovery metadata.
   *
   * @var string
   */
  const PERSISTENT_CACHE_ID = 'patternkit.patterns';

  /**
   * Cache tags to apply to cache entries.
   *
   * @var string[]
   */
  const CACHE_TAGS = [
    // Clear cache entries when all patternkit data is invalidated.
    'patternkit',

    // Clear this pattern data specifically.
    'patternkit_patterns',

    // Clear when the underlying library data is invalidated.
    'patternkit_libraries',
    'library_info',
  ];

  /**
   * The library namespace resolver service.
   *
   * @var \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface
   */
  protected LibraryNamespaceResolverInterface $namespaceResolver;

  /**
   * The pattern library plugin manager service.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager
   */
  protected PatternLibraryPluginManager $libraryPluginManager;

  /**
   * A logger for message output.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * A serializer for instantiating PatternLibrary instances.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected Serializer $serializer;

  /**
   * Creates an instance of PatternDiscoveryCollector.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface $namespaceResolver
   *   The library namespace resolver service.
   * @param \Drupal\patternkit\PatternLibraryPluginManager $libraryPluginManager
   *   The pattern library plugin manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger for message output.
   */
  public function __construct(CacheBackendInterface $cache, LockBackendInterface $lock, LibraryNamespaceResolverInterface $namespaceResolver, PatternLibraryPluginManager $libraryPluginManager, LoggerInterface $logger) {
    parent::__construct(self::PERSISTENT_CACHE_ID, $cache, $lock, self::CACHE_TAGS);

    $this->namespaceResolver = $namespaceResolver;
    $this->libraryPluginManager = $libraryPluginManager;
    $this->logger = $logger;

    $this->serializer = new Serializer([new PropertyNormalizer()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($key) {
    $this->storage[$key] = $this->getPatternsByNamespace($key);
    $this->persist($key);

    return $this->storage[$key];
  }

  /**
   * Load all patterns provided by a library.
   *
   * @param string $library
   *   The name of the library to load patterns for.
   *
   * @return \Drupal\patternkit\Entity\Pattern[]
   *   An array of all loaded patterns keyed by their namespaced pattern id.
   *
   * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *
   * @see \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibraryDefinitions()
   */
  protected function getPatternsByNamespace(string $library): array {
    // @todo Handle failures to load a library definition.
    $library_definition = $this->namespaceResolver->getLibraryFromNamespace($library);
    $pattern_info = $library_definition['patterns'] ?? [];

    // @todo Remove usage of the PatternLibrary object.
    $libraryObj = $this->getLibraryObject($library_definition);

    // Capture the original pattern info before processing to ensure nothing is
    // lost while iterating over and updating pattern library definitions.
    $lib_info = $libraryObj->getPatternInfo();

    $patterns = [];
    foreach ($pattern_info as $info) {
      // Path info may have been set during library info loading.
      // @see LibraryNamespaceResolver::getLibraryDefinitions
      // @see LibraryNamespaceResolver::normalizeLibraryPatternData
      $path = $info['data'] ?? '';
      if (empty($path)) {
        $this->logger->info('No path set for %library under the patterns key. Data:<br /><pre>@data</pre>',
          [
            '%library' => $library,
            '@data' => print_r($info, TRUE),
          ],
        );
        continue;
      }

      /** @var \Drupal\patternkit\PatternLibraryPluginInterface $plugin */
      try {
        $plugin = $this->getLibraryPlugin($info);
      }
      catch (PluginException $exception) {
        // Allow plugin fall-backs of type 'base_plugin.override_plugin'.
        $plugin_id = strstr($info['plugin'], '.', TRUE);
        if (empty($plugin_id)) {
          throw new InvalidLibraryFileException("Error loading pattern libraries via $plugin_id: " . $exception->getMessage());
        }
        try {
          $plugin = $this->libraryPluginManager->createInstance($plugin_id);
        }
        catch (PluginException $exception) {
          throw new InvalidLibraryFileException("Error loading pattern libraries via $plugin_id: " . $exception->getMessage());
        }
      }

      // Record the plugin that was identified for parsing this library.
      $info['plugin'] = $plugin->getPluginId();

      // Update the pattern info for this library on the library definition.
      $lib_info[$path] = $info;
      $libraryObj->setPatternInfo($lib_info);

      // Use the plugin to discover patterns for the given pattern library path.
      $discovered_patterns = $plugin->getMetadata($libraryObj->getExtension(), $libraryObj, $path);
      $patterns = array_merge($patterns, $discovered_patterns);
      if (empty($discovered_patterns)) {
        $this->logger->info("No patterns found in %library library for path %path.",
          [
            '%library' => $library,
            '%path' => $path,
          ]
        );
      }
    }

    // Prefix all pattern keys with the library namespace.
    $namespace = $library[0] === '@' ? $library : "@$library";
    $pattern_definitions = [];
    foreach (array_keys($patterns) as $name) {
      $pattern_definitions["$namespace/$name"] = $patterns[$name];
    }

    return $pattern_definitions;
  }

  /**
   * Get an instance of the library plugin for the given library.
   *
   * @param array $library_info
   *   The pattern library information as loaded from the *.libraries.yml file.
   *
   * @return \Drupal\patternkit\PatternLibraryPluginInterface
   *   An instance of the pattern library plugin for loading patterns in the
   *   given pattern library definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Throws an exception if the specified library parser plugin cannot be
   *   loaded successfully.
   */
  protected function getLibraryPlugin(array $library_info): PatternLibraryPluginInterface {
    if (empty($library_info['plugin'])) {
      $this->logger->notice(
        "No 'plugin' key set for the '%library' pattern library, defaulting to '%default'."
        . " Recommend setting the key to one of the available plugins: %plugins.", [
          '%library' => $library_info['name'],
          '%default' => self::DEFAULT_LIBRARY_PLUGIN_ID,
          '%plugins' => implode(', ', array_keys($this->libraryPluginManager->getSortedDefinitions())),
        ]
      );
      $library_info['plugin'] = self::DEFAULT_LIBRARY_PLUGIN_ID;
    }

    /** @var \Drupal\patternkit\PatternLibraryPluginInterface $plugin */
    $plugin = $this->libraryPluginManager->createInstance($library_info['plugin']);

    return $plugin;
  }

  /**
   * Get an instance of PatternLibrary for the given library definition.
   *
   * @param array $library_info
   *   A loaded library definition array.
   *
   * @return \Drupal\patternkit\PatternLibrary
   *   An instantiated PatternLibrary instance for the given library info.
   *
   * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
   *
   * @deprecated
   *   Usage of the PatternLibrary class should be refactored and removed.
   *
   * @todo Refactor to remove usage of the PatternLibrary class.
   */
  protected function getLibraryObject(array $library_info): PatternLibrary {
    /** @var \Drupal\patternkit\PatternLibrary $library */
    $library = $this->serializer->denormalize($library_info, PatternLibrary::class);

    return $library;
  }

}
