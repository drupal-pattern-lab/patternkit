<?php

namespace Drupal\patternkit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\Entity\Media;
use Drupal\media_library\MediaLibraryOpenerInterface;
use Drupal\media_library\MediaLibraryState;
use Drupal\patternkit\AJAX\PatternkitEditorUpdateCommand;
use Exception;

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
   * MediaLibraryFieldWidgetOpener constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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
      /** @var Media $media */
      $media = $this->entityTypeManager->getStorage('media')->load($mid);
      $fid = $media->getSource()->getSourceFieldValue($media);
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
    }
    catch (Exception $exception) {
      return $response;
    }
    if (!$file) {
      return $response;
    }
    try {
      if ($file->hasLinkTemplate('canonical')) {
        $url = $file->toUrl()->setAbsolute(TRUE);
      }
      elseif ($file->access('download')) {
        $url = file_create_url($file->getFileUri());
      }
      else {
        $url = $file->label();
      }
      $response->addCommand(new PatternkitEditorUpdateCommand($widget_id, $url));
    }
    catch (Exception $exception) {
      return $response;
    }

    return $response;
  }

}
