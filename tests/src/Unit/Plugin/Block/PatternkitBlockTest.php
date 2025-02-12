<?php

namespace Drupal\Tests\patternkit\Unit\Plugin\Block;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Utility\Token;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\patternkit\Traits\PatternkitMockHelperTrait;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock as EntityPatternkitBlock;
use Drupal\patternkit\Event\PatternUpdateEvent;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternkitEvents;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests for the PatternkitBlock block plugin.
 *
 * @coversDefaultClass \Drupal\patternkit\Plugin\Block\PatternkitBlock
 * @group patternkit
 */
class PatternkitBlockTest extends UnitTestCase {

  use ProphecyTrait;
  use PatternkitMockHelperTrait;

  /**
   * Controls the block cache.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Block\BlockManager>
   */
  protected ObjectProphecy $blockManager;

  /**
   * The context handler service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Plugin\Context\ContextHandlerInterface>
   */
  protected ObjectProphecy $contextHandler;

  /**
   * Allows adding all current contexts to the block plugin.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Plugin\Context\ContextRepositoryInterface>
   */
  protected ObjectProphecy $contextRepository;

  /**
   * Entity storage for patternkit_pattern entities.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\RevisionableStorageInterface>
   */
  protected ObjectProphecy $patternStorage;

  /**
   * Entity storage for patternkit_block entities.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\RevisionableStorageInterface>
   */
  protected ObjectProphecy $blockStorage;

  /**
   * The pattern repository service for loading patterns.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternRepository>
   */
  protected ObjectProphecy $patternRepository;

  /**
   * Loads plugins for parsing and rendering patterns.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternLibraryPluginManager>
   */
  protected ObjectProphecy $patternLibraryPluginManager;

  /**
   * Encodes and decodes configuration for storage.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Component\Serialization\SerializationInterface>
   */
  protected ObjectProphecy $serializer;

  /**
   * Parses and replaces Drupal tokens.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Utility\Token>
   */
  protected ObjectProphecy $token;

  /**
   * The language manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Language\LanguageManagerInterface>
   */
  protected ObjectProphecy $languageManager;

  /**
   * The event dispatcher service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Symfony\Component\EventDispatcher\EventDispatcherInterface>
   */
  protected ObjectProphecy $eventDispatcher;

  /**
   * The patternkit logger channel.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The Patternkit environment service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternkitEnvironment>
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
   * @var array<string, mixed>
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
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>
   */
  protected ObjectProphecy $defaultDefinitionPattern;

  /**
   * An associative array of method return values for mocked global functions.
   *
   * The key of the array should map to a lookup value for the mocked function
   * being overridden.
   *
   * @var array<string, mixed>
   *
   * @see \Drupal\patternkit\Plugin\Block\serialize()
   * @see https://akrabat.com/replacing-a-built-in-php-function-when-testing-a-component/
   */
  public static array $mockedFunctions = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->blockManager = $this->prophesize(BlockManager::class);
    $this->contextHandler = $this->prophesize(ContextHandlerInterface::class);
    $this->contextRepository = $this->prophesize(ContextRepositoryInterface::class);
    $this->patternStorage = $this->prophesize(RevisionableStorageInterface::class);
    $this->blockStorage = $this->prophesize(RevisionableStorageInterface::class);
    $this->patternRepository = $this->prophesize(PatternRepository::class);
    $this->patternLibraryPluginManager = $this->prophesize(PatternLibraryPluginManager::class);
    $this->serializer = $this->prophesize(SerializationInterface::class);
    $this->token = $this->prophesize(Token::class);
    $this->languageManager = $this->prophesize(LanguageManagerInterface::class);
    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->patternkitEnvironment = $this->prophesize(PatternkitEnvironment::class);

    // Initialize values for prepareTokenContexts() execution in constructor.
    $this->token->getInfo()->willReturn(['tokens' => []]);
    $this->contextRepository->getAvailableContexts()->willReturn([]);

    $this->defaultDefinitionPattern = $this->getMockPatternEntity([
      'asset_id' => self::DEFAULT_PATTERN_ID,
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
   * @param array<string, mixed> $configuration
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
      $this->eventDispatcher->reveal(),
      $this->logger->reveal(),
    );

    $plugin->setPatternkitEnvironment($this->patternkitEnvironment->reveal());

    return $this->plugin = $plugin;
  }

  /**
   * Get a partial mock of the test subject with injected dependencies.
   *
   * @param string[] $methods
   *   (Optional) An array of test class methods to be mocked. These methods
   *   will be marked for mocking, but expectations for them will still need
   *   to be configured on the returned instance.
   * @param array<string, mixed> $configuration
   *   (Optional) An associative array of configuration values to be passed into
   *   the plugin constructor during instantiation.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\patternkit\Plugin\Block\PatternkitBlock
   *   The mocked object with injected dependencies.
   */
  protected function getPartialMock(array $methods = [], array $configuration = []): MockObject {
    // Mock these to disable their execution from the constructor.
    $methods = array_merge($methods, [
      'prepareTokenContexts',
      'defaultConfiguration',
    ]);

    return $this->getMockBuilder(PatternkitBlock::class)
      ->setConstructorArgs([
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
        $this->eventDispatcher->reveal(),
        $this->logger->reveal(),
      ])
      ->onlyMethods($methods)
      ->getMock();
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
    $assetId = '@my/pattern';

    $prophecy = $this->getMockPatternEntity([
      'asset_id' => $assetId,
    ]);
    $this->patternStorage->loadRevision(Argument::type('int'))
      ->willReturn($prophecy->reveal());

    // Add a placeholder pattern ID to load the mocked pattern entity.
    $configuration = [
      'pattern' => 123,
    ];

    // Prepare a pattern to be returned.
    $patternProphecy = $this->getMockPatternEntity();
    $pattern = $patternProphecy->reveal();

    // Expect the base pattern to be loaded using our pattern name.
    $this->patternRepository->getPattern($assetId, TRUE)
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
    $patternProphecy = $this->getMockPatternEntity();
    $pattern = $patternProphecy->reveal();

    // Expect the revision to be requested.
    $this->patternStorage->loadRevision($configuration['pattern'])
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
    $basePatternProphecy = $this->getMockPatternEntity([
      'asset_id' => $expectations['pattern_id'],
      'library' => $expectations['library'],
      'path' => $expectations['path'],
    ]);

    // The mocked pattern definition should be loaded from the repository.
    $this->patternRepository->getPattern($expectations['pattern_id'], TRUE)
      ->shouldBeCalled()
      ->willReturn($basePatternProphecy->reveal());

    // Mock a result pattern to be returned.
    $resultPatternProphecy = $this->getMockPatternEntity();

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
    $configData = (array) $this->getRandomGenerator()->object();

    $block = $this->getMockPatternkitBlockEntity($blockCacheData + [
      'pattern_id' => self::DEFAULT_PATTERN_ID,
      'data' => $configData,
    ]);

    // Shortcut magic methods for looking up data value. Config data will be
    // artificially returned using the mocked decode method instead.
    $block->hasField('data')->willReturn(FALSE);

    $pattern = $this->getMockPatternEntity($patternCacheData);

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
    $this->assertEqualsCanonicalizing([
      ...$blockCacheData['tags'],
      ...$patternCacheData['tags'],
    ], $element['#cache']['tags']);
    $this->assertEqualsCanonicalizing(100, $element['#cache']['max-age']);
    $this->assertEqualsCanonicalizing([], $element['#attached']);
  }

  /**
   * @covers ::cachePatternEntity
   * @uses \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  public function testCachePatternEntity(): void {
    // Mock a cached pattern with an older hash.
    $cachedPattern = $this->getMockPatternEntity([
      'id' => 10,
      'revision_id' => 122,
      'hash' => 'older_hash',
    ]);

    // Mock a pattern with a newer hash.
    $newPattern = $this->getMockPatternEntity([
      'id' => 10,
      'revision_id' => 123,
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
    $pattern = $this->getMockPatternEntity([
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
    $cachedPattern = $this->getMockPatternEntity([
      'id' => 10,
      'revision_id' => 122,
      'hash' => 'older_hash',
    ]);

    // Mock a Pattern with a newer hash but no IDs to represent a Pattern loaded
    // from the filesystem.
    $newPattern = $this->getMockPatternEntity([
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
    $expectedRid = ($blockShouldBeSaved && $new_revision && isset($newRid)) ?
      $newRid : $configuration['patternkit_block_rid'];

    $updatedConfiguration = $plugin->getConfiguration();
    $this->assertEquals($expectedRid, $updatedConfiguration['patternkit_block_rid']);
    $this->assertNull($updatedConfiguration['block_serialized']);
  }

  /**
   * Data provider for testSaveBlockContent().
   */
  public static function providerSaveBlockContent(): array {
    $cases = [];

    $cases[] = [TRUE, TRUE];
    $cases[] = [TRUE, FALSE];
    $cases[] = [FALSE, FALSE];
    $cases[] = [FALSE, TRUE];

    return $cases;
  }

  /**
   * Test updatePattern() when both pattern parameters are provided.
   *
   * @covers ::updatePattern
   * @covers ::getUpdatedPatternRevision
   * @covers ::__construct
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent
   */
  public function testUpdatePatternWithParameters(): void {
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $cachedPattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $basePattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $newPatternRevision
     */
    [
      'cached' => $cachedPattern,
      'base' => $basePattern,
      'new' => $newPatternRevision,
    ] = $this->prepareUpdatePatternDependencies();

    // Prepare a spy for the update event to monitor interactions.
    $event = $this->prophesize(PatternUpdateEvent::class);
    $event->isAltered()->shouldBeCalledOnce()->willReturn(FALSE);

    // Confirm the update event is dispatched.
    $this->eventDispatcher->dispatch($event->reveal(), PatternkitEvents::PATTERN_UPDATE)
      ->shouldBeCalledOnce();

    $plugin = $this->getPartialMock([
      'getPatternEntity',
      'getBasePattern',
      'getUpdateEvent',
    ]);

    // Pattern entity methods shouldn't be called with parameters passed in.
    $plugin->expects($this->never())->method('getPatternEntity');
    $plugin->expects($this->never())->method('getBasePattern');

    // Inject the event spy to monitor interactions.
    $plugin->expects($this->once())->method('getUpdateEvent')
      ->with($this->equalTo($cachedPattern->reveal()), $newPatternRevision->reveal())
      ->willReturn($event->reveal());

    // Prepare a mocked query for loading existing pattern revisions.
    $this->preparePatternQuery();

    $actual = $plugin->updatePattern($cachedPattern->reveal(), $basePattern->reveal());
    $this->assertSame($newPatternRevision->reveal(), $actual);
  }

  /**
   * Test updatePattern() when pattern parameters are not provided.
   *
   * @covers ::updatePattern
   * @covers ::getUpdatedPatternRevision
   * @covers ::__construct
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent
   */
  public function testUpdatePatternWithoutParameters(): void {
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $cachedPattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $basePattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $newPatternRevision
     */
    [
      'cached' => $cachedPattern,
      'base' => $basePattern,
      'new' => $newPatternRevision,
    ] = $this->prepareUpdatePatternDependencies();

    // Prepare a mocked query for loading existing pattern revisions.
    $this->preparePatternQuery();

    // Prepare a spy for the update event to monitor interactions.
    $event = $this->prophesize(PatternUpdateEvent::class);
    $event->isAltered()->shouldBeCalledOnce()->willReturn(FALSE);

    // Confirm the update event is dispatched.
    $this->eventDispatcher->dispatch($event->reveal(), PatternkitEvents::PATTERN_UPDATE)
      ->shouldBeCalledOnce();

    // Mock out-of-scope methods.
    $plugin = $this->getPartialMock([
      'getPatternEntity',
      'getBasePattern',
      'getUpdateEvent',
    ]);

    // Inject the event spy to monitor interactions.
    $plugin->expects($this->once())->method('getUpdateEvent')
      ->with($this->equalTo($cachedPattern->reveal()), $cachedPattern->reveal())
      ->willReturn($event->reveal());

    // Pattern entity methods should be used to load entities if they are not
    // provided as parameters.
    $plugin->expects($this->once())->method('getPatternEntity')
      ->willReturn($cachedPattern->reveal());
    $plugin->expects($this->once())->method('getBasePattern')
      ->willReturn($basePattern->reveal());

    $actual = $plugin->updatePattern();
    $this->assertSame($newPatternRevision->reveal(), $actual);
  }

  /**
   * Test updatePattern() when a new revision has to be created.
   *
   * @covers ::updatePattern
   * @covers ::getUpdatedPatternRevision
   * @covers ::__construct
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent
   */
  public function testUpdatePatternWithoutExistingRevision(): void {
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $cachedPattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $basePattern
     */
    [
      'cached' => $cachedPattern,
      'base' => $basePattern,
    ] = $this->prepareUpdatePatternDependencies();

    // Prepare a mocked query for loading existing pattern revisions that will
    // return no results necessitating the creation of a new revision.
    $this->preparePatternQuery([]);

    // Prepare a spy for the update event to monitor interactions.
    $event = $this->prophesize(PatternUpdateEvent::class);
    $event->isAltered()->shouldBeCalledOnce()->willReturn(FALSE);

    // Confirm the update event is dispatched.
    $this->eventDispatcher->dispatch($event->reveal(), PatternkitEvents::PATTERN_UPDATE)
      ->shouldBeCalledOnce();

    // Mock out-of-scope methods.
    $plugin = $this->getPartialMock([
      'getPatternEntity',
      'getBasePattern',
      'getUpdateEvent',
    ]);

    // Pattern entity methods should be used to load entities if they are not
    // provided as parameters.
    $plugin->expects($this->never())->method('getPatternEntity')
      ->willReturn($cachedPattern->reveal());
    $plugin->expects($this->once())->method('getBasePattern')
      ->willReturn($basePattern->reveal());

    // Inject the event spy to monitor interactions.
    $plugin->expects($this->once())->method('getUpdateEvent')
      ->with($this->equalTo($cachedPattern->reveal()), $cachedPattern->reveal())
      ->willReturn($event->reveal());

    // Mock expected calls for the pattern update process.
    $cachedPattern->updateFromPattern($basePattern->reveal())
      ->shouldBeCalledOnce()
      ->will(function (array $args): PatternInterface {
        /** @var \Drupal\patternkit\Entity\PatternInterface $basePattern */
        $basePattern = $args[0];

        // Inherit relevant values from the base pattern.
        /** @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\Pattern $this */
        $this->getHash()->willReturn($basePattern->getHash());
        $this->getVersion()->willReturn($basePattern->getVersion());

        return $this->reveal();
      });
    $cachedPattern->setNewRevision()->shouldBeCalled();
    $cachedPattern->isDefaultRevision(FALSE)->shouldBeCalled();
    $cachedPattern->save()->shouldBeCalled();

    $actual = $plugin->updatePattern($cachedPattern->reveal());
    $this->assertSame($cachedPattern->reveal(), $actual);
    $this->assertEquals($cachedPattern->reveal()->getVersion(), $basePattern->reveal()->getVersion());
    $this->assertEquals($cachedPattern->reveal()->getHash(), $basePattern->reveal()->getHash());
  }

  /**
   * Test updatePattern() when no update is needed.
   *
   * @covers ::updatePattern
   * @covers ::__construct
   * @uses \Drupal\patternkit\Event\PatternUpdateEvent
   */
  public function testUpdatePatternWithoutUpdateNeeded(): void {
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $cachedPattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $basePattern
     */
    [
      'cached' => $cachedPattern,
      'base' => $basePattern,
    ] = $this->prepareUpdatePatternDependencies();

    // Override the base pattern hash to match thereby indicating an update is
    // not needed.
    $basePattern->getHash()->willReturn($cachedPattern->reveal()->getHash());

    // A query should never be triggered to look up existing revisions.
    $this->patternStorage->getQuery()->shouldNotBeCalled();

    // Calls to update and save the pattern should never be made.
    $cachedPattern->updateFromPattern(Argument::any())->shouldNotBeCalled();
    $cachedPattern->save()->shouldNotBeCalled();

    // An update event should never be created or dispatched.
    $this->eventDispatcher->dispatch(Argument::any(), PatternkitEvents::PATTERN_UPDATE)
      ->shouldNotBeCalled();

    // Mock out-of-scope methods.
    $plugin = $this->getPartialMock([
      'getPatternEntity',
      'getBasePattern',
      'getUpdateEvent',
      'getUpdatedPatternRevision',
    ]);

    // An update event should not be created.
    $plugin->expects($this->never())->method('getUpdateEvent');

    // An updated revision should never be searched for.
    $plugin->expects($this->never())->method('getUpdatedPatternRevision');

    // Pattern entity methods should be used to load entities if they are not
    // provided as parameters.
    $plugin->expects($this->once())->method('getPatternEntity')
      ->willReturn($cachedPattern->reveal());
    $plugin->expects($this->once())->method('getBasePattern')
      ->willReturn($basePattern->reveal());

    // Snapshot configuration beforehand to verify no changes were made.
    $originalConfiguration = $plugin->getConfiguration();

    // Null should be returned since no update was made.
    $this->assertNull($plugin->updatePattern());

    // Confirm no changes were made to the configuration during the process.
    $this->assertEquals($originalConfiguration, $plugin->getConfiguration());
  }

  /**
   * @covers ::updatePattern
   * @covers ::getUpdatedPatternRevision
   * @covers ::__construct
   */
  public function testUpdateEventDispatchingWithoutAlterations(): void {
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $cachedPattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $basePattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $newPatternRevision
     */
    [
      'cached' => $cachedPattern,
      'base' => $basePattern,
      'new' => $newPatternRevision,
    ] = $this->prepareUpdatePatternDependencies();

    // Prepare a mocked query for loading existing pattern revisions.
    $this->preparePatternQuery();

    // Prepare a spy for the update event to monitor interactions.
    $event = $this->prophesize(PatternUpdateEvent::class);

    // Confirm the update event is dispatched.
    $this->eventDispatcher->dispatch($event->reveal(), PatternkitEvents::PATTERN_UPDATE)
      ->shouldBeCalledOnce();

    // Mock out-of-scope methods.
    $plugin = $this->getPartialMock([
      'getUpdateEvent',
      'updateBlockEntity',
    ]);

    // Inject the event spy to monitor interactions.
    $plugin->expects($this->once())->method('getUpdateEvent')
      ->with(
        // A clone of the original pattern should be provided.
        $this->equalTo($cachedPattern->reveal()),
        // The new revision should also be passed in.
        $newPatternRevision->reveal())
      ->willReturn($event->reveal());

    // Indicate no alterations were made for this test.
    $event->isAltered()->shouldBeCalledOnce()->willReturn(FALSE);

    // The block entity should not be altered.
    $plugin->expects($this->never())->method('updateBlockEntity');

    $actual = $plugin->updatePattern($cachedPattern->reveal(), $basePattern->reveal());
    $this->assertSame($newPatternRevision->reveal(), $actual);
  }

  /**
   * @covers ::updatePattern
   * @covers ::getUpdatedPatternRevision
   * @covers ::updateBlockEntity
   * @covers ::__construct
   * @covers ::getPatternId
   *
   * @see \Drupal\Tests\patternkit\Unit\Plugin\Block\PatternkitBlockTest::$mockedFunctions
   * @see \Drupal\patternkit\Plugin\Block\serialize()
   */
  public function testUpdateEventDispatchingWithAlterations(): void {
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $cachedPattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $basePattern
     * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\patternkit\Entity\PatternInterface $newPatternRevision
     */
    [
      'cached' => $cachedPattern,
      'base' => $basePattern,
      'new' => $newPatternRevision,
    ] = $this->prepareUpdatePatternDependencies();

    // Prepare a mocked query for loading existing pattern revisions.
    $this->preparePatternQuery();

    // Prepare a spy for the update event to monitor interactions.
    $event = $this->prophesize(PatternUpdateEvent::class);

    // Confirm the update event is dispatched.
    $this->eventDispatcher->dispatch($event->reveal(), PatternkitEvents::PATTERN_UPDATE)
      ->shouldBeCalledOnce();

    // Mock out-of-scope methods.
    $plugin = $this->getPartialMock([
      'getUpdateEvent',
      'getBlockEntity',
    ]);

    // Inject the event spy to monitor interactions.
    $plugin->expects($this->once())->method('getUpdateEvent')
      ->with(
        // A clone of the original pattern should be provided.
        $this->equalTo($cachedPattern->reveal()),
        // The new revision should also be passed in.
        $newPatternRevision->reveal())
      ->willReturn($event->reveal());

    // Indicate alterations were made to test saving updates.
    $event->isAltered()->shouldBeCalledOnce()->willReturn(TRUE);

    // Mock updated content to be assigned to block.
    $updatedContent = (array) $this->getRandomGenerator()->object();
    $event->getContent()->shouldBeCalled()->willReturn($updatedContent);

    // Mock the block entity to be updated.
    $blockEntity = $this->getMockPatternkitBlockEntity();
    $plugin->method('getBlockEntity')->willReturn($blockEntity->reveal());

    // Handle overriding of the builtin serialize() function and set an expected
    // return value.
    // @see \Drupal\patternkit\Plugin\Block\serialize()
    self::$mockedFunctions['serialize'] = $serialized = 'serialized block with updates';

    // Expect the updated content to be serialized and set.
    $blockEntity->set('data', json_encode($updatedContent))
      ->shouldBeCalled();

    // Allow other values to be set as well without breaking for unexpected
    // method calls.
    $blockEntity->set(Argument::type('string'), Argument::any())
      ->shouldBeCalled();

    $actual = $plugin->updatePattern($cachedPattern->reveal(), $basePattern->reveal());
    $this->assertSame($newPatternRevision->reveal(), $actual);

    // @todo Confirm updates to configuration.
    $configuration = $plugin->getConfiguration();
    $this->assertEquals($serialized, $configuration['block_serialized']);
  }

  /**
   * A helper function to prepare pattern prophecies for updatePattern() tests.
   *
   * The pattern storage prophecy is also prepared to load the new pattern
   * revision if queried for the default revision ID.
   *
   * @return array{string: \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>}
   *   An associative array of pattern prophecies with the following keys:
   *   - 'cached': A pattern mocked as if loaded from the database and in need
   *     of updating.
   *   - 'base': A mocked base pattern that is an updated version of the cached
   *     pattern mock.
   *   - 'new': A mocked pattern representing an updated revision.
   *
   * @phpstan-return array{
   *    'cached': \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>,
   *    'base': \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>,
   *    'new': \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>,
   *  }
   */
  protected function prepareUpdatePatternDependencies(): array {
    // Mock a cached Pattern with an older hash.
    $cachedPattern = $this->getMockPatternEntity([
      'id' => 10,
      'revision_id' => 122,
      'hash' => 'older_hash',
      'version' => 'OLD',
      'asset_id' => self::DEFAULT_PATTERN_ID,
    ]);

    // Mock a Pattern with a newer hash but no IDs to represent a Pattern loaded
    // from the filesystem.
    $basePattern = $this->getMockPatternEntity([
      'id' => NULL,
      'hash' => 'newer_hash',
      'version' => 'NEW',
      'asset_id' => self::DEFAULT_PATTERN_ID,
    ]);

    // Mock an updated revision being loaded from the database.
    $newPatternRevision = $this->getMockPatternEntity([
      'id' => 10,
      'revision_id' => 123,
      'hash' => 'newer_hash',
      'version' => 'NEW',
      'asset_id' => self::DEFAULT_PATTERN_ID,
    ]);
    $this->patternStorage->loadRevision(123)
      ->willReturn($newPatternRevision->reveal());

    return [
      'cached' => $cachedPattern,
      'base' => $basePattern,
      'new' => $newPatternRevision,
    ];
  }

  /**
   * Mock and return a query with provided results from pattern storage.
   *
   * @param array<int, int> $results
   *   (Optional) An array of ID-keyed results to return from the query. If no
   *   values are provided, results will default to a single entry matching the
   *   revision ID of mocked entities in prepareUpdatePatternDependencies().
   *
   * @see \Drupal\patternkit\Plugin\Block\PatternkitBlock::getUpdatedPatternRevision()
   */
  protected function preparePatternQuery(array $results = [123 => 123]): void {
    // Prepare a mocked query for loading existing pattern revisions.
    $query = $this->prophesize(QueryInterface::class);
    $query->accessCheck(Argument::any())->willReturn($query->reveal());
    $query->latestRevision()->willReturn($query->reveal());
    $query->condition(Argument::cetera())->willReturn($query->reveal());
    $query->execute()->willReturn($results);

    // Return the mocked query from pattern storage when it's requested.
    $this->patternStorage->getQuery()->willReturn($query->reveal());
  }

}

// Override the builtin serialize() function for testing.
namespace Drupal\patternkit\Plugin\Block;

use Drupal\Tests\patternkit\Unit\Plugin\Block\PatternkitBlockTest;

/**
 * An overridden test function for validating serialized content.
 *
 * @param string $value
 *   The value to be serialized.
 *
 * @return string
 *   A preconfigured return value for the method.
 *
 * @see \Drupal\Tests\patternkit\Unit\Plugin\Block\PatternkitBlockTest::$mockedFunctions
 * @see https://akrabat.com/replacing-a-built-in-php-function-when-testing-a-component/
 */
function serialize($value): string {
  assert(is_string(PatternkitBlockTest::$mockedFunctions['serialize']));
  return PatternkitBlockTest::$mockedFunctions['serialize'];
}
