<?php

namespace Drupal\patternkit_media_library\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for supporting media content selection in Patternkit.
 */
class PatternkitMediaLibraryController extends ControllerBase {

  /**
   * The media library UI builder service.
   *
   * @var \Drupal\media_library\MediaLibraryUiBuilder
   */
  protected MediaLibraryUiBuilder $mediaLibraryUiBuilder;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The media view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected EntityViewBuilderInterface $mediaViewBuilder;

  /**
   * Constructs a new PatternkitMediaLibraryController instance.
   *
   * @param \Drupal\media_library\MediaLibraryUiBuilder $media_library_builder
   *   The media library UI builder service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $media_view_builder
   *   The media view builder.
   */
  public function __construct(
    MediaLibraryUiBuilder $media_library_builder,
    EntityRepositoryInterface $entity_repository,
    EntityViewBuilderInterface $media_view_builder,
  ) {
    $this->mediaLibraryUiBuilder = $media_library_builder;
    $this->entityRepository = $entity_repository;
    $this->mediaViewBuilder = $media_view_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $container->get('media_library.ui_builder'),
      $container->get('entity.repository'),
      $entity_type_manager->getViewBuilder('media')
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

}
