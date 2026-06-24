<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Hook\ReusableBlockHooks;
use Drupal\patternkit\PatternkitEnvironment;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for ReusableBlockHooks::requirements().
 *
 * Covers the guards in requirements() that prevent DB exceptions when the
 * reusable field is absent or the feature is intentionally enabled:
 *
 *   - When enable_reusable_blocks is true, the deprecation warning is not
 *     applicable; returns [].
 *   - When the reusable column is absent, the query is skipped rather than
 *     raising a DB exception; returns [].
 *
 * The warning path (feature disabled, column present, reusable blocks exist)
 * is the pre-existing behavior asserted via direct DB insert rather than
 * full entity creation, which would require block_content module dependencies.
 */
#[Group('patternkit')]
#[CoversMethod(ReusableBlockHooks::class, 'requirements')]
class ReusableBlockHooksRequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'patternkit',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Load REQUIREMENT_* constants used by hook_requirements() implementations.
    require_once DRUPAL_ROOT . '/core/includes/install.inc';

    $this->installConfig(['system']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('patternkit_block');
  }

  /**
   * Non-runtime phases always return empty requirements.
   */
  public function testRequirementsIgnoresNonRuntimePhase(): void {
    $hooks = \Drupal::classResolver(ReusableBlockHooks::class);

    $this->assertEmpty($hooks->requirements('install'));
    $this->assertEmpty($hooks->requirements('update'));
  }

  /**
   * Feature enabled → empty requirements, no deprecation warning.
   *
   * When an operator has explicitly set enable_reusable_blocks = true in their
   * services.yml, the deprecation warning is not applicable. requirements()
   * must return [] without querying the DB so that the status report page does
   * not mislead the operator into thinking they have a migration problem.
   */
  public function testRequirementsReturnsEmptyWhenFeatureEnabled(): void {
    // Construct an instance with enable_reusable_blocks = true, bypassing the
    // container's default environment service parameter (which is false).
    $env = new PatternkitEnvironment([
      'features' => [PatternkitEnvironment::FEATURE_ENABLE_REUSABLE_BLOCKS => TRUE],
    ]);
    $hooks = new ReusableBlockHooks($this->container->get('database'));
    $hooks->setPatternkitEnvironment($env);
    $hooks->setStringTranslation($this->container->get('string_translation'));

    $result = $hooks->requirements('runtime');

    $this->assertEmpty($result, 'No deprecation warning should appear when reusable blocks are intentionally enabled.');
  }

  /**
   * Column absent → empty requirements, no DB exception.
   *
   * Simulates a site where the reusable column is not present before 9.1.3
   * was deployed. The patternkit_update_10310() hook restores the column on
   * next drush updb, but hook_requirements() fires on every admin status page
   * load — including before updb runs. Without the fieldExists guard, this
   * would throw a DB exception.
   */
  public function testRequirementsReturnsEmptyWhenColumnAbsent(): void {
    // Simulate the pre-release removal: uninstall the field storage definition,
    // which drops the reusable column from patternkit_field_data.
    $edm = \Drupal::entityDefinitionUpdateManager();
    $definition = $edm->getFieldStorageDefinition('reusable', 'patternkit_block');
    $this->assertNotNull($definition, 'Field must be present before simulating removal.');
    $edm->uninstallFieldStorageDefinition($definition);

    $this->assertFalse(
      $this->container->get('database')->schema()->fieldExists('patternkit_field_data', 'reusable'),
      'Column must be absent after simulated removal.'
    );

    // requirements() must not throw and must return [] (nothing to warn about;
    // can't query a missing column).
    $hooks = \Drupal::classResolver(ReusableBlockHooks::class);
    $result = $hooks->requirements('runtime');

    $this->assertEmpty($result, 'No exception and empty requirements when the reusable column is absent.');
  }

  /**
   * Default state: feature disabled, column present, no reusable blocks.
   *
   * The most common production state: operator has not enabled the feature
   * (default), the column exists, and no blocks have reusable = 1.
   * requirements() should return empty (no warning, no exception).
   */
  public function testRequirementsReturnsEmptyWhenNoReusableBlocksExist(): void {
    // Default container: enable_reusable_blocks = false, column present.
    $hooks = \Drupal::classResolver(ReusableBlockHooks::class);

    $result = $hooks->requirements('runtime');

    $this->assertEmpty($result, 'No deprecation warning when no reusable blocks exist.');
  }

  /**
   * Warning path: feature disabled, column present, reusable blocks exist.
   *
   * Asserts that the deprecation warning is emitted when there are rows with
   * reusable = 1 in patternkit_field_data and the feature is not enabled.
   * A direct DB insert is used instead of full entity creation to avoid
   * pulling in block_content module dependencies; requirements() only performs
   * a COUNT query on this table.
   */
  public function testRequirementsReturnsWarningWhenReusableBlocksExist(): void {
    // Insert a minimal row with reusable = 1. The requirements() query only
    // needs SELECT COUNT(*) FROM patternkit_field_data WHERE reusable = 1.
    $this->container->get('database')->insert('patternkit_field_data')
      ->fields([
        'id' => 1,
        'revision_id' => 1,
        'langcode' => 'en',
        'default_langcode' => 1,
        'reusable' => 1,
        'info' => 'Test block',
        'status' => 1,
        'pattern_id' => 'patternkit_block--test',
        'data' => '{}',
      ])
      ->execute();

    $hooks = \Drupal::classResolver(ReusableBlockHooks::class);
    $result = $hooks->requirements('runtime');

    $this->assertArrayHasKey(
      'patternkit_reusable_blocks_deprecated',
      $result,
      'Deprecation warning key must be present when reusable blocks exist.'
    );
    // REQUIREMENT_WARNING = 1 (core/includes/install.inc).
    $this->assertSame(1, $result['patternkit_reusable_blocks_deprecated']['severity']);
    $this->assertStringContainsString(
      '1',
      (string) $result['patternkit_reusable_blocks_deprecated']['description'],
      'Warning description must include the block count.'
    );
  }

}
