<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel;

/**
 * Kernel tests for patternkit_post_update_set_render_validation_on_save().
 *
 * Verifies that the post-update hook sets render_validation_on_save to TRUE
 * when the key is absent (pre-upgrade simulation) and leaves an explicitly
 * set value untouched (idempotency).
 *
 * @group patternkit
 * @group patternkit_post_update
 */
class PostUpdateRenderValidationOnSaveTest extends PatternkitKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'filter',
    'block',
    'block_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('patternkit_block');
    $this->installEntitySchema('patternkit_pattern');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['patternkit']);

    // Include the post-update file so its functions are available.
    // dirname(__FILE__, 4) resolves to the module root (repo root) from
    // tests/src/Kernel/.
    require_once dirname(__FILE__, 4) . '/patternkit.post_update.php';
  }

  /**
   * Pre-upgrade simulation: absent key is set to TRUE by the hook.
   *
   * Simulates a site that was installed before render_validation_on_save
   * existed in the config schema. The key is cleared so that config->get()
   * returns NULL, then the hook is invoked and must write TRUE.
   */
  public function testPreUpgradeAbsentKeyIsSetToTrue(): void {
    // Remove the key to simulate a pre-upgrade config state.
    $this->config('patternkit.settings')
      ->clear('render_validation_on_save')
      ->save();

    // Confirm the key is absent before running the hook.
    $this->assertNull(
      $this->config('patternkit.settings')->get('render_validation_on_save'),
      'Key must be absent before the hook runs.'
    );

    patternkit_post_update_set_render_validation_on_save();

    // Re-fetch config to bypass any in-memory cache.
    $value = \Drupal::configFactory()
      ->getEditable('patternkit.settings')
      ->get('render_validation_on_save');

    $this->assertTrue(
      $value,
      'Hook must set render_validation_on_save to TRUE when the key was absent.'
    );
  }

  /**
   * Idempotency: explicit FALSE is not overwritten by the hook.
   *
   * An operator who opted out before the post-update runs must retain FALSE
   * after the hook executes.
   */
  public function testIdempotencyWithOperatorOptOut(): void {
    // Operator has explicitly disabled save-time validation enforcement.
    $this->config('patternkit.settings')
      ->set('render_validation_on_save', FALSE)
      ->save();

    patternkit_post_update_set_render_validation_on_save();

    $value = \Drupal::configFactory()
      ->getEditable('patternkit.settings')
      ->get('render_validation_on_save');

    $this->assertFalse(
      $value,
      'Hook must not overwrite an explicitly set FALSE value.'
    );
  }

  /**
   * Idempotency: explicit TRUE is unchanged after the hook runs.
   *
   * Running the hook on a site that already has the correct value must be a
   * no-op.
   */
  public function testIdempotencyWithExplicitTrue(): void {
    // Value is already TRUE (matches the default install config).
    $this->config('patternkit.settings')
      ->set('render_validation_on_save', TRUE)
      ->save();

    patternkit_post_update_set_render_validation_on_save();

    $value = \Drupal::configFactory()
      ->getEditable('patternkit.settings')
      ->get('render_validation_on_save');

    $this->assertTrue(
      $value,
      'Hook must leave render_validation_on_save as TRUE when it was already TRUE.'
    );
  }

}
