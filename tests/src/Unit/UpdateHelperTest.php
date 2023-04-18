<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\block\BlockInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock as EntityPatternkitBlock;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Plugin\Block\PatternkitBlock as BlockPatternkitBlock;
use Drupal\patternkit\UpdateHelper;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Unit tests for the UpdateHelper service.
 *
 * @coversDefaultClass \Drupal\patternkit\UpdateHelper
 * @covers ::__construct
 * @group patternkit
 */
class UpdateHelperTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $entityTypeManager;

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
   * The expirable key value factory service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $keyValueExpirable;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $moduleHandler;

  /**
   * The patternkit logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $logger;

  /**
   * The patternkit pattern repository service.
   *
   * @var \Drupal\patternkit\PatternRepository|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternRepository;

  /**
   * The Patternkit layout helper service, if Layout Builder is available.
   *
   * @var \Drupal\patternkit\LayoutHelper|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $layoutHelper;

  /**
   * The update helper service being tested.
   *
   * @var \Drupal\patternkit\UpdateHelper
   */
  protected UpdateHelper $updateHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->blockStorage = $this->prophesize(EntityStorageInterface::class);
    $this->patternStorage = $this->prophesize(EntityStorageInterface::class);
    $this->patternRepository = $this->prophesize(PatternRepository::class);
    $this->keyValueExpirable = $this->prophesize(KeyValueExpirableFactoryInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
  }

  /**
   * Get the UpdateHelper instance for testing with all mocks injected.
   *
   * @return \Drupal\patternkit\UpdateHelper
   *   The instantiated update helper service for testing.
   */
  protected function getUpdateHelper(): UpdateHelper {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('patternkit_block')
      ->willReturn($this->blockStorage->reveal());
    $entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    $updateHelper = new UpdateHelper(
      $entityTypeManager->reveal(),
      $this->patternRepository->reveal(),
      $this->keyValueExpirable->reveal(),
      $this->moduleHandler->reveal(),
      $this->logger->reveal(),
    );

    $stringTranslation = $this->prophesize(TranslationInterface::class);
    $updateHelper->setStringTranslation($stringTranslation->reveal());

    return $updateHelper;
  }

  /**
   * Tests the isPatternkitComponent method.
   *
   * @covers ::isPatternkitComponent
   * @dataProvider providerIsPatternkitComponent
   */
  public function testIsPatternkitComponent(string $plugin_id, bool $expected) {
    $updateHelper = $this->getUpdateHelper();

    $component = $this->prophesize(SectionComponent::class);
    $component->getPluginId()->willReturn($plugin_id);

    $this->assertEquals($expected, $updateHelper->isPatternkitComponent($component->reveal()));
  }

  /**
   * Data provider for the testIsPatternkitComponent() test.
   */
  public function providerIsPatternkitComponent(): array {
    $cases = [];

    $cases[] = ['patternkit:patternkit_example_src_example', TRUE];
    $cases[] = ['block:some_other_block', FALSE];

    return $cases;
  }

  /**
   * Tests the getBlockFromComponent method.
   *
   * @covers ::getBlockFromComponent
   * @dataProvider providerGetBlockFromComponent
   */
  public function testGetBlockFromComponent(bool $entity_exists): void {
    $plugin = $this->prophesize(BlockPatternkitBlock::class);
    $block = $this->prophesize(EntityPatternkitBlock::class);
    $plugin->getBlockEntity()->willReturn($block->reveal());

    $block->isNew()->willReturn(!$entity_exists);

    $component = $this->prophesize(BlockInterface::class);
    $component->getPlugin()->willReturn($plugin->reveal());

    $updateHelper = $this->getUpdateHelper();
    $actual = $updateHelper->getBlockFromComponent($component->reveal());
    $this->assertEquals($entity_exists ? $block->reveal() : NULL, $actual);
  }

  /**
   * Data provider for the testGetBlockFromComponent() test.
   */
  public function providerGetBlockFromComponent(): array {
    $cases = [];

    $cases['entity exists'] = [
      TRUE,
    ];

    $cases['entity does not exist'] = [
      TRUE,
    ];

    return $cases;
  }

  /**
   * Tests the getPatternFromComponent method.
   *
   * @covers ::getPatternFromComponent
   */
  public function testGetPatternFromComponent(): void {
    $plugin = $this->prophesize(BlockPatternkitBlock::class);
    $pattern = $this->prophesize(Pattern::class);
    $plugin->getPatternEntity()->willReturn($pattern->reveal());

    $component = $this->prophesize(BlockInterface::class);
    $component->getPlugin()->willReturn($plugin->reveal());

    $updateHelper = $this->getUpdateHelper();
    $actual = $updateHelper->getPatternFromComponent($component->reveal());
    $this->assertEquals($pattern->reveal(), $actual);
  }

  /**
   * @covers ::updatePattern
   */
  public function testUpdatePattern(): void {
    $base_pattern = $this->prophesize(PatternInterface::class);
    $pattern = $this->prophesize(PatternInterface::class);

    // A new default revision should get created.
    $pattern->setNewRevision()->shouldBeCalledOnce();
    $pattern->isDefaultRevision(TRUE)->shouldBeCalledOnce();

    // Values should be updated from the base pattern onto the target pattern.
    $pattern->updateFromPattern($base_pattern->reveal())->shouldBeCalledOnce();

    // The target pattern should get updated.
    $pattern->save()->shouldBeCalledOnce()->willReturn(1);

    $this->getUpdateHelper()->updatePattern($pattern->reveal(), $base_pattern->reveal());
  }

  /**
   * @covers ::updateBlockComponentPattern
   * @covers ::isPatternkitComponent
   * @covers ::getBlockFromComponent
   * @covers ::getPatternFromComponent
   * @covers ::updatePattern
   * @uses \Drupal\patternkit\Plugin\Derivative\PatternkitBlock::derivativeToAssetId()
   */
  public function testUpdateBlockComponentPattern(): void {
    $configuration = [
      'pattern' => 123,
      'patternkit_block_id' => 1,
      'patternkit_block_rid' => 2,
    ];
    $derivative_id = 'patternkit_example_src_example';
    $plugin_id = 'patternkit:' . $derivative_id;
    $pattern_id = '@patternkit/example/src/example';

    // Prepare the component being processed.
    $component = $this->prophesize(BlockInterface::class);
    $component->getPluginId()->willReturn($plugin_id);
    $component->get('configuration')->willReturn($configuration);

    // Prepare the plugin for the component.
    $plugin = $this->prophesize(BlockPatternkitBlock::class);
    $plugin->getDerivativeId()->willReturn($derivative_id);
    $component->getPlugin()->willReturn($plugin->reveal());

    // Prepare the related patternkit block to be loaded from the component.
    $block = $this->prophesize(EntityPatternkitBlock::class);
    $block->getLoadedRevisionId()->willReturn($configuration['patternkit_block_rid']);
    $block->isNew()->willReturn(FALSE);
    $plugin->getBlockEntity()->willReturn($block->reveal());

    // Prepare the pattern to be loaded from the component.
    $pattern = $this->prophesize(PatternInterface::class);
    $pattern->getHash()->willReturn('abc123');
    $pattern->save()->willReturn(2);
    $pattern->getRevisionId()->willReturn($new_pattern_revision = 2);
    $pattern->getAssetId()->willReturn($pattern_id);
    $pattern->getLibrary()->willReturn('patternkit');
    $pattern->getVersion()->willReturn(\Drupal::VERSION);
    $pattern->setNewRevision()->shouldBeCalledOnce();
    $pattern->isDefaultRevision(TRUE)->shouldBeCalledOnce();
    $pattern->updateFromPattern(Argument::type(PatternInterface::class))
      ->willReturn($pattern->reveal());
    $plugin->getPatternEntity()->willReturn($pattern->reveal());

    // Prepare the base pattern to be loaded for updating.
    $base_pattern = $this->prophesize(PatternInterface::class);
    $base_pattern->getHash()->willReturn('def456');
    $base_pattern->getVersion()->willReturn(\Drupal::VERSION);
    $this->patternRepository->getPattern($pattern_id, TRUE)
      ->willReturn($base_pattern->reveal());

    $updateHelper = $this->getUpdateHelper();
    $actual = $updateHelper->updateBlockComponentPattern($component->reveal());

    $this->assertEquals([
      'pattern' => $new_pattern_revision,
    ] + $configuration, $actual);
  }

  /**
   * @covers ::updateBlockComponentPattern
   * @covers ::isPatternkitComponent
   * @uses \Drupal\patternkit\Plugin\Derivative\PatternkitBlock::derivativeToAssetId()
   */
  public function testUpdateBlockComponentPatternWithFilter(): void {
    $library_name = 'my_library';

    // Prepare the component being processed.
    $component = $this->prophesize(BlockInterface::class);
    $component->getPluginId()->willReturn('patternkit:patternkit_example_src_example');

    // Prepare the plugin for the component.
    $plugin = $this->prophesize(BlockPluginInterface::class);
    $plugin->getDerivativeId()->willReturn('patternkit_example_src_example');
    $component->getPlugin()->willReturn($plugin->reveal());

    $updateHelper = $this->getUpdateHelper();

    // The library name shouldn't match, so FALSE should get returned.
    $this->assertFalse($updateHelper->updateBlockComponentPattern($component->reveal(), $library_name));
  }

  /**
   * @covers ::updateBlockComponents
   */
  public function testUpdateBlockComponents(): void {
    $blocks = [];
    $block = $this->prophesize(BlockInterface::class);
    $plugin = $this->prophesize(BlockPluginInterface::class);
    $block->getPlugin()->willReturn($plugin->reveal());

    // Mock 3 blocks for iteration.
    $blocks[] = $block->reveal();
    $blocks[] = $block->reveal();
    $blocks[] = $block->reveal();
    $this->blockStorage->loadMultiple()->willReturn($blocks);

    // Mock for the constructor arguments.
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('patternkit_block')
      ->willReturn($this->blockStorage->reveal());
    $entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    // Partially mock the service to avoid excessive mocking for deeper
    // functions on each block.
    $mock = $this->getMockBuilder(UpdateHelper::class)
      ->setConstructorArgs([
        $entityTypeManager->reveal(),
        $this->patternRepository->reveal(),
        $this->keyValueExpirable->reveal(),
        $this->moduleHandler->reveal(),
        $this->logger->reveal(),
      ])
      ->onlyMethods(['updateBlockComponentPattern'])
      ->getMock();

    // Mock the update method to confirm function behavior.
    $mock->expects($this->exactly(count($blocks)))
      ->method('updateBlockComponentPattern')
      ->willReturnOnConsecutiveCalls(
        // Mock a successful update.
        ['my_config' => []],

        // Mock a failed update.
        FALSE,

        // Mock one more successful update.
        ['more_config' => TRUE],
      );

    $this->assertEquals(2, $mock->updateBlockComponents());
  }

  /**
   * @covers ::updateAllPatternComponents
   * @covers ::layoutHelper
   */
  public function testUpdateAllPatternComponentsWithoutLayoutBuilder(): void {
    $this->moduleHandler->moduleExists('layout_builder')
      ->willReturn(FALSE);

    // Mock for the constructor arguments.
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('patternkit_block')
      ->willReturn($this->blockStorage->reveal());
    $entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    // Partially mock the service to avoid excessive mocking for deeper
    // functions on each block.
    $mock = $this->getMockBuilder(UpdateHelper::class)
      ->setConstructorArgs([
        $entityTypeManager->reveal(),
        $this->patternRepository->reveal(),
        $this->keyValueExpirable->reveal(),
        $this->moduleHandler->reveal(),
        $this->logger->reveal(),
      ])
      ->onlyMethods(['updateBlockComponents'])
      ->getMock();

    $mock->expects($this->once())->method('updateBlockComponents')
      ->willReturn(3);

    $this->assertEquals([3, 0], $mock->updateAllPatternComponents());
  }

  /**
   * @covers ::updateAllPatternComponents
   * @covers ::layoutHelper
   * @covers ::setLayoutHelper
   */
  public function testUpdateAllPatternComponentsWithoutFilters(): void {
    $this->layoutHelper = $this->prophesize(LayoutHelper::class);
    $this->layoutHelper->setUpdateHelper(Argument::type(UpdateHelper::class))
      ->shouldBeCalledOnce();
    $this->layoutHelper->updateLayoutBuilderComponents([])
      ->shouldBeCalledOnce()
      ->willReturn([5, 2]);

    // Mock for the constructor arguments.
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('patternkit_block')
      ->willReturn($this->blockStorage->reveal());
    $entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());

    // Partially mock the service to avoid excessive mocking for deeper
    // functions on each block.
    $mock = $this->getMockBuilder(UpdateHelper::class)
      ->setConstructorArgs([
        $entityTypeManager->reveal(),
        $this->patternRepository->reveal(),
        $this->keyValueExpirable->reveal(),
        $this->moduleHandler->reveal(),
        $this->logger->reveal(),
      ])
      ->onlyMethods(['updateBlockComponents'])
      ->getMock();
    $mock->setLayoutHelper($this->layoutHelper->reveal());

    $mock->expects($this->once())->method('updateBlockComponents')
      ->willReturn(3);

    $this->assertEquals([8, 2], $mock->updateAllPatternComponents());
  }

  /**
   * @covers ::updateAllPatternComponents
   * @covers ::layoutHelper
   */
  public function testUpdateAllPatternComponentsWithFilters(): void {
    $this->markTestIncomplete();
  }

}
