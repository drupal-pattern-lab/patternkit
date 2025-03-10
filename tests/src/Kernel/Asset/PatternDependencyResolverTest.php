<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\LibraryNamespaceResolver;
use Drupal\patternkit\Asset\PatternDependencyResolver;
use Drupal\patternkit\Asset\PatternDiscovery;
use Drupal\patternkit\Asset\PatternDiscoveryLoader;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection;
use Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver;
use Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\patternkit\Schema\PatternkitRefProvider;
use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\patternkit\Schema\UnresolvedSchema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Test discovery of dependencies using the PatternDependencyResolver.
 */
#[Group('patternkit')]
#[CoversClass(ContextBuilder::class)]
#[CoversClass(DataPreProcessorFactory::class)]
#[CoversClass(PatternDependencyResolver::class)]
#[CoversClass(SchemaRefObserver::class)]
#[CoversClass(UnresolvedSchema::class)]
#[UsesClass(BundleReferenceDataPreProcessor::class)]
#[UsesClass(ContextBuilder::class)]
#[UsesClass(DataPreProcessorCollection::class)]
#[UsesClass(LibraryNamespaceResolver::class)]
#[UsesClass(PatternDiscovery::class)]
#[UsesClass(PatternDiscoveryLoader::class)]
#[UsesClass(PatternLibraryPluginManager::class)]
#[UsesClass(PatternkitEnvironment::class)]
#[UsesClass(PatternkitRefProvider::class)]
#[UsesClass(RefNormalizerDataPreProcessor::class)]
#[UsesClass(SchemaRefHandler::class)]
#[UsesClass(TokenValidationDataPreProcessor::class)]
class PatternDependencyResolverTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'patternkit_example',
    'text',
  ];

  /**
   * The pattern dependency resolver service being tested.
   *
   * @var \Drupal\patternkit\Asset\PatternDependencyResolver
   */
  protected PatternDependencyResolver $patternDependencyResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->patternDependencyResolver = $this->container->get('patternkit.pattern.dependency_resolver');
  }

  /**
   * Test identification of pattern dependencies.
   */
  public function testGetAllPatternDependencies() {
    $dependencies = $this->patternDependencyResolver->getAllPatternDependencies();

    $this->assertIsArray($dependencies);

    // Confirm a pattern with no dependencies.
    $this->assertArrayHasKey('@patternkit/atoms/example/src/example', $dependencies);
    $this->assertEquals([], $dependencies['@patternkit/atoms/example/src/example']);

    // Confirm a pattern with dependencies.
    $this->assertArrayHasKey('@patternkit/atoms/example_ref/src/example_ref', $dependencies);
    $this->assertEquals(
      ['@patternkit/atoms/example/src/example'],
      $dependencies['@patternkit/atoms/example_ref/src/example_ref']
    );
  }

  /**
   * Confirm patterns with dependencies are listed later.
   */
  public function testGetPatternLoadOrder() {
    $order = $this->patternDependencyResolver->getPatternLoadOrder();

    $exampleIndex = array_search('@patternkit/atoms/example/src/example', $order);
    $refIndex = array_search('@patternkit/atoms/example_ref/src/example_ref', $order);

    $this->assertGreaterThan($exampleIndex, $refIndex);
  }

  #[DataProvider('providerGetSchemaDependencies')]
  public function testGetSchemaDependencies(string $schema, string $pattern_id, array $expected_dependencies) {
    $dependencies = $this->patternDependencyResolver->getSchemaDependencies($schema, $pattern_id);
    $this->assertEquals($expected_dependencies, $dependencies);
  }

  /**
   * Data provider for testGetSchemaDependencies().
   */
  public static function providerGetSchemaDependencies() {
    $cases = [];

    $schema_with_reference = <<<JSON
      {
        "type": "object",
        "properties": {
          "reference": {
            "\$ref": "@my/pattern/dependency"
          },
          "internal_reference": {
            "\$ref": "#/properties/reference"
          }
        }
      }
      JSON;
    $cases['schema with references'] = [
      $schema_with_reference,
      '@my/pattern',
      ['@my/pattern/dependency'],
    ];

    $schema_with_self_reference = <<<JSON
      {
        "type": "object",
        "properties": {
          "reference": {
            "\$ref": "@my/pattern#/properties/text"
          },
          "text": {
            "type": "string"
          }
        }
      }
      JSON;
    $cases['schema with self reference'] = [
      $schema_with_self_reference,
      '@my/pattern',
      [],
    ];

    return $cases;
  }

}
