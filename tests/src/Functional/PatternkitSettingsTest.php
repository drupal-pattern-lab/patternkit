<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * E2E testing for the Patternkit settings forms.
 *
 * @coversDefaultClass \Drupal\patternkit\Form\PatternkitSettingsForm
 * @group patternkit
 * @group legacy
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
    // @todo Remove this once the referenced deprecation is resolved.
    // @see https://www.drupal.org/node/3000490
    // @see https://www.drupal.org/project/patternkit/issues/3295521
    $this->expectDeprecation('ModuleHandlerInterface::implementsHook() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Instead you should use ModuleHandlerInterface::hasImplementations()  with the $modules argument. See https://www.drupal.org/node/3000490');

    $account = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/user-interface/patternkit');

    $assert = $this->assertSession();

    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Unable to load Patternkit libraries list. Check the logs for more information.');
    $assert->pageTextNotContains('Settings are unavailable when Pattern libraries fail to load to prevent config errors.');
  }

}
