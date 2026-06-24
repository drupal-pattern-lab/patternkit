<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\block\Entity\Block;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel tests for the reusable block update hook sequence (10308–10310).
 *
 * ## Testing procedural update hooks.
 *
 * Drupal update hooks are procedural functions in *.install files. The
 * approach for kernel-level testing is:
 *
 * 1. Enable required modules via $modules.
 * 2. Load the .install file with
 *    \Drupal::moduleHandler()->loadInclude('module', 'install').
 * 3. Call the hook function directly and assert the return value or side
 *    effects.
 *
 * This matches the pattern used by Drupal core's own UpdateDescriptionTest
 * and FileSystemRequirementsTest. module_load_include() is deprecated in
 * D9.4+; moduleHandler()->loadInclude() is the current API.
 *
 * ## Covered hooks
 *
 * patternkit_update_10308 processes Patternkit blocks with unsaved serialized
 * changes. When a block's plugin cannot be resolved it logs a warning and
 * skips the block rather than aborting the update run:
 *
 *   - Empty state: no matching blocks → returns status string, no exception.
 *   - Guard path: one block with an unresolvable plugin → warning logged,
 *     block skipped, returns "no invalid blocks" message.
 *   - Multiple guard path: two blocks both hitting the guard → loop continues
 *     past the first skip, both are skipped, count stays at zero.
 *
 * The happy path (plugin resolves, block saved, positive count message) is
 * covered by functional tests in ReusableBlocksEnabledTest. Exercising it in
 * a kernel test requires functional-test weight infrastructure.
 *
 * patternkit_update_10309 is a no-op that retains the reusable field storage
 * until the 9.2.0 removal. It does not alter schema.
 *
 * patternkit_update_10310 restores the reusable field storage on sites where
 * it is absent, and no-ops on sites where it is already present:
 *
 *   - No-op path: field storage is tracked → returns status string.
 *   - Restore path: field storage absent → field restored, message returned.
 *
 * @see https://www.drupal.org/project/patternkit/issues/3587714
 * @see https://www.drupal.org/project/patternkit/issues/3587730
 */
#[Group('patternkit')]
#[CoversFunction('patternkit_update_10308')]
#[CoversFunction('patternkit_update_10309')]
#[CoversFunction('patternkit_update_10310')]
class ReusableBlocksUpdateHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
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

    // Block is a config entity — install config schema but not a DB table.
    $this->installConfig(['block', 'system']);

    // Install patternkit_block entity schema so entity field manager and
    // entityDefinitionUpdateManager have a real schema context for 10310 tests.
    $this->installEntitySchema('user');
    $this->installEntitySchema('patternkit_block');

    // Load the patternkit install functions into the current PHP process.
    \Drupal::moduleHandler()->loadInclude('patternkit', 'install');
  }

  /**
   * Update 10308 with no blocks returns a status string without throwing.
   *
   * When no blocks match the query conditions, the hook completes normally
   * and returns a message indicating nothing was processed.
   */
  public function testUpdate10308WithNoBlocksReturnsCleanly(): void {
    $result = patternkit_update_10308();

    $this->assertNotNull($result);
    $this->assertStringContainsString('No invalid blocks were found', (string) $result);
  }

  /**
   * Update 10308 skips a block with an unresolvable plugin.
   *
   * When a block's plugin cannot be resolved to a PatternkitBlock instance,
   * a warning is logged and the block is skipped. The hook returns a message
   * reflecting zero successful updates.
   */
  public function testUpdate10308SkipsBlockWithUnresolvablePlugin(): void {
    // Create a block config entity that matches both query conditions:
    // - provider = 'patternkit'
    // - block_serialized IS NOT NULL
    // The plugin ID references a UUID-based derivative that does not exist in
    // patternkit_block entity storage, so getPlugin() returns a broken plugin
    // which is not an instanceof PatternkitBlock, triggering the skip guard.
    Block::create([
      'id' => 'test_guard_block',
      'theme' => 'stark',
      'region' => 'content',
      'weight' => 0,
      'status' => FALSE,
      'plugin' => 'patternkit_block:00000000-0000-0000-0000-000000000000',
      'settings' => [
        'id' => 'patternkit_block:00000000-0000-0000-0000-000000000000',
        'provider' => 'patternkit',
        'label' => 'Guard test block',
        'label_display' => 'visible',
        'block_serialized' => '{"field":"test_value"}',
      ],
      'visibility' => [],
    ])->save();

    $result = patternkit_update_10308();

    $this->assertNotNull($result);
    // Block was skipped; updated count stays at 0.
    $this->assertStringContainsString('No invalid blocks were found', (string) $result);
  }

  /**
   * Update 10308 continues processing after a skipped block.
   *
   * When the first block in the result set triggers the skip guard, subsequent
   * blocks are still processed. Both unresolvable blocks are skipped cleanly
   * and the return reflects zero successful updates.
   *
   * Testing the post-skip branch with a truly resolvable plugin instance
   * requires functional-test infrastructure (patternkit_block entity + pattern
   * entity + derivative registration).
   */
  public function testUpdate10308LoopContinuesAfterGuardPath(): void {
    // Two blocks: both have block_serialized set and both reference UUIDs that
    // do not exist in patternkit_block entity storage. Both will trigger the
    // skip guard. The test verifies neither throws and both are skipped.
    Block::create([
      'id' => 'test_guard_block_a',
      'theme' => 'stark',
      'region' => 'content',
      'weight' => 0,
      'status' => FALSE,
      'plugin' => 'patternkit_block:00000000-0000-0000-0000-000000000001',
      'settings' => [
        'id' => 'patternkit_block:00000000-0000-0000-0000-000000000001',
        'provider' => 'patternkit',
        'label' => 'Guard test block A',
        'label_display' => 'visible',
        'block_serialized' => '{"field":"value_a"}',
      ],
      'visibility' => [],
    ])->save();

    Block::create([
      'id' => 'test_guard_block_b',
      'theme' => 'stark',
      'region' => 'content',
      'weight' => 1,
      'status' => FALSE,
      'plugin' => 'patternkit_block:00000000-0000-0000-0000-000000000002',
      'settings' => [
        'id' => 'patternkit_block:00000000-0000-0000-0000-000000000002',
        'provider' => 'patternkit',
        'label' => 'Guard test block B',
        'label_display' => 'visible',
        'block_serialized' => '{"field":"value_b"}',
      ],
      'visibility' => [],
    ])->save();

    $result = patternkit_update_10308();

    $this->assertNotNull($result);
    // Both blocks were skipped; updated count stays at 0.
    $this->assertStringContainsString('No invalid blocks were found', (string) $result);
  }

  /**
   * Update 10309 is a no-op that returns a string without modifying schema.
   *
   * Patternkit_update_10309 retains the reusable field storage in place for
   * the 9.1.x migration window. It must not alter schema and must return a
   * descriptive string confirming no change was made.
   */
  public function testUpdate10309IsNoopAndReturnsString(): void {
    $result = patternkit_update_10309();

    $this->assertNotNull($result);
    $this->assertStringContainsString('No schema change', (string) $result);
    $this->assertStringContainsString('9.2.0', (string) $result);
  }

  /**
   * Update 10310 returns a no-op message when field storage is already present.
   *
   * When the reusable field is already tracked by entityDefinitionUpdateManager
   * the hook detects this and skips the install call, returning a status
   * message.
   */
  public function testUpdate10310IsNoopWhenFieldPresent(): void {
    $edm = \Drupal::entityDefinitionUpdateManager();
    // Field should be tracked after installEntitySchema in setUp.
    $this->assertNotNull($edm->getFieldStorageDefinition('reusable', 'patternkit_block'));

    $result = patternkit_update_10310();

    $this->assertNotNull($result);
    $this->assertStringContainsString('No schema change', (string) $result);
    // Field is still tracked after the no-op run.
    $this->assertNotNull($edm->getFieldStorageDefinition('reusable', 'patternkit_block'));
  }

  /**
   * Update 10310 restores field storage when it is absent.
   *
   * When the reusable field storage is not tracked, the hook installs the
   * field definition and restores the physical DB column. Sites on which the
   * field was previously removed will have it restored on the next drush updb.
   */
  public function testUpdate10310RestoresFieldWhenAbsent(): void {
    $edm = \Drupal::entityDefinitionUpdateManager();

    // Remove the field storage definition to simulate the absent state.
    $definition = $edm->getFieldStorageDefinition('reusable', 'patternkit_block');
    $this->assertNotNull($definition, 'Field must be present before simulating removal.');
    $edm->uninstallFieldStorageDefinition($definition);
    $this->assertNull(
      $edm->getFieldStorageDefinition('reusable', 'patternkit_block'),
      'Field must be absent after simulated removal.'
    );

    $result = patternkit_update_10310();

    $this->assertNotNull($result);
    $this->assertStringContainsString('Restored', (string) $result);
    // Field tracking metadata is restored.
    $this->assertNotNull(
      $edm->getFieldStorageDefinition('reusable', 'patternkit_block'),
      'Field must be tracked again after 10310 restores it.'
    );
    // Physical DB column is restored.
    $this->assertTrue(
      $this->container->get('database')->schema()->fieldExists('patternkit_field_data', 'reusable'),
      'DB column patternkit_field_data.reusable must exist after 10310 restores it.'
    );
  }

}
