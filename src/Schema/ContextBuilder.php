<?php

namespace Drupal\patternkit\Schema;

use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\RemoteRefProvider;
use Webmozart\Assert\Assert;

/**
 * A service for building consistent contexts for schema operations.
 *
 * @see \Swaggest\JsonSchema\Context
 */
class ContextBuilder {

  /**
   * A configured default context to build from.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $context;

  /**
   * A default remote reference provider to be used.
   *
   * @var \Swaggest\JsonSchema\RemoteRefProvider|null
   */
  protected ?RemoteRefProvider $refProvider = NULL;

  /**
   * A data preprocessor to configure on all schema contexts.
   *
   * A default collection of data preprocessors for usage are assembled using
   * the 'patternkit.schema.data_preprocessor.defaults' data collector service.
   *
   * @var \Swaggest\JsonSchema\DataPreProcessor|null
   */
  protected ?DataPreProcessor $dataPreProcessor = NULL;

  /**
   * A collection of defaults to be applied to all contexts.
   *
   * @var array
   */
  protected array $defaultOptions = [
    'tolerateStrings' => TRUE,
  ];

  /**
   * An internal flag to determine when a context needs to be recreated.
   *
   * This flag indicates that options may have changed since the default context
   * was created, so a new one should be configured for ongoing usage.
   *
   * @var bool
   */
  protected bool $needsRebuild = TRUE;

  /**
   * Create a new ContextBuilder instance.
   *
   * @param \Swaggest\JsonSchema\RemoteRefProvider|null $refProvider
   *   (optional) A default remote ref provider to set in contexts.
   * @param \Swaggest\JsonSchema\DataPreProcessor|null $dataPreProcessor
   *   (optional) A default data preprocessor to set in contexts.
   */
  public function __construct(?RemoteRefProvider $refProvider = NULL, ?DataPreProcessor $dataPreProcessor = NULL) {
    $this->refProvider = $refProvider;
    $this->dataPreProcessor = $dataPreProcessor;
  }

  /**
   * Get the default options to be applied to every context.
   *
   * @return array
   *   An associative array of default options applied to every context keyed by
   *   the option name.
   */
  public function getDefaultOptions(): array {
    return $this->defaultOptions;
  }

  /**
   * Set default options to be configured on every context.
   *
   * @param array $options
   *   An associative array of context options keyed by the option name.
   *
   * @see \Swaggest\JsonSchema\Context
   *
   * @throws \Webmozart\Assert\InvalidArgumentException
   */
  public function setDefaultOptions(array $options): void {
    $this->validateOptions($options);

    $this->defaultOptions = $options;

    // Mark the context for rebuild to ensure these options are used.
    $this->needsRebuild = TRUE;
  }

  /**
   * Set a value for a specific option to be used on all contexts.
   *
   * @param string $option
   *   The name of the option to set a value for. Review instance variables on
   *   the \Swaggest\JsonSchema\Context class for available options.
   * @param mixed $value
   *   The value to set for the specified option.
   *
   * @throws \Webmozart\Assert\InvalidArgumentException
   */
  public function setDefaultOption(string $option, $value): void {
    $this->validateOptions([$option => $value]);

    $this->defaultOptions[$option] = $value;

    // Mark the context for rebuild to ensure these options are used.
    $this->needsRebuild = TRUE;
  }

  /**
   * Get a configured default context for use in schema operations.
   *
   * @return \Swaggest\JsonSchema\Context
   *   A configured default context for schema operations.
   */
  public function getDefaultContext(): Context {
    if ($this->needsRebuild) {
      $this->context = new Context();
      $this->configureContextDefaults($this->context);

      $this->needsRebuild = FALSE;
    }

    // Clone the default context to avoid capturing processing state data during
    // usage of the returned instance in schema operations.
    return clone($this->context);
  }

  /**
   * Set the default remote reference provider to be used.
   *
   * @param \Swaggest\JsonSchema\RemoteRefProvider|null $refProvider
   *   The default remote reference provider to be used.
   */
  public function setRemoteRefProvider(?RemoteRefProvider $refProvider): void {
    $this->refProvider = $refProvider;

    // Mark the context for rebuild to ensure this reference provider is used.
    $this->needsRebuild = TRUE;
  }

  /**
   * Set the default data preprocessor to be used.
   *
   * @param \Swaggest\JsonSchema\DataPreProcessor|null $preprocessor
   *   The default data preprocessor to be used.
   */
  public function setDataPreProcessor(?DataPreProcessor $preprocessor): void {
    $this->dataPreProcessor = $preprocessor;

    // Mark the context for rebuild to ensure data preprocessor is used.
    $this->needsRebuild = TRUE;
  }

  /**
   * Apply configuration defaults to a given Context.
   *
   * @param \Swaggest\JsonSchema\Context $context
   *   A Context to be configured.
   *
   * @return \Swaggest\JsonSchema\Context
   *   The provided Context with defaults applied.
   */
  public function configureContextDefaults(Context $context): Context {
    $this->applyOptions($context, $this->defaultOptions + [
      'refProvider' => $this->refProvider,
      'dataPreProcessor' => $this->dataPreProcessor,
    ]);

    return $context;
  }

  /**
   * Get a configured context using the provided options and global defaults.
   *
   * @param array $options
   *   An associative array of options to be combined with global defaults and
   *   configured on the resulting context. Any settings explicitly included
   *   will override global defaults. In addition to the keys of instance
   *   variables on the \Swaggest\JsonSchema\Context class, the following values
   *   may be provided for configuration:
   *   - refProvider: A RemoteRefProvider instance to be used or NULL to
   *     enforce one to not be provided.
   *   - dataPreProcessor: A DataPreProcessor instance to be used or NULL to
   *     enforce one to not be provided.
   *
   * @return \Swaggest\JsonSchema\Context
   *   A Context instance with the specified configuration applied.
   *
   * @throws \Webmozart\Assert\InvalidArgumentException
   */
  public function getConfiguredContext(array $options): Context {
    $this->validateOptions($options);

    $context = new Context();

    // Apply merged option set to the context.
    $this->applyOptions($context, $options + $this->defaultOptions);

    return $context;
  }

  /**
   * Apply a collection of configuration options to the provided Context.
   *
   * @param \Swaggest\JsonSchema\Context $context
   *   The context to be configured.
   * @param array $options
   *   An associative array of options to configure on the context.
   */
  protected function applyOptions(Context $context, array $options): void {
    // Apply basic configuration options.
    foreach ($options as $name => $value) {
      if (in_array($name, ['refProvider', 'dataPreProcessor'])) {
        continue;
      }

      $context->$name = $value;
    }

    // Apply the remote reference provider if applicable.
    if (array_key_exists('refProvider', $options)) {
      if ($options['refProvider'] === NULL) {
        $context->remoteRefPovider = NULL;
      }
      else {
        $context->setRemoteRefProvider($options['refProvider']);
      }
    }

    // Apply the data preprocessor if applicable.
    if (array_key_exists('dataPreProcessor', $options)) {
      if ($options['dataPreProcessor'] === NULL) {
        $context->dataPreProcessor = NULL;
      }
      else {
        $context->setDataPreProcessor($options['dataPreProcessor']);
      }
    }
  }

  /**
   * Iterate over provided options to validate them.
   *
   * @param array $options
   *   An associative array of option values keyed by their option name.
   *
   * @throws \Webmozart\Assert\InvalidArgumentException
   *   Throws an exception if arguments fail to validate.
   *
   * @todo Add validation for instance variables.
   */
  protected function validateOptions(array $options) {
    if (array_key_exists('refProvider', $options)) {
      Assert::nullOrIsInstanceOf($options['refProvider'], RemoteRefProvider::class);
    }
    if (array_key_exists('dataPreProcessor', $options)) {
      Assert::nullOrIsInstanceOf($options['dataPreProcessor'], DataPreProcessor::class);
    }
  }

}
