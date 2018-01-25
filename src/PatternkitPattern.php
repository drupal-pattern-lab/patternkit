<?php

/**
 * Class PatternkitPattern.
 */
class PatternkitPattern {

  /**
   * PatternkitPattern constructor.
   *
   * @param object|array $schema
   *   An optional JSON Schema object to use.
   */
  public function __construct($schema = array()) {
    $this->subtype = NULL;
    $this->title = NULL;
    $this->html = NULL;
    $this->library = NULL;
    $this->version = NULL;
    $this->attachments = NULL;
    $this->schema = $schema;
    if (empty($schema)) {
      return;
    }
    foreach ($schema as $key => $value) {
      if ($key !== 'schema' && property_exists($this, $key)) {
        $this->{$key} = $value;
      }
    }
  }

  /**
   * The subtype for the pattern. Typically "pk_$pattern".
   *
   * @var string
   *
   * This is mostly in use with Drupal to avoid panels namespace conflicts.
   */
  public $subtype;

  /**
   * The human-readable title of the pattern.
   *
   * @var string
   */
  public $title;

  /**
   * An override TTL in ms for the pattern.
   *
   * @var int
   */
  public $ttl;

  /**
   * Optional array of attached assets.
   *
   * @var array
   */
  public $attachments;

  /**
   * The category of pattern.
   *
   * @var string
   *
   * @see https://atomicdesign.bradfrost.com
   */
  public $category;

  /**
   * The configuration object containing token data.
   *
   * @var object
   */
  public $config;

  /**
   * The pattern body template.
   *
   * @var string
   */
  public $body;

  /**
   * Pre-rendered HTML using the configuration object, if available.
   *
   * @var string
   */
  public $html;

  /**
   * The Patternkit Library that loaded the pattern.
   *
   * @var \PatternkitLibInterface
   */
  public $library;

  /**
   * The JSON Schema for the pattern.
   *
   * @var object
   *
   * @see https://datatracker.ietf.org/doc/draft-handrews-json-schema/
   */
  public $schema;

  /**
   * The API URL for the pattern.
   *
   * @var string
   */
  public $url;

  /**
   * The version of the pattern.
   *
   * @var string
   */
  public $version;

  /**
   * Fetches the assets and updates asset references for this pattern.
   *
   * @param \PatternkitEditorConfig $config
   *   The config to use for retrieving the pattern assets.
   *
   * @return $this
   */
  public function fetchAssets(PatternkitEditorConfig $config) {
    $this->library->fetchPatternAssets($this, $config);
    return $this;
  }

}
