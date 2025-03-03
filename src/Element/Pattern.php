<?php

namespace Drupal\patternkit\Element;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Event\PatternRenderFailureEvent;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternFieldProcessorPluginManager;
use Drupal\patternkit\PatternkitEvents;
use Drupal\patternkit\PatternLibraryPluginInterface;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\PatternRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a render element to display a pattern.
 *
 * Properties:
 * - '#pattern': The id of the pattern to be rendered.
 * - '#config': Configuration to be passed to the pattern for rendering.
 * - '#context': Context values for rendering the pattern.
 *
 * Usage Example:
 * @code
 * $build['example_pattern'] = [
 *   '#type' => 'pattern',
 *   '#pattern' => '@my/pattern/name',
 *   '#config' => [
 *     'text' => '[node:title]',
 *     'formatted_text' => '<p><strong>My formatted text</strong></p>',
 *   ],
 *   '#context' => [
 *     'node' => $node,
 *   ],
 * ];
 * @endcode
 *
 * @see \Drupal\patternkit\Element\PatternError
 * @see \Drupal\patternkit\PatternkitEvents::RENDER_FAILURE
 * @see \Drupal\patternkit\EventSubscriber\RenderFailureErrorDisplaySubscriber
 */
#[RenderElement(
  id: "pattern",
)]
class Pattern extends RenderElementBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#pre_render' => [
        [$this, 'loadPattern'],
        [$this, 'preRenderPatternElement'],
      ],
      '#pattern' => NULL,
      '#config' => [],
      '#context' => [],
    ];
  }

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\patternkit\PatternFieldProcessorPluginManager $fieldProcessorPluginManager
   *   The field processor plugin manager service.
   * @param \Drupal\patternkit\PatternLibraryPluginManager $libraryPluginManager
   *   The pattern library parser plugin manager service.
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected PatternFieldProcessorPluginManager $fieldProcessorPluginManager,
    protected PatternLibraryPluginManager $libraryPluginManager,
    protected PatternRepository $patternRepository,
    protected EventDispatcherInterface $eventDispatcher,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.pattern_field_processor'),
      $container->get('plugin.manager.library.pattern'),
      $container->get('patternkit.pattern.repository'),
      $container->get('event_dispatcher'),
    );
  }

  /**
   * Preload the pattern entity if not available yet.
   *
   * @param array $element
   *   An associative array containing the properties of the pattern element.
   *
   * @return array
   *   The modified element.
   */
  public function loadPattern(array $element): array {
    // If the pattern is specified as a string, assume it is the pattern name
    // and attempt to load it.
    if (isset($element['#pattern']) && is_string($element['#pattern'])) {
      $pattern_id = $element['#pattern'];

      try {
        $element['#pattern'] = $this->patternRepository->getPattern($pattern_id);
      }
      catch (\Throwable $exception) {
        // Dispatch an event for the render failure to allow subscribers to
        // react and handle it appropriately.
        $event = $this->dispatchRenderFailureEvent($element, $exception);

        // If the event was handled, update the elements to be returned.
        if ($event->isHandled()) {
          return $event->getElement();
        }
        else {
          // Propagate the exception if an event dispatcher didn't handle it.
          throw $exception;
        }
      }
    }

    return $element;
  }

  /**
   * Pattern element pre render callback.
   *
   * @param array $element
   *   An associative array containing the properties of the pattern element.
   *
   * @return array
   *   The modified element.
   */
  public function preRenderPatternElement(array $element): array {
    // Capture base bubbleable metadata.
    $metadata = BubbleableMetadata::createFromRenderArray($element);

    $pattern = $element['#pattern'] ?? NULL;

    try {
      // Fail early if a pattern was unable to be loaded.
      if (!($pattern instanceof PatternInterface)) {
        throw new UnknownPatternException('No pattern entity available for rendering.');
      }

      // Ensure the metadata from the pattern is present.
      $metadata->addCacheableDependency($pattern);

      // Capture instance data for the pattern and apply preprocessing
      // throughout schema values.
      $pattern->config = $element['#config'];
      $pattern->context = $element['#context'];
      $this->fieldProcessorPluginManager->processSchemaValues($pattern, $pattern->config, $pattern->context, $metadata);

      // Use the pattern's library plugin to produce rendered output.
      $library_plugin = $this->getPatternLibraryPlugin($pattern);
      $elements = $library_plugin->render([$pattern]);
    }
    catch (\Throwable $exception) {
      // Dispatch an event for the render failure to allow subscribers to react
      // and handle it appropriately.
      $event = $this->dispatchRenderFailureEvent($element, $exception);

      // If the event was handled, update the elements to be returned.
      if ($event->isHandled()) {
        $elements = $event->getElement();
      }
      else {
        $elements = [
          '#markup' => $this->t('This content is unavailable.'),
        ];
      }
    }

    // Ensure all metadata persists to the result.
    $metadata->applyTo($elements);

    // Persist the cache keys and bin data if set since these aren't captured by
    // cacheable dependency metadata.
    if (isset($element['#cache']['keys'])) {
      $elements['#cache']['keys'] = $element['#cache']['keys'];
    }
    if (isset($element['#cache']['bin'])) {
      $elements['#cache']['bin'] = $element['#cache']['bin'];
    }

    // Persist render array children if defined.
    $children = Element::children($element, TRUE);
    foreach ($children as $key) {
      $elements[$key] = $element[$key];
    }

    return $elements;
  }

  /**
   * Load the appropriate pattern library plugin for rendering the pattern.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern entity being prepared for rendering.
   *
   * @return \Drupal\patternkit\PatternLibraryPluginInterface
   *   The pattern library plugin needed by the given pattern.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getPatternLibraryPlugin(PatternInterface $pattern): PatternLibraryPluginInterface {
    $pattern_plugin = $pattern->getLibraryPluginId();
    $library_plugin_id = !empty($pattern_plugin) ? $pattern_plugin : 'twig';

    /** @var \Drupal\patternkit\PatternLibraryPluginInterface $plugin */
    $plugin = $this->libraryPluginManager->createInstance($library_plugin_id);
    return $plugin;
  }

  /**
   * Create a new render failure event instance for dispatching.
   *
   * @param array $element
   *   The render element resulting in a failure.
   * @param \Throwable $exception
   *   The exception encountered leading to the render failure.
   *
   * @return \Drupal\patternkit\Event\PatternRenderFailureEvent
   *   The configured render failure event for dispatching.
   */
  protected function dispatchRenderFailureEvent(array $element, \Throwable $exception): PatternRenderFailureEvent {
    $event = new PatternRenderFailureEvent($element, $exception);
    return $this->eventDispatcher->dispatch($event, PatternkitEvents::RENDER_FAILURE);
  }

}
