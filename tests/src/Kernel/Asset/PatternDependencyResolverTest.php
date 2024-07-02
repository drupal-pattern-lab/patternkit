<?php

namespace Drupal\Tests\patternkit\Kernel\Asset;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\PatternDependencyResolver;

/**
 * Test discovery of dependencies using the PatternDependencyResolver.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternDependencyResolver
 * @group patternkit
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
   *
   * @covers ::getAllPatternDependencies
   */
  public function testGetAllPatternDependencies() {
    $dependencies = $this->patternDependencyResolver->getAllPatternDependencies();

    $this->assertIsArray($dependencies);

    // Confirm a pattern with no dependencies.
    $this->assertArrayHasKey('@patternkit/atoms/example/src/example', $dependencies);
    $this->assertEquals([], $dependencies['@patternkit/atoms/example/src/example']);

    // Confirm a pattern with dependencies.
    $this->assertArrayHasKey('@patternkit/atoms/example_ref/src/example_ref', $dependencies);
    $this->assertEquals([
      '@patternkit/atoms/example/src/example',
    ], $dependencies['@patternkit/atoms/example_ref/src/example_ref']);
  }

  /**
   * Confirm patterns with dependencies are listed later.
   *
   * @covers ::getAllPatternDependencies
   */
  public function testGetPatternLoadOrder() {
    $order = $this->patternDependencyResolver->getPatternLoadOrder();

    $exampleIndex = array_search('@patternkit/atoms/example/src/example', $order);
    $refIndex = array_search('@patternkit/atoms/example_ref/src/example_ref', $order);

    $this->assertGreaterThan($exampleIndex, $refIndex);
  }

  /**
   * @covers ::getSchemaDependencies
   * @uses \Drupal\patternkit\Schema\UnresolvedSchema
   * @uses \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver
   *
   * @dataProvider providerGetSchemaDependencies
   */
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
