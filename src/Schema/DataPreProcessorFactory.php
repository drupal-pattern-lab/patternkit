<?php

namespace Drupal\patternkit\Schema;

use Drupal\patternkit\Exception\MissingDataPreProcessorException;
use Swaggest\JsonSchema\DataPreProcessor;

/**
 * A factory service for registering and loading schema data preprocessors.
 *
 * This service is registered as a service collector looking for services tagged
 * with a 'schema_data_preprocessor' tag. Any new schema data preprocessors may
 * be registered as a service using this tag and an associated alias.
 */
class DataPreProcessorFactory {

  /**
   * An associative array of discovered data preprocessors.
   *
   * Data preprocessors are keyed by the alias specified in their service
   * definition.
   *
   * @var \Swaggest\JsonSchema\DataPreProcessor[]
   */
  protected array $dataPreProcessors = [];

  /**
   * Register a new data preprocessor for use.
   *
   * @param \Swaggest\JsonSchema\DataPreProcessor $dataPreProcessor
   *   The data preprocessor instance to be registered.
   * @param string $alias
   *   The identifier to register the data preprocessor under.
   */
  public function addPreProcessor(DataPreProcessor $dataPreProcessor, string $alias): void {
    $this->dataPreProcessors[$alias] = $dataPreProcessor;
  }

  /**
   * Get a registered data preprocessor.
   *
   * @param string $id
   *   The identifying alias the data preprocessor was registered under.
   *
   * @return \Swaggest\JsonSchema\DataPreProcessor
   *   The requested data preprocessor instance.
   *
   * @throws \Drupal\patternkit\Exception\MissingDataPreProcessorException
   *   Throws an exception if the requested preprocessor is not registered.
   */
  public function getPreProcessor(string $id): DataPreProcessor {
    if (empty($this->dataPreProcessors[$id])) {
      throw new MissingDataPreProcessorException('Unknown DataPreProcessor "' . $id . '"');
    }

    return clone($this->dataPreProcessors[$id]);
  }

}
