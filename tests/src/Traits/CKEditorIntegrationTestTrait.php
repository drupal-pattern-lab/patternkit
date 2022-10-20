<?php

namespace Drupal\Tests\patternkit\Traits;

use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;

/**
 * A helper trait to interact with and test CKEditor integration.
 *
 * This trait is only intended for use in tests.
 */
trait CKEditorIntegrationTestTrait {

  use CKEditorTestTrait;

  /**
   * Get the content of a field's editor instance.
   *
   * @param string $field_name
   *   The JSON Editor field name for the field to interact with, such as
   *   'root.formatted_text'.
   *
   * @return string
   *   The returned value from the editor instance.
   */
  protected function getEditorValue(string $field_name): string {
    $js = sprintf('patternkitEditor.getEditor("%s").getValue()', $field_name);
    return $this->getSession()->evaluateScript($js);
  }

  /**
   * Get the content of a field's editor instance.
   *
   * @param string $field_name
   *   The JSON Editor field name for the field to interact with, such as
   *   'root.formatted_text'.
   * @param string $value
   *   The markup value to set in the editor.
   */
  protected function setEditorValue(string $field_name, string $value): void {
    $js = sprintf('patternkitEditor.getEditor("%s").setValue("%s")', $field_name, $value);
    $this->getSession()->evaluateScript($js);
  }

}
