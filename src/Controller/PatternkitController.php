<?php

namespace Drupal\patternkit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\patternkit\Asset\LibraryInterface;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Plugin\Derivative\PatternkitBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * The pattern library.
   *
   * @var \Drupal\patternkit\Asset\LibraryInterface
   */
  protected $library;

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
   * @param \Drupal\patternkit\Asset\LibraryInterface $library
   *   The Pattern Library.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(
    EntityStorageInterface $patternkit_storage,
    LibraryInterface $library,
    ThemeHandlerInterface $theme_handler) {
    $this->patternkitStorage = $patternkit_storage;
    $this->library = $library;
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
    /** @var \Drupal\patternkit\Asset\LibraryInterface $library */
    $library = $container->get('patternkit.asset.library');
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = $container->get('theme_handler');
    return new static(
      $entity_manager->getStorage('patternkit_block'),
      $library,
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
      $types = $this->library->getAssets();
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
    foreach ($types as $pattern_key => $type) {
      try {
        $pattern = Pattern::create($type);
      }
      catch (\Exception $e) {
        continue;
      }
      $pattern_id = PatternkitBlock::assetToDerivativeId($pattern->getAssetId());
      $label = $pattern->label();
      $content['types'][$pattern_id] = [
        'link' => Link::fromTextAndUrl($label, Url::fromRoute('patternkit.add_form', ['pattern_id' => $pattern_id], ['query' => $query])),
        'description' => [
          '#markup' => $pattern->getDescription(),
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
    $block = $this->patternkitStorage->create([]);
    $block->setPattern($pattern_id);
    if (($theme = $request->query->get('theme'))
      && array_key_exists($theme, $this->themeHandler->listInfo())) {
      // We have navigated to this page from the block library and will keep
      // track of the theme for redirecting the user to the configuration page
      // for the newly created block in the given theme.
      $block->setTheme($theme);
    }
    return $this->entityFormBuilder()->getForm($block, 'default', ['pattern' => $pattern_id]);
  }

  /**
   * Returns the JSON-encoded Patternkit schema for the provided pattern.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $pattern_id
   *   The name of the pattern to use for retrieving the schema.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The schema response.
   */
  public function apiPattern(Request $request, $pattern_id = NULL): Response {
    $pattern = $request->query->get('pattern') ?? $pattern_id;
    $pattern = urldecode($pattern);
    if (!$pattern) {
      return new Response();
    }
    if ($request->query->get('asset') === 'schema') {
      return $this->apiPatternSchema($pattern);
    }
    $asset_id = '@' . $pattern;
    try {
      $response = $this->library->getLibraryAsset($asset_id);
      if ($response === NULL) {
        throw new \RuntimeException("Unable to locate $asset_id.");
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
   * @param string $pattern_id
   *   The name of the pattern to use for retrieving the schema.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The schema response.
   */
  public function apiPatternSchema($pattern_id): JsonResponse {
    $asset_id = '@' . urldecode($pattern_id);
    try {
      $pattern_asset = $this->library->getLibraryAsset($asset_id);
      if ($pattern_asset === NULL) {
        throw new \RuntimeException("Unable to locate $asset_id schema.");
      }
      $pattern = Pattern::create($pattern_asset);
      $response = json_decode($pattern->getSchema()) ?? [];
    }
    catch (\Exception $exception) {
      $response = [
        'error' => $exception->getMessage(),
        'patterns' => implode(', ', array_keys($this->library->getAssets()))
      ];
    }
    return new JsonResponse($response);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param string $pattern_id
   *   The pattern being added.
   *
   * @return string
   *   The page title.
   *
   * @throws \Exception
   */
  public function getAddFormTitle($pattern_id): string {
    $asset_id = PatternkitBlock::derivativeToAssetId(urldecode($pattern_id));
    try {
      $pattern_asset = $this->library->getLibraryAsset($asset_id);
      if ($pattern_asset === NULL) {
        throw new \RuntimeException("Unable to locate $asset_id.");
      }
      $pattern = Pattern::create($pattern_asset);
    }
    catch (\Exception $exception) {
      throw $exception;
    }
    return $this->t('Add %type Patternkit block', ['%type' => $pattern->label()]);
  }

  public function patternTitle(Request $request, Pattern $pattern) {
    return '';
  }

  public function patternView(Request $request, Pattern $pattern) {
    return [];
  }
}
