<?php

namespace Drupal\patternkit\Hook;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternkitEnvironmentAwareInterface;
use Drupal\patternkit\PatternkitEnvironmentAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hooks to alter entity definitions and links to support reusable blocks.
 *
 * @deprecated in patternkit:9.1.2 and is removed from patternkit:9.2.0.
 *   Reusable blocks should be removed or replaced before updating.
 * @see https://www.drupal.org/node/3517440
 *
 * @see \Drupal\patternkit\Entity\Pattern
 * @see \Drupal\patternkit\Entity\PatternkitBlock
 * @see \Drupal\patternkit\EventSubscriber\PatternkitReusableBlocksRouteSubscriber
 */
class ReusableBlockHooks implements ContainerInjectionInterface, PatternkitEnvironmentAwareInterface {

  use PatternkitEnvironmentAwareTrait;
  use StringTranslationTrait;

  /**
   * A lazy-loaded value for the enable_reusable_blocks setting.
   *
   * @var bool
   *
   * @see self::enableReusableBlocks()
   */
  private bool $enableReusableBlocks;

  /**
   * Constructs a new ReusableBlockHooks object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(
    protected Connection $database,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static($container->get('database'));
    $instance->setPatternkitEnvironment($container->get('patternkit.environment'));
    $instance->setStringTranslation($container->get('string_translation'));

    return $instance;
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[\Deprecated(
    message: 'Patternkit reusable block support will be removed in Patternkit 9.2.0.',
    since: '9.1.2',
  )]
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    /** @var \Drupal\Core\Entity\ContentEntityType[] $entity_types */
    if ($this->enableReusableBlocks()) {
      $patternkitPattern = &$entity_types['patternkit_pattern'];
      $patternkitBlock = &$entity_types['patternkit_block'];

      $templates = [
        'canonical' => '/block/{patternkit_block}',
        'delete-form' => '/block/{patternkit_block}/delete',
        'edit-form' => '/block/{patternkit_block}',
        'collection' => '/admin/structure/block/patternkit',
        'create' => '/block',
      ];
      foreach ($templates as $template => $path) {
        $patternkitBlock->setLinkTemplate($template, $path);
      }

      $patternkitPattern->setLinkTemplate('collection', '/admin/structure/block/patternkit');
    }
  }

  /**
   * Implements hook_contextual_links_alter().
   */
  #[\Deprecated(
    message: 'Patternkit reusable block support will be removed in Patternkit 9.2.0.',
    since: '9.1.2',
  )]
  #[Hook('contextual_links_alter')]
  public function contextualLinksAlter(array &$links, $group, array $route_parameters): void {
    if ($group === 'patternkit' && !$this->enableReusableBlocks()) {
      unset($links['patternkit.block_edit']);
    }
  }

  /**
   * Implements hook_local_tasks_alter().
   */
  #[\Deprecated(
    message: 'Patternkit reusable block support will be removed in Patternkit 9.2.0.',
    since: '9.1.2',
  )]
  #[Hook('local_tasks_alter')]
  public function localTasksAlter(array &$tasks): void {
    if (!$this->enableReusableBlocks()) {
      unset($tasks['entity.patternkit_block.collection']);
      unset($tasks['entity.patternkit_block.canonical']);
      unset($tasks['entity.patternkit_block.delete_form']);
      unset($tasks['patternkit.list_sub']);
    }
  }

  /**
   * Implements hook_menu_local_actions_alter().
   */
  #[\Deprecated(
    message: 'Patternkit reusable block support will be removed in Patternkit 9.2.0.',
    since: '9.1.2',
  )]
  #[Hook('menu_local_actions_alter')]
  public function menuLocalActionsAlter(array &$local_actions): void {
    if (!$this->enableReusableBlocks()) {
      unset($local_actions['patternkit_add_action']);
    }
  }

  /**
   * Implements hook_requirements().
   *
   * @see \patternkit_requirements()
   */
  public function requirements(string $phase): array {
    // Stop here if we're not checking runtime requirements.
    if ($phase !== 'runtime') {
      return [];
    }

    // Test if reusable blocks exist in the system already.
    $query = $this->database->select('patternkit_field_data')
      ->condition('reusable', 1)
      ->countQuery();
    $count = $query
      ->execute()
      ->fetchField();

    $requirements = [];
    if ($count > 0) {
      $description = $this->formatPlural(
        $count,
        'Reusable Patternkit blocks are to be disabled, but @count exists in the database.',
        'Reusable Patternkit blocks are to be disabled, but there are currently @count existing in the database.',
        ['@count' => $count],
      );

      $warning = [
        'title' => $this->t('Reusable Patternkit blocks are deprecated'),
        'description' => $description,
        'severity' => REQUIREMENT_WARNING,
      ];

      $requirements['patternkit_reusable_blocks_deprecated'] = $warning;
    }

    return $requirements;
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
