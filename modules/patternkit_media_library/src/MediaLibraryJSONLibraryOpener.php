<?php

namespace Drupal\patternkit_media_library;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media_library\MediaLibraryOpenerInterface;
use Drupal\media_library\MediaLibraryState;
use Drupal\patternkit\AJAX\PatternkitEditorUpdateCommand;

/**
 * The media library opener for field widgets.
 */
class MediaLibraryJSONLibraryOpener implements MediaLibraryOpenerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * MediaLibraryFieldWidgetOpener constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionResponse(MediaLibraryState $state, array $selected_ids): AjaxResponse {
    $response = new AjaxResponse();

    $parameters = $state->getOpenerParameters();
    if (empty($parameters['field_widget_id'])) {
      throw new \InvalidArgumentException('field_widget_id parameter is missing.');
    }

    $widget_id = $parameters['field_widget_id'];
    if (!$mid = reset($selected_ids)) {
      return $response;
    }
    try {
      /** @var \Drupal\media\Entity\Media $media */
      $media = $this->entityTypeManager->getStorage('media')->load($mid);
      $fid = $media->getSource()->getSourceFieldValue($media);
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
    }
    catch (\Exception $exception) {
      return $response;
    }
    if (!$file) {
      return $response;
    }
    try {
      if ($file->hasLinkTemplate('canonical')) {
        $url = $file->toUrl()->setAbsolute(FALSE);
      }
      elseif ($file->access('download')) {
        $url = $this->fileUrlGenerator->generateString($file->getFileUri());
      }
      else {
        $url = $file->label();
      }
      $response->addCommand(new PatternkitEditorUpdateCommand($widget_id, $url));
    }
    catch (\Exception $exception) {
      return $response;
    }

    return $response;
  }

}
