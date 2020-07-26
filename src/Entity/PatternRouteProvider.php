<?php

namespace Drupal\patternkit\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for nodes.
 */
class PatternRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/patternkit/{pattern}'))
      ->addDefaults([
        '_controller' => '\Drupal\patternkit\Controller\PatternkitController::patternView',
        '_title_callback' => '\Drupal\patternkit\Controller\PatternkitController::patternTitle',
      ])
      ->setRequirement('pattern', '\d+')
      ->setRequirement('_entity_access', 'pattern.view');
    $route_collection->add('entity.pattern.canonical', $route);

    return $route_collection;
  }

}
