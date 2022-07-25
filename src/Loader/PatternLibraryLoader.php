<?php

namespace Drupal\patternkit\Loader;

use Drupal\patternkit\Asset\LibraryNamespaceResolverInterface;
use Psr\Log\LoggerInterface;
use Twig\Loader\FilesystemLoader;

/**
 * Functionality for parsing Twig pattern libraries.
 */
class PatternLibraryLoader extends FilesystemLoader {

  /**
   * Overrides to add paths from pattern libraries.
   *
   * @param string|array $paths
   *   Paths to pass to the Filesystem loader.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logs to the patternkit channel.
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolverInterface $libraryNamespaceResolver
   *   Provides library names and paths.
   */
  public function __construct(
    $paths,
    LoggerInterface $logger,
    LibraryNamespaceResolverInterface $libraryNamespaceResolver
  ) {
    parent::__construct($paths);

    $libraries = [];
    try {
      $libraries = $libraryNamespaceResolver->getLibraryDefinitions();
    }
    catch (\Exception $exception) {
      $logger->error('Error loading pattern libraries: @message', ['@message' => $exception->getMessage()]);
    }
    foreach ($libraries as $namespace => $pattern_library) {
      if (isset($pattern_library['patterns'])) {
        foreach ($pattern_library['patterns'] as $info) {
          if (!isset($info['data'])) {
            continue;
          }

          // Trim the leading symbol for registered namespaces since they are
          // trimmed before lookup when loading templates.
          $this->addPath($info['data'], ltrim($namespace, '@'));
        }
      }
    }
  }

  /**
   * Adds a path where templates are stored.
   *
   * @param string $path
   *   A path where to look for templates.
   * @param string $namespace
   *   (optional) A path name.
   */
  public function addPath($path, $namespace = self::MAIN_NAMESPACE): void {
    // Invalidate the cache.
    $this->cache = [];
    $this->paths[$namespace][] = rtrim($path, '/\\');
  }

}
