<?php

namespace Drupal\patternkit_media_library\AJAX;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for passing media attributes to JavaScript.
 *
 * This command is implemented by
 * Drupal.AjaxCommands.prototype.patternkitMediaAttributes()
 * defined in js/patternkit.jsoneditor.media_library.js.
 *
 * @ingroup ajax
 */
class MediaAttributesCommand implements CommandInterface {

  /**
   * Constructs a MediaAttributesCommand object.
   *
   * @param array $attributes
   *   The media attributes (alt, width, height, title).
   * @param string $containerId
   *   The container ID for the editor.
   */
  public function __construct(
    protected array $attributes,
    protected string $containerId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'patternkitMediaAttributes',
      'attributes' => $this->attributes,
      'containerId' => $this->containerId,
    ];
  }

}
