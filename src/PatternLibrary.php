<?php

namespace Drupal\patternkit;

use Drupal\Core\Asset\Exception\LibraryDefinitionMissingLicenseException;

/**
 * Wraps and provides defaults for an array of Patterns.
 *
 * @todo Remove hardcoded js, css, patterns properties.
 * These only exist to keep compatibility with current Drupal core.
 *
 * @todo Contribute back to core as Library.
 *
 * @package Drupal\patternkit
 */
class PatternLibrary {

  /**
   * The machine name for the library.
   *
   * @var string
   */
  protected $id;

  /**
   * The id of the extension providing the library definition.
   *
   * @var string
   */
  protected $extension;

  /**
   * The type of extension providing the library definition.
   *
   * @var string
   */
  protected $extensionType;

  /**
   * Truthy if there are overrides present in the library definition info.
   *
   * @var string|array|bool
   */
  protected $override;

  /**
   * Container for metadata about the library patterns.
   *
   * @var array
   */
  protected $patternInfo = [];

  /**
   * A list of dependencies for the library.
   *
   * @var array
   */
  public $dependencies = [];

  /**
   * Human-readable info about the library from the definition.
   *
   * @var string
   */
  public $description = '';

  /**
   * An array of JS assets provided by the library definition.
   *
   * @var array
   */
  public $js = [];

  /**
   * An array of CSS assets provided by the library definition.
   *
   * @var array
   */
  public $css = [];

  /**
   * TRUE if the library is to be used in page headers and loaded first.
   *
   * @var bool|null
   */
  public $header = NULL;

  /**
   * A link or text of the library license.
   *
   * @var string
   */
  public $license;

  /**
   * An array of Pattern assets provided by the library definition.
   *
   * @var array
   */
  public $patterns = [];

  /**
   * TRUE if the library is loaded via remote URL.
   *
   * @var bool
   */
  public $remote;

  /**
   * Library version provided by the definition.
   *
   * @var false|string
   */
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
   * @param string|null $version
   *   Library version provided by the definition.
   * @param mixed $license
   *   A link or text of the library license.
   * @param bool|null $remote
   *   TRUE if the library is loaded via remote URL.
   * @param bool|null $header
   *   TRUE if the library is to be used in page headers and loaded first.
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
    if (!isset($license)) {
      $license = [
        'name' => 'GNU-GPL-2.0-or-later',
        'url' => 'https://www.drupal.org/licensing/faq',
        'gpl-compatible' => TRUE,
      ];
    }
    $this->remote = $remote;
    $this->license = $license;

    if (($header !== NULL) && !is_bool($header)) {
      throw new \LogicException(sprintf("The 'header' key in the library definition '%s' in extension '%s' is invalid: it must be a boolean.", $id, $extension));
    }
    $this->header = $header;
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
   * @return \Drupal\Core\Extension\Extension
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
   * Returns an array of Pattern Library metadata, keyed by paths.
   *
   *   This metadata is typically ingested from YAML files.
   *
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
   * @param string|array|false $override
   *   A string overrides the entire library.
   *   An array overrides a single asset.
   *   FALSE requests no overrides, but may be ignored by other modules.
   *
   * @return \Drupal\patternkit\PatternLibrary
   *   The updated PatternLibrary with overrides present.
   */
  public function setOverride($override): PatternLibrary {
    $this->override = $override;
    return $this;
  }

  /**
   * Sets info for the pattern library, keyed by path.
   *
   * @param array $info
   *   - string plugin
   *   - string category
   *   An array of pattern info for the current set of patterns being processed.
   *
   * @todo Validate and provide helpful feedback for Pattern Library Info.
   */
  public function setPatternInfo(array $info) {
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
