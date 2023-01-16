<?php

namespace Drupal\Tests\patternkit\Kernel;

use Drupal\Core\Render\RendererInterface;

/**
 * Test Patternkit twig caching behavior with default parameters.
 *
 * @coversNothing
 * @group patternkit
 */
class PatternkitTwigCacheTest extends PatternkitKernelTestBase {

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

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Test how many Twig cache files are compiled for a pattern render.
   */
  public function testPatternRender(): void {
    $pattern_id = '@patternkit/atoms/example/src/example';

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
      '#pattern' => $pattern_id,
      '#config' => $config,
      '#context' => [],
    ];

    // Confirm the rendered output contains expected contents.
    $output = (string) $this->renderer->renderRoot($element);
    $this->assertStringContainsString($title_text, $output);
    $this->assertStringContainsString($body_text, $output);
    $this->assertStringContainsString('Sample twig template.', $output);

    // Look for created Twig cache files.
    $files = scandir($this->siteDirectory . '/files/php/twig');
    // We only care about the string template cache files being created.
    $cache_files = array_filter($files, function ($file) {
      return str_contains($file, 'string_template');
    });
    // Only one for the overall pattern template should be created.
    $this->assertEquals(1, count($cache_files));
  }

}
