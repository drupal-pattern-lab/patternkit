<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\PatternDependencyResolver;

/**
 * Test discovery of dependencies using the PatternDependencyResolver.
 *
 * @group patternkit
 * @covers \Drupal\patternkit\Schema\ContextBuilder
 * @covers \Drupal\patternkit\Schema\DataPreProcessorFactory
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternDependencyResolver
 * @covers \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver
 * @covers \Drupal\patternkit\Schema\UnresolvedSchema
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor
 * @uses \Drupal\patternkit\Schema\ContextBuilder
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection
 * @uses \Drupal\patternkit\Asset\LibraryNamespaceResolver
 * @uses \Drupal\patternkit\Asset\PatternDiscovery
 * @uses \Drupal\patternkit\Asset\PatternDiscoveryLoader
 * @uses \Drupal\patternkit\PatternLibraryPluginManager
 * @uses \Drupal\patternkit\PatternkitEnvironment
 * @uses \Drupal\patternkit\Schema\PatternkitRefProvider
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor
 * @uses \Drupal\patternkit\Schema\SchemaRefHandler
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor
 */
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
  public function testGetAllPatternDependencies(): void {
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
  public function testGetPatternLoadOrder(): void {
    $order = $this->patternDependencyResolver->getPatternLoadOrder();

    $exampleIndex = array_search('@patternkit/atoms/example/src/example', $order);
    $refIndex = array_search('@patternkit/atoms/example_ref/src/example_ref', $order);

    $this->assertGreaterThan($exampleIndex, $refIndex);
  }

  /**
   * Test getting schema dependencies.
   *
   * @dataProvider providerGetSchemaDependencies
   */
  public function testGetSchemaDependencies(string $schema, string $pattern_id, array $expected_dependencies) {
    $dependencies = $this->patternDependencyResolver->getSchemaDependencies($schema, $pattern_id);
    $this->assertEquals($expected_dependencies, $dependencies);
  }

  /**
   * Data provider for testGetSchemaDependencies().
   *
   * @return array
   *   An array of test cases.
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
