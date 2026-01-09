<?php

namespace Drupal\Tests\patternkit_media_library\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit_media_library\BlockAltTextUpdater;
use Drupal\patternkit_media_library\ContentImageFieldLocator;
use Drupal\patternkit_media_library\MediaAttributeService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the BlockAltTextUpdater class.
 *
 * @group patternkit
 * @group patternkit_media_library
 * @coversDefaultClass \Drupal\patternkit_media_library\BlockAltTextUpdater
 */
class BlockAltTextUpdaterTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The entity type manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityTypeManagerInterface>
   */
  protected ObjectProphecy $entityTypeManager;

  /**
   * The content image field locator service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit_media_library\ContentImageFieldLocator>
   */
  protected ObjectProphecy $fieldLocator;

  /**
   * The media attribute service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit_media_library\MediaAttributeService>
   */
  protected ObjectProphecy $mediaAttributes;

  /**
   * The pattern repository service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternRepository>
   */
  protected ObjectProphecy $patternRepository;

  /**
   * The logger service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The pattern storage.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  protected ObjectProphecy $patternStorage;

  /**
   * The service under test.
   *
   * @var \Drupal\patternkit_media_library\BlockAltTextUpdater
   */
  protected BlockAltTextUpdater $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->fieldLocator = $this->prophesize(ContentImageFieldLocator::class);
    $this->mediaAttributes = $this->prophesize(MediaAttributeService::class);
    $this->patternRepository = $this->prophesize(PatternRepository::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->patternStorage = $this->prophesize(EntityStorageInterface::class);

    $this->service = new BlockAltTextUpdater(
      $this->entityTypeManager->reveal(),
      $this->fieldLocator->reveal(),
      $this->mediaAttributes->reveal(),
      $this->patternRepository->reveal(),
    );
    $this->service->setLogger($this->logger->reveal());
  }

  /**
   * Test loading pattern with numeric ID.
   *
   * @covers ::loadPatternForBlock
   */
  public function testLoadPatternForBlockWithNumericId(): void {
    $block = $this->prophesize(PatternkitBlock::class);
    $block->getPattern()->willReturn('123');
    $block->id()->willReturn(1);

    $pattern = $this->prophesize(PatternInterface::class);

    $this->entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal())
      ->shouldBeCalledOnce();

    $this->patternStorage->load('123')
      ->willReturn($pattern->reveal())
      ->shouldBeCalledOnce();

    // Use reflection to access protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('loadPatternForBlock');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $block->reveal());

    $this->assertSame($pattern->reveal(), $result);
  }

  /**
   * Test loading pattern with derivative ID.
   *
   * @covers ::loadPatternForBlock
   */
  public function testLoadPatternForBlockWithDerivativeId(): void {
    $pattern_id = '@patternkit_test/image_attributes_test/image_attributes_test';
    $block = $this->prophesize(PatternkitBlock::class);
    $block->getPattern()->willReturn($pattern_id);
    $block->id()->willReturn(21);

    $pattern = $this->prophesize(PatternInterface::class);

    $this->patternRepository->getPattern($pattern_id, TRUE)
      ->willReturn($pattern->reveal())
      ->shouldBeCalledOnce();

    // Entity storage should not be called for derivative IDs.
    $this->entityTypeManager->getStorage(Argument::any())
      ->shouldNotBeCalled();

    // Use reflection to access protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('loadPatternForBlock');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $block->reveal());

    $this->assertSame($pattern->reveal(), $result);
  }

  /**
   * Test loading pattern with derivative ID when pattern is not found.
   *
   * @covers ::loadPatternForBlock
   */
  public function testLoadPatternForBlockWithDerivativeIdNotFound(): void {
    $pattern_id = '@unknown/pattern';
    $block = $this->prophesize(PatternkitBlock::class);
    $block->getPattern()->willReturn($pattern_id);
    $block->id()->willReturn(1);

    $exception = new UnknownPatternException('Pattern not found');

    $this->patternRepository->getPattern($pattern_id, TRUE)
      ->willThrow($exception)
      ->shouldBeCalledOnce();

    $this->logger->warning(
      'Pattern not found for block @block_id: @pattern_id - @message',
      Argument::type('array')
    )->shouldBeCalledOnce();

    // Use reflection to access protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('loadPatternForBlock');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $block->reveal());

    $this->assertNull($result);
  }

  /**
   * Test loading pattern with unrecognized format.
   *
   * @covers ::loadPatternForBlock
   */
  public function testLoadPatternForBlockWithUnrecognizedFormat(): void {
    $pattern_id = 'invalid_format';
    $block = $this->prophesize(PatternkitBlock::class);
    $block->getPattern()->willReturn($pattern_id);
    $block->id()->willReturn(1);

    // Neither entity storage nor repository should be called.
    $this->entityTypeManager->getStorage(Argument::any())
      ->shouldNotBeCalled();
    $this->patternRepository->getPattern(Argument::any(), Argument::any())
      ->shouldNotBeCalled();

    $this->logger->warning(
      'Unrecognized pattern_id format for block @block_id: @pattern_id',
      Argument::type('array')
    )->shouldBeCalledOnce();

    // Use reflection to access protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('loadPatternForBlock');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $block->reveal());

    $this->assertNull($result);
  }

  /**
   * Test loading pattern when block has no pattern_id.
   *
   * @covers ::loadPatternForBlock
   */
  public function testLoadPatternForBlockWithNoPatternId(): void {
    $block = $this->prophesize(PatternkitBlock::class);
    $block->getPattern()->willReturn(NULL);

    // No services should be called.
    $this->entityTypeManager->getStorage(Argument::any())
      ->shouldNotBeCalled();
    $this->patternRepository->getPattern(Argument::any(), Argument::any())
      ->shouldNotBeCalled();

    // Use reflection to access protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('loadPatternForBlock');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $block->reveal());

    $this->assertNull($result);
  }

  /**
   * Test loading pattern with numeric ID when entity is not found.
   *
   * @covers ::loadPatternForBlock
   */
  public function testLoadPatternForBlockWithNumericIdNotFound(): void {
    $block = $this->prophesize(PatternkitBlock::class);
    $block->getPattern()->willReturn('999');
    $block->id()->willReturn(1);

    $this->entityTypeManager->getStorage('patternkit_pattern')
      ->willReturn($this->patternStorage->reveal())
      ->shouldBeCalledOnce();

    $this->patternStorage->load('999')
      ->willReturn(NULL)
      ->shouldBeCalledOnce();

    // When numeric ID doesn't find an entity, it should return NULL
    // without logging as "unrecognized format" since the format is valid.
    $this->logger->warning(Argument::any(), Argument::any())
      ->shouldNotBeCalled();

    // Use reflection to access protected method.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('loadPatternForBlock');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $block->reveal());

    $this->assertNull($result);
  }

}
