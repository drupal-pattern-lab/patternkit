<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\patternkit\Asset\PatternDependencyResolver;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\RemoteRef\Preloaded;

/**
 * Unit tests for the PatternRepository class.
 *
 * @coversDefaultClass \Drupal\patternkit\PatternRepository
 * @uses \Drupal\patternkit\Schema\ContextBuilderTrait
 * @group patternkit
 */
class PatternRepositoryTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $discovery;

  /**
   * The pattern dependency resolver service.
   *
   * @var \Drupal\patternkit\Asset\PatternDependencyResolver|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $dependencyResolver;

  /**
   * The schema data preprocessor factory service.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessorFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $dataPreProcessorFactory;

  /**
   * The entity storage handler for the patternkit_pattern entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternStorage;

  /**
   * The schema context builder service.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $contextBuilder;

  /**
   * The pattern repository service being tested.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->discovery = $this->prophesize(PatternDiscoveryInterface::class);
    $this->dependencyResolver = $this->prophesize(PatternDependencyResolver::class);
    $this->dataPreProcessorFactory = $this->prophesize(DataPreProcessorFactory::class);
    $this->patternStorage = $this->prophesize(EntityStorageInterface::class);
    $this->contextBuilder = $this->prophesize(ContextBuilder::class);
  }

  /**
   * A utility function to get the pattern repository with injected mocks.
   *
   * This is a utility function to help defer resolution of mocks to allow
   * individual tests to configure them before instantiation.
   *
   * @return \Drupal\patternkit\PatternRepository
   *   The pattern repository instance for testing with all configured mocks
   *   injected.
   */
  protected function getPatternRepository(): PatternRepository {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    $repository = new PatternRepository(
      $this->discovery->reveal(),
      $this->dependencyResolver->reveal(),
      $this->dataPreProcessorFactory->reveal(),
      $entityTypeManager->reveal(),
    );

    $repository->setContextBuilder($this->contextBuilder->reveal());

    return $repository;
  }

  /**
   * @covers ::getPattern
   * @covers ::patternStorage
   * @covers ::__construct
   */
  public function testGetPattern() {
    $pattern_id = '@my/pattern';
    $pattern_definition = [
      'id' => $pattern_id,
    ];
    $pattern = $this->prophesize(PatternInterface::class);

    $this->discovery->getPatternDefinition($pattern_id)
      ->willReturn($pattern_definition);

    $this->patternStorage->create($pattern_definition)
      ->willReturn($pattern->reveal());

    $this->assertSame($pattern->reveal(), $this->getPatternRepository()->getPattern($pattern_id));
  }

  /**
   * Test static caching behavior for the getPattern method.
   *
   * @covers ::getPattern
   * @covers ::patternStorage
   * @covers ::__construct
   */
  public function testGetPatternCaching() {
    $pattern_id = '@my/pattern';
    $pattern_definition = [
      'id' => $pattern_id,
    ];
    $pattern = $this->prophesize(PatternInterface::class);

    $this->discovery->getPatternDefinition($pattern_id)
      ->willReturn($pattern_definition)
      ->shouldBeCalledOnce();

    $this->patternStorage->create($pattern_definition)
      ->willReturn($pattern->reveal());

    $repository = $this->getPatternRepository();

    // The first request should build and cache the pattern.
    $result = $repository->getPattern($pattern_id);
    $this->assertSame($pattern->reveal(), $result);

    // A second request should load the same cached object.
    $this->assertSame($result, $repository->getPattern($pattern_id));
  }

  /**
   * Test getting pattern definitions with bundling.
   *
   * @covers ::getPattern
   * @covers ::bundleSchema
   * @covers ::patternStorage
   * @covers ::__construct
   */
  public function testGetPatternWithBundling() {
    $pattern_id = '@my/pattern';
    $pattern_definition = [
      'id' => $pattern_id,
      'schema' => '{ "my pattern": "schema" }',
    ];
    $bundled_schema = json_decode('{ "my pattern": "bundled schema" }');

    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->isBundled()->willReturn(FALSE);
    $pattern->getAssetId()->willReturn($pattern_id);
    $pattern->setSchema($bundled_schema)->shouldBeCalledOnce();
    $pattern->setBundled()->shouldBeCalledOnce();

    $this->discovery->getPatternDefinition($pattern_id)
      ->willReturn($pattern_definition)
      ->shouldBeCalledOnce();

    $this->patternStorage->create($pattern_definition)
      ->willReturn($pattern->reveal());

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    $repository = $this->getMockBuilder(PatternRepository::class)
      ->onlyMethods(['getBundledPatternSchema'])
      ->setConstructorArgs([
        $this->discovery->reveal(),
        $this->dependencyResolver->reveal(),
        $this->dataPreProcessorFactory->reveal(),
        $entityTypeManager->reveal(),
      ])
      ->getMock();

    $repository->expects($this->once())
      ->method('getBundledPatternSchema')
      ->with($pattern_id)
      ->willReturn($bundled_schema);

    $result = $repository->getPattern($pattern_id, TRUE);
    $this->assertSame($pattern->reveal(), $result);
  }

  /**
   * @covers ::getPattern
   * @covers ::patternStorage
   * @covers ::__construct
   */
  public function testGetUnknownPattern() {
    $this->expectException(UnknownPatternException::class);
    $this->expectExceptionMessage('No known pattern definition for "@unknown/pattern".');
    $this->getPatternRepository()->getPattern('@unknown/pattern');
  }

  /**
   * @covers ::getAllPatternNames
   * @covers ::__construct
   */
  public function testGetAllPatternNames() {
    $pattern_definitions = [
      '@patternkit' => [
        '@patternkit/example' => ['stuff'],
        '@patternkit/example2' => ['stuff'],
        '@patternkit/example3' => ['stuff'],
      ],
      '@another_library' => [
        '@another_library/pattern1' => ['stuff'],
        '@another_library/pattern2' => ['stuff'],
        '@another_library/pattern3' => ['stuff'],
      ],
    ];
    $this->discovery->getPatternDefinitions()
      ->willReturn($pattern_definitions)
      ->shouldBeCalledOnce();

    $expected_results = [
      '@patternkit/example',
      '@patternkit/example2',
      '@patternkit/example3',
      '@another_library/pattern1',
      '@another_library/pattern2',
      '@another_library/pattern3',
    ];
    $this->assertEquals($expected_results, $this->getPatternRepository()->getAllPatternNames());
  }

  /**
   * @covers ::getBundledPatternSchema
   * @covers ::getPatternStack
   * @covers ::getPattern
   * @covers ::patternStorage
   * @covers ::__construct
   * @uses \Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor
   * @uses \Drupal\patternkit\Schema\UnresolvedSchema
   * @uses \Swaggest\JsonSchema\RemoteRef\Preloaded
   */
  public function testGetBundledPatternSchema() {
    $pattern_id = "@my/parent/pattern";
    $pattern_dependencies = [
      '@my/child/pattern',
      '@my/parent/pattern',
    ];
    $pattern_definitions = [
      '@my/child/pattern' => [
        'id' => '@my/child/pattern',
      ],
      '@my/parent/pattern' => [
        'id' => '@my/parent/pattern',
      ],
    ];
    $parent_schema = <<<JSON
      {
        "type": "object",
        "properties": {
          "reference": {
            "\$ref": "@my/child/pattern"
          }
        }
      }
      JSON;
    $child_schema = <<<JSON
      {
        "type": "string"
      }
      JSON;

    $this->dependencyResolver->getOrderedDependencies($pattern_id)
      ->willReturn($pattern_dependencies);

    $parent_pattern = $this->prophesize(PatternInterface::class);
    $parent_pattern->getSchema()->willReturn($parent_schema);
    $child_pattern = $this->prophesize(PatternInterface::class);
    $child_pattern->getSchema()->willReturn($child_schema);

    // Mock pattern creation inside ::getPattern().
    $this->discovery->getPatternDefinition('@my/parent/pattern')
      ->willReturn($pattern_definitions['@my/parent/pattern']);
    $this->discovery->getPatternDefinition('@my/child/pattern')
      ->willReturn($pattern_definitions['@my/child/pattern']);
    $this->patternStorage->create(Argument::withEntry('id', '@my/parent/pattern'))
      ->willReturn($parent_pattern->reveal());
    $this->patternStorage->create(Argument::withEntry('id', '@my/child/pattern'))
      ->willReturn($child_pattern->reveal());

    // Prepare schema handler for usage inside the data preprocessor.
    $schemaRefHandler = $this->prophesize(SchemaRefHandler::class);
    $schemaRefHandler->parseNormalizedSchemaReference('@my/child/pattern')
      ->willReturn([
        'asset_id' => '@my/child/pattern',
        'pointer' => '',
      ]);

    // Mock preprocessor preparation.
    $this->dataPreProcessorFactory->getPreProcessor('bundler')
      ->willReturn(new BundleReferenceDataPreProcessor($schemaRefHandler->reveal()));

    $this->contextBuilder->getConfiguredContext(Argument::withKey('dataPreProcessor'))
      ->will(function ($args) use ($child_schema) {
        // Preload references for schema import.
        $refProvider = new Preloaded();
        $refProvider->setSchemaData('@my/child/pattern', json_decode($child_schema));

        $context = new Context($refProvider);
        $context->setDataPreProcessor($args[0]['dataPreProcessor']);
        $context->dereference = $args[0]['dereference'];

        return $context;
      });

    $schema = $this->getPatternRepository()->getBundledPatternSchema($pattern_id);
    $schema_json = json_encode($schema, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    $this->assertStringContainsString('#/definitions/my_child_pattern', $schema_json);
    $this->assertStringContainsString('"type": "string"', $schema_json);
  }

}
