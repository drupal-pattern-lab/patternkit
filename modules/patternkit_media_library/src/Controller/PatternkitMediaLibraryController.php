<?php

namespace Drupal\patternkit_media_library\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for block example routes.
 */
class PatternkitMediaLibraryController extends ControllerBase {

  /**
   * Returns a media library display especially for Patternkit.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Symfony request object.
   *
   * @return array
   *   A Drupal render array.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */

  /**
   * Block storage manager.
   *
   * @var \Drupal\media_library\MediaLibraryUiBuilderInterface
   */
  protected MediaLibraryUiBuilder $mediaLibraryUiBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(MediaLibraryUiBuilder $media_library_builder) {
    $this->mediaLibraryUiBuilder = $media_library_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_library.ui_builder')
    );
  }

  /**
   * Builds the UI.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The Symfony request object.
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

    return $this->mediaLibraryUiBuilder->buildUi($ml_state);

  }

}
