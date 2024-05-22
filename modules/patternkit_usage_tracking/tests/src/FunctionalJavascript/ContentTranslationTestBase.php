<?php

namespace Drupal\Tests\patternkit_usage_tracking\FunctionalJavascript;

use Drupal\Tests\patternkit\FunctionalJavascript\PatternkitBrowserTestBase;
use Drupal\user\UserInterface;

/**
 * A base class for Content Translation browser test.
 */
abstract class ContentTranslationTestBase extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'views',
  ];

  /**
   * An translator user account for translating any node content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $translatorUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a bundle type and node we'll enable layout builder for.
    $this->drupalCreateNode([
      'type' => 'bundle_with_layout_enabled',
      'title' => 'Test node title (English)',
      'body' => [
        [
          'value' => 'Test node body(English)',
        ],
      ],
    ]);

    // Enable content translation.
    $content_translation_manager = $this->container->get('content_translation.manager');
    $content_translation_manager->setEnabled('node', 'bundle_with_layout_enabled', TRUE);
    $this->rebuildContainer();

    // Create a translator user.
    $permissions = [
      'translate any entity',
    ];
    $this->translatorUser = $this->drupalCreateUser($permissions);

    // Create an editorial user with preconfigured permissions.
    $this->editorUser = $this->drupalCreateUser([
      'configure any layout',
      'access site reports',
      'administer languages',
    ]);

    $this->drupalLogin($this->editorUser);

    // Configure an additional language.
    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/config/regional/language/add');
    $page->selectFieldOption('edit-predefined-langcode', 'French');
    $page->pressButton('Add language');
    $page->pressButton('Save configuration');
  }

}
