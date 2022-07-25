<?php

namespace Drupal\patternkit\Asset;

/**
 * A resolver service to map library namespaces to the providing extension.
 *
 * This allows pattern libraries to have namespaces that exist independently
 * of the modules providing them.
 */
interface LibraryNamespaceResolverInterface {

  /**
   * Get an array of all library definitions keyed by their library namespace.
   *
   * Iterate through all enabled and supported extensions to load library
   * definitions and process with additional contextual and pattern information.
   * After processing the individual definitions, provided library definitions
   * from each extension are aggregated together to form a list of available
   * namespaces independent of the providing extensions. This allows for
   * overriding of discovered library definitions using the same namespaces.
   *
   * These library definitions are amended to include contextual information
   * about the extensions providing them and standardized parsing of the pattern
   * library information.
   *
   * @param bool $reset
   *   Whether to force a reload of the library data. Defaults to FALSE.
   *
   * @return array
   *   An associative array of library definitions keyed by the library's
   *   namespace. Each definition also has additional metadata added to it for
   *   context include the following keys:
   *   - id: The name of the library.
   *   - extension: The name of the extension providing the library.
   *   - extensionType: The type of extension providing the library.
   *   - extensionPath: The path to the extension providing the library.
   *   - namespace: The namespace for the library.
   */
  public function getLibraryDefinitions(bool $reset = FALSE): array;

  /**
   * Get an array of library definitions provided by an extension.
   *
   * These library definitions are amended to include contextual information
   * about the extensions providing them and standardized parsing of the pattern
   * library information.
   *
   * @return array
   *   An associative array of library definitions keyed by the library's
   *   namespace. Each definition also has additional metadata added to it for
   *   context include the following keys:
   *   - id: The name of the library.
   *   - extension: The name of the extension providing the library.
   *   - extensionType: The type of extension providing the library.
   *   - extensionPath: The path to the extension providing the library.
   *   - namespace: The namespace for the library.
   */
  public function getLibrariesByExtension(string $extension_name): array;

  /**
   * Get the library definition matching a given namespace.
   *
   * @param string $namespace
   *   The namespace to be mapped to a library definition. Namespaces are
   *   expected to include a leading '@'. Example: '@patternkit'.
   *
   * @return array|null
   *   The loaded library definition or NULL if one could not be identified.
   */
  public function getLibraryFromNamespace(string $namespace): ?array;

}
