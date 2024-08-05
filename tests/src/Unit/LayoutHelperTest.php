<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\patternkit\LayoutHelper;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\patternkit\UpdateHelper;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Unit tests for the LayoutHelper service.
 *
 * @coversDefaultClass \Drupal\patternkit\LayoutHelper
 * @group patternkit
 */
class LayoutHelperTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The entity type manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityTypeManagerInterface>
   */
  protected ObjectProphecy $entityTypeManager;

  /**
   * Pattern storage manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  protected ObjectProphecy $patternStorage;

  /**
   * The storage manager for entity view displays.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  protected ObjectProphecy $displayStorage;

  /**
   * The expirable key value factory service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface>
   */
  protected ObjectProphecy $keyValueExpirable;

  /**
   * The patternkit logger channel.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Logger\LoggerChannelInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The patternkit pattern repository service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternRepository>
   */
  protected ObjectProphecy $patternRepository;


  /**
   * The layout tempstore repository service, if available.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\layout_builder\LayoutTempstoreRepositoryInterface>
   */
  protected ObjectProphecy $layoutTempstoreRepository;

  /**
   * The section storage manager service, if available.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface>
   */
  protected ObjectProphecy $sectionStorageManager;

  /**
   * The Patternkit layout helper service, if Layout Builder is available.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\LayoutHelper>
   */
  protected ObjectProphecy $layoutHelper;

  /**
   * The update helper service being tested.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\UpdateHelper>
   */
  protected ObjectProphecy $updateHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->patternStorage = $this->prophesize(EntityStorageInterface::class);
    $this->displayStorage = $this->prophesize(EntityStorageInterface::class);
    $this->keyValueExpirable = $this->prophesize(KeyValueExpirableFactoryInterface::class);
    $this->patternRepository = $this->prophesize(PatternRepository::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $this->layoutTempstoreRepository = $this->prophesize(LayoutTempstoreRepositoryInterface::class);
    $this->sectionStorageManager = $this->prophesize(SectionStorageManagerInterface::class);
    $this->updateHelper = $this->prophesize(UpdateHelper::class);
  }

  /**
   * Get the LayoutHelper instance for testing with all mocks injected.
   *
   * @return \Drupal\patternkit\LayoutHelper
   *   The instantiated layout helper service for testing.
   */
  protected function getLayoutHelper(): LayoutHelper {
    $this->entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());
    $this->entityTypeManager->getStorage('entity_view_display')
      ->willReturn($this->displayStorage->reveal());

    $layoutHelper = new LayoutHelper(
      $this->entityTypeManager->reveal(),
      $this->keyValueExpirable->reveal(),
      $this->patternRepository->reveal(),
      $this->logger->reveal(),
      $this->layoutTempstoreRepository->reveal(),
      $this->sectionStorageManager->reveal(),
    );

    $layoutHelper->setUpdateHelper($this->updateHelper->reveal());

    return $layoutHelper;
  }

  /**
   * @covers ::getPatternComponentsFromLayout
   * @covers ::setUpdateHelper
   * @covers ::updateHelper
   * @covers ::__construct
   */
  public function testGetPatternComponentsFromLayout(): void {
    // Mock a section/component hierarchy, but instead of components we'll just
    // include a boolean value to indicate if it should be considered a
    // patternkit component by our updateHelper mock.
    $component_hierarchy = [
      0 => [
        '1234' => TRUE,
        '5678' => FALSE,
      ],
      1 => [
        '9012' => TRUE,
        '3456' => TRUE,
        '7890' => FALSE,
      ],
    ];

    // Mock section classes for traversal.
    $sectionStorage = $this->prepareSectionLayout($component_hierarchy);

    // Mock the component test to test filtering.
    $this->updateHelper->isPatternkitComponent(Argument::type('bool'))
      ->willReturnArgument(0);

    $layoutHelper = $this->getLayoutHelper();

    $this->assertEquals([
      '0.1234' => TRUE,
      '1.9012' => TRUE,
      '1.3456' => TRUE,
    ], $layoutHelper->getPatternComponentsFromLayout($sectionStorage->reveal()));
  }

  /**
   * @covers ::updateLayoutBuilderComponents
   * @covers ::applyToSectionComponents
   * @covers ::getBufferedLogger
   * @covers ::getUpdateCallback
   * @covers ::__construct
   *
   * @todo Add test coverage for filtering.
   */
  public function testUpdateLayoutBuilderComponents(): void {
    $component = $this->prophesize(SectionComponent::class);
    $seed_data = [
      'entity_sections' => [
        'defaults:node.page.default' => [
          0 => [
            '1234' => $component->reveal(),
            '5678' => $component->reveal(),
          ],
        ],
        'defaults:node.article.default' => [
          0 => [
            '9012' => $component->reveal(),
          ],
          1 => [
            '3456' => $component->reveal(),
          ],
        ],
      ],
      'tempstore_sections' => [
        'overrides:node.1.default.en' => [
          0 => [
            '1234' => $component->reveal(),
            '5678' => $component->reveal(),
          ],
          1 => [
            '1234' => $component->reveal(),
            '5678' => $component->reveal(),
          ],
        ],
        'overrides:node.2.default.en' => [
          0 => [
            '1234' => $component->reveal(),
            '5678' => $component->reveal(),
          ],
          1 => [
            '1234' => $component->reveal(),
            '5678' => $component->reveal(),
          ],
          2 => [
            '1234' => $component->reveal(),
            '5678' => $component->reveal(),
          ],
        ],
      ],
    ];
    $expected = [
      'blocks' => 14,
      'entities' => 4,
    ];

    // Prepare mocked entity sections.
    $entity_sections = [];
    foreach ($seed_data['entity_sections'] as $id => $section_config) {
      $layout = $this->prepareSectionLayout($section_config);
      $layout->save()->willReturn(1);
      $entity_sections[$id] = $layout->reveal();
    }

    // Prepare mocked tempstore sections.
    $tempstore_sections = [];
    foreach ($seed_data['tempstore_sections'] as $id => $section_config) {
      $layout = $this->prepareSectionLayout($section_config);
      $layout->save()->willReturn(1);
      $tempstore_sections[$id] = $layout->reveal();
    }

    $this->updateHelper->updateBlockComponentPattern(Argument::type(SectionComponent::class))
      ->willReturn(['updated' => TRUE]);
    $this->updateHelper->filterComponent(Argument::type(SectionComponent::class), [])
      ->willReturn($this->prophesize(PatternkitBlock::class)->reveal());

    $this->entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal());
    $this->entityTypeManager->getStorage('entity_view_display')
      ->willReturn($this->displayStorage->reveal());

    // Partially mock the layout helper to shortcut mocking of data.
    $layoutHelper = $this->getMockBuilder(LayoutHelper::class)
      ->onlyMethods([
        'getSectionStorageFromEntities',
        'getSectionStorageFromTempStore',
        'updateHelper',
        // 'applyToSectionComponents',
      ])
      ->setConstructorArgs([
        $this->entityTypeManager->reveal(),
        $this->keyValueExpirable->reveal(),
        $this->patternRepository->reveal(),
        $this->logger->reveal(),
        $this->layoutTempstoreRepository->reveal(),
        $this->sectionStorageManager->reveal(),
      ])
      ->getMock();

    $layoutHelper->expects($this->once())
      ->method('getSectionStorageFromEntities')
      ->willReturn($entity_sections);
    $layoutHelper->expects($this->once())
      ->method('getSectionStorageFromTempStore')
      ->willReturn($tempstore_sections);
    $layoutHelper->method('updateHelper')
      ->willReturn($this->updateHelper->reveal());

    [$block_count, $entity_count] = $layoutHelper->updateLayoutBuilderComponents();
    $this->assertEquals($expected['blocks'], $block_count);
    $this->assertEquals($expected['entities'], $entity_count);
  }

  /**
   * @covers ::applyToSectionComponents
   * @covers ::setUpdateHelper
   * @covers ::__construct
   */
  public function testApplyToSectionComponents(): void {
    $generator = $this->getRandomGenerator();
    $component_hierarchy = [
      0 => [
        '1234' => $generator->object(),
        '5678' => $generator->object(),
      ],
      1 => [
        '9012' => $generator->object(),
        '3456' => $generator->object(),
        '7890' => $generator->object(),
      ],
    ];

    // Mock section classes for traversal.
    $sectionStorage = $this->prepareSectionLayout($component_hierarchy);
    $sectionStorage->save()->shouldBeCalledOnce();

    $section_storage_collection = [
      'test_storage:1' => $sectionStorage->reveal(),
    ];

    // Create a second storage that won't be updated and should be excluded
    // from final results.
    $component_hierarchy_2 = [
      0 => [
        '9876' => $generator->object(),
        '5432' => $generator->object(),
      ],
    ];
    $sectionStorage = $this->prepareSectionLayout($component_hierarchy_2);
    $sectionStorage->save()->shouldNotBeCalled();
    $section_storage_collection['test_storage:2'] = $sectionStorage->reveal();

    // Create an additional argument that should get passed into each callback.
    $additional_arg = $generator->string();

    // Prepare an observable callback function to confirm execution.
    $callback_mock = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['myCallback'])
      ->getMock();
    $callback_mock->expects($this->exactly(7))
      ->method('myCallback')
      ->withConsecutive(
        [
          'test_storage:1',
          $section_storage_collection['test_storage:1'],
          $component_hierarchy[0]['1234'],
          $additional_arg,
        ],
        [
          'test_storage:1',
          $section_storage_collection['test_storage:1'],
          $component_hierarchy[0]['5678'],
          $additional_arg,
        ],
        [
          'test_storage:1',
          $section_storage_collection['test_storage:1'],
          $component_hierarchy[1]['9012'],
          $additional_arg,
        ],
        [
          'test_storage:1',
          $section_storage_collection['test_storage:1'],
          $component_hierarchy[1]['3456'],
          $additional_arg,
        ],
        [
          'test_storage:1',
          $section_storage_collection['test_storage:1'],
          $component_hierarchy[1]['7890'],
          $additional_arg,
        ],
        [
          'test_storage:2',
          $section_storage_collection['test_storage:2'],
          $component_hierarchy_2[0]['9876'],
          $additional_arg,
        ],
        [
          'test_storage:2',
          $section_storage_collection['test_storage:2'],
          $component_hierarchy_2[0]['5432'],
          $additional_arg,
        ],
      )
      // Return a mixture of indicators for whether a component should be saved.
      // Return FALSE for all items on the second storage entry to ensure it is
      // not updated and should be excluded from returned results.
      ->willReturnOnConsecutiveCalls(FALSE, FALSE, TRUE, FALSE, TRUE, FALSE, FALSE);
    $callback = [$callback_mock, 'myCallback'];

    $layoutHelper = $this->getLayoutHelper();
    $changed = $layoutHelper->applyToSectionComponents($section_storage_collection, $callback, $additional_arg);

    // Since components were only updated within the first storage item, the
    // second collection should not be included in returned results.
    $this->assertEquals(array_intersect_key($section_storage_collection, ['test_storage:1' => TRUE]), $changed);
  }

  /**
   * @covers ::getSectionStorageFromEntities
   * @covers ::setUpdateHelper
   * @covers ::__construct
   * @uses \Drupal\Core\Plugin\Context\Context
   * @uses \Drupal\Core\Plugin\Context\ContextDefinition
   * @uses \Drupal\Core\Plugin\Context\EntityContext
   * @uses \Drupal\Core\Plugin\Context\EntityContextDefinition
   *
   * @todo Add test coverage for filtering.
   */
  public function testGetSectionStorageFromEntities(): void {
    $seed_data = [
      'displays' => [
        'node.page.default' => [
          'id' => 'node.page.default',
          'mode' => 'default',
          'target_entity_type' => 'node',
          'layout_builder_enabled' => TRUE,
        ],
        'node.article.default' => [
          'id' => 'node.article.default',
          'mode' => 'default',
          'target_entity_type' => 'node',
          'layout_builder_enabled' => FALSE,
        ],
      ],
      'storage_definitions' => [
        'overrides' => TRUE,
        'defaults' => TRUE,
      ],
      'entities' => [
        'node' => [
          'class' => Node::class,
          'id' => 'node',
          'label' => 'Node',
          'entities' => [
            [
              'id' => 1,
            ],
            [
              'id' => 2,
            ],
          ],
        ],
      ],
    ];

    // Prepare handling for contexts looking up typed data definitions.
    $this->mockTypedDataDefinitions();

    // Only the keys are used from this to identify the types of section storage
    // that are available.
    $this->sectionStorageManager->getDefinitions()
      ->willReturn($seed_data['storage_definitions']);

    // Prepare display mocks for iteration.
    $displays = [];
    foreach ($seed_data['displays'] as $display_id => $display_data) {
      $display = $this->mockEntityDisplay($display_data);
      $displays[$display_id] = $display->reveal();
    }
    $this->displayStorage->loadMultiple()->willReturn($displays);

    // Mock loading of default storages using data from mocked display contexts.
    $prophesize = [$this, 'prophesize'];
    $this->sectionStorageManager->load(Argument::type('string'), Argument::any())
      ->will(function (array $args) use ($prophesize) {
        // Only load storages here for the 'defaults' plugin.
        if ($args[0] !== 'defaults') {
          return NULL;
        }

        $contexts = $args[1];
        /** @var \Drupal\Core\Plugin\Context\EntityContext $entity_context */
        $entity_context = $contexts['display'];
        /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
        $display = $entity_context->getContextData();

        $storage = $prophesize(SectionStorageInterface::class);
        $storage->getStorageId()->willReturn($display->id());
        return $storage->reveal();
      });

    // Mock entity storage loading by context using data from mocked entities
    // created later.
    $this->sectionStorageManager->findByContext(Argument::type('array'), Argument::any())
      ->will(function (array $args) use ($prophesize) {
        $contexts = $args[0];
        /** @var \Drupal\Core\Plugin\Context\EntityContext $entity_context */
        $entity_context = $contexts['entity'];
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $entity_context->getContextData();

        $storage = $prophesize(SectionStorageInterface::class);
        $storage_id = $entity->getEntityTypeId() . '.' . $entity->id();
        $storage->getStorageId()->willReturn($storage_id);
        return $storage->reveal();
      });

    // Prepare mocks for all entities in the seed data.
    foreach ($seed_data['entities'] as $entity_type_id => $type_data) {
      $entity_storage = $this->prophesize(EntityStorageInterface::class);

      // Collect entity instances for loading.
      $entities = [];
      foreach ($type_data['entities'] as $entity_data) {
        $entity_type = $this->prophesize(EntityTypeInterface::class);
        $entity_type->id()->willReturn($entity_type_id);
        $entity_type->getLabel()->willReturn($type_data['label']);

        $entity = $this->prophesize($type_data['class']);
        // Implement this interface to shortcut Context value assignment.
        $entity->willImplement(TypedDataInterface::class);
        $entity->getEntityType()->willReturn($entity_type->reveal());
        $entity->getEntityTypeId()->willReturn($entity_type_id);
        $entity->id()->willReturn($entity_data['id']);
        $entity->getCacheContexts()->willReturn([]);
        $entity->getCacheTags()->willReturn([]);
        $entity->getCacheMaxAge()->willReturn([]);

        $entities[$entity_data['id']] = $entity->reveal();
      }
      $entity_storage->loadMultiple(NULL)->willReturn($entities);

      // Register the mocked entity storage for this entity type with all
      // prepared entities.
      $this->entityTypeManager->getStorage($entity_type_id)
        ->willReturn($entity_storage->reveal());

      $this->entityTypeManager->getDefinition($entity_type_id)
        ->willReturn(NULL);
    }

    // Assemble list of expected keys in the returned result.
    $expected = [];
    foreach ($seed_data['displays'] as $display_id => $display_data) {
      if ($display_data['layout_builder_enabled']) {
        // Default display configurations are only loaded for the 'defaults'
        // storage plugin.
        $expected[] = 'defaults:' . $display_id;
      }
    }
    foreach (array_keys($seed_data['storage_definitions']) as $storage_type) {
      foreach ($seed_data['entities'] as $entity_type_id => $type_data) {
        foreach ($type_data['entities'] as $entity) {
          $expected[] = $storage_type . ':' . $entity_type_id . '.' . $entity['id'];
        }
      }
    }

    $layoutHelper = $this->getLayoutHelper();
    $actual = $layoutHelper->getSectionStorageFromEntities();

    // Only check the keys since all returned results are mocked.
    $this->assertEqualsCanonicalizing($expected, array_keys($actual));
    $this->assertContainsOnlyInstancesOf(SectionStorageInterface::class, $actual);
  }

  /**
   * @covers ::getSectionStorageFromTempStore
   * @covers ::setUpdateHelper
   * @covers ::__construct
   */
  public function testGetSectionStorageFromTempStore(): void {
    $generator = $this->getRandomGenerator();
    $seed_data = [
      // Storage type definition key.
      'overrides' => [
        // KeyValueStore entry.
        'node.1.default.en' => [
          'data' => [
            // Section storage content.
            'section_storage' => [
              0 => [
                '1234' => $generator->object(),
              ],
            ],
          ],
        ],
      ],
      'defaults' => [],
    ];

    // Only the keys are used from this to identify the types of section storage
    // that are available.
    $this->sectionStorageManager->getDefinitions()->willReturn($seed_data);

    // Prepare mocks from the seed data.
    $expected = [];
    foreach ($seed_data as $storage_type => $entries) {
      $emptyStorage = $this->prophesize(SectionStorageInterface::class);

      $mocked_entries = [];
      foreach ($entries as $key => $data) {
        $parts = explode('.', $key);
        $context_key = implode('.', array_slice($parts, 0, 2));

        $contexts = [$generator->object(), $generator->object()];
        $emptyStorage->deriveContextsFromRoute($context_key, Argument::cetera())
          ->willReturn($contexts);

        if (isset($data['data']['section_storage']) && $storage_data = $data['data']['section_storage']) {
          $sectionStorage = $this->prepareSectionLayout($storage_data)->reveal();
          $expected[] = $sectionStorage;
        }
        else {
          $sectionStorage = NULL;
        }

        // Ignore loading of additional storages from contexts.
        // @todo Mock varied loading of storages with additional contexts.
        $this->sectionStorageManager->load($storage_type, $contexts)
          ->willReturn(NULL);

        $mocked_entries[$key] = (object) [
          'data' => [
            'section_storage' => $sectionStorage,
          ],
        ];
      }

      $keyValueEntry = $this->prophesize(KeyValueStoreInterface::class);
      $keyValueEntry->getAll()->willReturn($mocked_entries);

      $this->keyValueExpirable->get("tempstore.shared.layout_builder.section_storage.$storage_type")
        ->willReturn($keyValueEntry->reveal());

      $this->sectionStorageManager->loadEmpty($storage_type)
        ->willReturn($emptyStorage->reveal());
    }

    $layoutHelper = $this->getLayoutHelper();
    $this->assertEquals($expected, $layoutHelper->getSectionStorageFromTempStore());
  }

  /**
   * Prepare a mocked section storage hierarchy for a collection of components.
   *
   * @param array $component_hierarchy
   *   An associative array of mocked components keyed first by a section id and
   *   then by a uuid.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\layout_builder\SectionStorageInterface>
   *   A SectionStorage class prophecy prepared with mocked methods and
   *   hierarchy for traversal of components. Prior to usage, the prophecy will
   *   still need to be revealed.
   */
  protected function prepareSectionLayout(array $component_hierarchy): ObjectProphecy {
    // Mock section classes for traversal.
    $sections = [];
    foreach ($component_hierarchy as $section_delta => $components) {
      $section = $this->prophesize(Section::class);
      $section->getComponents()->willReturn($components);
      $sections[$section_delta] = $section->reveal();
    }

    $sectionStorage = $this->prophesize(SectionStorageInterface::class);
    $sectionStorage->getSections()->willReturn($sections);

    return $sectionStorage;
  }

  /**
   * Prepare mocking for Context usage looking up typed data definitions.
   */
  protected function mockTypedDataDefinitions(): void {
    // Prepare a promise callback to support method chaining.
    $returnSelf = function (array $args, ObjectProphecy $objectProphecy) {
      return $objectProphecy->reveal();
    };

    // Mock a data definition that will handle Context plugin usage and chained
    // configuration calls. Note that all assigned values are ignored for the
    // purposes of these tests.
    $definition = $this->prophesize(DataDefinition::class);
    $definition->setLabel(Argument::any())->will($returnSelf);
    $definition->setDescription(Argument::any())->will($returnSelf);
    $definition->setRequired(Argument::any())->will($returnSelf);
    $definition->setConstraints(Argument::any())->will($returnSelf);
    $definition->getConstraints()->willReturn([]);

    // Prepare the typed data manager service to create data definitions when
    // it gets called.
    $typed_data_manager = $this->prophesize(TypedDataManagerInterface::class);
    $typed_data_manager->createDataDefinition(Argument::any())
      ->willReturn($definition->reveal());
    $typed_data_manager->create(Argument::cetera())->willReturn(NULL);

    // Register the typed data manager service for lookup when Context requests
    // it during Context instantiation.
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typed_data_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Mock an entity display using provided settings.
   *
   * @param array $display_data
   *   An array of display settings to guide configuration of the mocked entity.
   *   The following keys are required:
   *   - layout_builder_enabled: A boolean value indicating if the display uses
   *     layout builder.
   *   - mode: A string value indicating the display mode.
   *   - target_entity_type: A string value containing the applicable entity
   *     type id for this display.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay>
   *   The prepared object prophecy for the entity display.
   */
  public function mockEntityDisplay(array $display_data): ObjectProphecy {
    $entity_type = $this->prophesize(ConfigEntityType::class);
    $entity_type->id()->willReturn('entity_view_display');
    $entity_type->getLabel()->willReturn('Entity view display');

    $display = $this->prophesize(LayoutBuilderEntityViewDisplay::class);
    // Implement this interface to shortcut Context value assignment.
    $display->willImplement(TypedDataInterface::class);
    $display->isLayoutBuilderEnabled()
      ->willReturn($display_data['layout_builder_enabled']);
    $display->getEntityType()->willReturn($entity_type->reveal());
    $display->id()->willReturn($display_data['id']);
    $display->getMode()->willReturn($display_data['mode']);
    $display->getTargetEntityTypeId()
      ->willReturn($display_data['target_entity_type']);
    $display->getCacheContexts()->willReturn([]);
    $display->getCacheTags()->willReturn([]);
    $display->getCacheMaxAge()->willReturn([]);

    // @phpstan-ignore return.type
    return $display;
  }

}
