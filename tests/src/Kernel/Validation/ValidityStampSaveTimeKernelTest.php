<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel\Validation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Hook\PatternkitBlockEntityHooks;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;

/**
 * Kernel tests for validity stamp writes triggered via saveBlockComponent().
 *
 * Tests the full stack from real entity persistence through to the
 * patternkit_block_validity DB row. Uses reflection to invoke the
 * protected writeValidityStamp() method with real container services,
 * avoiding the full plugin instantiation chain while still exercising
 * the live DB write path.
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampSaveTimeKernelTest extends PatternkitKernelTestBase {

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

    $this->config('patternkit.settings')
      ->set('render_validation_on_save', FALSE)
      ->save();

    $this->hooks = PatternkitBlockEntityHooks::create($this->container);
  }

  /**
   * Creates and saves a patternkit_block entity, returning it.
   *
   * @param string $langcode
   *   Language code.
   * @param array<string, mixed>|string $data
   *   Block content data. Arrays are JSON-encoded; strings are stored as-is.
   *
   * @return \Drupal\patternkit\Entity\PatternkitBlock
   *   The saved block entity.
   */
  protected function createBlock(string $langcode = 'en', array|string $data = []): PatternkitBlock {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    $dataJson = is_string($data) ? $data : json_encode($data, JSON_THROW_ON_ERROR);
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => $langcode,
      'pattern_id' => '@patternkit/atoms/example/src/example',
      'data' => ['value' => $dataJson],
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

    // Insert into base table (no auto-increment id needed; let DB assign).
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

    // Link revision back to base table.
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
   * Invokes the full stamp write pipeline via reflection.
   *
   * Mirrors production flow: resolveValidationState() then writeValidityStamp()
   * with the result. Tests that check skip behavior (NULL result) still work
   * because the stamp write is only called when a result is returned.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The component passed as the first argument.
   * @param array<string, mixed> $configuration
   *   The post-save plugin configuration passed as the second argument.
   * @param string|null $parentLangcode
   *   Optional parent entity translation langcode override.
   */
  protected function invokeWriteValidityStamp(
    SectionComponent $component,
    array $configuration,
    ?string $parentLangcode = NULL,
  ): void {
    $resolve = new \ReflectionMethod($this->hooks, 'resolveValidationState');
    $resolve->setAccessible(TRUE);
    [$result, $blockEntity] = $resolve->invoke($this->hooks, $component, $configuration, $parentLangcode);
    if ($result !== NULL) {
      $write = new \ReflectionMethod($this->hooks, 'writeValidityStamp');
      $write->setAccessible(TRUE);
      $write->invoke($this->hooks, $component, $result, $blockEntity);

      // SectionComponent saves defer stamp writes to post-save flush.
      $flush = new \ReflectionMethod($this->hooks, 'flushPendingStamps');
      $flush->setAccessible(TRUE);
      $entity = $this->createMock(EntityInterface::class);
      $flush->invoke($this->hooks, $entity);
    }
  }

  /**
   * Builds a minimal SectionComponent prophecy with a fixed UUID.
   *
   * @param string $uuid
   *   The UUID for the component.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   The component mock.
   */
  protected function makeComponent(string $uuid = 'test-uuid-1234'): SectionComponent {
    // SectionComponent is a final-ish class so create a real one.
    // The constructor requires plugin config and id; UUID is set separately.
    $component = new SectionComponent($uuid, 'content', [
      'id' => 'patternkit_block:test',
    ]);
    return $component;
  }

  /**
   * Valid content with no tokens produces is_valid=1 and has_tokens=0.
   */
  public function testStampWrittenForValidContentNoTokens(): void {
    $block = $this->createBlock(data: ['title' => 'Hello']);
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $pattern = $this->createPattern('{"type":"object"}');
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-valid-no-tokens');

    $this->invokeWriteValidityStamp($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $row = $writer->getStamp($blockId, 'en');
    $this->assertNotNull($row, 'Stamp row should exist for a valid block save.');
    $this->assertSame('1', $row['is_valid'], 'is_valid should be 1 for content that passes schema validation.');
    $this->assertSame('0', $row['has_tokens'], 'has_tokens should be 0 when no token placeholders are present.');
    $this->assertSame('save', $row['stamp_source'], 'stamp_source should be "save" for save-time writes.');
    $this->assertSame('test-uuid-valid-no-tokens', $row['component_uuid'], 'component_uuid should match the SectionComponent UUID.');
  }

  /**
   * Valid block data passes against an additionalProperties:false schema.
   *
   * Regression: isBlockValid() previously passed the full plugin configuration
   * to validatePattern() instead of $configuration['data']. Any schema with
   * additionalProperties:false would reject the extra config keys and return
   * is_valid=0 even for valid block data.
   */
  public function testStampValidWithStrictSchema(): void {
    $block = $this->createBlock(data: ['title' => 'Valid string']);
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $schema = json_encode([
      'type' => 'object',
      'properties' => ['title' => ['type' => 'string']],
      'required' => ['title'],
      'additionalProperties' => FALSE,
    ]);
    $pattern = $this->createPattern($schema);
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-strict-schema');

    $this->invokeWriteValidityStamp($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $row = $writer->getStamp($blockId, 'en');
    $this->assertNotNull($row, 'Stamp row should exist.');
    $this->assertSame('1', $row['is_valid'], 'Valid block data against an additionalProperties:false schema must produce is_valid=1.');
  }

  /**
   * Valid content with token placeholders produces is_valid=1 and has_tokens=1.
   */
  public function testStampWrittenForValidContentWithTokens(): void {
    $block = $this->createBlock(data: ['title' => '[node:title]']);
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $pattern = $this->createPattern('{"type":"object"}');
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-valid-with-tokens');

    $this->invokeWriteValidityStamp($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $row = $writer->getStamp($blockId, 'en');
    $this->assertNotNull($row, 'Stamp row should exist.');
    $this->assertSame('1', $row['is_valid'], 'is_valid should be 1 for content that passes schema validation.');
    $this->assertSame('1', $row['has_tokens'], 'has_tokens should be 1 when token placeholders are present.');
  }

  /**
   * Invalid content produces is_valid=0 with enforcement disabled in setUp().
   *
   * The schema requires "title" as a string. Providing an integer fails
   * validation but does not abort the save — the stamp records the failure.
   */
  public function testStampWrittenForInvalidContent(): void {
    // Integer "title" violates the string type constraint.
    $block = $this->createBlock(data: ['title' => 42]);
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $schema = json_encode([
      'type' => 'object',
      'properties' => ['title' => ['type' => 'string']],
      'required' => ['title'],
      'additionalProperties' => FALSE,
    ]);
    $pattern = $this->createPattern($schema);
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-invalid-content');

    // Integer "title" violates the string type constraint.
    $this->invokeWriteValidityStamp($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $row = $writer->getStamp($blockId, 'en');
    $this->assertNotNull($row, 'Stamp row should exist even when content fails validation.');
    $this->assertSame('0', $row['is_valid'], 'is_valid should be 0 when content fails schema validation.');
    $this->assertSame('0', $row['has_tokens'], 'has_tokens should be 0 when no token placeholders are present.');
  }

  /**
   * Invalid content with tokens produces is_valid=0 and has_tokens=1.
   *
   * Schema allows both "title" (string, required) and "body" (string) so that
   * only the integer type mismatch on "title" drives the is_valid=0 result.
   * Keeping additionalProperties unrestricted isolates the type-constraint
   * failure and prevents false negatives if type validation were regressed.
   */
  public function testStampWrittenForInvalidContentWithTokens(): void {
    // Integer "title" violates type constraint; "body" has a token.
    // Raw JSON string passed so blockHasTokens() can scan it directly.
    $block = $this->createBlock(data: '{"title": 42, "body": "[node:title]"}');
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $schema = json_encode([
      'type' => 'object',
      'properties' => [
        'title' => ['type' => 'string'],
        'body' => ['type' => 'string'],
      ],
      'required' => ['title'],
    ]);
    $pattern = $this->createPattern($schema);
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-invalid-with-tokens');

    $this->invokeWriteValidityStamp($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $row = $writer->getStamp($blockId, 'en');
    $this->assertNotNull($row, 'Stamp row should exist.');
    $this->assertSame('0', $row['is_valid'], 'is_valid should be 0: integer title violates the string type constraint.');
    $this->assertSame('1', $row['has_tokens'], 'has_tokens should be 1 when token placeholders are present.');
  }

  /**
   * No stamp is written when the block revision is not the default revision.
   *
   * The isDefaultRevision guard must prevent writes when patternkit_block_rid
   * references a non-default revision, such as a content-moderation draft.
   */
  public function testNoStampWrittenForDraftRevision(): void {
    // Create the block with default revision (status=true), then create a
    // new non-default revision to simulate a draft.
    $block = $this->createBlock();
    $blockId = (int) $block->id();

    // Create a new revision and explicitly mark it as NOT default.
    $block->setNewRevision(TRUE);
    // Setter: mark this revision as non-default.
    $block->isDefaultRevision(FALSE);
    $block->save();
    $draftRevisionId = (int) $block->getRevisionId();

    $pattern = $this->createPattern('{"type":"object"}');
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-draft-revision');

    $this->invokeWriteValidityStamp($component, [
      'patternkit_block_rid' => $draftRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $row = $writer->getStamp($blockId, 'en');
    $this->assertNull($row, 'No stamp should be written for a non-default (draft) block revision.');
  }

  /**
   * Eligible block confirms resolveValidationState returns a result.
   *
   * Precondition test anchoring the unit test for $skipStamps: confirms that
   * without the guard the block would produce a stamp result,
   * so the unit test's assertion that no stamp exists with skipStamps=TRUE is
   * meaningful and not trivially true. The full $skipStamps=TRUE path through
   * saveBlockComponent() is covered by ValidityStampSaveBlockComponentTest.
   */
  public function testEligibleBlockProducesValidationResult(): void {
    $block = $this->createBlock(data: ['title' => 'Hello']);
    $blockRevisionId = (int) $block->getRevisionId();

    $pattern = $this->createPattern('{"type":"object"}');
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-skip-stamps-precondition');

    $resolve = new \ReflectionMethod($this->hooks, 'resolveValidationState');
    $resolve->setAccessible(TRUE);
    [$result] = $resolve->invoke($this->hooks, $component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $this->assertNotNull($result, 'A default-revision block with a valid pattern should produce a stamp result.');
  }

  /**
   * Stamp langcode uses parent entity translation langcode, not block langcode.
   *
   * Patternkit_block entities carry the default language langcode regardless of
   * which translation is being saved. The parent langcode must be threaded
   * through to resolveValidationState() so stamps are keyed correctly.
   */
  public function testStampKeyedToParentTranslationLangcode(): void {
    // Block entity langcode is 'en' (default).
    $block = $this->createBlock('en', ['title' => 'Hello']);
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $pattern = $this->createPattern('{"type":"object"}');
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-translation-langcode');

    // Simulate a translated layout save: parent is 'fr', block entity is 'en'.
    $this->invokeWriteValidityStamp($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ], 'fr');

    $writer = $this->container->get('patternkit.validity.stamp_writer');

    // Stamp must use the parent translation langcode, not block langcode.
    $frRow = $writer->getStamp($blockId, 'fr');
    $this->assertNotNull($frRow, 'Stamp row should be keyed to the parent translation langcode (fr).');
    $this->assertSame('1', $frRow['is_valid'], 'Stamp for the translated layout save should be valid.');

    // No stamp should exist for the block entity's own langcode.
    $enRow = $writer->getStamp($blockId, 'en');
    $this->assertNull($enRow, 'No stamp should be written for the block entity langcode (en) when parent langcode is fr.');
  }

  /**
   * Default-language save uses block entity langcode as fallback.
   *
   * When no parentLangcode is supplied (config block path), the stamp langcode
   * falls back to the block entity's own langcode. This regression guard
   * ensures the fallback path is unaffected by the langcode threading change.
   */
  public function testStampLangcodeFallsBackToBlockEntityLangcode(): void {
    $block = $this->createBlock('en', ['title' => 'Hello']);
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $pattern = $this->createPattern('{"type":"object"}');
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-fallback-langcode');

    // No parentLangcode supplied — fallback to block entity langcode.
    $this->invokeWriteValidityStamp($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $row = $writer->getStamp($blockId, 'en');
    $this->assertNotNull($row, 'Stamp should be keyed to block entity langcode (en) when no parent langcode is provided.');
  }

  /**
   * A repeated save upserts the existing stamp row, not a duplicate row.
   */
  public function testRepeatedSaveUpsertOverwritesPriorStamp(): void {
    // First revision: no tokens.
    $block = $this->createBlock(data: ['title' => 'First']);
    $blockId = (int) $block->id();
    $blockRevisionId = (int) $block->getRevisionId();

    $pattern = $this->createPattern('{"type":"object"}');
    $patternRevisionId = (int) $pattern->getRevisionId();

    $component = $this->makeComponent('test-uuid-upsert-first');

    // First write — valid, no tokens.
    $this->invokeWriteValidityStamp($component, [
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $first = $writer->getStamp($blockId, 'en');
    $this->assertNotNull($first, 'Stamp row should exist after the first write.');
    $this->assertSame('1', $first['is_valid']);

    // Second revision: token present.
    $tokenJson = json_encode(['title' => '[node:title]'], JSON_THROW_ON_ERROR);
    $block->set('data', ['value' => $tokenJson]);
    $block->setNewRevision(TRUE);
    $block->save();
    $blockRevisionId2 = (int) $block->getRevisionId();

    // Second write: tokens present, different component UUID.
    $component2 = $this->makeComponent('test-uuid-upsert-second');
    $this->invokeWriteValidityStamp($component2, [
      'patternkit_block_rid' => $blockRevisionId2,
      'pattern' => $patternRevisionId,
    ]);

    $second = $writer->getStamp($blockId, 'en');
    $this->assertNotNull($second, 'Stamp row should exist after the second write.');
    $this->assertSame('1', $second['has_tokens'], 'Upsert should overwrite has_tokens with the updated value.');
    $this->assertSame('test-uuid-upsert-second', $second['component_uuid'], 'Upsert should overwrite component_uuid with the latest value.');

    // Confirm only one row exists for this block/langcode.
    $count = $this->container->get('database')
      ->select('patternkit_block_validity', 'v')
      ->condition('block_entity_id', $blockId)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertSame('1', (string) $count, 'Upsert must not create duplicate rows for the same block and langcode.');
  }

}
