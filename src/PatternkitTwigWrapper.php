<?php
/**
 * @file
 * Patternkit module - Twig wrapper.
 */

/**
 * Class PatternkitTwigWrapper.
 */
class PatternkitTwigWrapper {

  protected $metadata;
  protected $libraries;
  protected $twigEngine;

  /**
   * PatternkitTwigWrapper constructor.
   *
   * @param array $libraries
   *   The collection of patterns and metadata.
   *
   * @return PatternkitTwigWrapper
   *   Returns the singleton PatternkitTwigWrapper.
   */
  public static function getInstance(array $libraries = NULL) {
    static $instance;

    if (empty($instance)) {
      $instance = new static($libraries);
    }

    return $instance;
  }

  /**
   * PatternkitTwigWrapper constructor.
   *
   * @param \PatternkitDrupalTwigLib[] $libraries
   *   The collection of patterns and metadata.
   */
  protected function __construct(array $libraries) {
    if (empty($libraries)) {
      watchdog('patternkit', 'Metadata not passed to constructor for patternTwig', WATCHDOG_ERROR);
    }

    $this->libraries = $libraries;
    // Setup twig environment.
    // @TODO: Properly libraryize this.
    require_once DRUPAL_ROOT . '/sites/all/libraries/Twig/Autoloader.php';
    Twig_Autoloader::register();

    $loader = new Twig_Loader_Filesystem();

    // Collect all metadata.
    $this->metadata = array();
    $meta = array();
    foreach ($libraries as $library) {
      $meta[] = $library->getCachedMetadata();
      $loader->addPath($library->getPath(), $library->getNamespace());
    }
    $this->metadata = call_user_func_array('array_merge', $meta);

    foreach ($this->metadata as $module_name => $module) {
      if (!empty($module->filename)) {
        $templatesDirectory = DRUPAL_ROOT . DIRECTORY_SEPARATOR . dirname(
            $module->filename
          );

        // We put the namespace into the url, since it's otherwise unused
        // and serves a similar purpose.
        try {
          $loader->addPath($templatesDirectory, $module->url);

          // To support empty namespaces (for now)
          $loader->addPath($templatesDirectory);
        }
        catch (Twig_Error_Loader $e) {
          drupal_set_message("Error loading $templatesDirectory", 'warning');
          watchdog(
            'patternkit',
            'Error loading @module',
            array('@module' => $templatesDirectory),
            WATCHDOG_WARNING
          );
        }
      }
    }

    $theme_debug = (bool) variable_get('theme_debug', FALSE);
    $this->twigEngine = new Twig_Environment(
      $loader,
      array(
        'autorender'  => (bool) variable_get('pktwig_auto_render', TRUE),
        'autoescape'  => (bool) variable_get('pktwig_auto_escape', FALSE),
        'auto_reload' => (bool) variable_get('pktwig_auto_reload', FALSE),
        'cache'       => variable_get('pktwig_template_cache_path', '/tmp/twig_compilation_cache'),
        'debug'       => $theme_debug,
      )
    );
    if ($theme_debug) {
      $this->twigEngine->addExtension(new Twig_Extension_Debug());
    }

  }

  /**
   * Returns a singleton version of the twig template engine.
   *
   * @return Twig_Environment
   *   Twig environment object.
   *
   * @throws \Twig_Error_Loader
   *   Twig engine instance object.
   */
  public function getTwigInstance() {
    return $this->twigEngine;
  }

}
