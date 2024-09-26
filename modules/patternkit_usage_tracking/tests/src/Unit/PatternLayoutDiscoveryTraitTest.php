<?php

namespace Drupal\Tests\patternkit_usage_tracking\Unit;

use Drupal\Core\Block\Plugin\Block\Broken;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\Utility\PatternDetector;
use Drupal\patternkit_usage_tracking\PatternLayoutDiscoveryTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests for the PatternLayoutDiscoveryTrait implementation.
 *
 * @coversDefaultClass \Drupal\patternkit_usage_tracking\PatternLayoutDiscoveryTrait
 *
 * @group patternkit
 * @group patternkit_usage_tracking
 *
 * @phpstan-import-type LBComponentMockConfig from PatternkitMockHelperTrait
 */
class PatternLayoutDiscoveryTraitTest extends UnitTestCase {

  use PatternkitMockHelperTrait;
  use ProphecyTrait;

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
   * The mock under test that uses PatternLayoutDiscoveryTrait.
   *
   * @var \Drupal\Tests\patternkit_usage_tracking\Unit\TestClass
   */
  protected TestClass $discovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->layoutHelper = $this->prophesize(LayoutHelper::class);
    $this->patternDetector = $this->prophesize(PatternDetector::class);

    $this->discovery = new TestClass($this->layoutHelper->reveal(), $this->patternDetector->reveal());
  }

  /**
   * @covers ::getPatternsByLayoutComponent
   * @covers ::identifyPatternsByComponent
   * @covers ::getNestedPatterns
   */
  public function testGetPatternsByLayoutComponentWithoutNesting(): void {
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
    $layout = $this->mockLayoutWithData($data);

    // Don't detect any nested patterns.
    $this->patternDetector->detect(Argument::type(PatternInterface::class), Argument::type('string'))
      ->willReturn([]);

    $patterns = $this->discovery->getPatternsByLayoutComponent($layout->reveal());

    // Validate all components were discovered.
    $this->assertEqualsCanonicalizing(['uuid_1', 'uuid_2'], array_keys($patterns));

    // Validate each usage discovery.
    $this->assertEquals([
      'uuid' => 'uuid_1',
      'layout_path' => "1.uuid_1:$pattern_id_1",
      'pattern_id' => $pattern_id_1,
      'nested' => [],
    ], $patterns['uuid_1']);
    $this->assertEquals([
      'uuid' => 'uuid_2',
      'layout_path' => "1.uuid_2:$pattern_id_2",
      'pattern_id' => $pattern_id_2,
      'nested' => [],
    ], $patterns['uuid_2']);
  }

  /**
   * @covers ::getPatternsByLayoutComponent
   * @covers ::identifyPatternsByComponent
   * @covers ::getNestedPatterns
   */
  public function testGetPatternsByLayoutComponentWithNesting(): void {
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
    $layout = $this->mockLayoutWithData($data);

    // Detect nested patterns in content from the second component.
    $this->patternDetector->detect(Argument::type(PatternInterface::class), '{}')
      ->willReturn([]);
    $this->patternDetector->detect(Argument::type(PatternInterface::class), json_encode($ref_data))
      ->willReturn([
        '@patternkit/atoms/example/src/example' => '@patternkit/atoms/example/src/example',
      ]);

    $patterns = $this->discovery->getPatternsByLayoutComponent($layout->reveal());

    // Validate all components were discovered.
    $this->assertEqualsCanonicalizing(['uuid_1', 'uuid_2'], array_keys($patterns));

    // Validate each usage discovery.
    $this->assertEquals([
      'uuid' => 'uuid_1',
      'layout_path' => "1.uuid_1:$pattern_id_1",
      'pattern_id' => $pattern_id_1,
      'nested' => [],
    ], $patterns['uuid_1']);
    $this->assertEquals([
      'uuid' => 'uuid_2',
      'layout_path' => "1.uuid_2:$pattern_id_2",
      'pattern_id' => $pattern_id_2,
      'nested' => [
        '@patternkit/atoms/example/src/example' => '@patternkit/atoms/example/src/example',
      ],
    ], $patterns['uuid_2']);
  }

  /**
   * @covers ::getPatternsByLayoutComponent
   * @covers ::identifyPatternsByComponent
   * @covers ::getNestedPatterns
   */
  public function testGetPatternsByLayoutComponentWithBrokenPlugin(): void {
    // Mock a broken block plugin to be included in the test data.
    $broken_block = $this->prophesize(Broken::class);

    // Mock layout data with pattern components to be discovered.
    $data = [
      1 => [
        'uuid_1' => [
          'plugin' => $broken_block->reveal(),
          'plugin_id' => 'broken',
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
    $layout = $this->mockLayoutWithData($data);

    // Don't detect any nested patterns.
    $this->patternDetector->detect(Argument::type(PatternInterface::class), Argument::type('string'))
      ->willReturn([]);

    $patterns = $this->discovery->getPatternsByLayoutComponent($layout->reveal());

    // Validate all components were discovered.
    $this->assertEqualsCanonicalizing(['uuid_1', 'uuid_2'], array_keys($patterns));

    // Validate each usage discovery.
    $this->assertEquals([
      'uuid' => 'uuid_1',
      'layout_path' => '1.uuid_1',
      'pattern_id' => 'Broken',
      'nested' => [],
    ], $patterns['uuid_1']);
    $this->assertEquals([
      'uuid' => 'uuid_2',
      'layout_path' => "1.uuid_2:$pattern_id_2",
      'pattern_id' => $pattern_id_2,
      'nested' => [],
    ], $patterns['uuid_2']);
  }

  /**
   * @covers ::getPatternsByLayoutComponentByEntity
   */
  public function testGetPatternsByLayoutComponentByEntityWithoutLayout(): void {
    // Partially mock the test subject since underlying functionality is covered
    // in other tests.
    $mock = $this->getMockBuilder(TestClass::class)
      ->onlyMethods([
        'getPatternsByLayoutComponent',
        'getSectionStorageForEntity',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    // Mock an entity to use.
    $entity = $this->prophesize(ContentEntityInterface::class)
      ->reveal();

    // Don't discover a layout for the entity.
    $mock->expects($this->once())
      ->method('getSectionStorageForEntity')
      ->with($entity)
      ->willReturn(NULL);

    $this->assertEquals([], $mock->getPatternsByLayoutComponentByEntity($entity));
  }

  /**
   * @covers ::getPatternsByLayoutComponentByEntity
   */
  public function testGetPatternsByLayoutComponentByEntityWithLayout(): void {
    // Partially mock the test subject since underlying functionality is covered
    // in other tests.
    $mock = $this->getMockBuilder(TestClass::class)
      ->onlyMethods([
        'getPatternsByLayoutComponent',
        'getSectionStorageForEntity',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    // Mock an entity to use.
    $entity = $this->prophesize(ContentEntityInterface::class)
      ->reveal();

    // Mock a layout to be discovered.
    $layout = $this->prophesize(SectionStorageInterface::class)
      ->reveal();

    // Mock return data.
    $expected_data = (array) $this->getRandomGenerator()->object();

    // Discover the layout for the entity.
    $mock->expects($this->once())
      ->method('getSectionStorageForEntity')
      ->with($entity)
      ->willReturn($layout);

    // Get patterns for the layout.
    $mock->expects($this->once())
      ->method('getPatternsByLayoutComponent')
      ->with($layout)
      ->willReturn($expected_data);

    $this->assertEquals($expected_data, $mock->getPatternsByLayoutComponentByEntity($entity));
  }

  /**
   * Prepare a mocked layout with components from provided data.
   *
   * @param array<int, array<string, array<string, mixed>>> $data
   *   Layout data to guide the creation of mocked components.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\layout_builder\SectionListInterface>
   *   A mocked layout that will return all mocked components.
   *
   * @phpstan-param array<int, array<string, LBComponentMockConfig>> $data
   */
  protected function mockLayoutWithData(array $data): ObjectProphecy {
    $layout = $this->prophesize(SectionListInterface::class);
    $components = $this->mockLayoutComponents($data);

    $this->layoutHelper->getPatternComponentsFromLayout($layout->reveal())
      ->willReturn($components);

    return $layout;
  }

}

/**
 * Test class using the trait.
 */
class TestClass {

  use PatternLayoutDiscoveryTrait {
    getPatternsByLayoutComponent as public;
    getPatternsByLayoutComponentByEntity as public;
  }

  /**
   * Constructor for the test class.
   *
   * @param \Drupal\patternkit\LayoutHelper $layout_helper
   *   The mocked layout helper service.
   * @param \Drupal\patternkit\Utility\PatternDetector $pattern_detector
   *   The mocked pattern detector service.
   */
  public function __construct(LayoutHelper $layout_helper, PatternDetector $pattern_detector) {
    $this->layoutHelper = $layout_helper;
    $this->patternDetector = $pattern_detector;
  }

}
