<?php

namespace Drupal\patternkit\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * An event triggered when a pattern encounters exceptions during rendering.
 *
 * @event
 *
 * @see \Drupal\patternkit\PatternkitEvents::RENDER_FAILURE
 */
class PatternRenderFailureEvent extends Event {

  /**
   * A flag to track whether the failure event has been handled.
   *
   * @var bool
   */
  protected bool $isHandled = FALSE;

  /**
   * The render element array that encountered a failure.
   *
   * @var array
   */
  protected array $element;

  /**
   * Create a new PatternRenderFailureEvent instance.
   *
   * @param array $originalElement
   *   The original render element that failed to render.
   * @param \Throwable $exception
   *   The exception that lead to the render failure.
   */
  public function __construct(
    protected readonly array $originalElement,
    protected readonly \Throwable $exception,
  ) {
    $this->element = $originalElement;
  }

  /**
   * Checks or sets whether the failure event has been handled.
   *
   * @param bool|null $isHandled
   *   (optional) The new handled state.
   *
   * @return bool
   *   TRUE if the event has been handled, FALSE otherwise.
   */
  public function isHandled(?bool $isHandled = NULL): bool {
    if (!is_null($isHandled)) {
      $this->isHandled = $isHandled;
    }

    return $this->isHandled;
  }

  /**
   * Gets the original render element array.
   *
   * @return array
   *   The original render element array.
   */
  public function getOriginalElement(): array {
    return $this->originalElement;
  }

  /**
   * Gets the render element array.
   *
   * This render element may have been altered by other event subscribers since
   * the original exception was raised. For the original element structure use
   * `::getOriginalElement()`.
   *
   * @return array
   *   The render element array.
   *
   * @see self::getOriginalElement()
   */
  public function getElement(): array {
    return $this->element;
  }

  /**
   * Gets the exception that caused the render failure.
   *
   * @return \Throwable
   *   The exception instance.
   */
  public function getException(): \Throwable {
    return $this->exception;
  }

  /**
   * Sets the render element.
   *
   * @param array $element
   *   The new render element.
   *
   * @return self
   *   The current instance for method chaining.
   */
  public function setElement(array $element): self {
    $this->element = $element;

    return $this;
  }

}
