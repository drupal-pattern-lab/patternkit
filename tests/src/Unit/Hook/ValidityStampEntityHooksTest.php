<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Unit\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\patternkit\Entity\PatternkitBlock as PatternkitBlockEntity;
use Drupal\patternkit\Hook\ValidityStampEntityHooks;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests orphan stamp cleanup in ValidityStampEntityHooks.
 *
 * Covers hook_entity_delete and hook_entity_translation_delete for the
 * patternkit_block entity type, which must remove stamp rows keyed by
 * (block_entity_id, langcode) from patternkit_block_validity.
 *
 * @group patternkit
 * @group patternkit_validation
 * @coversDefaultClass \Drupal\patternkit\Hook\ValidityStampEntityHooks
 */
class ValidityStampEntityHooksTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Validity stamp writer prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Validation\ValidityStampWriterInterface>
   */
  protected ObjectProphecy $stampWriter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->stampWriter = $this->prophesize(ValidityStampWriterInterface::class);
  }

  /**
   * Builds a ValidityStampEntityHooks instance under test.
   *
   * @return \Drupal\patternkit\Hook\ValidityStampEntityHooks
   *   The hooks instance under test.
   */
  protected function getSubject(): ValidityStampEntityHooks {
    return new ValidityStampEntityHooks($this->stampWriter->reveal());
  }

  /**
   * DeleteStamp is called with entity ID and NULL langcode on entity delete.
   *
   * When a patternkit_block is deleted, all stamp rows for that entity (across
   * all translations) must be removed by calling deleteStamp($id, NULL).
   *
   * @covers ::entityDelete
   */
  public function testStampDeletedOnEntityDelete(): void {
    $entityId = 42;

    $entity = $this->prophesize(PatternkitBlockEntity::class);
    $entity->id()->willReturn($entityId);

    $this->stampWriter->deleteStamp($entityId, NULL)->shouldBeCalledOnce();

    $subject = $this->getSubject();
    $subject->entityDelete($entity->reveal());
  }

  /**
   * DeleteStamp is called with entity ID and langcode on translation delete.
   *
   * When a single patternkit_block translation is removed, only that
   * translation's stamp row should be deleted.
   *
   * @covers ::entityTranslationDelete
   */
  public function testStampDeletedOnTranslationDelete(): void {
    $entityId = 99;
    $langcode = 'fr';

    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn($langcode);

    $entity = $this->prophesize(PatternkitBlockEntity::class);
    $entity->id()->willReturn($entityId);
    $entity->language()->willReturn($language->reveal());

    $this->stampWriter->deleteStamp($entityId, $langcode)->shouldBeCalledOnce();

    $subject = $this->getSubject();
    $subject->entityTranslationDelete($entity->reveal());
  }

  /**
   * Non-patternkit_block entity delete does not call deleteStamp.
   *
   * The hooks fire for all entity types; deleteStamp must not be called for
   * entity types other than patternkit_block.
   *
   * @covers ::entityDelete
   */
  public function testEntityDeleteIgnoredForNonPatternkitBlock(): void {
    $entity = $this->prophesize(EntityInterface::class);

    $this->stampWriter->deleteStamp()->shouldNotBeCalled();

    $subject = $this->getSubject();
    $subject->entityDelete($entity->reveal());
  }

  /**
   * Non-patternkit_block entity translation delete does not call deleteStamp.
   *
   * @covers ::entityTranslationDelete
   */
  public function testTranslationDeleteIgnoredForNonPatternkitBlock(): void {
    $entity = $this->prophesize(EntityInterface::class);

    $this->stampWriter->deleteStamp()->shouldNotBeCalled();

    $subject = $this->getSubject();
    $subject->entityTranslationDelete($entity->reveal());
  }

}
