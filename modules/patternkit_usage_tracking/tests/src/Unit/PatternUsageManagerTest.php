<?php

namespace Drupal\Tests\patternkit_usage_tracking\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\patternkit_usage_tracking\Entity\PatternUsage;
use Drupal\patternkit_usage_tracking\PatternUsageManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test the PatternUsageManager service.
 *
 * @coversDefaultClass \Drupal\patternkit_usage_tracking\PatternUsageManager
 * @covers \Drupal\patternkit_usage_tracking\PatternUsageManager::__construct()
 * @covers \Drupal\patternkit_usage_tracking\PatternUsageManager::storage()
 * @group patternkit
 * @group patternkit_usage_tracking
 */
class PatternUsageManagerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The storage handler for the pattern_usage entity.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  protected ObjectProphecy $storage;

  /**
   * A mocked entity for testing operations.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\ContentEntityInterface>
   */
  protected ObjectProphecy $entity;

  /**
   * An instance of the PatternUsageManager service being tested.
   *
   * @var \Drupal\patternkit_usage_tracking\PatternUsageManager
   */
  protected PatternUsageManager $patternUsageManager;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Mock injected dependencies for the service.
    $this->storage = $this->prophesize(EntityStorageInterface::class);
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('pattern_usage')
      ->willReturn($this->storage->reveal());

    // Instantiate an instance of the service for testing.
    $this->patternUsageManager = new PatternUsageManager($entityTypeManager->reveal());

    // Prepare a mocked entity to use for testing.
    $this->entity = $this->prophesize(ContentEntityInterface::class);

    // Prepare translation-related lookups for the mock entity.
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn('en');
    $this->entity->language()->willReturn($language->reveal());
  }

  /**
   * @covers ::deleteByTranslation
   */
  public function testDeleteByTranslation(): void {
    $entity_type = $this->getRandomGenerator()->string();
    $id = rand();
    $langcode = 'DE';

    // Mock all lookup values.
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn($langcode);
    $this->entity->getEntityTypeId()->willReturn($entity_type);
    $this->entity->id()->willReturn($id);
    $this->entity->language()->willReturn($language->reveal());

    // Mock records to return.
    $records = [];
    for ($i = 0; $i < 3; $i++) {
      $record = $this->prophesize(PatternUsage::class);
      $records[] = $record->reveal();
    }

    // Expect query for records.
    $this->storage->loadByProperties([
      'source_entity_type' => $entity_type,
      'source_id' => $id,
      'source_langcode' => $langcode,
    ])->shouldBeCalledOnce()->willReturn($records);

    // Expect all records to be deleted.
    $this->storage->delete(Argument::is($records))
      ->shouldBeCalledOnce();

    $this->patternUsageManager->deleteByTranslation($this->entity->reveal());
  }

  /**
   * @covers ::loadByPattern
   */
  public function testLoadByPattern(): void {
    $pattern_id = '@pattern/' . $this->getRandomGenerator()->string();

    // Mock records to return.
    $records = [];
    for ($i = 0; $i < 3; $i++) {
      $record = $this->prophesize(PatternUsage::class);
      $records[] = $record->reveal();
    }

    // Expect query for records.
    $this->storage->loadByProperties(['pattern_name' => $pattern_id])
      ->shouldBeCalledOnce()
      ->willReturn($records);

    $actual = $this->patternUsageManager->loadByPattern($pattern_id);
    $this->assertSame($records, $actual);
  }

  /**
   * @covers ::loadByEntityAndPattern
   */
  public function testLoadByEntityAndPattern(): void {
    $entity_type = $this->getRandomGenerator()->string();
    $id = rand();
    $pattern_id = '@pattern/' . $this->getRandomGenerator()->string();

    // Mock all lookup values.
    $this->entity->getEntityTypeId()->willReturn($entity_type);
    $this->entity->id()->willReturn($id);

    // Mock records to return.
    $records = [];
    for ($i = 0; $i < 3; $i++) {
      $record = $this->prophesize(PatternUsage::class);
      $records[] = $record->reveal();
    }

    // Expect query for records.
    $this->storage->loadByProperties([
      'source_entity_type' => $entity_type,
      'source_id' => $id,
      'pattern_name' => $pattern_id,
    ])->shouldBeCalledOnce()->willReturn($records);

    $actual = $this->patternUsageManager->loadByEntityAndPattern($this->entity->reveal(), $pattern_id);
    $this->assertSame($records, $actual);
  }

  /**
   * @covers ::deleteTrackingInformation
   */
  public function testDeleteTrackingInformation(): void {
    // Mock records to return.
    $records = [];
    for ($i = 0; $i < 3; $i++) {
      $record = $this->prophesize(PatternUsage::class);
      $records[] = $record->reveal();
    }

    // Expect query for records.
    $this->storage->loadMultiple()
      ->shouldBeCalledOnce()
      ->willReturn($records);

    // Expect all records to be deleted.
    $this->storage->delete(Argument::is($records))
      ->shouldBeCalledOnce();

    $this->patternUsageManager->deleteTrackingInformation();
  }

  /**
   * @covers ::deleteByEntity
   */
  public function testDeleteByEntity(): void {
    $entity_type = $this->getRandomGenerator()->string();
    $id = rand();

    // Mock all lookup values.
    $this->entity->getEntityTypeId()->willReturn($entity_type);
    $this->entity->id()->willReturn($id);

    // Mock records to return.
    $records = [];
    for ($i = 0; $i < 3; $i++) {
      $record = $this->prophesize(PatternUsage::class);
      $records[] = $record->reveal();
    }

    // Expect query for records.
    $this->storage->loadByProperties([
      'source_entity_type' => $entity_type,
      'source_id' => $id,
    ])->shouldBeCalledOnce()->willReturn($records);

    // Expect all records to be deleted.
    $this->storage->delete(Argument::is($records))
      ->shouldBeCalledOnce();

    $this->patternUsageManager->deleteByEntity($this->entity->reveal());
  }

  /**
   * @covers ::loadByEntity
   * @covers ::getLastRecordedRevisionIdByEntity
   */
  public function testLoadByEntity(): void {
    $entity_type = $this->getRandomGenerator()->string();
    $id = rand();
    $revision_id = rand();

    // Mock all lookup values.
    $this->entity->getEntityTypeId()->willReturn($entity_type);
    $this->entity->id()->willReturn($id);
    $this->entity->getLoadedRevisionId()->willReturn($revision_id);

    // Bypass translations for this test.
    $this->entity->isTranslatable()->willReturn(FALSE);

    // Mock records to return.
    $records = [];
    for ($i = 0; $i < 3; $i++) {
      $record = $this->prophesize(PatternUsage::class);
      $records[] = $record->reveal();
    }

    // Mock the query for looking up the old records.
    $this->setupLastRecordedRevisionId($revision_id);

    // Expect query for records with no language code.
    $this->storage->loadByProperties([
      'source_entity_type' => $entity_type,
      'source_id' => $id,
      'source_revision_id' => $revision_id,
    ])->shouldBeCalledOnce()->willReturn($records);

    $actual = $this->patternUsageManager->loadByEntity($this->entity->reveal());
    $this->assertSame($records, $actual);
  }

  /**
   * @covers ::loadByEntity
   * @covers ::getLastRecordedRevisionIdByEntity
   */
  public function testLoadByEntityWithTranslations(): void {
    $entity_type = $this->getRandomGenerator()->string();
    $id = rand();
    $revision_id = rand();

    // Mock all lookup values.
    $this->entity->getEntityTypeId()->willReturn($entity_type);
    $this->entity->id()->willReturn($id);
    $this->setupLastRecordedRevisionId($revision_id);

    // Mock translation lookup values.
    $langcode = 'en';
    $this->entity->isTranslatable()->willReturn(TRUE);
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn($langcode);
    $this->entity->language()->willReturn($language->reveal());

    // Mock records to return.
    $records = [];
    for ($i = 0; $i < 3; $i++) {
      $record = $this->prophesize(PatternUsage::class);
      $records[] = $record->reveal();
    }

    // Expect query for records with language code included.
    $this->storage->loadByProperties([
      'source_entity_type' => $entity_type,
      'source_id' => $id,
      'source_revision_id' => $revision_id,
      'source_langcode' => $langcode,
    ])->shouldBeCalledOnce()->willReturn($records);

    $actual = $this->patternUsageManager->loadByEntity($this->entity->reveal());
    $this->assertSame($records, $actual);
  }

  /**
   * @covers ::register
   */
  public function testRegister(): void {
    $entity_type = $this->getRandomGenerator()->string();
    $id = rand();
    $revision_id = rand();
    $langcode = 'DE';
    $pattern_id = $this->getRandomGenerator()->string();
    $path = $this->getRandomGenerator()->string();

    // Mock all lookup values.
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn($langcode);
    $this->entity->getEntityTypeId()->willReturn($entity_type);
    $this->entity->id()->willReturn($id);
    $this->entity->getRevisionId()->willReturn($revision_id);
    $this->entity->language()->willReturn($language->reveal());

    // Mock record to return.
    $record = $this->prophesize(PatternUsage::class);
    $record->save()->shouldBeCalledOnce();

    // Expect the record to be created.
    $this->storage->create([
      'source_entity_type' => $entity_type,
      'source_id' => $id,
      'source_revision_id' => $revision_id,
      'source_langcode' => $langcode,
      'pattern_name' => $pattern_id,
      'path' => $path,
    ])->shouldBeCalledOnce()->willReturn($record->reveal());

    $this->patternUsageManager->register($this->entity->reveal(), $pattern_id, $path);
  }

  /**
   * @covers ::deleteByEntityAndUuid
   */
  public function testDeleteByEntityAndUuid(): void {
    $entity_type = $this->getRandomGenerator()->string();
    $id = rand();
    $revision_id = rand();
    $langcode = 'DE';
    $uuid = $this->getRandomGenerator()->string(16);

    // Mock all lookup values.
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn($langcode);
    $this->entity->getEntityTypeId()->willReturn($entity_type);
    $this->entity->id()->willReturn($id);
    $this->entity->getRevisionId()->willReturn($revision_id);
    $this->entity->language()->willReturn($language->reveal());

    // Mock records to return.
    $records = [];
    for ($i = 0; $i < 3; $i++) {
      $record = $this->prophesize(PatternUsage::class);
      $records[] = $record->reveal();
    }

    // Expect query for records.
    $this->storage->loadByProperties([
      'source_entity_type' => $entity_type,
      'source_id' => $id,
      'source_revision_id' => $revision_id,
      'source_langcode' => $langcode,
      'component_uuid' => $uuid,
    ])->shouldBeCalledOnce()->willReturn($records);

    // Expect all records to be deleted.
    $this->storage->delete(Argument::is($records))
      ->shouldBeCalledOnce();

    $this->patternUsageManager->deleteByEntityAndUuid($this->entity->reveal(), $uuid);
  }

  /**
   * Prepare mocking to return a given last revision ID.
   *
   * @param int|null $revision_id
   *   The revision ID to be returned as the last default revision for the
   *   entity being processed. If no value is provided, a NULL result will be
   *   returned from the method call.
   *
   * @see \Drupal\patternkit_usage_tracking\PatternUsageManager::getLastRecordedRevisionIdByEntity()
   */
  protected function setupLastRecordedRevisionId(?int $revision_id = NULL): void {
    $query = $this->prophesize(QueryInterface::class);
    $this->storage->getQuery()->willReturn($query->reveal());
    $query->accessCheck(Argument::type('bool'))
      ->shouldBeCalled()
      ->willReturn($query->reveal());
    $query->condition(Argument::type('string'), Argument::any())
      ->willReturn($query->reveal());
    $query->sort(Argument::type('string'), Argument::in(['ASC', 'DESC']))
      ->willReturn($query->reveal());
    $query->range(Argument::type('int'), Argument::type('int'))
      ->willReturn($query->reveal());

    if ($revision_id === NULL) {
      // Return no results if no revision ID was provided.
      $query->execute()->willReturn([]);
    }
    else {
      // Prepare a Usage Record ID to be returned for lookup.
      $record_id = rand(1, 100);
      $results = [$record_id => $record_id];
      $query->execute()->willReturn($results);

      // Prepare the returned record to provide the source revision ID value.
      $record = $this->prophesize(PatternUsage::class);
      $record->get('source_revision_id')->willReturn((object) ['value' => $revision_id]);
      $this->storage->load($record_id)->willReturn($record->reveal());
    }
  }

}
