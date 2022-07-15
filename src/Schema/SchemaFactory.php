<?php

namespace Drupal\patternkit\Schema;

use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\Exception\SchemaReferenceException;
use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Exception;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\RemoteRefProvider;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\SchemaContract;

/**
 * A factory service for creating Schema validator instances.
 *
 * This factory service allows the creation of Schema instances with
 * properly loaded context to support the resolution of normalized Patternkit
 * schemas.
 */
class SchemaFactory {

  /**
   * The reference provider to be used for loading schema references.
   *
   * @var \Swaggest\JsonSchema\RemoteRefProvider
   */
  protected RemoteRefProvider $refProvider;

  /**
   * A data preprocessor to configure on all schema instances.
   *
   * @var \Swaggest\JsonSchema\DataPreProcessor
   */
  protected DataPreProcessor $dataPreProcessor;

  /**
   * The default context to use for schema operations.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $defaultContext;

  /**
   * Create a new Schema instance.
   *
   * @param \Swaggest\JsonSchema\RemoteRefProvider $refProvider
   *   The ref provider for loading schema references.
   * @param \Swaggest\JsonSchema\DataPreProcessor|null $dataPreProcessor
   *   (Optional) A data preprocessor to use on all Schema instances. Defaults
   *   to an ObjectCoercionDataPreProcessor if none is provided.
   *
   * @see \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
   */
  public function __construct(RemoteRefProvider $refProvider, ?DataPreProcessor $dataPreProcessor = NULL) {
    $this->refProvider = $refProvider;
    $this->dataPreProcessor = $dataPreProcessor ?? new ObjectCoercionDataPreProcessor();

    $this->defaultContext = new Context($refProvider);
    $this->defaultContext->setDataPreProcessor($this->dataPreProcessor);
  }

  /**
   * Create a new Schema instance.
   *
   * @param string $schema
   *   The schema JSON for injection into the schema instance.
   * @param \Swaggest\JsonSchema\Context|null $context
   *   (Optional) Context configuration for schema processing and operations.
   *   In most cases, this is not needed, but it may be provided for detailed
   *   customization of how a Schema instance should behave.
   *
   * @return \Swaggest\JsonSchema\SchemaContract
   *   A configured Schema instance.
   *
   * @throws \Drupal\patternkit\Exception\SchemaException
   * @throws \Drupal\patternkit\Exception\SchemaReferenceException
   *   Throws an exception if a referenced schema could not be loaded.
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   *   Throws an exception if the given value fails to validate against the
   *   provided schema.
   */
  public function createInstance(string $schema, ?Context $context = NULL): SchemaContract {
    // Configure defaults on the provided context options if one is provided.
    if ($context !== NULL) {
      // Don't override the remote ref provider if one is already configured
      // for use.
      if ($context->getRemoteRefProvider() === NULL) {
        $context->setRemoteRefProvider($this->refProvider);
      }

      // Don't override the data preprocessor if one is already configured
      // for use.
      if ($context->getDataPreProcessor() === NULL) {
        $context->setDataPreProcessor($this->dataPreProcessor);
      }
    }
    else {
      $context = $this->getDefaultContext();
    }

    try {
      return Schema::import($this->decodeJson($schema), $context);
    }
    catch (InvalidValue $invalidValueException) {
      // Wrap the exception in our own class for abstraction.
      throw new SchemaValidationException($invalidValueException->getMessage(), $invalidValueException->getCode(), $invalidValueException);
    }
    catch (Exception $jsonSchemaException) {
      // Re-cast as a more specific exception if we can identify the cause.
      $message = $jsonSchemaException->getMessage();
      if (strpos($message, 'Failed to decode content from') === 0) {
        throw new SchemaReferenceException($message, $jsonSchemaException->getCode(), $jsonSchemaException);
      }

      // Wrap the exception in our own class for abstraction.
      throw new SchemaException($message, $jsonSchemaException->getCode(), $jsonSchemaException);
    }
    catch (\Exception $exception) {
      // Wrap the exception in our own class for abstraction.
      throw new SchemaException($exception->getMessage(), $exception->getCode(), $exception);
    }
  }

  /**
   * Get a configured default context for use in schema operations.
   *
   * @return \Swaggest\JsonSchema\Context
   *   A configured default context for schema operations.
   */
  public function getDefaultContext(): Context {
    // Clone the default context to avoid capturing processing state data during
    // usage of the returned instance in schema operations.
    return clone($this->defaultContext);
  }

  /**
   * Encapsulate the process for decoding provided JSON values.
   *
   * @param string $schema
   *   The JSON string value to be decoded.
   *
   * @return mixed
   *   The decoded JSON value.
   *
   * @throws \JsonException
   */
  protected function decodeJson(string $schema) {
    return json_decode($schema, FALSE, 512, JSON_THROW_ON_ERROR);
  }

}
