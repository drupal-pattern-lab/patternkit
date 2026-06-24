<?php

declare(strict_types=1);

namespace Drupal\patternkit\EventSubscriber;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternkitEnvironmentAwareInterface;
use Drupal\patternkit\PatternkitEnvironmentAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber to conditionally remove reusable block routes.
 *
 * @see \Drupal\patternkit\Hook\ReusableBlockHooks
 *
 * @deprecated in patternkit:9.1.2 and is removed from patternkit:9.2.0.
 *   Reusable blocks should be removed or replaced before updating.
 * @see https://www.drupal.org/node/3517440
 * @see https://www.drupal.org/project/patternkit/issues/3587714
 * @see https://www.drupal.org/project/patternkit/issues/3587730
 */
final class PatternkitReusableBlocksRouteSubscriber extends RouteSubscriberBase implements ContainerInjectionInterface, PatternkitEnvironmentAwareInterface {

  use PatternkitEnvironmentAwareTrait;

  /**
   * A lazy-loaded value for the enable_reusable_blocks setting.
   *
   * @var bool
   *
   * @see self::enableReusableBlocks()
   */
  private bool $enableReusableBlocks;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->setPatternkitEnvironment($container->get('patternkit.environment'));

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if (!$this->enableReusableBlocks()) {
      $collection->remove([
        'entity.patternkit_block.canonical',
        'entity.patternkit_block.collection',
        'entity.patternkit_block.delete_form',
        'entity.patternkit_block.edit_form',
        'patternkit.add_form',
        'patternkit.add_page',
      ]);
    }
  }

  /**
   * Lazy-load fetcher for the enable_reusable_blocks service parameter.
   *
   * @return bool
   *   True if the enable_reusable_blocks parameter is enabled. False otherwise.
   */
  private function enableReusableBlocks(): bool {
    if (!isset($this->enableReusableBlocks)) {
      $this->enableReusableBlocks = $this->getEnvironmentFeature(PatternkitEnvironment::FEATURE_ENABLE_REUSABLE_BLOCKS, FALSE);
    }

    return $this->enableReusableBlocks;
  }

}
