<?php

namespace Drupal\patternkit\Asset;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\patternkit\Form\PatternkitSettingsForm;
use Drupal\patternkit\PatternLibrary;
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
 * @deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0.
 *   Use \Drupal\patternkit\Asset\LibraryNamespaceResolver or
 *   \Drupal\patternkit\Asset\PatternDiscovery instead.
 * @see https://www.drupal.org/node/3295833
 * @see \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface
 * @see \Drupal\patternkit\Asset\PatternDiscoveryInterface
 */
class Library extends CacheCollector implements LibraryInterface, ContainerInjectionInterface {

  /**
   * The identifier for the library discovery cache.
   *
   * @var string
   */
  const PERSISTENT_CACHE_ID = 'patternkit.library.cache';

  /**
   * Configuration values for the Patternkit module settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * A static cache of loaded library definitions.
   *
   * @var array
   */
  protected array $libraries;

  /**
   * The final library definitions, statically cached.
   *
   * Hooks hook_library_info_alter() and hook_js_settings_alter() allow modules
   * and themes to dynamically alter a library definition (once per request).
   *
   * @var array
   */
  protected array $libraryDefinitions = [];

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $patternDiscovery;

  /**
   * The library namespace resolver service.
   *
   * @var \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface
   */
  protected LibraryNamespaceResolverInterface $libraryNamespaceResolver;

  /**
   * A serializer for instantiating PatternLibrary instances.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected Serializer $serializer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $cache = $container->get('cache.discovery');
    $config_factory = $container->get('config.factory');
    $lock = $container->get('lock');
    $pattern_discovery = $container->get('patternkit.pattern.discovery');
    $library_namespace_resolver = $container->get('patternkit.library.namespace_resolver');
    return new static(
      $cache,
      $config_factory,
      $lock,
      $pattern_discovery,
      $library_namespace_resolver
    );
  }

  /**
   * Constructs a Library instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Provide a default cache.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Provide the factory for configuration objects.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Provide the lock backend.
   * @param \Drupal\patternkit\Asset\PatternDiscoveryInterface $pattern_discovery
   *   The pattern discovery service.
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface $library_namespace_resolver
   *   The library namespace resolver service.
   */
  public function __construct(
    CacheBackendInterface $cache,
    ConfigFactoryInterface $config_factory,
    LockBackendInterface $lock,
    PatternDiscoveryInterface $pattern_discovery,
    LibraryNamespaceResolverInterface $library_namespace_resolver
  ) {
    $this->config = $config_factory->get(PatternkitSettingsForm::SETTINGS);
    $this->patternDiscovery = $pattern_discovery;
    $this->libraryNamespaceResolver = $library_namespace_resolver;

    $this->serializer = new Serializer([new PropertyNormalizer()]);

    parent::__construct(static::PERSISTENT_CACHE_ID, $cache, $lock);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions(): void {
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
   * @deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0.
   *   Use LibraryNamespaceResolver::getLibrariesByExtension() instead.
   * @see https://www.drupal.org/node/3295833
   * @see \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibrariesByExtension()
   */
  public function buildByExtension($extension_type, $extension): array {
    @trigger_error(__METHOD__ . ' is deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibrariesByExtension() instead. See https://www.drupal.org/node/3295833', E_USER_DEPRECATED);

    return $this->getLibrariesByExtension($extension);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0.
   *   Use PatternDiscovery::getPatternDefinitions() instead.
   * @see https://www.drupal.org/node/3295833
   * @see \Drupal\patternkit\Asset\PatternDiscovery::getPatternDefinitions()
   */
  public function getAssets(): array {
    @trigger_error(__METHOD__ . ' is deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\Asset\PatternDiscovery::getPatternDefinitions() instead. See https://www.drupal.org/node/3295833', E_USER_DEPRECATED);

    $pattern_definitions = $this->patternDiscovery->getPatternDefinitions();

    return array_merge([], ...array_values($pattern_definitions));
  }

  /**
   * Returns a static array of libraries with parsed information.
   *
   * @param bool $reset
   *   TRUE if the library cache should be reparsed.
   *
   * @return \Drupal\patternkit\PatternLibrary[]
   *   An array of libraries and information.
   *
   * @deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0.
   *   Use LibraryNamespaceResolver::getLibraryDefinitions() instead.
   * @see https://www.drupal.org/node/3295833
   * @see \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibraryDefinitions()
   */
  public function getLibraries(bool $reset = FALSE): array {
    @trigger_error(__METHOD__ . ' is deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibraryDefinitions() instead. See https://www.drupal.org/node/3295833', E_USER_DEPRECATED);

    $definitions = $this->libraryNamespaceResolver->getLibraryDefinitions();

    // Instantiate each library definition as a PatternLibrary object for
    // backward compatibility.
    $libraries = [];
    foreach ($definitions as $namespace => $definition) {
      $library_name = ltrim($namespace, '@');
      $libraries[$library_name] = $this->getLibraryObject($definition);
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0.
   *   Use LibraryNamespaceResolver::getLibrariesByExtension() instead.
   * @see https://www.drupal.org/node/3295833
   * @see \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibrariesByExtension()
   */
  public function getLibrariesByExtension(string $extension): array {
    @trigger_error(__METHOD__ . ' is deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibrariesByExtension() instead. See https://www.drupal.org/node/3295833', E_USER_DEPRECATED);

    $definitions = $this->libraryNamespaceResolver->getLibrariesByExtension($extension);

    // Instantiate each library definition as a PatternLibrary object for
    // backward compatibility.
    $libraries = [];
    foreach ($definitions as $namespace => $definition) {
      $library_name = ltrim($namespace, '@');
      $libraries[$library_name] = $this->getLibraryObject($definition);
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0.
   *   Use PatternDiscovery::getPatternDefinition() instead.
   * @see https://www.drupal.org/node/3295833
   * @see \Drupal\patternkit\Asset\PatternDiscovery::getPatternDefinition()
   */
  public function getLibraryAsset($key) {
    @trigger_error(__METHOD__ . ' is deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\Asset\PatternDiscovery::getPatternDefinition() instead. See https://www.drupal.org/node/3295833', E_USER_DEPRECATED);

    return $this->patternDiscovery->getPatternDefinition($key);
  }

  /**
   * Returns the specified Patternkit module metadata.
   *
   * @return \Drupal\patternkit\PatternLibrary[]
   *   Array of metadata objects found or object if specific pattern requested.
   *   Keyed by library name in the format.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0.
   *   Use LibraryNamespaceResolver::getLibraryDefinitions() instead.
   * @see https://www.drupal.org/node/3295833
   * @see \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibraryDefinitions()
   */
  public function getLibraryDefinitions(): array {
    @trigger_error(__METHOD__ . ' is deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibraryDefinitions() instead. See https://www.drupal.org/node/3295833', E_USER_DEPRECATED);

    $definitions = $this->libraryNamespaceResolver->getLibraryDefinitions();
    $patterns = $this->patternDiscovery->getPatternDefinitions();

    // Instantiate each library definition as a PatternLibrary object for
    // backward compatibility.
    $libraries = [];
    foreach ($definitions as $namespace => $definition) {
      $library_name = ltrim($namespace, '@');
      $libObj = $this->getLibraryObject($definition);

      // Add discovered patterns to the returned library definition object
      // to maintain backward compatibility.
      if (isset($definition['patterns'])) {
        // Persist pattern library info before overwriting it with discovered
        // pattern definitions.
        $libObj->setPatternInfo($definition['patterns']);

        if (isset($patterns[$namespace])) {
          // Reindex the patterns by path for backward compatibility.
          $lib_patterns = [];
          foreach ($patterns[$namespace] as $pattern) {
            // @todo Figure out why the JSON parser returns path as a string instead of an array.
            $path = is_array($pattern['path']) ? $pattern['path'][0]['value'] : $pattern['path'];
            $lib_patterns[$path] = $pattern;
          }

          $libObj->patterns = $lib_patterns;
        }
      }

      $libraries[$library_name] = $libObj;
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0.
   *   Use LibraryNamespaceResolver::getLibraryFromNamespace() instead.
   * @see https://www.drupal.org/node/3295833
   * @see \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibraryFromNamespace()
   */
  public function getLibraryByName($extension, $name) {
    @trigger_error(__METHOD__ . ' is deprecated in patternkit:9.1.0-beta4 and is removed from patternkit:9.1.0. Use \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibraryFromNamespace() instead. See https://www.drupal.org/node/3295833', E_USER_DEPRECATED);

    return $this->libraryNamespaceResolver->getLibraryFromNamespace("@$name");
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function resolveCacheMiss($key): ?PatternLibrary {
    $definitions = $this->getLibraryDefinitions();
    $this->storage[$key] = $definitions[$key] ?? NULL;
    $this->persist($key);

    return $this->storage[$key];
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
