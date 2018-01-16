<?php

/**
 * Class PatternkitPattern.
 */
class PatternkitPattern {
  /**
   * The subtype of pattern. Typically the filename/pattern URL.
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
   * An override TTL in ms for the pattern.
   *
   * @var int
   */
  public $ttl;

  /**
   * The category of pattern.
   *
   * @var string
   *
   * @see
   */
  public $category;

  public $config;

  public $body;

  /**
   * The Patternkit Library that loaded the pattern.
   *
   * @var \PatternkitLibInterface
   */
  public $library;

  public $version;

}
