<?php

namespace Drupal\patternkit\StreamWrapper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a Drupal library (library://) stream wrapper class.
 *
 * Provides support for retrieving library files.
 */
class LibraryStream extends PublicStream {

  use StringTranslationTrait;

  /**
   * Override to interpret the uri as the actual library file.
   *
   * {@inheritdoc}
   */
  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    [, $path] = explode('://', $uri, 2);

    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    $uri = trim($path, '\/');

    /** @var \Drupal\Core\Asset\AssetResolverInterface $asset_resolver */
    $asset_resolver = \Drupal::service('asset.resolver');
    [$extension, $library, $type_level] = explode('/', $uri);
    // @todo Allow retrieving specific filenames and css levels.
    $type = strstr($type_level, '.', TRUE) ?: $type_level;
    // Resolve the attached libraries into asset collections.
    $assets = new AttachedAssets();
    $assets->setLibraries(["$extension/$library"]);
    switch ($type) {
      case 'js':
        $js_header_footer = $asset_resolver->getJsAssets($assets, TRUE);
        $query_assets = array_merge($js_header_footer[0], $js_header_footer[1]);
        break;

      case 'css':
        $query_assets = $asset_resolver->getCssAssets($assets, TRUE);
        break;

      default:
        $js = $asset_resolver->getJsAssets($assets, TRUE);
        $css = $asset_resolver->getCssAssets($assets, TRUE);
        // @todo Create a temporary {libraryname}.json that holds the data.
        return ['js' => $js, 'css' => $css];
    }

    $query_assets_first_item = reset($query_assets)['data'];
    // In Drupal 10.1+, this asset path will be a relative URL to the file.
    if (str_starts_with($query_assets_first_item, '/')) {
      return $query_assets_first_item;
    }
    // Before Drupal 10.1, the asset path is a URI to the Drupal public files
    // directory, e.g., "public://path/to/file.css".
    else {
      return explode('://', $query_assets_first_item, 2)[1];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Library files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Library files for usage in processing and rendering.');
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());

    // For Drupal 10.1+, the path is the relative URL to the asset, so we only
    // need to prepend the base URL, to get the absolute URL.
    if (str_starts_with($path, '/')) {
      // Does not encode the path because it already contains the query string
      // (encoding '?' breaks the query string).
      return $GLOBALS['base_url'] . $path;
    }
    // Before Drupal 10.1, the path is the URI to the file in the public files
    // directory, so we have to encode that path and convert it to a URL.
    else {
      return static::baseUrl() . '/' . UrlHelper::encodePath($path);
    }
  }

}
