<?php

namespace Drupal\patternkit\Schema\DataPreProcessor;

use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Schema;

/**
 * A collection of data preprocessors to be executed in order of priority.
 *
 * @todo Refactor sorting by priority.
 */
class DataPreProcessorCollection implements DataPreProcessor {

  const DEFAULT_PRIORITY = 10;

  /**
   * An associative array of preprocessor groups keyed by weight.
   *
   * @var array
   */
  protected array $preprocessors = [];

  /**
   * Get all registered data preprocessors.
   *
   * @return array
   *   An associative array of all registered data preprocessors grouped by
   *   assigned weight.
   */
  public function getPreProcessors(): array {
    return $this->preprocessors;
  }

  /**
   * Add a data preprocessor to the collection for processing.
   *
   * @param \Swaggest\JsonSchema\DataPreProcessor $dataPreProcessor
   *   The data preprocessor to be executed.
   * @param int $priority
   *   (optional) A priority group to assign the preprocessor into. A higher
   *   priority results in the processor running earlier than others. By
   *   default, new processors will have a priority of 10.
   *
   * @return $this
   */
  public function addPreProcessor(DataPreProcessor $dataPreProcessor, int $priority = self::DEFAULT_PRIORITY): self {
    $this->preprocessors[$priority][] = $dataPreProcessor;

    return $this;
  }

  /**
   * Process the provided schema data.
   *
   * @param mixed $data
   *   The original data prior to processing.
   * @param \Swaggest\JsonSchema\Schema $schema
   *   The related schema for the data being processed.
   * @param bool $import
   *   A boolean flag indicating if schema data is currently being imported.
   *
   * @return mixed
   *   Processed data.
   */
  public function process($data, Schema $schema, $import = TRUE) {
    /** @var \Swaggest\JsonSchema\DataPreProcessor[] $group */
    foreach ($this->preprocessors as $group) {
      foreach ($group as $dataPreProcessor) {
        $data = $dataPreProcessor->process($data, $schema, $import);
      }
    }

    return $data;
  }

}
