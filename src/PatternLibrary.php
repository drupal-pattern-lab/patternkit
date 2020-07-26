<?php

namespace Drupal\patternkit;

use Drupal\Core\Asset\Exception\LibraryDefinitionMissingLicenseException;
use Drupal\Core\Extension\Extension;

/**
 * Class PatternLibrary.
 *
 * @todo Remove hardcoded js, css, patterns properties.
 * These only exist to keep compatibility with current Drupal core.
 *
 * @todo Contribute back to core as Library.
 *
 * @package Drupal\patternkit
 */
class PatternLibrary {

  /** @var string */
  protected $id;

  /** @var string */
  protected $extension;

  /** @var string */
  protected $extensionType;

  /** @var string|array|bool */
  protected $override;

  /** @var array */
  protected $patternInfo = [];

  /** @var array */
  public $dependencies = [];

  /** @var string */
  public $description = '';

  /** @var array */
  public $js = [];

  /** @var array */
  public $css = [];

  /** @var string */
  public $license;

  /** @var array */
  public $patterns = [];

  /** @var bool */
  public $remote;

  /** @var false|string */
  public $version;

  /**
   * PatternLibrary constructor.
   *
   * @param string $id
   *   ID of the library.
   * @param string $extensionType
   *   The type of extension that owns the library.
   * @param string $extension
   *   The name of the extension that owns the library. May be 'core'.
   * @param null $version
   * @param null $license
   * @param null $remote
   * @param null $header
   */
  public function __construct(
    $id,
    $extensionType,
    $extension,
    $version = NULL,
    $license = NULL,
    $remote = NULL,
    $header = NULL) {

    $this->id = $id;
    $this->extensionType = $extensionType;
    $this->extension = $extension;

    if (($version !== NULL) && is_string($version)) {
      // @todo Retrieve version of a non-core extension.
      $this->version = $version;
      if ($version === 'VERSION') {
        $this->version = \Drupal::VERSION;
      }
      // Remove 'v' prefix from external library versions.
      elseif (strpos($version, 'v') === 0) {
        $this->version = substr($version, 1);
      }
    }

    // If this is a 3rd party library, the license info is required.
    if (isset($remote) && !isset($license)) {
      throw new LibraryDefinitionMissingLicenseException(sprintf("Missing license information in library definition for definition '%s' extension '%s': it has a remote, but no license.", $id, $extension));
    }

    // Assign Drupal's license to libraries that don't have license info.
    if (!isset($library['license'])) {
      $library['license'] = [
        'name' => 'GNU-GPL-2.0-or-later',
        'url' => 'https://www.drupal.org/licensing/faq',
        'gpl-compatible' => TRUE,
      ];
    }

    if (($header !== NULL) && !is_bool($header)) {
      throw new \LogicException(sprintf("The 'header' key in the library definition '%s' in extension '%s' is invalid: it must be a boolean.", $id, $extension));
    }
  }

  /**
   * Returns the library ID.
   *
   * @return string
   *   The ID of the library.
   */
  public function id(): string {
    return $this->id;
  }

  /**
   * Returns the Extension that owns the library.
   *
   * @return Extension
   *   The extension name.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public function getExtension() {
    /** @var \Drupal\Core\Extension\ExtensionList $extension_list */
    $extension_list = \Drupal::service("extension.list." . $this->extensionType);
    return $extension_list->get($this->extension);
  }

  /**
   * @return array
   *   Pattern info array, in the following format:
   *   - string data The pattern path.
   */
  public function getPatternInfo() {
    return $this->patternInfo;
  }

  /**
   * Set an override for this library.
   *
   * Usually used by a theme to selectively replace JS or CSS.
   *
   * @param string|array|FALSE $override
   *   A string overrides the entire library.
   *   An array overrides a single asset.
   *   FALSE requests no overrides, but may be ignored by other modules.
   *
   * @return \Drupal\patternkit\PatternLibrary
   */
  public function setOverride($override): PatternLibrary {
    $this->override = $override;
    return $this;
  }

  /**
   * @param $info
   * An array of pattern info:
   *  -
   *
   * @todo Validate and provide helpful feedback for Pattern Library Info.
   */
  public function setPatternInfo($info) {
    $this->patternInfo = $info;
  }

  /**
   * Returns the Pattern Library as an array.
   *
   * @return array
   *   The Pattern Library's public properties as an array.
   */
  public function toArray(): array {
    return (array) $this;
  }

}
