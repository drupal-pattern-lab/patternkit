<?php

namespace Drupal\patternkit\AJAX;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for invoking a Patternkit JSON Editor Update method.
 *
 * This command is implemented by
 * Drupal.AjaxCommands.prototype.patternkitEditorUpdateCommand()
 * defined in js/patternkit.jsoneditor.js.
 *
 * @ingroup ajax
 */
class PatternkitEditorUpdateCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value can be NULL.
   *
   * @var string
   */
  protected $selector;

  /**
   * The value to pass to the method.
   *
   * @var array
   */
  protected $value;

  /**
   * Constructs a PatternkitEditorUpdateCommand object.
   *
   * @param string $selector
   *   A jQuery selector.
   * @param mixed $value
   *   The value to pass to the setter.
   */
  public function __construct($selector, $value) {
    $this->selector = $selector;
    $this->value = $value;
  }

  /**
   * {@inheritDoc}
   */
  public function render(): array {
    return [
      'command' => 'patternkitEditorUpdate',
      'selector' => $this->selector,
      'value' => $this->value,
    ];
  }

}
