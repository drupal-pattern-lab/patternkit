<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\Core\Entity\EntityInterface;

/**
 * Tests server-side acceptance of placeholder token values in pattern blocks.
 *
 * These tests confirm that the server-side processing pipeline — including
 * the TokenValidationDataPreProcessor — accepts Drupal token values in
 * format-constrained fields during both block save and node rendering,
 * without schema validation errors or PHP exceptions.
 *
 * Unlike the FunctionalJavascript tests, these tests operate without a
 * browser and without client-side JSON Editor validation, allowing the
 * server-side behavior to be exercised in isolation. A passing result here
 * confirms that any remaining issues with token values in the full browser
 * flow are attributable to the client-side layer.
 *
 * @group patternkit
 * @group patternkit_token_validation
 * @see https://www.drupal.org/project/patternkit/issues/3585847
 */
class PlaceholderValueFunctionalTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_test',
  ];

  /**
   * Asset ID of the pattern used as the test fixture.
   *
   * The library root is lib/patternkit_test/components, so the pattern's
   * path within the library is render_validation_test/render_validation_test.
   */
  const RENDER_VALIDATION_PATTERN_ASSET_ID
    = '@patternkit_test/render_validation_test/render_validation_test';

  /**
   * Confirms a token hostname value saves and renders without error.
   *
   * Creates a PatternkitBlock with a bare Drupal token in
   * the hostname field, places it in a node's layout, and loads the node. A
   * 200 response without PHP errors confirms the full server-side pipeline
   * (block save → layout render → TokenValidationDataPreProcessor) accepts
   * the token value without schema rejection.
   */
  public function testTokenInHostnameFieldSavesAndRenders(): void {
    $config = ['hostname' => '[site:name]'];

    $node = $this->createNodeWithPatternBlock($config);
    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextNotContains('The website encountered an unexpected error');
  }

  /**
   * Confirms token values in multiple format fields render without error.
   *
   * Exercises the TokenValidationDataPreProcessor across the full set of
   * format keywords supported by the render_validation_test fixture:
   * hostname, ipv4, url, email, and date-time.
   */
  public function testTokensInMultipleFormatFieldsRender(): void {
    $config = [
      'hostname' => '[site:name]',
      'ipv4' => '[site:ip]',
      'url' => '[node:url]',
      'email' => '[site:mail]',
      'date_time' => '[node:created]',
    ];

    $node = $this->createNodeWithPatternBlock($config);
    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextNotContains('The website encountered an unexpected error');
  }

  /**
   * Creates a node and places a render_validation_test block in its layout.
   *
   * @param array $config
   *   JSON Editor configuration values to populate the block with, keyed by
   *   field name (without the "root[]" wrapper).
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The node whose layout has the block placed into it.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNodeWithPatternBlock(array $config): EntityInterface {
    $node = $this->drupalCreateNode([
      'type' => 'bundle_with_layout_enabled',
      'title' => 'Placeholder value test node',
    ]);

    $block = $this->createPatternBlock(
      static::RENDER_VALIDATION_PATTERN_ASSET_ID,
      $config
    );
    $this->placePatternBlockInLayout($node, $block);

    return $node;
  }

}
