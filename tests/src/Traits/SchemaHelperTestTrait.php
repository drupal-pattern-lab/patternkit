<?php

namespace Drupal\Tests\patternkit\Traits;

use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\Tests\patternkit\Unit\Schema\TestPatternkitRefProvider;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\RemoteRefProvider;

/**
 * A helper trait to assist unit tests with dependencies on SchemaHelper.
 *
 * This trait is only intended for use in tests. Implementation of the
 * tearDown() method assumes the implementing class inherits from
 * \PHPUnit\Framework\TestCase for a parent tearDown() implementation to extend.
 *
 * @see \Drupal\patternkit\Schema\SchemaHelper
 */
trait SchemaHelperTestTrait {

  use ProphecyTrait;

  /**
   * A prophecy mock for the schema factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $schemaFactory;

  /**
   * A reference provider for loading schemas from.
   *
   * @var \Drupal\Tests\patternkit\Unit\Schema\TestPatternkitRefProvider
   */
  protected TestPatternkitRefProvider $refProvider;

  /**
   * A data preprocessor to configure in schema contexts.
   *
   * @var \Swaggest\JsonSchema\DataPreProcessor
   */
  protected DataPreProcessor $dataPreProcessor;

  /**
   * A default schema context to use for schema operations.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $context;

  /**
   * Prepares a mocked schema factory service.
   *
   * Prepares a mocked schema factory service with configuration to return a
   * configured schema context from the getDefaultContext() method. If a
   * container is provided as an argument, the mock will be registered as the
   * 'patternkit.schema.schema_factory' service as well.
   *
   * Note: If the container is provided to this method, the mock wil be
   * instantiated and registered, so further manipulation of it in the class
   * variable will not be reflected in the registered service. If further
   * customization and mocking of the service is needed, it is recommended to
   * not provide the container as an argument and register it separately.
   *
   * @param \Psr\Container\ContainerInterface|null $container
   *   (Optional) A container to register the mocked service to.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The configured prophecy for the schema factory service.
   */
  protected function setUpSchemaFactory(?ContainerInterface $container = NULL): ObjectProphecy {
    $this->schemaFactory = $this->prophesize(SchemaFactory::class);
    $this->schemaFactory->getDefaultContext()->willReturn($this->getSchemaContext());

    if (isset($container)) {
      $container->set('patternkit.schema.schema_factory', $this->schemaFactory->reveal());
    }

    return $this->schemaFactory;
  }

  /**
   * Get the default remote reference provider for schema contexts.
   *
   * @return \Swaggest\JsonSchema\RemoteRefProvider
   *   A remote reference provider for schema contexts.
   */
  protected function refProvider(): RemoteRefProvider {
    if (!isset($this->refProvider)) {
      $this->refProvider = new TestPatternkitRefProvider();
    }

    return $this->refProvider;
  }

  /**
   * Get the default data preprocessor for schema contexts.
   *
   * @return \Swaggest\JsonSchema\DataPreProcessor
   *   A date preprocessor to use in schema contexts.
   */
  protected function dataPreProcessor(): DataPreProcessor {
    if (!isset($this->dataPreProcessor)) {
      $this->dataPreProcessor = new ObjectCoercionDataPreProcessor();
    }

    return $this->dataPreProcessor;
  }

  /**
   * Get a configured schema context for schema operations.
   *
   * @return \Swaggest\JsonSchema\Context
   *   A configured schema context for schema operations.
   */
  protected function getSchemaContext(): Context {
    if (!isset($this->context)) {
      $this->context = new Context($this->refProvider());
      $this->context->setDataPreProcessor($this->dataPreProcessor());
    }

    // Clone the context to avoid persisting state of schema operations.
    return clone($this->context);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    parent::tearDown();
    $this->tearDownProphecy();
  }

}
