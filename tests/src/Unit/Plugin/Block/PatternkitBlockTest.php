<?php

namespace Drupal\Tests\patternkit\Unit\Plugin\Block;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Utility\Token;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\patternkit\Entity\PatternkitBlock as EntityPatternkitBlock;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests for the PatternkitBlock block plugin.
 *
 * @coversDefaultClass \Drupal\patternkit\Plugin\Block\PatternkitBlock
 * @group patternkit
 */
class PatternkitBlockTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Controls the block cache.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface|\Drupal\Core\Block\BlockManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $blockManager;

  /**
   * The context handler service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $contextHandler;

  /**
   * Allows adding all current contexts to the block plugin.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $contextRepository;

  /**
   * Entity storage for patternkit_pattern entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternStorage;

  /**
   * Entity storage for patternkit_block entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $blockStorage;

  /**
   * The pattern repository service for loading patterns.
   *
   * @var \Drupal\patternkit\PatternRepository|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternRepository;

  /**
   * Loads plugins for parsing and rendering patterns.
   *
   * @var \Drupal\patternkit\PatternLibraryPluginManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternLibraryPluginManager;

  /**
   * Encodes and decodes configuration for storage.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $serializer;

  /**
   * Parses and replaces Drupal tokens.
   *
   * @var \Drupal\Core\Utility\Token|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $token;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $languageManager;

  /**
   * The patternkit logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $logger;

  /**
   * The Patternkit environment service.
   *
   * @var \Drupal\patternkit\PatternkitEnvironment|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $patternkitEnvironment;

  /**
   * The plugin instance being tested.
   *
   * @var \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  protected PatternkitBlock $plugin;

  /**
   * The default pattern ID to use for plugin instantiations.
   *
   * @const string
   *
   * @see \Drupal\Tests\patternkit\Unit\Plugin\Block\PatternkitBlockTest::getTestSubject()
   */
  const DEFAULT_PATTERN_ID = '@patternkit/atoms/example/src/example';

  /**
   * The default plugin ID to use for plugin instantiations.
   *
   * @const string
   *
   * @see \Drupal\Tests\patternkit\Unit\Plugin\Block\PatternkitBlockTest::getTestSubject()
   */
  const DEFAULT_PLUGIN_ID = 'patternkit_block:patternkit_atoms_example_src_example';

  /**
   * The default plugin definition values to use for plugin instantiations.
   *
   * @var array
   *
   * @see \Drupal\Tests\patternkit\Unit\Plugin\Block\PatternkitBlockTest::getTestSubject()
   */
  protected array $mockPluginDefinition = [
    'category' => 'Patternkit:patternkit/atom',
    'admin_label' => '[Patternkit] @pattern',
    'pattern' => NULL,
    'context_definitions' => [],
    'id' => 'patternkit_block',
    'deriver' => 'Drupal\patternkit\Plugin\Derivative\PatternkitBlock',
    'class' => 'Drupal\patternkit\Plugin\Block\PatternkitBlock',
    'provider' => 'patternkit',
  ];

  /**
   * A mocked Pattern entity to be loaded in the plugin definition.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface
   */
  protected ObjectProphecy $defaultDefinitionPattern;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->blockManager = $this->prophesize(BlockManager::class);
    $this->contextHandler = $this->prophesize(ContextHandlerInterface::class);
    $this->contextRepository = $this->prophesize(ContextRepositoryInterface::class);
    $this->patternStorage = $this->prophesize(EntityStorageInterface::class);
    $this->blockStorage = $this->prophesize(EntityStorageInterface::class);
    $this->patternRepository = $this->prophesize(PatternRepository::class);
    $this->patternLibraryPluginManager = $this->prophesize(PatternLibraryPluginManager::class);
    $this->serializer = $this->prophesize(SerializationInterface::class);
    $this->token = $this->prophesize(Token::class);
    $this->languageManager = $this->prophesize(LanguageManagerInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $this->patternkitEnvironment = $this->prophesize(PatternkitEnvironment::class);

    // Initialize values for prepareTokenContexts() execution in constructor.
    $this->token->getInfo()->willReturn(['tokens' => []]);
    $this->contextRepository->getAvailableContexts()->willReturn([]);

    $this->defaultDefinitionPattern = $this->preparePatternProphecy([
      'name' => self::DEFAULT_PATTERN_ID,
      'library' => '@patternkit',
      'path' => 'atoms/example/src/example',
    ]);

    $this->mockPluginDefinition['pattern'] = $this->defaultDefinitionPattern->reveal();
  }

  /**
   * A helper method to instantiate an instance of the subject being tested.
   *
   * This allows for late instantiation of the prophecy objects passed into the
   * constructor to enable more customization of the dependencies per test
   * without as much boilerplate in each test case.
   *
   * @param array $configuration
   *   (Optional) An associative array of configuration values to be passed into
   *   the plugin constructor during instantiation.
   *
   * @return \Drupal\patternkit\Plugin\Block\PatternkitBlock
   *   An instantiated instance of the test subject with all configured
   *   dependency mocks injected appropriately.
   */
  protected function getTestSubject(array $configuration = []): PatternkitBlock {
    $plugin = new PatternkitBlock(
      $this->blockManager->reveal(),
      $configuration,
      $this->contextHandler->reveal(),
      $this->contextRepository->reveal(),
      $this->patternStorage->reveal(),
      $this->blockStorage->reveal(),
      $this->patternRepository->reveal(),
      $this->patternLibraryPluginManager->reveal(),
      self::DEFAULT_PLUGIN_ID,
      $this->mockPluginDefinition,
      $this->serializer->reveal(),
      $this->token->reveal(),
      $this->languageManager->reveal(),
      $this->logger->reveal(),
    );

    $plugin->setPatternkitEnvironment($this->patternkitEnvironment->reveal());

    return $this->plugin = $plugin;
  }

  /**
   * Test the default configuration for the plugin.
   *
   * @covers ::defaultConfiguration
   * @covers ::__construct
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testDefaultConfiguration(): void {
    $configuration = $this->getTestSubject()->defaultConfiguration();

    $this->assertIsArray($configuration);
    $this->assertArrayHasKey('presentation_style', $configuration);
    $this->assertEquals($configuration['presentation_style'], 'html');
    $this->assertArrayHasKey('label_display', $configuration);
    $this->assertEquals($configuration['label_display'], FALSE);
    $this->assertArrayHasKey('version', $configuration);
    $this->assertNull($configuration['version']);
  }

  /**
   * @covers ::getPatternId
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetPatternId(): void {
    $plugin = $this->getTestSubject();

    $this->assertEquals(self::DEFAULT_PATTERN_ID, $plugin->getPatternId());
  }

  /**
   * Test Pattern entity loading with default database caching behavior.
   *
   * @covers ::getPatternEntity
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetPatternEntityWithCache(): void {
    // Mock the pattern revision ID to be loaded.
    $configuration = [
      'pattern' => 1234,
    ];

    // Mock a Pattern entity to return.
    $patternProphecy = $this->prophesize(Pattern::class);
    $pattern = $patternProphecy->reveal();

    // Mock the revision loading process.
    $this->patternStorage->loadRevision(1234)
      ->shouldBeCalledOnce()
      ->willReturn($pattern);

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($pattern, $plugin->getPatternEntity());
  }

  /**
   * Test Pattern entity loading with database caching behavior disabled.
   *
   * @covers ::getPatternEntity
   * @covers ::getEnvironmentFeature
   * @covers ::getPatternId
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetPatternEntityWithoutCache(): void {
    // Mock the pattern revision ID to be loaded.
    $configuration = [
      'pattern' => 1234,
    ];

    // Set the configuration to bypass the database cache.
    $this->patternkitEnvironment->getFeatureOption('bypass_database_cache', FALSE)
      ->shouldBeCalledOnce()
      ->willReturn(TRUE);

    // Mock a Pattern entity to return.
    $patternProphecy = $this->prophesize(Pattern::class);
    $pattern = $patternProphecy->reveal();

    // The specific revision should not be loaded.
    $this->patternStorage->loadRevision($configuration['pattern'])
      ->shouldNotBeCalled();

    // The pattern should be loaded from the pattern repository instead.
    $this->patternRepository->getPattern(self::DEFAULT_PATTERN_ID, TRUE)
      ->shouldBeCalled()
      ->willReturn($pattern);

    // The ID should be fetched from the plugin definition.
    $this->defaultDefinitionPattern->getAssetId()->shouldBeCalled();

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($pattern, $plugin->getPatternEntity());
  }

  /**
   * Test Pattern entity loading with a load failure.
   *
   * @covers ::getPatternEntity
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetPatternEntityWithLoadFailure(): void {
    // Mock the pattern revision ID to be loaded.
    $configuration = [
      'pattern' => 1234,
    ];

    // Mock a Pattern entity to return.
    $patternProphecy = $this->prophesize(Pattern::class);
    $pattern = $patternProphecy->reveal();

    // Mock a failure to load the requested revision.
    $this->patternStorage->loadRevision(1234)
      ->shouldBeCalledOnce()
      ->willReturn(NULL);

    // Expect a warning to be logged.
    $this->logger->warning('Unable to load pattern revision %revision_id for block plugin %plugin_id.', [
      '%revision_id' => $configuration['pattern'],
      '%plugin_id' => self::DEFAULT_PLUGIN_ID,
    ])
      ->shouldBeCalledOnce();

    // The pattern should be loaded from the pattern repository instead.
    $this->patternRepository->getPattern(self::DEFAULT_PATTERN_ID, TRUE)
      ->shouldBeCalled()
      ->willReturn($pattern);

    // The ID should be fetched from the plugin definition.
    $this->defaultDefinitionPattern->getAssetId()->shouldBeCalled();

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($pattern, $plugin->getPatternEntity());
  }

  /**
   * @covers ::getBlockEntity
   * @covers ::getConfiguration
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetBlockEntityWithRevisionId(): void {
    $configuration = [
      'patternkit_block_rid' => 123,
    ];

    // Mock the block to be returned.
    $block = $this->prophesize(EntityPatternkitBlock::class);
    $this->blockStorage->loadRevision($configuration['patternkit_block_rid'])
      ->willReturn($block->reveal());

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($block->reveal(), $plugin->getBlockEntity());
  }

  /**
   * @covers ::getBlockEntity
   * @covers ::getConfiguration
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetBlockEntityWithBlockId(): void {
    $configuration = [
      'patternkit_block_id' => 123,
    ];

    // Mock the block to be returned.
    $block = $this->prophesize(EntityPatternkitBlock::class);
    $this->blockStorage->load($configuration['patternkit_block_id'])
      ->willReturn($block->reveal());

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($block->reveal(), $plugin->getBlockEntity());
  }

  /**
   * @covers ::getBlockEntity
   * @covers ::getConfiguration
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetBlockEntityWithMissingBlock(): void {
    $configuration = [
      'patternkit_block_rid' => 123,
    ];

    // Mock a load failure.
    $this->blockStorage->loadRevision($configuration['patternkit_block_rid'])
      ->willReturn(NULL);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Unable to load Patternkit Block entity.');

    $plugin = $this->getTestSubject($configuration);
    $plugin->getBlockEntity();
  }

  /**
   * @covers ::getBlockEntity
   * @covers ::getConfiguration
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetBlockEntityWithNewBlock(): void {
    // Don't provide a block entity reference as if a new block is being
    // created.
    $configuration = [];

    // Mock the block to be created.
    $block = $this->prophesize(EntityPatternkitBlock::class);
    $this->blockStorage->create(Argument::type('array'))
      ->willReturn($block->reveal());

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($block->reveal(), $plugin->getBlockEntity());
  }

  /**
   * @covers ::getBlockEntity
   * @covers ::getConfiguration
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetBlockEntityWithTranslation(): void {
    // Include a language code to trigger attempts to load a translation.
    $configuration = [
      'patternkit_block_rid' => 123,
      'langcode' => 'EN',
    ];

    // Mock the block to be loaded.
    $block = $this->prophesize(EntityPatternkitBlock::class);
    $this->blockStorage->loadRevision($configuration['patternkit_block_rid'])
      ->willReturn($block->reveal());

    // Prepare a translation to load.
    $translatedBlock = $this->prophesize(EntityPatternkitBlock::class);
    $block->hasTranslation('EN')->shouldBeCalledOnce()
      ->willReturn(TRUE);
    $block->getTranslation('EN')->willReturn($translatedBlock->reveal());

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($translatedBlock->reveal(), $plugin->getBlockEntity());
  }

  /**
   * @covers ::getBlockEntity
   * @covers ::getConfiguration
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetBlockEntityWithoutAvailableTranslation(): void {
    // Include a language code to trigger attempts to load a translation.
    $configuration = [
      'patternkit_block_rid' => 123,
      'langcode' => 'DE',
    ];

    // Mock the block to be loaded.
    $block = $this->prophesize(EntityPatternkitBlock::class);
    $this->blockStorage->loadRevision($configuration['patternkit_block_rid'])
      ->willReturn($block->reveal());

    // Prepare translation loading.
    $block->hasTranslation('DE')->shouldBeCalledOnce()
      ->willReturn(FALSE);
    $block->getTranslation('DE')->shouldNotBeCalled();

    $plugin = $this->getTestSubject($configuration);

    // The originally loaded revision should be the returned output.
    $this->assertSame($block->reveal(), $plugin->getBlockEntity());
  }

  /**
   * @covers ::getBasePattern
   * @covers ::getPatternEntity
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetBasePattern(): void {
    $patternName = '@my/pattern';

    $prophecy = $this->preparePatternProphecy([
      'name' => $patternName,
    ]);
    $this->patternStorage->loadRevision(Argument::type('int'))
      ->willReturn($prophecy->reveal());

    // Add a placeholder pattern ID to load the mocked pattern entity.
    $configuration = [
      'pattern' => 123,
    ];

    // Prepare a pattern to be returned.
    $patternProphecy = $this->preparePatternProphecy();
    $pattern = $patternProphecy->reveal();

    // Expect the base pattern to be loaded using our pattern name.
    $this->patternRepository->getPattern($patternName, TRUE)
      ->shouldBeCalledOnce()
      ->willReturn($pattern);

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($pattern, $plugin->getBasePattern());
  }

  /**
   * Test loading the cached pattern entity with a defined revision ID.
   *
   * @covers ::getCachedPatternEntity
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetCachedPatternEntityWithPatternRevision(): void {
    // Define a specific revision ID to be loaded.
    $configuration = [
      'pattern' => rand(1, 100),
    ];

    // Mock a pattern to be returned.
    $patternProphecy = $this->preparePatternProphecy();
    $pattern = $patternProphecy->reveal();

    // Expect the revision to be requested.
    $this->patternStorage->loadRevision($configuration['pattern'] ?? '')
      ->shouldBeCalledOnce()
      ->willReturn($pattern);

    // The pattern should not be loaded from the repository.
    $this->patternRepository->getPattern(Argument::cetera())
      ->shouldNotBeCalled();

    // The pattern should not be searched for without an ID.
    $this->patternStorage->loadByProperties(Argument::any())
      ->shouldNotBeCalled();

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($pattern, $plugin->getCachedPatternEntity());
  }

  /**
   * Test loading the cached pattern entity without a defined revision ID.
   *
   * @covers ::getCachedPatternEntity
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testGetCachedPatternEntityWithoutRevision(): void {
    // Consolidate expectation values for reuse and reference.
    $expectations = [
      'pattern_id' => self::DEFAULT_PATTERN_ID,
      'library' => '@patternkit',
      'path' => 'atoms/example/src/example',
    ];

    $configuration = [];

    // Mock a base pattern to be returned.
    $basePatternProphecy = $this->preparePatternProphecy([
      'name' => $expectations['pattern_id'],
      'library' => $expectations['library'],
      'path' => $expectations['path'],
    ]);

    // The mocked pattern definition should be loaded from the repository.
    $this->patternRepository->getPattern($expectations['pattern_id'], TRUE)
      ->shouldBeCalled()
      ->willReturn($basePatternProphecy->reveal());

    // Mock a result pattern to be returned.
    $resultPatternProphecy = $this->preparePatternProphecy();

    // The pattern should be searched for without an ID.
    $this->patternStorage->loadByProperties(Argument::allOf(
      Argument::withEntry('library', $expectations['library']),
      Argument::withEntry('path', $expectations['path']),
    ))
      ->shouldBeCalledOnce()
      ->willReturn([
        $resultPatternProphecy->reveal(),
      ]);

    $plugin = $this->getTestSubject($configuration);
    $this->assertSame($resultPatternProphecy->reveal(), $plugin->getCachedPatternEntity());
  }

  /**
   * @covers ::build
   * @uses \Drupal\Core\Render\BubbleableMetadata
   */
  public function testBuild(): void {
    $blockCacheData = [
      'contexts' => [],
      'tags' => [
        'patternkit_block:123',
      ],
      'max-age' => 100,
    ];
    $patternCacheData = [
      'contexts' => [],
      'tags' => [
        'pattern:' . self::DEFAULT_PATTERN_ID,
        'pattern:456',
      ],
      'max-age' => 100,
    ];
    $contextValues = [
      'node' => 'node placeholder',
      'user' => 'user placeholder',
    ];
    $configData = $this->getRandomGenerator()->object();

    $block = $this->prophesize(EntityPatternkitBlock::class);
    $block->getCacheContexts()->willReturn($blockCacheData['contexts']);
    $block->getCacheTags()->willReturn($blockCacheData['tags']);
    $block->getCacheMaxAge()->willReturn($blockCacheData['max-age']);

    // Shortcut magic methods for looking up data value. Config data will be
    // artificially returned using the mocked decode method instead.
    $block->hasField('data')->willReturn(FALSE);

    $pattern = $this->preparePatternProphecy();
    $pattern->getCacheContexts()->willReturn($patternCacheData['contexts']);
    $pattern->getCacheTags()->willReturn($patternCacheData['tags']);
    $pattern->getCacheMaxAge()->willReturn($patternCacheData['max-age']);

    $plugin = $this->createPartialMock(PatternkitBlock::class, [
      'getBlockEntity',
      'getPatternEntity',
      'getContextValues',
      'decode',
    ]);
    $plugin->method('getBlockEntity')->willReturn($block->reveal());
    $plugin->method('getPatternEntity')->willReturn($pattern->reveal());
    $plugin->method('getContextValues')->willReturn($contextValues);
    $plugin->method('decode')->willReturn($configData);

    $element = $plugin->build();

    $this->assertEqualsCanonicalizing([
      '#type',
      '#pattern',
      '#config',
      '#context',
      '#cache',
      '#attached',
    ], array_keys($element));
    $this->assertEquals('pattern', $element['#type']);
    $this->assertSame($pattern->reveal(), $element['#pattern']);
    $this->assertSame($configData, $element['#config']);
    $this->assertEquals($contextValues, $element['#context']);
    $this->assertEqualsCanonicalizing(array_merge($blockCacheData['contexts'], $patternCacheData['contexts']), $element['#cache']['contexts']);
    $this->assertEqualsCanonicalizing(array_merge($blockCacheData['tags'], $patternCacheData['tags']), $element['#cache']['tags']);
    $this->assertEqualsCanonicalizing(100, $element['#cache']['max-age']);
    $this->assertEqualsCanonicalizing([], $element['#attached']);
  }

  /**
   * @covers ::cachePatternEntity
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testCachePatternEntity(): void {
    // Mock a cached pattern with an older hash.
    $cachedPattern = $this->preparePatternProphecy([
      'id' => 10,
      'rid' => 122,
      'hash' => 'older_hash',
    ]);

    // Mock a pattern with a newer hash.
    $newPattern = $this->preparePatternProphecy([
      'id' => 10,
      'rid' => 123,
      'hash' => 'newer_hash',
    ]);

    // Mock returning of an updated pattern.
    $cachedPattern->updateFromPattern(Argument::exact($newPattern->reveal()))
      ->shouldBeCalledOnce()
      ->will(function (array $args, ObjectProphecy $objectProphecy) {
        // Update the cached pattern prophecy to return the new revision ID.
        $objectProphecy->getRevisionId()->willReturn(123);

        // Return self.
        return $objectProphecy->reveal();
      });

    $cachedPattern->setNewRevision()->shouldBeCalledOnce();
    $cachedPattern->isDefaultRevision(TRUE)->shouldBeCalledOnce();

    // The pattern should be saved after updating from the new pattern since the
    // hash differs.
    $cachedPattern->save()->shouldBeCalled();

    // Since we're updating the existing cached pattern, the new pattern should
    // not be saved.
    $newPattern->save()->shouldNotBeCalled();

    $cachedPattern->isDefaultRevision()->willReturn(TRUE);
    $cachedPattern->isLatestRevision()->willReturn(TRUE);

    // Partially mock the plugin to ease unit testing.
    $pluginMock = $this->createPartialMock(PatternkitBlock::class, [
      'getPatternEntity',
      'getCachedPatternEntity',
      'getEnvironmentFeature',
      'setConfigurationValue',
    ]);

    // Disable database bypassing for this test.
    $pluginMock->method('getEnvironmentFeature')->willReturn(FALSE);

    // Load the new and cached patterns appropriately.
    $pluginMock->method('getPatternEntity')
      ->willReturn($newPattern->reveal());
    $pluginMock->method('getCachedPatternEntity')
      ->willReturn($cachedPattern->reveal());

    $pluginMock->expects($this->once())
      ->method('setConfigurationValue')
      ->with('pattern', 123);

    // The updated cached pattern should be returned.
    $result = $pluginMock->cachePatternEntity();
    $this->assertSame($cachedPattern->reveal(), $result);
  }

  /**
   * @covers ::cachePatternEntity
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testCachePatternEntityWithNewPattern(): void {
    // Mock a pattern without existing IDs to represent a new Pattern entity.
    $pattern = $this->preparePatternProphecy([
      'hash' => 'abc123',
    ]);

    // Partially mock the plugin to ease unit testing.
    $pluginMock = $this->createPartialMock(PatternkitBlock::class, [
      'getPatternEntity',
      'getCachedPatternEntity',
      'getEnvironmentFeature',
    ]);

    // Disable database bypassing for this test.
    $pluginMock->method('getEnvironmentFeature')->willReturn(FALSE);

    // Load the pattern being used.
    $pluginMock->method('getPatternEntity')
      ->willReturn($pattern->reveal());

    // Return NULL to indicate no cached pattern could be found.
    $pluginMock->method('getCachedPatternEntity')
      ->willReturn(NULL);

    // The pattern should be saved.
    $pattern->save()->shouldBeCalled();

    // Assign a revision ID to be recorded after saving.
    $pattern->getRevisionId()->willReturn(123);

    $this->assertSame($pattern->reveal(), $pluginMock->cachePatternEntity());

    // Configuration should be updated to reflect the new revision ID.
    $config = $pluginMock->getConfiguration();
    $this->assertEquals(123, $config['pattern']);
  }

  /**
   * Test when a matching pattern exists in the cache but wasn't loaded.
   *
   * @covers ::cachePatternEntity
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testCachePatternEntityWithUncachedPattern(): void {
    // Mock a cached Pattern with an older hash.
    $cachedPattern = $this->preparePatternProphecy([
      'id' => 10,
      'rid' => 122,
      'hash' => 'older_hash',
    ]);

    // Mock a Pattern with a newer hash but no IDs to represent a Pattern loaded
    // from the filesystem.
    $newPattern = $this->preparePatternProphecy([
      'id' => NULL,
      'hash' => 'newer_hash',
    ]);

    // Partially mock the plugin to ease unit testing.
    $pluginMock = $this->createPartialMock(PatternkitBlock::class, [
      'getPatternEntity',
      'getCachedPatternEntity',
      'getEnvironmentFeature',
    ]);

    // Disable database bypassing for this test.
    $pluginMock->method('getEnvironmentFeature')->willReturn(FALSE);

    // Load the new and cached patterns appropriately.
    $pluginMock->method('getPatternEntity')
      ->willReturn($newPattern->reveal());
    $pluginMock->method('getCachedPatternEntity')
      ->willReturn($cachedPattern->reveal());

    // Since this is an updated version the same Pattern type, a new revision
    // should be getting created with all updated values.
    $cachedPattern->updateFromPattern(Argument::exact($newPattern->reveal()))
      ->shouldBeCalled();
    $cachedPattern->setNewRevision()->shouldBeCalled();
    $cachedPattern->isDefaultRevision(TRUE)->shouldBeCalled();

    // The cached Pattern entity should be saved to create a new revision with
    // updated values.
    $cachedPattern->save()->shouldBeCalled();

    // An updated revision of the cached Pattern entity should be returned.
    $this->assertSame($cachedPattern->reveal(), $pluginMock->cachePatternEntity());
  }

  /**
   * Test saving block content without serialized block content.
   *
   * @covers ::saveBlockContent
   * @covers ::getConfiguration
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   * @dataProvider providerSaveBlockContent
   *
   * @todo Test with serialized block content separately.
   */
  public function testSaveBlockContent(bool $new_revision, bool $duplicate_block): void {
    $configuration = [
      'patternkit_block_rid' => 123,
      'block_serialized' => NULL,
    ];

    $block = $this->prophesize(EntityPatternkitBlock::class);

    if ($duplicate_block) {
      $this->blockStorage->loadRevision($configuration['patternkit_block_rid'])
        ->shouldBeCalled()
        ->willReturn($block->reveal());

      $block->createDuplicate()
        ->shouldBeCalledOnce()
        ->willReturn($block->reveal());
    }

    // This test assumes no serialized content is defined which means a block
    // was not edited. In that case, if a block doesn't need to be duplicated
    // it also shouldn't be saved.
    $blockShouldBeSaved = $duplicate_block;

    if ($blockShouldBeSaved) {
      if ($new_revision) {
        $block->setNewRevision()->shouldBeCalledOnce();
        $newRid = rand(0, 100);
        $block->getRevisionId()->willReturn($newRid);
      }
      else {
        $block->getRevisionId()->willReturn($configuration['patternkit_block_rid']);
      }

      $block->save()->shouldBeCalledOnce();

      // Block caches should be cleared after updates as well.
      $this->blockManager->clearCachedDefinitions()->shouldBeCalledOnce();
    }
    else {
      $block->save()->shouldNotBeCalled();

      // Since nothing was saved, caches should not be cleared either.
      $this->blockManager->clearCachedDefinitions()->shouldNotBeCalled();
    }

    $plugin = $this->getTestSubject($configuration);
    $plugin->saveBlockContent($new_revision, $duplicate_block);

    // The revision ID should only be changed if a new revision is saved.
    $expectedRid = ($blockShouldBeSaved && $new_revision) ?
      $newRid : $configuration['patternkit_block_rid'];

    $updatedConfiguration = $plugin->getConfiguration();
    $this->assertEquals($expectedRid, $updatedConfiguration['patternkit_block_rid']);
    $this->assertNull($updatedConfiguration['block_serialized']);
  }

  /**
   * Data provider for testSaveBlockContent().
   */
  public function providerSaveBlockContent(): array {
    $cases = [];

    $cases[] = [TRUE, TRUE];
    $cases[] = [TRUE, FALSE];
    $cases[] = [FALSE, FALSE];
    $cases[] = [FALSE, TRUE];

    return $cases;
  }

  /**
   * Create a Pattern entity prophecy with preconfigured values.
   *
   * This is a helper method to simplify the creation of Pattern entity
   * prophecies with lookup methods configured for specified values.
   *
   * @param array $patternConfig
   *   (Optional) An array of configuration values to map lookup method return
   *   values to.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface
   *   A preconfigured prophecy for a Pattern entity.
   */
  protected function preparePatternProphecy(array $patternConfig = []): ObjectProphecy {
    $patternProphecy = $this->prophesize(Pattern::class);

    foreach ($patternConfig as $key => $value) {
      switch ($key) {
        case 'name':
          $patternProphecy->getAssetId()->willReturn($value);
          break;

        case 'library':
          $patternProphecy->getLibrary()->willReturn($value);
          break;

        case 'path':
          $patternProphecy->getPath()->willReturn($value);
          break;

        case 'id':
          $patternProphecy->id()->willReturn($value);
          break;

        case 'rid':
          $patternProphecy->getRevisionId()->willReturn($value);
          break;

        case 'hash':
          $patternProphecy->getHash()->willReturn($value);
          break;

        default:
          $this->addWarning("Unknown key/value assignment for pattern mocking: $key => $value");
      }

      // NB. This is not returning the SAVED_NEW constant because it is not
      // loaded from common.inc during unit test execution.
      $patternProphecy->save()->willReturn(1);
    }

    return $patternProphecy;
  }

}
