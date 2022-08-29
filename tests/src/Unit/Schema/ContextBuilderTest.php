<?php

namespace Drupal\Tests\patternkit\Unit\Schema;

use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\Tests\UnitTestCase;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\RemoteRefProvider;
use Webmozart\Assert\InvalidArgumentException;

/**
 * Unit tests for the ContextBuilder class.
 *
 * @coversDefaultClass \Drupal\patternkit\Schema\ContextBuilder
 * @covers ::__construct
 * @uses \Swaggest\JsonSchema\Context
 * @group patternkit
 */
class ContextBuilderTest extends UnitTestCase {

  /**
   * The context builder service being tested.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder
   */
  protected ContextBuilder $contextBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->contextBuilder = new ContextBuilder();
  }

  /**
   * @covers ::getDefaultOptions
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setDefaultOptions()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::validateOptions()
   */
  public function testGetDefaultOptions() {
    $options = $this->contextBuilder->getDefaultOptions();
    $this->assertIsArray($options);

    $defaults = [
      'applyDefaults' => TRUE,
      'tolerateStrings' => TRUE,
    ];
    $this->contextBuilder->setDefaultOptions($defaults);
    $this->assertEquals($defaults, $this->contextBuilder->getDefaultOptions());
  }

  /**
   * @covers ::setDefaultOptions
   * @covers ::validateOptions
   * @uses \Drupal\patternkit\Schema\ContextBuilder::getDefaultOptions()
   */
  public function testSetDefaultOptions() {
    $defaults = [
      'applyDefaults' => TRUE,
      'tolerateStrings' => TRUE,
    ];
    $this->contextBuilder->setDefaultOptions($defaults);
    $this->assertEquals($defaults, $this->contextBuilder->getDefaultOptions());
  }

  /**
   * @covers ::setDefaultOption
   * @covers ::validateOptions
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setDefaultOptions()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::getDefaultOptions()
   */
  public function testSetDefaultOption() {
    // Initialize the default options to a known value.
    $default_options = [
      'applyDefaults' => TRUE,
      'tolerateStrings' => TRUE,
      'skipValidation' => FALSE,
    ];
    $this->contextBuilder->setDefaultOptions($default_options);
    $this->assertEquals($default_options, $this->contextBuilder->getDefaultOptions());

    // Toggle existing options.
    $this->contextBuilder->setDefaultOption('tolerateStrings', FALSE);
    $this->contextBuilder->setDefaultOption('skipValidation', TRUE);

    // Add a new option.
    $this->contextBuilder->setDefaultOption('strictBase64Validation', TRUE);

    // Confirm the results.
    $this->assertEquals([
      'applyDefaults' => TRUE,
      'tolerateStrings' => FALSE,
      'skipValidation' => TRUE,
      'strictBase64Validation' => TRUE,
    ], $this->contextBuilder->getDefaultOptions());
  }

  /**
   * @covers ::getDefaultContext
   * @covers ::configureContextDefaults
   * @covers ::applyOptions
   * @covers ::validateOptions
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setDefaultOptions()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::applyOptions()
   */
  public function testGetDefaultContext() {
    // Initialize the default options to a known value.
    $default_options = [
      'applyDefaults' => TRUE,
      'tolerateStrings' => TRUE,
      'skipValidation' => FALSE,
    ];
    $this->contextBuilder->setDefaultOptions($default_options);

    $context = $this->contextBuilder->getDefaultContext();
    $this->assertInstanceOf(Context::class, $context);

    $this->assertContextConfiguration($default_options, $context);
  }

  /**
   * @covers ::setRemoteRefProvider
   * @uses \Drupal\patternkit\Schema\ContextBuilder::getDefaultContext()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::configureContextDefaults()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::applyOptions()
   */
  public function testSetRemoteRefProvider() {
    $refProvider = $this->prophesize(RemoteRefProvider::class)->reveal();
    $this->contextBuilder->setRemoteRefProvider($refProvider);

    $context = $this->contextBuilder->getDefaultContext();
    $this->assertSame($refProvider, $context->getRemoteRefProvider());
  }

  /**
   * @covers ::setDataPreProcessor
   * @uses \Drupal\patternkit\Schema\ContextBuilder::getDefaultContext()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::configureContextDefaults()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::applyOptions()
   */
  public function testSetDataPreProcessor() {
    $dataPreProcessor = $this->prophesize(DataPreProcessor::class)->reveal();
    $this->contextBuilder->setDataPreProcessor($dataPreProcessor);

    $context = $this->contextBuilder->getDefaultContext();
    $this->assertSame($dataPreProcessor, $context->getDataPreProcessor());
  }

  /**
   * @covers ::configureContextDefaults
   * @covers ::applyOptions
   * @covers ::validateOptions
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setDefaultOptions()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setRemoteRefProvider()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setDataPreProcessor()
   */
  public function testConfigureContextDefaults() {
    // Initialize the default options to a known value.
    $default_options = [
      'applyDefaults' => TRUE,
      'tolerateStrings' => TRUE,
      'skipValidation' => FALSE,
    ];
    $this->contextBuilder->setDefaultOptions($default_options);

    $refProvider = $this->prophesize(RemoteRefProvider::class)->reveal();
    $this->contextBuilder->setRemoteRefProvider($refProvider);

    $dataPreProcessor = $this->prophesize(DataPreProcessor::class)->reveal();
    $this->contextBuilder->setDataPreProcessor($dataPreProcessor);

    // Confirm default options are configured.
    $this->assertContextConfiguration(
      $default_options + [
        'refProvider' => $refProvider,
        'dataPreProcessor' => $dataPreProcessor,
      ],
      $this->contextBuilder->configureContextDefaults(new Context())
    );
  }

  /**
   * @covers ::getConfiguredContext
   * @covers ::applyOptions
   * @covers ::validateOptions
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setDefaultOptions()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setRemoteRefProvider()
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setDataPreProcessor()   */
  public function testGetConfiguredContext() {
    $refProvider = $this->prophesize(RemoteRefProvider::class)->reveal();
    $dataPreProcessor = $this->prophesize(DataPreProcessor::class)->reveal();

    // Initialize the default options to a known value.
    $default_options = [
      'applyDefaults' => TRUE,
      'tolerateStrings' => TRUE,
      'skipValidation' => FALSE,
      'refProvider' => $refProvider,
      'dataPreProcessor' => $dataPreProcessor,
    ];
    $this->contextBuilder->setDefaultOptions($default_options);

    // Test without special options set.
    $this->assertContextConfiguration($default_options, $this->contextBuilder->getConfiguredContext([]));

    $alternativeDataPreProcessor = $this->prophesize(DataPreProcessor::class)->reveal();

    $overrides = [
      'tolerateStrings' => FALSE,
      'refProvider' => NULL,
      'dataPreProcessor' => $alternativeDataPreProcessor,
    ];

    // Test with overrides provided.
    $this->assertContextConfiguration($overrides + $default_options,
      $this->contextBuilder->getConfiguredContext($overrides));
  }

  /**
   * Test option validation with various option values.
   *
   * @covers ::validateOptions
   * @uses \Drupal\patternkit\Schema\ContextBuilder::setDefaultOptions()
   *
   * @dataProvider providerOptionValidation
   */
  public function testOptionValidation(array $options, string $expected_exception_message) {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage($expected_exception_message);
    $this->contextBuilder->setDefaultOptions($options);
  }

  /**
   * Data provider for testOptionValidation().
   */
  public function providerOptionValidation() {
    $cases = [];

    $cases['invalid ref provider'] = [
      ['refProvider' => (object) []],
      'Expected an instance of Swaggest\JsonSchema\RemoteRefProvider. Got: stdClass',
    ];

    $cases['invalid data preprocessor'] = [
      ['dataPreProcessor' => (object) []],
      'Expected an instance of Swaggest\JsonSchema\DataPreProcessor. Got: stdClass',
    ];

    return $cases;
  }

  /**
   * Validate assigned configuration on a provided Context instance.
   *
   * @param array $expected
   *   An associative array of configuration options to validate.
   * @param \Swaggest\JsonSchema\Context $context
   *   The context object to validate.
   */
  protected function assertContextConfiguration(array $expected, Context $context): void {
    // Identify options that need to specially handled for validation.
    $special_options = ['refProvider', 'dataPreProcessor'];

    foreach ($expected as $option => $value) {
      // Skip special options for later validation.
      if (in_array($option, $special_options)) {
        continue;
      }

      $this->assertEquals($value, $context->$option);
    }

    // Confirm the assigned reference provider if an option is included.
    if (array_key_exists('refProvider', $expected)) {
      $this->assertEquals($expected['refProvider'], $context->getRemoteRefProvider());
    }

    // Confirm the assigned data preprocessor if an option is included.
    if (array_key_exists('dataPreProcessor', $expected)) {
      $this->assertEquals($expected['dataPreProcessor'], $context->getDataPreProcessor());
    }
  }

}
