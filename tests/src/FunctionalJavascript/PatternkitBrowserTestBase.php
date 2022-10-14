<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Drupal\Core\Entity\EntityInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\patternkit\Traits\SchemaFixtureTrait;
use Drupal\user\UserInterface;

/**
 * End-to-end testing for patternkit block placement in layout builder layouts.
 */
abstract class PatternkitBrowserTestBase extends WebDriverTestBase {

  use SchemaFixtureTrait;
  use JsonDecodeTrait;
  use LayoutEntityHelperTrait;

  /**
   * {@inheritdoc}
   */
  static protected $modules = [
    'patternkit',
    'layout_builder',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable9';

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

}
