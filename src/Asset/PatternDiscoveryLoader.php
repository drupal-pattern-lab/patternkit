<?php

namespace Drupal\patternkit\Asset;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Webmozart\Assert\Assert;

/**
 * A loading service for discovering and loading pattern definitions.
 *
 * This service handles the plugin management and library definition parsing
 * necessary to identify all available patterns in Patternkit-enabled libraries.
 *
 * @internal
 */
class PatternDiscoveryLoader implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * The default library parser plugin to be used if not explicitly specified.
   *
   * @var string
   */
  const DEFAULT_LIBRARY_PLUGIN_ID = 'twig';

  /**
   * Creates an instance of PatternDiscoveryLoader.
   *
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface $namespaceResolver
   *   The library namespace resolver service.
   * @param \Drupal\patternkit\PatternLibraryPluginManager $libraryPluginManager
   *   The pattern library plugin manager service.
   * @param \Symfony\Component\Serializer\Normalizer\DenormalizerInterface|null $serializer
   *   (optional) A serializer for normalizing pattern library properties.
   */
  public function __construct(
    protected readonly LibraryNamespaceResolverInterface $namespaceResolver,
    protected readonly PatternLibraryPluginManager $libraryPluginManager,
    protected ?DenormalizerInterface $serializer = NULL,
  ) {
    $this->serializer = $serializer ?? new Serializer([new PropertyNormalizer()]);
  }

  /**
   * Load all patterns provided by a library namespace.
   *
   * @param string $namespace
   *   The name of the library namespace to load patterns for.
   *
   * @return array<string, array<string, mixed>>
   *   An associative array of all discovered pattern definitions within the
   *   namespace. Each definition is keyed by their fully-namespaced pattern id.
   *
   * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   * @throws \Drupal\patternkit\Exception\UnknownPatternException
   *   Throws an exception if the requested namespace cannot be found.
   *
   * @see \Drupal\patternkit\Asset\LibraryNamespaceResolver::getLibraryDefinitions()
   */
  public function getPatternsByNamespace(string $namespace): array {
    Assert::startsWith($namespace, '@', 'The library namespace is expected to begin with an "@" symbol. Got: ' . $namespace);

    $library_definition = $this->namespaceResolver->getLibraryFromNamespace($namespace);
    $pattern_info = $library_definition['patterns'] ?? [];

    if ($library_definition === NULL) {
      throw new UnknownPatternException('Unable to load "' . $namespace . '" namespace.');
    }

    // @todo Remove usage of the PatternLibrary object.
    // @phpstan-ignore method.deprecated
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
            '%library' => $namespace,
            '@data' => print_r($info, TRUE),
          ],
        );
        continue;
      }

      // Propagate the library namespace down for access from each plugin.
      $info['namespace'] = $namespace;

      /** @var \Drupal\patternkit\PatternLibraryPluginInterface $plugin */
      try {
        $plugin = $this->getLibraryPlugin($info);
      }
      catch (PluginException $exception) {
        // Allow plugin fall-backs of type 'base_plugin.override_plugin'.
        // @todo Move fall-back handling into plugin manager.
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
            '%library' => $namespace,
            '%path' => $path,
          ]
        );
      }
    }

    // Prefix all pattern keys with the library namespace.
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
          '%library' => $library_info['namespace'],
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
   * @deprecated @phpcs:ignore
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
