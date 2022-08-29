<?php

namespace Drupal\patternkit\Schema;

/**
 * A trait for accessing the ContextBuilder service and setting it for use.
 *
 * @see \Drupal\patternkit\Schema\ContextBuilder
 */
trait ContextBuilderTrait {

  /**
   * The schema context builder service.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder
   */
  protected ContextBuilder $contextBuilder;

  /**
   * Set the context builder service to be used.
   *
   * @param \Drupal\patternkit\Schema\ContextBuilder $contextBuilder
   *   The context builder service to be used.
   */
  public function setContextBuilder(ContextBuilder $contextBuilder): void {
    $this->contextBuilder = $contextBuilder;
  }

  /**
   * Get the context builder service.
   *
   * @return \Drupal\patternkit\Schema\ContextBuilder
   *   The context builder service to be used.
   */
  protected function contextBuilder(): ContextBuilder {
    if (!isset($this->contextBuilder)) {
      $this->contextBuilder = \Drupal::service('patternkit.schema.context_builder');
    }

    return $this->contextBuilder;
  }

}
