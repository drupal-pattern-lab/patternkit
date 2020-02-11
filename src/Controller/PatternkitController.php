<?php

namespace Drupal\patternkit\Controller;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\patternkit\Pattern;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for block example routes.
 */
class PatternkitController extends ControllerBase {

  /**
   * The custom block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $patternkitStorage;

  /**
   * The pattern library collector.
   *
   * @var \Drupal\patternkit\PatternkitLibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a Patternkit Controller.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $patternkit_storage
   *   The Patternkit storage.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The Pattern Library Collector.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(
    EntityStorageInterface $patternkit_storage,
    LibraryDiscoveryInterface $library_discovery,
    ThemeHandlerInterface $theme_handler) {
    $this->patternkitStorage = $patternkit_storage;
    $this->libraryDiscovery = $library_discovery;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
    $entity_manager = $container->get('entity_type.manager');
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = $container->get('patternkit.library.discovery');
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $container->get('theme_handler');
    return new static(
      $entity_manager->getStorage('patternkit_block'),
      $library_discovery,
      $theme_handler
    );
  }

  /**
   * Displays add custom block links for available types.
   *
   * @return array
   *   A render array for a list of the Patternkit pattern blocks that can be
   *   added.
   */
  public function add(): array {
    try {
      $types = $this->libraryDiscovery->getAssets();
    }
    catch (\Exception $exception) {
      $this->getLogger('patternkit')->error('Error loading pattern library assets: ', ['@message' => $exception->getMessage()]);
      return [
        '#markup' => $this->t('Error loading pattern library assets - check the log or the format of your libraries.yml.'),
      ];
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('Could not find any patterns to load. Did you add a theme or module that includes a patterns definition in the libraries.yml?'),
      ];
    }
    $content['types'] = [];
    $query = \Drupal::request()->query->all();
    /** @var \Drupal\patternkit\Pattern $type */
    foreach ($types as $type) {
      $id = $type->getId();
      $label = $type->getLabel();
      $content['types'][$id] = [
        'link' => Link::fromTextAndUrl($label, Url::fromRoute('patternkit.add_form', ['pattern_id' => $id], ['query' => $query])),
        'description' => [
          '#markup' => $type->getDescription(),
        ],
        'title' => $label,
        'localized_options' => [
          'query' => $query,
        ],
      ];
    }
    return ['#theme' => 'patternkit_add_list', '#content' => $content];
  }

  /**
   * Presents the Patternkit block creation form.
   *
   * @param string $pattern_id
   *   The pattern to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function addForm($pattern_id, Request $request): array {
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $this->patternkitStorage->create([
      'type' => $pattern_id,
    ]);
    if (($theme = $request->query->get('theme'))
      && array_key_exists($theme, $this->themeHandler->listInfo())) {
      // We have navigated to this page from the block library and will keep
      // track of the theme for redirecting the user to the configuration page
      // for the newly created block in the given theme.
      $block->setTheme($theme);
    }
    return $this->entityFormBuilder()->getForm($block);
  }

  /**
   * Returns the JSON-encoded Patternkit schema for the provided pattern.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $pattern
   *   The name of the pattern to use for retrieving the schema.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The schema response.
   */
  public function apiPattern(Request $request, $pattern = NULL): Response {
    $pattern = $request->query->get('pattern') ?? $pattern;
    if (!$pattern) {
      return new Response();
    }
    $test_len = strlen('/schema');
    if (substr_compare($pattern, '/schema', strlen($pattern) - $test_len, $test_len) === 0) {
      return $this->apiPatternSchema(substr($pattern, 0, -$test_len));
    }
    $asset_id = str_replace('/', '.', $pattern);
    try {
      $response = $this->libraryDiscovery->getLibraryAsset($asset_id);
      if ($response === NULL) {
        throw new \RuntimeException("Unable to locate $pattern.");
      }
    }
    catch (\Exception $exception) {
      $response = ['error' => $exception->getMessage()];
    }
    return new JsonResponse($response);
  }

  /**
   * Returns the JSON-encoded Patternkit schema for the provided pattern.
   *
   * @param string $pattern
   *   The name of the pattern to use for retrieving the schema.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The schema response.
   */
  public function apiPatternSchema($pattern): JsonResponse {
    $asset_id = str_replace('/', '.', $pattern);
    try {
      $pattern_asset = $this->libraryDiscovery->getLibraryAsset($asset_id);
      if ($pattern_asset === NULL) {
        throw new \RuntimeException("Unable to locate $pattern.");
      }
      $response = $pattern_asset->schema ?? [];
    }
    catch (\Exception $exception) {
      $response = ['error' => $exception->getMessage()];
    }
    return new JsonResponse($response);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\patternkit\Pattern $pattern
   *   The pattern being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(Pattern $pattern): string {
    return $this->t('Add %type Patternkit block', ['%type' => $pattern->getLabel()]);
  }

  /**
   * Returns a media library display especially for Patternkit.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public function mediaLibrary(Request $request): array {
    $query = $request->query;
    $ml_state = MediaLibraryState::create(
      $query->get('media_library_opener_id'),
      $query->get('media_library_allowed_types', []),
      $query->get('media_library_selected_type'),
      $query->get('media_library_remaining'),
      $query->get('media_library_opener_parameters', [])
    );
    if (!\Drupal::hasService('media_library.ui_builder')) {
      throw new ServiceNotFoundException('media_library.ui_builder');
    }
    /** @var MediaLibraryUiBuilder $ml_ui_builder */
    $ml_ui_builder = \Drupal::service('media_library.ui_builder');

    return $ml_ui_builder->buildUi($ml_state);
  }

}
