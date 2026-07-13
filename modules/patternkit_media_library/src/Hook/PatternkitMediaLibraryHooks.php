<?php

namespace Drupal\patternkit_media_library\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\patternkit_media_library\JsonEditorFormElementAlterHandler;

/**
 * Hooks for the Patternkit Media Library module.
 */
class PatternkitMediaLibraryHooks {

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(array &$info): void {
    if (isset($info['jsoneditor_form'])) {
      // Add a pre-renderer for handling media library customizations.
      $info['jsoneditor_form']['#pre_render'][] = [
        JsonEditorFormElementAlterHandler::class,
        'attachMediaLibrarySupport',
      ];
    }
  }

}
