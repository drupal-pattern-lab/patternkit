<?php

namespace Drupal\patternkit\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element to display in place of a pattern error.
 *
 * If a pattern fails to load or render where expected, this render element
 * may be used as a replacement to handle a graceful degradation experience
 * of what should be displayed in place of the failed pattern content.
 *
 * Properties:
 * - '#pattern': The loaded Pattern entity to be rendered.
 * - '#config': Configuration to be passed to the pattern for rendering.
 * - '#context': Context values for rendering the pattern.
 * - '#exception': The exception object causing the render failure.
 *
 * Usage Example:
 * @code
 * $build['failed_pattern'] = [
 *   '#type' => 'pattern_error',
 *   '#pattern' => Pattern,
 *   '#config' => [
 *     'text' => '[node:title]',
 *     'formatted_text' => '<p><strong>My formatted text</strong></p>',
 *   ],
 *   '#context' => [
 *     'node' => $node,
 *   ],
 *   '#exception' => Exception,
 * ];
 * @endcode
 *
 * @RenderElement("pattern_error")
 */
class PatternError extends RenderElement {

  /**
   * The permission to check for including debug output in error displays.
   */
  const DEBUG_PERMISSION = 'access devel information';

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#pre_render' => [
        [$this, 'preRenderPatternErrorElement'],
        [$this, 'preRenderDebugOutput'],
      ],
      '#pattern' => NULL,
      '#config' => [],
      '#context' => [],
      '#exception' => NULL,
    ];
  }

  /**
   * Pattern error element pre render callback to handle basic failure display.
   *
   * @param array $element
   *   An associative array containing the properties of the pattern element.
   *
   * @return array
   *   The modified element.
   */
  public function preRenderPatternErrorElement(array $element): array {
    /** @var \Drupal\patternkit\Entity\Pattern $pattern */
    $pattern = $element['#pattern'];

    // Return an error message to display in place of the rendered pattern.
    $message = $this->t('Failed to render pattern %pattern (%pattern_id).', [
      '%pattern' => $pattern->getName(),
      '%pattern_id' => $pattern->getAssetId(),
    ]);
    $element['message'] = [
      '#markup' => $message,
    ];

    return $element;
  }

  /**
   * Secondary pre render callback to prepare debug information if applicable.
   *
   * @param array $element
   *   An associative array containing the properties of the pattern element.
   *
   * @return array
   *   The modified element.
   */
  public function preRenderDebugOutput(array $element): array {
    // Skip altogether if the user doesn't have access to dev output.
    if (!$this->shouldDisplayDebugOutput()) {
      return $element;
    }

    if (isset($element['#exception'])) {
      /** @var \Exception $exception */
      $exception = $element['#exception'];

      // Collect all debug information in a collapsed container to avoid
      // overwhelming the user, especially in the case of multiple failures on
      // a single page.
      $element['debug'] = [
        '#type' => 'details',
        '#title' => $this->t('Debug output'),
        '#open' => FALSE,
      ];

      // Expose the exception message in a formatted block for easier
      // parsing by developers.
      $element['debug']['message'] = [
        '#markup' => '<pre>' . $exception->getMessage() . '</pre>',
      ];
    }

    return $element;
  }

  /**
   * Test if debug output should be displayed.
   *
   * @return bool
   *   TRUE if debug output should be displayed. FALSE otherwise.
   */
  protected function shouldDisplayDebugOutput(): bool {
    return \Drupal::currentUser()->hasPermission(static::DEBUG_PERMISSION);
  }

}
