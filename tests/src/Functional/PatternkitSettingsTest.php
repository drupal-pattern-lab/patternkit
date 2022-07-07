<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * E2E testing for the Patternkit settings forms.
 *
 * @coversDefaultClass \Drupal\patternkit\Form\PatternkitSettingsForm
 * @group patternkit
 */
class PatternkitSettingsTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  static protected $modules = [
    'patternkit',
    'patternkit_example',
  ];

  /**
   * Default Drupal theme for running tests.
   *
   * @var string
   */
  protected $defaultTheme = 'stable';

  /**
   * Storage handler for patternkit_block content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $patternBlockStorage;

  /**
   * Storage handler for patternkit_pattern content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $patternStorage;

  /**
   * The patternkit library service.
   *
   * @var \Drupal\patternkit\Asset\Library
   */
  protected $library;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->patternBlockStorage = $entity_type_manager->getStorage('patternkit_block');
    $this->patternStorage = $entity_type_manager->getStorage('patternkit_pattern');
    $this->library = $this->container->get('patternkit.asset.library');
  }

  /**
   * Test that the settings form loads as expected.
   */
  public function testPatternkitSettingsForm() {
    $account = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/user-interface/patternkit');

    $assert = $this->assertSession();

    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Unable to load Patternkit libraries list. Check the logs for more information.');
    $assert->pageTextNotContains('Settings are unavailable when Pattern libraries fail to load to prevent config errors.');
  }

}
