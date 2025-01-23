<?php

namespace Drupal\Tests\patternkit_usage_tracking\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\TestTools\Random;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\Utility\PatternDetector;
use Drupal\patternkit_usage_tracking\Entity\PatternUsage;
use Drupal\patternkit_usage_tracking\PatternUsageManager;
use Drupal\patternkit_usage_tracking\UsageRevisionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Test the UsageRevisionHelper service.
 *
 * @coversDefaultClass \Drupal\patternkit_usage_tracking\UsageRevisionHelper
 *
 * @group patternkit
 * @group patternkit_usage_tracking
 *
 * @phpstan-import-type LBComponentMockConfig from PatternkitMockHelperTrait
 * @phpstan-type MockComponentData array{
 *    component_uuid: string,
 *    pattern_id: string,
 *    layout_path: string,
 *    is_nested?: bool,
 *    content_path?: string,
 *  }
 */
class UsageRevisionHelperTest extends UnitTestCase {

  use PatternkitMockHelperTrait;
  use ProphecyTrait;

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
   * A mocked entity for testing operations.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\ContentEntityInterface>
   */
  protected ObjectProphecy $entity;

  /**
   * An instance of the UsageRevisionHelper service being tested.
   *
   * @var \Drupal\patternkit_usage_tracking\UsageRevisionHelper
   */
  protected UsageRevisionHelper $usageRevisionHelper;

  /**
   * A log of callback executions with arguments for testing callbacks.
   *
   * Since PHPUnit mocking does not support multiple predictions to the same
   * method with different arguments, and Prophecy requires a class or interface
   * with the specified methods to exist, an alternative for testing callbacks
   * passed into a function had to be established.
   *
   * @var array<string, List<List<mixed>>>
   *
   * @see self::logCallback()
   * @see self::assertCallback()
   * @see self::assertCallbackCount()
   */
  protected array $calls = [];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->usageManager = $this->prophesize(PatternUsageManager::class);
    $this->layoutHelper = $this->prophesize(LayoutHelper::class);
    $this->patternDetector = $this->prophesize(PatternDetector::class);
    $this->sectionStorageManager = $this->prophesize(SectionStorageManagerInterface::class);

    $this->entity = $this->prophesize(ContentEntityInterface::class);
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->isRevisionable()->willReturn(TRUE);
    $this->entity->getEntityType()->willReturn($entity_type->reveal());
    $this->entity->isDefaultRevision()->willReturn(TRUE);

    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $configFactory = $this->prophesize(ConfigFactoryInterface::class);

    $this->usageRevisionHelper = new UsageRevisionHelper(
      $entityTypeManager->reveal(),
      $entityFieldManager->reveal(),
      $configFactory->reveal(),
      $this->usageManager->reveal(),
      $this->layoutHelper->reveal(),
      $this->patternDetector->reveal(),
      $this->sectionStorageManager->reveal(),
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
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\patternkit_usage_tracking\UsageRevisionHelper
   *   The mocked object with injected dependencies.
   */
  protected function getPartialMock(array $methods = []): MockObject {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $configFactory = $this->prophesize(ConfigFactoryInterface::class);

    return $this->getMockBuilder(UsageRevisionHelper::class)
      ->setConstructorArgs([
        $entityTypeManager->reveal(),
        $entityFieldManager->reveal(),
        $configFactory->reveal(),
        $this->usageManager->reveal(),
        $this->layoutHelper->reveal(),
        $this->patternDetector->reveal(),
        $this->sectionStorageManager->reveal(),
      ])
      ->onlyMethods($methods)
      ->getMock();
  }

  /**
   * @covers ::shouldTrackEntity
   * @covers ::__construct
   */
  public function testShouldTrackEntity(): void {
    $mock = $this->getPartialMock(['isLayoutCompatibleEntity']);

    // Test with a matching interface, but no layout.
    $entity = $this->prophesize(ContentEntityInterface::class);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')
      ->with($entity->reveal())
      ->willReturn(FALSE);
    $this->assertFalse($mock->shouldTrackEntity($entity->reveal()),
      'Expected FALSE when tracking an entity without layout compatibility.');

    // Test with full compatibility but not revisionable.
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->isRevisionable()->willReturn(FALSE);
    $entity->getEntityType()->willReturn($entity_type->reveal());
    $mock = $this->getPartialMock(['isLayoutCompatibleEntity']);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')
      ->with($entity->reveal())
      ->willReturn(TRUE);
    $this->assertFalse($mock->shouldTrackEntity($entity->reveal()),
      'Expected FALSE when tracking an entity that is not revisionable.');

    // Test with full compatibility but not default revision.
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->isRevisionable()->willReturn(TRUE);
    $entity->isDefaultRevision()->willReturn(FALSE);
    $mock = $this->getPartialMock(['isLayoutCompatibleEntity']);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')
      ->with($entity->reveal())
      ->willReturn(TRUE);
    $this->assertFalse($mock->shouldTrackEntity($entity->reveal()),
      'Expected FALSE when tracking an entity that is not the default revision.');

    // Test with full compatibility and default revision.
    $mock = $this->getPartialMock(['isLayoutCompatibleEntity']);
    $mock->expects($this->once())
      ->method('isLayoutCompatibleEntity')
      ->with($this->entity->reveal())
      ->willReturn(TRUE);
    $this->assertTrue($mock->shouldTrackEntity($this->entity->reveal()),
      'Expected TRUE when tracking an entity with full compatibility.');
  }

  /**
   * @covers ::loadPreviouslyTrackedPatterns
   * @covers ::__construct
   *
   * @dataProvider providerLoadPreviouslyTrackedPatterns
   */
  public function testLoadPreviouslyTrackedPatterns(array $loaded_records, array $expected): void {
    // Load record data into mocked record entities.
    [
      'all' => $all_records,
      'top' => $top_records,
      'nested' => $nested_records,
    ] = $this->mockLoadedRecords($loaded_records);
    $this->usageManager->loadByEntity($this->entity->reveal())
      ->willReturn(array_map(fn ($record) => $record->reveal(), $all_records));

    $actual = $this->usageRevisionHelper
      ->loadPreviouslyTrackedPatterns($this->entity->reveal());

    // The expected returned structure includes the record objects themselves,
    // but these aren't available in the data provider. This requires them to be
    // assigned into the expected array structure before comparison to validate
    // proper assembly of the data.
    foreach ($expected as &$expected_element) {
      $identifier = $expected_element['layout_path'];

      // Assign the mocked record for each top-level pattern.
      $expected_element['record'] = $top_records[$identifier]->reveal();

      // Assign the mocked record for each nested pattern.
      foreach ($expected_element['nested'] as $index => $pattern_id) {
        // Unset the original placeholder.
        unset($expected_element['nested'][$index]);

        $nested_id = "$identifier=>$pattern_id";
        $expected_element['nested'][$pattern_id] = $nested_records[$nested_id]->reveal();
      }
    }

    // NB. We have to use assertSame() here to validate the correct record
    // prophecies are assigned. Using assertEquals() instead would pass as long
    // as the prophecy is for the same class regardless whether it's the same
    // instance or not.
    $this->assertSame($expected, $actual);
  }

  /**
   * Data provider for testLoadPreviouslyTrackedPatterns().
   */
  public static function providerLoadPreviouslyTrackedPatterns(): array {
    $cases = [];

    $uuids = [];
    $patterns = [];
    $cases['no nested patterns'] = [
      // Existing records.
      [
        [
          'component_uuid' => $uuids[0] = 'uuid_' . rand(),
          'pattern_id' => $patterns[0] = '@pattern_' . rand(),
          'layout_path' => "1.$uuids[0]:$patterns[0]",
          'content_path' => "$patterns[0]",
          'top_pattern' => "$patterns[0]",
        ],
        [
          'component_uuid' => $uuids[1] = 'uuid_' . Random::string(),
          'pattern_id' => $patterns[1] = '@pattern_' . Random::string(),
          'layout_path' => "1.$uuids[1]:$patterns[1]",
          'content_path' => "$patterns[1]",
          'top_pattern' => "$patterns[1]",
        ],
      ],
      // Expected results.
      [
        $uuids[0] => [
          'uuid' => $uuids[0],
          'layout_path' => "1.$uuids[0]:$patterns[0]",
          'pattern_id' => $patterns[0],
          'nested' => [],
          'record' => '',
        ],
        $uuids[1] => [
          'uuid' => $uuids[1],
          'layout_path' => "1.$uuids[1]:$patterns[1]",
          'pattern_id' => $patterns[1],
          'nested' => [],
          'record' => '',
        ],
      ],
    ];

    $uuids = [];
    $patterns = [];
    $cases['nested patterns'] = [
      // Existing records.
      [
        // Define 1 top-level component that will contain 1 nested usage.
        [
          'component_uuid' => $uuids[0] = 'uuid_' . rand(),
          'pattern_id' => $patterns[0] = '@pattern_' . Random::string(),
          'layout_path' => "1.$uuids[0]:$patterns[0]",
          'content_path' => "$patterns[0]",
          'top_pattern' => "$patterns[0]",
        ],
        // Nest one usage within the first top-level component.
        [
          'component_uuid' => $uuids[0],
          'pattern_id' => $patterns[1] = '@pattern_' . Random::string(),
          'layout_path' => "1.$uuids[0]:$patterns[0]=>$patterns[1]",
          'is_nested' => TRUE,
          'content_path' => "$patterns[0]=>$patterns[1]",
          'top_pattern' => "$patterns[0]",
          'nested_pattern' => "$patterns[1]",
        ],

        // Define a second component that will contain multiple nested usages.
        [
          'component_uuid' => $uuids[1] = 'uuid_' . rand(),
          'pattern_id' => $patterns[2] = '@pattern_' . Random::string(),
          'layout_path' => "1.$uuids[1]:$patterns[2]",
          'content_path' => "$patterns[2]",
          'top_pattern' => "$patterns[2]",
        ],
        // Define multiple nested usages within the second component.
        [
          'component_uuid' => $uuids[1],
          'pattern_id' => $patterns[3] = '@pattern_' . Random::string(),
          'layout_path' => "1.$uuids[1]:$patterns[2]=>$patterns[3]",
          'is_nested' => TRUE,
          'content_path' => "$patterns[2]=>$patterns[3]",
          'top_pattern' => $patterns[2],
          'nested_pattern' => $patterns[3],
        ],
        [
          'component_uuid' => $uuids[1],
          'pattern_id' => $patterns[4] = '@pattern_' . Random::string(),
          'layout_path' => "1.$uuids[1]:$patterns[2]=>$patterns[4]",
          'is_nested' => TRUE,
          'content_path' => "$patterns[2]=>$patterns[4]",
          'top_pattern' => $patterns[2],
          'nested_pattern' => $patterns[4],
        ],
      ],
      // Expected results.
      [
        $uuids[0] => [
          'uuid' => $uuids[0],
          'layout_path' => "1.{$uuids[0]}:$patterns[0]",
          'pattern_id' => $patterns[0],
          'nested' => [
            $patterns[1],
          ],
          'record' => '',
        ],
        $uuids[1] => [
          'uuid' => $uuids[1],
          'layout_path' => "1.{$uuids[1]}:$patterns[2]",
          'pattern_id' => $patterns[2],
          'nested' => [
            $patterns[3],
            $patterns[4],
          ],
          'record' => '',
        ],
      ],
    ];

    return $cases;
  }

  /**
   * @covers \Drupal\patternkit_usage_tracking\UsageRevisionHelper
   * @covers \Drupal\patternkit_usage_tracking\PatternLayoutDiscoveryTrait
   * @covers \Drupal\patternkit_usage_tracking\PatternLayoutUsageInsertTrait
   *
   * @dataProvider providerTrackOnEntityUpdate
   */
  public function testTrackOnEntityUpdate(array $edited_data, array $loaded_records, array $expected_calls, array $nested_content_mapping = []): void {
    $new_revision_id = rand(0, 100);
    $this->entity->getRevisionId()->willReturn($new_revision_id);

    // Prepare for loading translations.
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn($langcode = 'en');
    $this->entity->language()->willReturn($language->reveal());
    $this->entity->isTranslatable()->willReturn(TRUE);
    $this->entity->getTranslationLanguages()->willReturn(['en' => $language->reveal()]);

    // Load mocked update data.
    $layout = $this->mockLayoutWithData($edited_data);

    // Load mocked record data.
    ['all' => $all_records] = $this->mockLoadedRecords($loaded_records);
    $this->usageManager->loadByEntity($this->entity->reveal())
      ->shouldBeCalledOnce()
      ->willReturn(array_map(fn ($record) => $record->reveal(), $all_records));

    // Register all content scanning results with the pattern detector.
    foreach ($nested_content_mapping as $discovery) {
      [
        'content' => $content,
        'result' => $result,
      ] = $discovery;

      $this->patternDetector->detect(Argument::type(PatternInterface::class), $content)
        ->shouldBeCalledOnce()
        ->willReturn($result);
    }

    // Provide a catch-all for any unspecified detections to return no
    // results instead of failing with an invalid return type.
    $this->patternDetector->detect(Argument::cetera())->willReturn([]);

    foreach ($expected_calls['register'] ?? [] as [$pattern_id, $path]) {
      $this->usageManager->register($this->entity->reveal(), $pattern_id, $path)
        ->shouldBeCalledOnce();
    }

    foreach ($expected_calls['update'] ?? [] as $identifier => $operations) {
      /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit_usage_tracking\Entity\PatternUsage $record */
      $record = $all_records[$identifier];

      // Dynamically set the method expectation.
      if (!empty($operations)) {
        $method = array_shift($operations);
        call_user_func_array([$record, $method], $operations)
          ->shouldBeCalledOnce();
      }

      // Expect the revision ID to get updated.
      $record->set('source_revision_id', $new_revision_id)
        ->shouldBeCalledOnce();

      // Expect the element to be saved.
      $record->save()->shouldBeCalledOnce();
    }

    foreach ($expected_calls['delete'] ?? [] as $locator) {
      // Handle deletion of top-level components and all nested content.
      if (empty($locator['path'])) {
        $this->usageManager->deleteByEntityAndUuid($this->entity->reveal(), $locator['uuid'])
          ->shouldBeCalledOnce();
      }
      else {
        /** @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit_usage_tracking\Entity\PatternUsage> $record */
        $record = $all_records[$locator['path']];

        // Expect the individual record element to be deleted.
        $record->delete()->shouldBeCalledOnce();
      }
    }

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

    $mock->trackOnEntityUpdate($this->entity->reveal());
  }

  /**
   * Data provider for testTrackOnEntityUpdate().
   *
   * @phpstan-return array<array-key, array{
   *   array<int, array<string, LBComponentMockConfig>>,
   *   List<MockComponentData>,
   *   array{
   *     register?: List<array{string, string}>,
   *     update?: array<string, array{string, string}|array<empty>>,
   *     delete?: List<array{uuid: string}|array{path: string}>,
   *   },
   *   List<array{
   *     content: string,
   *     result: List<string>
   *   }>,
   * }>
   */
  public static function providerTrackOnEntityUpdate(): array {
    $cases = [];

    $uuids = $patterns = [];
    $cases['add top-level component'] = [
      // Edited layout data.
      [
        1 => [
          ($uuids[0] = 'uuid_1') => [
            'plugin' => [
              'block' => [
                'data' => [],
              ],
              'pattern' => [
                'asset_id' => $patterns[0] = '@patternkit/atoms/example/src/example',
                'dependencies' => [],
              ],
            ],
          ],
          ($uuids[1] = 'uuid_2') => [
            'plugin' => [
              'block' => [
                'data' => [],
              ],
              'pattern' => [
                'asset_id' => $patterns[1] = '@patternkit/atoms/example_ref/src/example_ref',
                'dependencies' => [],
              ],
            ],
          ],
        ],
      ],
      // Loaded usage record data.
      [
        // The first component already exists.
        [
          'component_uuid' => $uuids[0],
          'pattern_id' => $patterns[0],
          'layout_path' => "1.$uuids[0]:$patterns[0]",
        ],
      ],
      // Expected usage record calls.
      [
        'register' => [
          // Method arguments.
          [$patterns[1], "1.$uuids[1]:$patterns[1]"],
        ],
        'update' => [
          // The existing record will need to be updated.
          "1.$uuids[0]:$patterns[0]" => [],
        ],
      ],
    ];

    $uuids = $patterns = [];
    $cases['update top-level component'] = [
      // Edited layout data.
      [
        1 => [
          ($uuids[0] = 'uuid_1') => [
            'plugin' => [
              'block' => [
                'data' => [],
              ],
              'pattern' => [
                'asset_id' => $patterns[0] = '@patternkit/atoms/example/src/example',
                'dependencies' => [],
              ],
            ],
          ],
        ],
        // Move component to a new section.
        2 => [
          ($uuids[1] = 'uuid_2') => [
            'plugin' => [
              'block' => [
                'data' => $nested_content = [
                  'reference' => Random::string(),
                ],
              ],
              'pattern' => [
                'asset_id' => $patterns[1] = '@patternkit/atoms/example_ref/src/example_ref',
                'dependencies' => [
                  $patterns['nested'] = '@patternkit/atoms/example/src/example',
                ],
              ],
            ],
          ],
        ],
      ],
      // Loaded usage record data.
      [
        // Load records placing both components in the first section.
        [
          'component_uuid' => $uuids[0],
          'pattern_id' => $patterns[0],
          'layout_path' => "1.$uuids[0]:$patterns[0]",
        ],
        [
          'component_uuid' => $uuids[1],
          'pattern_id' => $patterns[1],
          'layout_path' => "1.$uuids[1]:$patterns[1]",
        ],
        // Load a usage record for the nested pattern to be updated as well.
        [
          'component_uuid' => $uuids[1],
          'pattern_id' => $patterns['nested'],
          'is_nested' => TRUE,
          'content_path' => $nested_path = "$patterns[1]=>{$patterns['nested']}",
          'layout_path' => "1.$uuids[1]:$nested_path",
        ],
      ],
      // Expected usage record calls.
      [
        'update' => [
          // Expect only revision updates for the unchanged component.
          "1.$uuids[0]:$patterns[0]" => [],
          // Expect the path for the second component to get updated to
          // reference section 2.
          "1.$uuids[1]:$patterns[1]" => ['setPath', "2.$uuids[1]:$patterns[1]"],
          // Expect the path for the nested pattern in the second component to
          // get updated to reflect the new layout path.
          "1.$uuids[1]:$nested_path" => ['setPath', "2.$uuids[1]:$nested_path"],
        ],
      ],
      // Nested content data mapping.
      [
        // Expect a lookup for the nested content and return a match for the new
        // nested pattern.
        [
          'content' => json_encode($nested_content),
          'result' => [
            $patterns['nested'],
          ],
        ],
      ],
    ];

    $uuids = $patterns = [];
    $cases['add nested pattern'] = [
      // Edited layout data.
      [
        1 => [
          ($uuids[0] = 'uuid_1') => [
            'plugin' => [
              'block' => [
                'data' => [],
              ],
              'pattern' => [
                'asset_id' => $patterns[0] = '@patternkit/atoms/example/src/example',
                'dependencies' => [],
              ],
            ],
          ],
          ($uuids[1] = 'uuid_2') => [
            'plugin' => [
              'block' => [
                'data' => $nested_content = [
                  'reference' => Random::string(),
                ],
              ],
              'pattern' => [
                'asset_id' => $patterns[1] = '@patternkit/atoms/example_ref/src/example_ref',
                'dependencies' => [
                  $patterns['nested'] = '@patternkit/atoms/example/src/example',
                ],
              ],
            ],
          ],
        ],
      ],
      // Loaded usage record data.
      [
        // Load usage records for only the two top-level components.
        [
          'component_uuid' => $uuids[0],
          'pattern_id' => $patterns[0],
          'layout_path' => "1.$uuids[0]:$patterns[0]",
        ],
        [
          'component_uuid' => $uuids[1],
          'pattern_id' => $patterns[1],
          'layout_path' => "1.$uuids[1]:$patterns[1]",
        ],
      ],
      // Expected usage record calls.
      [
        // Expect a record for the new nested pattern to be registered.
        'register' => [
          [
            $patterns['nested'],
            "1.$uuids[1]:" . ($nested_path = "$patterns[1]=>{$patterns['nested']}"),
          ],
        ],
        'update' => [
          // Expect only revision updates for the unchanged components.
          "1.$uuids[0]:$patterns[0]" => [],
          "1.$uuids[1]:$patterns[1]" => [],
        ],
      ],
      // Nested content data mapping.
      [
        // Expect a lookup for the nested content and return a match for the new
        // nested pattern.
        [
          'content' => json_encode($nested_content),
          'result' => [
            $patterns['nested'],
          ],
        ],
      ],
    ];

    $uuids = $patterns = [];
    $cases['no updates with nested pattern'] = [
      // Edited layout data.
      [
        1 => [
          ($uuids[0] = 'uuid_1') => [
            'plugin' => [
              'block' => [
                'data' => [],
              ],
              'pattern' => [
                'asset_id' => $patterns[0] = '@patternkit/atoms/example/src/example',
                'dependencies' => [],
              ],
            ],
          ],
          ($uuids[1] = 'uuid_2') => [
            'plugin' => [
              'block' => [
                'data' => $nested_content = [
                  'reference' => Random::string(),
                ],
              ],
              'pattern' => [
                'asset_id' => $patterns[1] = '@patternkit/atoms/example_ref/src/example_ref',
                'dependencies' => [
                  $patterns['nested'] = '@patternkit/atoms/example/src/example',
                ],
              ],
            ],
          ],
        ],
      ],
      // Loaded usage record data.
      [
        // Load usage records for the two top-level components.
        [
          'component_uuid' => $uuids[0],
          'pattern_id' => $patterns[0],
          'layout_path' => "1.$uuids[0]:$patterns[0]",
        ],
        [
          'component_uuid' => $uuids[1],
          'pattern_id' => $patterns[1],
          'layout_path' => "1.$uuids[1]:$patterns[1]",
        ],
        // Load a usage record for the nested pattern to be updated as well.
        [
          'component_uuid' => $uuids[1],
          'pattern_id' => $patterns['nested'],
          'is_nested' => TRUE,
          'content_path' => $nested_path = "$patterns[1]=>{$patterns['nested']}",
          'layout_path' => "1.$uuids[1]:$nested_path",
        ],
      ],
      [
        'update' => [
          // Expect only revision updates for unchanged records.
          "1.$uuids[0]:$patterns[0]" => [],
          "1.$uuids[1]:$patterns[1]" => [],
          // Nested paths will always be updated to ensure accuracy.
          "1.$uuids[1]:$nested_path" => ['setPath', "1.$uuids[1]:$nested_path"],
        ],
      ],
      // Nested content data mapping.
      [
        // Expect a lookup for the nested content and return a match for the
        // nested pattern.
        [
          'content' => json_encode($nested_content),
          'result' => [
            $patterns['nested'],
          ],
        ],
      ],
    ];

    $uuids = $patterns = [];
    $cases['add new component with nested pattern'] = [
      // Edited layout data.
      [
        1 => [
          // Specify an existing component to persist.
          ($uuids[0] = 'uuid_1') => [
            'plugin' => [
              'block' => [
                'data' => [],
              ],
              'pattern' => [
                'asset_id' => $patterns[0] = '@patternkit/atoms/example/src/example',
                'dependencies' => [],
              ],
            ],
          ],
          // Specify a new component with a nested pattern for discovery.
          ($uuids[1] = 'uuid_2') => [
            'plugin' => [
              'block' => [
                'data' => $nested_content = [
                  'reference' => Random::string(),
                ],
              ],
              'pattern' => [
                'asset_id' => $patterns[1] = '@patternkit/atoms/example_ref/src/example_ref',
                'dependencies' => [
                  $patterns['nested'] = '@patternkit/atoms/example/src/example',
                ],
              ],
            ],
          ],
        ],
      ],
      // Loaded usage record data.
      [
        // Load a record for the existing first component.
        [
          'component_uuid' => $uuids[0],
          'pattern_id' => $patterns[0],
          'layout_path' => "1.$uuids[0]:$patterns[0]",
        ],
      ],
      // Expected usage record calls.
      [
        // Expect registration for the new top-level and nested patterns.
        'register' => [
          // Register the new top-level pattern.
          [$patterns[1], "1.$uuids[1]:$patterns[1]"],
          // Register the new nested pattern.
          [
            $patterns['nested'],
            "1.$uuids[1]:" . ($nested_path = "$patterns[1]=>{$patterns['nested']}"),
          ],
        ],
        'update' => [
          // The existing record will need to be updated.
          "1.$uuids[0]:$patterns[0]" => [],
        ],
      ],
      // Nested content data mapping.
      [
        // Expect a lookup for the nested content and return a match for the new
        // nested pattern.
        [
          'content' => json_encode($nested_content),
          'result' => [
            $patterns['nested'],
          ],
        ],
      ],
    ];

    $uuids = $patterns = [];
    $uuids[0] = 'uuid_1';
    $patterns[0] = '@patternkit/atoms/example/src/example';
    $cases['multiple changes'] = [
      // Edited layout data.
      [
        // Delete component from the first section.
        1 => [],
        // Move component to a new section.
        2 => [
          ($uuids[1] = 'uuid_2') => [
            'plugin' => [
              'block' => [
                // Add a nested pattern.
                'data' => $nested_content = [
                  'reference' => Random::string(),
                ],
              ],
              'pattern' => [
                'asset_id' => $patterns[1] = '@patternkit/atoms/example_ref/src/example_ref',
                'dependencies' => [
                  $patterns['nested'] = '@patternkit/atoms/example/src/example',
                ],
              ],
            ],
          ],
        ],
      ],
      // Loaded usage record data.
      [
        // Specify the record for the now removed first component.
        [
          'component_uuid' => $uuids[0],
          'pattern_id' => $patterns[0],
          'layout_path' => "1.$uuids[0]:$patterns[0]",
        ],
        // Specify a nested pattern within the first component to be deleted.
        [
          'component_uuid' => $uuids[0],
          'pattern_id' => $patterns[0],
          'is_nested' => TRUE,
          'content_path' => "$patterns[0]=>$patterns[0]",
          'layout_path' => "1.$uuids[0]:$patterns[0]=>$patterns[0]",
        ],
        // Specify an existing top-level component to be moved to a new section.
        [
          'component_uuid' => $uuids[1],
          'pattern_id' => $patterns[1],
          'layout_path' => "1.$uuids[1]:$patterns[1]",
        ],
        // Specify a nested pattern in the updated component to be deleted.
        [
          'component_uuid' => $uuids[1],
          'pattern_id' => $patterns[1],
          'is_nested' => TRUE,
          'content_path' => $deleted_content_path = "$patterns[1]=>$patterns[1]",
          'layout_path' => $deleted_layout_path = "1.$uuids[1]:$deleted_content_path",
        ],
      ],
      // Expected usage record calls.
      [
        'register' => [
          // Register a new nested pattern within the updated component.
          [
            $patterns['nested'],
            "2.$uuids[1]:" . "$patterns[1]=>{$patterns['nested']}",
          ],
        ],
        'update' => [
          // Update the path on the existing component.
          "1.$uuids[1]:$patterns[1]" => ['setPath', "2.$uuids[1]:$patterns[1]"],
        ],
        'delete' => [
          // Delete the first top-level component and its nested pattern record.
          // The nested pattern would be handled by a call to delete all entries
          // for the entity and top-level UUID.
          [
            'uuid' => $uuids[0],
          ],
          // Delete the pre-existing nested pattern in the second component.
          [
            'path' => $deleted_layout_path,
          ],
        ],
      ],
      // Nested content data mapping.
      [
        [
          'content' => json_encode($nested_content),
          'result' => [
            $patterns['nested'],
          ],
        ],
      ],
    ];

    return $cases;
  }

  /**
   * @covers ::trackChanges
   * @covers ::__construct
   */
  public function testTrackChanges(): void {
    $random = $this->getRandomGenerator();
    $original = [
      'a' => $random->string(),
      'b' => $random->string(),
      'c' => 'unchanged value',
      'd' => 'old value',
    ];

    $new = [
      'c' => 'unchanged value',
      'd' => 'new value',
      'e' => $random->string(),
      'f' => $random->string(),
    ];

    UsageRevisionHelper::trackChanges(
      $original,
      $new,
      [$this, 'logInsert'],
      [$this, 'logDelete'],
      [$this, 'logUpdate'],
    );

    // Confirm logged expectations.
    $this->assertCallbackCount('insert', 2);
    $this->assertCallback('insert', ['e', $new['e']]);
    $this->assertCallback('insert', ['f', $new['f']]);
    $this->assertCallbackCount('delete', 2);
    $this->assertCallback('delete', ['a', $original['a']]);
    $this->assertCallback('delete', ['b', $original['b']]);
    $this->assertCallbackCount('update', 2);
    $this->assertCallback('update', ['c', $original['c'], $new['c']]);
    $this->assertCallback('update', ['d', $original['d'], $new['d']]);
  }

  /**
   * Assert an operation callback was logged with the given arguments.
   *
   * @param string $operation
   *   The operation type to be tested.
   * @param List<mixed> $arguments
   *   The expected arguments for the callback.
   *
   * @see self::$calls
   */
  protected function assertCallback(string $operation, array $arguments): void {
    $this->assertContainsEquals($arguments, $this->calls[$operation]);
  }

  /**
   * Assert an operation type was called a specified number of times.
   *
   * @param string $operation
   *   The operation type to be tested.
   * @param int $count
   *   The expected number of calls for the operation.
   *
   * @see self::$calls
   */
  protected function assertCallbackCount(string $operation, int $count): void {
    $this->assertCount($count, $this->calls[$operation]);
  }

  /**
   * Log a callback for a given operation.
   *
   * @param string $operation
   *   The operation type to be logged.
   * @param List<mixed> $arguments
   *   An array of arguments provided to the callback to be logged.
   *
   * @see self::$calls
   */
  protected function logCallback(string $operation, array $arguments): void {
    $this->calls[$operation][] = $arguments;
  }

  /**
   * Log an insert operation callback.
   *
   * @param mixed ...$arguments
   *   All arguments provided to the callback.
   *
   * @see self::$calls
   */
  public function logInsert(...$arguments): void {
    $this->logCallback('insert', $arguments);
  }

  /**
   * Log a delete operation callback.
   *
   * @param mixed ...$arguments
   *   All arguments provided to the callback.
   *
   * @see self::$calls
   */
  public function logDelete(...$arguments): void {
    $this->logCallback('delete', $arguments);
  }

  /**
   * Log an update operation callback.
   *
   * @param mixed ...$arguments
   *   All arguments provided to the callback.
   *
   * @see self::$calls
   */
  public function logUpdate(...$arguments): void {
    $this->logCallback('update', $arguments);
  }

  /**
   * Load static data into mocked pattern usage records.
   *
   * @param List<array{component_uuid: string, pattern_id: string, layout_path: string, is_nested?: bool, content_path?: string}> $loaded_records
   *   An array of static record data to configure into loaded prophecy objects
   *   for individual pattern usage records. Each entry should be an associative
   *   array specifying the following keys and values:
   *   - component_uuid: This is a string value to use as the UUID of the
   *     top-level component.
   *   - pattern_id: A string to specify the asset identifier of the pattern
   *     referenced within this usage record.
   *   - layout_path: A string value specifying the path within the layout
   *     structure to the component containing this referenced pattern. This
   *     string should be in the format of `<section_delta>.<component_uuid>`.
   *   - is_nested: (Optional) A boolean value indicating whether this record
   *     references a pattern nested within the content of another top-level
   *     component. If not provided, the value is assumed to be FALSE.
   *   - content_path: (Optional) A string value referencing the path within a
   *     component's content where a pattern usage was found. This is required
   *     if 'is_nested' is set to TRUE.
   *
   * @return array{all: array<string, \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit_usage_tracking\Entity\PatternUsage>>, top: array<string, \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit_usage_tracking\Entity\PatternUsage>>, nested: array<string, \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit_usage_tracking\Entity\PatternUsage>>}
   *   An associative array containing all record prophecies keyed into separate
   *   lists. <strong>All record prophecies are un-revealed</strong> to allow
   *   for further expectations to be specified. <strong>Each record will need
   *   to be revealed prior to submission into a method execution.</strong> The
   *   top-level array contains the following keys and related lists:
   *   - all: An associative array of all record prophecies keyed by the
   *     top-level component UUID for top-level components, and a concatenation
   *     of the top-level component's UUID and the record's content path in the
   *     form `<component_uuid>.<content_path>` for nested records.
   *   - top: An associative array of all top-level component records keyed by
   *     the component UUID of each.
   *   - nested: An associative array of all nested component records keyed by
   *     a concatenation of the top-level component's UUID and the record's
   *     content path in the form `<component_uuid>.<content_path>`
   *
   * @phpstan-param List<MockComponentData> $loaded_records
   */
  protected function mockLoadedRecords(array $loaded_records): array {
    $all_records = $top_records = $nested_records = [];
    foreach ($loaded_records as $record) {
      $uuid = $record['component_uuid'];
      $is_nested = $record['is_nested'] ?? FALSE;
      $identifier = $record['layout_path'];

      $mocked_record = $this->prophesize(PatternUsage::class);
      $mocked_record->get('component_uuid')
        ->willReturn((object) ['value' => $uuid]);
      $mocked_record->get('pattern_name')
        ->willReturn((object) ['value' => $record['pattern_id']]);
      $mocked_record->isNested()->willReturn($is_nested);
      $mocked_record->getContentPath()
        ->willReturn($record['content_path'] ?? NULL);
      $mocked_record->getLayoutPath()->willReturn($record['layout_path']);
      $mocked_record->createDuplicate()->willReturn($mocked_record->reveal());

      // Sort by nested and top-level records for lookup later in assignment to
      // expected values.
      if ($is_nested) {
        $nested_records[$identifier] = $mocked_record;
      }
      else {
        $top_records[$identifier] = $mocked_record;
      }

      $all_records[$identifier] = $mocked_record;
    }
    return [
      'all' => $all_records,
      'top' => $top_records,
      'nested' => $nested_records,
    ];
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
