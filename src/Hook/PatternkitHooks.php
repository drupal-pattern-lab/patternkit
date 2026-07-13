<?php

namespace Drupal\patternkit\Hook;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\patternkit\Asset\LibraryNamespaceResolver;
use Drupal\patternkit\Asset\PatternDiscovery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hooks for the Patternkit module.
 */
class PatternkitHooks implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new PatternkitHooks object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\patternkit\Asset\LibraryNamespaceResolver $namespaceResolver
   *   The library namespace resolver service.
   * @param \Drupal\patternkit\Asset\PatternDiscovery $patternDiscovery
   *   The pattern discovery service.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected LibraryNamespaceResolver $namespaceResolver,
    protected PatternDiscovery $patternDiscovery,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static(
      $container->get('module_handler'),
      $container->get('patternkit.library.namespace_resolver'),
      $container->get('patternkit.pattern.discovery'),
    );
    $instance->setStringTranslation($container->get('string_translation'));
    return $instance;
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.patternkit':
        $patternkit_example = $this->moduleHandler->moduleExists('patternkit_example') ? Url::fromRoute('help.page', ['name' => 'patternkit_example'])->toString() : '#';
        $patternkit_media_library = $this->moduleHandler->moduleExists('patternkit_media_library') ? Url::fromRoute('help.page', ['name' => 'patternkit_media_library'])->toString() : '#';

        $output = <<<HTML
          <h3>About</h3>
          <p>The Patternkit module loads your templates, patterns, and components into Drupal as blocks where you can then add them to your pages and layouts. You can configure them with a JSON file with an identical filename in the same directory as the Twig template.</p>\
          <p>This allows Layout Builder, Page Manager, Drupal blocks layout, or anything that uses blocks, to function as a custom page-builder app.</p>
          <p>Site builders can add Drupal tokens to Pattern blocks instead of trying to pick through Drupal variables in a template. These are displayed from assigned Context such as node, user, and language.</p>

          <h3>Uses</h3>
          <dl>
          <dt>Loading a library of patterns to use with Layout Builder</dt>
          <dd>Users with the <em>Administer blocks</em> permission can add Patternkit blocks to entity layouts.</dd>
          <dt>Loading a directory of patterns to use with the Block Layout</dt>
          <dd>Users with the <em>Administer blocks</em> permission can add Patternkit blocks to the site block layout.</dd>
          </dl>
          HTML;

        // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
        return $this->t($output, [
          ':patternkit_example' => $patternkit_example,
          ':patternkit_media_library' => $patternkit_media_library,
        ]);

      default:
        return NULL;
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'patternkit_add_list' => [
        'variables' => ['content' => NULL],
      ],
    ];
  }

  /**
   * Implements hook_rebuild().
   */
  #[Hook('rebuild')]
  public function rebuild(): void {
    // Rebuild the library definitions cache.
    $this->namespaceResolver->rebuild();

    // Rebuild the pattern discovery cache.
    $this->patternDiscovery->rebuild();
  }

}
