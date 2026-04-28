<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\Pattern as EntityPattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternFieldProcessorPluginManager;
use Drupal\patternkit\Plugin\PatternFieldProcessor\TokenProcessor;
use Drupal\patternkit\Plugin\PatternFieldProcessor\WysiwygFieldProcessor;
use Drupal\Tests\patternkit\Traits\MetadataTestTrait;

/**
 * Test pattern field processor plugin manager functionality.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Schema\ContextBuilder
 * @covers \Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection
 * @covers \Drupal\patternkit\Schema\DataPreProcessorFactory
 * @covers \Drupal\patternkit\Element\Pattern
 * @covers \Drupal\patternkit\Entity\Pattern
 * @covers \Drupal\patternkit\Attribute\PatternFieldProcessor
 * @covers \Drupal\patternkit\PatternFieldProcessorPluginManager
 * @covers \Drupal\patternkit\Schema\PatternkitRefProvider
 * @covers \Drupal\patternkit\Schema\SchemaFactory
 * @covers \Drupal\patternkit\Schema\SchemaHelper
 * @covers \Drupal\patternkit\Schema\SchemaIterator
 * @covers \Drupal\patternkit\Schema\SchemaWalker
 * @covers \Drupal\patternkit\Schema\SchemaWalkerFactory
 * @covers \Drupal\patternkit\Plugin\PatternFieldProcessor\TokenProcessor
 * @covers \Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor
 * @covers \Drupal\patternkit\Plugin\PatternFieldProcessor\WysiwygFieldProcessor
 * @uses \Drupal\patternkit\Attribute\PatternLibrary
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor
 * @uses \Drupal\patternkit\Asset\PatternLibraryParser\JSONPatternLibraryParser
 * @uses \Drupal\patternkit\Element\JsonEditorForm
 * @uses \Drupal\patternkit\JsonEditorFormBuilder
 * @uses \Drupal\patternkit\Asset\LibraryNamespaceResolver
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor
 * @uses \Drupal\patternkit\Asset\PatternDependencyResolver
 * @uses \Drupal\patternkit\Asset\PatternDiscovery
 * @uses \Drupal\patternkit\Asset\PatternDiscoveryLoader
 * @uses \Drupal\patternkit\Element\PatternError
 * @uses \Drupal\patternkit\PatternLibrary
 * @uses \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryJSON
 * @uses \Drupal\patternkit\Loader\PatternLibraryLoader
 * @uses \Drupal\patternkit\Asset\PatternLibraryParserBase
 * @uses \Drupal\patternkit\PatternLibraryPluginDefault
 * @uses \Drupal\patternkit\PatternLibraryPluginManager
 * @uses \Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig
 * @uses \Drupal\patternkit\PatternRepository
 * @uses \Drupal\patternkit\Entity\PatternRouteProvider
 * @uses \Drupal\patternkit\PatternkitEnvironment
 * @uses \Drupal\patternkit\Routing\PatternkitTranslateBlockFormRouteSubscriber
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor
 * @uses \Drupal\patternkit\Schema\SchemaRefHandler
 * @uses \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver
 * @uses \Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser
 * @uses \Drupal\patternkit\Schema\UnresolvedSchema
 */
class PatternFieldProcessorPluginManagerTest extends PatternkitKernelTestBase {

  use MetadataTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'system',
    'node',
    'user',
    'filter',
  ];

  /**
   * An instance of the plugin manager for testing.
   *
   * @var \Drupal\patternkit\PatternFieldProcessorPluginManager
   */
  protected PatternFieldProcessorPluginManager $pluginManager;

  /**
   * The pattern discovery service.
   *
   * @var \Drupal\patternkit\Asset\PatternDiscoveryInterface
   */
  protected PatternDiscoveryInterface $patternDiscovery;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Mock required services here.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->pluginManager = $this->container->get('plugin.manager.pattern_field_processor');
    $this->patternDiscovery = $this->container->get('patternkit.pattern.discovery');
    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Test that defined processor plugins are discovered successfully.
   */
  public function testPluginsAreDiscovered() {
    $this->assertNotNull($this->pluginManager);
    $this->assertInstanceOf(PatternFieldProcessorPluginManager::class, $this->pluginManager);

    $plugin_definitions = $this->pluginManager->getDefinitions();
    $this->assertCount(2, $plugin_definitions);

    $wysiwyg_definition = $this->pluginManager->getDefinition('wysiwyg');
    $wysiwyg_plugin = $this->pluginManager->createInstance('wysiwyg', $wysiwyg_definition);
    $this->assertInstanceOf(WysiwygFieldProcessor::class, $wysiwyg_plugin);

    $token_definition = $this->pluginManager->getDefinition('token');
    $token_plugin = $this->pluginManager->createInstance('token', $token_definition);
    $this->assertInstanceOf(TokenProcessor::class, $token_plugin);
  }

  /**
   * Test basic rendering of a pattern.
   */
  public function testBasicPatternRender() {
    $id = '@patternkit/atoms/example/src/example';
    $example_pattern = $this->loadPattern($id);
    $this->assertInstanceOf(PatternInterface::class, $example_pattern);

    // Prepare a render element for testing.
    $text = $this->getRandomGenerator()->sentences(3);
    $formatted_text = $this->getRandomGenerator()->paragraphs(1);
    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
      '#config' => [
        'text' => $text,
        'formatted_text' => $formatted_text,
      ],
    ];

    $output = $this->renderer->renderRoot($element);

    $this->assertStringContainsString('Sample twig template', $output);
    $this->assertStringContainsString($text, $output);
    $this->assertStringContainsString($formatted_text, $output);

    $this->assertBubbleableMetadata($element, [
      'tags' => ['pattern:' . $id],
    ]);
  }

  /**
   * Test rendering of a pattern with references.
   */
  public function testRefPatternRender() {
    $id = '@patternkit/atoms/example_ref/src/example_ref';
    $example_pattern = $this->loadPattern($id);
    $this->assertInstanceOf(PatternInterface::class, $example_pattern);

    // Prepare a render element for testing.
    $text = $this->getRandomGenerator()->sentences(3);
    $nested_text = $this->getRandomGenerator()->sentences(3);
    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
      '#config' => [
        'text' => $text,
        'nested_reference' => [
          'text' => $nested_text,
        ],
      ],
    ];

    $output = $this->renderer->renderRoot($element);

    $this->assertStringContainsString('Example twig template with include and reference.', $output);
    $this->assertStringContainsString('Sample twig template', $output);
    $this->assertStringContainsString($text, $output);

    $this->assertBubbleableMetadata($element, [
      'tags' => ['pattern:' . $id],
    ]);
  }

  /**
   * Test token replacement processing.
   */
  public function testTokenReplacementProcessor() {
    // Load our pattern instance.
    $id = '@patternkit/atoms/example_ref/src/example_ref';
    $example_pattern = $this->loadPattern($id);
    $this->assertInstanceOf(PatternInterface::class, $example_pattern);

    // Prepare test values for identification in rendered output.
    $title = $this->getRandomGenerator()->sentences(3);
    $title_token = '[node:title]';
    $nested_text = $this->getRandomGenerator()->sentences(3);
    $body_text = 'Node URL: [node:url]';
    $expected_body_text = 'Node URL: http://localhost/node/1';

    // Prepare the node to use for token replacement context.
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
    ]);
    $node->save();

    // Prepare pattern config values.
    $config = [
      'text' => $title_token,
      'nested_reference' => [
        'text' => $nested_text,
        'formatted_text' => $body_text,
      ],
    ];

    // Assemble the pattern element for rendering.
    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
      '#config' => $config,
      '#context' => [
        'node' => $node,
      ],
    ];

    $output = $this->renderer->renderRoot($element);

    // Confirm raw template content is included.
    $this->assertStringContainsString('Example twig template with include and reference.
', $output);
    $this->assertStringContainsString('Sample twig template', $output);

    // Confirm unprocessed tokens are not visible.
    $this->assertStringNotContainsString($title_token, $output);
    $this->assertStringNotContainsString('[node:url]', $output);

    // Confirm raw config values are included as expected.
    $this->assertStringContainsString($nested_text, $output, 'Nested text was not found in rendered output.');

    // Confirm token replacement text is included as expected.
    $this->assertStringContainsString($title, $output, 'Title text was not replaced into token placement.');
    $this->assertStringContainsString($expected_body_text, $output, 'Body content was not replaced with tokenized values.');

    $this->assertBubbleableMetadata($element, [
      'tags' => array_merge(
        ['pattern:' . $id],
        $node->getCacheTags(),
      ),
    ]);
  }

  /**
   * Test token replacement processing handling for invalid tokens.
   */
  public function testTokenReplacementProcessorWithInvalidTokens() {
    // Load our pattern instance.
    $id = '@patternkit/atoms/example/src/example';
    $example_pattern = $this->loadPattern($id);
    $this->assertInstanceOf(PatternInterface::class, $example_pattern);

    // Prepare test values for identification in rendered output.
    $title = $this->getRandomGenerator()->sentences(3);
    $title_token = '[node:title]';
    $optional_field_token = '[node:field_url]';
    $invalid_token = '[node:invalid_token]';
    $body_text = 'My body text with an invalid token: ';

    // Prepare the node to use for token replacement context.
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
      'field_url' => '',
    ]);
    $node->save();

    // Prepare pattern config values.
    $config = [
      'text' => $title_token,
      'formatted_text' => $body_text . $invalid_token,
      'image_url' => $optional_field_token,
    ];

    // Assemble the pattern element for rendering.
    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
      '#config' => $config,
      '#context' => [
        'node' => $node,
      ],
    ];

    $output = $this->renderer->renderRoot($element);

    // Confirm raw template content is included.
    $this->assertStringContainsString('Sample twig template', $output);

    // Confirm tokens with replacement values are not visible.
    $this->assertStringNotContainsString($title_token, $output);

    // Expect the invalid token and token without a replacement value are both
    // unaffected within the output.
    $this->assertStringContainsString($invalid_token, $output);
    $this->assertStringContainsString($optional_field_token, $output);

    // Confirm token replacement text is included as expected.
    $this->assertStringContainsString($title, $output, 'Title text was not replaced into token placement.');
    $this->assertStringContainsString($body_text, $output, 'Body content was not output.');

    $this->assertBubbleableMetadata($element, [
      'tags' => array_merge(
        ['pattern:' . $id],
        $node->getCacheTags(),
      ),
    ]);
  }

  /**
   * Test token replacement handling for invalid tokens with clearing enabled.
   */
  public function testTokenReplacementProcessorWithInvalidTokensWithClear() {
    // Load our pattern instance.
    $id = '@patternkit/atoms/example/src/example';
    $example_pattern = $this->loadPattern($id);
    $this->assertInstanceOf(PatternInterface::class, $example_pattern);

    // Enable the clear option in patternkit settings.
    $config = $this->config('patternkit.settings');
    $config->set('patternkit_json_editor_token_clear', TRUE);
    $config->save();

    // Prepare test values for identification in rendered output.
    $title = $this->getRandomGenerator()->sentences(3);
    $title_token = '[node:title]';
    $optional_field_token = '[node:field_url]';
    $invalid_token = '[node:invalid_token]';
    $body_text = 'My body text with an invalid token: ';

    // Prepare the node to use for token replacement context.
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
      'field_url' => '',
    ]);
    $node->save();

    // Prepare pattern config values.
    $config = [
      'text' => $title_token,
      'formatted_text' => $body_text . $invalid_token,
      'image_url' => $optional_field_token,
    ];

    // Assemble the pattern element for rendering.
    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
      '#config' => $config,
      '#context' => [
        'node' => $node,
      ],
    ];

    $output = $this->renderer->renderRoot($element);

    // Confirm raw template content is included.
    $this->assertStringContainsString('Sample twig template', $output);

    // Confirm tokens with replacement values are not visible.
    $this->assertStringNotContainsString($title_token, $output);

    // Confirm the invalid token and token without a replacement value are both
    // removed from the output.
    $this->assertStringNotContainsString($invalid_token, $output);
    $this->assertStringNotContainsString($optional_field_token, $output);

    // Confirm token replacement text is included as expected.
    $this->assertStringContainsString($title, $output, 'Title text was not replaced into token placement.');
    $this->assertStringContainsString($body_text, $output, 'Body content was not output.');

    $this->assertBubbleableMetadata($element, [
      'tags' => array_merge(
        ['pattern:' . $id],
        $node->getCacheTags(),
      ),
    ]);
  }

  /**
   * Test WYSIWYG filter execution on formatted text properties.
   */
  public function testWysiwygProcessor() {
    $id = '@patternkit/atoms/example/src/example';
    $example_pattern = $this->loadPattern($id);
    $this->assertInstanceOf(PatternInterface::class, $example_pattern);

    // Prepare a render element for testing.
    $text = $this->getRandomGenerator()->sentences(3);
    $formatted_text = '<p><strong>' . $this->getRandomGenerator()->sentences(3) . '</strong></p>';

    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
      '#config' => [
        'text' => $text,
        'formatted_text' => $formatted_text,
      ],
      '#context' => [],
    ];

    // Prepare a text filter to escape all HTML markup.
    $filtered_html_format = FilterFormat::create([
      'format' => 'escaped_html',
      'name' => 'Escaped HTML',
      'weight' => 0,
      'filters' => [
        'filter_html_escape' => [
          'status' => 1,
        ],
      ],
    ]);
    $filtered_html_format->save();

    // Set the globally configured WYSIWYG filter to be used.
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $settings = $config_factory->getEditable('patternkit.settings');
    $settings->set('patternkit_json_editor_wysiwyg', 'ckeditor');
    $settings->set('patternkit_json_editor_ckeditor_toolbar', 'escaped_html');
    $settings->save();

    $output = $this->renderer->renderRoot($element);

    $this->assertStringContainsString('Sample twig template', $output);
    $this->assertStringContainsString($text, $output);

    // Confirm the filter ran and escaped the markup in our text.
    $this->assertStringContainsString(htmlentities($formatted_text), $output);

    $this->assertBubbleableMetadata($element, [
      'tags' => array_merge(
        ['pattern:' . $id],
        $filtered_html_format->getCacheTags(),
      ),
    ]);
  }

  /**
   * Test WYSIWYG filter execution on formatted text properties.
   */
  public function testMultipleProcessors() {
    $id = '@patternkit/atoms/example/src/example';
    $example_pattern = $this->loadPattern($id);
    $this->assertInstanceOf(PatternInterface::class, $example_pattern);

    // Prepare test values for identification in rendered output.
    $title = $this->getRandomGenerator()->sentences(3);
    $title_token = '[node:title]';
    $formatted_text = '<p><strong>' . $this->getRandomGenerator()->sentences(3) . '</strong></p>';

    // Prepare the node to use for token replacement context.
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
    ]);
    $node->save();

    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
      '#config' => [
        'text' => $title_token,
        'formatted_text' => $formatted_text,
      ],
      '#context' => [
        'node' => $node,
      ],
    ];

    // Prepare a text filter to escape all HTML markup.
    $filtered_html_format = FilterFormat::create([
      'format' => 'escaped_html',
      'name' => 'Escaped HTML',
      'weight' => 0,
      'filters' => [
        'filter_html_escape' => [
          'status' => 1,
        ],
      ],
    ]);
    $filtered_html_format->save();

    // Set the globally configured WYSIWYG filter to be used.
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $settings = $config_factory->getEditable('patternkit.settings');
    $settings->set('patternkit_json_editor_wysiwyg', 'ckeditor');
    $settings->set('patternkit_json_editor_ckeditor_toolbar', 'escaped_html');
    $settings->save();

    $output = $this->renderer->renderRoot($element);

    // Confirm output contains raw content in the template.
    $this->assertStringContainsString('Sample twig template', $output);

    // Confirm the token value wasn't left unprocessed.
    $this->assertStringNotContainsString($title_token, $output, 'The title token was exposed without replacement in output.');

    // Confirm the token value was properly replaced.
    $this->assertStringContainsString($title, $output, 'The title token value was not properly replaced into the output.');

    // Confirm the filter ran and escaped the markup in our text.
    $this->assertStringContainsString(htmlentities($formatted_text), $output);

    $this->assertBubbleableMetadata($element, [
      'tags' => array_merge(
        ['pattern:' . $id],
        $node->getCacheTags(),
        $filtered_html_format->getCacheTags(),
      ),
    ]);
  }

  /**
   * Verify that processSchemaValues does not validate or reject stored content.
   *
   * The render path must pass stored values through unchanged regardless of
   * the field's type, format, or pattern constraint. Content saved before
   * strict render-time validation was introduced must not cause a
   * SchemaValidationException or a WARNING log entry on cache-miss renders.
   *
   * Cases are drawn from the render_validation_test pattern, which covers all
   * swaggest-validated draft-04 format keywords (uri, email, hostname,
   * date-time) and a regex pattern constraint, plus a plain type mismatch.
   * The uri case also exercises TokenValidationDataPreProcessor: that service
   * is active in the full kernel stack and nulls the format constraint for
   * token strings, so the uri field demonstrates the preprocessor behavior
   * alongside the no-validation contract.
   *
   * @dataProvider providerRenderValidationTestStoredContent
   */
  public function testProcessSchemaValuesDoesNotValidateStoredContent(
    array $values,
  ): void {
    // Enable patternkit_test here rather than in $modules to avoid its
    // ExceptionThrowerProcessor plugin affecting testPluginsAreDiscovered.
    $this->enableModules(['patternkit_test']);

    // Re-fetch services from the rebuilt container so that pattern discovery
    // includes libraries registered by the newly-enabled module.
    $patternDiscovery = $this->container->get('patternkit.pattern.discovery');
    $pluginManager = $this->container->get('plugin.manager.pattern_field_processor');

    $id = '@patternkit_test/render_validation_test/render_validation_test';
    $definition = $patternDiscovery->getPatternDefinition($id);
    $pattern = EntityPattern::create($definition);

    $original = $values;
    $metadata = new BubbleableMetadata();

    // processSchemaValues must complete without throwing. Any exception here
    // blocks rendering and surfaces as a visitor-facing failure.
    $pluginManager->processSchemaValues($pattern, $values, [], $metadata);

    // Confirm values are passed through unmodified. Capturing $original before
    // the call is required because $values is passed by reference; comparing
    // $values to itself post-call would always pass regardless of mutation.
    $this->assertEquals($original, $values);
  }

  /**
   * Data provider for testProcessSchemaValuesDoesNotValidateStoredContent.
   *
   * Each case specifies stored field values that violate the corresponding
   * field constraint in the render_validation_test pattern. Providing only
   * the one relevant field per case isolates the assertion to a single
   * constraint type, making failure output actionable.
   *
   * Token resolution note: tokens such as [node:url], [user:mail],
   * [node:created:raw], and [node:created] are expected to pass through
   * processSchemaValues unmodified in this kernel environment because no
   * entity context is present (no current node, user, etc.). The equality
   * assertion in the test therefore holds — the token string in equals the
   * token string out. The hostname case uses [hostname:invalid_field], an
   * intentionally unresolvable token, for the same reason (the hostname
   * module's valid fields would resolve to a real value).
   */
  public static function providerRenderValidationTestStoredContent(): array {
    return [
      // Type mismatch: integer stored where label's type: string is declared.
      'label_type_mismatch' => [
        ['label' => 42],
      ],
      // Token in a uri-format field. TokenValidationDataPreProcessor nulls the
      // format constraint for uri fields containing tokens, so the full stack
      // must not throw even before the no-validation fix is considered.
      'url_token_in_uri_format_field' => [
        ['url' => '[node:url]'],
      ],
      // Token in an email-format field. No preprocessor bypass exists for
      // email; this case directly represents the WARNING regression.
      'email_token_in_email_format_field' => [
        ['email' => '[user:mail]'],
      ],
      // Token in a hostname-format field. Same regression class as email.
      // [site:name] resolves in the kernel environment (to an empty string from
      // site config), so use an unresolvable token to keep the value stable for
      // the equality assertion. The regression class is the same: any token
      // stored in a hostname-constrained field must not cause a throw.
      'hostname_token_in_hostname_format_field' => [
        ['hostname' => '[hostname:invalid_field]'],
      ],
      // Token in a date-time-format field. Same regression class.
      'date_time_token_in_date_time_format_field' => [
        ['date_time' => '[node:created:raw]'],
      ],
      // Token in a pattern-constrained field. Tokens never match the ISO 8601
      // date regex; no bypass exists. The no-validation contract still applies.
      'date_pattern_token_in_pattern_constrained_field' => [
        ['date_pattern' => '[node:created]'],
      ],
    ];
  }

  /**
   * Create a new pattern instance of the specified pattern ID.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern ID to load.
   *
   * @return \Drupal\patternkit\Entity\Pattern
   *   The instantiated pattern entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadPattern(string $pattern_id) {
    $definition = $this->patternDiscovery->getPatternDefinition($pattern_id);
    return EntityPattern::create($definition);
  }

}
