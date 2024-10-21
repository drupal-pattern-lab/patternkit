<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\FilteredPluginManagerTrait;
use Drupal\patternkit\Annotation\PatternLibrary as PatternLibraryAnnotation;
use Drupal\patternkit\Attribute\PatternLibrary as PatternLibraryAttribute;
use Drupal\patternkit\Entity\PatternInterface;

/**
 * The plugin manager service for pattern library parser plugins.
 *
 * @todo Implement FallbackPluginManagerInterface to take responsibility for loading fallback plugins.
 * @todo Remove unused categorization functionality.
 * @todo Remove unused filtering functionality.
 */
class PatternLibraryPluginManager extends DefaultPluginManager implements CategorizingPluginManagerInterface {

  use FilteredPluginManagerTrait;
  use CategorizingPluginManagerTrait;

  /**
   * Manages plugins that provide parsing and editing for pattern libraries.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cacheBackend,
    ModuleHandlerInterface $moduleHandler,
    protected ThemeHandlerInterface $themeHandler,
  ) {
    parent::__construct(
      'Plugin/PatternLibrary',
      $namespaces,
      $moduleHandler,
      PatternLibraryPluginInterface::class,
      PatternLibraryAttribute::class,
      PatternLibraryAnnotation::class,
    );

    $type = $this->getType();
    $this->setCacheBackend($cacheBackend, $type);
    $this->alterInfo($type);
  }

  /**
   * {@inheritdoc}
   */
  protected function getType(): string {
    return 'pattern_library';
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider): bool {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * Returns an array of layout labels grouped by category.
   *
   * @return string[][]
   *   A nested array of labels suitable for #options.
   */
  public function getLayoutOptions(): array {
    $layout_options = [];
    foreach ($this->getGroupedDefinitions() as $category => $layout_definitions) {
      foreach ($layout_definitions as $name => $layout_definition) {
        $layout_options[$category][$name] = $layout_definition->getLabel();
      }
    }
    return $layout_options;
  }

  /**
   * Load the pattern library plugin used by a given pattern.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern entity to load the library plugin for.
   *
   * @return \Drupal\patternkit\PatternLibraryPluginInterface
   *   The loaded pattern library plugin for the given pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo Reimplement with proper fallback plugin loading.
   * @see \Drupal\Component\Plugin\FallbackPluginManagerInterface
   */
  public function getPluginForPattern(PatternInterface $pattern): PatternLibraryPluginInterface {
    $plugin_id = $pattern->getLibraryPluginId();
    try {
      $plugin = $this->createInstance($plugin_id);
    }
    catch (PluginException $exception) {
      // Allow plugin fall-backs of type 'base_plugin.override_plugin'.
      $plugin_id = strstr($plugin_id, '.', TRUE);
      if (empty($plugin_id)) {
        throw new PluginNotFoundException($plugin_id,
          "Unable to fetch assets for pattern " . $pattern->getPath());
      }
      $plugin = $this->createInstance($plugin_id);
    }

    assert($plugin instanceof PatternLibraryPluginInterface);
    return $plugin;
  }

}
