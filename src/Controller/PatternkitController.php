<?php

namespace Drupal\patternkit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Plugin\Derivative\PatternkitBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The response controller for Patternkit API endpoints.
 */
class PatternkitController extends ControllerBase {

  /**
   * The custom block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $blockStorage;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * The pattern repository service.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

  /**
   * Constructs a Patternkit Controller.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $patternkit_block_storage
   *   The Patternkit block entity storage.
   * @param \Drupal\patternkit\PatternRepository $pattern_repository
   *   The pattern repository service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   */
  public function __construct(
    EntityStorageInterface $patternkit_block_storage,
    PatternRepository $pattern_repository,
    ThemeHandlerInterface $theme_handler,
    Request $request
  ) {
    $this->blockStorage = $patternkit_block_storage;
    $this->patternRepository = $pattern_repository;
    $this->themeHandler = $theme_handler;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public static function create(ContainerInterface $container): self {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('patternkit_block'),
      $container->get('patternkit.pattern.repository'),
      $container->get('theme_handler'),
      $container->get('request_stack')->getCurrentRequest()
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
      $patterns = $this->patternRepository->getAllPatternNames();
    }
    catch (\Exception $exception) {
      $this->getLogger('patternkit')->error('Error loading pattern library assets: ', ['@message' => $exception->getMessage()]);
      return [
        '#markup' => $this->t('Error loading pattern library assets - check the log or the format of your libraries.yml.'),
      ];
    }
    if (count($patterns) === 0) {
      return [
        '#markup' => $this->t('Could not find any patterns to load. Did you add a theme or module that includes a patterns definition in the libraries.yml?'),
      ];
    }
    $content['types'] = [];
    $query = $this->request->query->all();
    foreach ($patterns as $asset_id) {
      try {
        $pattern = $this->patternRepository->getPattern($asset_id);
      }
      catch (\Exception $e) {
        continue;
      }
      $derivative_id = PatternkitBlock::assetToDerivativeId($asset_id);
      $label = $pattern->label();
      $content['types'][$derivative_id] = [
        'link' => Link::fromTextAndUrl($label, Url::fromRoute('patternkit.add_form', ['pattern_id' => $derivative_id], ['query' => $query])),
        'description' => [
          '#markup' => $pattern->getDescription(),
        ],
        'title' => $label,
        'localized_options' => [
          'query' => $query,
        ],
      ];
    }

    return [
      '#theme' => 'patternkit_add_list',
      '#content' => $content,
    ];
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
  public function addForm(string $pattern_id, Request $request): array {
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $this->blockStorage->create([]);
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
   * @param string|null $pattern_id
   *   The name of the pattern to use for retrieving the schema.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The schema response.
   */
  public function apiPattern(Request $request, ?string $pattern_id = NULL): Response {
    $pattern_id = $request->query->get('pattern') ?? $pattern_id;
    $pattern_id = urldecode($pattern_id);
    if (!$pattern_id) {
      return new Response();
    }
    if ($request->query->get('asset') === 'schema') {
      return $this->apiPatternSchema($pattern_id);
    }
    $asset_id = '@' . $pattern_id;
    try {
      $pattern = $this->patternRepository->getPattern($asset_id);
    }
    catch (UnknownPatternException $exception) {
      $response = [
        'error' => $exception->getMessage(),
        'patterns' => implode(', ', $this->patternRepository->getAllPatternNames()),
      ];
      return new JsonResponse($response, 404);
    }
    catch (\Exception $exception) {
      $response = ['error' => $exception->getMessage()];
      return new JsonResponse($response, 500);
    }

    return new JsonResponse($pattern);
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
  public function apiPatternSchema(string $pattern_id): JsonResponse {
    $asset_id = '@' . urldecode($pattern_id);
    try {
      $pattern = $this->patternRepository->getPattern($asset_id);
      $response = json_decode($pattern->getSchema()) ?? [];
    }
    catch (UnknownPatternException $exception) {
      $response = [
        'error' => $exception->getMessage(),
        'patterns' => implode(', ', $this->patternRepository->getAllPatternNames()),
      ];
      return new JsonResponse($response, 404);
    }
    catch (\Exception $exception) {
      $response = ['error' => $exception->getMessage()];
      return new JsonResponse($response, 500);
    }

    return new JsonResponse($response);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param string $pattern_id
   *   The pattern block derivative ID for the pattern being added.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\patternkit\Exception\UnknownPatternException
   */
  public function getAddFormTitle(string $pattern_id): TranslatableMarkup {
    $asset_id = PatternkitBlock::derivativeToAssetId(urldecode($pattern_id));
    $pattern = $this->patternRepository->getPattern($asset_id);
    return $this->t('Add %type Patternkit block', ['%type' => $pattern->label()]);
  }

  /**
   * Get the title of the pattern.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern to display the title of.
   *
   * @return string
   *   The title of the pattern.
   */
  public function patternTitle(Request $request, PatternInterface $pattern): string {
    return '';
  }

  /**
   * View the pattern.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern to display.
   *
   * @return array
   *   The render array for output of the requested pattern.
   */
  public function patternView(Request $request, PatternInterface $pattern): array {
    return [];
  }

}
