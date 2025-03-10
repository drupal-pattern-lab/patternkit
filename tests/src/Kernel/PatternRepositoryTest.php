<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\patternkit\PatternRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Test integrated usage of the PatternRepository service.
 */
#[Group('patternkit')]
#[UsesClass('\Drupal\patternkit\Asset\LibraryNamespaceResolver')]
#[UsesClass('\Drupal\patternkit\Asset\PatternDependencyResolver')]
#[UsesClass('\Drupal\patternkit\Asset\PatternDiscovery')]
#[UsesClass('\Drupal\patternkit\Asset\PatternDiscoveryLoader')]
#[UsesClass('\Drupal\patternkit\Asset\PatternLibraryParserBase')]
#[UsesClass('\Drupal\patternkit\Asset\PatternLibraryParser\JSONPatternLibraryParser')]
#[UsesClass('\Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser')]
#[UsesClass('\Drupal\patternkit\Attribute\PatternLibrary')]
#[UsesClass('\Drupal\patternkit\Entity\Pattern')]
#[UsesClass('\Drupal\patternkit\JsonEditorFormBuilder')]
#[UsesClass('\Drupal\patternkit\PatternLibrary')]
#[UsesClass('\Drupal\patternkit\PatternLibraryJSONParserTrait')]
#[UsesClass('\Drupal\patternkit\PatternLibraryPluginDefault')]
#[UsesClass('\Drupal\patternkit\PatternLibraryPluginManager')]
#[UsesClass('\Drupal\patternkit\PatternkitEnvironment')]
#[UsesClass('\Drupal\patternkit\PatternkitEnvironmentAwareTrait')]
#[UsesClass('\Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryJSON')]
#[UsesClass('\Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig')]
#[UsesClass('\Drupal\patternkit\Schema\ContextBuilder')]
#[UsesClass('\Drupal\patternkit\Schema\ContextBuilderTrait')]
#[UsesClass('\Drupal\patternkit\Schema\DataPreProcessorFactory')]
#[UsesClass('\Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor')]
#[UsesClass('\Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection')]
#[UsesClass('\Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor')]
#[UsesClass('\Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver')]
#[UsesClass('\Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor')]
#[UsesClass('\Drupal\patternkit\Schema\PatternkitRefProvider')]
#[UsesClass('\Drupal\patternkit\Schema\SchemaRefHandler')]
#[UsesClass('\Drupal\patternkit\Schema\UnresolvedSchema')]
#[CoversClass(PatternRepository::class)]
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
  protected $repository;

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
