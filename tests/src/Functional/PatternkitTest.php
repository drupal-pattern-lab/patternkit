<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\node\Entity\Node;
use Drupal\patternkit\Element\PatternError;
use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Plugin\Derivative\PatternkitBlock;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\patternkit\Traits\PatternkitBlockHelperTrait;

/**
 * Basic E2E testing for Patternkit.
 *
 * @group legacy
 *
 * @ingroup patternkit
 *
 * @todo Remove legacy group on 1.0 release and fix any deprecations.
 */
class PatternkitTest extends BrowserTestBase {

  use PatternkitBlockHelperTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  static protected $modules = [
    'patternkit',
    'patternkit_example',
    'patternkit_test',
    'layout_builder',
    'node',
  ];

  /**
   * Default Drupal theme for running tests.
   *
   * @var string
   */
  protected $defaultTheme = 'stable';

  /**
   * Storage handler for patternkit_block content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $patternBlockStorage;

  /**
   * Storage handler for patternkit_pattern content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $patternStorage;

  /**
   * The patternkit library service.
   *
   * @var \Drupal\patternkit\Asset\Library
   */
  protected $library;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->patternBlockStorage = $entity_type_manager->getStorage('patternkit_block');
    $this->patternStorage = $entity_type_manager->getStorage('patternkit_pattern');
    $this->library = $this->container->get('patternkit.asset.library');

    // Create a bundle type and node we'll enable layout builder for.
    $this->drupalCreateContentType([
      'type' => 'bundle_with_layout_enabled',
      'name' => 'Bundle with layout enabled',
    ]);
    $this->drupalCreateNode([
      'type' => 'bundle_with_layout_enabled',
      'title' => 'Test node title',
      'body' => [
        [
          'value' => 'Test node body.',
        ],
      ],
    ]);

    // Enable layout builder for the test bundle.
    LayoutBuilderEntityViewDisplay::load('node.bundle_with_layout_enabled.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
  }

  /**
   * Verify successful page load after installation.
   */
  public function testSiteInstall() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('Test node title');
    $this->assertSession()->pageTextContains('Test node body');
  }

  /**
   * Verify creation, placement, and display of a patternkit block.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testPatternkitExampleBlock() {
    // Create a block instance for placement.
    // @todo Remove this when
    // Drupal\patternkit\Plugin\Block\PatternkitBlock::setConfig
    // handles default block creation.
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $patternkit_block */
    $patternkit_block = $this->patternBlockStorage->create([
      'data' => ['value' => '{}'],
      'info' => $this->randomMachineName(),
      'reusable' => FALSE,
      'published' => TRUE,
      'pattern_id' => '@patternkit/atoms/example/src/example',
    ]);
    $patternkit_block->save();

    // Identify and create the pattern entity to be used.
    $pattern_id = PatternkitBlock::derivativeToAssetId('patternkit_atoms_example_src_example');
    $pattern = Pattern::create($this->library->getLibraryAsset($pattern_id));

    // Load the pattern from storage.
    $pattern_cache = $this->patternStorage->loadByProperties([
      'library' => $pattern->getLibrary(),
      'path' => $pattern->getPath(),
    ]);
    /** @var \Drupal\patternkit\Entity\PatternInterface $pattern_loaded */
    $pattern_loaded = end($pattern_cache);
    if (!empty($pattern_loaded)) {
      if ($pattern_loaded->getHash() !== $pattern->getHash()) {
        $pattern->setNewRevision();
        $pattern->isDefaultRevision(TRUE);
      }
      else {
        $pattern = $pattern_loaded;
      }
    }
    $pattern->save();

    // Place the block for viewing.
    $block = $this->drupalPlaceBlock('patternkit_block:patternkit_atoms_example_src_example', [
      'region' => 'content',
      'pattern' => $pattern->getRevisionId(),
      'patternkit_block_id' => $patternkit_block->id(),
      'patternkit_block_rid' => $patternkit_block->getRevisionId(),
    ]);

    $assert = $this->assertSession();

    // Expect to see the block content on the page request.
    $this->drupalGet('');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Sample twig template.');
  }

  /**
   * Verify successful placement and rendering of a pattern through the UI.
   */
  public function testLayoutBuilderBlockPlacementUi() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    // Edit the node-specific layout override.
    $this->drupalGet('node/1/layout');
    $page->clickLink('Add block');
    $page->clickLink('[Patternkit] Example');

    // Fill in pattern data using the hidden config field since the JSON editor
    // form requires JS to be properly rendered.
    $page->find('css', '#schema_instance_config')->setValue(json_encode([
      'text' => 'Pattern block title',
      'formatted_text' => 'Pattern block body',
      'image_url' => '',
      'hidden' => 'Hidden text',
    ]));

    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    $assert_session->pageTextContains('Pattern block title');
    $assert_session->pageTextContains('Pattern block body');
  }

  /**
   * Test behavior when a block encounters an exception during rendering.
   *
   * @see \Drupal\patternkit_test\Plugin\PatternFieldProcessor\ExceptionThrowerProcessor
   *
   * @todo Add testing to confirm debug output for privileged users.
   */
  public function testBlockRenderException() {
    $assert_session = $this->assertSession();

    // Create a block with a value that will be processed and trigger the
    // exception processor.
    $pattern_id = '@patternkit/atoms/example_ref/src/example_ref';
    $block = $this->createPatternBlock($pattern_id, [
      'text' => 'My test value',
      'nested_reference' => [
        'exception' => 'Planned exception',
      ],
    ]);

    $node = Node::load(1);
    $this->placePatternBlockInLayout($node, $block);

    $this->drupalGet('node/1');
    $assert_session->pageTextContains("Failed to render pattern Example with Reference ($pattern_id).");
    // Debug output including the exception message should not be visible to
    // anonymous users.
    $assert_session->pageTextNotContains('Planned exception');
  }

  /**
   * Test to confirm debug output isn't cached and served anonymously.
   *
   * @requires devel
   */
  public function testDebugOutputCaching() {
    $assert_session = $this->assertSession();

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();

    // Enable the devel module to see debug output.
    \Drupal::service('module_installer')
      ->install([
        'devel',
      ]);

    // Create a block with a value that will be processed and trigger the
    // exception processor.
    $pattern_id = '@patternkit/atoms/example_ref/src/example_ref';
    $block = $this->createPatternBlock($pattern_id, [
      'text' => 'My test value',
      'nested_reference' => [
        'exception' => 'Planned exception',
      ],
    ]);

    $node = Node::load(1);
    $this->placePatternBlockInLayout($node, $block);

    // View as a privileged user first to see debug output.
    $user = $this->createUser([
      PatternError::DEBUG_PERMISSION,
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('node/1');
    $assert_session->pageTextContains("Failed to render pattern Example with Reference ($pattern_id).");
    // A privileged user should be able to see the exception message output.
    $assert_session->pageTextContains('Planned exception');

    // Logout and view the page again to confirm debug output is no longer
    // visible for an unprivileged user.
    $this->drupalLogout();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains("Failed to render pattern Example with Reference ($pattern_id).");
    $assert_session->pageTextNotContains('Planned exception');
  }

}
