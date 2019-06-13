<?php

namespace Drupal\patternkit\Loader;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\patternkit\PatternLibraryCollector;

/**
 * Functionality for parsing Twig pattern libraries.
 */
class PatternLibraryLoader extends \Twig_Loader_Filesystem {

  /**
   * Overrides to add paths from pattern libraries.
   *
   * @param string|array $paths
   *   Paths to pass to the Filesystem loader.
   * @param \Drupal\patternkit\PatternLibraryCollector $pattern_collector
   *   Provides library names and paths.
   */
  public function __construct($paths,
    PatternLibraryCollector $pattern_collector) {
    parent::__construct($paths);
    $libraries = [];
    try {
      $libraries = $pattern_collector->getLibraryDefinitions();
    }
    catch (PluginException $exception) {
      // Do not block rendering if libraries do not load.
      // @todo Add logging service and log.
    }
    foreach ($libraries as $namespace => $library) {
      $path = $library['data'];
      $this->addPath($path, $namespace);
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
  public function addPath($path, $namespace = self::MAIN_NAMESPACE) {
    // Invalidate the cache.
    $this->cache = [];
    $this->paths[$namespace][] = rtrim($path, '/\\');
  }

}
