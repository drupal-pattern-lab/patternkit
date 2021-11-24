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
   * @var string
   *   Default Drupal theme for running tests.
   */
  protected $defaultTheme = 'stable';

  /**
   * Verify that the toolbar tab and tray are showing and functioning.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testPatternkitExampleBlock() {
    // @todo Remove this when
    // Drupal\patternkit\Plugin\Block\PatternkitBlock::setConfig
    // handles default block creation.
    /** @var \Drupal\patternkit\Entity\PatternkitBlock $patternkit_block */
    $patternkit_block = \Drupal::entityTypeManager()->getStorage('patternkit_block')->create([
      'data' => ['value' => '{}'],
      'info' => $this->randomMachineName(),
      'reusable' => FALSE,
      'published' => TRUE,
    ]);
    $patternkit_block->save();
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $pattern_storage */
    $pattern_storage = \Drupal::entityTypeManager()->getStorage('patternkit_pattern');
    $pattern_id = PatternkitBlock::derivativeToAssetId('patternkit_atoms_example_src_example');
    /** @var \Drupal\patternkit\Entity\PatternInterface $pattern */
    $pattern = Pattern::create(\Drupal::service('patternkit.asset.library')->getLibraryAsset($pattern_id));
    $pattern_cache = $pattern_storage->loadByProperties([
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

    $this->drupalPlaceBlock('patternkit_block:patternkit_atoms_example_src_example', [
      'region' => 'content',
      'pattern' => $pattern->getRevisionId(),
      'patternkit_block_id' => $patternkit_block->id(),
      'patternkit_block_rid' => $patternkit_block->getRevisionId(),
    ]);

    $assert = $this->assertSession();

    $this->drupalGet('');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Test sample twig template.');
  }

}
