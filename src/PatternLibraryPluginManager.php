<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator;
use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\FilteredPluginManagerTrait;
use Drupal\patternkit\Annotation\PatternLibrary;

/**
 * The plugin manager service for pattern library parser plugins.
 */
class PatternLibraryPluginManager extends DefaultPluginManager implements CategorizingPluginManagerInterface {

  use FilteredPluginManagerTrait;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * Manages plugins that provide parsing and editing for pattern libraries.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    parent::__construct('Plugin/PatternLibrary', $namespaces, $module_handler, PatternLibraryPluginInterface::class, PatternLibrary::class);
    $this->themeHandler = $theme_handler;

    $type = $this->getType();
    $this->setCacheBackend($cache_backend, $type);
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
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface {
    if (!$this->discovery) {
      $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);
      $discovery = new AnnotationBridgeDecorator($discovery, $this->pluginDefinitionAnnotationName);
      $discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
      $this->discovery = $discovery;
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function processDefinition(&$definition, $plugin_id): void {
    parent::processDefinition($definition, $plugin_id);

    if (!$definition instanceof PatternLibraryPluginDefinition) {
      throw new InvalidPluginDefinitionException($plugin_id, sprintf('The "%s" layout definition must extend %s', $plugin_id, PatternLibraryPluginDefinition::class));
    }

    // Add the module or theme path to the 'path'.
    $provider = $definition->getProvider();
    if ($this->moduleHandler->moduleExists($provider)) {
      $base_path = $this->moduleHandler->getModule($provider)->getPath();
    }
    elseif ($this->themeHandler->themeExists($provider)) {
      $base_path = $this->themeHandler->getTheme($provider)->getPath();
    }
    else {
      $base_path = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories(): array {
    // Fetch all categories from definitions and remove duplicates.
    $categories = array_unique(array_values(array_map(static function (PatternLibraryPluginDefinition $definition) {
      return $definition->getCategory();
    }, $this->getDefinitions())));
    natcasesort($categories);

    return $categories;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\patternkit\PatternLibraryPluginDefinition[]
   *   An array of plugin definitions, sorted by category and label.
   */
  public function getSortedDefinitions(array $definitions = NULL, $label_key = 'label'): array {
    // Sort the plugins first by category, then by label.
    $definitions ??= $this->getDefinitions();
    // Suppress errors because PHPUnit will indirectly modify the contents,
    // triggering https://bugs.php.net/bug.php?id=50688.
    @uasort($definitions, static function (PatternLibraryPluginDefinition $a, PatternLibraryPluginDefinition $b): int {
      if ($a->getCategory() !== $b->getCategory()) {
        return strnatcasecmp($a->getCategory(), $b->getCategory());
      }
      return strnatcasecmp($a->getLabel(), $b->getLabel());
    });
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\patternkit\PatternLibraryPluginDefinition[][]
   *   Keys are category names, and values are arrays of which the keys are
   *   plugin IDs and the values are plugin definitions.
   */
  public function getGroupedDefinitions(array $definitions = NULL, $label_key = 'label'): array {
    $definitions = $this->getSortedDefinitions($definitions ?? $this->getDefinitions(), $label_key);
    $grouped_definitions = [];
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition->getCategory()][$id] = $definition;
    }
    return $grouped_definitions;
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

}
