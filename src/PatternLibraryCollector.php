<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Asset\Exception\InvalidLibrariesOverrideSpecificationException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Asset\Exception\LibraryDefinitionMissingLicenseException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * The PatternLibraryCollector caches library information and performs retrieval
 * for a library discovery service. This merges partial functionality of
 * \Drupal\Core\Asset\LibraryDiscoveryParser with
 * \Drupal\Core\Asset\LibraryDiscoveryCollector.
 *
 * This will allow parsers to focus on library asset types (js, css, patterns)
 * and move the redundant code to the central collector service.
 *
 * @todo Trim this down even more.
 */
class PatternLibraryCollector extends CacheCollector implements ContainerInjectionInterface {

  protected const LOCAL_CACHE_ID = 'patternkit.library.static';

  /** @var string */
  protected const PERSISTENT_CACHE_ID = 'patternkit.library.cache';

  /** @var \Drupal\Core\Config\ImmutableConfig */
  protected $config;

  /** @var \Drupal\Core\Config\ConfigFactoryInterface */
  protected $configFactory;

  /** @var \Drupal\Core\File\FileSystemInterface */
  protected $fileSystem;

  /** @var \Drupal\patternkit\PatternLibraryPluginManager */
  protected $libraryPluginManager;

  /** @var \Drupal\Core\Extension\ModuleHandlerInterface */
  protected $moduleHandler;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /** @var \Drupal\Core\Theme\ThemeManagerInterface */
  protected $themeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Provide a default cache.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Provide the factory for configuration objects.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   Provide the file system service.
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
    FileSystemInterface $file_system,
    PatternLibraryPluginManager $library_plugin_manager,
    LockBackendInterface $lock,
    ModuleHandlerInterface $module_handler,
    $root,
    ThemeManagerInterface $theme_manager) {

    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get(PatternkitSettingsForm::SETTINGS);
    $this->fileSystem = $file_system;
    $this->libraryPluginManager = $library_plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->root = $root;
    $this->themeManager = $theme_manager;
    parent::__construct(static::PERSISTENT_CACHE_ID, $cache, $lock);
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Symfony app container.
   *
   * @return \Drupal\patternkit\PatternLibraryCollector
   */
  public static function create(ContainerInterface $container): self {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache */
    $cache = $container->get('cache.discovery');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    /** @var \Drupal\patternkit\PatternLibraryPluginManager $library_plugin_manager */
    $library_plugin_manager = $container->get('plugin.manager.library.pattern');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $container->get('file_system');
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
      $file_system,
      $library_plugin_manager,
      $lock,
      $module_handler,
      $root,
      $theme_manager);
  }

  /**
   * Returns the specified Patternkit module metadata.
   *
   * @return \Drupal\patternkit\Pattern[]
   *   Array of metadata objects found or object if specific pattern requested.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getLibraryDefinitions(): array {
    $config = $this->configFactory->get(PatternkitSettingsForm::SETTINGS);
    $cache_enabled = $config->get('patternkit_cache_enabled');
    // If cache is enabled, attempt to load from cache.
    if ($cache_enabled
      && ($cache = $this->cache->get(static::PERSISTENT_CACHE_ID))) {
      $cached_metadata = $cache->data;
    }
    else {
      $cached_metadata = $this->getLibraryMetadata();
      // Cache the data so that we don't have to build it again.
      // (if cache enabled, otherwise just a slow, redundant memcache set).
      if ($cache_enabled) {
        // Explicit copy of the data into cache_set to avoid implicit copy.
        $this->cache->set(static::PERSISTENT_CACHE_ID,
          $cached_metadata);
      }
    }
    return $cached_metadata;
  }

  /**
   * Builds up all the libraries information of an extension.
   *
   * @param string $extension_type
   *   The type of the extension that registered a library.
   * @param string $extension
   *   The name of the extension that registered a library.
   *
   * @return array
   *   All library definitions of the passed extension.
   *
   * @throws \Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException
   *   Thrown when a library has no js/css/setting.
   * @throws \UnexpectedValueException
   *   Thrown when a js file defines a positive weight.
   */
  public function buildByExtension($extension_type, $extension): array {
    if ($extension === 'core') {
      $path = 'core';
    }
    else {
      $path = $this->drupalGetPath($extension_type, $extension);
    }

    $libraries = $this->parseLibraryInfo($extension, $path);
    $libraries = $this->applyLibrariesOverride($libraries, $extension);

    foreach ($libraries as $id => &$library) {
      if (!isset($library['patterns'])) {
        unset($libraries[$id]);
        continue;
      }
      $library += [
        'dependencies' => [],
        'js' => [],
        'css' => [],
        'patterns' => [],
      ];

      if (isset($library['header']) && !is_bool($library['header'])) {
        throw new \LogicException(sprintf("The 'header' key in the library definition '%s' in extension '%s' is invalid: it must be a boolean.", $id, $extension));
      }

      if (isset($library['version'])) {
        // @todo Retrieve version of a non-core extension.
        if ($library['version'] === 'VERSION') {
          $library['version'] = \Drupal::VERSION;
        }
        // Remove 'v' prefix from external library versions.
        elseif ($library['version'][0] === 'v') {
          $library['version'] = substr($library['version'], 1);
        }
      }

      // If this is a 3rd party library, the license info is required.
      if (isset($library['remote']) && !isset($library['license'])) {
        throw new LibraryDefinitionMissingLicenseException(sprintf("Missing license information in library definition for definition '%s' extension '%s': it has a remote, but no license.", $id, $extension));
      }

      // Assign Drupal's license to libraries that don't have license info.
      if (!isset($library['license'])) {
        $library['license'] = [
          'name' => 'GNU-GPL-2.0-or-later',
          'url' => 'https://www.drupal.org/licensing/faq',
          'gpl-compatible' => TRUE,
        ];
      }
      $library_patterns = $library['patterns'] ?? [];
      // Replace source keys with a list of validated paths.
      foreach ($library_patterns as $source => $options) {
        unset($library['patterns'][$source]);
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

        if (!isset($library['version'])) {
          // @todo Get the information from the extension.
          $options['version'] = -1;
        }
        else {
          $options['version'] = $library['version'];
        }
        $library['patterns'][] = $options;
      }
    }

    return $libraries;
  }

  /**
   * Returns a specific library by name, collected from all extensions.
   *
   * @param string $name
   *   The name of the library, registered by libraries.yml.
   *
   * @return array|null
   *   The pattern library metadata.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getLibraryByName($name): ?array {
    $libraries = $this->getLibraryMetadata();
    return $libraries[$name] ?? NULL;
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function resolveCacheMiss($key) {
    $definitions = $this->getLibraryDefinitions();
    $this->storage[$key] = $definitions[$key] ?? NULL;
    $this->persist($key);

    return $this->storage[$key];
  }

  /**
   * Apply libraries overrides specified for the current active theme.
   *
   * @param array $libraries
   *   The libraries definitions.
   * @param string $extension
   *   The extension in which these libraries are defined.
   *
   * @return array
   *   The modified libraries definitions.
   */
  protected function applyLibrariesOverride($libraries, $extension): array {
    $active_theme = $this->themeManager->getActiveTheme();
    // ActiveTheme::getLibrariesOverride() returns libraries-overrides for the
    // current theme as well as all its base themes.
    $all_libraries_overrides = $active_theme->getLibrariesOverride();
    foreach ($all_libraries_overrides as $theme_path => $libraries_overrides) {
      foreach ($libraries as $library_name => $library) {
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
              $libraries[$library_name]['override'] = $override_definition;
            }
            else {
              $libraries[$library_name]['override'] = FALSE;
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
              if ($sub_key === 'drupalSettings') {
                // drupalSettings may not be overridden.
                throw new InvalidLibrariesOverrideSpecificationException(sprintf('drupalSettings may not be overridden in libraries-override. Trying to override %s. Use hook_library_info_alter() instead.', "$extension/$library_name/$sub_key"));
              }
              if ($sub_key === 'css') {
                // SMACSS category should be incorporated into the asset name.
                foreach ($value as $category => $overrides) {
                  $this->setOverrideValue($libraries[$library_name], [$sub_key, $category], $overrides, $theme_path);
                }
              }
              else {
                $this->setOverrideValue($libraries[$library_name], [$sub_key], $value, $theme_path);
              }
            }
          }
        }
      }
    }

    return $libraries;
  }

  /**
   * Utility function to get all Patternkit module metadata.
   *
   * @return array
   *   Array of metadata objects found.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   When a plugin cannot be instanced.
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   *   When a circular reference is detected.
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
   *   When the service is not defined.
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *   Thrown if an invalid library path was passed to the parser.
   */
  protected function getLibraryMetadata(): array {
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
    $metadata = [];
    // @todo Cleanup too many for loops (cyclomatic complexity).
    foreach ($list_services as $service_name) {
      $extension_type = substr($service_name, strlen($extension_prefix));
      /** \Drupal\Core\Extension\Extension[] $extension_list */
      $extension_list = $container->get($service_name)->getList();
      foreach ($extension_list as $extension_name => $extension) {
        if (empty($extension->status)) {
          continue;
        }
        $libraries = $this->buildByExtension($extension_type, $extension_name);
        foreach ($libraries as $library_name => $library) {
          $pattern_libraries = $library['patterns'] ?? [];
          if (!empty($pattern_libraries)) {
            $metadata[$library_name] = $library;
            unset($metadata[$library_name]['patterns']);
          }
          foreach ($pattern_libraries as $info) {
            $metadata[$library_name]['name'] = $library_name;
            $metadata[$library_name] += $info;
            $plugin_id = $info['plugin'] ?? 'twig';
            /** @var \Drupal\patternkit\PatternLibraryPluginInterface $plugin */
            try {
              $plugin = $this->libraryPluginManager->createInstance($plugin_id);
            }
            catch (PluginNotFoundException $exception) {
              // Allow plugin fallbacks of type 'base_plugin.override_plugin'.
              $plugin_id = strstr($plugin_id, '.', TRUE);
              try {
                $plugin = $this->libraryPluginManager->createInstance($plugin_id);
              }
              catch (PluginNotFoundException $exception) {
                \Drupal::logger('patternkit')->error('Error loading pattern libraries: @message', ['@message' => $exception->getMessage()]);
                continue;
              }
            }
            /** @var \Drupal\patternkit\Pattern $pattern */
            foreach ($plugin->getMetadata($extension, $metadata[$library_name], $info['data']) as $pattern_path => $pattern) {
              $pattern->setLibraryPluginId($plugin_id);
              $cache_id = str_replace('/', '.', trim($pattern_path, '@'));
              $metadata[$library_name]['patterns'][$cache_id] = $pattern;
            }
          }
        }
      }
    }
    return $metadata;
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
    $uri_scheme = $this->fileSystem->uriScheme($uri);
    if (!$this->fileSystem->validScheme($uri_scheme)) {
      return FALSE;
    }
    return TRUE;
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
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $path
   *   The relative path to the extension.
   *
   * @return array
   *   An array of parsed library data.
   *
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *   Thrown when a parser exception got thrown.
   */
  protected function parseLibraryInfo($extension, $path): array {
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

    return $libraries;
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
   * Overrides the specified library asset.
   *
   * @param array $library
   *   The containing library definition.
   * @param array $sub_key
   *   An array containing the sub-keys specifying the library asset, e.g.
   *   @code['js']@endcode or @code['css', 'component']@endcode
   * @param array $overrides
   *   Specifies the overrides, this is an array where the key is the asset to
   *   be overridden while the value is overriding asset.
   * @param string $theme_path
   *   The theme path for the override.
   */
  protected function setOverrideValue(
    array &$library,
    array $sub_key,
    array $overrides,
    $theme_path) {

    foreach ($overrides as $original => $replacement) {
      // Get the attributes of the asset to be overridden. If the key does
      // not exist, then throw an exception.
      $key_exists = NULL;
      $parents = array_merge($sub_key, [$original]);
      // Save the attributes of the library asset to be overridden.
      $attributes = NestedArray::getValue($library, $parents, $key_exists);
      if ($key_exists) {
        // Remove asset to be overridden.
        NestedArray::unsetValue($library, $parents);
        // No need to replace if FALSE is specified, since that is a removal.
        if ($replacement) {
          // Ensure the replacement path is relative to drupal root.
          $replacement = $this->resolveThemeAssetPath($theme_path, $replacement);
          $new_parents = array_merge($sub_key, [$replacement]);
          // Replace with an override if specified.
          NestedArray::setValue($library, $new_parents, $attributes);
        }
      }
    }
  }

}
