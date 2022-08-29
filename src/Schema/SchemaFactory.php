<?php

namespace Drupal\patternkit\Schema;

use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\Exception\SchemaReferenceException;
use Drupal\patternkit\Exception\SchemaValidationException;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\Exception as JsonSchemaException;
use Swaggest\JsonSchema\InvalidValue;
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
   * The context builder service for preparing schema contexts.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder
   */
  protected ContextBuilder $contextBuilder;

  /**
   * Create a new SchemaFactory instance.
   *
   * @param \Drupal\patternkit\Schema\ContextBuilder $contextBuilder
   *   The context builder service.
   */
  public function __construct(ContextBuilder $contextBuilder) {
    $this->contextBuilder = $contextBuilder;
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
      $context = $this->contextBuilder->configureContextDefaults($context);
    }
    else {
      $context = $this->contextBuilder->getDefaultContext();
    }

    try {
      return Schema::import($this->decodeJson($schema), $context);
    }
    catch (InvalidValue $invalidValueException) {
      // Wrap the exception in our own class for abstraction.
      throw new SchemaValidationException($invalidValueException->getMessage(), $invalidValueException->getCode(), $invalidValueException);
    }
    catch (JsonSchemaException $jsonSchemaException) {
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
