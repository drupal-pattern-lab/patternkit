<?php

namespace Drupal\Tests\patternkit\Kernel\Schema;

use Drupal\patternkit\Schema\DataPreProcessorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver;
use PHPUnit\Framework\Attributes\Group;

/**
 * Integration tests to confirm registration of data preprocessors.
 */
#[Group('patternkit')]
#[CoversClass(DataPreProcessorFactory::class)]
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
   */
  public function testDiscoveredPreProcessors() {
    $factory = $this->container->get('patternkit.schema.data_preprocessor.factory');

    foreach ($this->knownPreProcessors as $alias => $class) {
      $this->assertInstanceOf($class, $factory->getPreProcessor($alias));
    }
  }

}
