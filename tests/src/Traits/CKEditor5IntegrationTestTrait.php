<?php

namespace Drupal\Tests\patternkit\Traits;

use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use PHPUnit\Framework\ExpectationFailedException;

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

    $editor = Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor5',
    ]);

    // Add the source editing button to the toolbar.
    $settings = $editor->getSettings();
    $settings['toolbar']['items'][] = 'sourceEditing';
    $editor->setSettings($settings);

    $editor->save();

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

  /**
   * Mimic the change event triggered by changing content in CKEditor.
   *
   * When entering the content traditionally in the editor, manually triggering
   * the change event would not be needed. Unfortunately, the web driver doesn't
   * trigger the event when setting the data value, so we have to trigger it
   * manually for tests.
   *
   * @param string $schema_path
   *   The schema path to the editor to trigger a change event on. For example,
   *   "root.formatted_text".
   */
  protected function triggerCkeditorChange(string $schema_path): void {
    $javascript = <<<JS
      (function(){
        window.patternkitEditor.getEditor('$schema_path').saveEditorContent();
      })();
      JS;
    $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Attempt to press the "Source" button with failure handling.
   *
   * Attempt to click the "Source" button, but try again after clicking the
   * "Show more" button if it fails to find it.
   */
  protected function pressSourceButton(): void {
    try {
      $this->pressEditorButton('Source');
    }
    catch (ExpectationFailedException $exception) {
      $this->pressEditorButton('Show more items');
      $this->pressEditorButton('Source');
    }
  }

}
