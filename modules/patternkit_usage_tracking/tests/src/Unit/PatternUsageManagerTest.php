<?php

namespace Drupal\Tests\patternkit_usage_tracking\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $storage;

  /**
   * A mocked entity for testing operations.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\Core\Entity\ContentEntityInterface
   */
  protected ObjectProphecy $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->storage = $this->prophesize(EntityStorageInterface::class);

    $this->entity = $this->prophesize(ContentEntityInterface::class);
  }

  /**
   * A helper method to instantiate an instance of the subject being tested.
   *
   * This allows for late instantiation of the prophecy objects passed into the
   * constructor to enable more customization of the dependencies per test
   * without as much boilerplate in each test case.
   *
   * @return \Drupal\patternkit_usage_tracking\PatternUsageManager
   *   An instantiated instance of the test subject with all configured
   *   dependency mocks injected appropriately.
   */
  protected function getTestSubject(): PatternUsageManager {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('pattern_usage')
      ->willReturn($this->storage->reveal());

    return new PatternUsageManager($entityTypeManager->reveal());
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

    $usage_manager = $this->getTestSubject();
    $usage_manager->deleteByTranslation($this->entity->reveal());
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

    $usage_manager = $this->getTestSubject();
    $actual = $usage_manager->loadByPattern($pattern_id);
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

    $usage_manager = $this->getTestSubject();
    $actual = $usage_manager->loadByEntityAndPattern($this->entity->reveal(), $pattern_id);
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

    $usage_manager = $this->getTestSubject();
    $usage_manager->deleteTrackingInformation();
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

    $usage_manager = $this->getTestSubject();
    $usage_manager->deleteByEntity($this->entity->reveal());
  }

  /**
   * @covers ::loadByEntity
   */
  public function testLoadByEntity(): void {
    $entity_type = $this->getRandomGenerator()->string();
    $id = rand();
    $revision_id = rand();

    // Mock all lookup values.
    $this->entity->getEntityTypeId()->willReturn($entity_type);
    $this->entity->id()->willReturn($id);
    $this->entity->getLoadedRevisionId()->willReturn($revision_id);

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
    ])->shouldBeCalledOnce()->willReturn($records);

    $usage_manager = $this->getTestSubject();
    $actual = $usage_manager->loadByEntity($this->entity->reveal());
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

    $usage_manager = $this->getTestSubject();
    $usage_manager->register($this->entity->reveal(), $pattern_id, $path);
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

    $usage_manager = $this->getTestSubject();
    $usage_manager->deleteByEntityAndUuid($this->entity->reveal(), $uuid);
  }

}
