<?php

namespace Drupal\tests\patternkit\Kernel\Entity;

use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;
use Drupal\patternkit\Entity\Pattern;

/**
 * Tests for the Pattern class.
 *
 * @coversDefaultClass \Drupal\patternkit\Entity\Pattern
 */
class PatternTest extends PatternkitKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('patternkit_pattern');
    $this->installConfig(['patternkit']);
  }

  /**
   * Test behavior for getCacheTags.
   *
   * @param bool $isNew
   *   Should the Pattern entity be marked as new?
   * @param bool $isDefault
   *   Should the Pattern revision be marked as the default revision?
   * @param bool|null $entityTagInvalidation
   *   The config value to assign to entity_cache_tag_invalidation. If null,
   *   the config will not be explicitly set and default values will be used.
   * @param bool $shouldReturnEntityTag
   *   Should the pattern instance cache tag be returned?
   * @param bool $shouldReturnAssetTag
   *   Should the pattern asset cache tag be returned?
   *
   * @dataProvider providerGetCacheTags
   *
   * @covers ::getCacheTags
   * @covers ::getCacheTagsToInvalidate
   * @covers ::shouldInvalidateCacheTags
   */
  public function testGetCacheTags(
    bool $isNew,
    bool $isDefault,
    ?bool $entityTagInvalidation,
    bool $shouldReturnEntityTag,
    bool $shouldReturnAssetTag,
  ): void {
    // Initialize the entity cache tag invalidation configuration value if
    // assigned. If NULL, the configuration value is unassigned to test default
    // behavior.
    if ($entityTagInvalidation !== NULL) {
      $this->config('patternkit.settings')
        ->set('entity_cache_tag_invalidation', $entityTagInvalidation)
        ->save();
    }

    // Create the pattern instance for testing.
    [
      'asset_id' => $asset_id,
      'pattern' => $pattern,
      'values' => $values,
    ] = $this->createPattern();

    // Configure revision flags.
    $pattern->enforceIsNew($isNew);
    $pattern->isDefaultRevision($isDefault);

    // Prepare expected tags array.
    $expected = [];
    if ($shouldReturnEntityTag) {
      $expected[] = "patternkit_pattern:{$values['id']}";
    }
    if ($shouldReturnAssetTag) {
      $expected[] = "pattern:$asset_id";
    }

    $this->assertEqualsCanonicalizing($expected, $pattern->getCacheTags());
  }

  /**
   * Data provider for getCacheTags().
   */
  public static function providerGetCacheTags(): array {
    return [
      // Test variations with default configuration.
      'new and default revision with default configuration' => [TRUE, TRUE, NULL, TRUE, TRUE],
      'new and non-default revision with default configuration' => [TRUE, FALSE, NULL, TRUE, TRUE],
      'existing and new default revision with default configuration' => [FALSE, TRUE, NULL, TRUE, TRUE],
      'existing and non-default revision with default configuration' => [FALSE, FALSE, NULL, TRUE, TRUE],

      // Test variations with entity tag validation explicitly enabled.
      'new and default revision with tag validation' => [TRUE, TRUE, TRUE, TRUE, TRUE],
      'new and non-default revision with tag validation' => [TRUE, FALSE, TRUE, TRUE, TRUE],
      'existing and new default revision with tag validation' => [FALSE, TRUE, TRUE, TRUE, TRUE],
      'existing and non-default revision with tag validation' => [FALSE, FALSE, TRUE, TRUE, TRUE],

      // Test variations with entity tag validation explicitly disabled.
      'new and default revision with disabled tag validation' => [TRUE, TRUE, FALSE, TRUE, TRUE],
      'new and non-default revision with disabled tag validation' => [TRUE, FALSE, FALSE, TRUE, TRUE],
      'existing and new default revision with disabled tag validation' => [FALSE, TRUE, FALSE, TRUE, TRUE],
      'existing and non-default revision with disabled tag validation' => [FALSE, FALSE, FALSE, TRUE, TRUE],
    ];
  }

  /**
   * Test behavior for getCacheTagsToInvalidate.
   *
   * @param bool $isNew
   *   Should the Pattern entity be marked as new?
   * @param bool $isDefault
   *   Should the Pattern revision be marked as the default revision?
   * @param bool|null $entityTagInvalidation
   *   The config value to assign to entity_cache_tag_invalidation. If null,
   *   the config will not be explicitly set and default values will be used.
   * @param bool $shouldReturnEntityTag
   *   Should the pattern instance cache tag be returned?
   * @param bool $shouldReturnAssetTag
   *   Should the pattern asset cache tag be returned?
   *
   * @dataProvider providerGetCacheTagsToInvalidate
   *
   * @covers ::getCacheTagsToInvalidate
   * @covers ::shouldInvalidateCacheTags
   */
  public function testGetCacheTagsToInvalidate(
    bool $isNew,
    bool $isDefault,
    ?bool $entityTagInvalidation,
    bool $shouldReturnEntityTag,
    bool $shouldReturnAssetTag,
  ): void {
    // Initialize the entity cache tag invalidation configuration value if
    // assigned. If NULL, the configuration value is unassigned to test default
    // behavior.
    if ($entityTagInvalidation !== NULL) {
      $this->config('patternkit.settings')
        ->set('entity_cache_tag_invalidation', $entityTagInvalidation)
        ->save();
    }

    // Create the pattern instance for testing.
    [
      'asset_id' => $asset_id,
      'pattern' => $pattern,
      'values' => $values,
    ] = $this->createPattern();

    // Configure revision flags.
    $pattern->enforceIsNew($isNew);
    $pattern->isDefaultRevision($isDefault);

    // Prepare expected tags array.
    $expected = [];
    if ($shouldReturnEntityTag) {
      $expected[] = "patternkit_pattern:{$values['id']}";
    }
    if ($shouldReturnAssetTag) {
      $expected[] = "pattern:$asset_id";
    }

    $this->assertEqualsCanonicalizing($expected, $pattern->getCacheTagsToInvalidate());
  }

  /**
   * Data provider for getCacheTagsToInvalidate().
   */
  public static function providerGetCacheTagsToInvalidate(): array {
    return [
      // Test variations with default configuration.
      'new and default revision with default configuration' => [TRUE, TRUE, NULL, FALSE, FALSE],
      'new and non-default revision with default configuration' => [TRUE, FALSE, NULL, FALSE, FALSE],
      'existing and new default revision with default configuration' => [FALSE, TRUE, NULL, FALSE, FALSE],
      'existing and non-default revision with default configuration' => [FALSE, FALSE, NULL, FALSE, FALSE],

      // Test variations with entity tag validation explicitly enabled.
      'new and default revision with tag validation' => [TRUE, TRUE, TRUE, FALSE, FALSE],
      'new and non-default revision with tag validation' => [TRUE, FALSE, TRUE, FALSE, FALSE],
      'existing and new default revision with tag validation' => [FALSE, TRUE, TRUE, TRUE, TRUE],
      'existing and non-default revision with tag validation' => [FALSE, FALSE, TRUE, FALSE, FALSE],

      // Test variations with entity tag validation explicitly disabled.
      'new and default revision with disabled tag validation' => [TRUE, TRUE, FALSE, FALSE, FALSE],
      'new and non-default revision with disabled tag validation' => [TRUE, FALSE, FALSE, FALSE, FALSE],
      'existing and new default revision with disabled tag validation' => [FALSE, TRUE, FALSE, FALSE, FALSE],
      'existing and non-default revision with disabled tag validation' => [FALSE, FALSE, FALSE, FALSE, FALSE],
    ];
  }

  /**
   * Create a pattern instance with randomized test data.
   *
   * @param array<string, mixed> $values
   *   (Optional) An optional collection of values to assign to the created
   *   pattern instance.
   *
   * @return array{asset_id: string, values: array<string, mixed>, pattern: \Drupal\patternkit\Entity\Pattern}
   *   An array of prepared test data.
   */
  protected function createPattern(array $values = []): array {
    $random = $this->getRandomGenerator();

    $values += [
      'id' => rand(1, 25),
      'library' => $this->randomMachineName(),
      'path' => $random->word(5) . '/' . $random->word(5),
    ];

    $pattern = Pattern::create($values);
    return [
      'asset_id' => "@{$values['library']}/{$values['path']}",
      'pattern' => $pattern,
      'values' => $values,
    ];
  }

}
