<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\patternkit\Asset\PatternDiscoveryInterface;

/**
 * Test pattern discovery with plugin fallback behavior.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Asset\LibraryNamespaceResolver
 * @covers \Drupal\patternkit\Asset\PatternDiscovery
 * @covers \Drupal\patternkit\Asset\PatternDiscoveryLoader
 * @covers \Drupal\patternkit\PatternLibraryPluginManager
 * @uses \Drupal\patternkit\Attribute\PatternLibrary
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor
 * @uses \Drupal\patternkit\Schema\ContextBuilder
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection
 * @uses \Drupal\patternkit\Schema\DataPreProcessorFactory
 * @uses \Drupal\patternkit\Entity\Pattern
 * @uses \Drupal\patternkit\Asset\PatternLibraryParser\FilePatternLibraryParser
 * @uses \Drupal\patternkit\Asset\PatternDependencyResolver
 * @uses \Drupal\patternkit\PatternLibrary
 * @uses \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryFile
 * @uses \Drupal\patternkit\Asset\PatternLibraryParserBase
 * @uses \Drupal\patternkit\PatternLibraryPluginDefault
 * @uses \Drupal\patternkit\PatternkitEnvironment
 * @uses \Drupal\patternkit\Schema\PatternkitRefProvider
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor
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
