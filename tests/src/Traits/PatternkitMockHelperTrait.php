<?php

namespace Drupal\Tests\patternkit\Traits;

use Drupal\block\BlockInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock as EntityPatternkitBlock;
use Drupal\patternkit\Plugin\Block\PatternkitBlock as BlockPatternkitBlock;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Provides methods to create and place patternkit blocks.
 *
 * This trait is meant to be used only by test classes.
 */
trait PatternkitMockHelperTrait {

  use ProphecyTrait;

  /**
   * Get a mock patternkit block plugin with given configuration.
   *
   * @param array $values
   *   An associative array of configuration values to assign to the mock's
   *   related methods.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Plugin\Block\PatternkitBlock
   *   A configured prophecy for the patternkit block plugin.
   *
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  protected function getMockPatternkitBlockPlugin(array $values = []): ObjectProphecy {
    $plugin = $this->prophesize(BlockPatternkitBlock::class);

    $map = [
      'plugin_id' => 'getPluginId',
      'derivative_id' => 'getDerivativeId',
      'configuration' => 'getConfiguration',
      'block' => 'getBlockEntity',
      'pattern' => 'getPatternEntity',
      'base_pattern' => 'getBasePattern',
      'pattern_id' => 'getPatternId',
    ];

    // Mock nested elements if configuration is passed in for them.
    if (isset($values['block']) && is_array($values['block'])) {
      $values['block'] = $this->getMockPatternkitBlockEntity($values['block']);
    }
    if (isset($values['pattern']) && is_array($values['pattern'])) {
      $values['pattern'] = $this->getMockPatternEntity($values['pattern']);
    }
    if (isset($values['base_pattern']) && is_array($values['base_pattern'])) {
      $values['base_pattern'] = $this->getMockPatternEntity($values['base_pattern']);
    }

    // Assign known values for inspection functions.
    $plugin = $this->mapValuesToMethods($plugin, $map, $values);

    // Mock assignments for realistic behavior.
    $plugin->setConfiguration(Argument::type('array'))
      ->will(function (array $args) {
        /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Plugin\Block\PatternkitBlock $this */
        $this->getConfiguration()->willReturn($args[0]);
      });

    return $plugin;
  }

  /**
   * Get a mocked patternkit block entity with given configuration.
   *
   * @param array $values
   *   An associative array of configuration values to assign to the mock's
   *   related methods.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternkitBlock
   *   A configured prophecy for the patternkit block entity.
   *
   * @see \Drupal\patternkit\Entity\PatternkitBlock
   */
  protected function getMockPatternkitBlockEntity(array $values = []): ObjectProphecy {
    $map = [
      'pattern_id' => 'getPattern',
      'data' => 'getData',
    ];

    $defaults = [
      'is_new' => FALSE,
    ];

    /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $this->getEntityProphecy(
      EntityPatternkitBlock::class,
      $map,
      $values + $defaults
    );

    // Automatically make data string available through get() as well.
    if (isset($values['data'])) {
      $value_object = (object) [
        'value' => json_encode($values['data']),
      ];
      $block->get('data')->willReturn($value_object);
    }

    // Configure assignment behaviors for realistic interactions.
    $block->setPattern(Argument::type('string'))
      ->will(function (array $args) {
        /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternkitBlock $this */
        $this->getPattern()->willReturn($args[0]);
      });

    // Mock a list of fields to be returned.
    // @see \Drupal\patternkit\Entity\PatternkitBlock::baseFieldDefinitions()
    $block->getFields()->willReturn(array_flip([
      'id',
      'uuid',
      'revision_id',
      'langcode',
      'revision_log',
      'info',
      'changed',
      'pattern_id',
      'data',
      'reusable',
    ]));

    return $block;
  }

  /**
   * Get a mocked pattern entity with given configuration.
   *
   * @param array $values
   *   An associative array of configuration values to assign to the mock's
   *   related methods.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface
   *   A configured prophecy for the patternkit block entity.
   *
   * @see \Drupal\patternkit\Entity\PatternInterface
   */
  protected function getMockPatternEntity(array $values = []): ObjectProphecy {
    $map = [
      'hash' => 'getHash',
      'asset_id' => 'getAssetId',
      'name' => 'getAssetId',
      'version' => 'getVersion',
      'library' => 'getLibrary',
      'category' => 'getCategory',
      'description' => 'getDescription',
      'library_plugin' => 'getLibraryPluginId',
      'path' => 'getPath',
      'schema' => 'getSchema',
      'template' => 'getTemplate',
      'dependencies' => 'getDependencies',
      'is_bundled' => 'isBundled',
    ];

    $defaults = [
      'is_new' => FALSE,
      'library_plugin' => 'twig',
    ];

    $pattern = $this->getEntityProphecy(
      PatternInterface::class,
      $map,
      $values + $defaults
    );

    // Configure assignment behaviors for realistic interactions.
    // @todo Configure more setter methods as needed for tests.
    $pattern->setBundled()->will(function (array $args) {
      /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $this */
      $this->isBundled()->willReturn($args[0] ?? TRUE);
    });

    return $pattern;
  }

  /**
   * Create a specified mock component type as indicated by the $type parameter.
   *
   * This is intended to provide an easier programmatic method for generating
   * different mock types through a test case argument without having to include
   * the conditional logic in the calling function.
   *
   * @param string $type
   *   The type of component to be mocked. Valid options are "block" or
   *   "layout".
   * @param array $values
   *   An array of configuration values for the mock component.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The configured prophecy mock object.
   *
   * @see \Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait::getMockBlockComponent()
   * @see \Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait::getMockLayoutBuilderComponent()
   */
  protected function getMockComponent(string $type, array $values = []): ObjectProphecy {
    assert(in_array($type, ['block', 'layout']), 'Only "block" or "layout" are allowed for component type selections.');

    switch ($type) {
      case 'block':
        return $this->getMockBlockComponent($values);

      case 'layout':
        return $this->getMockLayoutBuilderComponent($values);
    }
  }

  /**
   * Get a configured prophecy for a block instance using patternkit.
   *
   * This mock will imitate a Patternkit block created through the traditional
   * block UI as opposed to one created in Layout Builder which should instead
   * be mocked using
   * @link \Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait::getMockLayoutBuilderComponent @endlink.
   *
   * @param array $values
   *   An associative array of configuration values to assign to the mock's
   *   related methods.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy|\Drupal\block\BlockInterface
   *   A configured prophecy for the block component.
   *
   * @see \Drupal\block\BlockInterface
   * @see \Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait::getMockLayoutBuilderComponent()
   * @see \Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait::getMockComponent()
   */
  protected function getMockBlockComponent(array $values = []): ObjectProphecy {
    $component = $this->prophesize(BlockInterface::class);

    $map = [
      'plugin_id' => 'getPluginId',
      'uuid' => 'uuid',
      'theme' => 'getTheme',
      'weight' => 'getWeight',
      'plugin' => 'getPlugin',
    ];

    // Mock a nested plugin if configuration is passed in for it.
    if (isset($values['plugin']) && is_array($values['plugin'])) {
      $values['plugin'] = $this->getMockPatternkitBlockPlugin($values['plugin']);
    }

    // Handle values assigned with 'set()' manually, so they will need to be
    // excluded from the map assignment to avoid throwing an exception.
    $map_values = array_diff_key($values, array_flip([
      'configuration',
    ]));
    /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\block\BlockInterface $component */
    $component = $this->mapValuesToMethods($component, $map, $map_values);

    if (isset($values['configuration'])) {
      $component->get('configuration')->willReturn($values['configuration']);
    }

    // Configure assignment behaviors for realistic interactions.
    $component->set('configuration', Argument::type('array'))
      ->will(function (array $args) {
        /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\block\BlockInterface $this */
        $this->get('configuration')->willReturn($args[1]);
      });
    $component->set(Argument::type('string'), Argument::any())
      ->will(function (array $args) {
        /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\block\BlockInterface $this */
        $this->get($args[0])->willReturn($args[1]);
      });

    return $component;
  }

  /**
   * Get a configured prophecy for a layout builder patternkit block.
   *
   * This mock will imitate a Patternkit block created through the Layout
   * Builder interface as opposed to one created through the traditional block
   * UI which should instead be mocked using
   * @link \Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait::getMockBlockComponent() @endlink.
   *
   * @param array $values
   *   An associative array of configuration values to assign to the mock's
   *   related methods.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy|\Drupal\layout_builder\SectionComponent
   *   A configured prophecy for the block component.
   *
   * @see \Drupal\layout_builder\SectionComponent
   * @see \Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait::getMockBlockComponent()
   * @see \Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait::getMockComponent()
   */
  protected function getMockLayoutBuilderComponent(array $values = []): ObjectProphecy {
    $component = $this->prophesize(SectionComponent::class);

    $map = [
      'plugin_id' => 'getPluginId',
      'theme' => 'getTheme',
      'weight' => 'getWeight',
      'plugin' => 'getPlugin',
      'uuid' => 'getUuid',
      'configuration' => 'getConfiguration',
    ];

    // Mock a nested plugin if configuration is passed in for it.
    if (isset($values['plugin']) && is_array($values['plugin'])) {
      $values['plugin'] = $this->getMockPatternkitBlockPlugin($values['plugin']);
    }

    /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\layout_builder\SectionComponent $component */
    $component = $this->mapValuesToMethods($component, $map, $values);

    // Configure multiple ways to access configuration if provided.
    if (isset($values['configuration'])) {
      $component->get('configuration')->willReturn($values['configuration']);
    }

    // Configure assignment behaviors for realistic interactions.
    $component->set('configuration', Argument::type('array'))
      ->will(function (array $args) {
        /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\layout_builder\SectionComponent $this */
        $this->get('configuration')->willReturn($args[1]);
        $this->getConfiguration()->willReturn($args[1]);
      });
    $component->setConfiguration(Argument::type('array'))
      ->will(function (array $args) {
        /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\layout_builder\SectionComponent $this */
        $this->get('configuration')->willReturn($args[1]);
        $this->getConfiguration()->willReturn($args[1]);
      });
    $component->set(Argument::type('string'), Argument::any())
      ->will(function (array $args) {
        /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\layout_builder\SectionComponent $this */
        $this->get($args[0])->willReturn($args[1]);
      });

    return $component;
  }

  /**
   * Mock an entity with support to configure common inspection values.
   *
   * @param string $class
   *   The class name of the entity type to be mocked. This class is expected to
   *   extend @link \Drupal\Core\Entity\EntityInterface@endlink.
   * @param string[] $map
   *   An associative array of methods for configuration keyed by a shared key
   *   in the $values parameter.
   * @param array $values
   *   (optional) An array of values to map onto the entity mock.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy|\Drupal\Core\Entity\EntityInterface
   *   The configured prophecy for the specified entity class.
   */
  private function getEntityProphecy(string $class, array $map = [], array $values = []): ObjectProphecy {
    assert(is_a($class, EntityInterface::class, TRUE));
    $entity = $this->prophesize($class);

    // Configure basic inspection methods with assigned values.
    $map += [
      'id' => 'id',
      'revision_id' => 'getRevisionId',
      'language' => 'language',
      'uuid' => 'uuid',
      'is_new' => 'isNew',
      'entity_type' => 'getEntityTypeId',
      'bundle' => 'bundle',
      'label' => 'label',
      'contexts' => 'getCacheContexts',
      'tags' => 'getCacheTags',
      'max-age' => 'getCacheMaxAge',
    ];
    $entity = $this->mapValuesToMethods($entity, $map, $values);

    // NB. This is not returning the SAVED_NEW constant because it is not
    // loaded from common.inc during unit test execution.
    $entity->save()->willReturn(1);

    return $entity;
  }

  /**
   * Map values onto specified inspection methods for a prophecy.
   *
   * @param \Prophecy\Prophecy\ObjectProphecy $prophecy
   *   The instantiated prophecy to be configured.
   * @param string[] $map
   *   An associative array of methods for configuration keyed by a shared key
   *   in the $values parameter.
   * @param array $values
   *   An associative array of values to map onto the prophecy by assigning the
   *   values as returns for the method indicated by the related entry in the
   *   $map parameter.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The configured prophecy object.
   */
  private function mapValuesToMethods(ObjectProphecy $prophecy, array $map, array $values): ObjectProphecy {
    foreach ($values as $key => $value) {
      if (isset($map[$key])) {
        $prophecy->{$map[$key]}()->willReturn($value);
      }
      else {
        throw new \RuntimeException(sprintf('Unknown configuration value "%s" for mocking.', $key));
      }
    }

    return $prophecy;
  }

}
