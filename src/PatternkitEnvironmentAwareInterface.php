<?php

namespace Drupal\patternkit;

/**
 * An interface for classes using the PatternkitEnvironment service.
 *
 * @see \Drupal\patternkit\PatternkitEnvironmentAwareTrait
 * @see \Drupal\patternkit\PatternkitEnvironment
 */
interface PatternkitEnvironmentAwareInterface {

  /**
   * Get the patternkit environment service.
   *
   * @return \Drupal\patternkit\PatternkitEnvironment
   *   The patternkit environment service.
   */
  public function patternkitEnvironment(): PatternkitEnvironment;

  /**
   * Set the patternkit environment value.
   *
   * @param \Drupal\patternkit\PatternkitEnvironment $environment
   *   The patternkit environment service to be used.
   */
  public function setPatternkitEnvironment(PatternkitEnvironment $environment): void;

  /**
   * Checks if debug mode is enabled in the patternkit environment.
   *
   * @return bool
   *   True if debug mode is enabled, false otherwise.
   */
  public function isDebug(): bool;

  /**
   * Get the configuration value for an environment feature.
   *
   * @param string $name
   *   The name of the feature to get configuration options for.
   * @param mixed $default
   *   The default value to use for the configuration option if the option is
   *   not available.
   *
   * @return mixed
   *   The configured value for the specified feature, or the provided default
   *   value if an option was not configured.
   *
   * @see \Drupal\patternkit\PatternkitEnvironment::getFeatureOption()
   */
  public function getEnvironmentFeature(string $name, $default);

}
