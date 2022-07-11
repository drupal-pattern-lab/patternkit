<?php

namespace Drupal\Tests\patternkit\Traits;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormState;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\SectionComponent;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\patternkit\Plugin\Block\PatternkitBlock as BlockPatternkitBlock;
use Drupal\patternkit\Plugin\Derivative\PatternkitBlock as DerivativePatternkitBlock;

/**
 * Provides methods to create and place patternkit blocks.
 *
 * This trait is meant to be used only by test classes.
 */
trait PatternkitBlockHelperTrait {

  use LayoutEntityHelperTrait;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface|null
   */
  protected ?BlockManagerInterface $blockManager = NULL;

  /**
   * Create a new pattern block plugin with underlying data as necessary.
   *
   * @param string $pattern_id
   *   The namespaced pattern ID to use for the pattern block.
   * @param string|array $config
   *   The JSON Editor configuration values to save for the block. If an array
   *   is provided, it will be encoded before saving. A string directly may be
   *   provided and will be used as-is.
   *
   * @return \Drupal\patternkit\Plugin\Block\PatternkitBlock
   *   An instantiated and configured PatternkitBlock instance with underlying
   *   pattern entity caching completed and referenced appropriately.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  protected function createPatternBlock(string $pattern_id, $config = []): PatternkitBlock {
    // Encode to JSON if we got an array. Accept a string directly to support
    // block creation with invalid configuration values if necessary.
    if (is_array($config)) {
      $config = json_encode($config, JSON_THROW_ON_ERROR);
    }

    $derivative_id = DerivativePatternkitBlock::assetToDerivativeId($pattern_id);
    $plugin_id = "patternkit_block:$derivative_id";
    /** @var \Drupal\patternkit\Plugin\Block\PatternkitBlock $block */
    $block = $this->getBlockManager()->createInstance($plugin_id);

    // Fake the form state to run submission handling.
    $form_state = new FormState();
    $form_state->setValues([
      'instance_config' => $config,
      'label' => '',
      'reusable' => FALSE,
      'presentation_style' => 'html',
      'version' => NULL,
    ]);
    $block->blockSubmit([], $form_state);

    return $block;
  }

  /**
   * Place a block in an entity layout with layout builder enabled.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to place the block into the layout display of.
   * @param \Drupal\patternkit\Plugin\Block\PatternkitBlock $block
   *   The block instance to place in the layout.
   */
  protected function placePatternBlockInLayout(EntityInterface $entity, BlockPatternkitBlock $block): void {
    $storage = $this->getSectionStorageForEntity($entity);
    $section = $storage->getSection(0);

    $config = $block->getConfiguration();
    unset($config['instance_uuid']);
    $component = new SectionComponent($entity->uuid(), 'content', $config);
    $section->appendComponent($component);

    $storage->save();
  }

  /**
   * Get the block manager service.
   *
   * @return \Drupal\Core\Block\BlockManagerInterface
   *   The block manager service.
   */
  private function getBlockManager(): BlockManagerInterface {
    return $this->blockManager ?: $this->blockManager = \Drupal::service('plugin.manager.block');
  }

}
