<?php

declare(strict_types=1);

namespace Drupal\Tests\patternkit\Unit\Hook;

use Drupal\block\BlockInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Utility\Token;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternkitBlock as PatternkitBlockEntity;
use Drupal\patternkit\Hook\PatternkitBlockEntityHooks;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Drupal\patternkit\Utility\PatternValidationHelper;
use Drupal\patternkit\Validation\ValidityStampPendingStore;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

/**
 * Tests validity stamp writes inside saveBlockComponent().
 *
 * Verifies that PatternkitBlockEntityHooks::saveBlockComponent() populates the
 * pending store (for SectionComponent paths) or writes a stamp inline (for
 * BlockInterface paths), recording the correct is_valid, has_tokens,
 * component_uuid, and stamp_source values.
 *
 * @group patternkit
 * @group patternkit_validation
 * @coversDefaultClass \Drupal\patternkit\Hook\PatternkitBlockEntityHooks
 */
class ValidityStampSaveBlockComponentTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Block storage prophecy (must support loadRevision()).
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\RevisionableStorageInterface>
   */
  protected ObjectProphecy $blockStorage;

  /**
   * Pattern storage prophecy (must support loadRevision()).
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\RevisionableStorageInterface>
   */
  protected ObjectProphecy $patternStorage;

  /**
   * Module handler prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Extension\ModuleHandlerInterface>
   */
  protected ObjectProphecy $moduleHandler;

  /**
   * Section storage manager prophecy.
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
   * Patternkit settings config prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Config\ImmutableConfig>
   */
  protected ObjectProphecy $config;

  /**
   * Config factory prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Config\ConfigFactoryInterface>
   */
  protected ObjectProphecy $configFactory;

  /**
   * Real pending store instance shared with the subject under test.
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

    $this->config = $this->prophesize(ImmutableConfig::class);
    $this->config->get('render_validation_on_save')->willReturn(FALSE);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->configFactory->get('patternkit.settings')->willReturn($this->config->reveal());

    $this->pendingStore = new ValidityStampPendingStore();
  }

  /**
   * Builds a hooks instance with all dependencies injected.
   *
   * @return \Drupal\patternkit\Hook\PatternkitBlockEntityHooks
   *   The hooks instance under test.
   */
  protected function getSubject(): PatternkitBlockEntityHooks {
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
   * Flushes the pending store using a stub entity.
   *
   * @return array<string, array<string, mixed>>
   *   All pending entries keyed by composite key.
   */
  protected function flushPending(): array {
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    return $this->pendingStore->flushForEntity($entity);
  }

  /**
   * Builds a PatternkitBlockEntity prophecy with common stubs.
   *
   * @param int $entityId
   *   The block entity ID.
   * @param string $langcode
   *   The language code.
   * @param array<string, mixed> $data
   *   The block data to return from getData() and get('data')->value.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternkitBlock>
   *   The entity prophecy.
   */
  protected function makeBlockEntity(int $entityId, string $langcode = 'en', array $data = []): ObjectProphecy {
    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn($langcode);

    $dataJson = json_encode($data, JSON_THROW_ON_ERROR);
    $dataField = new \stdClass();
    $dataField->value = $dataJson;

    $blockEntity = $this->prophesize(PatternkitBlockEntity::class);
    $blockEntity->id()->willReturn($entityId);
    $blockEntity->language()->willReturn($language->reveal());
    $blockEntity->isDefaultRevision()->willReturn(TRUE);
    $blockEntity->getData()->willReturn($data);
    $blockEntity->get('data')->willReturn($dataField);

    return $blockEntity;
  }

  /**
   * Builds a SectionComponent mock that returns a PatternkitBlock plugin.
   *
   * @param array<string, mixed> $configuration
   *   Plugin configuration returned by getConfiguration().
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\layout_builder\SectionComponent>
   *   The component prophecy.
   */
  protected function makeComponent(array $configuration): ObjectProphecy {
    $patternEntity = $this->prophesize(PatternInterface::class);
    $plugin = $this->prophesize(PatternkitBlock::class);
    $plugin->cachePatternEntity()->willReturn($patternEntity->reveal());
    $plugin->saveBlockContent(Argument::type('bool'), Argument::type('bool'));

    $plugin->getConfiguration()->willReturn($configuration);

    $component = $this->prophesize(SectionComponent::class);
    $component->getPlugin()->willReturn($plugin->reveal());
    $component->setConfiguration(Argument::any())->willReturn(NULL);

    return $component;
  }

  /**
   * Pending store is populated with correct fields after a successful save.
   *
   * For SectionComponent paths, stamp writes are deferred to the post-save
   * hook boundary. saveBlockComponent() must populate the pending store rather
   * than calling writeStamp() directly.
   *
   * @covers ::saveBlockComponent
   */
  public function testStampWrittenAfterSuccessfulBlockSave(): void {
    $blockRevisionId = 42;
    $patternRevisionId = 7;
    $entityId = 10;
    $langcode = 'en';
    $componentUuid = 'uuid-abc-123';

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn($componentUuid);

    $blockEntity = $this->makeBlockEntity($entityId, $langcode);
    $this->blockStorage->loadRevision($blockRevisionId)
      ->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)
      ->willReturn($patternEntity->reveal());

    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())
      ->willReturn(TRUE);
    $this->validationHelper->hasExceptions()->willReturn(FALSE);

    $this->token->scan(Argument::any())->willReturn([]);

    // SectionComponent path: stamp is deferred to pending store, not written
    // directly.
    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $pending = $this->flushPending();
    $this->assertCount(1, $pending);
    $entry = reset($pending);
    $this->assertTrue($entry['isValid']);
    $this->assertFalse($entry['hasTokens']);
    $this->assertSame($componentUuid, $entry['componentUuid']);
    $this->assertSame($entityId, $entry['blockEntityId']);
    $this->assertSame($langcode, $entry['langcode']);
    $this->assertSame($blockRevisionId, $entry['blockRevisionId']);
    $this->assertSame($patternRevisionId, $entry['patternRevisionId']);
    $this->assertSame(ValidityStampWriterInterface::STAMP_SOURCE_SAVE, $entry['stampSource']);
  }

  /**
   * Stamp has NULL component_uuid when saving a config BlockInterface.
   *
   * Config block saves pass a BlockInterface rather than a SectionComponent.
   * The stamp is written inline (not deferred) and component_uuid is NULL
   * because Layout Builder context is absent.
   *
   * @covers ::saveBlockComponent
   */
  public function testStampHasNullUuidForConfigBlock(): void {
    $blockRevisionId = 55;
    $patternRevisionId = 8;
    $entityId = 11;

    $plugin = $this->prophesize(PatternkitBlock::class);
    $patternEntity = $this->prophesize(PatternInterface::class);
    $plugin->cachePatternEntity()->willReturn($patternEntity->reveal());
    $plugin->saveBlockContent(Argument::type('bool'), Argument::type('bool'));
    $plugin->getConfiguration()->willReturn([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    // Use a BlockInterface component (config block path).
    $block = $this->prophesize(BlockInterface::class);
    $block->getPlugin()->willReturn($plugin->reveal());
    $block->set('settings', Argument::any())->willReturn(NULL);

    $blockEntity = $this->makeBlockEntity($entityId);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(TRUE);
    $this->validationHelper->hasExceptions()->willReturn(FALSE);
    $this->token->scan(Argument::any())->willReturn([]);

    // BlockInterface path: stamp is written inline, not deferred.
    $this->stampWriter->writeStamp(
      $entityId,
      'en',
      $blockRevisionId,
      $patternRevisionId,
      TRUE,
      FALSE,
      ValidityStampWriterInterface::STAMP_SOURCE_SAVE,
      NULL,
    )->shouldBeCalledOnce();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $block->reveal(), FALSE, FALSE);
  }

  /**
   * Stamp write failure on the BlockInterface path does not abort the save.
   *
   * Enforcement is ON and content is valid, so enforcement passes. The inline
   * writeValidityStamp() call (BlockInterface path) then throws. The exception
   * must be caught and logged — the save must complete. Verifies the
   * log-and-continue policy documented in writeValidityStamp().
   *
   * @covers ::saveBlockComponent
   */
  public function testStampWriteFailureDoesNotAbortSaveWhenEnforcementEnabled(): void {
    $this->config->get('render_validation_on_save')->willReturn(TRUE);
    $blockRevisionId = 55;
    $patternRevisionId = 8;
    $entityId = 11;

    $plugin = $this->prophesize(PatternkitBlock::class);
    $patternEntity = $this->prophesize(PatternInterface::class);
    $plugin->cachePatternEntity()->willReturn($patternEntity->reveal());
    $plugin->saveBlockContent(Argument::type('bool'), Argument::type('bool'));
    $plugin->getConfiguration()->willReturn([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);

    $block = $this->prophesize(BlockInterface::class);
    $block->getPlugin()->willReturn($plugin->reveal());
    $block->set('settings', Argument::any())->willReturn(NULL);

    $blockEntity = $this->makeBlockEntity($entityId);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(TRUE);
    $this->validationHelper->hasExceptions()->willReturn(FALSE);
    $this->token->scan(Argument::any())->willReturn([]);

    // Stamp write throws — save must still complete.
    $this->stampWriter->writeStamp(Argument::cetera())
      ->willThrow(new \RuntimeException('DB unavailable'));

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);

    // No exception expected — stamp failure must not propagate.
    $method->invoke($subject, $block->reveal(), FALSE, FALSE);
    $this->addToAssertionCount(1);
  }

  /**
   * Pending store records is_valid=TRUE when schema validation passes.
   *
   * @covers ::saveBlockComponent
   */
  public function testStampIsValidWhenValidationPasses(): void {
    $blockRevisionId = 10;
    $patternRevisionId = 2;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn('uuid-valid');

    $blockEntity = $this->makeBlockEntity(5);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(TRUE);
    $this->validationHelper->hasExceptions()->willReturn(FALSE);
    $this->token->scan(Argument::any())->willReturn([]);

    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $pending = $this->flushPending();
    $this->assertCount(1, $pending);
    $entry = reset($pending);
    $this->assertTrue($entry['isValid']);
    $this->assertSame('uuid-valid', $entry['componentUuid']);
  }

  /**
   * Pending store records is_valid=FALSE when validation fails and toggle off.
   *
   * @covers ::saveBlockComponent
   */
  public function testStampIsInvalidWhenValidationFails(): void {
    $blockRevisionId = 10;
    $patternRevisionId = 2;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn('uuid-invalid');

    $blockEntity = $this->makeBlockEntity(5);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(FALSE);
    $this->validationHelper->hasExceptions()->willReturn(TRUE);
    // getExceptions() is called by the debug log path when isValid is FALSE.
    $this->validationHelper->getExceptions()->willReturn([]);
    $this->token->scan(Argument::any())->willReturn([]);

    // Stamp is deferred to pending store even for invalid content when
    // enforcement is disabled.
    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $pending = $this->flushPending();
    $this->assertCount(1, $pending);
    $entry = reset($pending);
    $this->assertFalse($entry['isValid']);
    $this->assertSame('uuid-invalid', $entry['componentUuid']);
  }

  /**
   * Invalid content throws when enforcement is enabled; pending store empty.
   *
   * With validate-before-enforce ordering, enforcement throws before the
   * pending store is populated. A blocked save produces no pending entry —
   * blocked attempts must not create misleading effective-state records.
   *
   * @covers ::saveBlockComponent
   */
  public function testInvalidContentThrowsWhenEnforcementEnabled(): void {
    $this->config->get('render_validation_on_save')->willReturn(TRUE);
    $blockRevisionId = 10;
    $patternRevisionId = 2;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn('uuid-invalid-enforce-on');

    $blockEntity = $this->makeBlockEntity(5);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(FALSE);
    $this->validationHelper->hasExceptions()->willReturn(TRUE);
    $this->validationHelper->getExceptions()->willReturn([]);

    // Enforcement throws before the pending store is populated — blocked saves
    // produce no pending entry and no direct stamp write.
    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessageMatches('/failed validation/i');
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);
  }

  /**
   * Valid content is not blocked when enforcement is enabled.
   *
   * @covers ::saveBlockComponent
   */
  public function testValidContentNotBlockedWhenEnforcementEnabled(): void {
    $this->config->get('render_validation_on_save')->willReturn(TRUE);
    $blockRevisionId = 42;
    $patternRevisionId = 7;
    $entityId = 10;
    $langcode = 'en';
    $componentUuid = 'uuid-valid-enforce-on';

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn($componentUuid);

    $blockEntity = $this->makeBlockEntity($entityId, $langcode);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(TRUE);
    $this->validationHelper->hasExceptions()->willReturn(FALSE);
    $this->token->scan(Argument::any())->willReturn([]);

    // SectionComponent path: deferred to pending store.
    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $pending = $this->flushPending();
    $this->assertCount(1, $pending);
    $entry = reset($pending);
    $this->assertTrue($entry['isValid']);
    $this->assertSame($componentUuid, $entry['componentUuid']);
  }

  /**
   * Invalid content is not blocked when enforcement is explicitly disabled.
   *
   * @covers ::saveBlockComponent
   */
  public function testInvalidContentNotBlockedWhenEnforcementDisabled(): void {
    $this->config->get('render_validation_on_save')->willReturn(FALSE);
    $blockRevisionId = 10;
    $patternRevisionId = 2;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn('uuid-invalid-enforce-off');

    $blockEntity = $this->makeBlockEntity(5);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(FALSE);
    $this->validationHelper->hasExceptions()->willReturn(TRUE);
    $this->validationHelper->getExceptions()->willReturn([]);
    $this->token->scan(Argument::any())->willReturn([]);

    // SectionComponent path: deferred to pending store even for invalid
    // content.
    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $pending = $this->flushPending();
    $this->assertCount(1, $pending);
    $entry = reset($pending);
    $this->assertFalse($entry['isValid']);
    $this->assertSame('uuid-invalid-enforce-off', $entry['componentUuid']);
  }

  /**
   * Enforcement fires before pending store population for invalid content.
   *
   * Validates that enforcement is resolved independently of stamp persistence:
   * enforceValidationPolicy() throws before the pending store is populated, so
   * stamp infrastructure is never exercised on a blocked save.
   *
   * @covers ::saveBlockComponent
   */
  public function testEnforcementFiresBeforeStampWriteForInvalidContent(): void {
    $this->config->get('render_validation_on_save')->willReturn(TRUE);
    $blockRevisionId = 10;
    $patternRevisionId = 2;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn('uuid-enforce-before-stamp');

    $blockEntity = $this->makeBlockEntity(5);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(FALSE);
    $this->validationHelper->hasExceptions()->willReturn(TRUE);
    $this->validationHelper->getExceptions()->willReturn([]);

    // Enforcement throws before the pending store is populated — stamp writer
    // must not be called and the store must remain empty.
    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessageMatches('/failed validation/i');
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);
  }

  /**
   * Valid content is not blocked even when stamp write fails during flush.
   *
   * For SectionComponent paths, stamp writes happen at post-save flush time
   * via writeValidityStampFromEntry(). A flush-time failure must be caught
   * and logged without affecting the save result.
   *
   * @covers ::writeValidityStampFromEntry
   */
  public function testValidContentNotBlockedWhenStampWriteThrows(): void {
    $this->config->get('render_validation_on_save')->willReturn(TRUE);
    $blockRevisionId = 10;
    $patternRevisionId = 2;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn('uuid-stamp-throw-valid');

    $blockEntity = $this->makeBlockEntity(5);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(TRUE);
    $this->validationHelper->hasExceptions()->willReturn(FALSE);
    $this->token->scan(Argument::any())->willReturn([]);

    // Stamp write throws at flush time — must be caught and not propagate.
    $this->stampWriter->writeStamp(Argument::cetera())
      ->willThrow(new \RuntimeException('DB connection lost'));

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);

    // saveBlockComponent() must not throw — only populates the pending store.
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    // Simulate the post-save flush; the exception must be swallowed.
    $flushMethod = new \ReflectionMethod($subject, 'flushPendingStamps');
    $flushMethod->setAccessible(TRUE);
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $flushMethod->invoke($subject, $entity);

    $this->addToAssertionCount(1);
  }

  /**
   * Pending store records has_tokens=TRUE when token patterns are in data.
   *
   * @covers ::saveBlockComponent
   */
  public function testStampHasTokensWhenTokensFound(): void {
    $blockRevisionId = 10;
    $patternRevisionId = 2;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn('uuid-tokens');

    $blockEntity = $this->makeBlockEntity(5, data: ['field' => '[node:title]']);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(TRUE);
    $this->validationHelper->hasExceptions()->willReturn(FALSE);
    // Token::scan() must receive the raw JSON string from the entity.
    $expectedJson = '{"field":"[node:title]"}';
    $this->token->scan($expectedJson)->willReturn(['node' => ['title' => '[node:title]']]);

    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $pending = $this->flushPending();
    $this->assertCount(1, $pending);
    $entry = reset($pending);
    $this->assertTrue($entry['hasTokens']);
    $this->assertSame('uuid-tokens', $entry['componentUuid']);
  }

  /**
   * No pending entry when post-save configuration has no block revision ID.
   *
   * When patternkit_block_rid is absent, saveBlockContent() was a no-op and
   * the block entity was not persisted. No entry should be added to the store.
   *
   * @covers ::saveBlockComponent
   */
  public function testStampSkippedWhenNoBlockRevisionId(): void {
    $component = $this->makeComponent([
      // No patternkit_block_rid — saveBlockContent was a no-op.
      'pattern' => 5,
    ]);

    $this->stampWriter->writeStamp(Argument::any())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $this->assertCount(0, $this->flushPending());
  }

  /**
   * No pending entry when block revision ID is zero.
   *
   * A zero revision ID indicates an unsaved block entity; treated the same as
   * an absent patternkit_block_rid.
   *
   * @covers ::saveBlockComponent
   */
  public function testStampSkippedWhenBlockRevisionIdIsZero(): void {
    $component = $this->makeComponent([
      'patternkit_block_rid' => 0,
      'pattern' => 5,
    ]);

    $this->stampWriter->writeStamp(Argument::any())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $this->assertCount(0, $this->flushPending());
  }

  /**
   * No pending entry when loadRevision() returns NULL for a positive RID.
   *
   * A positive patternkit_block_rid that does not resolve to a loaded entity
   * (e.g. a revision deleted out-of-band) must skip the stamp write silently.
   *
   * @covers ::saveBlockComponent
   */
  public function testStampSkippedWhenBlockEntityNotFound(): void {
    $blockRevisionId = 99;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => 5,
    ]);

    // loadRevision() returns NULL — entity not found.
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn(NULL);

    $this->stampWriter->writeStamp(Argument::any())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $this->assertCount(0, $this->flushPending());
  }

  /**
   * No pending entry when block entity is a non-default (draft) revision.
   *
   * Content-moderation draft revisions must not be stamped — only the
   * default (published) revision represents authoritative save-time state.
   *
   * @covers ::saveBlockComponent
   */
  public function testStampSkippedForNonDefaultRevision(): void {
    $blockRevisionId = 55;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => 5,
    ]);

    // Non-default revision — draft or pending moderation.
    $blockEntity = $this->makeBlockEntity(20);
    $blockEntity->isDefaultRevision()->willReturn(FALSE);

    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $this->stampWriter->writeStamp(Argument::any())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $this->assertCount(0, $this->flushPending());
  }

  /**
   * No stamp or enforcement when skipStamps=TRUE.
   *
   * When handlePreSaveForLayouts() detects a draft parent entity, it passes
   * $skipStamps=TRUE. The entire validation/enforcement/stamp block must be
   * skipped: the pending store stays empty and writeStamp() is never called.
   * This test exercises the actual if(!$skipStamps) guard inside the method.
   *
   * @covers ::saveBlockComponent
   */
  public function testNoStampOrEnforcementWhenSkipStampsIsTrue(): void {
    $this->config->get('render_validation_on_save')->willReturn(TRUE);

    $component = $this->makeComponent([
      'patternkit_block_rid' => 42,
      'pattern' => 7,
    ]);
    $component->getUuid()->willReturn('uuid-skip-stamps');

    // The guard fires before resolveValidationState() — no storage lookups
    // should occur.
    $this->blockStorage->loadRevision(Argument::any())->shouldNotBeCalled();
    $this->patternStorage->loadRevision(Argument::any())->shouldNotBeCalled();
    $this->validationHelper->validatePattern(Argument::cetera())->shouldNotBeCalled();
    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    // Pass skipStamps=TRUE as the fourth argument.
    $method->invoke($subject, $component->reveal(), FALSE, FALSE, TRUE);

    $pending = $this->flushPending();
    $this->assertCount(0, $pending, 'Pending store must be empty when skipStamps=TRUE.');
  }

  /**
   * ShouldSkipStamps returns TRUE when entity is unpublished (initial Draft).
   *
   * When Content Moderation saves a new node in Draft state,
   * isDefaultRevision() returns TRUE so the isDraftRevision arm does not fire.
   * The isUnpublished arm must catch this case.
   *
   * @covers ::shouldSkipStamps
   */
  public function testShouldSkipStampsReturnsTrueWhenEntityIsUnpublished(): void {
    $entity = $this->prophesize(EntityInterface::class)
      ->willImplement(RevisionableInterface::class)
      ->willImplement(EntityPublishedInterface::class);
    $entity->isDefaultRevision()->willReturn(TRUE);
    $entity->isPublished()->willReturn(FALSE);

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'shouldSkipStamps');
    $method->setAccessible(TRUE);

    $this->assertTrue(
      $method->invoke($subject, $entity->reveal()),
      'shouldSkipStamps must return TRUE for an unpublished entity with default revision.',
    );
  }

  /**
   * ShouldSkipStamps returns FALSE for a published default-revision entity.
   *
   * This is the normal published-node save path — stamps must be written.
   *
   * @covers ::shouldSkipStamps
   */
  public function testShouldSkipStampsReturnsFalseWhenEntityIsPublished(): void {
    $entity = $this->prophesize(EntityInterface::class)
      ->willImplement(RevisionableInterface::class)
      ->willImplement(EntityPublishedInterface::class);
    $entity->isDefaultRevision()->willReturn(TRUE);
    $entity->isPublished()->willReturn(TRUE);

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'shouldSkipStamps');
    $method->setAccessible(TRUE);

    $this->assertFalse(
      $method->invoke($subject, $entity->reveal()),
      'shouldSkipStamps must return FALSE for a published default-revision entity.',
    );
  }

  /**
   * ShouldSkipStamps returns TRUE for a non-default (draft) revision.
   *
   * Already-published nodes creating a new draft revision:
   * isDefaultRevision() returns FALSE so isDraftRevision fires. isPublished()
   * is stubbed TRUE to isolate the isDraftRevision arm — the result must be
   * TRUE even though isUnpublished evaluates to FALSE.
   *
   * @covers ::shouldSkipStamps
   */
  public function testShouldSkipStampsReturnsTrueForNonDefaultRevision(): void {
    $entity = $this->prophesize(EntityInterface::class)
      ->willImplement(RevisionableInterface::class)
      ->willImplement(EntityPublishedInterface::class);
    $entity->isDefaultRevision()->willReturn(FALSE);
    // Published state is TRUE — isUnpublished arm evaluates to FALSE.
    // Only the isDraftRevision arm drives the TRUE result.
    $entity->isPublished()->willReturn(TRUE);

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'shouldSkipStamps');
    $method->setAccessible(TRUE);

    $this->assertTrue(
      $method->invoke($subject, $entity->reveal()),
      'shouldSkipStamps must return TRUE for a non-default revision even when entity is published.',
    );
  }

  /**
   * ShouldSkipStamps returns FALSE for non-publishable default-revision entity.
   *
   * Block_content is revisionable but not publishable — the isUnpublished arm
   * must not fire for it, so stamps are written normally.
   *
   * @covers ::shouldSkipStamps
   */
  public function testShouldSkipStampsReturnsFalseForNonPublishableDefaultRevision(): void {
    // Entity implements RevisionableInterface but NOT EntityPublishedInterface.
    $entity = $this->prophesize(EntityInterface::class)
      ->willImplement(RevisionableInterface::class);
    $entity->isDefaultRevision()->willReturn(TRUE);

    $subject = $this->getSubject();
    $method = new \ReflectionMethod($subject, 'shouldSkipStamps');
    $method->setAccessible(TRUE);

    $this->assertFalse(
      $method->invoke($subject, $entity->reveal()),
      'shouldSkipStamps must return FALSE for a revisionable but non-publishable default-revision entity.',
    );
  }

  /**
   * Debug log is emitted with exception detail when validation fails.
   *
   * When isBlockValid() returns FALSE and a logger is injected, the debug
   * log must be called with the SchemaValidationException messages so
   * developers can diagnose is_valid=0 stamps before dashboard tooling ships.
   *
   * @covers ::saveBlockComponent
   */
  public function testDebugLogEmittedOnValidationFailure(): void {
    $blockRevisionId = 10;
    $patternRevisionId = 2;

    $component = $this->makeComponent([
      'patternkit_block_rid' => $blockRevisionId,
      'pattern' => $patternRevisionId,
    ]);
    $component->getUuid()->willReturn('uuid-debug');

    $blockEntity = $this->makeBlockEntity(5);
    $this->blockStorage->loadRevision($blockRevisionId)->willReturn($blockEntity->reveal());

    $patternEntity = $this->prophesize(PatternInterface::class);
    $this->patternStorage->loadRevision($patternRevisionId)->willReturn($patternEntity->reveal());
    $this->validationHelper->clearExceptions();
    $this->validationHelper->validatePattern($patternEntity->reveal(), Argument::any())->willReturn(FALSE);
    $this->validationHelper->hasExceptions()->willReturn(TRUE);

    // getExceptions() must return exception objects whose getMessage() is used
    // to build the debug log message.
    $exception = new \RuntimeException('Type mismatch: expected string');
    $this->validationHelper->getExceptions()->willReturn([$exception]);
    $this->token->scan(Argument::any())->willReturn([]);

    // SectionComponent path: deferred to pending store; writeStamp() not called
    // inline.
    $this->stampWriter->writeStamp(Argument::cetera())->shouldNotBeCalled();

    // Logger must be called with a debug message containing the exception text.
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->debug(
      Argument::containingString('is_valid=0'),
      Argument::that(fn($ctx) => str_contains($ctx['@messages'] ?? '', 'Type mismatch')),
    )->shouldBeCalledOnce();

    $subject = $this->getSubject();
    $subject->setLogger($logger->reveal());

    $method = new \ReflectionMethod($subject, 'saveBlockComponent');
    $method->setAccessible(TRUE);
    $method->invoke($subject, $component->reveal(), FALSE, FALSE);

    $pending = $this->flushPending();
    $this->assertCount(1, $pending);
    $entry = reset($pending);
    $this->assertFalse($entry['isValid']);
  }

}
