<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Kernel\Plugin\QueueWorker;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Plugin\QueueWorker\ValidityStampQueueWorker;
use Drupal\Tests\patternkit\Kernel\PatternkitKernelTestBase;

/**
 * Kernel tests for ValidityStampQueueWorker::processItem().
 *
 * @group patternkit
 * @group patternkit_validation
 */
class ValidityStampQueueWorkerKernelTest extends PatternkitKernelTestBase {

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
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('patternkit_block');
    $this->installEntitySchema('patternkit_pattern');
    $this->installSchema('patternkit', ['patternkit_block_validity']);
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['patternkit']);
  }

  /**
   * Builds a worker instance from the container.
   */
  protected function createWorker(): ValidityStampQueueWorker {
    return ValidityStampQueueWorker::create(
      $this->container,
      [],
      'patternkit_validity_scan',
      ['id' => 'patternkit_validity_scan', 'cron' => ['time' => 60]],
    );
  }

  /**
   * Creates a published patternkit_block with the given pattern_id.
   *
   * @param string $patternId
   *   The pattern asset ID, e.g. '@patternkit/atoms/test/example'.
   * @param string $langcode
   *   The language code for the block.
   * @param array<string, mixed> $content
   *   Pattern content keyed by field name. Stored as JSON in the data field.
   */
  protected function createBlock(string $patternId, string $langcode = 'en', array $content = []): PatternkitBlock {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block');
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $block */
    $block = $storage->create([
      'info' => $this->randomMachineName(),
      'langcode' => $langcode,
      'pattern_id' => $patternId,
      'data' => ['value' => json_encode($content ?: new \stdClass())],
      'status' => TRUE,
    ]);
    $block->save();
    return $block;
  }

  /**
   * Creates a pattern via direct DB insert, bypassing plugin discovery.
   *
   * Pattern::save() triggers computeHash() which calls fetchAssets() via the
   * library plugin manager, requiring a registered library plugin. Since these
   * tests only need a loadable pattern with a known schema, we insert the
   * minimum required rows directly.
   */
  protected function createPattern(string $library, string $path, string $schema = '{"type":"object"}'): PatternInterface {
    $db = $this->container->get('database');
    $uuid = \Drupal::service('uuid')->generate();
    $hash = md5($schema);

    $entityId = $db->insert('pattern')
      ->fields([
        'uuid' => $uuid,
        'hash' => $hash,
        'name__value' => $this->randomMachineName(),
        'assets' => serialize([]),
        'category' => 'test',
        'description' => '',
        'library' => $library,
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
   * Writes a raw string directly into the block data field in all DB tables.
   *
   * The patternkit_block entity stores the translatable data field in both
   * patternkit_field_data (current default revision) and
   * patternkit_field_revision (per-revision). Both must be updated so the
   * entity storage layer reads the overridden value regardless of which table
   * it consults. The entity static cache is reset after writing.
   *
   * @param int $blockId
   *   The block entity ID.
   * @param int $revisionId
   *   The block revision ID.
   * @param string $rawData
   *   The raw string to write into the data column.
   */
  protected function overrideBlockData(int $blockId, int $revisionId, string $rawData): void {
    // The data column is declared with 'serialize' => TRUE in the field schema,
    // so Drupal's database layer PHP-serializes values on write and
    // PHP-unserializes on read. Write the PHP-serialized form so the entity
    // storage reads back the raw JSON string we intend to test.
    $serialized = serialize($rawData);
    $db = $this->container->get('database');
    $db->update('patternkit_field_data')
      ->fields(['data' => $serialized])
      ->condition('id', $blockId)
      ->execute();
    $db->update('patternkit_field_revision')
      ->fields(['data' => $serialized])
      ->condition('id', $blockId)
      ->condition('revision_id', $revisionId)
      ->execute();
    $this->container->get('entity_type.manager')
      ->getStorage('patternkit_block')
      ->resetCache();
  }

  /**
   * Provides cases for stamp is_valid field.
   *
   * Each case: [schema, block content array, expected is_valid int].
   *
   * @return array<string, array{string, array<string,mixed>, int}>
   *   Keyed data provider cases.
   */
  public static function provideStampValidityCases(): array {
    return [
      'valid content passes open schema' => [
        '{"type":"object"}',
        ['title' => 'Hello'],
        1,
      ],
      'invalid content fails required-field schema' => [
        '{"type":"object","required":["title"],"properties":{"title":{"type":"string"}},"additionalProperties":false}',
        ['wrong_key' => 'val'],
        0,
      ],
    ];
  }

  /**
   * Verifies processItem() writes a stamp reflecting actual validity state.
   *
   * @param string $schema
   *   JSON schema string for the pattern.
   * @param array<string, mixed> $content
   *   Block content to store.
   * @param int $expectedIsValid
   *   Expected is_valid value on the written stamp.
   *
   * @dataProvider provideStampValidityCases
   */
  public function testProcessItemStampValidity(string $schema, array $content, int $expectedIsValid): void {
    $schemaKey = substr(md5($schema), 0, 8);
    $pattern = $this->createPattern('patternkit', 'atoms/test/validity-' . $schemaKey, $schema);
    $patternId = '@patternkit/atoms/test/validity-' . $schemaKey;
    $block = $this->createBlock($patternId, 'en', $content);

    $worker = $this->createWorker();
    $worker->processItem([
      'block_entity_id' => (int) $block->id(),
      'langcode' => 'en',
    ]);

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $stamp = $writer->getStamp((int) $block->id(), 'en');

    $this->assertNotNull($stamp);
    $this->assertSame($expectedIsValid, (int) $stamp['is_valid']);
    $this->assertSame('cron_scan', $stamp['stamp_source']);
    $this->assertSame((int) $pattern->getRevisionId(), (int) $stamp['pattern_revision_id']);
  }

  /**
   * Provides cases for stamp has_tokens field.
   *
   * Each case: [block content array, expected has_tokens int].
   *
   * @return array<string, array{array<string,mixed>, int}>
   *   Keyed data provider cases.
   */
  public static function provideTokenDetectionCases(): array {
    return [
      'content with a Drupal token' => [
        ['title' => '[site:name]'],
        1,
      ],
      'content without any tokens' => [
        ['title' => 'Plain text, no tokens here'],
        0,
      ],
    ];
  }

  /**
   * Verifies processItem() records whether block data contains Drupal tokens.
   *
   * @param array<string, mixed> $content
   *   Block content to store.
   * @param int $expectedHasTokens
   *   Expected has_tokens value on the written stamp.
   *
   * @dataProvider provideTokenDetectionCases
   */
  public function testProcessItemStampTokenDetection(array $content, int $expectedHasTokens): void {
    $contentKey = substr(md5(serialize($content)), 0, 8);
    $this->createPattern('patternkit', 'atoms/test/tokens-' . $contentKey, '{"type":"object"}');
    $patternId = '@patternkit/atoms/test/tokens-' . $contentKey;
    $block = $this->createBlock($patternId, 'en', $content);

    $worker = $this->createWorker();
    $worker->processItem([
      'block_entity_id' => (int) $block->id(),
      'langcode' => 'en',
    ]);

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $stamp = $writer->getStamp((int) $block->id(), 'en');

    $this->assertNotNull($stamp);
    $this->assertSame($expectedHasTokens, (int) $stamp['has_tokens']);
  }

  /**
   * Verifies processItem() does not throw for degenerate block data values.
   *
   * Regression test for blocks whose data field contains valid-but-non-object
   * JSON (e.g. 'null', '42'). getData() normalizes these to [], the worker
   * treats the block as having empty config, and stamps it as valid against an
   * open schema.
   *
   * Each raw value is tested in-process against the same block so the full
   * range of degenerate inputs is covered in a single kernel bootstrap.
   */
  public function testProcessItemHandlesDegenerateBlockData(): void {
    $this->createPattern('patternkit', 'atoms/test/degenerate', '{"type":"object"}');
    $block = $this->createBlock('@patternkit/atoms/test/degenerate', 'en');
    $worker = $this->createWorker();
    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');

    // Each raw string is a valid-but-non-array JSON value that previously
    // caused a TypeError in getData(). The fix normalizes all to [].
    $cases = ['null', '', '"hello"', '42', 'true'];
    foreach ($cases as $rawData) {
      $this->overrideBlockData(
        (int) $block->id(),
        (int) $block->getRevisionId(),
        $rawData,
      );
      // Must not throw TypeError regardless of the stored raw value.
      $worker->processItem([
        'block_entity_id' => (int) $block->id(),
        'langcode' => 'en',
      ]);
      $stamp = $writer->getStamp((int) $block->id(), 'en');
      $this->assertNotNull($stamp, "No stamp written for raw data: $rawData");
      $this->assertSame('cron_scan', $stamp['stamp_source'], "Wrong stamp_source for raw data: $rawData");
    }
  }

  /**
   * Missing block entity is handled gracefully: no exception, no stamp.
   */
  public function testProcessItemHandlesMissingBlockGracefully(): void {
    $worker = $this->createWorker();
    // Should not throw.
    $worker->processItem([
      'block_entity_id' => 99999,
      'langcode' => 'en',
    ]);

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $stamp = $writer->getStamp(99999, 'en');
    $this->assertNull($stamp);
  }

  /**
   * Missing translation is handled gracefully: no exception, no stamp.
   */
  public function testProcessItemHandlesMissingTranslationGracefully(): void {
    $this->createPattern('patternkit', 'atoms/test/trans', '{"type":"object"}');
    $block = $this->createBlock('@patternkit/atoms/test/trans', 'en');

    $worker = $this->createWorker();
    // 'fr' translation does not exist.
    $worker->processItem([
      'block_entity_id' => (int) $block->id(),
      'langcode' => 'fr',
    ]);

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $stamp = $writer->getStamp((int) $block->id(), 'fr');
    $this->assertNull($stamp);
  }

  /**
   * Missing pattern entity is handled gracefully: no exception, no stamp.
   */
  public function testProcessItemHandlesMissingPatternGracefully(): void {
    // Block references a pattern_id that has no pattern entity.
    $block = $this->createBlock('@patternkit/atoms/test/nonexistent', 'en');

    $worker = $this->createWorker();
    $worker->processItem([
      'block_entity_id' => (int) $block->id(),
      'langcode' => 'en',
    ]);

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $stamp = $writer->getStamp((int) $block->id(), 'en');
    $this->assertNull($stamp);
  }

  /**
   * Pattern with an unresolvable $ref is skipped: no exception, no stamp.
   *
   * A schema containing an external $ref that cannot be fetched causes
   * SchemaReferenceException during schema compilation. The item should be
   * skipped rather than retried, since the broken ref is a data problem on the
   * pattern entity.
   */
  public function testProcessItemSkipsBlockWithUnresolvableRefSchema(): void {
    // A $ref to a remote URL that cannot be fetched triggers
    // SchemaReferenceException.
    $schema = '{"type":"object","properties":{"field":{"$ref":"http://localhost/no-such-schema.json"}}}';
    // cspell:ignore brokenref
    $this->createPattern('patternkit', 'atoms/test/brokenref', $schema);
    $block = $this->createBlock('@patternkit/atoms/test/brokenref', 'en', ['field' => 'value']);

    $worker = $this->createWorker();
    $worker->processItem([
      'block_entity_id' => (int) $block->id(),
      'langcode' => 'en',
    ]);

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $stamp = $writer->getStamp((int) $block->id(), 'en');
    $this->assertNull($stamp);
  }

  /**
   * Pattern with a corrupt schema is skipped: no exception, no stamp.
   *
   * A schema stored as non-JSON in the pattern entity causes SchemaException
   * (wrapping the underlying JsonException) during schema compilation. The
   * item should be skipped without retrying.
   */
  public function testProcessItemSkipsBlockWithCorruptSchema(): void {
    $pattern = $this->createPattern('patternkit', 'atoms/test/corrupt', '{"type":"object"}');

    // Overwrite the stored schema with non-JSON directly in the DB to simulate
    // a corrupt pattern entity without going through entity save validation.
    $db = $this->container->get('database');
    $db->update('pattern_revision')
      ->fields(['schema' => 'not-valid-json{{{'])
      ->condition('id', $pattern->id())
      ->execute();
    $db->update('pattern')
      ->fields(['schema' => 'not-valid-json{{{'])
      ->condition('id', $pattern->id())
      ->execute();

    // Clear the entity static cache so the worker loads the updated schema.
    $this->container->get('entity_type.manager')
      ->getStorage('patternkit_pattern')
      ->resetCache();

    $block = $this->createBlock('@patternkit/atoms/test/corrupt', 'en');

    $worker = $this->createWorker();
    $worker->processItem([
      'block_entity_id' => (int) $block->id(),
      'langcode' => 'en',
    ]);

    /** @var \Drupal\patternkit\Validation\ValidityStampWriter $writer */
    $writer = $this->container->get('patternkit.validity.stamp_writer');
    $stamp = $writer->getStamp((int) $block->id(), 'en');
    $this->assertNull($stamp);
  }

}
