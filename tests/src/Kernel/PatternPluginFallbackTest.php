<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\patternkit\Asset\PatternDiscoveryInterface;

/**
 * Test pattern discovery with plugin fallback behavior.
 *
 * @covers \Drupal\patternkit\Asset\PatternDiscovery
 * @covers \Drupal\patternkit\Asset\PatternDiscoveryLoader
 * @covers \Drupal\patternkit\Asset\LibraryNamespaceResolver
 * @covers \Drupal\patternkit\PatternLibraryPluginManager
 * @group patternkit
 */
class PatternPluginFallbackTest extends PatternkitKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_plugin_test',
  ];

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $discovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->discovery = $this->container->get('patternkit.pattern.discovery');
  }

  /**
   * Test bundling pattern schemas with references.
   *
   * If the plugin fallback behavior for `file.svg` fails, an exception is
   * thrown instead of returning an array of definitions.
   */
  public function testDiscoverFallbackPlugin() {
    $definition = $this->discovery->getPatternDefinitions();

    $this->assertIsArray($definition);
  }

}
