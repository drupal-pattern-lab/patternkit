<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\patternkit\Annotation\PatternLibrary as PatternLibraryAnnotation;
use Drupal\patternkit\Attribute\PatternLibrary as PatternLibraryAttribute;
use Drupal\patternkit\Entity\PatternInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * The plugin manager service for pattern library parser plugins.
 */
class PatternLibraryPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

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
   * A string identifying the plugin type.
   *
   * This string should be unique and generally will correspond to the string
   * used by the discovery, e.g. the annotation class or the YAML file name.
   *
   * @return string
   *   A string identifying the plugin type.
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
   * Load the pattern library plugin used by a given pattern.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern entity to load the library plugin for.
   *
   * @return \Drupal\patternkit\PatternLibraryPluginInterface
   *   The loaded pattern library plugin for the given pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPluginForPattern(PatternInterface $pattern): PatternLibraryPluginInterface {
    $plugin_id = $pattern->getLibraryPluginId();
    try {
      $plugin = $this->createInstance($plugin_id);
    }
    catch (PluginException $exception) {
      throw new PluginException(
        message: 'Unable to fetch assets for pattern ' . $pattern->getPath(),
        previous: $exception,
      );
    }

    assert($plugin instanceof PatternLibraryPluginInterface);
    return $plugin;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   When a fallback plugin may not be identified.
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []): string {
    // Allow plugin fall-backs of type 'base_plugin.override_plugin'.
    if ($pos = strrpos($plugin_id, '.')) {
      return substr($plugin_id, 0, $pos);
    }
    else {
      throw new PluginNotFoundException($plugin_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function handlePluginNotFound($plugin_id, array $configuration): object {
    $this->logger->warning('The "%plugin_id" library plugin was not found', ['%plugin_id' => $plugin_id]);
    $fallback_id = $this->getFallbackPluginId($plugin_id, $configuration);

    // Override parent method to call createInstance() on this class instead of
    // on the factory allowing fallback handling until no fallback ID is found
    // eventually throwing the exception higher.
    return $this->createInstance($fallback_id, $configuration);
  }

}
