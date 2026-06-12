<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\patternkit\Traits\PatternkitBlockHelperTrait;
use Drupal\Tests\patternkit\Traits\SchemaFixtureTrait;
use Drupal\user\UserInterface;

/**
 * A base class for all patternkit browser tests.
 */
abstract class PatternkitBrowserTestBase extends BrowserTestBase {

  use PatternkitBlockHelperTrait;
  use SchemaFixtureTrait;
  use JsonDecodeTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'layout_builder',
    'node',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Storage handler for patternkit_block content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternBlockStorage;

  /**
   * Storage handler for patternkit_pattern content.
   *
   * @var \Drupal\Core\Entity\RevisionableStorageInterface
   */
  protected RevisionableStorageInterface $patternStorage;

  /**
   * A test user with editor permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $editorUser;

  /**
   * Place a pattern block via the Layout Builder UI and assert success.
   *
   * Covers the five-step placement sequence used by Functional tests:
   * open the block picker, select the pattern, inject schema config via the
   * hidden field (JSON Editor is not rendered without JS), submit the block
   * form, and save the layout.
   *
   * A linkExists() assertion is made after opening the block picker, before
   * clicking the pattern link. This is a diagnostic assertion not present at
   * every original call site; it is applied uniformly here to surface block
   * discovery failures (pattern not registered, module not enabled) with a
   * clear message rather than a generic click failure.
   *
   * An intermediate check after "Add block" verifies that the block config
   * form was accepted before the layout save is attempted, catching silent
   * server-side validation failures at the correct call site rather than as a
   * confusing "Save layout" not-found error. When validation fails the error
   * element text is included in the failure message for immediate diagnosis.
   *
   * @param string $pattern_name
   *   The display name of the pattern as it appears in the block picker link,
   *   e.g. '[Patternkit] Example'.
   * @param array $content
   *   The pattern configuration to inject as JSON into #schema_instance_config.
   */
  protected function placePatternBlockViaUi(string $pattern_name, array $content): void {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $page->clickLink('Add block');
    $assert->linkExists($pattern_name);
    $page->clickLink($pattern_name);
    $page->find('css', '#schema_instance_config')->setValue(json_encode($content));
    $page->pressButton('Add block');
    // If the block config form was rejected by server-side validation, Drupal
    // re-renders it with an error message instead of returning to the layout.
    // Fail here with the validation error text rather than two steps later as
    // a confusing "Save layout" button-not-found error.
    $error = $page->find('css', '[role="alert"]');
    if ($error !== NULL) {
      $this->fail('Block config form rejected by server-side validation: ' . $error->getText());
    }
    $page->pressButton('Save layout');
  }

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->patternBlockStorage = $entity_type_manager->getStorage('patternkit_block');
    // @phpstan-ignore assign.propertyType
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
      'configure any layout',
    ]);
  }

}
