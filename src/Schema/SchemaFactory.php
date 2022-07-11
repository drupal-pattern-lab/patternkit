<?php

namespace Drupal\patternkit\Schema;

use Drupal\patternkit\Exception\SchemaException;
use Drupal\patternkit\Exception\SchemaReferenceException;
use Drupal\patternkit\Exception\SchemaValidationException;
use Swaggest\JsonSchema\Context;
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
   * Create a new Schema instance.
   *
   * @param \Swaggest\JsonSchema\RemoteRefProvider $refProvider
   *   The ref provider for loading schema references.
   */
  public function __construct(RemoteRefProvider $refProvider) {
    $this->refProvider = $refProvider;
  }

  /**
   * Create a new Schema instance.
   *
   * @param string $schema
   *   The schema JSON for injection into the schema instance.
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
  public function createInstance(string $schema): SchemaContract {
    try {
      return Schema::import(json_decode($schema), new Context($this->refProvider));
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

}
