<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\patternkit\Asset\LibraryNamespaceResolver;
use Drupal\patternkit\Asset\PatternDependencyResolver;
use Drupal\patternkit\Asset\PatternDiscovery;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Asset\PatternDiscoveryLoader;
use Drupal\patternkit\Asset\PatternLibraryParser\FilePatternLibraryParser;
use Drupal\patternkit\Asset\PatternLibraryParserBase;
use Drupal\patternkit\Attribute\PatternLibrary as AttributePatternLibrary;
use Drupal\patternkit\Entity\Pattern as EntityPattern;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryFile;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection;
use Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver;
use Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\patternkit\Schema\PatternkitRefProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Test pattern discovery with plugin fallback behavior.
 */
#[Group('patternkit')]
#[CoversClass(LibraryNamespaceResolver::class)]
#[CoversClass(PatternDiscovery::class)]
#[CoversClass(PatternDiscoveryLoader::class)]
#[CoversClass(PatternLibraryPluginManager::class)]
#[UsesClass(AttributePatternLibrary::class)]
#[UsesClass(BundleReferenceDataPreProcessor::class)]
#[UsesClass(ContextBuilder::class)]
#[UsesClass(DataPreProcessorCollection::class)]
#[UsesClass(DataPreProcessorFactory::class)]
#[UsesClass(EntityPattern::class)]
#[UsesClass(FilePatternLibraryParser::class)]
#[UsesClass(PatternDependencyResolver::class)]
#[UsesClass(PatternLibrary::class)]
#[UsesClass(PatternLibraryFile::class)]
#[UsesClass(PatternLibraryParserBase::class)]
#[UsesClass(PatternLibraryPluginDefault::class)]
#[UsesClass(PatternkitEnvironment::class)]
#[UsesClass(PatternkitRefProvider::class)]
#[UsesClass(RefNormalizerDataPreProcessor::class)]
#[UsesClass(SchemaRefObserver::class)]
#[UsesClass(TokenValidationDataPreProcessor::class)]
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
