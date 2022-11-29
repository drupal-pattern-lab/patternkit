<?php

namespace Drupal\Tests\patternkit_media_library\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\patternkit\FunctionalJavascript\PatternkitBrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * A base class for testing patternkit media library functionality.
 *
 * This class adds a variety of helper methods for interacting with and setting
 * up media. Most of them are copied directly from
 * @link \Drupal\Tests\media_library\FunctionalJavascript\MediaLibraryTestBase @endlink
 * since they can't easily be included via trait or inheritance.
 */
abstract class PatternkitMediaLibraryBrowserTestBase extends PatternkitBrowserTestBase {

  use TestFileCreationTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
    'patternkit_example',
    'patternkit_media_library',
    'media_library',
  ];

  /**
   * The media item to embed.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected MediaInterface $media;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a sample media entity to be embedded.
    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);
    File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ])->save();
    $this->media = Media::create([
      'bundle' => 'image',
      'name' => 'Super fancy image',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $this->media->save();
  }

  /**
   * Waits for the specified selector and returns it if not empty.
   *
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The page element node if found. If not found, the test fails.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   *
   * @see \Drupal\Tests\media_library\FunctionalJavascript\MediaLibraryTestBase::assertElementExistsAfterWait()
   */
  protected function assertElementExistsAfterWait($selector, $locator, $timeout = 10000) {
    $element = $this->assertSession()->waitForElement($selector, $locator, $timeout);
    $this->assertNotEmpty($element);
    return $element;
  }

  /**
   * Gets all available media item checkboxes.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The available checkboxes.
   *
   * @see \Drupal\Tests\media_library\FunctionalJavascript\MediaLibraryTestBase::getCheckboxes()
   */
  protected function getCheckboxes() {
    return $this->getSession()
      ->getPage()
      ->findAll('css', '.js-media-library-view .js-click-to-select-checkbox input');
  }

  /**
   * Selects an item in the media library modal.
   *
   * @param int $index
   *   The zero-based index of the item to select.
   * @param string $expected_selected_count
   *   (optional) The expected text of the selection counter.
   *
   * @see \Drupal\Tests\media_library\FunctionalJavascript\MediaLibraryTestBase::selectMediaItem()
   */
  protected function selectMediaItem($index, $expected_selected_count = NULL) {
    $checkboxes = $this->getCheckboxes();
    $this->assertGreaterThan($index, count($checkboxes));
    $checkboxes[$index]->check();

    if ($expected_selected_count) {
      $this->assertSelectedMediaCount($expected_selected_count);
    }
  }

  /**
   * Asserts the current text of the selected item counter.
   *
   * @param string $text
   *   The expected text of the counter.
   */
  protected function assertSelectedMediaCount($text) {
    $selected_count = $this->assertSession()
      ->elementExists('css', '.js-media-library-selected-count');

    $this->assertSame('status', $selected_count->getAttribute('role'));
    $this->assertSame('polite', $selected_count->getAttribute('aria-live'));
    $this->assertSame('true', $selected_count->getAttribute('aria-atomic'));
    $this->assertSame($text, $selected_count->getText());
  }

}
