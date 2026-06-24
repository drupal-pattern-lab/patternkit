<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\patternkit\Hook\ReusableBlockHooks;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Testing for functionality with reusable blocks enabled.
 */
#[Group('patternkit')]
#[Group('legacy')]
#[CoversClass(ReusableBlockHooks::class)]
class ReusableBlocksEnabledTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * Use Claro to ensure menu link tabs are placed and visible.
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function initSettings(): void {
    parent::initSettings();

    // Set the container parameter for testing.
    $patternkit_config = [
      'features' => ['enable_reusable_blocks' => TRUE],
    ];
    $this->setContainerParameter('patternkit.config', $patternkit_config);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Bypass permissions restrictions by logging in as User 1.
    $this->drupalLogin(User::load(1));
  }

  /**
   * Test access to reusable patternkit block collection pages.
   */
  public function testBlockCollectionPageAccess(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Expect local task link to the Patternkit library to be available.
    $this->drupalGet('admin/structure/block');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Patternkit library');
    $page->clickLink('Patternkit library');

    // Expect the collection route to be accessible.
    $assert->addressEquals('admin/structure/block/patternkit');
    $assert->statusCodeEquals(200);
    $assert->linkExists('Add Patternkit block');
    $page->clickLink('Add Patternkit block');

    // Expect block add route to be accessible.
    $assert->addressEquals('patternkit_block/add');
    $assert->statusCodeEquals(200);
    $assert->linkExists('Example');
    $page->clickLink('Example');

    // Expect to be able to create the block.
    $assert->addressEquals('patternkit_block/add/patternkit_atoms_example_src_example');
    $page->fillField('Block description', 'My example block');
    $page->pressButton('Save');

    // Expect the new block to be listed on the collection page.
    $this->drupalGet('admin/structure/block/patternkit');
    $assert->pageTextContains('My example block');
  }

  /**
   * Test to confirm the reusable option is shown when reusable blocks enabled.
   */
  public function testBlockForm(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/1/layout');

    $page->clickLink("Add block");

    $pattern_name = '[Patternkit] Example';
    $assert->linkExists($pattern_name);
    $page->clickLink($pattern_name);

    // The "reusable" checkbox should be shown when reusable blocks are enabled.
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Reusable');
  }

}
