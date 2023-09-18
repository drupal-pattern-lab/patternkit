<?php

namespace Drupal\patternkit;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\layout_builder\DefaultsSectionStorageInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to entity events related to Patternkit Blocks.
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 *
 * @todo Implement usage tracking as seen in \Drupal\layout_builder\InlineBlockEntityOperations.
 * @todo Remove unused block entities on save.
 */
class PatternkitBlockEntityOperations implements ContainerInjectionInterface {

  use LayoutEntityHelperTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Entity storage for patternkit_pattern entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $patternStorage;

  /**
   * Entity storage for patternkit_block entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $blockStorage;

  /**
   * Constructs a new PatternkitBlockEntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   *   The section storage manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, SectionStorageManagerInterface $section_storage_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->patternStorage = $entityTypeManager->getStorage('patternkit_pattern');
    $this->blockStorage = $entityTypeManager->getStorage('patternkit_block');
    $this->sectionStorageManager = $section_storage_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.layout_builder.section_storage')
    );
  }

  /**
   * Handles saving a parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::updateBlockEntity()
   * @see \patternkit_entity_presave()
   * @see \Drupal\layout_builder\InlineBlockEntityOperations::handlePreSave()
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::blockSubmit()
   *
   * @todo Add removal handling for deleted blocks.
   * @todo Add usage tracking for block components and patterns.
   */
  public function handlePreSave(EntityInterface $entity): void {
    if (!$this->isLayoutCompatibleEntity($entity)) {
      return;
    }
    $duplicate_blocks = FALSE;

    $storage = $this->getSectionStorageForEntity($entity);
    if ($sections = $storage->getSections()) {
      if ($this->originalEntityUsesDefaultStorage($entity)) {
        // This is a new override from a default and the blocks need to be
        // duplicated.
        $duplicate_blocks = TRUE;
      }
      // Since multiple parent entity revisions may reference common block
      // revisions, when a block is modified, it must always result in the
      // creation of a new block revision.
      $new_revision = $entity instanceof RevisionableInterface;
      foreach ($this->getPatternBlockComponents($sections) as $component) {
        $this->saveBlockComponent($entity, $component, $new_revision, $duplicate_blocks);
      }
    }

    // @todo Implement removal of unused entities.
    // $this->removeUnusedForEntityOnSave($entity);
  }

  /**
   * Gets components that have Patternkit Block plugins.
   *
   * @param \Drupal\layout_builder\Section[] $sections
   *   The layout sections.
   *
   * @return \Drupal\layout_builder\SectionComponent[]
   *   The components that contain Patternkit Block plugins.
   */
  protected function getPatternBlockComponents(array $sections): array {
    $block_components = [];
    foreach ($sections as $section) {
      foreach ($section->getComponents() as $component) {
        $plugin = $component->getPlugin();
        if ($plugin instanceof DerivativeInspectionInterface && $plugin->getBaseId() === 'patternkit_block') {
          $block_components[] = $component;
        }
      }
    }
    return $block_components;
  }

  /**
   * Saves an inline block component.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the layout.
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The section component with an inline block.
   * @param bool $new_revision
   *   Whether a new revision of the block should be created when modified.
   * @param bool $duplicate_blocks
   *   Whether the blocks should be duplicated.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function saveBlockComponent(EntityInterface $entity, SectionComponent $component, $new_revision, $duplicate_blocks): void {
    /** @var \Drupal\patternkit\Plugin\Block\PatternkitBlock $plugin */
    $plugin = $component->getPlugin();
    $plugin->cachePatternEntity();
    $plugin->saveBlockContent($new_revision, $duplicate_blocks);
    $post_save_configuration = $plugin->getConfiguration();
    // @todo Record in usage tracking.
    $component->setConfiguration($post_save_configuration);
  }

}
