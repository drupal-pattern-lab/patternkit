<?php

namespace Drupal\patternkit\StreamWrapper;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Defines a Drupal library (library://) stream wrapper class.
 *
 * Provides support for retrieving library files.
 */
class LibraryStream extends PublicStream {

  /**
   * Override to interpret the uri as the actual library file.
   *
   * {@inheritDoc}
   */
  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    list(, $path) = explode('://', $uri, 2);

    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    $uri = trim($path, '\/');

    /** @var \Drupal\Core\Asset\AssetResolverInterface $asset_resolver */
    $asset_resolver = \Drupal::service('asset.resolver');
    [$extension, $library, $type_level] = explode('/', $uri);
    // @todo Allow retrieving specific filenames and css levels.
    $filename = substr($uri, strlen($extension . $library . $type_level));
    [$type, $level] = array_pad(explode('.', $type_level), 2, NULL);
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
    list(, $target) = explode('://', reset($query_assets)['data'], 2);
    return $target;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Library files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Library files for usage in processing and rendering.');
  }

}
