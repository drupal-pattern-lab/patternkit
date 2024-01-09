<?php

namespace Drupal\patternkit_usage_tracking;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Block\Plugin\Block\Broken;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\SectionListInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\patternkit\Utility\PatternDetector;
use Drupal\patternkit_usage_tracking\Entity\PatternUsage;

/**
 * A trait for discovering pattern usages within a layout.
 */
trait PatternLayoutDiscoveryTrait {

  use LayoutEntityHelperTrait;

  /**
   * The Patternkit layout helper service.
   *
   * @var \Drupal\patternkit\LayoutHelper
   */
  protected LayoutHelper $layoutHelper;

  /**
   * The pattern detector service for scanning content.
   *
   * @var \Drupal\patternkit\Utility\PatternDetector
   */
  protected PatternDetector $patternDetector;

  /**
   * Identify patterns used within content.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The pattern containing the content to be scanned.
   * @param string $content
   *   The content to be scanned.
   *
   * @return string[]
   *   An array of external pattern usages within the given content.
   *
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  protected function getNestedPatterns(PatternInterface $pattern, string $content): array {
    // Only scan the content if this pattern can contain nested pattern
    // references.
    if (!empty($pattern->getDependencies())) {
      $detected = $this->patternDetector->detect($pattern, $content);
    }

    return $detected ?? [];
  }

  /**
   * Identify patterns within a layout.
   *
   * @param \Drupal\layout_builder\SectionComponent[] $components
   *   An array of pattern components as provided by
   *   @link \Drupal\patternkit\LayoutHelper::getPatternComponentsFromLayout()
   *   @endlink.
   *
   * @return array<string, array{uuid: string, layout_path: string, pattern_id: string, nested: string[]}>
   *   An array of all discovered pattern usages. Each top-level pattern is
   *   keyed by the containing component's UUID, and contains an associative
   *   array of usage data with the following keys:
   *   - uuid: The UUID of the containing component.
   *   - layout_path: The path to the component within the layout in the form
   *       `<section_delta>.<uuid>`.
   *   - pattern_id: The asset name of the pattern contained in the component.
   *   - nested: An array of the names of all pattern usages detected within the
   *       content.
   *
   * @throws \Swaggest\JsonSchema\InvalidValue
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function identifyPatternsByComponent(array $components): array {
    $patterns = [];

    foreach ($components as $component_path => $component) {
      $record = [
        'uuid' => $component->getUuid(),
        'layout_path' => $component_path,
      ];

      try {
        $plugin = $component->getPlugin();
        // Skip this component if the plugin doesn't load successfully.
        if ($plugin instanceof Broken) {
          throw new PluginException('Encountered broken block.');
        }

        assert($plugin instanceof PatternkitBlock);
        $pattern = $plugin->getPatternEntity();
        $block = $plugin->getBlockEntity();
        $content = $block->get('data')->value ?? '';

        // Record patterns discovered within the content, including this pattern
        // itself.
        $record['pattern_id'] = $pattern->getAssetId();
        $record['layout_path'] .= PatternUsage::PATH_SEPARATOR . $record['pattern_id'];
        $record['nested'] = $this->getNestedPatterns($pattern, $content);
      }
      catch (EntityStorageException | PluginException $exception) {
        $record['pattern_id'] = 'Broken';
        $record['nested'] = [];
      }

      $patterns[$record['uuid']] = $record;
    }

    return $patterns;
  }

  /**
   * Discover patterns within layout components of an entity.
   *
   * This method assumes the provided entity has layout overrides enabled.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to load layout from.
   *
   * @return array<string, array{uuid: string, layout_path: string, pattern_id: string, nested: string[]}>
   *   An array of all discovered pattern usages. Each top-level pattern is
   *   keyed by the containing component's UUID, and contains an associative
   *   array of usage data with the following keys:
   *   - uuid: The UUID of the containing component.
   *   - layout_path: The path to the component within the layout in the form
   *     `<section_delta>.<uuid>`.
   *   - pattern_id: The asset name of the pattern contained in the component.
   *   - nested: An array of the names of all pattern usages detected within the
   *     content.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  protected function getPatternsByLayoutComponentByEntity(ContentEntityInterface $entity): array {
    // Get the layout.
    $section_storage = $this->getSectionStorageForEntity($entity);

    if (empty($section_storage)) {
      return [];
    }

    // Identify patterns in the updated entity layout.
    return $this->getPatternsByLayoutComponent($section_storage);
  }

  /**
   * Discover patterns within a given layout.
   *
   * @param \Drupal\layout_builder\SectionListInterface $section_storage
   *   The layout to be inspected.
   *
   * @return array<string, array{uuid: string, layout_path: string, pattern_id: string, nested: string[]}>
   *   An array of all discovered pattern usages. Each top-level pattern is
   *   keyed by the containing component's UUID, and contains an associative
   *   array of usage data with the following keys:
   *   - uuid: The UUID of the containing component.
   *   - layout_path: The path to the component within the layout in the form
   *     `<section_delta>.<uuid>`.
   *   - pattern_id: The asset name of the pattern contained in the component.
   *   - nested: An array of the names of all pattern usages detected within the
   *     content.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  protected function getPatternsByLayoutComponent(SectionListInterface $section_storage): array {
    // Identify patterns in the updated entity layout.
    $components = $this->layoutHelper->getPatternComponentsFromLayout($section_storage);
    return $this->identifyPatternsByComponent($components);
  }

}
