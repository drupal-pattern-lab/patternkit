<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\Core\Render\RendererInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\patternkit\Asset\LibraryNamespaceResolver;
use Drupal\patternkit\Asset\PatternDependencyResolver;
use Drupal\patternkit\Asset\PatternDiscovery;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Asset\PatternDiscoveryLoader;
use Drupal\patternkit\Asset\PatternLibraryParser\JSONPatternLibraryParser;
use Drupal\patternkit\Asset\PatternLibraryParser\TwigPatternLibraryParser;
use Drupal\patternkit\Asset\PatternLibraryParserBase;
use Drupal\patternkit\Attribute\PatternFieldProcessor;
use Drupal\patternkit\Attribute\PatternLibrary as AttributePatternLibrary;
use Drupal\patternkit\Element\JsonEditorForm;
use Drupal\patternkit\Element\Pattern as ElementPattern;
use Drupal\patternkit\Element\PatternError;
use Drupal\patternkit\Entity\Pattern as EntityPattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Entity\PatternRouteProvider;
use Drupal\patternkit\JsonEditorFormBuilder;
use Drupal\patternkit\Loader\PatternLibraryLoader;
use Drupal\patternkit\PatternFieldProcessorPluginManager;
use Drupal\patternkit\PatternkitEnvironment;
use Drupal\patternkit\PatternLibrary;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Drupal\patternkit\PatternLibraryPluginManager;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Plugin\PatternFieldProcessor\TokenProcessor;
use Drupal\patternkit\Plugin\PatternFieldProcessor\WysiwygFieldProcessor;
use Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryJSON;
use Drupal\patternkit\Plugin\PatternLibrary\PatternLibraryTwig;
use Drupal\patternkit\Routing\PatternkitTranslateBlockFormRouteSubscriber;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\BundleReferenceDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection;
use Drupal\patternkit\Schema\DataPreProcessor\ObjectCoercionDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\RefNormalizerDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver;
use Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\patternkit\Schema\PatternkitRefProvider;
use Drupal\patternkit\Schema\SchemaFactory;
use Drupal\patternkit\Schema\SchemaHelper;
use Drupal\patternkit\Schema\SchemaIterator;
use Drupal\patternkit\Schema\SchemaRefHandler;
use Drupal\patternkit\Schema\SchemaWalker;
use Drupal\patternkit\Schema\SchemaWalkerFactory;
use Drupal\patternkit\Schema\UnresolvedSchema;
use Drupal\Tests\patternkit\Traits\MetadataTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Test pattern field processor plugin manager functionality.
 */
#[Group('patternkit')]
#[CoversClass(ContextBuilder::class)]
#[CoversClass(DataPreProcessorCollection::class)]
#[CoversClass(DataPreProcessorFactory::class)]
#[CoversClass(ElementPattern::class)]
#[CoversClass(EntityPattern::class)]
#[CoversClass(PatternFieldProcessor::class)]
#[CoversClass(PatternFieldProcessorPluginManager::class)]
#[CoversClass(PatternkitRefProvider::class)]
#[CoversClass(SchemaFactory::class)]
#[CoversClass(SchemaHelper::class)]
#[CoversClass(SchemaIterator::class)]
#[CoversClass(SchemaWalker::class)]
#[CoversClass(SchemaWalkerFactory::class)]
#[CoversClass(TokenProcessor::class)]
#[CoversClass(TokenValidationDataPreProcessor::class)]
#[CoversClass(WysiwygFieldProcessor::class)]
#[UsesClass(AttributePatternLibrary::class)]
#[UsesClass(BundleReferenceDataPreProcessor::class)]
#[UsesClass(JSONPatternLibraryParser::class)]
#[UsesClass(JsonEditorForm::class)]
#[UsesClass(JsonEditorFormBuilder::class)]
#[UsesClass(LibraryNamespaceResolver::class)]
#[UsesClass(ObjectCoercionDataPreProcessor::class)]
#[UsesClass(PatternDependencyResolver::class)]
#[UsesClass(PatternDiscovery::class)]
#[UsesClass(PatternDiscoveryLoader::class)]
#[UsesClass(PatternError::class)]
#[UsesClass(PatternLibrary::class)]
#[UsesClass(PatternLibraryJSON::class)]
#[UsesClass(PatternLibraryLoader::class)]
#[UsesClass(PatternLibraryParserBase::class)]
#[UsesClass(PatternLibraryPluginDefault::class)]
#[UsesClass(PatternLibraryPluginManager::class)]
#[UsesClass(PatternLibraryTwig::class)]
#[UsesClass(PatternRepository::class)]
#[UsesClass(PatternRouteProvider::class)]
#[UsesClass(PatternkitEnvironment::class)]
#[UsesClass(PatternkitTranslateBlockFormRouteSubscriber::class)]
#[UsesClass(RefNormalizerDataPreProcessor::class)]
#[UsesClass(SchemaRefHandler::class)]
#[UsesClass(SchemaRefObserver::class)]
#[UsesClass(TwigPatternLibraryParser::class)]
#[UsesClass(UnresolvedSchema::class)]
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
