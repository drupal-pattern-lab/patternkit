<?php

namespace Drupal\patternkit_media_library\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\patternkit_media_library\AJAX\MediaAttributesCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\patternkit_media_library\MediaAttributeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for supporting media content selection in Patternkit.
 */
class PatternkitMediaLibraryController extends ControllerBase {

  /**
   * Constructs a new PatternkitMediaLibraryController instance.
   *
   * @param \Drupal\media_library\MediaLibraryUiBuilder $mediaLibraryUiBuilder
   *   The media library UI builder service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $mediaViewBuilder
   *   The media view builder.
   * @param \Drupal\patternkit_media_library\MediaAttributeService $mediaAttributeService
   *   The media attribute service.
   */
  public function __construct(
    protected MediaLibraryUiBuilder $mediaLibraryUiBuilder,
    protected EntityRepositoryInterface $entityRepository,
    protected EntityViewBuilderInterface $mediaViewBuilder,
    protected MediaAttributeService $mediaAttributeService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $container->get('media_library.ui_builder'),
      $container->get('entity.repository'),
      $entity_type_manager->getViewBuilder('media'),
      $container->get('patternkit_media_library.media_attributes')
    );
  }

  /**
   * Builds the UI.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Symfony request object.
   *
   * @return array
   *   The render array for the media library.
   */
  public function mediaLibrary(Request $request): array {
    $query = $request->query;

    // @todo Remove this when Symfony 4 is no longer supported.
    if (!($query instanceof InputBag)) {
      $query = new InputBag($query->all());
    }

    $ml_state = MediaLibraryState::create(
      $query->get('media_library_opener_id'),
      $query->all('media_library_allowed_types'),
      $query->get('media_library_selected_type'),
      $query->get('media_library_remaining'),
      $query->all('media_library_opener_parameters')
    );

    return $this->mediaLibraryUiBuilder->buildUi($ml_state);
  }

  /**
   * Renders a preview of the selected media entity.
   *
   * @param string $uuid
   *   The UUID of the media entity to be previewed.
   *
   * @return array
   *   A render array of the media entity preview.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if the referenced media entity could not be found.
   */
  public function mediaPreview(string $uuid): array {
    $media = $this->entityRepository->loadEntityByUuid('media', $uuid);

    if ($media !== NULL) {
      return $this->mediaViewBuilder->view($media, 'media_library');
    }
    else {
      throw new NotFoundHttpException(sprintf('Unable to find media entity with UUID "%s".', $uuid));
    }
  }

  /**
   * Returns media attributes for a file URL.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request containing the URL query parameter.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   AjaxResponse for AJAX requests, JsonResponse otherwise.
   *   Contains media attributes (alt, width, height, title)
   *   or empty array if no matching media found.
   */
  public function mediaAttributesByUrl(Request $request) {
    $query = $request->query;

    // @todo Remove this when Symfony 4 is no longer supported.
    if (!($query instanceof InputBag)) {
      $query = new InputBag($query->all());
    }

    $url = $query->get('url');

    if (empty($url)) {
      $attributes = [];
    }
    else {
      $attributes = $this->mediaAttributeService->getAttributesByUrl($url);
    }

    // Return AjaxResponse for AJAX requests to work with Drupal AJAX system.
    if ($request->isXmlHttpRequest()) {
      $response = new AjaxResponse();
      // Get the container ID from the request if provided.
      $container_id = $query->get('container_id') ?? '';
      // Use custom AJAX command to pass attributes directly to JavaScript.
      $response->addCommand(new MediaAttributesCommand($attributes, $container_id));
      return $response;
    }

    // Return JsonResponse for non-AJAX requests.
    return new JsonResponse($attributes);
  }

}
