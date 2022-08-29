<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\patternkit\Exception\MissingDataPreProcessorException;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\Tests\UnitTestCase;
use Swaggest\JsonSchema\DataPreProcessor;

/**
 * Unit tests for the DataPreProcessorFactory class.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\DataPreProcessorFactory
 * @group patternkit
 */
class DataPreProcessorFactoryTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessorFactory
   */
  protected DataPreProcessorFactory $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->factory = new DataPreProcessorFactory();
  }

  /**
   * @covers ::addPreProcessor
   * @covers ::getPreProcessor
   */
  public function testAddPreProcessor() {
    $preprocessor = $this->createMock(DataPreProcessor::class);

    $this->factory->addPreProcessor($preprocessor, 'test');
    $this->assertEquals($preprocessor, $this->factory->getPreProcessor('test'));
  }

  /**
   * @covers ::getPreProcessor
   * @covers ::addPreProcessor
   */
  public function testGetPreProcessor() {
    $preprocessor = $this->createMock(DataPreProcessor::class);

    $this->factory->addPreProcessor($preprocessor, 'test');
    $this->assertEquals($preprocessor, $this->factory->getPreProcessor('test'));
    $this->assertNotSame($preprocessor, $this->factory->getPreProcessor('test'), 'The returned preprocessor was expected to not be the same original instance.');

    // Test requesting a missing preprocessor.
    $this->expectException(MissingDataPreProcessorException::class);
    $this->expectExceptionMessage('Unknown DataPreProcessor "unknown"');
    $this->factory->getPreProcessor('unknown');
  }

}
