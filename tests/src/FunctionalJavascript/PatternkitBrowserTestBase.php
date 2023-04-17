<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Entity\EntityInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\patternkit\Traits\SchemaFixtureTrait;
use Drupal\user\UserInterface;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * End-to-end testing for patternkit block placement in layout builder layouts.
 */
abstract class PatternkitBrowserTestBase extends WebDriverTestBase {

  use SchemaFixtureTrait;
  use JsonDecodeTrait;
  use LayoutEntityHelperTrait;
  use ContextualLinkClickTrait;

  /**
   * Locator for pattern blocks.
   */
  const PATTERN_BLOCK_LOCATOR = '.block-patternkit';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
    'patternkit',
    'layout_builder',
    'node',
    'contextual',
  ];

  /**
   * Storage handler for patternkit_block content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternBlockStorage;

  /**
   * Storage handler for patternkit_pattern content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * An editorial user account for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $editorUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->patternBlockStorage = $entity_type_manager->getStorage('patternkit_block');
    $this->patternStorage = $entity_type_manager->getStorage('patternkit_pattern');

    // Create a bundle type and node we'll enable layout builder for.
    $this->drupalCreateContentType([
      'type' => 'bundle_with_layout_enabled',
      'name' => 'Bundle with layout enabled',
    ]);
    $this->drupalCreateNode([
      'type' => 'bundle_with_layout_enabled',
      'title' => 'Test node title',
      'body' => [
        [
          'value' => 'Test node body.',
        ],
      ],
    ]);

    // Enable layout builder for the test bundle.
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_layout_enabled.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    // Create an editorial user with preconfigured permissions.
    $this->editorUser = $this->drupalCreateUser([
      'access administration pages',
      'access contextual links',
      'configure any layout',
      'create and edit custom blocks',
      'bypass node access',
    ]);
  }

  /**
   * Get the UUID of the last component added to the given entity's layout.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to load the layout for.
   *
   * @return string
   *   The UUID of the most recently added component in the layout.
   */
  protected function getLastLayoutBlockUuid(EntityInterface $entity): string {
    $sections = $this->getEntitySections($entity);
    $components = $sections[0]->getComponents();
    end($components);

    return key($components);
  }

  /**
   * Click through Layout Builder selection to add a new Pattern block.
   *
   * From the layout display page, this method will click to add a new block and
   * select the specified pattern name as the block to be created. Once the
   * block configuration form is visible, that form element will be returned.
   *
   * @param string $pattern_name
   *   The string name of the pattern link to be selected. For example,
   *   "[Patternkit] Example".
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The form element for the block configuration form of the selected
   *   pattern to be added.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function addPatternBlock(string $pattern_name): NodeElement {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $page->clickLink('Add block');

    // Wait for the block list to load before selecting a pattern to insert.
    $link = $assert_session->waitForLink($pattern_name);

    // Wait to confirm the AJAX listener has been attached to the link.
    $link->waitFor(1, fn(NodeElement $link) => $link->getAttribute('data-once') === 'ajax');
    $this->assertEquals('ajax', $link->getAttribute('data-once'));

    // Scroll the link into view to ensure the correct link is clicked.
    $this->scrollOffCanvasElementIntoView("li a:contains($pattern_name)");

    // Click the link to add the block.
    $link->click();
    $assert_session->assertWaitOnAjaxRequest();

    $form = $assert_session->waitForElementVisible('css', 'form.layout-builder-configure-block', 40000);

    // Fail if we still didn't find the loaded block form.
    $this->assertNotNull($form, 'Unable to find block configuration form.');

    return $form;
  }

  /**
   * Wait for the JSON Editor form to load.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element for the JSON Editor form container.
   */
  protected function waitForJsonEditorForm(): NodeElement {
    $assert_session = $this->assertSession();
    $form = $assert_session->waitForElement('css', '.je-ready', 100000);

    try {
      $this->assertNotEmpty($form, 'The JSON Editor form was not completely loaded.');
    }
    catch (ExpectationFailedException $exception) {
      $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
      $caller_method = $backtrace[1]['function'];
      $filename = "fail__$caller_method.jpg";

      // Capture a screenshot to help with debugging.
      $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/' . $filename);

      // Output the HTML after AJAX requests to help with debugging.
      $this->htmlOutput($this->getSession()->getPage()->getHtml());

      throw $exception;
    }

    return $form;
  }

  /**
   * Wait for the field value to change.
   *
   * This may be helpful when a JavaScript event is expected to trigger a change
   * in a field value. Including an original value will ensure a change is
   * properly detected in pre-populated fields.
   *
   * @param string $field_name
   *   The name of the field to watch. Example: 'root[formatted_text]'.
   * @param string $original_value
   *   (Optional) An original value to compare against in order to determine
   *   whether the value changed. If it is not provided, an empty string is
   *   assumed.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The field being watched.
   */
  public function waitForFieldValueChange(string $field_name, string $original_value = ''): NodeElement {
    $page = $this->getSession()->getPage();
    $field = $page->findField($field_name);
    $field->waitFor(5, function (NodeElement $element) use ($original_value) {
      return $element->getValue() !== $original_value;
    });

    return $field;
  }

  /**
   * Scroll an element in the off-canvas display into view.
   *
   * @param string $css_selector
   *   A CSS selector for the element to be scrolled into view.
   */
  public function scrollOffCanvasElementIntoView(string $css_selector): void {
    $page = $this->getSession()->getPage();

    // The off-canvas wrapper doesn't exist in Drupal 9.x, so we need to
    // determine which selector to use for scrolling.
    $wrapper = $page->find('css', '#drupal-off-canvas-wrapper');
    $wrapper_selector = ($wrapper !== NULL) ? '#drupal-off-canvas-wrapper' : '#drupal-off-canvas';

    $function = <<<JS
      (
        function(){
          let elem = jQuery('$css_selector');
          jQuery('$wrapper_selector').animate({scrollTop:elem.offset().top})
        }
      )()
      JS;
    try {
      $this->getSession()->executeScript($function);
    }
    catch (\Exception $e) {
      throw new \Exception("Scroll into view failed: " . $e->getMessage());
    }
  }

  /**
   * Saves a layout and asserts the message is correct.
   */
  protected function assertSaveLayout(): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Reload the page to prevent random failures.
    $this->drupalGet($this->getUrl());
    $page->pressButton('Save layout');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.messages--status'));

    if (stristr($this->getUrl(), 'admin/structure') === FALSE) {
      $assert_session->pageTextContains('The layout override has been saved.');
    }
    else {
      $assert_session->pageTextContains('The layout has been saved.');
    }
  }

  /**
   * Configures a pattern block in the Layout Builder.
   *
   * @param array $old_config
   *   The old configuration values.
   * @param array $new_config
   *   The new configuration values.
   * @param string|null $block_css_locator
   *   The CSS locator to use to select the contextual link.
   */
  protected function configurePatternBlock(array $old_config, array $new_config, ?string $block_css_locator = NULL): void {
    $block_css_locator = $block_css_locator ?: static::PATTERN_BLOCK_LOCATOR;
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->clickContextualLink($block_css_locator, 'Configure');

    // Wait for the first JSON Editor field to be visible.
    $je_form = $this->waitForJsonEditorForm();

    // Fill in JSON Editor fields.
    foreach ($old_config as $key => $old_value) {
      $selector = '[name="root[' . $key . ']"]';
      $field = $je_form->find('css', $selector);
      $this->assertSame($old_value, $field->getValue());
      $field->setValue($new_config[$key] ?? '');
    }

    $page->pressButton('Update');
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();

    // Test for text in the first config value by default.
    $this->assertDialogClosedAndTextVisible(reset($new_config));
  }

  /**
   * Asserts that the dialog closes and the new text appears on the main canvas.
   *
   * @param string $text
   *   The text.
   * @param string|null $css_locator
   *   The css locator to use inside the main canvas if any.
   */
  protected function assertDialogClosedAndTextVisible(string $text, ?string $css_locator = NULL): void {
    $assert_session = $this->assertSession();
    $assert_session->assertNoElementAfterWait('css', '#drupal-off-canvas');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '#drupal-off-canvas');
    if ($css_locator) {
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', ".dialog-off-canvas-main-canvas $css_locator:contains('$text')"));
    }
    else {
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', ".dialog-off-canvas-main-canvas:contains('$text')"));
    }
  }

  /**
   * Wait for all change events to fire and update the hidden config values.
   *
   * @param string $original_value
   *   The original configuration value to compare against and identify when the
   *   content has changed.
   *
   * @return string|false
   *   The changed configuration value or false if no change was detected.
   */
  protected function waitForConfigChange(string $original_value) {
    $config_field = $this->getSession()->getPage()->find('css', '#schema_instance_config');

    $value_changed = $config_field->waitFor(5, function ($element) use ($original_value) {
      return $element->getValue() !== $original_value;
    });

    return $value_changed ? $config_field->getValue() : FALSE;
  }

}
