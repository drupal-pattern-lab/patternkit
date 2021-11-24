<?php

namespace Drupal\patternkit_media_library\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media_library\MediaLibraryState;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;

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
    /** @var \Drupal\media_library\MediaLibraryUiBuilder $ml_ui_builder */
    $ml_ui_builder = \Drupal::service('media_library.ui_builder');

    return $ml_ui_builder->buildUi($ml_state);
  }

}
