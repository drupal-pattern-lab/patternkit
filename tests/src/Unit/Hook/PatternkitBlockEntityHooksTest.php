<?php

namespace Drupal\Tests\patternkit\Unit\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Utility\Token;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\patternkit\Hook\PatternkitBlockEntityHooks;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\patternkit\Utility\PatternValidationHelper;
use Drupal\patternkit\Validation\ValidityStampPendingStore;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Unit tests for the PatternkitBlockEntityHooks service.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Hook\PatternkitBlockEntityHooks
 */
class PatternkitBlockEntityHooksTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Block storage manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\RevisionableStorageInterface>
   */
  protected ObjectProphecy $blockStorage;

  /**
   * Pattern storage manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\RevisionableStorageInterface>
   */
  protected ObjectProphecy $patternStorage;

  /**
   * Module handler service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Extension\ModuleHandlerInterface>
   */
  protected ObjectProphecy $moduleHandler;

  /**
   * Section storage manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface>
   */
  protected ObjectProphecy $sectionStorageManager;

  /**
   * Validity stamp writer prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Validation\ValidityStampWriterInterface>
   */
  protected ObjectProphecy $stampWriter;

  /**
   * Pattern validation helper prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Utility\PatternValidationHelper>
   */
  protected ObjectProphecy $validationHelper;

  /**
   * Token service prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Utility\Token>
   */
  protected ObjectProphecy $token;

  /**
   * Config factory prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Config\ConfigFactoryInterface>
   */
  protected ObjectProphecy $configFactory;

  /**
   * Pending store instance.
   *
   * @var \Drupal\patternkit\Validation\ValidityStampPendingStore
   */
  protected ValidityStampPendingStore $pendingStore;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->blockStorage = $this->prophesize(RevisionableStorageInterface::class);
    $this->patternStorage = $this->prophesize(RevisionableStorageInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->sectionStorageManager = $this->prophesize(SectionStorageManagerInterface::class);
    $this->stampWriter = $this->prophesize(ValidityStampWriterInterface::class);
    $this->validationHelper = $this->prophesize(PatternValidationHelper::class);
    $this->token = $this->prophesize(Token::class);

    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('render_validation_on_save')->willReturn(FALSE);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->configFactory->get('patternkit.settings')->willReturn($config->reveal());

    $this->pendingStore = new ValidityStampPendingStore();
  }

  /**
   * A helper method to instantiate an instance of the subject being tested.
   *
   * This allows for late instantiation of the prophecy objects passed into the
   * constructor to enable more customization of the dependencies per test
   * without as much boilerplate in each test case.
   *
   * @return \Drupal\patternkit\Hook\PatternkitBlockEntityHooks
   *   An instantiated instance of the test subject with all configured
   *   dependency mocks injected appropriately.
   */
  protected function getTestSubject(): PatternkitBlockEntityHooks {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('patternkit_block')
      ->willReturn($this->blockStorage->reveal());
    $entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    return new PatternkitBlockEntityHooks(
      $entityTypeManager->reveal(),
      $this->moduleHandler->reveal(),
      $this->stampWriter->reveal(),
      $this->validationHelper->reveal(),
      $this->token->reveal(),
      $this->configFactory->reveal(),
      $this->pendingStore,
      $this->sectionStorageManager->reveal(),
    );
  }

  /**
   * Verifies that handlePreSave() iterates all Patternkit block components.
   *
   * Confirms that only patternkit_block plugin components trigger save methods
   * and that non-patternkit blocks in the same sections are left untouched.
   */
  public function testHandlePresave() {
    $entity = $this->prophesize(EntityInterface::class);
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity->getEntityType()->willReturn($entity_type->reveal());
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn('en');
    $entity->language()->willReturn($language->reveal());

    $mock = $this->createPartialMock(PatternkitBlockEntityHooks::class, [
      'isLayoutBuilderEnabled',
      'isLayoutCompatibleEntity',
      'originalEntityUsesDefaultStorage',
      'getSectionStorageForEntity',
    ]);
    $mock->setSectionStorageManager($this->sectionStorageManager->reveal());
    $mock->method('isLayoutBuilderEnabled')->willReturn(TRUE);
    $mock->method('originalEntityUsesDefaultStorage')->willReturn(TRUE);
    $mock->method('isLayoutCompatibleEntity')->with($entity->reveal())
      ->willReturn(TRUE);

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

    $sectionStorage = $this->prophesize(SectionStorageInterface::class);
    $sectionStorage->getSections()->willReturn([
      $section0->reveal(),
      $section1->reveal(),
    ]);

    $mock->method('getSectionStorageForEntity')->with($entity->reveal())
      ->willReturn($sectionStorage->reveal());

    $mock->handlePreSave($entity->reveal());
  }

  public function testHandlePresaveForNonLayoutEntity() {
    $entity = $this->prophesize(EntityInterface::class);

    $mock = $this->createPartialMock(PatternkitBlockEntityHooks::class, [
      'isLayoutBuilderEnabled',
      'isLayoutCompatibleEntity',
      'getSectionStorageForEntity',
      'saveBlockComponent',
    ]);
    $mock->expects($this->once())
      ->method('isLayoutBuilderEnabled')->willReturn(TRUE);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')->willReturn(FALSE);
    $mock->expects($this->never())->method('getSectionStorageForEntity');
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
   * @dataProvider providerHandlePresaveOperationFlags
   */
  public function testHandlePresaveOperationFlags(bool $usesDefaultStorage, bool $isRevisionable): void {
    // Prophesize from ContentEntityInterface when revisionable so the double
    // already implements RevisionableInterface without a post-hoc willImplement
    // call (Prophecy rejects interface additions after instantiation).
    $entity = $isRevisionable
      ? $this->prophesize(ContentEntityInterface::class)
      : $this->prophesize(EntityInterface::class);

    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn('en');
    $entity->language()->willReturn($language->reveal());

    if ($isRevisionable) {
      // Published (default) revision: $skipStamps = FALSE.
      $entity->isDefaultRevision()->willReturn(TRUE);
    }

    $mock = $this->createPartialMock(PatternkitBlockEntityHooks::class, [
      'isLayoutBuilderEnabled',
      'isLayoutCompatibleEntity',
      'getSectionStorageForEntity',
      'originalEntityUsesDefaultStorage',
      'saveBlockComponent',
    ]);
    $mock->setSectionStorageManager($this->sectionStorageManager->reveal());
    $mock->expects($this->once())
      ->method('isLayoutBuilderEnabled')->willReturn(TRUE);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')->willReturn(TRUE);
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
    $sectionStorage = $this->prophesize(SectionStorageInterface::class);
    $sectionStorage->getSections()->willReturn($sections);
    $mock->method('getSectionStorageForEntity')
      ->with($entity->reveal())
      ->willReturn($sectionStorage->reveal());

    $mock->expects($this->once())->method('saveBlockComponent')
      ->with(
        $patternBlockComponent->reveal(),
        $isRevisionable,
        $usesDefaultStorage,
        // skipStamps=FALSE: published (default) revision parent entity.
        FALSE,
        'en',
      );

    $mock->handlePreSave($entity->reveal());
  }

  /**
   * Data provider for testHandlePresaveOperationFlags().
   */
  public static function providerHandlePresaveOperationFlags(): array {
    $cases = [];

    $cases[] = [TRUE, TRUE];
    $cases[] = [FALSE, TRUE];
    $cases[] = [TRUE, FALSE];
    $cases[] = [FALSE, FALSE];

    return $cases;
  }

  /**
   * Test instantiation when Layout Builder is not installed.
   *
   * This verifies the fix for the sectionStorageManager null assignment
   * issue where the trait property is non-nullable but the service may not
   * be available.
   */
  public function testConstructionWithoutLayoutBuilder(): void {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('patternkit_block')
      ->willReturn($this->blockStorage->reveal());
    $entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    // Create instance with NULL sectionStorageManager (Layout Builder not
    // installed). This should not throw a TypeError about assigning null to
    // non-nullable property.
    $subject = new PatternkitBlockEntityHooks(
      $entityTypeManager->reveal(),
      $this->moduleHandler->reveal(),
      $this->stampWriter->reveal(),
      $this->validationHelper->reveal(),
      $this->token->reveal(),
      $this->configFactory->reveal(),
      $this->pendingStore,
      NULL,
    );

    // Verify the instance was created successfully.
    $this->assertInstanceOf(PatternkitBlockEntityHooks::class, $subject);
  }

}
