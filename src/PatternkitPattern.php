<?php

/**
 * Class PatternkitPattern.
 */
class PatternkitPattern {

  /**
   * PatternkitPattern constructor.
   *
   * @param \PatternkitLibInterface $library
   *   The library the pattern belongs to.
   * @param object|array $schema
   *   An optional JSON Schema object to use.
   */
  public function __construct(PatternkitLibInterface $library, $schema = array()) {
    $this->subtype     = NULL;
    $this->title       = NULL;
    $this->html        = NULL;
    $this->version     = NULL;
    $this->attachments = NULL;
    $this->schema      = $schema;

    // If schema is undefined or does not validate, initialize empty.
    // @todo Throw an exception if schema does not validate.
    if (empty($schema) || !self::validateSchema($schema)) {
      return;
    }

    // Walk the provided schemas and generate the library.
    foreach ($schema as $key => $value) {
      if ($key !== 'schema' && property_exists($this, (string) $key)) {
        $this->{$key} = $value;
      }
    }
    $this->library = $library;
  }

  /**
   * The required minimum data for a pattern.
   *
   * @ingroup required
   * @{
   */

  /**
   * Specifications for required properties.
   *
   * @var array
   */
  public static $requiredProperties = array(
    'title'   => 'string',
  );

  /**
   * The Patternkit Library that loaded the pattern.
   *
   * @var \PatternkitLibInterface
   */
  public $library;

  /**
   * The subtype for the pattern. Typically "pk_{$namespace}_{$pattern}".
   *
   * Must be unique across the site. Is used by Panels to address config, etc.
   *
   * @var string
   */
  public $subtype;

  /**
   * The human-readable title of the pattern.
   *
   * @var string
   */
  public $title;

  /**
   * @}
   */

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
   * The pattern template file.
   *
   * @var string
   */
  public $filename;

  /**
   * Pre-rendered HTML using the configuration object, if available.
   *
   * @var string
   */
  public $html;

  /**
   * The legacy subtype for Patternkit versions without namespacing.
   *
   * @var string
   *
   * @deprecated v1.0.0
   */
  public $legacySubtype;

  /**
   * The JSON Schema for the pattern.
   *
   * @var object
   *
   * @see https://datatracker.ietf.org/doc/draft-handrews-json-schema/
   */
  public $schema;

  /**
   * Optional pattern template contents.
   *
   * Typically Twig or Nunjucks, others include Handlebars and Jinja.
   *
   * @var string
   */
  public $template;

  /**
   * An override TTL in ms for the pattern.
   *
   * @var int
   */
  public $ttl;

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
   * Validates a provided schema.
   *
   * @param array|object $schema
   *   The JSON Pattern schema to validate.
   *
   * @return array|bool
   *   TRUE if the schema validates, otherwise an array of errors.
   */
  public static function validateSchema($schema) {
    // @todo Use an existing JSON Schema Validation Library.
    $errors = array();
    foreach (self::$requiredProperties as $property => $type) {
      if (!isset($schema->{$property})) {
        $errors[] = "Required schema property $property not found.";
        continue;
      }
      if (gettype($schema->{$property}) !== $type) {
        $errors[] = "Schema property $property is not of the specified type $type";
      }
    }
    return !empty($errors) ? $errors : TRUE;
  }

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

  /**
   * Renders the pattern and returns the generated markup.
   *
   * @param \PatternkitEditorConfig $config
   *   The editor configuration for the pattern.
   *
   * @return string
   *   The rendered HTML pattern markup.
   */
  public function getRenderedMarkup(PatternkitEditorConfig $config) {
    return $this->library->getRenderedPatternMarkup($this, $config);
  }

}
