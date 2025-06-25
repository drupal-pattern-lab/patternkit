<?php

namespace Drupal\Tests\patternkit\Functional;

use Drupal\patternkit\Hook\ReusableBlockHooks;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Testing for functionality with reusable blocks disabled.
 */
#[Group('patternkit')]
#[Group('legacy')]
#[CoversClass(ReusableBlockHooks::class)]
class ReusableBlocksDisabledTest extends PatternkitBrowserTestBase {

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

    // Expect no link to the Patternkit library to be available.
    $this->drupalGet('admin/structure/block');
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Patternkit library');

    // Expect the collection route to be inaccessible.
    $this->drupalGet('admin/structure/block/patternkit');
    $assert->statusCodeEquals(404);

    // Expect block add route to be inaccessible.
    $this->drupalGet('patternkit_block/add');
    $assert->statusCodeEquals(404);
  }

  /**
   * Test to confirm a block cannot be made reusable through Layout Builder.
   */
  public function testBlockForm(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/1/layout');

    $page->clickLink("Add block");

    $pattern_name = '[Patternkit] Example';
    $assert->linkExists($pattern_name);
    $page->clickLink($pattern_name);

    // The "reusable" checkbox should not be shown on the form.
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Reusable');
  }

}
