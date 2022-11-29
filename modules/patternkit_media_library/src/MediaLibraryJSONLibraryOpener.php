<?php

namespace Drupal\patternkit_media_library;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\Attribute;
use Drupal\media\MediaInterface;
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
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The loaded settings for the Pattern Media Library module.
   *
   * @var array
   */
  protected array $settings;

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

    // Return a media token by default, but return the file URL instead if the
    // extra "media_token" parameter is set to false.
    $use_media_token = TRUE;
    if (isset($parameters['media_token'])) {
      $use_media_token = (bool) $parameters['media_token'];
    }

    $widget_id = $parameters['field_widget_id'];
    if (!$mid = reset($selected_ids)) {
      return $response;
    }
    try {
      /** @var \Drupal\media\Entity\Media $media */
      $media = $this->entityTypeManager->getStorage('media')->load($mid);

      if ($use_media_token) {
        $value = $this->getMediaToken($media);
      }
      else {
        $value = $this->getFileUrl($media);
      }

      $response->addCommand(new PatternkitEditorUpdateCommand($widget_id, $value));
    }
    catch (\Exception $exception) {
      return $response;
    }

    return $response;
  }

  /**
   * Get a media embed token for a Media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The Media entity to be referenced.
   *
   * @return string
   *   A media embed token for rendering into media output.
   *
   * @see \Drupal\media\Plugin\Filter\MediaEmbed::process()
   */
  protected function getMediaToken(MediaInterface $media): string {
    $attributes = new Attribute([
      'data-entity-type' => 'media',
      'data-entity-uuid' => $media->uuid(),
    ]);

    // A leading space is included in the attributes output, so no space is
    // included here to avoid duplication.
    return "<drupal-media$attributes>";
  }

  /**
   * Get a file URL to return for linking.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The selected media entity to get a file link for.
   *
   * @return string
   *   A URL for the media entity's source file.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getFileUrl(MediaInterface $media): string {
    $fid = $media->getSource()->getSourceFieldValue($media);
    $file = $this->entityTypeManager->getStorage('file')->load($fid);

    if ($file->hasLinkTemplate('canonical')) {
      $url = $file->toUrl()->setAbsolute(FALSE);
    }
    elseif ($file->access('download')) {
      $url = $this->fileUrlGenerator->generateString($file->getFileUri());
    }
    else {
      $url = $file->label();
    }

    return $url;
  }

}
