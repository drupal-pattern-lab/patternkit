<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel\Validation;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\layout_builder\SectionComponent;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Hook\PatternkitBlockEntityHooks;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;

/**
 * Integration kernel tests for the full Track A enforcement pipeline.
 *
 * These tests exercise the real orchestration path:
 * resolveValidationState() → enforceValidationPolicy(), called in sequence
 * with real container services and real entity persistence. This closes the
 * gap identified in D-REV-6: at least one generic save-path integration test
 * (real entity save) is required for Track A.
 *
 * Contrast with ValidityStampEnforcementKernelTest (reflection on
 * enforceValidationPolicy() only) and ValidityStampSaveTimeKernelTest
 * (stamp write pipeline only, enforcement disabled).
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampSavePathKernelTest extends PatternkitKernelTestBase {

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
   * Real hooks instance backed by container services.
   *
   * @var \Drupal\patternkit\Hook\PatternkitBlockEntityHooks
   */
  protected PatternkitBlockEntityHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('patternkit_block');
    $this->installEntitySchema('patternkit_pattern');
    $this->installSchema('patternkit', ['patternkit_block_validity']);
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['patternkit']);

    $this->hooks = PatternkitBlockEntityHooks::create($this->container);
  }

  /**
   * Creates and saves a patternkit_block entity, returning it.
   *
   * @param string $langcode
   *   Language code.
   * @param array<string, mixed> $data
   *   Block content data to store as JSON in the data field.
   *
   * @return \Drupal\patternkit\Entity\PatternkitBlock
   *   The saved block entity.
   */
  protected function createBlock(string $langcode = 'en', array $data = []): PatternkitBlock {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => $langcode,
      'pattern_id' => '@patternkit/atoms/example/src/example',
      'data' => ['value' => json_encode($data, JSON_THROW_ON_ERROR)],
      'status' => TRUE,
    ]);
    $block->save();
    return $block;
  }

  /**
   * Creates a pattern entity via direct DB insert, bypassing save() hooks.
   *
   * Pattern::save() triggers computeHash() which calls fetchAssets() via the
   * library plugin manager — requiring a registered library plugin. Since these
   * tests only need a loadable pattern with a known schema, we insert the
   * minimum required rows directly.
   *
   * @param string $schema
   *   JSON schema string to store on the pattern.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The loaded pattern entity (loaded after insert).
   */
  protected function createPattern(string $schema = '{"type":"object"}'): PatternInterface {
    $db = $this->container->get('database');
    $uuid = \Drupal::service('uuid')->generate();
    $hash = md5($schema);
    $path = 'atoms/test/' . $this->randomMachineName();

    $entityId = $db->insert('pattern')
      ->fields([
        'uuid' => $uuid,
        'hash' => $hash,
        'name__value' => $this->randomMachineName(),
        'assets' => serialize([]),
        'category' => 'test',
        'description' => '',
        'library' => 'patternkit',
        'libraryPluginId' => 'json',
        'path' => $path,
        'schema' => $schema,
        'template' => '',
        'version' => '1.0',
      ])
      ->execute();

    $revisionId = $db->insert('pattern_revision')
      ->fields([
        'id' => $entityId,
        'hash' => $hash,
        'schema' => $schema,
        'template' => '',
        'version' => '1.0',
        'revision_default' => 1,
      ])
      ->execute();

    $db->update('pattern')
      ->fields(['revision' => $revisionId])
      ->condition('id', $entityId)
      ->execute();

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_pattern');
    /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
    $pattern = $storage->loadRevision($revisionId);
    return $pattern;
  }

  /**
   * Builds a minimal SectionComponent with a fixed UUID.
   *
   * @param string $uuid
   *   The UUID for the component.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   The component.
   */
  protected function makeComponent(string $uuid = 'test-uuid-1234'): SectionComponent {
    return new SectionComponent($uuid, 'content', [
      'id' => 'patternkit_block:test',
    ]);
  }

  /**
   * Invokes the full Track A enforcement pipeline via reflection.
   *
   * Calls resolveValidationState() then enforceValidationPolicy() in sequence,
   * mirroring the production flow in saveBlockComponent(). This exercises the
   * real orchestrated enforcement path rather than calling
   * enforceValidationPolicy() directly with synthetic boolean inputs.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The component.
   * @param array<string, mixed> $configuration
   *   The post-save plugin configuration.
   */
  protected function invokeEnforcementPipeline(SectionComponent $component, array $configuration): void {
    $resolve = new \ReflectionMethod($this->hooks, 'resolveValidationState');
    $resolve->setAccessible(TRUE);
    [$result] = $resolve->invoke($this->hooks, $component, $configuration);
    if ($result !== NULL) {
      $enforceToggle = $this->config('patternkit.settings')->get('render_validation_on_save') ?? TRUE;
      $enforce = new \ReflectionMethod($this->hooks, 'enforceValidationPolicy');
      $enforce->setAccessible(TRUE);
      $enforce->invoke($this->hooks, $result->isValid, $result->blockEntityId, $result->langcode, $enforceToggle);
    }
  }

  /**
   * Sets the enforcement toggle in patternkit.settings.
   *
   * @param bool $enabled
   *   TRUE to enable enforcement, FALSE to disable.
   */
  protected function setEnforcementToggle(bool $enabled): void {
    $this->config('patternkit.settings')
      ->set('render_validation_on_save', $enabled)
      ->save();
  }

  /**
   * Toggle ON + valid content: pipeline completes without exception.
   *
   * Covers the happy path: resolveValidationState() returns a result with
   * isValid=TRUE, enforceValidationPolicy() allows the save.
   */
  public function testToggleOnValidContentAllowed(): void {
    $this->setEnforcementToggle(TRUE);

    $block = $this->createBlock(data: ['title' => 'Hello']);
    $blockRevisionId = (int) $block->getRevisionId();

    $pattern = $this->createPattern('{"type":"object"}');
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-toggle-on-valid');

    $this->invokeEnforcementPipeline($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $this->addToAssertionCount(1);
  }

  /**
   * Toggle ON + invalid content: pipeline throws EntityStorageException.
   *
   * ResolveValidationState() returns isValid=FALSE; enforceValidationPolicy()
   * must throw. This is the core Track A guarantee: enforcement fires on the
   * result of real schema validation against real entities.
   */
  public function testToggleOnInvalidContentBlocked(): void {
    $this->setEnforcementToggle(TRUE);

    // Provide an integer for "title" — violates the string type constraint.
    $block = $this->createBlock(data: ['title' => 42]);
    $blockRevisionId = (int) $block->getRevisionId();

    $schema = json_encode([
      'type' => 'object',
      'properties' => ['title' => ['type' => 'string']],
      'required' => ['title'],
      'additionalProperties' => FALSE,
    ]);
    $pattern = $this->createPattern($schema);
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-toggle-on-invalid');

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessageMatches('/failed validation/i');

    $this->invokeEnforcementPipeline($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
  }

  /**
   * Toggle OFF + invalid content: pipeline completes without exception.
   *
   * ResolveValidationState() returns isValid=FALSE; enforceValidationPolicy()
   * must allow the save because the toggle is disabled. The real validation
   * decision is still computed — only enforcement is suppressed.
   */
  public function testToggleOffInvalidContentAllowed(): void {
    $this->setEnforcementToggle(FALSE);

    // Integer "title" violates string constraint, but enforcement is disabled.
    $block = $this->createBlock(data: ['title' => 42]);
    $blockRevisionId = (int) $block->getRevisionId();

    $schema = json_encode([
      'type' => 'object',
      'properties' => ['title' => ['type' => 'string']],
      'required' => ['title'],
      'additionalProperties' => FALSE,
    ]);
    $pattern = $this->createPattern($schema);
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-toggle-off-invalid');

    $this->invokeEnforcementPipeline($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $this->addToAssertionCount(1);
  }

  /**
   * Missing block revision ID skips enforcement entirely.
   *
   * ResolveValidationState() returns NULL when patternkit_block_rid is absent,
   * so neither validation nor enforcement is attempted. No exception must be
   * thrown regardless of toggle state.
   */
  public function testMissingBlockRevisionIdSkipsEnforcement(): void {
    $this->setEnforcementToggle(TRUE);
    $component = $this->makeComponent('test-uuid-no-rid');

    $this->invokeEnforcementPipeline($component, [
      'patternkit_block_rid' => 0,
      'pattern' => 1,
    ]);

    $this->addToAssertionCount(1);
  }

  /**
   * Default install config enforces validation for invalid content.
   *
   * Verifies the out-of-box default (render_validation_on_save=TRUE from
   * patternkit.settings config) without an explicit setEnforcementToggle()
   * call. Mirrors testDefaultConfigEnforcesValidation() in the standalone
   * enforcement kernel test but through the full resolveValidationState() path.
   */
  public function testDefaultConfigEnforcesValidationViaFullPipeline(): void {
    // Integer "title" violates string constraint; default toggle ON must block.
    $block = $this->createBlock(data: ['title' => 42]);
    $blockRevisionId = (int) $block->getRevisionId();

    $schema = json_encode([
      'type' => 'object',
      'properties' => ['title' => ['type' => 'string']],
      'required' => ['title'],
      'additionalProperties' => FALSE,
    ]);
    $pattern = $this->createPattern($schema);
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-default-config');

    $this->expectException(EntityStorageException::class);

    $this->invokeEnforcementPipeline($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
  }

  /**
   * First save of a new block with invalid content: enforcement fires.
   *
   * After saveBlockContent() runs for a brand-new block, patternkit_block_rid
   * is populated from the first-ever revision. This test simulates that state:
   * createBlock() persists the entity and returns its revision ID, which is
   * then passed directly in the configuration array — exactly as
   * saveBlockComponent() would assemble it after calling saveBlockContent().
   *
   * With toggle ON and a schema violation present, enforceValidationPolicy()
   * must throw EntityStorageException, confirming enforcement fires for new
   * blocks on their first save.
   */
  public function testFirstSaveNewBlockEnforcementFires(): void {
    $this->setEnforcementToggle(TRUE);

    // Provide an integer for "name" — violates the string type constraint.
    $block = $this->createBlock(data: ['name' => 99]);
    $blockRevisionId = (int) $block->getRevisionId();

    $schema = json_encode([
      'type' => 'object',
      'properties' => ['name' => ['type' => 'string']],
      'required' => ['name'],
      'additionalProperties' => FALSE,
    ]);
    $pattern = $this->createPattern($schema);
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-first-save-invalid');

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessageMatches('/failed validation/i');

    $this->invokeEnforcementPipeline($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
  }

  /**
   * First save of a new block with valid content: pipeline allows the save.
   *
   * Mirrors testFirstSaveNewBlockEnforcementFires() but supplies content that
   * satisfies the schema. With toggle ON and no violation present,
   * enforceValidationPolicy() must not throw, confirming that new blocks with
   * valid content pass through the enforcement gate on their first save.
   */
  public function testFirstSaveNewBlockValidContentAllowed(): void {
    $this->setEnforcementToggle(TRUE);

    // String "name" satisfies the schema; no exception must be thrown.
    $block = $this->createBlock(data: ['name' => 'Hello']);
    $blockRevisionId = (int) $block->getRevisionId();

    $schema = json_encode([
      'type' => 'object',
      'properties' => ['name' => ['type' => 'string']],
      'required' => ['name'],
      'additionalProperties' => FALSE,
    ]);
    $pattern = $this->createPattern($schema);
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-first-save-valid');

    $this->invokeEnforcementPipeline($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $this->addToAssertionCount(1);
  }

}
