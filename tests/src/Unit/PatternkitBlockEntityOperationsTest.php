<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\patternkit\PatternkitBlockEntityOperations;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Unit tests for the PatternkitBlockEntityOperations service.
 *
 * @coversDefaultClass \Drupal\patternkit\PatternkitBlockEntityOperations
 * @group patternkit
 */
class PatternkitBlockEntityOperationsTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Block storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $blockStorage;

  /**
   * Pattern storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternStorage;

  /**
   * Section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $sectionStorageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->blockStorage = $this->prophesize(EntityStorageInterface::class);
    $this->patternStorage = $this->prophesize(EntityStorageInterface::class);
    $this->sectionStorageManager = $this->prophesize(SectionStorageManagerInterface::class);
  }

  /**
   * A helper method to instantiate an instance of the subject being tested.
   *
   * This allows for late instantiation of the prophecy objects passed into the
   * constructor to enable more customization of the dependencies per test
   * without as much boilerplate in each test case.
   *
   * @return \Drupal\patternkit\PatternkitBlockEntityOperations
   *   An instantiated instance of the test subject with all configured
   *   dependency mocks injected appropriately.
   */
  protected function getTestSubject(): PatternkitBlockEntityOperations {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('patternkit_block')
      ->willReturn($this->blockStorage->reveal());
    $entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    return new PatternkitBlockEntityOperations(
      $entityTypeManager->reveal(),
      $this->sectionStorageManager->reveal(),
    );
  }

  /**
   * @covers ::handlePreSave
   * @covers ::getPatternBlockComponents
   * @covers ::saveBlockComponent
   */
  public function testHandlePresave() {
    $entity = $this->prophesize(EntityInterface::class);

    $mock = $this->createPartialMock(PatternkitBlockEntityOperations::class, [
      'isLayoutCompatibleEntity',
      'getEntitySections',
      'originalEntityUsesDefaultStorage',
    ]);
    $mock->method('originalEntityUsesDefaultStorage')->willReturn(TRUE);

    // Mock a placed patternkit block component.
    $patternBlockPlugin = $this->prophesize(PatternkitBlock::class);
    $patternBlockPlugin->getBaseId()->willReturn('patternkit_block');
    $patternBlockComponent = $this->prophesize(SectionComponent::class);
    $patternBlockComponent->getPlugin()->willReturn($patternBlockPlugin->reveal());

    // Each block type will be placed twice, so save methods should be called
    // for each time it is encountered. In practice, these would be separate
    // plugin instances, but for testing we're using multiple placements of the
    // same plugin mock.
    $patternBlockPlugin->cachePatternEntity()->shouldBeCalledTimes(2);
    // @todo Test correct settings for revision and duplication flags.
    $patternBlockPlugin->saveBlockContent(Argument::type('bool'), Argument::type('bool'))
      ->shouldBeCalledTimes(2);

    $updatedConfig = ['updated' => TRUE];
    $patternBlockPlugin->getConfiguration()->shouldBeCalledTimes(2)
      ->willReturn($updatedConfig);
    $patternBlockComponent->setConfiguration($updatedConfig)->shouldBeCalledTimes(2);

    // Mock an unrelated block component that should be unaffected.
    // Nb. This plugin interface doesn't implement the save methods for the
    // pattern block classes, so attempts to save on these blocks would trigger
    // an exception and fail the test.
    $otherBlockPlugin = $this->prophesize(BlockPluginInterface::class);
    $otherBlockPlugin->getBaseId()->willReturn('other_block');
    $otherBlockComponent = $this->prophesize(SectionComponent::class);
    $otherBlockComponent->getPlugin()->willReturn($otherBlockPlugin->reveal());

    // Mock multiple sections to confirm iteration over all sections.
    $section0 = $this->prophesize(Section::class);
    $section1 = $this->prophesize(Section::class);

    // Return each block in each section.
    $section0->getComponents()->shouldBeCalledOnce()->willReturn([
      $patternBlockComponent->reveal(),
      $otherBlockComponent->reveal(),
    ]);
    $section1->getComponents()->shouldBeCalledOnce()->willReturn([
      $otherBlockComponent->reveal(),
      $patternBlockComponent->reveal(),
    ]);

    $mock->method('isLayoutCompatibleEntity')->willReturn(TRUE);
    $mock->method('getEntitySections')->with($entity->reveal())
      ->willReturn([
        $section0->reveal(),
        $section1->reveal(),
      ]);

    $mock->handlePreSave($entity->reveal());
  }

  /**
   * @covers ::handlePreSave
   */
  public function testHandlePresaveForNonLayoutEntity() {
    $entity = $this->prophesize(EntityInterface::class);

    $mock = $this->createPartialMock(PatternkitBlockEntityOperations::class, [
      'isLayoutCompatibleEntity',
      'getEntitySections',
      'saveBlockComponent',
    ]);
    $mock->method('isLayoutCompatibleEntity')->willReturn(FALSE);
    $mock->expects($this->never())->method('getEntitySections');
    $mock->expects($this->never())->method('saveBlockComponent');

    $mock->handlePreSave($entity->reveal());
  }

  /**
   * Test configuration of block duplication and revision flags.
   *
   * @param bool $usesDefaultStorage
   *   A testing flag to indicate whether the mocked entity should indicate it
   *   uses the default entity section storage.
   * @param bool $isRevisionable
   *   Whether the mocked entity should be indicated as revisionable.
   *
   * @covers ::handlePreSave
   * @covers ::getPatternBlockComponents
   * @dataProvider providerHandlePresaveOperationFlags
   */
  public function testHandlePresaveOperationFlags(bool $usesDefaultStorage, bool $isRevisionable): void {
    $entity = $this->prophesize(EntityInterface::class);

    if ($isRevisionable) {
      $entity->willImplement(RevisionableInterface::class);
    }

    $mock = $this->createPartialMock(PatternkitBlockEntityOperations::class, [
      'isLayoutCompatibleEntity',
      'getEntitySections',
      'originalEntityUsesDefaultStorage',
      'saveBlockComponent',
    ]);
    $mock->method('isLayoutCompatibleEntity')->willReturn(TRUE);
    $mock->method('originalEntityUsesDefaultStorage')->willReturn($usesDefaultStorage);

    // Mock a placed patternkit block component.
    $patternBlockPlugin = $this->prophesize(PatternkitBlock::class);
    $patternBlockPlugin->getBaseId()->willReturn('patternkit_block');
    $patternBlockComponent = $this->prophesize(SectionComponent::class);
    $patternBlockComponent->getPlugin()->willReturn($patternBlockPlugin->reveal());

    // Mock placement within a section.
    $section0 = $this->prophesize(Section::class);
    $section0->getComponents()->shouldBeCalledOnce()->willReturn([
      $patternBlockComponent->reveal(),
    ]);

    $sections = [$section0->reveal()];
    $mock->method('getEntitySections')
      ->with($entity->reveal())
      ->willReturn($sections);

    $mock->expects($this->once())->method('saveBlockComponent')
      ->with(
        $entity->reveal(),
        $patternBlockComponent->reveal(),
        $isRevisionable,
        $usesDefaultStorage,
      );

    $mock->handlePreSave($entity->reveal());
  }

  /**
   * Data provider for testHandlePresaveOperationFlags().
   */
  public function providerHandlePresaveOperationFlags(): array {
    $cases = [];

    $cases[] = [TRUE, TRUE];
    $cases[] = [FALSE, TRUE];
    $cases[] = [TRUE, FALSE];
    $cases[] = [FALSE, FALSE];

    return $cases;
  }

}
