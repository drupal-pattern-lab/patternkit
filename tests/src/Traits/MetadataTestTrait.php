<?php

namespace Drupal\Tests\patternkit\Traits;

use Drupal\Core\Render\BubbleableMetadata;

/**
 * A helper trait for making assertions against bubbleable metadata.
 */
trait MetadataTestTrait {

  /**
   * Asserts that two variables are equal (canonicalizing).
   *
   * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
   * @throws \PHPUnit\Framework\ExpectationFailedException
   */
  abstract public static function assertEqualsCanonicalizing($expected, $actual, string $message = ''): void;

  /**
   * Test the bubbleable metadata on a render element.
   *
   * This method allows for testing of individual elements of metadata while
   * ignoring others, but if a complete view of the cacheable metadata is
   * needed, testing the '#cache' key directly with
   * 'assertEqualsCanonicalizing' may be a better solution for consideration.
   *
   * @param array $element
   *   A render array to test the bubbleable metadata on.
   * @param array|\Drupal\Core\Render\BubbleableMetadata $expected
   *   An associative array of expected metadata containing one of the following
   *   keys:
   *   - 'tags': Cache tags expected on the element.
   *   - 'contexts': Cache context keys expected on the element.
   *   - 'max-age': A max age value expected on the element.
   *   - '#attachments': Any attachments expected on the element.
   */
  public static function assertBubbleableMetadata(array $element, $expected): void {
    $metadata = BubbleableMetadata::createFromRenderArray($element);

    if (isset($expected['tags'])) {
      static::assertEqualsCanonicalizing($expected['tags'], $metadata->getCacheTags());
    }
    if (isset($expected['contexts'])) {
      static::assertEqualsCanonicalizing($expected['contexts'], $metadata->getCacheContexts());
    }
    if (isset($expected['max-age'])) {
      static::assertEqualsCanonicalizing($expected['max-age'], $metadata->getCacheMaxAge());
    }
    if (isset($expected['#attached'])) {
      static::assertEqualsCanonicalizing($expected['#attached'], $metadata->getAttachments());
    }
  }

}
