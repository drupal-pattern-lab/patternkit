<?php

namespace Drupal\Tests\patternkit\Traits;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;

/**
 * A helper trait to interact with and test CKEditor integration.
 *
 * This trait is only intended for use in tests.
 */
trait CKEditor5IntegrationTestTrait {

  use CKEditor5TestTrait;

  /**
   * Create the appropriate filter form and editor configurations for CKEditor.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function configureCkeditorFormat(): void {
    // Create a text format and associate CKEditor.
    FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ])->save();
    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor5',
    ])->save();

    // Enable CKEditor integration.
    $config = $this->config('patternkit.settings');
    $config->set('patternkit_json_editor_wysiwyg', 'ckeditor5');
    $config->set('patternkit_json_editor_ckeditor_toolbar', 'filtered_html');
    $config->save();
  }

  /**
   * Get the content of a field's editor instance.
   *
   * This is a revised implementation of
   * @link \Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait::getEditorDataAsHtmlString() @endlink
   * that allows specification of a specific field name to support testing forms
   * with multiple editors present.
   *
   * @param string $schema_path
   *   The JSON Editor schema path for the field to interact with, such as
   *   'root.formatted_text'.
   *
   * @return string
   *   The returned value from the editor instance.
   */
  protected function getEditorValue(string $schema_path): string {
    $ckeditor_id = $this->getCkeditorIdForElement($schema_path);

    // We cannot trust on CKEditor updating the textarea every time model
    // changes. Therefore, the most reliable way to get downcasted data is to
    // use the CKEditor API.
    $javascript = <<<JS
      (function(){
        return Drupal.CKEditor5Instances.get('$ckeditor_id').getData();
      })();
      JS;
    return $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Get the content of a field's editor instance.
   *
   * @param string $schema_path
   *   The JSON Editor schema path for the field to interact with, such as
   *   'root.formatted_text'.
   * @param string $value
   *   The markup value to set in the editor.
   */
  protected function setEditorValue(string $schema_path, string $value): void {
    $ckeditor_id = $this->getCkeditorIdForElement($schema_path);

    // We cannot trust on CKEditor updating the textarea every time model
    // changes. Therefore, the most reliable way to get downcasted data is to
    // use the CKEditor API.
    $javascript = <<<JS
      (function(){
        Drupal.CKEditor5Instances.get('$ckeditor_id').setData('$value');
      })();
      JS;
    $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Get the CKEditor instance ID for a specified JSON Editor field.
   *
   * @param string $schema_path
   *   The JSON Editor schema path for the field to interact with, such as
   *   'root.formatted_text'.
   *
   * @return string
   *   The numeric identifier for the CKEditor instance attached to the
   *   specified JSON Editor field.
   */
  protected function getCkeditorIdForElement(string $schema_path): string {
    /** @var \Behat\Mink\Element\NodeElement $wrapper */
    $wrapper = $this->getSession()->getPage()
      ->find('css', '[data-schemapath="' . $schema_path . '"]');
    $this->assertNotEmpty($ckeditor_id = $wrapper->find('css', '[data-ckeditor5-id]')->getAttribute('data-ckeditor5-id'));

    return $ckeditor_id;
  }

}
