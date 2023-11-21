<?php

namespace Drupal\Tests\patternkit\Unit\Asset\PatternLibraryParser;

use Drupal\patternkit\Asset\PatternLibraryParserBase;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\Tests\patternkit_example\Traits\PatternkitExampleLibraryAwareTrait;
use org\bovigo\vfs\vfsStream;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests for the PatternLibraryParserBase class.
 *
 * @coversDefaultClass \Drupal\patternkit\Asset\PatternLibraryParserBase
 * @uses \Drupal\Tests\patternkit_example\Traits\PatternkitExampleLibraryAwareTrait
 * @group patternkit
 */
class PatternLibraryParserBaseTest extends PatternLibraryParserTestBase {

  use ProphecyTrait;
  use PatternkitExampleLibraryAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function getTestClass(): string {
    return PatternLibraryParserBase::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getParserInstance(): PatternLibraryParserInterface {
    $this->entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    return $this->getMockForAbstractClass(PatternLibraryParserBase::class, [
      $this->serializer->reveal(),
      $this->vfsRoot->url(),
      $this->moduleHandler->reveal(),
      $this->themeManager->reveal(),
      $this->streamWrapperManager->reveal(),
      $this->librariesDirectoryFileFinder->reveal(),
      $this->extensionPathResolver->reveal(),
      $this->entityTypeManager->reveal(),
      $this->dataPreProcessorFactory->reveal(),
      $this->patternDependencyResolver->reveal(),
      $this->logger->reveal(),
    ]);
  }

  /**
   * @covers ::discoverComponents
   */
  public function testDiscoverComponents() {
    $path = $this->vfsRoot->url();
    $filter = ['twig', 'json'];
    $expected_results = $this->getExpectedFiles($path, $filter);

    vfsStream::copyFromFileSystem(__DIR__ . '/../../../../modules/patternkit_test/lib/patternkit/src', $this->vfsRoot);

    $results = PatternLibraryParserBase::discoverComponents($path, $filter);

    $this->assertEqualsCanonicalizing($expected_results, $results);
  }

  /**
   * @covers ::fetchPatternAssets
   * @covers ::__construct
   */
  public function testFetchPatternAssets() {
    $json_content = '{ "content": "UNPARSED JSON" }';
    $twig_content = 'This is my {{ twig }} content.';

    vfsStream::newFile('my_asset_file.json')
      ->withContent($json_content)
      ->at($this->vfsRoot);
    vfsStream::newFile('my_asset_file.twig')
      ->withContent($twig_content)
      ->at($this->vfsRoot);

    $pattern_prophecy = $this->prophesize(PatternInterface::class);
    $pattern_prophecy->getAssetId()->willReturn('@test/my/asset');
    $pattern_prophecy->getAssets()->willReturn(
      [
        'twig' => 'my_asset_file.twig',
        'json' => 'my_asset_file.json',
      ]
    );

    $parser = $this->getParserInstance();
    $this->assertEquals([
      'twig' => $twig_content,
      'json' => $json_content,
    ], $parser->fetchPatternAssets($pattern_prophecy->reveal()));
  }

}
