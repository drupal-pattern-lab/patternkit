<?php

namespace Drupal\Tests\patternkit\Kernel\Element;

use Drupal\Core\Render\RendererInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Asset\PatternDiscoveryInterface;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\Tests\patternkit\Traits\MetadataTestTrait;

/**
 * Tests for the Pattern element class.
 *
 * @coversDefaultClass \Drupal\patternkit\Element\Pattern
 * @group patternkit
 */
class PatternTest extends KernelTestBase {

  use MetadataTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
    'patternkit_example',
    'text',
    'system',
    'node',
    'user',
    'filter',
  ];

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

    $this->patternDiscovery = $this->container->get('patternkit.pattern.discovery');
    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Test for the presence of default pattern cache data.
   *
   * @covers ::preRenderPatternElement
   */
  public function testBasicPatternCacheData() {
    // Load our pattern instance.
    $pattern_id = '@patternkit/atoms/example/src/example';
    $example_pattern = $this->loadPattern($pattern_id);
    $this->assertInstanceOf(PatternInterface::class, $example_pattern);

    // Prepare test values for identification in rendered output.
    $title_text = $this->getRandomGenerator()->sentences(3);
    $body_text = $this->getRandomGenerator()->sentences(3);

    // Prepare pattern config values.
    $config = [
      'text' => $title_text,
      'formatted_text' => $body_text,
    ];

    // Assemble the pattern element for rendering.
    $element = [
      '#type' => 'pattern',
      '#pattern' => $example_pattern,
      '#config' => $config,
      '#context' => [],
    ];

    $this->renderer->renderRoot($element);

    $this->assertBubbleableMetadata($element, [
      'tags' => ['pattern:' . $pattern_id],
    ]);
  }

  /**
   * Create a new pattern instance of the specified pattern ID.
   *
   * @param string $pattern_id
   *   The namespaced identifier for the pattern ID to load.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The instantiated pattern entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadPattern(string $pattern_id): PatternInterface {
    $definition = $this->patternDiscovery->getPatternDefinition($pattern_id);
    return Pattern::create($definition);
  }

}
