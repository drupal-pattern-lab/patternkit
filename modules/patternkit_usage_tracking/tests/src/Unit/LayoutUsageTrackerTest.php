<?php

namespace Drupal\Tests\patternkit_usage_tracking\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\Utility\PatternDetector;
use Drupal\patternkit_usage_tracking\LayoutUsageTracker;
use Drupal\patternkit_usage_tracking\PatternUsageManager;
use Drupal\patternkit_usage_tracking\UsageRevisionHelper;
use Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test the LayoutUsageTracker service.
 *
 * @group patternkit
 * @group patternkit_usage_tracking
 * @coversDefaultClass \Drupal\patternkit_usage_tracking\LayoutUsageTracker
 * @covers \Drupal\patternkit_usage_tracking\PatternLayoutUsageInsertTrait
 */
class LayoutUsageTrackerTest extends UnitTestCase {

  use ProphecyTrait;
  use PatternkitMockHelperTrait;

  /**
   * The pattern usage manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit_usage_tracking\PatternUsageManager>
   */
  protected ObjectProphecy $usageManager;

  /**
   * The layout helper service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\LayoutHelper>
   */
  protected ObjectProphecy $layoutHelper;

  /**
   * The pattern detector service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Utility\PatternDetector>
   */
  protected ObjectProphecy $patternDetector;

  /**
   * The section storage manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface>
   */
  protected ObjectProphecy $sectionStorageManager;

  /**
   * The pattern usage revision helper service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit_usage_tracking\UsageRevisionHelper>
   */
  protected ObjectProphecy $revisionHelper;

  /**
   * A mocked entity for testing operations.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\ContentEntityInterface>
   */
  protected ObjectProphecy $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->usageManager = $this->prophesize(PatternUsageManager::class);
    $this->layoutHelper = $this->prophesize(LayoutHelper::class);
    $this->patternDetector = $this->prophesize(PatternDetector::class);
    $this->sectionStorageManager = $this->prophesize(SectionStorageManagerInterface::class);
    $this->revisionHelper = $this->prophesize(UsageRevisionHelper::class);

    $this->entity = $this->prophesize(ContentEntityInterface::class);
  }

  /**
   * A helper method to instantiate an instance of the subject being tested.
   *
   * This allows for late instantiation of the prophecy objects passed into the
   * constructor to enable more customization of the dependencies per test
   * without as much boilerplate in each test case.
   *
   * @return \Drupal\patternkit_usage_tracking\LayoutUsageTracker
   *   An instantiated instance of the test subject with all configured
   *   dependency mocks injected appropriately.
   */
  protected function getTestSubject(): LayoutUsageTracker {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $configFactory = $this->prophesize(ConfigFactoryInterface::class);

    return new LayoutUsageTracker(
      $entityTypeManager->reveal(),
      $entityFieldManager->reveal(),
      $configFactory->reveal(),
      $this->usageManager->reveal(),
      $this->layoutHelper->reveal(),
      $this->patternDetector->reveal(),
      $this->sectionStorageManager->reveal(),
      $this->revisionHelper->reveal(),
    );
  }

  /**
   * Get a partial mock of the test subject with injected dependencies.
   *
   * @param string[] $methods
   *   (Optional) An array of test class methods to be mocked. These methods
   *   will be marked for mocking, but expectations for them will still need
   *   to be configured on the returned instance.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\patternkit_usage_tracking\LayoutUsageTracker
   *   The mocked object with injected dependencies.
   */
  protected function getPartialMock(array $methods = []): MockObject {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $configFactory = $this->prophesize(ConfigFactoryInterface::class);

    return $this->getMockBuilder(LayoutUsageTracker::class)
      ->setConstructorArgs([
        $entityTypeManager->reveal(),
        $entityFieldManager->reveal(),
        $configFactory->reveal(),
        $this->usageManager->reveal(),
        $this->layoutHelper->reveal(),
        $this->patternDetector->reveal(),
        $this->sectionStorageManager->reveal(),
        $this->revisionHelper->reveal(),
      ])
      ->onlyMethods($methods)
      ->getMock();
  }

  public function testShouldTrackEntity(): void {
    $mock = $this->getPartialMock(['isLayoutCompatibleEntity']);

    // Test with a mismatched interface.
    $entity = $this->prophesize(EntityInterface::class);
    $this->assertFalse($mock->shouldTrackEntity($entity->reveal()),
      'Expected FALSE when tracking a non-content entity.');

    // Test with a matching interface, but no layout.
    $entity = $this->prophesize(ContentEntityInterface::class);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')
      ->with($entity->reveal())
      ->willReturn(FALSE);
    $this->assertFalse($mock->shouldTrackEntity($entity->reveal()),
      'Expected FALSE when tracking an entity without layout compatibility.');

    // Test with full compatibility.
    $mock = $this->getPartialMock(['isLayoutCompatibleEntity']);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')
      ->with($entity->reveal())
      ->willReturn(TRUE);
    $this->assertTrue($mock->shouldTrackEntity($entity->reveal()),
      'Expected TRUE when tracking an entity with full compatibility.');
  }

  public function testTrackOnEntityUpdate(): void {
    $mock = $this->getPartialMock([
      'trackOnEntityCreation',
      'shouldTrackAllRevisions',
    ]);

    // Test behavior while tracking all revisions.
    $mock->method('shouldTrackAllRevisions')
      ->willReturn(TRUE);

    // The update method should simply pass everything to the create method for
    // insertion of all detected records.
    $mock->expects($this->once())
      ->method('trackOnEntityCreation')
      ->with($this->entity->reveal());

    $mock->trackOnEntityUpdate($this->entity->reveal());
  }

  public function testTrackOnEntityUpdateOnlyLatestRevision(): void {
    $mock = $this->getPartialMock([
      'trackOnEntityCreation',
      'shouldTrackAllRevisions',
    ]);

    // Test behavior while tracking only the latest revision.
    $mock->method('shouldTrackAllRevisions')
      ->willReturn(FALSE);

    // The entity creation method should not be called since more complex
    // tracking is needed for revisions.
    $mock->expects($this->never())
      ->method('trackOnEntityCreation');

    // The update event should be passed to the revision helper for processing.
    $this->revisionHelper->trackOnEntityUpdate($this->entity->reveal())
      ->shouldBeCalledOnce();

    $mock->trackOnEntityUpdate($this->entity->reveal());
  }

  public function testTrackOnEntityCreation(): void {
    $layout = $this->prophesize(SectionListInterface::class)
      ->willImplement(FieldItemListInterface::class);

    // Mock layout data with pattern components to be discovered.
    $data = [
      1 => [
        'uuid_1' => [
          'plugin' => [
            'block' => [
              'data' => [],
            ],
            'pattern' => [
              'asset_id' => $pattern_id_1 = '@patternkit/atoms/example/src/example',
              'dependencies' => [],
            ],
          ],
        ],
        'uuid_2' => [
          'plugin' => [
            'block' => [
              'data' => [],
            ],
            'pattern' => [
              'asset_id' => $pattern_id_2 = '@patternkit/atoms/example_ref/src/example_ref',
              'dependencies' => [
                '@patternkit/atoms/example/src/example',
              ],
            ],
          ],
        ],
      ],
    ];
    $components = $this->mockLayoutComponents($data);

    $this->layoutHelper->getPatternComponentsFromLayout($layout->reveal())
      ->willReturn($components);

    // Don't detect any nested patterns.
    $this->patternDetector->detect(Argument::type(PatternInterface::class), Argument::type('string'))
      ->willReturn([]);

    // Expect both top-level patterns to be registered.
    $this->usageManager->register($this->entity->reveal(), $pattern_id_1, '1.uuid_1:@patternkit/atoms/example/src/example')
      ->shouldBeCalledOnce();
    $this->usageManager->register($this->entity->reveal(), $pattern_id_2, '1.uuid_2:@patternkit/atoms/example_ref/src/example_ref')
      ->shouldBeCalledOnce();

    // Create a partial mock to override trait methods.
    $mock = $this->getPartialMock([
      'isLayoutCompatibleEntity',
      'getSectionStorageForEntity',
    ]);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')
      ->with($this->entity->reveal())
      ->willReturn(TRUE);
    $mock->expects($this->once())
      ->method('getSectionStorageForEntity')
      ->with($this->entity->reveal())
      ->willReturn($layout->reveal());

    $mock->trackOnEntityCreation($this->entity->reveal());
  }

  public function testTrackOnEntityCreationWithNestedPatterns(): void {
    $layout = $this->prophesize(SectionListInterface::class)
      ->willImplement(FieldItemListInterface::class);

    // Mock layout data with pattern components to be discovered.
    $data = [
      1 => [
        'uuid_1' => [
          'plugin' => [
            'block' => [
              'data' => [],
            ],
            'pattern' => [
              'asset_id' => $pattern_id_1 = '@patternkit/atoms/example/src/example',
              'dependencies' => [],
            ],
          ],
        ],
        'uuid_2' => [
          'plugin' => [
            'block' => [
              'data' => $ref_data = [
                'nested_reference' => [
                  'text' => 'Some text',
                ],
              ],
            ],
            'pattern' => [
              'asset_id' => $pattern_id_2 = '@patternkit/atoms/example_ref/src/example_ref',
              'dependencies' => [
                '@patternkit/atoms/example/src/example',
              ],
            ],
          ],
        ],
      ],
    ];
    $components = $this->mockLayoutComponents($data);

    $this->layoutHelper->getPatternComponentsFromLayout($layout->reveal())
      ->willReturn($components);

    // Detect nested patterns in content from the second component.
    $this->patternDetector->detect(Argument::type(PatternInterface::class), '{}')
      ->willReturn([]);
    $this->patternDetector->detect(Argument::type(PatternInterface::class), json_encode($ref_data))
      ->willReturn([
        '@patternkit/atoms/example/src/example' => '@patternkit/atoms/example/src/example',
      ]);

    // Expect top-level patterns and one nested pattern to be registered.
    $this->usageManager->register($this->entity->reveal(), $pattern_id_1, '1.uuid_1:@patternkit/atoms/example/src/example')
      ->shouldBeCalledOnce();
    $this->usageManager->register($this->entity->reveal(), $pattern_id_2, '1.uuid_2:@patternkit/atoms/example_ref/src/example_ref')
      ->shouldBeCalledOnce();
    $this->usageManager->register(
      $this->entity->reveal(),
      '@patternkit/atoms/example/src/example',
      '1.uuid_2:@patternkit/atoms/example_ref/src/example_ref=>@patternkit/atoms/example/src/example'
    )->shouldBeCalledOnce();

    // Create a partial mock to override trait methods.
    $mock = $this->getPartialMock([
      'isLayoutCompatibleEntity',
      'getSectionStorageForEntity',
    ]);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')
      ->with($this->entity->reveal())
      ->willReturn(TRUE);
    $mock->expects($this->once())
      ->method('getSectionStorageForEntity')
      ->with($this->entity->reveal())
      ->willReturn($layout->reveal());

    $mock->trackOnEntityCreation($this->entity->reveal());
  }

  public function testTrackOnEntityDelete(): void {
    $this->usageManager->deleteByEntity($this->entity->reveal())
      ->shouldBeCalledOnce();

    $tracker = $this->getTestSubject();
    $tracker->trackOnEntityDelete($this->entity->reveal(), 'entity');
  }

}
