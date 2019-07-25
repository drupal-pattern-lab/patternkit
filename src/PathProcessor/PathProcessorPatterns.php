<?php

namespace Drupal\patternkit\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite pattern URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the pattern path to a query parameter on the request.
 */
class PathProcessorPatterns implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request): string {
    if (strpos($path, '/api/patternkit') === 0
      && !$request->query->has('pattern')) {

      $pattern_path = preg_replace('|^\/api\/patternkit\/|', '', $path);
      $request->query->set('pattern', $pattern_path);
      return '/api/patternkit';
    }
    return $path;
  }

}
