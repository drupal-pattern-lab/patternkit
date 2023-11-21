<?php

namespace Drupal\Tests\patternkit\Unit\Asset\PatternLibraryParser;

use Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\MissingSchemaException;
use Drupal\patternkit\Exception\MissingTemplateException;
use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor;
use Drupal\patternkit\Schema\UnresolvedSchema;
use Drupal\Tests\patternkit_example\Traits\PatternkitExampleLibraryAwareTrait;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;
use Prophecy\Argument\Token\TokenInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\InvalidValue;

/**
 * Tests for the TwigPatternLibraryParser class.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser
 * @covers \Drupal\patternkit\Asset\PatternLibraryParser\JSONPatternLibraryParser
 * @uses \Drupal\patternkit\PatternLibraryJSONParserTrait::setContextBuilder()
 * @uses \Drupal\patternkit\PatternLibraryJSONParserTrait::contextBuilder()
 * @uses \Drupal\Tests\patternkit_example\Traits\PatternkitExampleLibraryAwareTrait
 * @uses \Drupal\patternkit\PatternkitEnvironmentAwareTrait
 * @group patternkit
 */
class TwigPatternLibraryParserTest extends PatternLibraryParserTestBase {

  use ProphecyTrait;
  use PatternkitExampleLibraryAwareTrait;

  /**
   * The context builder service.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $contextBuilder;

  /**
   * {@inheritdoc}
   */
  protected function getTestClass(): string {
    return TwigPatternLibraryParser::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpParserDependencies(): void {
    parent::setUpParserDependencies();

    $this->contextBuilder = $this->prophesize(ContextBuilder::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function getParserInstance(): PatternLibraryParserInterface {
    $parser = parent::getParserInstance();

    $parser->setContextBuilder($this->contextBuilder->reveal());

    return $parser;
  }

  /**
   * @covers ::createPattern
   * @uses \Drupal\patternkit\Asset\PatternLibraryParserBase::__construct
   * @dataProvider providerCreatePattern
   */
  public function testCreatePattern(string $name, array $data, TokenInterface $argument_expectations) {
    // Mock the pattern to be created and returned.
    $pattern = $this->prophesize(PatternInterface::class);

    $this->patternStorage->create($argument_expectations)
      ->willReturn($pattern->reveal())
      ->shouldBeCalledOnce();

    $parser = $this->getParserInstance();
    $parser->createPattern($name, $data);
  }

  /**
   * Data provider for testCreatePattern().
   */
  public function providerCreatePattern() {
    $cases = [];

    $cases['no title'] = [
      'my pattern name',
      [],
      Argument::withEntry('name', 'my pattern name'),
    ];

    $cases['with title'] = [
      'my pattern name',
      ['title' => 'some other title'],
      Argument::withEntry('name', 'some other title'),
    ];

    $cases['more data'] = [
      'my pattern name',
      [
        'other data' => 'other values',
      ],
      Argument::exact([
        'name' => 'my pattern name',
        'other data' => 'other values',
      ]),
    ];

    return $cases;
  }

  /**
   * @covers ::parsePatternLibraryInfo
   * @covers ::<protected>
   * @uses \Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser::createPattern()
   * @uses \Drupal\patternkit\Asset\PatternLibraryParserBase
   */
  public function testParsePatternLibraryInfo() {
    $lib = vfsStream::newDirectory('lib');
    $this->vfsRoot->addChild($lib);
    vfsStream::copyFromFileSystem(__DIR__ . '/../../../../modules/patternkit_test/lib/patternkit/src', $lib);

    $lib_path = 'lib';
    $library = [
      'id' => 'patternkit',
      'version' => NULL,
      'pattern_info' => [
        // This is keyed by the file path to the pattern library directory.
        $lib_path => [],
      ],
    ];

    $pattern_library = $this->prophesize(PatternLibrary::class);
    $pattern_library->id()->willReturn($library['id']);
    $pattern_library->getPatternInfo()->willReturn($library['pattern_info']);

    // Prepare a mock to be loaded for each pattern.
    foreach ($this->getPatternPaths() as $pattern_path) {
      $this->createMockPattern($pattern_path);
    }

    $parser = $this->getParserInstance();
    $results = $parser->parsePatternLibraryInfo($pattern_library->reveal(), $lib_path);

    // Define a set of keys to be tested. All other keys included in the
    // metadata results for each pattern will be ignored.
    $tested_keys = [
      'name',
      'category',
      'title',
      'assets',
      'library',
      'libraryPluginId',
      'path',
      'version',
    ];

    // Define the expected metadata results for each discovered pattern.
    $expected = $this->getExpectedLibraryInformation([], 'lib/');

    $this->assertLibraryInfo($expected, $results, $tested_keys);
  }

  /**
   * @covers ::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::__construct
   * @uses \Drupal\patternkit\PatternLibraryJSONParserTrait
   * @uses \Drupal\patternkit\Schema\UnresolvedSchema
   * @uses \Swaggest\JsonSchema\Context
   */
  public function testFetchPatternAssets() {
    $json = '{"content":"MY JSON"}';
    $twig_content = 'This is my {{ twig }} content.';
    $dependencies = ['@my_pattern_dependency'];
    $pattern_id = '@my_library/my_asset_file';

    vfsStream::newFile('my_asset_file.json')
      ->withContent($json)
      ->at($this->vfsRoot);
    vfsStream::newFile('my_asset_file.twig')
      ->withContent($twig_content)
      ->at($this->vfsRoot);

    // Prepare the unresolved schema returned while normalizing the schema.
    $unresolved_schema = $this->prophesize(UnresolvedSchema::class);
    $unresolved_schema->jsonSerialize()
      ->willReturn($this->decodeJson($json));

    // Mock dependencies for schema normalization.
    $normalizer = $this->prophesize(RefNormalizerDataPreProcessor::class);
    $normalizer->process(Argument::cetera())
      ->willReturnArgument(0)
      ->shouldBeCalled();
    $this->dataPreProcessorFactory->getPreProcessor('normalizer')
      ->willReturn($normalizer->reveal())
      ->shouldBeCalledOnce();
    $this->contextBuilder->getConfiguredContext(Argument::type('array'))
      ->will(function () use ($normalizer) {
        $context = new Context();
        $context->setDataPreProcessor($normalizer->reveal());

        return $context;
      })
      ->shouldBeCalledOnce();

    // Mock the pattern instance to be passed into the method.
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getAssets()->willReturn(
      [
        'twig' => 'my_asset_file.twig',
        'json' => 'my_asset_file.json',
      ]
    );
    $pattern->getAssetId()->willReturn($pattern_id);
    $pattern->setDependencies($dependencies)
      ->willReturn($pattern->reveal())
      ->shouldBeCalled();

    // Mock the dependency resolver to be used.
    $this->patternDependencyResolver
      ->getSchemaDependencies($json, $pattern_id)
      ->willReturn($dependencies);

    $parser = $this->getParserInstance();
    $this->assertEquals([
      'twig' => $twig_content,
      'json' => $json,
      'template' => $twig_content,
      'schema' => $json,
    ], $parser->fetchPatternAssets($pattern->reveal()));
  }

  /**
   * @covers ::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::__construct
   */
  public function testFetchPatternAssetsWithMissingSchema() {
    $twig_content = 'This is my {{ twig }} content.';
    $pattern_id = '@my_library/my_asset_file';

    vfsStream::newFile('my_asset_file.twig')
      ->withContent($twig_content)
      ->at($this->vfsRoot);

    // Mock the pattern instance to be passed into the method.
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getAssets()->willReturn(
      [
        'twig' => 'my_asset_file.twig',
      ]
    );
    $pattern->getAssetId()->willReturn($pattern_id);

    // Expect an exception caused by the missing schema file.
    $this->expectException(MissingSchemaException::class);
    $this->expectExceptionMessage('Unable to find a suitable schema for pattern "@my_library/my_asset_file"');

    $this->getParserInstance()->fetchPatternAssets($pattern->reveal());
  }

  /**
   * @covers ::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::__construct
   */
  public function testFetchPatternAssetsWithMissingTemplate() {
    $schema_content = '{"my": "schema"}';
    $pattern_id = '@my_library/my_asset_file';

    vfsStream::newFile('my_asset_file.json')
      ->withContent($schema_content)
      ->at($this->vfsRoot);

    // Mock the pattern instance to be passed into the method.
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getAssets()->willReturn(
      [
        'json' => 'my_asset_file.json',
      ]
    );
    $pattern->getAssetId()->willReturn($pattern_id);

    // Expect an exception caused by the missing template file.
    $this->expectException(MissingTemplateException::class);
    $this->expectExceptionMessage('Unable to find a suitable Twig template for pattern "@my_library/my_asset_file"');

    // Mock schema processing to ignore plugin loading.
    $mock = $this->getPartialMock()
      ->onlyMethods([
        'processSchema',
      ])
      ->getMock();
    $mock->expects($this->once())
      ->method('processSchema')
      ->with($schema_content, $pattern->reveal())
      ->willReturnArgument(0);

    $mock->fetchPatternAssets($pattern->reveal());
  }

  /**
   * @covers ::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::__construct
   * @covers \Drupal\patternkit\PatternLibraryJSONParserTrait::isJson
   */
  public function testFetchPatternAssetsWithNonJsonSchema() {
    $twig_content = 'This is my {{ twig }} content.';
    $schema_content = 'Not JSON content.';
    $pattern_id = '@my_library/my_asset_file';

    vfsStream::newFile('my_asset_file.twig')
      ->withContent($twig_content)
      ->at($this->vfsRoot);
    vfsStream::newFile('my_asset_file.json')
      ->withContent($schema_content)
      ->at($this->vfsRoot);

    // Mock the pattern instance to be passed into the method.
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getAssets()->willReturn(
      [
        'twig' => 'my_asset_file.twig',
        'json' => 'my_asset_file.json',
      ]
    );
    $pattern->getAssetId()->willReturn($pattern_id);

    // Expect an exception caused by the missing schema file.
    $this->expectException(MissingSchemaException::class);
    $this->expectExceptionMessage('Unable to find a suitable schema for pattern "@my_library/my_asset_file"');

    $this->getParserInstance()->fetchPatternAssets($pattern->reveal());
  }

  /**
   * @covers ::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::fetchPatternAssets
   * @covers \Drupal\patternkit\Asset\PatternLibraryParserBase::__construct
   * @covers \Drupal\patternkit\PatternLibraryJSONParserTrait::isJson
   * @covers \Drupal\patternkit\PatternLibraryJSONParserTrait::processSchema
   */
  public function testFetchPatternAssetsWithInvalidJsonSchema() {
    $twig_content = 'This is my {{ twig }} content.';
    $schema_content = '{"my": "schema"}';
    $pattern_id = '@my_library/my_asset_file';

    vfsStream::newFile('my_asset_file.twig')
      ->withContent($twig_content)
      ->at($this->vfsRoot);
    vfsStream::newFile('my_asset_file.json')
      ->withContent($schema_content)
      ->at($this->vfsRoot);

    // Mock the pattern instance to be passed into the method.
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getAssets()->willReturn(
      [
        'twig' => 'my_asset_file.twig',
        'json' => 'my_asset_file.json',
      ]
    );
    $pattern->getAssetId()->willReturn($pattern_id);

    // Expect an exception caused by the missing template file.
    $this->expectException(SchemaException::class);
    $this->expectExceptionMessage('Unable to consume the schema for pattern "@my_library/my_asset_file"');

    $mock = $this->getPartialMock()
      ->onlyMethods([
        'normalizeSchemaReferences',
        'getSchemaDependencies',
      ])
      ->getMock();

    // Expect specific processing steps to be triggered, and fake an exception
    // during processing.
    $mock->expects($this->once())
      ->method('normalizeSchemaReferences')
      ->with($schema_content)
      ->willReturnArgument(0);
    $mock->expects($this->once())
      ->method('getSchemaDependencies')
      ->with($schema_content, $pattern_id)
      ->willThrowException(new InvalidValue());

    $mock->fetchPatternAssets($pattern->reveal());
  }

}
