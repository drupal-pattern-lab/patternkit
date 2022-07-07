<?php

namespace Drupal\patternkit\Loader;

use Psr\Log\LoggerInterface;
use Drupal\patternkit\Asset\LibraryInterface;
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
   * @param \Drupal\patternkit\Asset\LibraryInterface $library
   *   Provides library names and paths.
   */
  public function __construct(
    $paths,
    LoggerInterface $logger,
    LibraryInterface $library
  ) {
    parent::__construct($paths);

    $libraries = [];
    try {
      $libraries = $library->getLibraryDefinitions();
    }
    catch (\Exception $exception) {
      $logger->error('Error loading pattern libraries: @message', ['@message' => $exception->getMessage()]);
    }
    foreach ($libraries as $namespace => $pattern_library) {
      foreach ($pattern_library->getPatternInfo() as $info) {
        if (!isset($info['data'])) {
          continue;
        }
        $this->addPath($info['data'], $namespace);
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
