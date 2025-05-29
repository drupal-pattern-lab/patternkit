<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\patternkit\PatternRepository;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test integrated usage of the PatternRepository service.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\PatternRepository
 * @uses \Drupal\patternkit\Asset\LibraryNamespaceResolver
 * @uses \Drupal\patternkit\Asset\PatternDependencyResolver
 * @uses \Drupal\patternkit\Asset\PatternDiscovery
 * @uses \Drupal\patternkit\Asset\PatternDiscoveryLoader
 * @uses \Drupal\patternkit\Asset\PatternLibraryParserBase
 * @uses \Drupal\patternkit\Asset\PatternLibraryParser\JSONPatternLibraryParser
 * @uses \Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser
 * @uses \Drupal\patternkit\Attribute\PatternLibrary
 * @uses \Drupal\patternkit\Entity\Pattern
 * @uses \Drupal\patternkit\JsonEditorFormBuilder
 * @uses \Drupal\patternkit\PatternLibrary
 * @uses \Drupal\patternkit\PatternLibraryJSONParserTrait
 * @uses \Drupal\patternkit\PatternLibraryPluginDefault
 * @uses \Drupal\patternkit\PatternLibraryPluginManager
 * @uses \Drupal\patternkit\PatternkitEnvironment
 * @uses \Drupal\patternkit\PatternkitEnvironmentAwareTrait
 * @uses \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryJSON
 * @uses \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig
 * @uses \Drupal\patternkit\Schema\ContextBuilder
 * @uses \Drupal\patternkit\Schema\ContextBuilderTrait
 * @uses \Drupal\patternkit\Schema\DataPreProcessorFactory
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor
 * @uses \Drupal\patternkit\Schema\PatternkitRefProvider
 * @uses \Drupal\patternkit\Schema\SchemaRefHandler
 * @uses \Drupal\patternkit\Schema\UnresolvedSchema
 */
class PatternRepositoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'patternkit_example',
    'text',
  ];

  /**
   * The pattern repository service being tested.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $repository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->repository = $this->container->get('patternkit.pattern.repository');
  }

  /**
   * Test bundling pattern schemas with references.
   */
  public function testGetBundledPatternSchema() {
    $pattern_id = '@patternkit/atoms/example_ref/src/example_ref';

    $schema = $this->repository->getBundledPatternSchema($pattern_id);

    $schema_json = json_encode($schema, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    // Confirm the reference to the embedded schema exists.
    $this->assertStringContainsString('#/definitions/patternkit_atoms_example_src_example', $schema_json);

    // Confirm the embedded schema is present.
    $this->assertStringContainsString('"title": "Example"', $schema_json);
  }

  /**
   * Confirm bundling schemas without dependencies leaves the schema unchanged.
   */
  public function testGetBundledPatternSchemaWithoutDependencies() {
    // Identify a pattern without external references that should remain
    // unchanged.
    $pattern_id = '@patternkit/atoms/example/src/example';

    // Get the unbundled schema.
    $pattern = $this->repository->getPattern($pattern_id, FALSE);
    $original_schema = $pattern->getSchema();

    // Get the bundled schema.
    $bundled_schema = $this->repository->getBundledPatternSchema($pattern_id);

    $this->assertJsonStringEqualsJsonString($original_schema, json_encode($bundled_schema));
  }

  /**
   * Test caching behavior for bundling in the getPattern() method.
   */
  public function testGetPatternCaching() {
    $pattern_id = '@patternkit/atoms/example_ref/src/example_ref';
    $original_pattern = $this->repository->getPattern($pattern_id);

    $this->assertFalse($original_pattern->isBundled(), 'The original pattern load was not expected to be bundled.');
    $original_schema = $original_pattern->getSchema();

    $pattern = $this->repository->getPattern($pattern_id, TRUE);
    $this->assertJsonStringNotEqualsJsonString($original_schema, $pattern->getSchema(), 'The bundled schema was expected to be different from the original schema.');
    $this->assertTrue($pattern->isBundled(), 'The bundled schema was expected to be flagged properly.');
    $this->assertSame($original_pattern, $pattern, 'Expected the same pattern object to be returned after bundling.');

    // Fetching without a bundling flag should serve the cached pattern that has
    // already been bundled.
    $pattern = $this->repository->getPattern($pattern_id);
    $this->assertTrue($pattern->isBundled());
    $this->assertSame($original_pattern, $pattern, 'Expected the same pattern object to be returned after bundling.');

    // Fetching with bundling disabled should create a new pattern instance.
    $pattern = $this->repository->getPattern($pattern_id, FALSE);
    $this->assertFalse($pattern->isBundled());
    $this->assertJsonStringEqualsJsonString($original_schema, $pattern->getSchema(), 'Expected the unbundled pattern to have the original schema.');
    $this->assertNotSame($original_pattern, $pattern, 'Expected the unbundled pattern to be a newly created Pattern instance.');
  }

}
