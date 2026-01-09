<?php

namespace Drupal\patternkit_media_library;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for retrieving media attributes by file URL.
 */
class MediaAttributeService implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Cached mapping of media type bundles to their source field names.
   *
   * @var array<string, string>|null
   */
  protected ?array $mediaTypeSourceFieldMapping = NULL;

  /**
   * Constructs a MediaAttributeService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The entity type bundle info service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected EntityTypeBundleInfoInterface $bundleInfo,
    protected RequestStack $requestStack,
  ) {}

  /**
   * Get media attributes by file URL.
   *
   * @param string $url
   *   The file URL to look up.
   *
   * @return array
   *   An associative array with media attributes:
   *   - alt: Alt text from the media source field
   *   - width: Image width
   *   - height: Image height
   *   - title: Image title
   *   Returns empty array if no matching media found.
   */
  public function getAttributesByUrl(string $url): array {
    try {
      // Find the file entity that matches this URL.
      $file = $this->findFileByUrl($url);
      if (!$file) {
        return [];
      }

      // Find the first media entity that references this file.
      $media = $this->findMediaByFile($file);
      if (!$media) {
        return [];
      }

      // Extract attributes from the media source field.
      return $this->extractMediaAttributes($media);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get media attributes for URL @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Find a file entity by URL.
   *
   * @param string $url
   *   The URL to match.
   *
   * @return \Drupal\file\FileInterface|null
   *   The matching file entity or NULL if not found.
   */
  protected function findFileByUrl(string $url): ?FileInterface {
    $file_storage = $this->entityTypeManager->getStorage('file');

    // Normalize the URL by removing query parameters, fragments,
    // and trailing slashes.
    $normalized_url = $this->normalizeUrl($url);

    // Extract possible file URIs from the URL path.
    $possible_uris = $this->extractPossibleFileUris($normalized_url);

    // If the URL is not a relative link, assume it doesn't reference a local
    // file and bypass further processing. Relative URLs start with '/'.
    if (!str_starts_with($normalized_url, '/')) {
      // If no local file patterns match, this is an external URL - skip
      // processing.
      if (empty($possible_uris)) {
        return NULL;
      }
    }

    // Query files by URI for each possible URI.
    foreach ($possible_uris as $uri) {
      $fids = $file_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uri', $uri)
        ->execute();

      if (!empty($fids)) {
        $file = $file_storage->load(reset($fids));
        if ($file instanceof FileInterface) {
          // Verify the generated URL matches the input URL.
          $generated_relative = $this->fileUrlGenerator
            ->generateString($file->getFileUri());
          if ($file->hasLinkTemplate('canonical')) {
            $generated_absolute = $file->toUrl()
              ->setAbsolute(TRUE)
              ->toString();
          }
          else {
            $generated_absolute = $generated_relative;
          }
          if ($generated_relative === $normalized_url ||
              $generated_absolute === $normalized_url) {
            return $file;
          }
        }
      }
    }

    // Fallback: If URI-based lookup failed, try a limited search for
    // matching URLs.
    // This is less efficient but handles edge cases.
    return $this->findFileByUrlFallback($normalized_url);
  }

  /**
   * Normalize a URL for comparison.
   *
   * @param string $url
   *   The URL to normalize.
   *
   * @return string
   *   The normalized URL (relative path if it matches the local site).
   */
  protected function normalizeUrl(string $url): string {
    // Use parse_url() to extract URL components, excluding query and fragment.
    $parsed = parse_url($url);
    if (!$parsed) {
      // If parsing fails, fall back to simple string operations.
      return rtrim(strtok(strtok($url, '?'), '#'), '/');
    }

    // Check if this is an absolute URL pointing to the local site.
    if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
      $relative_path = $this->stripLocalBaseUrl($parsed);
      if ($relative_path !== NULL) {
        return $relative_path;
      }
    }

    // For relative URLs or external URLs, rebuild from parsed components.
    $normalized = '';
    if (!empty($parsed['scheme'])) {
      $normalized .= $parsed['scheme'] . '://';
    }
    if (!empty($parsed['host'])) {
      $normalized .= $parsed['host'];
    }
    if (!empty($parsed['port'])) {
      $normalized .= ':' . $parsed['port'];
    }
    if (!empty($parsed['path'])) {
      $normalized .= rtrim($parsed['path'], '/');
    }

    return $normalized;
  }

  /**
   * Strip the local site's base URL from an absolute URL if it matches.
   *
   * @param array $parsed
   *   A parsed URL array from parse_url().
   *
   * @return string|null
   *   The relative path if the URL matches the local site's base URL,
   *   or NULL if it doesn't match or no request is available.
   */
  protected function stripLocalBaseUrl(array $parsed): ?string {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return NULL;
    }

    $base_scheme_and_host = $request->getSchemeAndHttpHost();
    $base_port = $request->getPort();

    // If getPort() returns NULL, try to get port from SERVER_PORT directly.
    // This handles cases where HTTP_HOST doesn't include the port.
    if ($base_port === NULL) {
      $server_port = $request->server->get('SERVER_PORT');
      if ($server_port) {
        $base_port = (int) $server_port;
      }
    }

    // Extract scheme and host from getSchemeAndHttpHost().
    // Note: getSchemeAndHttpHost() may include the port if the request was
    // created with a URL that includes a port, so we need to parse it.
    $base_parsed = parse_url($base_scheme_and_host);
    if (!$base_parsed) {
      // Fallback: assume base_scheme_and_host is just scheme://host.
      $base_parsed = ['scheme' => $request->getScheme(), 'host' => $request->getHost()];
    }

    // Build the base URL including port if it's non-standard.
    // Standard ports (80 for HTTP, 443 for HTTPS) are implied and not
    // included.
    $base_url_with_port = $base_parsed['scheme'] . '://' . $base_parsed['host'];
    if ($base_port && !in_array($base_port, [80, 443])) {
      $base_url_with_port .= ':' . $base_port;
    }

    // Build the URL's base (scheme + host + port).
    // If the URL has a standard port (80 or 443), normalize it by excluding
    // the port for comparison.
    $url_base = $parsed['scheme'] . '://' . $parsed['host'];
    $url_port = $parsed['port'] ?? NULL;
    if ($url_port && !in_array($url_port, [80, 443])) {
      $url_base .= ':' . $url_port;
    }

    // Compare normalized base URLs (standard ports are treated
    // equivalently).
    if ($url_base === $base_url_with_port && !empty($parsed['path'])) {
      // Strip the base URL and return just the path as a relative URL.
      $path = rtrim($parsed['path'], '/');
      return $path ?: '/';
    }

    return NULL;
  }

  /**
   * Extract possible file URIs from a URL.
   *
   * @param string $url
   *   The normalized URL.
   *
   * @return array
   *   An array of possible file URIs.
   *
   * @todo Explore methods to improve efficiency of this method.
   */
  protected function extractPossibleFileUris(string $url): array {
    $uris = [];

    // Parse the URL to extract the path.
    $parsed_url = parse_url($url);
    if (!$parsed_url || !isset($parsed_url['path'])) {
      return $uris;
    }

    $path = $parsed_url['path'];

    // Common patterns for file URLs in Drupal.
    // Pattern 1: /sites/default/files/... -> public://...
    if (preg_match('#/sites/[^/]+/files/(.+)$#', $path, $matches)) {
      $uris[] = 'public://' . $matches[1];
    }

    // Pattern 2: /system/files/... -> public://...
    // (for private files served via system)
    if (preg_match('#/system/files/(.+)$#', $path, $matches)) {
      $uris[] = 'public://' . $matches[1];
      $uris[] = 'private://' . $matches[1];
    }

    // Pattern 3: Direct path that might match public://.
    if (preg_match('#^/(.+)$#', $path, $matches)) {
      $uris[] = 'public://' . $matches[1];
    }

    // Pattern 4: Try removing common prefixes.
    $common_prefixes = ['/files/', '/sites/default/files/', '/system/files/'];
    foreach ($common_prefixes as $prefix) {
      if (str_starts_with($path, $prefix)) {
        $relative_path = substr($path, strlen($prefix));
        $uris[] = 'public://' . $relative_path;
      }
    }

    // Remove duplicates and empty values.
    return array_filter(array_unique($uris));
  }

  /**
   * Fallback method to find file by URL when URI-based lookup fails.
   *
   * @param string $normalized_url
   *   The normalized URL to match.
   *
   * @return \Drupal\file\FileInterface|null
   *   The matching file entity or NULL if not found.
   */
  protected function findFileByUrlFallback(string $normalized_url): ?FileInterface {
    $file_storage = $this->entityTypeManager->getStorage('file');

    // Limit the fallback search to a reasonable number to avoid
    // performance issues.
    // This is a compromise for edge cases where URI extraction fails.
    $fids = $file_storage->getQuery()
      ->accessCheck(FALSE)
      ->range(0, 1000)
      ->execute();

    if (empty($fids)) {
      return NULL;
    }

    foreach ($file_storage->loadMultiple($fids) as $file) {
      if (!$file instanceof FileInterface) {
        continue;
      }

      // Generate URLs for this file and compare.
      $generated_relative = $this->fileUrlGenerator
        ->generateString($file->getFileUri());
      if ($file->hasLinkTemplate('canonical')) {
        $generated_absolute = $file->toUrl()
          ->setAbsolute(TRUE)
          ->toString();
      }
      else {
        $generated_absolute = $generated_relative;
      }

      if ($generated_relative === $normalized_url ||
          $generated_absolute === $normalized_url) {
        return $file;
      }
    }

    return NULL;
  }

  /**
   * Find a media entity that references the given file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to find media for.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The first matching media entity or NULL if not found.
   *
   * @todo Consider optimizing this by limiting media types based on contextual
   *   data about the field being populated. For example:
   *   - If populating an image field, filter to only media types enabled for
   *     images in patternkit_media_library.settings (media_mapping.image_types)
   *   - If populating a video field, filter to only media types enabled
   *     for videos (media_mapping.video_types)
   *   This would reduce the number of queries needed when the field type is
   *   known.
   */
  protected function findMediaByFile(FileInterface $file): ?MediaInterface {
    $media_storage = $this->entityTypeManager->getStorage('media');

    // Get mapping of media type bundles to their source field names.
    $source_field_mapping = $this->getMediaTypeSourceFieldMapping();

    // Query media entities for each media type that has a source
    // field.
    foreach ($source_field_mapping as $bundle => $source_field_name) {

      // Query media entities of this type where the source field
      // references our file.
      $media_ids = $media_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', $bundle)
        ->condition(
          $source_field_name . '.target_id',
          $file->id()
        )
        ->range(0, 1)
        ->execute();

      if (!empty($media_ids)) {
        $media = $media_storage->load(reset($media_ids));
        if ($media instanceof MediaInterface) {
          return $media;
        }
      }
    }

    return NULL;
  }

  /**
   * Get mapping of media type bundles to their source field names.
   *
   * @return array<string, string>
   *   An associative array mapping media type bundle names to their source
   *   field names. Only bundles with valid source fields are included.
   *
   * @todo Replace the per-request caching in this method with loading of cached
   *   values from Drupal cache to persist across multiple requests.
   */
  protected function getMediaTypeSourceFieldMapping(): array {
    // Return cached mapping if available.
    if ($this->mediaTypeSourceFieldMapping !== NULL) {
      return $this->mediaTypeSourceFieldMapping;
    }

    // Build the mapping.
    $mapping = [];
    $bundle_info = $this->bundleInfo->getBundleInfo('media');

    foreach (array_keys($bundle_info) as $bundle) {
      $source_field_name = $this->getSourceFieldNameForMediaType($bundle);
      if ($source_field_name) {
        $mapping[$bundle] = $source_field_name;
      }
    }

    // Cache the mapping for the lifetime of this service instance.
    $this->mediaTypeSourceFieldMapping = $mapping;

    return $mapping;
  }

  /**
   * Get the source field name for a media type bundle.
   *
   * @param string $bundle
   *   The media type bundle name.
   *
   * @return string|null
   *   The source field name for the media type, or NULL if not found or
   *   invalid.
   */
  protected function getSourceFieldNameForMediaType(string $bundle): ?string {
    $media_type_storage = $this->entityTypeManager->getStorage('media_type');
    $media_type = $media_type_storage->load($bundle);
    if (!$media_type) {
      return NULL;
    }

    $source = $media_type->getSource();
    if (!$source) {
      return NULL;
    }

    $source_configuration = $source->getConfiguration();
    return is_array($source_configuration) ? $source_configuration['source_field'] ?? NULL : NULL;
  }

  /**
   * Extract attributes from a media entity's source field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return array
   *   An associative array with extracted attributes.
   */
  protected function extractMediaAttributes(MediaInterface $media): array {
    $attributes = [];

    // Get the source field configuration to find the field name.
    $source_configuration = $media->getSource()?->getConfiguration();
    $source_field_name = is_array($source_configuration) ? $source_configuration['source_field'] : NULL;

    if ($source_field_name === NULL) {
      return $attributes;
    }

    // Get the source field value.
    $source_field_value = $media->get($source_field_name)?->first()?->getValue();
    if (!$source_field_value) {
      return $attributes;
    }

    // Extract attributes, filtering out empty values and target_id.
    foreach ($source_field_value as $key => $value) {
      if ($key !== 'target_id' && !empty($value)) {
        $attributes[$key] = $value;
      }
    }

    return $attributes;
  }

}
