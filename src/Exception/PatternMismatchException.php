<?php

namespace Drupal\patternkit\Exception;

/**
 * An exception thrown when a pattern is updated from a different pattern.
 *
 * When attempting to update properties on a pattern instance from another
 * pattern instance, it is assumed that both instances represent the same
 * pattern asset. This exception is thrown if a different pattern type is
 * used to update values on a pattern instance.
 */
class PatternMismatchException extends \LogicException {}
