<?php

namespace Drupal\Tests\patternkit\Unit\Schema\DataPreProcessor;

use Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor;
use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Swaggest\JsonSchema\Schema;

/**
 * Unit tests for the BundleReferenceDataPreProcessor.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor
 * @group patternkit
 */
class BundleReferenceDataPreProcessorTest extends UnitTestCase {

  use JsonDecodeTrait;
  use ProphecyTrait;

  /**
   * The BundleReferenceDataPreProcessor instance being tested.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor
   */
  protected BundleReferenceDataPreProcessor $dataPreProcessor;

  /**
   * The mocked SchemaRefHandler service.
   *
   * @var \Drupal\patternkit\Schema\SchemaRefHandler|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $schemaRefHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->schemaRefHandler = $this->prophesize(SchemaRefHandler::class);
  }

  /**
   * @covers ::process
   * @covers ::setBundleMap
   * @covers ::__construct
   *
   * @dataProvider providerProcess
   */
  public function testProcess($data, array $map, $expected) {
    $schema = $this->createMock(Schema::class);

    $this->schemaRefHandler->parseNormalizedSchemaReference('/api/patternkit/my/pattern/name?asset=schema')
      ->willReturn([
        'asset_id' => '@my/pattern/name',
        'pointer' => '',
      ]);
    $this->schemaRefHandler->parseNormalizedSchemaReference('/api/patternkit/my/pattern/name?asset=schema#/my/pointer')
      ->willReturn([
        'asset_id' => '@my/pattern/name',
        'pointer' => '/my/pointer',
      ]);

    $this->dataPreProcessor = new BundleReferenceDataPreProcessor($this->schemaRefHandler->reveal());
    $this->dataPreProcessor->setBundleMap($map);

    $this->assertEquals($expected, $this->dataPreProcessor->process($data, $schema));
  }

  /**
   * Data provider for testProcess().
   */
  public function providerProcess(): array {
    $cases = [];

    $cases['object reference'] = [
      (object) [
        '$ref' => '/api/patternkit/my/pattern/name?asset=schema',
      ],
      [
        '@my/pattern/name' => '#/definitions/my_pattern_name',
      ],
      (object) [
        '$ref' => '#/definitions/my_pattern_name',
      ],
    ];

    $cases['array reference'] = [
      [
        '$ref' => '/api/patternkit/my/pattern/name?asset=schema',
      ],
      [
        '@my/pattern/name' => '#/definitions/my_pattern_name',
      ],
      [
        '$ref' => '#/definitions/my_pattern_name',
      ],
    ];

    $cases['no reference'] = [
      (object) [
        'text property' => 'my text',
      ],
      [],
      (object) [
        'text property' => 'my text',
      ],
    ];

    $cases['reference with pointer'] = [
      (object) [
        '$ref' => '/api/patternkit/my/pattern/name?asset=schema#/my/pointer',
      ],
      [
        '@my/pattern/name' => '#/definitions/my_pattern_name',
      ],
      (object) [
        '$ref' => '#/definitions/my_pattern_name/my/pointer',
      ],
    ];

    return $cases;
  }

}
