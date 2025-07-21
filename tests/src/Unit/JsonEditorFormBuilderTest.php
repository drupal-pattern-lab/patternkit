<?php

namespace Drupal\Tests\patternkit\Unit;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorManager;
use Drupal\editor\Plugin\EditorPluginInterface;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Form\PatternLibraryJSONForm;
use Drupal\patternkit\JsonEditorFormBuilder;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\Tests\patternkit\Traits\AssertArraySubsetTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Swaggest\JsonSchema\Schema;
use Webmozart\Assert\InvalidArgumentException;

/**
 * Unit tests for the JsonEditorFormBuilder service.
 *
 * @group patternkit
 * @uses \Drupal\Core\Render\BubbleableMetadata
 * @uses \Swaggest\JsonSchema\Schema
 * @uses \Webmozart\Assert\Assert
 * @coversDefaultClass \Drupal\patternkit\JsonEditorFormBuilder
 */
class JsonEditorFormBuilderTest extends UnitTestCase {

  use AssertArraySubsetTrait;
  use ProphecyTrait;

  /**
   * The mocked pattern library plugin manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\PatternLibraryPluginManager>
   */
  protected ObjectProphecy $patternLibraryPluginManager;

  /**
   * The mocked config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ConfigFactoryInterface|MockObject $configFactory;

  /**
   * The mocked entity type manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityTypeManagerInterface>
   */
  protected ObjectProphecy $entityTypeManager;

  /**
   * The mocked state service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\State\StateInterface>
   */
  protected ObjectProphecy $state;

  /**
   * The editor plugin manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\editor\Plugin\EditorManager>
   */
  protected ObjectProphecy $editorPluginManager;

  /**
   * The mocked logger channel.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Psr\Log\LoggerInterface>
   */
  protected ObjectProphecy $logger;

  /**
   * The mocked messenger service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Messenger\MessengerInterface>
   */
  protected ObjectProphecy $messenger;

  /**
   * The mocked string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected TranslationInterface|MockObject $stringTranslation;

  /**
   * The query string service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Asset\AssetQueryStringInterface>
   */
  protected ObjectProphecy $queryString;

  /**
   * The patternkit settings configuration object.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Config\ImmutableConfig>
   */
  protected ObjectProphecy $config;

  /**
   * The entity storage handler for editor entities.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityStorageInterface>
   */
  protected ObjectProphecy $editorStorage;

  /**
   * The form builder instance being tested.
   *
   * @var \Drupal\patternkit\JsonEditorFormBuilder
   */
  protected JsonEditorFormBuilder $builder;

  /**
   * A mocked pattern entity for use in relevant tests.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\patternkit\Entity\PatternInterface>
   */
  protected ObjectProphecy $pattern;

  /**
   * A mocked schema string for relevant tests.
   *
   * @var string
   */
  protected string $schema;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->patternLibraryPluginManager = $this->prophesize(PatternLibraryPluginManager::class);
    $this->configFactory = $this->getConfigFactoryStub();
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->editorStorage = $this->prophesize(EntityStorageInterface::class);
    $this->state = $this->prophesize(StateInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
    $this->messenger = $this->prophesize(MessengerInterface::class);
    $this->stringTranslation = $this->getStringTranslationStub();
    $this->editorPluginManager = $this->prophesize(EditorManager::class);

    $this->builder = new JsonEditorFormBuilder(
      $this->patternLibraryPluginManager->reveal(),
      $this->configFactory,
      $this->entityTypeManager->reveal(),
      $this->state->reveal(),
      $this->messenger->reveal(),
      $this->stringTranslation,
    );
    $this->builder->setLogger($this->logger->reveal());

    // Prepare a mocked schema and pattern for tests.
    $schema_obj = $this->getRandomGenerator()->object();
    $this->schema = json_encode($schema_obj);
    $this->pattern = $this->prophesize(PatternInterface::class);
    $this->pattern->getSchema()->willReturn($this->schema);
  }

  public function testBuildEditorFormWithoutWysiwyg(): void {
    $settings = [
      // Provide a query string to sidestep loading it during this test.
      'ajaxCacheBuster' => 'cache_buster_string',

      // Set theme and icon values for lookup.
      'theme' => 'cygnet',
      'icons' => 'fontawesome3',
    ];

    // Mimic no editor support.
    $this->entityTypeManager->hasHandler('editor', 'storage')
      ->willReturn(FALSE);

    // Build the editor form for testing.
    $form = $this->builder->buildEditorForm($this->pattern->reveal(), $settings);

    // Confirm the basic structure is prepared.
    $this->assertBasicFormStructure($form);

    // Confirm theme and icon attachments are present.
    $this->assertContains('patternkit/cygnet_no_shadow_dom', $form['#attached']['library']);
    $this->assertContains('patternkit/icons.fontawesome3', $form['#attached']['library']);

    // Confirm editor settings with added schema JSON are attached.
    $expected = $settings + ['schemaJson' => $this->schema];
    $this->assertEquals($expected, $form['#attached']['drupalSettings']['patternkitEditor']);
  }

  public function testBuildEditorFormWithoutSchemaData(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('If no pattern is provided, the schema should be assigned in $settings["schemaJson"].');

    // Call the method and trigger the expected exception.
    $this->builder->buildEditorForm();
  }

  public function testBuildEditorFormWithInvalidSchemaData(): void {
    $schema = $this->prophesize(Schema::class);
    $settings = ['schemaJson' => $schema];

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('The schema JSON value assigned to "schemaJson" should be a string.');

    // Call the method and trigger the expected exception.
    $this->builder->buildEditorForm(settings: $settings);
  }

  public function testBuildEditorFormWithoutPattern(): void {
    $settings = [
      // Provide a query string to sidestep loading it during this test.
      'ajaxCacheBuster' => 'cache_buster_string',

      // Set schema data to be used.
      'schemaJson' => $this->schema,
    ];

    // Mimic no editor support.
    $this->entityTypeManager->hasHandler('editor', 'storage')
      ->willReturn(FALSE);

    // Build the editor form for testing.
    $form = $this->builder->buildEditorForm(settings: $settings);

    // Confirm the basic structure is prepared.
    $this->assertBasicFormStructure($form);

    // Confirm editor settings are attached.
    $this->assertEquals($settings, $form['#attached']['drupalSettings']['patternkitEditor']);
  }

  public function testBuildEditorFormWithWysiwyg(): void {
    $settings = [
      // Provide a query string to sidestep loading it during this test.
      'ajaxCacheBuster' => 'cache_buster_string',

      // Set theme and icon values for lookup.
      'theme' => 'cygnet',
      'icons' => 'fontawesome3',
    ];

    // Setup config to be looked up.
    $editor_id = 'ckeditor5';
    $selected_toolbar = 'html';
    $patternkit_config = [
      'patternkit_json_editor_wysiwyg' => $editor_id,
      'patternkit_json_editor_ckeditor_toolbar' => $selected_toolbar,
    ];
    $this->configFactory = $this->getConfigFactoryStub([
      PatternLibraryJSONForm::SETTINGS => $patternkit_config,
    ]);

    // Recreate the form builder to capture config.
    $this->builder = new JsonEditorFormBuilder(
      $this->patternLibraryPluginManager->reveal(),
      $this->configFactory,
      $this->entityTypeManager->reveal(),
      $this->state->reveal(),
      $this->messenger->reveal(),
      $this->stringTranslation,
    );
    $this->builder->setLogger($this->logger->reveal());

    // Configure editor dependencies.
    $this->entityTypeManager->hasHandler('editor', 'storage')
      ->willReturn(TRUE);
    $this->entityTypeManager->getStorage('editor')->willReturn($this->editorStorage->reveal());
    $this->builder->setEditorPluginManager($this->editorPluginManager->reveal());

    // Define attachments to be added for the editor.
    $editor_attachments = [
      'library' => [
        $this->getRandomGenerator()->string(),
      ],
    ];
    $this->editorPluginManager->getAttachments([$selected_toolbar])->willReturn($editor_attachments);

    // Mock the editor instance to be created.
    $editor_entity = $this->prophesize(Editor::class);
    $editor_entity->status()->willReturn(TRUE);
    $this->editorStorage->load($selected_toolbar)->willReturn($editor_entity->reveal());

    // Mock the editor instance to be loaded.
    $editor_plugin = $this->prophesize(EditorPluginInterface::class);
    $this->editorPluginManager->createInstance($editor_id)
      ->willReturn($editor_plugin->reveal());

    // Mock JS settings to be attached for the editor.
    $editor_settings = (array) $this->getRandomGenerator()->object();
    $editor_plugin->getJSSettings($editor_entity->reveal())->willReturn($editor_settings);

    // Build the editor form for testing.
    $form = $this->builder->buildEditorForm($this->pattern->reveal(), $settings);

    // Confirm the basic structure is prepared.
    $this->assertBasicFormStructure($form);

    // Confirm theme and icon attachments are present.
    $this->assertContains('patternkit/cygnet_no_shadow_dom', $form['#attached']['library']);
    $this->assertContains('patternkit/icons.fontawesome3', $form['#attached']['library']);

    // Confirm editor settings are attached.
    // Confirm editor settings with added schema JSON are attached.
    $expected = $settings + ['schemaJson' => $this->schema];
    $this->assertArraySubset($expected, $form['#attached']['drupalSettings']['patternkitEditor']);

    // Confirm CKEditor settings are attached.
    $patternkitCKEditorConfig = $form['#attached']['drupalSettings']['patternkitEditor']['patternkitCKEditorConfig'];
    $this->assertArraySubset($editor_settings, $patternkitCKEditorConfig);
    $this->assertEquals($selected_toolbar, $patternkitCKEditorConfig['selected_toolbar']);
  }

  public function testThemeLoadFailure(): void {
    $settings = [
      // Provide a query string to sidestep loading it during this test.
      'ajaxCacheBuster' => 'cache_buster_string',

      // Set theme and icon values for lookup.
      'theme' => $unknown_theme = 'unknown',
    ];

    // Mimic no editor support.
    $this->entityTypeManager->hasHandler('editor', 'storage')
      ->willReturn(FALSE);

    // Expect theme load failure message to be logged.
    $expected_message = 'Unable to find theme information for selected theme %theme.';
    $this->logger->error($expected_message, ['%theme' => $unknown_theme])
      ->shouldBeCalledOnce();

    // Build the editor form for testing.
    $form = $this->builder->buildEditorForm($this->pattern->reveal(), $settings);

    // Confirm the basic structure is prepared.
    $this->assertBasicFormStructure($form);

    // Confirm other attachments are still included.
    $this->assertContains('patternkit/patternkit.jsoneditor', $form['#attached']['library']);

    // Confirm editor settings with added schema JSON are attached.
    $expected = $settings + ['schemaJson' => $this->schema];
    $this->assertEquals($expected, $form['#attached']['drupalSettings']['patternkitEditor']);
  }

  public function testIconsLoadFailure(): void {
    $settings = [
      // Provide a query string to sidestep loading it during this test.
      'ajaxCacheBuster' => 'cache_buster_string',

      // Set theme and icon values for lookup.
      'icons' => $unknown_icons = 'unknown',
    ];

    // Mimic no editor support.
    $this->entityTypeManager->hasHandler('editor', 'storage')
      ->willReturn(FALSE);

    // Expect theme load failure message to be logged.
    $expected_message = 'Unable to find theme information for selected icons %icons.';
    $this->logger->error($expected_message, ['%icons' => $unknown_icons])
      ->shouldBeCalledOnce();

    // Build the editor form for testing.
    $form = $this->builder->buildEditorForm($this->pattern->reveal(), $settings);

    // Confirm the basic structure is prepared.
    $this->assertBasicFormStructure($form);

    // Confirm other attachments are still included.
    $this->assertContains('patternkit/patternkit.jsoneditor', $form['#attached']['library']);

    // Confirm editor settings with added schema JSON are attached.
    $expected = $settings + ['schemaJson' => $this->schema];
    $this->assertEquals($expected, $form['#attached']['drupalSettings']['patternkitEditor']);
  }

  /**
   * Test behavior when an editor plugin fails to load.
   */
  public function testEditorPluginException(): void {
    $settings = [
      // Provide a query string to sidestep loading it during this test.
      'ajaxCacheBuster' => 'cache_buster_string',
    ];

    // Setup config to be looked up.
    $editor_id = 'ckeditor5';
    $selected_toolbar = 'html';
    $patternkit_config = [
      'patternkit_json_editor_wysiwyg' => $editor_id,
      'patternkit_json_editor_ckeditor_toolbar' => $selected_toolbar,
    ];
    $this->configFactory = $this->getConfigFactoryStub([
      PatternLibraryJSONForm::SETTINGS => $patternkit_config,
    ]);

    // Recreate the form builder to capture config.
    $this->builder = new JsonEditorFormBuilder(
      $this->patternLibraryPluginManager->reveal(),
      $this->configFactory,
      $this->entityTypeManager->reveal(),
      $this->state->reveal(),
      $this->messenger->reveal(),
      $this->stringTranslation,
    );
    $this->builder->setLogger($this->logger->reveal());

    // Enable editor storage loading for early checks.
    $this->entityTypeManager->hasHandler('editor', 'storage')
      ->willReturn(TRUE);
    $this->entityTypeManager->getStorage('editor')->willReturn($this->editorStorage->reveal());
    $this->builder->setEditorPluginManager($this->editorPluginManager->reveal());

    // Configure editor dependencies with an exception.
    $exception_message = 'Failed to load plugin.';
    $exception = new PluginException($exception_message);
    $this->builder->setEditorPluginManager($this->editorPluginManager->reveal());
    $this->editorPluginManager->createInstance($editor_id)
      ->willThrow($exception);

    // Expect the errors to get logged.
    $error_message = "Unable to load plugin for editor <em class=\"placeholder\">$editor_id</em>. <em class=\"placeholder\">$exception_message</em>";
    $translation_test = function (TranslatableMarkup $translation) use ($error_message): bool {
      return (string) $translation == $error_message;
    };
    $this->logger->error(Argument::that($translation_test))->shouldBeCalledOnce();
    $this->messenger->addError(Argument::that($translation_test))->shouldBeCalledOnce();

    // Build the editor form for testing.
    $form = $this->builder->buildEditorForm($this->pattern->reveal(), $settings);

    // Confirm the form should still be built as expected otherwise.
    $this->assertBasicFormStructure($form);

    // Confirm editor settings with added schema JSON are attached.
    $expected = $settings + ['schemaJson' => $this->schema];
    $this->assertEquals($expected, $form['#attached']['drupalSettings']['patternkitEditor']);
  }

  /**
   * Test common form structure elements were loaded correctly.
   *
   * @param array $form
   *   The assembled form array to be tested.
   *
   * @throws \InvalidArgumentException
   * @throws \PHPUnit\Framework\ExpectationFailedException
   */
  protected function assertBasicFormStructure(array $form): void {
    // Confirm the target wrapper is prepared.
    $this->assertArraySubset([
      '#type' => 'html_tag',
      '#tag' => 'div',
    ], $form);
    $this->assertEquals('patternkit-editor-target', $form['#attributes']['id']);

    // Confirm the holder div is defined.
    $this->assertArrayHasKey('holder', $form);
    $this->assertArraySubset([
      '#type' => 'html_tag',
      '#tag' => 'div',
    ], $form['holder']);
    $this->assertEquals('editor_holder', $form['holder']['#attributes']['id']);
  }

}
