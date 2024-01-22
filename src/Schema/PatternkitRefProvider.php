<?php

namespace Drupal\patternkit\Schema;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\patternkit\PatternRepository;
use Swaggest\JsonSchema\RemoteRefProvider;

/**
 * A reference provider implementation to load Patternkit schema references.
 */
class PatternkitRefProvider implements RemoteRefProvider {

  /**
   * The schema handler service.
   *
   * @var \Drupal\patternkit\Schema\SchemaRefHandler
   */
  protected SchemaRefHandler $schemaRefHandler;

  /**
   * The pattern repository service for loading patterns.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

  /**
   * The logger channel for patternkit.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Creates a new PatternkitRefProvider instance.
   *
   * @param \Drupal\patternkit\Schema\SchemaRefHandler $schemaRefHandler
   *   The schema helper service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The patternkit logger channel.
   */
  public function __construct(SchemaRefHandler $schemaRefHandler, LoggerChannelInterface $logger) {
    $this->schemaRefHandler = $schemaRefHandler;
    $this->logger = $logger;
  }

  /**
   * Set the pattern repository service to be used.
   *
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service to be used.
   */
  public function setPatternRepository(PatternRepository $patternRepository): void {
    $this->patternRepository = $patternRepository;
  }

  /**
   * Get the pattern repository service to be used.
   *
   * Lazy-load the pattern repository service to prevent circular dependencies.
   *
   * @return \Drupal\patternkit\PatternRepository
   *   The pattern repository service to be used.
   */
  protected function patternRepository(): PatternRepository {
    if (!isset($this->patternRepository)) {
      $this->patternRepository = \Drupal::service('patternkit.pattern.repository');
    }

    return $this->patternRepository;
  }

  /**
   * Get schema data for the URL.
   *
   * @param string $url
   *   The URL to request schema data for.
   *
   * @return \stdClass|false
   *   Decoded JSON content for the $url resource or FALSE if unavailable.
   */
  public function getSchemaData($url) {
    // @todo Replace with str_starts_with() once a polyfill dependency is in place.
    if (strpos($url, '/api/patternkit/') === 0) {
      $parsed = $this->schemaRefHandler->parseNormalizedSchemaReference($url);
    }
    elseif ($url[0] === '@') {
      $parsed = $this->schemaRefHandler->parseNamespacedSchemaReference($url);
    }
    else {
      return FALSE;
    }

    if (is_array($parsed)) {
      try {
        $pattern = $this->patternRepository()->getPattern($parsed['asset_id']);
        $schema = $pattern->getSchema();
        return json_decode($schema, FALSE, 512, JSON_THROW_ON_ERROR);
      }
      catch (\JsonException $exception) {
        $this->logger->error(sprintf("Unable to decode JSON for pattern reference '%s'.\n%s",
          $url, $exception->getMessage()));

        return FALSE;
      }
    }
    else {
      // Unable to parse the schema reference.
      return FALSE;
    }
  }

}
