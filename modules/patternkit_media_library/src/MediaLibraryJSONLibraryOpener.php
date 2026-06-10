<?php

namespace Drupal\patternkit_media_library;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\ScrollTopCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Utility\Error;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media_library\MediaLibraryOpenerInterface;
use Drupal\media_library\MediaLibraryState;
use Drupal\patternkit\AJAX\PatternkitEditorUpdateCommand;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * The media library opener for field widgets.
 */
class MediaLibraryJSONLibraryOpener implements MediaLibraryOpenerInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * MediaLibraryFieldWidgetOpener constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file url generator service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

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
      /** @var \Drupal\media\MediaInterface|null $media */
      $media = $this->entityTypeManager->getStorage('media')->load($mid);

      // Fail here if the Media entity couldn't be loaded.
      if (!($media instanceof MediaInterface)) {
        $this->logger->error('Unable to load media entity "@mid" for media opener.', [
          '@mid' => $mid,
        ]);
        return $response;
      }

      if ($use_media_token) {
        $media_token = $this->getMediaToken($media);
        $response->addCommand(new PatternkitEditorUpdateCommand($widget_id, $media_token));
      }
      else {
        $image_src = $this->getFileUrl($media);
        $response->addCommand(new PatternkitEditorUpdateCommand($widget_id, $image_src));

        // If we're populating an image src field, also try to populate sibling
        // attributes (such as alt text and dimensions). If a schema does not
        // include these sibling fields, this additional processing is ignored.
        if (str_ends_with($widget_id, '.src')) {
          // Attempt to load the source file to identify additional available
          // attributes.
          $image = $this->getSourceFile($media);

          // Stop here if the image was not able to be loaded.
          if ($image !== NULL) {
            // Remove 'src' from the end of the widget path to easily append the
            // additional attributes.
            $widget_base_path = substr($widget_id, 0, -3);

            $source_configuration = $media->getSource()?->getConfiguration();
            $source_field_name = is_array($source_configuration) ? $source_configuration['source_field'] : NULL;

            if ($source_field_name !== NULL) {
              // Images will typically produce the following attributes: 'alt',
              // 'height', 'target_id', 'title', and 'width'.
              $attributes = $media->get($source_field_name)?->first()?->getValue() ?: [];
              foreach ($attributes as $key => $value) {
                // Only assign attributes with non-empty values, and ignore the
                // internal value for the target_id referencing the file entity.
                if ($key != 'target_id' && $value) {
                  $target = $widget_base_path . $key;

                  // If a field is not found in the schema client-side, the
                  // command should fail silently.
                  $response->addCommand(new PatternkitEditorUpdateCommand($target, $value));
                }
              }
            }
          }
        }
      }
    }
    catch (\Exception $exception) {
      // Log the failure server-side for review and visibility.
      Error::logException($this->logger, $exception);

      // Display a message client-side for context when the value fails to be
      // populated.
      $response->addCommand(new MessageCommand(
        'There was a problem selecting the media asset. Save your work.',
        '.patternkit-form-messages .messages-list',
        ['type' => 'warning']
      ));

      // A redundant close dialog command is added by the
      // MediaLibrarySelectForm, but we have to close the media dialog first for
      // the scroll command to take effect.
      $response->addCommand(new CloseDialogCommand('#drupal-modal'));
      $response->addCommand(new ScrollTopCommand('.patternkit-form-messages'));

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
    $file = $this->getSourceFile($media);

    if ($file?->hasLinkTemplate('canonical')) {
      $url = $file->toUrl()->setAbsolute(FALSE);
    }
    elseif ($file?->access('download')) {
      $url = $this->fileUrlGenerator->generateString($file->getFileUri());
    }
    else {
      $url = $file?->label();
    }

    return (string) $url;
  }

  /**
   * Load the source file entity from the given media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to load the source file from.
   *
   * @return \Drupal\file\FileInterface|null
   *   The loaded source file entity or NULL if the file could not be loaded.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSourceFile(MediaInterface $media): ?FileInterface {
    $fid = $media->getSource()->getSourceFieldValue($media);
    $file = $this->entityTypeManager->getStorage('file')->load($fid);

    assert($file === NULL || $file instanceof FileInterface);
    return $file;
  }

}
