<?php

namespace Drupal\patternkit\Hook;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Utility\Error;
use Drupal\layout_builder\SectionComponent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook implementations for theme-related functionality.
 */
class ThemeHooks implements LoggerAwareInterface, ContainerInjectionInterface {

  use LoggerAwareTrait;

  /**
   * Constructs a new ThemeHooks object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $keyValueExpirable
   *   The key-value expirable factory service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(
    #[Autowire('@keyvalue.expirable')]
    protected readonly KeyValueExpirableFactoryInterface $keyValueExpirable,
    protected readonly LanguageManagerInterface $languageManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static(
      $container->get('keyvalue.expirable'),
      $container->get('language_manager'),
    );

    // The logger channel will be created automatically if it doesn't exist yet.
    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $logger = $container->get('logger.channel.patternkit');
    $instance->setLogger($logger);

    return $instance;
  }

  /**
   * Implements hook_link_alter().
   */
  #[Hook('link_alter')]
  #[TrustedCallback]
  public function linkAlter(&$variables): void {
    try {
      /** @var \Drupal\Core\Url $url */
      $url = $variables['url'];

      if (
        $url->isRouted()
        && $url->getRouteName() == 'layout_builder.add_block'
        && ($routeParameters = $url->getRouteParameters())
        && isset($routeParameters['plugin_id'])
        && str_starts_with($routeParameters['plugin_id'], 'patternkit_block')) {
        $variables['options']['attributes']['class'][] = 'patternkit-add-block';
        $variables['options']['attributes']['data-dialog-options'] = '{"width": 900}';
      }
    }
    // Catch a lookup failure if the route name could not be successfully
    // loaded.
    catch (\UnexpectedValueException $e) {
      Error::logException($this->logger(), $e);
    }
  }

  /**
   * Implements hook_contextual_links_alter().
   *
   * This is a bit intensive with having to load the entity, section storage,
   * and blocks, but it counts on static caching for some of this as the
   * contextual links are all loaded in the same request. Heavy browser
   * caching of the contextual links themselves also helps by preventing them
   * from being frequently reloaded even after the Drupal cache is cleared.
   */
  #[Hook('contextual_links_alter')]
  #[TrustedCallback]
  public function contextualLinksAlter(array &$links, $group, array $route_parameters): void {
    if ($group == 'layout_builder_block' && isset($links['layout_builder_block_update'])) {
      // Load the layout component configuration.
      $config = $this->loadComponentFromTempStorage($route_parameters)
        ?->get('configuration');

      // Test if it's a patternkit block.
      if (!empty($config['id'])
        && str_starts_with($config['id'], 'patternkit_block')
      ) {
        // Alter the link.
        $update_link = &$links['layout_builder_block_update'];
        $update_link['localized_options']['attributes']['data-dialog-options'] = '{"width": 900}';
        $update_link['localized_options']['attributes']['class'][] = 'patternkit-update-component';
      }
    }
  }

  /**
   * Load the targeted component from temporary storage layouts.
   *
   * Load the targeted component from a temporarily saved layout in the
   * expirable tempstore if the layout has not yet been saved.
   *
   * @param array $route_parameters
   *   Route parameters for the targeted contextual link. Required keys include:
   *     - section_storage: The entity type and ID.
   *     - section_storage_type: The layout storage type.
   *
   * @return \Drupal\layout_builder\SectionComponent|null
   *   The loaded layout component or NULL if it could not be found.
   *
   * @todo Add support for non-default display modes.
   */
  protected function loadComponentFromTempStorage(array $route_parameters): ?SectionComponent {
    $temp_layouts = $this->keyValueExpirable
      ->get("tempstore.shared.layout_builder.section_storage.{$route_parameters['section_storage_type']}");

    $tempstore_id = $route_parameters['section_storage'];

    if ($route_parameters['section_storage_type'] == 'overrides') {
      $language = $this->languageManager->getCurrentLanguage()->getId();

      // Assume the default display mode. If a specific display mode is being
      // configured instead, this may fail and would need to identify the
      // display mode being targeted in some way.
      // This also assumes the current language is the language code of the
      // display being targeted.
      $tempstore_id .= ".default.$language";
    }

    if ($temp_layouts->has($tempstore_id)) {
      $section_storage = $temp_layouts->get($tempstore_id)
        ?->data['section_storage'];
    }

    // Traverse the layout and load the specific block.
    try {
      /** @var \Drupal\layout_builder\SectionStorageInterface|null $section_storage */
      $component = $section_storage?->getSection($route_parameters['delta'])
        ?->getComponent($route_parameters['uuid']);
    }
    catch (\OutOfBoundsException | \InvalidArgumentException $exception) {
      Error::logException($this->logger(), $exception);
    }

    // Return the component.
    return $component ?? NULL;
  }

  /**
   * Get the logger service to be used.
   *
   * @return \Psr\Log\LoggerInterface
   *   Get the logger to be used.
   */
  private function logger(): LoggerInterface {
    if (!isset($this->logger)) {
      // @phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
      $this->logger = \Drupal::logger('patternkit');
    }

    return $this->logger;
  }

}
