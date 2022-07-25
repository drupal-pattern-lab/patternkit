<?php

namespace Drupal\patternkit\Asset;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Lock\LockBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A service for resolving library namespaces to processed library definitions.
 *
 * This service specially validates and processes pattern data defined in
 * library definitions. It is also implemented as a CacheCollector
 * implementation to store the processed library definitions persistently.
 */
class LibraryNamespaceResolver extends CacheCollector implements LibraryNamespaceResolverInterface {

  /**
   * The cache ID for storing discovery metadata.
   *
   * @var string
   */
  const PERSISTENT_CACHE_ID = 'patternkit.library_namespaces';

  /**
   * Cache tags to apply to cache entries.
   *
   * @var string[]
   */
  const CACHE_TAGS = [
    // Clear cache entries when all patternkit data is invalidated.
    'patternkit',

    // Clear this library data specifically.
    'patternkit_libraries',

    // Clear when the underlying library data is invalidated.
    'library_info',
  ];

  /**
   * The dependency container for fetching extension list services.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * The library discovery service for loading library definitions.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected LibraryDiscoveryInterface $libraryDiscovery;

  /**
   * The collection of loaded extensions to search for libraries.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected array $extensionList;

  /**
   * Creates a new instance of LibraryNamespaceResolver.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   The library discovery service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Provide a default cache.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Provide the lock backend.
   */
  public function __construct(
    ContainerInterface $container,
    LibraryDiscoveryInterface $libraryDiscovery,
    CacheBackendInterface $cache,
    LockBackendInterface $lock
  ) {
    $this->container = $container;
    $this->libraryDiscovery = $libraryDiscovery;

    parent::__construct(self::PERSISTENT_CACHE_ID, $cache, $lock, self::CACHE_TAGS);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryFromNamespace(string $namespace): ?array {
    assert($namespace[0] === '@', 'Namespaces are expected to include a leading "@" symbol.');

    $library = $this->get($namespace);

    // Log a warning for deprecated libraries.
    if ($library !== NULL && isset($libraries[$namespace]['deprecated'])) {
      $library_id = $libraries[$namespace]['extension'] . '/' . substr($namespace, 1);
      // phpcs:ignore
      @trigger_error(str_replace('%library_id%', $library_id, $libraries[$namespace]['deprecated']), E_USER_DEPRECATED);
    }

    return $library;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryDefinitions(bool $reset = FALSE): array {
    if ($reset) {
      $this->reset();
    }
    else {
      // Return the cached definitions if available.
      $this->lazyLoadCache();
      if (!empty($this->storage)) {
        return $this->storage;
      }
    }

    $extensions = $this->getExtensionList();

    $libraries = [];
    foreach ($extensions as $extension_name => $extension) {
      if (!property_exists($extension, 'status') || !$extension->status) {
        continue;
      }

      $libraries[] = $this->getLibrariesByExtension($extension_name);
    }

    // Collapses the array and allows extensions with a bigger weight to
    // overwrite other libraries if they share the same ID. Typically, this
    // won't happen, but we want to preserve older-style overrides not using
    // the Library Override API.
    // @todo Confirm whether this indexing actually represents weight for our needs.
    // @todo Confirm whether we actually want to continue supporting this.
    // NB. All of the discovered library namespaces have been marked for
    // persistence during the loading process when discovered in their specific
    // extensions.
    $this->storage = array_merge([], ...$libraries);

    return $this->storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrariesByExtension(string $extension_name): array {
    $extension_libraries = $this->libraryDiscovery->getLibrariesByExtension($extension_name);
    $extension = $this->getExtensionList()[$extension_name];

    // Capture additional information for easy reference later.
    $libraries = [];
    foreach ($extension_libraries as $library_name => $definition) {
      $definition['id'] = $library_name;
      $definition['extension'] = $extension_name;
      $definition['extensionType'] = $extension->getType();
      $definition['extensionPath'] = $extension->getPath();
      $definition['namespace'] = "@$library_name";

      // Process pattern data if any is available.
      if (isset($definition['patterns'])) {
        $this->normalizeLibraryPatternData($definition);
      }

      // Key the library definitions by namespace.
      $libraries[$definition['namespace']] = $definition;

      // Set the cache entry for the namespace. If an extension is processed
      // later that provides a library with the same namespace, this value will
      // be overridden before being persisted at the end of the request.
      $this->set($definition['namespace'], $definition);
      $this->persist($definition['namespace']);
    };

    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($key) {
    // Process all library definitions to aggregate any overridden namespace
    // definitions.
    $definitions = $this->getLibraryDefinitions(TRUE);
    $this->storage[$key] = $definitions[$key] ?? NULL;
    $this->persist($key);

    return $this->storage[$key];
  }

  /**
   * Standardize information for each pattern library definition.
   *
   * Standardize each pattern library definition to contain the following keys:
   * - 'type': The type of pattern library collection to be loaded.
   * - 'data': The source path or URL for pattern library content.
   *
   * Following this standardization, the 'patterns' key for the given library
   * definition now contains a numerically indexed list of standardized pattern
   * library definitions.
   *
   * @param array $library_definition
   *   The loaded library definition. This is passed by reference and altered
   *   directly.
   */
  protected function normalizeLibraryPatternData(array &$library_definition): void {
    // Replace source keys with a list of validated paths.
    foreach ($library_definition['patterns'] as $source => $options) {
      // Unset the original data to be replaced with normalized content.
      unset($library_definition['patterns'][$source]);

      // Allow to omit the options hashmap in YAML declarations.
      if (!is_array($options)) {
        $options = [];
      }

      // By default, all library assets are files.
      if (!strpos($source, '.')) {
        $options['type'] = 'directory';
      }
      elseif (!isset($options['type'])) {
        $options['type'] = 'file';
      }

      if ($options['type'] === 'external') {
        $options['data'] = $source;
      }
      // Determine the file asset URI.
      elseif ($source[0] === '/') {
        // An absolute path maps to DRUPAL_ROOT / base_path().
        if ($source[1] !== '/') {
          // Remove the leading slash from an absolute path.
          $options['data'] = substr($source, 1);
        }
        // A protocol-free URI (e.g., //cdn.com/example.js) is external.
        else {
          $options['type'] = 'external';
          $options['data'] = $source;
        }
      }
      // A stream wrapper URI (e.g., public://generated_js/example.js).
      elseif ($this->fileValidUri($source)) {
        $options['data'] = $source;
      }
      // A regular URI (e.g., http://example.com/example.js) without
      // 'external' explicitly specified, which may happen if, e.g.
      // libraries-override is used.
      elseif ($this->isValidUri($source)) {
        $options['type'] = 'external';
        $options['data'] = $source;
      }
      // By default, file paths are relative to the registering extension.
      else {
        $options['data'] = $library_definition['extensionPath'] . '/' . $source;
      }

      if (!isset($definition['version'])) {
        // Fall back to the providing extension's version or the Drupal core
        // version if one is not provided.
        $options['version'] = $this->extensionList[$library_definition['extension']]->info['version'] ?? \Drupal::VERSION;
      }
      else {
        $options['version'] = $definition['version'];
      }

      // Add the assembled pattern library definition back into the overall
      // library patterns definition list.
      $library_definition['patterns'][$options['data']] = $options;
    }
  }

  /**
   * Returns a list of all enabled extensions for discovery.
   *
   * This is a workaround for the fact that those services do not have tags.
   *
   * @see https://www.drupal.org/project/drupal/issues/2940481
   *
   * @todo Replace with Symfony tag methods when they become available.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   A list of loaded extensions for processing.
   */
  protected function getExtensionList(): array {
    if (isset($this->extensionList)) {
      return $this->extensionList;
    }

    // Attempt to auto-discover extension list services if possible.
    if (method_exists($this->container, 'getServiceIds')) {
      $extension_prefix = 'extension.list.';
      $service_ids = $this->container->getServiceIds();
      $list_services = [];
      foreach ($service_ids as $service_id) {
        if (strpos($service_id, $extension_prefix) === 0) {
          $list_services[] = $service_id;
        }
      }
    }
    else {
      // Identify default services to load if autodiscovery is unavailable.
      $list_services = [
        'extension.list.module',
        'extension.list.profile',
        'extension.list.theme',
        'extension.list.theme_engine',
      ];
    }

    // Load the extension list from each of the discovered list services.
    $this->extensionList = [];
    foreach ($list_services as $service_name) {
      /** @var \Drupal\Core\Extension\ExtensionList|null $extension_service */
      $extension_service = $this->container->get($service_name);
      if ($extension_service === NULL) {
        continue;
      }
      /** @var \Drupal\Core\Extension\Extension[] $extension_list */
      $this->extensionList += $extension_service->getList();
    }

    return $this->extensionList;
  }

  /**
   * Determines if the supplied string is a valid URI.
   *
   * @param string $uri
   *   String to test as a valid URI.
   *
   * @return bool
   *   TRUE if the string is a valid URI.
   */
  protected function isValidUri($uri): bool {
    return count(explode('://', $uri)) === 2;
  }

  /**
   * Wraps file_valid_uri().
   *
   * Determines whether the URI has a valid scheme for file API operations.
   *
   * There must be a scheme and it must be a Drupal-provided scheme like
   * 'public', 'private', 'temporary', or an extension provided with
   * hook_stream_wrappers().
   *
   * @param string $uri
   *   The URI to be tested.
   *
   * @return bool
   *   TRUE if the URI is allowed.
   */
  protected function fileValidUri($uri): bool {
    // Assert that the URI has an allowed scheme. Bare paths are not allowed.
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    $uri_scheme = $stream_wrapper_manager::getScheme($uri);
    if (!$stream_wrapper_manager->isValidScheme($uri_scheme)) {
      return FALSE;
    }
    return TRUE;
  }

}
