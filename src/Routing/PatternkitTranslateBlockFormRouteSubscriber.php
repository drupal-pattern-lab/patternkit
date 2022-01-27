<?php

namespace Drupal\patternkit\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class PatternkitTranslateBlockFormRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('layout_builder.translate_block')) {
      $route->setDefault('_form', '\Drupal\patternkit\Form\PatternkitTranslateBlockForm');
    }
  }
}
