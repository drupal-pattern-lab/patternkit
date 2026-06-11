<?php

namespace Drupal\patternkit_media_library;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Security\Attribute\TrustedCallback;

// cspell:ignore prerendered
/**
 * A handler service to add necessary alternations to jsoneditor_form elements.
 */
class JsonEditorFormElementAlterHandler {

  /**
   * Element prerender callback for jsoneditor_form adding media support.
   *
   * @param array $element
   *   The jsoneditor_form render element being prerendered.
   *
   * @return array
   *   The altered jsoneditor_form render element.
   *
   * @see \patternkit_media_library_element_info_alter()
   * @see \Drupal\patternkit\Element\JsonEditorForm
   */
  #[TrustedCallback]
  public static function attachMediaLibrarySupport(array $element): array {
    $url_generator = \Drupal::urlGenerator();
    /** @var \Drupal\Core\GeneratedUrl $media_url_obj */
    $media_url_obj = $url_generator->generateFromRoute('patternkit.media_library', [], [], TRUE);
    $media_url = $media_url_obj->getGeneratedUrl();
    $settings = [
      'imageUrl' => $media_url,
      'videoUrl' => $media_url,
    ];
    $attachments['drupalSettings']['patternkitEditor'] = $settings;
    $attachments['library'][] = 'patternkit_media_library/patternkit.jsoneditor.media_library';

    $config = \Drupal::config('patternkit_media_library.settings');
    if ($config->get('use_styles')) {
      $attachments['library'][] = 'patternkit_media_library/media_library_modal';
    }

    // Expose media/field type mapping configuration to drive allowed types in
    // media modals.
    $media_mapping = $config->get('media_mapping') ?? [
      'image_types' => [],
      'video_types' => [],
    ];
    $attachments['drupalSettings']['patternkitEditor']['mediaMapping'] = $media_mapping;

    // Assemble and apply all attachments.
    $element_attachments = BubbleableMetadata::createFromRenderArray($element);
    $element_attachments->addAttachments($attachments);
    $element_attachments->addCacheableDependency($media_url_obj);
    $element_attachments->addCacheableDependency($config);
    $element_attachments->applyTo($element);

    return $element;
  }

}
