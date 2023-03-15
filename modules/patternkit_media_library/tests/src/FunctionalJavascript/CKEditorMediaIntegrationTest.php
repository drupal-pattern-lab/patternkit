<?php

namespace Drupal\Tests\patternkit_media_library\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\patternkit\Traits\CKEditorIntegrationTestTrait;

/**
 * Testing for media library integration with CKEditor in Patternkit forms.
 *
 * @group patternkit
 * @group patternkit_media_library
 * @group legacy
 * @requires module ckeditor
 */
class CKEditorMediaIntegrationTest extends PatternkitMediaLibraryBrowserTestBase {

  use CKEditorIntegrationTestTrait;

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
    'patternkit_example',
    'ckeditor',
    'media_library',
  ];

  /**
   * An array of needed editor permissions.
   *
   * @var string[]
   */
  protected array $editorPermissions = [
    'access administration pages',
    'configure any layout',
    'create and edit custom blocks',
    'bypass node access',

    // Add additional permissions for media interaction.
    'use text format test_format',
    'access media overview',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'media_embed' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'rows' => [
            [
              [
                'name' => 'Main',
                'items' => [
                  'Source',
                  'Undo',
                  'Redo',
                ],
              ],
            ],
            [
              [
                'name' => 'Embeds',
                'items' => [
                  'DrupalMediaLibrary',
                ],
              ],
            ],
          ],
        ],
      ],
    ])->save();

    // Enable CKEditor integration.
    $config = $this->config('patternkit.settings');
    $config->set('patternkit_json_editor_wysiwyg', 'ckeditor');
    $config->set('patternkit_json_editor_ckeditor_toolbar', 'test_format');
    $config->save();

    $this->editorUser = $this->drupalCreateUser($this->editorPermissions);
    $this->drupalLogin($this->editorUser);
  }

  /**
   * Test placement of media within a CKEditor field.
   *
   * Most of the CKEditor interaction and testing is based off of the
   * @link \Drupal\Tests\media_library\FunctionalJavascript\CKEditorIntegrationTest::testButton @endlink
   * method.
   *
   * @see \Drupal\Tests\media_library\FunctionalJavascript\CKEditorIntegrationTest::testButton()
   *
   * @todo Re-enable this test once #3092593 is resolved.
   * @see https://www.drupal.org/project/drupal/issues/3092593
   */
  public function testCkeditorMediaSelection(): void {
    // See https://www.drupal.org/project/drupal/issues/3092593.
    $this->markTestSkipped('Skip CKEditor media embed testing until issue #3092593 is resolved.');

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example';

    // Log in as an editor with access to update layouts.
    $this->drupalLogin($this->editorUser);

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $page->clickLink('Add block');

    // Wait for the block list to load before selecting a pattern to insert.
    $assert->waitForLink($pattern_name)->click();

    // Wait for CKEditor to load.
    $assert->waitForField('root[text]');
    $this->waitForEditor('editor1');

    // Press to select and insert media from the library.
    $this->pressEditorButton('drupalmedialibrary', 'editor1');

    // Wait for the library modal.
    $this->assertElementExistsAfterWait('css', '.js-media-library-view');

    // Select and save a media item.
    $this->selectMediaItem(0);
    $buttons = $this->assertElementExistsAfterWait('css', '.ui-dialog-buttonpane');
    $buttons->findButton('Insert selected')->press();

    $assert->waitForElementRemoved('css', '.js-media-library-view');

    // Confirm the media item was added to the CKEditor field.
    $this->assignNameToCkeditorIframe('ckeditor', 'editor1');
    $this->getSession()->switchToIFrame('ckeditor');

    // Search for the media entity added into the CKEditor rich text display.
    $this->assertElementExistsAfterWait('css', '.cke_widget_drupalmedia drupal-media img');

    // Confirm the media token was added to the source.
    $this->pressEditorButton('source', 'editor1');
    $value = $this->assertElementExistsAfterWait('css', 'textarea.cke_source')->getValue();
    $dom = Html::load($value);
    $xpath = new \DOMXPath($dom);
    $drupal_media = $xpath->query('//drupal-media')[0];
    $expected_attributes = [
      'data-entity-type' => 'media',
      'data-entity-uuid' => $this->media->uuid(),
      'data-align' => 'center',
    ];
    foreach ($expected_attributes as $name => $expected) {
      $this->assertSame($expected, $drupal_media->getAttribute($name));
    }

    // Save the layout to confirm it displays as expected.
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->elementExists('css', '.media .field--type-image img');

    // Get block ID to return to edit form.
    $uuid = $this->getLastLayoutBlockUuid(Node::load(1));

    // Confirm all configuration content is persisted when editing the block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $assert->waitForField('root[text]');
    $this->waitForEditor('editor1');

    // Confirm text values in CKEditor fields.
    $value = $this->getEditorValue('root.formatted_text');
    $this->assertStringContainsString('<drupal-media', $value);
  }

}
