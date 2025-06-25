<?php

namespace Drupal\patternkit;

/**
 * A class that collects and defines environment options for Patternkit.
 *
 * @see \Drupal\patternkit\PatternkitEnvironmentAwareInterface
 * @see \Drupal\patternkit\PatternkitEnvironmentAwareTrait
 */
class PatternkitEnvironment {

  /**
   * Whether debug mode should be enabled.
   *
   * @var bool
   */
  protected bool $debug;

  /**
   * Configured feature options.
   *
   * @var array
   */
  protected array $features;

  /**
   * A feature option to bypass the database cache for discovered patterns.
   *
   * @see self::getFeatureOption()
   * @see \Drupal\patternkit\Asset\PatternDiscovery
   */
  public const FEATURE_BYPASS_PATTERN_CACHE = 'bypass_pattern_cache';

  /**
   * A feature option to bypass the database cache for stored pattern content.
   *
   * @see self::getFeatureOption()
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::getPatternEntity()
   */
  public const FEATURE_BYPASS_DATABASE_CACHE = 'bypass_database_cache';

  /**
   * A feature option to enable usage of reusable blocks.
   *
   * @deprecated in patternkit:9.1.2 and is removed from patternkit:9.2.0.
   *   Reusable blocks should be removed or replaced before updating.
   * @see https://www.drupal.org/node/3517440
   *
   * @see self::getFeatureOption()
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::getPatternEntity()
   */
  #[\Deprecated(
    message: 'Patternkit reusable block support will be removed in Patternkit 9.2.0.',
    since: '9.1.2',
  )]
  public const FEATURE_ENABLE_REUSABLE_BLOCKS = 'enable_reusable_blocks';

  /**
   * Creates a new PatternkitEnvironment instance.
   *
   * @param array $options
   *   An associative array of options. Available options include the following:
   *
   *   - debug: When set to true, it enables debug behavior throughout the
   *     module. Defaults to false.
   */
  public function __construct(array $options = []) {
    $options = array_merge([
      'debug' => FALSE,
      'features' => [],
    ], $options);

    $this->debug = (bool) $options['debug'];
    $this->features = $options['features'];
  }

  /**
   * Checks if debug mode is enabled.
   *
   * @return bool
   *   True if debug mode is enabled, false otherwise.
   */
  public function isDebug(): bool {
    return $this->debug;
  }

  /**
   * Get the configuration value for a specified feature.
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
   */
  public function getFeatureOption(string $name, mixed $default): mixed {
    return $this->features[$name] ?? $default;
  }

}
