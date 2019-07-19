<?php

namespace Drupal\patternkit;

use function is_string;
use function PHPSTORM_META\type;

/**
 * A collection of a JSON schema and renderable markup.
 *
 * It is meant to be interoperable with anything that supports JSON Schema.
 *
 * @see https://datatracker.ietf.org/doc/draft-handrews-json-schema/
 */
class Pattern {


  /**
   * The required minimum data for a pattern.
   *
   * @ingroup required
   * @{
   */

  /**
   * The name of the pattern.
   *
   * @var string
   */
  public $name;

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
   * The description of the pattern.
   *
   * @var string
   */
  public $description;

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
   * The JSON Schema for the pattern.
   *
   * @var object
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
   * The plugin id for the library.
   *
   * @var string
   */
  protected $libraryPluginId;

  /**
   * PatternkitPattern constructor.
   *
   * @param string $name
   *   The name of the pattern.
   * @param object|array $schema
   *   An optional JSON Schema object to use.
   */
  public function __construct($name, $schema = NULL) {
    $this->name        = $name;
    $this->category    = 'default';
    $this->title       = NULL;
    $this->html        = NULL;
    $this->version     = NULL;
    $this->attachments = NULL;
    $this->schema      = $schema;

    // If schema is undefined, initialize empty.
    if (empty($schema)) {
      return;
    }

    // Walk the provided schemas and generate the pattern.
    foreach ($schema as $key => $value) {
      if ($key !== 'schema' && property_exists($this, (string) $key)) {
        $this->{$key} = $value;
      }
    }
  }

  /**
   * Returns the pattern description.
   *
   * @return string
   *   The pattern description.
   */
  public function getDescription(): string {
    return $this->description ?? '';
  }

  /**
   * Returns the id for this pattern.
   *
   * @return string
   *   The pattern description.
   */
  public function getId(): string {
    return $this->name;
  }

  /**
   * Returns the label for this pattern.
   *
   * @return string
   *   The label of the pattern.
   */
  public function getLabel(): string {
    return !empty($this->title) && is_string($this->title) ? $this->title : $this->name;
  }

  /**
   * Returns the id of the pattern library plugin.
   *
   * @return string
   *   The pattern library plugin id.
   */
  public function getLibraryPluginId(): string {
    return $this->libraryPluginId ?? '';
  }

  /**
   * Sets the id of the pattern library plugin.
   *
   * @param string $plugin_id
   *   The pattern library plugin id.
   */
  public function setLibraryPluginId($plugin_id): void {
    $this->libraryPluginId = $plugin_id;
  }

}
