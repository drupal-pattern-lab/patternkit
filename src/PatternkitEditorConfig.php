<?php

/**
 * Class PatternkitEditorConfig.
 */
class PatternkitEditorConfig extends stdClass {

  /**
   * PatternkitPattern constructor.
   *
   * @param object|array $config
   *   An optional JSON config object to use.
   */
  public function __construct($config = array()) {
    foreach ($config as $property => $value) {
      if (property_exists($this, (string) $property)) {
        $this->{$property} = $value;
        unset($config->{$property});
      }
    }
  }

  /**
   * JSON-style field values used in provisioning the editor.
   *
   * @var object
   */
  public $fields;

  // phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
  /**
   * The config instance id.
   *
   * Uses underscore_case for Drupal compatibility.
   *
   * @var string
   */
  public $instance_id;
  // phpcs:enable Drupal.NamingConventions.ValidVariableName.LowerCamelName

  /**
   * Optional lz-compressed serialized version of the configuration.
   *
   * @var string
   */
  public $lzstring;

  /**
   * Optional pattern to use for configuring the editor.
   *
   * @var \PatternkitPattern
   */
  public $pkdata;

  // phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
  /**
   * The presentation style for the pattern.
   *
   * Uses underscore_case for Drupal compatibility.
   *
   * @var string
   *   Typically the following:
   *   - html
   */
  public $presentation_style;
  // phpcs:enable Drupal.NamingConventions.ValidVariableName.LowerCamelName

  /**
   * The raw Editor configuration JSON.
   *
   * @var string
   */
  public $rawJSON;

}
