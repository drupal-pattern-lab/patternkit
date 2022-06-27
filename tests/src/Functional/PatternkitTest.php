<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\patternkit\Entity\Pattern;
use Drupal\patternkit\Plugin\Derivative\PatternkitBlock;
use Drupal\Tests\BrowserTestBase;

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

  /**
   * Modules to install.
   *
   * @var array
   */
  static protected $modules = [
    'patternkit',
    'patternkit_example',
    'layout_builder',
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

}
