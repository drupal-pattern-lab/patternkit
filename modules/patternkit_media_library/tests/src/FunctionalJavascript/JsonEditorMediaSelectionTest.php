<?php

namespace Drupal\Tests\patternkit_media_library\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\patternkit_media_library\Form\MediaLibrarySettingsForm;

/**
 * Browser test functionality for selecting media in JSON editor forms.
 *
 * @group patternkit
 * @group patternkit_media_library
 */
class JsonEditorMediaSelectionTest extends PatternkitMediaLibraryBrowserTestBase {

  /**
   * An image media entity for testing.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected MediaInterface $image;

  /**
   * A video media entity for testing.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected MediaInterface $video;

  /**
   * An array of needed editor permissions.
   *
   * @var string[]
   */
  protected array $editorPermissions = [
    'configure any layout',
    'create and edit custom blocks',
    'bypass node access',

    // Add additional permissions for media interaction.
    'access media overview',
    'view media',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Save into a new label for clarity.
    $this->image = $this->media;

    // Create a dummy file type to use for video selection. We're using a dummy
    // file type instead of actual videos since the currently available file
    // generation traits don't include support for preparing a video file.
    // @todo Implement actual video files instead.
    $this->createMediaType('file', [
      'id' => 'dummy_file',
      'label' => 'Dummy file',
    ]);
    File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ])->save();
    $this->video = Media::create([
      'bundle' => 'dummy_file',
      'name' => 'Intensely engaging video',
      'field_media_file' => [
        [
          'target_id' => 2,
          'alt' => 'Video alt',
          'title' => 'Video tile',
        ],
      ],
    ]);
    $this->video->save();

    // Configure media library mapping settings for Patternkit.
    $this->setMediaMappingConfiguration(['image'], ['dummy_file']);

    $this->editorUser = $this->drupalCreateUser($this->editorPermissions);
    $this->drupalLogin($this->editorUser);
  }

  /**
   * Test the workflow for adding and display an image from the media library.
   */
  public function testImageMediaSelection(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Media image embed';
    $field_name = 'root[image]';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Press the media selection button.
    $button = $assert->waitForButton('Select/Upload image');
    // Scroll the button into view since it may be below the fold.
    $this->scrollOffCanvasElementIntoView('button[title="Select/Upload image"]');
    $button->press();

    // Wait for the library modal.
    $this->assertElementExistsAfterWait('css', '.js-media-library-view');

    // @todo Validate the title of the modal as well.
    // @phpcs:ignore
    // $assert->elementTextContains('css', '.ui-dialog .ui-dialog-title', 'Select image');
    $assert->elementTextContains('css', '.js-media-library-view', 'Super fancy image');
    $this->selectMediaItem(0);
    $buttons = $this->assertElementExistsAfterWait('css', '#drupal-modal .form-actions');
    $buttons->findButton('Insert selected')->press();

    $assert->waitForElementRemoved('css', '.js-media-library-view');

    $media_uuid = $this->image->uuid();
    $assert->fieldValueEquals($field_name, "<drupal-media data-entity-type=\"media\" data-entity-uuid=\"$media_uuid\">");

    // Confirm the preview image is displaying as expected.
    $this->assertElementExistsAfterWait('css', '#je--root_image--preview .js-media-library-item-preview img');

    // Save the layout to confirm it displays as expected.
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->elementExists('css', '.patternkit-media-image-embed--component img');

    // Get block ID to return to edit form.
    $block_uuid = $this->getLastLayoutBlockUuid(Node::load(1));

    // Confirm all configuration content is persisted when editing the block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $block_uuid);
    $this->waitForJsonEditorForm();
    $assert->fieldValueEquals($field_name, "<drupal-media data-entity-type=\"media\" data-entity-uuid=\"$media_uuid\">");
  }

  /**
   * Test the workflow for adding and display a video from the media library.
   */
  public function testVideoMediaSelection(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Media video embed';
    $field_name = 'root[video]';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Press the media selection button.
    $button = $assert->waitForButton('Select/Upload video');
    // Scroll the button into view since it may be below the fold.
    $this->scrollOffCanvasElementIntoView('button[title="Select/Upload video"]');
    $button->press();

    // Wait for the library modal.
    $this->assertElementExistsAfterWait('css', '.js-media-library-view');

    // @todo Validate the title of the modal as well.
    // @phpcs:ignore
    // $assert->elementTextContains('css', '.ui-dialog .ui-dialog-title', 'Select video');
    $assert->elementTextContains('css', '.js-media-library-view', 'Intensely engaging video');
    $this->selectMediaItem(0);
    $buttons = $this->assertElementExistsAfterWait('css', '#drupal-modal .form-actions');
    $buttons->findButton('Insert selected')->press();

    $assert->waitForElementRemoved('css', '.js-media-library-view');

    $media_uuid = $this->video->uuid();
    $assert->fieldValueEquals($field_name, "<drupal-media data-entity-type=\"media\" data-entity-uuid=\"$media_uuid\">");

    // Confirm the preview image is displaying as expected.
    $this->assertElementExistsAfterWait('css', '#je--root_video--preview .js-media-library-item-preview img');

    // Save the layout to confirm it displays as expected.
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->elementExists('css', '.patternkit-media-video-embed--component img');

    // Get block ID to return to edit form.
    $block_uuid = $this->getLastLayoutBlockUuid(Node::load(1));

    // Confirm all configuration content is persisted when editing the block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $block_uuid);
    $this->waitForJsonEditorForm();
    $assert->fieldValueEquals($field_name, "<drupal-media data-entity-type=\"media\" data-entity-uuid=\"$media_uuid\">");
  }

  /**
   * Test the workflow for adding and displaying an image url from the library.
   */
  public function testImageUrlSelection(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example';
    $field_name = 'root[image_url]';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();
    $page->fillField('root[text]', 'PK Example block');
    $page->fillField('root[formatted_text]', 'Pattern block body');

    // Press the media selection button.
    $button = $assert->waitForButton('Select/Upload image');
    // Scroll the button into view since it may be below the fold.
    $this->scrollOffCanvasElementIntoView('button[title="Select/Upload image"]');
    $button->press();

    // Wait for the library modal.
    $this->assertElementExistsAfterWait('css', '.js-media-library-view');

    $assert->elementTextContains('css', '.js-media-library-view', 'Super fancy image');
    $this->selectMediaItem(0);
    $buttons = $this->assertElementExistsAfterWait('css', '#drupal-modal .form-actions');
    $buttons->findButton('Insert selected')->press();

    $assert->waitForElementRemoved('css', '.js-media-library-view');

    /** @var \Drupal\file\FileInterface $file */
    $file = $this->image->get('field_media_image')->entity;
    $file_uri = $file->createFileUrl();
    $assert->fieldValueEquals($field_name, $file_uri);

    // Confirm the preview image is displaying as expected.
    $css_target = '#patternkit-editor-target div[data-schemapath="root.image_url"] .preview img';
    $this->assertElementExistsAfterWait('css', $css_target);

    // Save the layout to confirm it displays as expected.
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->pageTextContains('PK Example block');
    $assert->pageTextContains('Pattern block body');
    $assert->elementExists('css', 'img[src="' . $file_uri . '"]');

    // Get block ID to return to edit form.
    $block_uuid = $this->getLastLayoutBlockUuid(Node::load(1));

    // Confirm all configuration content is persisted when editing the block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $block_uuid);
    $this->waitForJsonEditorForm();
    $assert->fieldValueEquals($field_name, $file_uri);
  }

  /**
   * Test the workflow for adding and multiple media from the media library.
   */
  public function testVariedMediaSelection(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Media combined embed';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Press the media selection button.
    $button = $assert->waitForButton('Select/Upload image');
    // Scroll the button into view since it may be below the fold.
    $this->scrollOffCanvasElementIntoView('button[title="Select/Upload image"]');
    $button->press();

    // Wait for the library modal.
    $this->assertElementExistsAfterWait('css', '.js-media-library-view');

    $assert->elementTextContains('css', '.js-media-library-view', 'Super fancy image');
    $this->selectMediaItem(0);
    $buttons = $this->assertElementExistsAfterWait('css', '#drupal-modal .form-actions');
    $buttons->findButton('Insert selected')->press();

    $assert->waitForElementRemoved('css', '.js-media-library-view');

    $image_uuid = $this->image->uuid();
    $image_media_token = "<drupal-media data-entity-type=\"media\" data-entity-uuid=\"$image_uuid\">";
    $assert->fieldValueEquals('root[image][image]', $image_media_token);

    // Confirm the preview image is displaying as expected.
    $this->assertElementExistsAfterWait('css', '#je--root_image_image--preview .js-media-library-item-preview img');

    $button = $assert->waitForButton('Select/Upload video');
    // Scroll the button into view since it may be below the fold.
    $this->scrollOffCanvasElementIntoView('button[title="Select/Upload video"]');
    $button->press();

    // Wait for the library modal.
    $this->assertElementExistsAfterWait('css', '.js-media-library-view');

    $assert->elementTextContains('css', '.js-media-library-view', 'Intensely engaging video');
    $this->selectMediaItem(0);
    $buttons = $this->assertElementExistsAfterWait('css', '#drupal-modal .form-actions');
    $buttons->findButton('Insert selected')->press();

    $assert->waitForElementRemoved('css', '.js-media-library-view');

    $video_uuid = $this->video->uuid();
    $video_media_token = "<drupal-media data-entity-type=\"media\" data-entity-uuid=\"$video_uuid\">";
    $assert->fieldValueEquals('root[video][video]', $video_media_token);

    // Confirm the preview image is displaying as expected.
    $this->assertElementExistsAfterWait('css', '#je--root_video_video--preview .js-media-library-item-preview img');

    // Save the layout to confirm it displays as expected.
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->elementExists('css', '.patternkit-media-image-embed--component img');
    $assert->elementExists('css', '.patternkit-media-video-embed--component img');

    // Get block ID to return to edit form.
    $block_uuid = $this->getLastLayoutBlockUuid(Node::load(1));

    // Confirm all configuration content is persisted when editing the block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $block_uuid);
    $this->waitForJsonEditorForm();
    $assert->fieldValueEquals('root[image][image]', $image_media_token);
    $assert->fieldValueEquals('root[video][video]', $video_media_token);
  }

  /**
   * Test the workflow for adding media from media wrapper library.
   */
  public function testMediaWrapperSelection(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Media embed wrapper';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Press the Add item button.
    $page->pressButton('Add item');
    $page->pressButton('Select/Upload image');

    // Wait for the library modal.
    $this->assertElementExistsAfterWait('css', '.js-media-library-view');

    $assert->elementTextContains('css', '.js-media-library-view', 'Super fancy image');
    $this->selectMediaItem(0);
    $buttons = $this->assertElementExistsAfterWait('css', '#drupal-modal .form-actions');
    $buttons->findButton('Insert selected')->press();

    $assert->waitForElementRemoved('css', '.js-media-library-view');

    // Save the layout to confirm it displays as expected.
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->elementExists('css', '.patternkit-media-image-embed--component img');
  }

  /**
   * Confirm upgrade path for existing blocks with URL values selected.
   */
  public function testUrlSelectionUpgradePath(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Media image embed';
    $field_name = 'root[image]';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Fill the image field with a direct file URL.
    // @phpstan-ignore method.notFound
    $image_url = $this->image->field_media_image->entity->createFileUrl();
    $page->fillField($field_name, $image_url);

    // Save the layout to simulate a pre-existing block with a URL saved.
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    // Get the block ID to return to edit form.
    $block_uuid = $this->getLastLayoutBlockUuid(Node::load(1));

    // Confirm all configuration content is persisted when editing the block.
    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $block_uuid);
    $this->waitForJsonEditorForm();
    $assert->fieldValueEquals($field_name, $image_url);
    $this->assertElementExistsAfterWait('css', '#je--root_image--preview img');

    // Follow through workflow to select an image and embed with media token.
    $assert->waitForButton('Select/Upload image')->press();

    // Wait for the library modal.
    $this->assertElementExistsAfterWait('css', '.js-media-library-view');

    // Select and insert an image from the library.
    $assert->elementTextContains('css', '.js-media-library-view', 'Super fancy image');
    $this->selectMediaItem(0);
    $buttons = $this->assertElementExistsAfterWait('css', '#drupal-modal .form-actions');
    $buttons->findButton('Insert selected')->press();

    $assert->waitForElementRemoved('css', '.js-media-library-view');

    $media_uuid = $this->image->uuid();
    $assert->fieldValueEquals($field_name, "<drupal-media data-entity-type=\"media\" data-entity-uuid=\"$media_uuid\">");

    // Confirm the preview image is displaying as expected.
    $this->assertElementExistsAfterWait('css', '#je--root_image--preview .js-media-library-item-preview img');

    // Save the layout to confirm it displays as expected.
    $page->pressButton('Update');
    $page->pressButton('Save layout');

    // Confirm the pattern is rendered on the Node display.
    $this->drupalGet('node/1');
    $assert->elementExists('css', '.patternkit-media-image-embed--component img');
  }

  /**
   * Test media selection for images without selected image media types.
   */
  public function testImageMediaSelectionWithoutConfiguration(): void {
    $assert = $this->assertSession();

    // Configure without any selected image bundles.
    $this->setMediaMappingConfiguration([]);

    $pattern_name = '[Patternkit] Media image embed';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Wait and confirm the upload button was not added.
    $assert->assertNoElementAfterWait('css', 'div[data-schemapath="root.image"] button');
  }

  /**
   * Test media selection for image urls without selected image media types.
   */
  public function testImageUrlSelectionWithoutConfiguration(): void {
    $assert = $this->assertSession();

    // Configure without any selected image bundles.
    $this->setMediaMappingConfiguration([]);

    $pattern_name = '[Patternkit] Example';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Wait and confirm the upload button was not added.
    $assert->assertNoElementAfterWait('css', 'div[data-schemapath="root.image_url"] button');
  }

  /**
   * Test media selection for video media without selected video media types.
   */
  public function testVideoMediaSelectionWithoutConfiguration(): void {
    $assert = $this->assertSession();

    // Configure without any selected video bundles.
    $this->setMediaMappingConfiguration([], []);

    $pattern_name = '[Patternkit] Media video embed';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Wait and confirm the upload button was not added.
    $assert->assertNoElementAfterWait('css', 'div[data-schemapath="root.video"] button');
  }

  /**
   * Test media selection for combined image/video media.
   */
  public function testMediaCombinedSelectionWithoutConfiguration(): void {
    $assert = $this->assertSession();

    // Configure without any selected image bundles.
    $this->setMediaMappingConfiguration([]);

    $pattern_name = '[Patternkit] Media combined embed';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Wait and confirm the upload image/video button was not added.
    $assert->assertNoElementAfterWait('css', 'div[data-schemapath="root.video.video"] button');
    $assert->assertNoElementAfterWait('css', 'div[data-schemapath="root.image.image"] button');
  }

  /**
   * Test media selection for image media wrapper without selected media types.
   */
  public function testMediaWrapperSelectionWithoutConfiguration(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Configure without any selected image bundles.
    $this->setMediaMappingConfiguration([]);

    $pattern_name = '[Patternkit] Media embed wrapper';

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/1/layout');
    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Press the Add item button.
    $page->pressButton('Add item');

    // Wait and confirm the upload image button was not added.
    $assert->assertNoElementAfterWait('css', 'div[data-schemapath="root.media.0.image"] button');
  }

  /**
   * Set configuration for selectable media types in Patternkit patterns.
   *
   * @param string[] $image_types
   *   (Optional) An array of media bundle keys to save as selectable media
   *   types for image fields.
   * @param string[] $video_types
   *   (Optional) An array of media bundle keys to save as selectable media
   *   types for video fields.
   */
  protected function setMediaMappingConfiguration(array $image_types = [], array $video_types = []): void {
    $config = $this->config(MediaLibrarySettingsForm::SETTINGS);
    $config->set('media_mapping', [
      'image_types' => $image_types,
      'video_types' => $video_types,
    ]);
    $config->save();
  }

}
