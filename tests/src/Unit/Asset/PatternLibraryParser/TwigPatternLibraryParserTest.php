<?php

namespace Drupal\Tests\patternkit\Unit\Asset\PatternLibraryParser;

use Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\MissingSchemaException;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor;
use Drupal\patternkit\Schema\UnresolvedSchema;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;
use Prophecy\Argument\Token\TokenInterface;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Context;

/**
 * Tests for the TwigPatternLibraryParser class.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser
 * @uses \Drupal\patternkit\PatternLibraryJSONParserTrait::setContextBuilder()
 * @uses \Drupal\patternkit\PatternLibraryJSONParserTrait::contextBuilder()
 * @group patternkit
 */
class TwigPatternLibraryParserTest extends PatternLibraryParserTestBase {

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
   * @uses \Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser::createPattern()
   * @uses \Drupal\patternkit\Asset\PatternLibraryParserBase
   */
  public function testParsePatternLibraryInfo() {
    vfsStream::copyFromFileSystem(__DIR__ . '/../../../../modules/patternkit_test/lib/patternkit/src', $this->vfsRoot);

    $path = $this->vfsRoot->url();
    $library = [
      'id' => 'patternkit',
      'version' => NULL,
      'pattern_info' => [
        "{$this->vfsRoot->url()}" => [],
      ],
    ];

    $pattern_library = $this->prophesize(PatternLibrary::class);
    $pattern_library->id()->willReturn($library['id']);
    $pattern_library->getPatternInfo()->willReturn($library['pattern_info']);

    $this->createMockPattern('atoms/example/src/example');
    $this->createMockPattern('atoms/example_filtered/src/example_filtered');
    $this->createMockPattern('atoms/example_ref/src/example_ref');
    $this->createMockPattern('atoms/example_filtered_multiple/src/example_filtered_multiple');

    $parser = $this->getParserInstance();
    $results = $parser->parsePatternLibraryInfo($pattern_library->reveal(), $path);

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

    // Define default values expected in each result if not overridden for a
    // specific pattern.
    $defaults = [
      'category' => 'atom',
      'library' => 'patternkit',
      'libraryPluginId' => 'twig',
      'version' => FALSE,
    ];

    // Define the expected metadata results for each discovered pattern.
    $expected = [
      'atoms/example/src/example' => [
        'name' => 'Example',
        'title' => 'Example',
        'assets' => [
          'twig' => $this->vfsRoot->url() . '/atoms/example/src/example.twig',
          'json' => $this->vfsRoot->url() . '/atoms/example/src/example.json',
        ],
        'path' => 'atoms/example/src/example',
      ] + $defaults,
      'atoms/example_filtered/src/example_filtered' => [
        'name' => 'Example with filtered content',
        'title' => 'Example with filtered content',
        'assets' => [
          'twig' => $this->vfsRoot->url() . '/atoms/example_filtered/src/example_filtered.twig',
          'json' => $this->vfsRoot->url() . '/atoms/example_filtered/src/example_filtered.json',
        ],
        'path' => 'atoms/example_filtered/src/example_filtered',
      ] + $defaults,
      'atoms/example_ref/src/example_ref' => [
        'name' => 'Example with Reference',
        'title' => 'Example with Reference',
        'assets' => [
          'twig' => $this->vfsRoot->url() . '/atoms/example_ref/src/example_ref.twig',
          'json' => $this->vfsRoot->url() . '/atoms/example_ref/src/example_ref.json',
        ],
        'path' => 'atoms/example_ref/src/example_ref',
      ] + $defaults,
      'atoms/example_filtered_multiple/src/example_filtered_multiple' => [
        'name' => 'Example with multiple filtered content blocks',
        'title' => 'Example with multiple filtered content blocks',
        'assets' => [
          'twig' => $this->vfsRoot->url() . '/atoms/example_filtered_multiple/src/example_filtered_multiple.twig',
          'json' => $this->vfsRoot->url() . '/atoms/example_filtered_multiple/src/example_filtered_multiple.json',
        ],
        'path' => 'atoms/example_filtered_multiple/src/example_filtered_multiple',
      ] + $defaults,
    ];

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
        'twig' => $this->vfsRoot->url() . '/my_asset_file.twig',
        'json' => $this->vfsRoot->url() . '/my_asset_file.json',
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
  public function testFetchPatternAssetsWithMissingAssets() {
    $twig_content = 'This is my {{ twig }} content.';
    $pattern_id = '@my_library/my_asset_file';

    vfsStream::newFile('my_asset_file.twig')
      ->withContent($twig_content)
      ->at($this->vfsRoot);

    // Mock the pattern instance to be passed into the method.
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getAssets()->willReturn(
      [
        'twig' => $this->vfsRoot->url() . '/my_asset_file.twig',
      ]
    );
    $pattern->getAssetId()->willReturn($pattern_id);

    // Expect an exception caused by the missing schema file.
    $this->expectException(MissingSchemaException::class);
    $this->expectExceptionMessage('Unable to find a suitable schema for pattern "@my_library/my_asset_file"');

    $this->getParserInstance()->fetchPatternAssets($pattern->reveal());
  }

}
