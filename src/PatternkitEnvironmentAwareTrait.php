<?php

namespace Drupal\patternkit;

/**
 * A helper trait for using values from the patternkit environment service.
 *
 * This is a standard implementation of the PatternkitEnvironmentAwareInterface
 * interface for classes to easily include.
 *
 * @see \Drupal\patternkit\PatternkitEnvironment
 * @see \Drupal\patternkit\PatternkitEnvironmentAwareInterface
 *
 * @phpstan-require-implements \Drupal\patternkit\PatternkitEnvironmentAwareInterface
 */
trait PatternkitEnvironmentAwareTrait {

  /**
   * The patternkit environment service.
   *
   * @var \Drupal\patternkit\PatternkitEnvironment
   */
  protected PatternkitEnvironment $patternkitEnvironment;

  /**
   * Get the patternkit environment service.
   *
   * @return \Drupal\patternkit\PatternkitEnvironment
   *   The patternkit environment service.
   */
  public function patternkitEnvironment(): PatternkitEnvironment {
    if (!isset($this->patternkitEnvironment)) {
      $this->patternkitEnvironment = \Drupal::service('patternkit.environment');
    }

    return $this->patternkitEnvironment;
  }

  /**
   * Set the patternkit environment value.
   *
   * @param \Drupal\patternkit\PatternkitEnvironment $environment
   *   The patternkit environment service to be used.
   */
  public function setPatternkitEnvironment(PatternkitEnvironment $environment): void {
    $this->patternkitEnvironment = $environment;
  }

  /**
   * Checks if debug mode is enabled in the patternkit environment.
   *
   * @return bool
   *   True if debug mode is enabled, false otherwise.
   */
  public function isDebug(): bool {
    return $this->patternkitEnvironment()->isDebug();
  }

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
  public function getEnvironmentFeature(string $name, $default) {
    return $this->patternkitEnvironment()->getFeatureOption($name, $default);
  }

}
