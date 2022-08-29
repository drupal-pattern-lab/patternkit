<?php

namespace Drupal\Tests\patternkit\Kernel\Schema;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver;

/**
 * Integration tests to confirm registration of data preprocessors.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\DataPreProcessorFactory
 * @group patternkit
 */
class DataPreProcessorFactoryIntegrationTest extends KernelTestBase {

  /**
   * An array of known data preprocessors to be tested for.
   *
   * @var array
   */
  protected array $knownPreProcessors = [
    'objects' => ObjectCoercionDataPreProcessor::class,
    'bundler' => BundleReferenceDataPreProcessor::class,
    'normalizer' => RefNormalizerDataPreProcessor::class,
    'ref_observer' => SchemaRefObserver::class,
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
  ];

  /**
   * Confirm discovery of known data preprocessors.
   *
   * @covers ::addPreProcessor
   * @covers ::getPreProcessor
   */
  public function testDiscoveredPreProcessors() {
    $factory = $this->container->get('patternkit.schema.data_preprocessor.factory');

    foreach ($this->knownPreProcessors as $alias => $class) {
      $this->assertInstanceOf($class, $factory->getPreProcessor($alias));
    }
  }

}
