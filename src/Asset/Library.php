<?php

namespace Drupal\patternkit\Asset;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Asset\Exception\InvalidLibrariesOverrideSpecificationException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Enhances core library handling.
 *
 * Patternkit Library is meant to be an enhancement to Drupal Core
 * Library handling that allows for arbitrary libraries and loading via plugins.
 *
 * The existing library infrastructure is likely overcomplicated and could be
 * genericized and simplified.
 *
 * For example, the current library system hard-codes asset types CSS and JS
 * which leaves little room for pleasantly extending with additional asset types
 *  - it is also highly reliant on a rendering path using ['#attachments'] which
 * require the full AssetResolver pathway.
 *
 * @todo Remove when https://www.drupal.org/project/drupal/issues/3154343 lands.
 *
 * Discovers available asset libraries in Drupal.
 */
class Library extends CacheCollector implements LibraryInterface, ContainerInjectionInterface {

  /** @var string */
  const DEFAULT_LIBRARY_PLUGIN_ID = 'twig';

  /** @var string */
  const PERSISTENT_CACHE_ID = 'patternkit.library.cache';

  /** @var \Drupal\Core\Config\ImmutableConfig */
  protected $config;

  /** @var \Drupal\Core\Extension\Extension[] */
  protected $extensionList;

  /** @var array */
  protected $libraries;

  /**
   * The final library definitions, statically cached.
   *
   * Hooks hook_library_info_alter() and hook_js_settings_alter() allow modules
   * and themes to dynamically alter a library definition (once per request).
   *
   * @var array
   */
  protected $libraryDefinitions = [];

  /** @var \Drupal\patternkit\PatternLibraryPluginManager */
  protected $libraryPluginManager;

  /** @var \Drupal\Core\Extension\ModuleHandlerInterface */
  protected $moduleHandler;

  /** @var string */
  protected $root;

  /** @var \Drupal\Core\Theme\ThemeManagerInterface */
  protected $themeManager;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Symfony app container.
   *
   * @return self
   */
  public static function create(ContainerInterface $container): self {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache */
    $cache = $container->get('cache.discovery');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    /** @var \Drupal\patternkit\PatternLibraryPluginManager $library_plugin_manager */
    $library_plugin_manager = $container->get('plugin.manager.library.pattern');
    /** @var \Drupal\Core\Lock\LockBackendInterface $lock */
    $lock = $container->get('lock');
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $container->get('module_handler');
    /** @var string $root */
    $root = $container->get('app.root');
    /** @var \Drupal\Core\Theme\ThemeManagerInterface $theme_manager */
    $theme_manager = $container->get('theme.manager');
    return new static(
      $cache,
      $config_factory,
      $library_plugin_manager,
      $lock,
      $module_handler,
      $root,
      $theme_manager);
  }

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Provide a default cache.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Provide the factory for configuration objects.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Provide the lock backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Provide a module handler.
   * @param \Drupal\patternkit\PatternLibraryPluginManager $library_plugin_manager
   *   Provide a plugin manager for pattern libraries.
   * @param string $root
   *   The app root path.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   Provide a theme manager.
   */
  public function __construct(
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory,
    PatternLibraryPluginManager $library_plugin_manager,
    LockBackendInterface $lock,
    ModuleHandlerInterface $module_handler,
    $root,
    ThemeManagerInterface $theme_manager) {

    $this->config = $config_factory->get(PatternkitSettingsForm::SETTINGS);
    $this->libraryPluginManager = $library_plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->root = $root;
    $this->themeManager = $theme_manager;
    parent::__construct(static::PERSISTENT_CACHE_ID, $cache, $lock);
  }

  /**
   * Apply pattern libraries overrides specified for the current active theme.
   *
   * @param array $libraries
   *   The libraries definitions.
   * @param string $extension
   *   The extension in which these libraries are defined.
   *
   * @return array
   *   The modified libraries definitions.
   *
   * @todo Borrowed from core and needs a rewrite.
   *
   * @see \Drupal\Core\Asset\LibraryDiscoveryParser::applyLibrariesOverride
   *
   * @throws \Drupal\Core\Asset\Exception\InvalidLibrariesOverrideSpecificationException
   */
  protected function applyLibrariesOverride($libraries, $extension): array {
    $active_theme = $this->themeManager->getActiveTheme();
    // ActiveTheme::getLibrariesOverride() returns libraries-overrides for the
    // current theme as well as all its base themes.
    $all_libraries_overrides = $active_theme->getLibrariesOverride();
    foreach ($all_libraries_overrides as $theme_path => $libraries_overrides) {
      /**
       * @var string $library_name
       * @var \Drupal\patternkit\PatternLibrary $library
       */
      foreach ($libraries as $library_name => $library) {
        if (!is_subclass_of($library, PatternLibrary::class)) {
          continue;
        }
        // Process libraries overrides.
        if (isset($libraries_overrides["$extension/$library_name"])) {
          // Active theme defines an override for this library.
          $override_definition = $libraries_overrides["$extension/$library_name"];
          if (is_string($override_definition) || $override_definition === FALSE) {
            // A string or boolean definition implies an override (or removal)
            // for the whole library. Use the override key to specify that this
            // library will be overridden when it is called.
            // @see \Drupal\Core\Asset\LibraryDiscovery::getLibraryByName()
            if ($override_definition) {
              $library->setOverride($override_definition);
            }
            else {
              $library->setOverride(FALSE);
            }
          }
          elseif (is_array($override_definition)) {
            // An array definition implies an override for an asset within this
            // library.
            foreach ($override_definition as $sub_key => $value) {
              // Throw an exception if the asset is not properly specified.
              if (!is_array($value)) {
                throw new InvalidLibrariesOverrideSpecificationException(sprintf('Library asset %s is not correctly specified. It should be in the form "extension/library_name/sub_key/path/to/asset.js".', "$extension/$library_name/$sub_key"));
              }
              if ($sub_key === 'patterns') {
                foreach ($value as $category => $overrides) {
                  $this->setOverrideValue($library->patterns, [$sub_key, $category], $overrides, $theme_path);
                }
              }
              else {
                $this->setOverrideValue($library->patterns, [$sub_key], $value, $theme_path);
              }
            }
          }
          $libraries[$library_name] = $library;
        }
      }
    }
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->libraryDefinitions = [];
    $this->clear();
  }

  /**
   * Builds up all the libraries information of an extension.
   *
   * @param string $extension_type
   *   The type of the extension that registered a library.
   * @param string $extension
   *   The name of the extension that registered a library.
   *
   * @return \Drupal\patternkit\PatternLibrary[]
   *   All library definitions of the passed extension.
   *
   * @throws \Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException
   *   Thrown when a library has no js/css/setting.
   * @throws \UnexpectedValueException
   *   Thrown when a js file defines a positive weight.
   *
   * @todo Remove current triggers for IncompleteLibraryDefinitionException and
   *   only throw when there are actual errors or missing lines, then add
   *   helpful error logs that have fix suggestions.
   */
  public function buildByExtension($extension_type, $extension): array {
    if ($extension === 'core') {
      $path = 'core';
    }
    else {
      $path = $this->drupalGetPath($extension_type, $extension);
    }

    $libraries = $this->parseLibraryInfo($extension_type, $extension, $path);
    $libraries = $this->applyLibrariesOverride($libraries, $extension);

    foreach ($libraries as $id => &$library) {
      if (!isset($library->patterns)) {
        unset($libraries[$id]);
        continue;
      }

      // Replace source keys with a list of validated paths.
      foreach ($library->patterns as $source => $options) {
        unset($library->patterns[$source]);
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
        elseif (strpos($source, '/') === 0) {
          // An absolute path maps to DRUPAL_ROOT / base_path().
          if ($source[1] !== '/') {
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
          $options['data'] = $path . '/' . $source;
        }

        if (!isset($library->version)) {
          $options['version'] = $this->extensionList[$extension]->info['version'];
        }
        else {
          $options['version'] = $library->version;
        }
        $library->patterns[] = $options;
      }
    }

    return $libraries;
  }

  /**
   * {@inheritDoc}
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getAssets(): array {
    $assets = [];
    foreach ($this->getLibraryDefinitions() as $library) {
      foreach ($library->patterns as $id => $pattern) {
        $assets['@' . $library->id() . '/' . $id] = $pattern;
      }
    }
    return $assets;
  }

  /**
   * Returns a static array of libraries with parsed information.
   *
   * @param bool $reset
   *   TRUE if the library cache should be re-parsed.
   *
   * @return \Drupal\patternkit\PatternLibrary[]
   *   An array of libraries and information.
   */
  public function getLibraries($reset = FALSE): array {
    if (isset($this->libraries) && !$reset) {
      return $this->libraries;
    }
    $libraries = [];
    foreach ($this->getExtensionList() as $extension_name => $extension) {
      if (!property_exists($extension, 'status') || !$extension->status) {
        continue;
      }
      $libraries[] = $this->buildByExtension($extension->getType(), $extension_name);
    }
    // Collapses the array and allows extensions with a bigger weight to
    // overwrite other libraries if they share the same ID. Typically, this
    // won't happen, but we want to preserve older-style overrides not using
    // the Library Override API.
    $this->libraries = array_merge([], ...$libraries);
    return $this->libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrariesByExtension($extension) {
    if (!isset($this->libraryDefinitions[$extension])) {
      $libraries = $this->get($extension);
      $this->libraryDefinitions[$extension] = [];
      foreach ($libraries as $name => $definition) {
        $this->libraryDefinitions[$extension][$name] = $definition;
      }
    }

    return $this->libraryDefinitions[$extension];
  }

  /**
   * {@inheritDoc}
   */
  public function getLibraryAsset($key) {
    $library_name = substr($key, 0, strpos($key, '/'));
    /** @var \Drupal\patternkit\PatternLibrary $library */
    $library = $this->get(trim($library_name, '@'));
    return $library->patterns[substr($key, strlen($library_name . '/'))] ?? NULL;
  }

  /**
   * Returns the specified Patternkit module metadata.
   *
   * @return \Drupal\patternkit\PatternLibrary[]
   *   Array of metadata objects found or object if specific pattern requested.
   *   Keyed by library name in the format.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getLibraryDefinitions(): array {
    if (!empty($this->libraryDefinitions)) {
      return $this->libraryDefinitions;
    }
    $cache_enabled = $this->config->get('patternkit_cache_enabled');
    // If cache is enabled, attempt to load from cache.
    if ($cache_enabled
      && ($cache = $this->cache->get(static::PERSISTENT_CACHE_ID))) {
      $cached_metadata = $cache->data;
    }
    else {
      try {
        $cached_metadata = $this->getLibraryMetadata();
      }
      catch (\RuntimeException $exception) {
        throw new PluginException($exception->getMessage());
      }
      // Cache the data so that we don't have to build it again.
      // (if cache enabled, otherwise just a slow, redundant memcache set).
      if ($cache_enabled) {
        // Explicit copy of the data into cache set to avoid implicit copy.
        // @todo Evaluate encoding via JSON instead to shrink data size.
        $this->cache->set(static::PERSISTENT_CACHE_ID,
          $cached_metadata);
        // @todo Evaluate if we should validate the cache set worked properly.
      }
    }
    $this->libraryDefinitions = $cached_metadata;
    return $cached_metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryByName($extension, $name) {
    $libraries = $this->getLibrariesByExtension($extension);
    if (!isset($libraries[$name])) {
      return FALSE;
    }
    if (isset($libraries[$name]['deprecated'])) {
      @trigger_error(str_replace('%library_id%', "$extension/$name", $libraries[$name]['deprecated']), E_USER_DEPRECATED);
    }
    return $libraries[$name];
  }

  /**
   * Wraps drupal_get_path().
   *
   * Returns the path to a system item (module, theme, etc.).
   *
   * @param $type
   *   The type of the item; one of 'core', 'profile', 'module', 'theme', or
   *   'theme_engine'.
   * @param $name
   *   The name of the item for which the path is requested. Ignored for
   *   $type 'core'.
   *
   * @return string
   *   The path to the requested item or an empty string if the item is not
   *   found.
   */
  protected function drupalGetPath($type, $name): string {
    return drupal_get_path($type, $name);
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

  /**
   * Returns a list of all 'extension.list' services.
   *
   * This is a workaround for the fact that those services do not have tags.
   *
   * @see https://www.drupal.org/project/drupal/issues/2940481
   *
   * @todo Replace with Symfony tag methods when they become available.
   *
   * @return \Drupal\Core\Extension\Extension[]
   */
  protected function getExtensionList(): array {
    if (isset($this->extensionList)) {
      return $this->extensionList;
    }
    $container = \Drupal::getContainer();
    if ($container === NULL) {
      return [];
    }
    $extension_prefix = 'extension.list.';
    $list_services = [
      'extension.list.module',
      'extension.list.profile',
      'extension.list.theme',
      'extension.list.theme_engine',
    ];
    if (method_exists($container, 'getServiceIds')) {
      $service_ids = $container->getServiceIds();
      $list_services = [];
      foreach ($service_ids as $service_id) {
        if (strpos($service_id, $extension_prefix) === 0) {
          $list_services[] = $service_id;
        }
      }
    }
    $this->extensionList = [];
    foreach ($list_services as $service_name) {
      /** @var ExtensionList|null $extension_service */
      $extension_service = $container->get($service_name);
      if ($extension_service === NULL) {
        continue;
      }
      /** \Drupal\Core\Extension\Extension[] $extension_list */
      $this->extensionList += $extension_service->getList();
    }
    return $this->extensionList;
  }

  /**
   * Utility function to get all Patternkit module metadata.
   *
   * @return array
   *   Array of metadata objects found.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   *   When a circular reference is detected.
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
   *   When the service is not defined.
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *   Thrown if an invalid library path was passed to the parser.
   */
  protected function getLibraryMetadata(): array {
    $metadata = [];
    $plugin_default = self::DEFAULT_LIBRARY_PLUGIN_ID;
    $plugin_list = implode(', ', array_keys($this->libraryPluginManager->getSortedDefinitions()));
    foreach ($this->getLibraries() as $library_name => $library) {
      $pattern_libraries = $library->patterns ?? [];
      if (!empty($pattern_libraries)) {
        $library->patterns = [];
      }
      $lib_info = $library->getPatternInfo();
      foreach ($pattern_libraries as $index => $info) {
        $path = $info['data'] ?? '';
        if (empty($path)) {
          \Drupal::logger('patternkit')->info(
            "No path set for $library_name under the patterns key. Data: " . print_r($info, TRUE)
          );
          continue;
        }
        if (empty($info['plugin'])) {
          \Drupal::logger('patternkit')->notice(
            "No 'plugin' key set for the '$library_name' pattern library, defaulting to '$plugin_default'."
            . " Recommend setting the key to one of the available plugins: $plugin_list."
          );
          $info['plugin'] = $plugin_default;
        }
        $lib_info[$path] = $info;
        $library->setPatternInfo($lib_info);
        /** @var \Drupal\patternkit\PatternLibraryPluginInterface $plugin */
        try {
          $plugin = $this->libraryPluginManager->createInstance($info['plugin']);
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
        /** @var \Drupal\patternkit\Entity\Pattern $pattern */
        $library->patterns = array_merge($library->patterns, $plugin->getMetadata($library->getExtension(), $library, $path));
        if (empty($library->patterns)) {
          \Drupal::logger('patternkit')->info(
            "No patterns found in $library_name library for path $path."
          );
        }
      }
      $metadata[$library_name] = $library;
    }
    return $metadata;
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
   * Parses a given library file and allows modules and themes to alter it.
   *
   * This method sets the parsed information onto the library property.
   *
   * Library information is parsed from *.libraries.yml files; see
   * editor.libraries.yml for an example. Every library must have at least one
   * js or css entry. Each entry starts with a machine name and defines the
   * following elements:
   * - js: A list of JavaScript files to include. Each file is keyed by the file
   *   path. An item can have several attributes (like HTML
   *   attributes). For example:
   *   @code
   *   js:
   *     path/js/file.js: { attributes: { defer: true } }
   *   @endcode
   *   If the file has no special attributes, just use an empty object:
   *   @code
   *   js:
   *     path/js/file.js: {}
   *   @endcode
   *   The path of the file is relative to the module or theme directory, unless
   *   it starts with a /, in which case it is relative to the Drupal root. If
   *   the file path starts with //, it will be treated as a protocol-free,
   *   external resource (e.g., //cdn.com/library.js). Full URLs
   *   (e.g., http://cdn.com/library.js) as well as URLs that use a valid
   *   stream wrapper (e.g., public://path/to/file.js) are also supported.
   * - css: A list of categories for which the library provides CSS files. The
   *   available categories are:
   *   - base
   *   - layout
   *   - component
   *   - state
   *   - theme
   *   Each category is itself a key for a sub-list of CSS files to include:
   *   @code
   *   css:
   *     component:
   *       css/file.css: {}
   *   @endcode
   *   Just like with JavaScript files, each CSS file is the key of an object
   *   that can define specific attributes. The format of the file path is the
   *   same as for the JavaScript files.
   * - dependencies: A list of libraries this library depends on.
   * - version: The library version. The string "VERSION" can be used to mean
   *   the current Drupal core version.
   * - header: By default, JavaScript files are included in the footer. If the
   *   script must be included in the header (along with all its dependencies),
   *   set this to true. Defaults to false.
   * - minified: If the file is already minified, set this to true to avoid
   *   minifying it again. Defaults to false.
   * - remote: If the library is a third-party script, this provides the
   *   repository URL for reference.
   * - license: If the remote property is set, the license information is
   *   required. It has 3 properties:
   *   - name: The human-readable name of the license.
   *   - url: The URL of the license file/information for the version of the
   *     library used.
   *   - gpl-compatible: A Boolean for whether this library is GPL compatible.
   *
   * See https://www.drupal.org/node/2274843#define-library for more
   * information.
   *
   * @param string $extension_type
   *   The type of the extension that registered a library.
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $path
   *   The relative path to the extension.
   *
   * @return \Drupal\patternkit\PatternLibrary[]
   *   An array of parsed library data.
   *
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *   Thrown when a parser exception got thrown.
   */
  protected function parseLibraryInfo($extension_type, $extension, $path): array {
    $libraries = [];

    $library_file = $path . '/' . $extension . '.libraries.yml';
    if (file_exists($this->root . '/' . $library_file)) {
      try {
        $libraries = Yaml::decode(file_get_contents($this->root . '/' . $library_file));
      }
      catch (InvalidDataTypeException $e) {
        // Rethrow a more helpful exception to provide context.
        throw new InvalidLibraryFileException(sprintf('Invalid library definition in %s: %s', $library_file, $e->getMessage()), 0, $e);
      }
    }

    // Allow modules to add dynamic library definitions.
    $hook = 'library_info_build';
    if ($this->moduleHandler->implementsHook($extension, $hook)) {
      $libraries = NestedArray::mergeDeep($libraries, $this->moduleHandler->invoke($extension, $hook));
    }

    // Allow modules to alter the module's registered libraries.
    $this->moduleHandler->alter('library_info', $libraries, $extension);
    $this->themeManager->alter('library_info', $libraries, $extension);

    foreach ($libraries as $id => $library) {
      $library['extensionType'] = $extension_type;
      $library['extension'] = $extension;
      $library['id'] = $id;
      $serializer = new Serializer([new PropertyNormalizer()]);
      /** @var \Drupal\patternkit\PatternLibrary $library */
      $libraries[$id] = $serializer->denormalize($library, PatternLibrary::class);
    }

    return $libraries;
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function resolveCacheMiss($key) {
    $definitions = $this->getLibraryDefinitions();
    $this->storage[$key] = $definitions[$key] ?? NULL;
    $this->persist($key);

    return $this->storage[$key];
  }

  /**
   * Ensures that a full path is returned for an overriding theme asset.
   *
   * @param string $theme_path
   *   The theme or base theme.
   * @param string $overriding_asset
   *   The overriding library asset.
   *
   * @return string
   *   A fully resolved theme asset path relative to the Drupal directory.
   */
  protected function resolveThemeAssetPath($theme_path, $overriding_asset): string {
    if ($overriding_asset[0] !== '/' && !$this->isValidUri($overriding_asset)) {
      // The destination is not an absolute path and it's not a URI (e.g.
      // public://generated_js/example.js or http://example.com/js/my_js.js), so
      // it's relative to the theme.
      return '/' . $theme_path . '/' . $overriding_asset;
    }
    return $overriding_asset;
  }

  /**
   * Overrides the specified library patterns.
   *
   * @param array $patterns
   *   The patterns asset list.
   * @param array $sub_key
   *   An array containing the sub-keys specifying the library asset, e.g.
   *   @code['patterns']@endcode
   * @param array $overrides
   *   Specifies the overrides, this is an array where the key is the asset to
   *   be overridden while the value is overriding asset.
   * @param string $theme_path
   *   The theme path for the override.
   */
  protected function setOverrideValue(
    array &$patterns,
    array $sub_key,
    array $overrides,
    $theme_path) {

    foreach ($overrides as $original => $replacement) {
      // Get the attributes of the asset to be overridden. If the key does
      // not exist, then throw an exception.
      $key_exists = NULL;
      $parents = array_merge($sub_key, [$original]);
      // Save the attributes of the library asset to be overridden.
      $attributes = NestedArray::getValue($patterns, $parents, $key_exists);
      if ($key_exists) {
        // Remove asset to be overridden.
        NestedArray::unsetValue($patterns, $parents);
        // No need to replace if FALSE is specified, since that is a removal.
        if ($replacement) {
          // Ensure the replacement path is relative to drupal root.
          $replacement = $this->resolveThemeAssetPath($theme_path, $replacement);
          $new_parents = array_merge($sub_key, [$replacement]);
          // Replace with an override if specified.
          NestedArray::setValue($patterns, $new_parents, $attributes);
        }
      }
    }
  }

}
