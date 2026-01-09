<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\block\BlockInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\Plugin\Block\Broken;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock as EntityPatternkitBlock;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\Plugin\Block\PatternkitBlock as BlockPatternkitBlock;
use Drupal\patternkit\UpdateHelper;
use Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the UpdateHelper service.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\UpdateHelper
 */
class UpdateHelperTest extends UnitTestCase {

  use ProphecyTrait;
  use PatternkitMockHelperTrait;

  /**
   * The entity type manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityTypeManagerInterface>
   */
  protected ObjectProphecy $entityTypeManager;

  /**
   * Block storage manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  protected ObjectProphecy $blockStorage;

  /**
   * Pattern storage manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  protected ObjectProphecy $patternStorage;

  /**
   * The expirable key value factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface>
   */
  protected ObjectProphecy $keyValueExpirable;

  /**
   * The module handler service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Extension\ModuleHandlerInterface>
   */
  protected ObjectProphecy $moduleHandler;

  /**
   * The patternkit logger channel.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The Patternkit layout helper service, if Layout Builder is available.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\LayoutHelper>
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
    $this->keyValueExpirable = $this->prophesize(KeyValueExpirableFactoryInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
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
      $this->keyValueExpirable->reveal(),
      $this->moduleHandler->reveal(),
    );
    $updateHelper->setLogger($this->logger->reveal());
    $updateHelper->setStringTranslation($this->getStringTranslationStub());

    return $updateHelper;
  }

  /**
   * Get a partial mock of the test subject with injected dependencies.
   *
   * @param string[] $methods
   *   (Optional) An array of test class methods to be mocked. These methods
   *   will be marked for mocking, but expectations for them will still need
   *   to be configured on the returned instance.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\patternkit\UpdateHelper
   *   The mocked object with injected dependencies.
   */
  protected function getPartialMock(array $methods = []): MockObject {
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
        $this->keyValueExpirable->reveal(),
        $this->moduleHandler->reveal(),
      ])
      ->onlyMethods($methods)
      ->getMock();
    $mock->setLogger($this->logger->reveal());
    $mock->setStringTranslation($this->getStringTranslationStub());

    return $mock;
  }

  /**
   * Tests the isPatternkitComponent method.
   *
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
  public static function providerIsPatternkitComponent(): array {
    $cases = [];

    $cases[] = ['patternkit:patternkit_example_src_example', TRUE];
    $cases[] = ['block:some_other_block', FALSE];

    return $cases;
  }

  /**
   * Tests the getBlockFromComponent method.
   *
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
  public static function providerGetBlockFromComponent(): array {
    return [
      'entity exists' => [TRUE],
      'entity does not exist' => [FALSE],
    ];
  }

  /**
   * Tests the getPatternFromComponent method.
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

  public function testUpdateBlockComponentPattern(): void {
    $configuration = [
      'pattern' => 123,
      'patternkit_block_id' => 1,
      'patternkit_block_rid' => 2,
    ];
    $derivative_id = 'patternkit_example_src_example';
    $plugin_id = 'patternkit:' . $derivative_id;
    $pattern_id = '@patternkit/example/src/example';

    // Prepare the related patternkit block to be loaded from the component.
    $block = $this->getMockPatternkitBlockEntity([
      'is_new' => FALSE,
    ]);
    $block->getLoadedRevisionId()
      ->willReturn($configuration['patternkit_block_rid']);

    // Prepare the pattern to be loaded from the component.
    $pattern = $this->getMockPatternEntity([
      'asset_id' => $pattern_id,
      'hash' => 'abc123',
      'version' => 'OLD',
    ]);

    // Prepare the base pattern to be loaded for updating.
    $base_pattern = $this->getMockPatternEntity([
      'asset_id' => $pattern_id,
      'hash' => 'def456',
      'version' => 'NEW',
    ]);

    // Prepare the plugin being processed.
    $plugin = $this->getMockPatternkitBlockPlugin([
      'plugin_id' => $plugin_id,
      'derivative_id' => $derivative_id,
      'configuration' => $configuration,
      'block' => $block->reveal(),
      'pattern' => $pattern->reveal(),
      'base_pattern' => $base_pattern->reveal(),
    ]);

    // Prepare the component being processed.
    $component = $this->getMockBlockComponent([
      'plugin_id' => $plugin_id,
      'configuration' => $configuration,
      'plugin' => $plugin->reveal(),
    ]);

    // Prepare an "updated" pattern instance.
    $updated_pattern = $this->prophesize(PatternInterface::class);
    $updated_pattern->getRevisionId()->willReturn($new_pattern_revision = 2);

    // Expect the plugin's update function to be called.
    $plugin->updatePattern($pattern->reveal(), $base_pattern->reveal())
      ->shouldBeCalledOnce()
      ->will(function () use ($plugin, $updated_pattern, $new_pattern_revision, $configuration) {
        // Update the returned configuration.
        $plugin->getConfiguration()->willReturn([
          'pattern' => $new_pattern_revision,
        ] + $configuration);

        return $updated_pattern->reveal();
      });

    $updateHelper = $this->getUpdateHelper();
    $actual = $updateHelper->updateBlockComponentPattern($component->reveal());

    $this->assertEquals([
      'pattern' => $new_pattern_revision,
    ] + $configuration, $actual);
  }

  /**
   * Test various failure scenarios while updating a block component.
   *
   * This is written using switch statements and strings to select the correct
   * test case to ensure a clean running environment for each scenario and clear
   * labeling for any failures without having to create a separate test method
   * for each scenario. Loading the necessary mocking in some way for each test
   * case from the data provider would be more ideal, but since the data
   * is only run once and can only provide static arguments this isn't easily
   * achievable.
   *
   * @dataProvider providerUpdateBlockComponentPatternWithFailures
   */
  public function testUpdateBlockComponentPatternWithFailures(string $scenario): void {
    $configuration = [
      'pattern' => 123,
      'patternkit_block_id' => 1,
      'patternkit_block_rid' => 2,
    ];
    $derivative_id = 'patternkit_example_src_example';
    $plugin_id = 'patternkit:' . $derivative_id;
    $pattern_id = '@patternkit/example/src/example';

    // Prepare the related patternkit block to be loaded from the component.
    $block = $this->getMockPatternkitBlockEntity([
      'is_new' => FALSE,
    ]);
    $block->getLoadedRevisionId()
      ->willReturn($configuration['patternkit_block_rid']);

    // Prepare the pattern to be loaded from the component.
    $pattern = $this->getMockPatternEntity([
      'asset_id' => $pattern_id,
      'hash' => 'abc123',
      'version' => 'OLD',
    ]);

    // Prepare the base pattern to be loaded for updating.
    $base_pattern = $this->getMockPatternEntity([
      'asset_id' => $pattern_id,
      'hash' => 'def456',
      'version' => 'NEW',
    ]);

    // Prepare the plugin being processed.
    $plugin = $this->getMockPatternkitBlockPlugin([
      'plugin_id' => $plugin_id,
      'derivative_id' => $derivative_id,
      'configuration' => $configuration,
      'block' => $block->reveal(),
      'pattern' => $pattern->reveal(),
      'base_pattern' => $base_pattern->reveal(),
    ]);

    // Prepare the component being processed.
    $component = $this->getMockBlockComponent([
      'plugin_id' => $plugin_id,
      'configuration' => $configuration,
      'plugin' => $plugin->reveal(),
    ]);

    $mock = $this->getPartialMock([
      'getBlockFromComponent',
      'getPatternFromComponent',
    ]);

    $this->logger->info('Updating block plugin with id @plugin.', [
      '@plugin' => $plugin_id,
    ])->shouldBeCalledOnce();

    switch ($scenario) {
      case 'failure to load block entity':
        $mock->method('getBlockFromComponent')->willReturn(NULL);

        $this->logger->error(
          'Unable to load the block entity for block @block_id. Check the logs for more info.',
          ['@block_id' => $plugin_id])
          ->shouldBeCalledOnce();

        $result = $mock->updateBlockComponentPattern($component->reveal());
        $this->assertFalse($result, 'Expected FALSE when the block entity could not be loaded.');
        break;

      case 'exception loading block entity':
        $mock->method('getBlockFromComponent')->willThrowException(new EntityStorageException());

        $this->logger->error(
          'Unable to load the block entity for block @block_id. Check the logs for more info.',
          ['@block_id' => $plugin_id]
        )->shouldBeCalledOnce();

        $result = $mock->updateBlockComponentPattern($component->reveal());
        $this->assertFalse($result, 'Expected FALSE when fetching the block entity throws an exception.');
        break;

      case 'failure to load pattern entity':
        $mock->method('getBlockFromComponent')->willReturn($block->reveal());
        $mock->method('getPatternFromComponent')->willReturn(NULL);

        $this->logger->error(
          'Unable to load the pattern for block @block_id. Check the logs for more info.',
          ['@block_id' => $plugin_id]
        )->shouldBeCalledOnce();

        $result = $mock->updateBlockComponentPattern($component->reveal());
        $this->assertFalse($result, 'Expected FALSE when the pattern entity could not be loaded.');
        break;

      case 'exception loading pattern entity':
        $mock->method('getBlockFromComponent')->willReturn($block->reveal());
        $mock->method('getPatternFromComponent')->willThrowException(new PluginException());

        $this->logger->error(
          'Unable to load the pattern for block @block_id. Check the logs for more info.',
          ['@block_id' => $plugin_id]
        )->shouldBeCalledOnce();

        $result = $mock->updateBlockComponentPattern($component->reveal());
        $this->assertFalse($result, 'Expected FALSE when fetching the pattern entity throws an exception.');
        break;

      case 'failure to load base pattern':
        $mock->method('getBlockFromComponent')->willReturn($block->reveal());
        $mock->method('getPatternFromComponent')->willReturn($pattern->reveal());
        $plugin->getBasePattern()->willThrow(new UnknownPatternException());

        $this->logger->error('Failed to load base pattern for @pattern.', ['@pattern' => $pattern_id])
          ->shouldBeCalledOnce();

        $result = $mock->updateBlockComponentPattern($component->reveal());
        $this->assertFalse($result, 'Expected FALSE when loading the base pattern throws an exception.');
        break;

      case 'update is not needed':
        $mock->method('getBlockFromComponent')->willReturn($block->reveal());
        $mock->method('getPatternFromComponent')->willReturn($pattern->reveal());
        $base_pattern->getHash()->willReturn('abc123');

        $this->logger->info('Pattern "@pattern" did not need to be updated.', ['@pattern' => $pattern_id])
          ->shouldBeCalledOnce();

        $result = $mock->updateBlockComponentPattern($component->reveal());
        $this->assertFalse($result, 'Expected FALSE when a pattern is already up to date.');
        break;

      case 'exception during pattern update':
        $mock->method('getBlockFromComponent')->willReturn($block->reveal());
        $mock->method('getPatternFromComponent')->willReturn($pattern->reveal());
        $plugin->updatePattern($pattern->reveal(), $base_pattern->reveal())
          ->willThrow(new EntityStorageException());

        $this->logger->notice('Updating pattern "@pattern" from "@old" to "@new".', [
          '@pattern' => $pattern_id,
          '@old' => 'OLD',
          '@new' => 'NEW',
        ])->shouldBeCalledOnce();
        $this->logger->error('Failed to update pattern "@pattern" for block "@block_id".', [
          '@pattern' => $pattern_id,
          '@block_id' => $plugin_id,
        ])->shouldBeCalledOnce();

        $result = $mock->updateBlockComponentPattern($component->reveal());
        $this->assertFalse($result, 'Expected FALSE when an exception occurs during the update process.');
        break;

    }
  }

  /**
   * Data provider for the testUpdateBlockComponentPatternWithFailures() test.
   */
  public static function providerUpdateBlockComponentPatternWithFailures(): array {
    $scenarios = [
      'failure to load block entity',
      'exception loading block entity',
      'failure to load pattern entity',
      'exception loading pattern entity',
      'failure to load base pattern',
      'update is not needed',
      'exception during pattern update',
    ];

    // Create an array keyed by the scenario name as well for readability in
    // test results.
    $cases = [];
    foreach ($scenarios as $name) {
      $cases[$name] = [$name];
    }

    return $cases;
  }

  public function testUpdateBlockComponents(): void {
    $filter = [];
    $blocks = [];
    $block = $this->prophesize(BlockInterface::class);
    $plugin = $this->prophesize(BlockPluginInterface::class);

    // Mock 3 blocks for iteration.
    $blocks[] = $block->reveal();
    $blocks[] = $block->reveal();
    $blocks[] = $block->reveal();
    $this->blockStorage->loadMultiple()->willReturn($blocks);

    // Partially mock the service to avoid excessive mocking for deeper
    // functions on each block.
    $mock = $this->getPartialMock([
      'filterComponent',
      'updateBlockComponentPattern',
    ]);

    $mock->expects($this->exactly(count($blocks)))
      ->method('filterComponent')
      ->with($this->isInstanceOf(BlockInterface::class), $filter)
      ->willReturn($plugin->reveal());

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

  public function testUpdateAllPatternComponentsWithoutLayoutBuilder(): void {
    $this->moduleHandler->moduleExists('layout_builder')
      ->willReturn(FALSE);

    // Partially mock the service to avoid excessive mocking for deeper
    // functions on each block.
    $mock = $this->getPartialMock(['updateBlockComponents']);

    $mock->expects($this->once())->method('updateBlockComponents')
      ->willReturn(3);

    $this->assertEquals([3, 0], $mock->updateAllPatternComponents());
  }

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
    $mock = $this->getPartialMock(['updateBlockComponents']);
    $mock->setLayoutHelper($this->layoutHelper->reveal());

    $mock->expects($this->once())->method('updateBlockComponents')
      ->with([])
      ->willReturn(3);

    $this->assertEquals([8, 2], $mock->updateAllPatternComponents());
  }

  /**
   * Test updating all pattern components with filters.
   *
   * @dataProvider providerUpdateAllPatternComponentsWithFilters
   */
  public function testUpdateAllPatternComponentsWithFilters(array $filter, array $expected_logging, array $options): void {
    [
      'layout_helper' => $use_layout_helper,
    ] = $options;

    $expected_blocks = 0;
    $expected_entities = 0;

    // Configure mocking and expected side effects for layout builder usage.
    if ($use_layout_helper ?? FALSE) {
      // Pretend layout builder components and layouts were updated.
      $expected_blocks = 5;
      $expected_entities = 2;

      $this->layoutHelper = $this->prophesize(LayoutHelper::class);
      $this->layoutHelper->updateLayoutBuilderComponents($filter)
        ->shouldBeCalledOnce()
        ->willReturn([
          $expected_blocks,
          $expected_entities,
        ]);
    }

    // Configure log messaging expectations.
    foreach ($expected_logging as $log) {
      [$channel, $message] = $log;

      if ($context = $log[2] ?? NULL) {
        $this->logger->$channel($message, $context)->shouldBeCalledOnce();
      }
      else {
        $this->logger->$channel($message)->shouldBeCalledOnce();
      }
    }

    $mock = $this->getPartialMock([
      'updateBlockComponents',
      'layoutHelper',
    ]);
    $mock->expects($this->atLeastOnce())->method('layoutHelper')
      ->willReturn($use_layout_helper ? $this->layoutHelper->reveal() : NULL);

    $mock
      ->method('updateBlockComponents')
      ->with($filter)
      ->willReturnCallback(function () use (&$expected_blocks): int {
        // Pretend a number of blocks were updated.
        $num_blocks = 3;

        // Increment the expected value to match.
        $expected_blocks += $num_blocks;

        return $num_blocks;
      });

    $actual = $mock->updateAllPatternComponents($filter);
    $this->assertEquals([$expected_blocks, $expected_entities], $actual);
  }

  /**
   * Data provider for the testUpdateAllPatternComponentsWithFilters() test.
   */
  public static function providerUpdateAllPatternComponentsWithFilters(): array {
    $cases = [];

    $cases['entity type filter'] = [
      // Filter values.
      ['entity_type' => 'node'],
      // Expected log messages.
      [
        [
          'info',
          'Updating patterns throughout defined layouts.',
        ],
        [
          'info',
          'Skipping block updates due to the presence of entity filters.',
        ],
        [
          'notice',
          'Updated @entities entity layouts and @blocks Patternkit blocks.',
          [
            '@entities' => 2,
            '@blocks' => 5,
          ],
        ],
      ],
      // Test run options.
      [
        'layout_helper' => TRUE,
      ],
    ];

    $cases['entity type filter without layout builder'] = [
      // Filter values.
      ['entity_type' => 'node'],
      // Expected log messages.
      [
        [
          'info',
          'Skipping block updates due to the presence of entity filters.',
        ],
        [
          'notice',
          'Updated @entities entity layouts and @blocks Patternkit blocks.',
          [
            '@entities' => 0,
            '@blocks' => 0,
          ],
        ],
      ],
      // Test run options.
      [
        'layout_helper' => FALSE,
      ],
    ];

    return $cases;
  }

  /**
   * Test filter behaviors of the filterComponents() method.
   *
   * @param array $component
   *   Configuration for a mock test component that will be passed to
   *   @link \Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait::getMockComponent()@endlink.
   * @param array $filter
   *   Filter configuration to test with the filterComponents() method.
   * @param bool $expected
   *   The expected result of the function execution. For these tests, a boolean
   *   FALSE result would indicate an error or failed filter match, but a TRUE
   *   instead will match against a boolean cast of the returned plugin result.
   * @param string $component_type
   *   The type of component to be tested with. Valid options are 'block' or
   *   'layout'.
   *
   * @dataProvider providerFilterComponent
   */
  public function testFilterComponent(array $component, array $filter, bool $expected, string $component_type) {
    $component = $this->getMockComponent($component_type, $component);

    $updateHelper = $this->getUpdateHelper();
    $actual = $updateHelper->filterComponent($component->reveal(), $filter);
    $this->assertEquals($expected, (bool) $actual);
  }

  /**
   * Data provider for the testFilterComponent() test.
   */
  public static function providerFilterComponent(): array {
    $cases = [];

    $derivative_id = Random::string();

    // Test a valid block with no filters.
    $cases['no filter'] = [
      // Mock configuration.
      [
        'plugin_id' => 'patternkit:' . $derivative_id,
        'plugin' => [
          'derivative_id' => $derivative_id,
          'pattern_id' => '@patternkit/atoms/example/src/example',
        ],
      ],
      // Filter values.
      [],
      // Expected result.
      TRUE,
    ];

    // Test a valid block with a matching library filter.
    $cases['library filter match'] = [
      // Mock configuration.
      [
        'plugin_id' => 'patternkit:' . $derivative_id,
        'plugin' => [
          'derivative_id' => $derivative_id,
          'pattern_id' => '@patternkit/atoms/example/src/example',
        ],
      ],
      // Filter values.
      ['library' => '@patternkit'],
      // Expected result.
      TRUE,
    ];

    // Test a valid block with an exclusionary library filter.
    $cases['library filter miss'] = [
      // Mock configuration.
      [
        'plugin_id' => 'patternkit:' . $derivative_id,
        'plugin' => [
          'derivative_id' => $derivative_id,
          'pattern_id' => '@patternkit/atoms/example/src/example',
        ],
      ],
      // Filter values.
      ['library' => '@not-patternkit'],
      // Expected result.
      FALSE,
    ];

    // Test a valid block with a matching pattern ID filter.
    $cases['pattern filter match'] = [
      // Mock configuration.
      [
        'plugin_id' => 'patternkit:' . $derivative_id,
        'plugin' => [
          'derivative_id' => $derivative_id,
          'pattern_id' => '@patternkit/atoms/example/src/example',
        ],
      ],
      // Filter values.
      ['pattern' => '@patternkit/atoms/example/src/example'],
      // Expected result.
      TRUE,
    ];

    // Test a valid block with an exclusionary pattern ID filter.
    $cases['pattern filter miss'] = [
      // Mock configuration.
      [
        'plugin_id' => 'patternkit:' . $derivative_id,
        'plugin' => [
          'derivative_id' => $derivative_id,
          'pattern_id' => '@patternkit/atoms/example/src/example',
        ],
      ],
      // Filter values.
      ['pattern' => '@a/different/pattern'],
      // Expected result.
      FALSE,
    ];

    // Run each test case with both component types.
    $test_cases = [];
    foreach ($cases as $name => $args) {
      $test_cases[$name . ' with block'] = [...$args, 'block'];
      $test_cases[$name . ' with layout builder'] = [...$args, 'layout'];
    }

    return $test_cases;
  }

  /**
   * Test error scenarios when filtering components.
   *
   * This is written using switch statements and strings to select the correct
   * test case to ensure a clean running environment for each scenario and clear
   * labeling for any failures without having to create a separate test method
   * for each scenario. Loading the necessary mocking in some way for each test
   * case from the data provider would be more ideal, but since the data
   * is only run once and can only provide static arguments this isn't easily
   * achievable.
   *
   * @dataProvider providerFilterComponentWithErrors
   */
  public function testFilterComponentWithErrors(string $scenario, string $component_type) {
    $derivative_id = $this->getRandomGenerator()->string();
    $plugin_id = 'patternkit:' . $derivative_id;

    switch ($scenario) {
      case 'not a patternkit component':
        $component = $this->getMockComponent($component_type, [
          'plugin_id' => 'something-else:' . $derivative_id,
        ]);

        $updateHelper = $this->getUpdateHelper();
        $actual = $updateHelper->filterComponent($component->reveal(), []);
        $this->assertFalse($actual);
        break;

      case 'broken block':
        $uuid = $this->getRandomGenerator()->string();
        $plugin = $this->prophesize(Broken::class);
        $component = $this->getMockComponent($component_type, [
          'plugin_id' => $plugin_id,
          'plugin' => $plugin->reveal(),
          'uuid' => $uuid,
        ]);

        $this->logger->warning('Encountered unknown plugin type "@plugin" while processing block ID "@block_id".', [
          '@plugin' => get_class($plugin->reveal()),
          '@block_id' => $uuid,
        ])->shouldBeCalledOnce();

        $updateHelper = $this->getUpdateHelper();
        $actual = $updateHelper->filterComponent($component->reveal(), []);
        $this->assertFalse($actual);
        break;

      case 'failure to load derivative id':
        $component = $this->getMockComponent($component_type, [
          'plugin_id' => $plugin_id,
          'plugin' => [
            'plugin_id' => $plugin_id,
            'derivative_id' => NULL,
            'pattern_id' => '@patternkit/atoms/example/src/example',
          ],
        ]);

        $this->logger->error('Encountered broken block plugin: @block_id', [
          '@block_id' => $plugin_id,
        ])->shouldBeCalledOnce();

        $updateHelper = $this->getUpdateHelper();
        $actual = $updateHelper->filterComponent($component->reveal(), []);
        $this->assertFalse($actual);
        break;

      case 'plugin exception':
        $uuid = $this->getRandomGenerator()->string();
        $error_message = $this->getRandomGenerator()->sentences(5);

        $component = $this->getMockComponent($component_type, [
          'plugin_id' => $plugin_id,
          'uuid' => $uuid,
        ]);
        $component->getPlugin()->willThrow(new PluginException($error_message));

        $this->logger->error('Unable to load the plugin for block @block_id: @message', [
          '@block_id' => $uuid,
          '@message' => $error_message,
        ])->shouldBeCalledOnce();

        $updateHelper = $this->getUpdateHelper();
        $actual = $updateHelper->filterComponent($component->reveal(), []);
        $this->assertFalse($actual);
        break;

    }
  }

  /**
   * Data provider for the testFilterComponentWithErrors() test.
   */
  public static function providerFilterComponentWithErrors(): array {
    $scenarios = [
      'not a patternkit component',
      'broken block',
      'failure to load derivative id',
      'plugin exception',
    ];

    // Run each test case with both component types.
    $cases = [];
    foreach ($scenarios as $scenario) {
      $cases[$scenario . ' with block'] = [$scenario, 'block'];
      $cases[$scenario . ' with layout builder'] = [$scenario, 'layout'];
    }

    return $cases;
  }

}
