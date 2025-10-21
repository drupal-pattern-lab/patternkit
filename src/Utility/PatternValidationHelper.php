<?php

namespace Drupal\patternkit\Utility;

use Drupal\patternkit\PatternValidationTrait;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\SchemaFactory;

/**
 * A service for validating pattern content against schemas.
 *
 * This service provides a centralized way for other classes to validate
 * pattern content against JSON schemas. It wraps the PatternValidationTrait
 * functionality in a service that can be easily injected into other classes.
 *
 * @internal This service may be replaced or rewritten in the future and does
 *   not guarantee backward compatibility.
 */
class PatternValidationHelper {

  use PatternValidationTrait {
    hasExceptions as public;
    getExceptions as public;
    validateContent as public;
    validatePattern as public;
  }

  /**
   * Constructs a PatternValidationHelper object.
   *
   * @param \Drupal\patternkit\Schema\ContextBuilder $contextBuilder
   *   The schema context builder service.
   * @param \Drupal\patternkit\Schema\SchemaFactory $schemaFactory
   *   The schema factory service.
   */
  public function __construct(
    ContextBuilder $contextBuilder,
    SchemaFactory $schemaFactory,
  ) {
    $this->setContextBuilder($contextBuilder);
    $this->setSchemaFactory($schemaFactory);
  }

  /**
   * Clear all stored exceptions.
   *
   * This method allows the service to be reused for multiple validations
   * without carrying over exceptions from previous validation attempts.
   */
  public function clearExceptions(): void {
    $this->exceptions = [];
  }

}
