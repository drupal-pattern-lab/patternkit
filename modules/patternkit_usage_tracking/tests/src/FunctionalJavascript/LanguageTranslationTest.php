<?php

namespace Drupal\Tests\patternkit_usage_tracking\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;

/**
 * End to end flow to test content translation in patternkit usage report.
 */
#[Group('patternkit')]
#[Group('patternkit_translation')]
#[Group('patternkit_usage_tracking')]
class LanguageTranslationTest extends ContentTranslationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
    'patternkit_usage_tracking',
  ];

  /**
   * Browser tests for verifying language translation content in usage report.
   */
  public function testLanguageConfiguration() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $pattern_name = '[Patternkit] Example';

    // Log in as an editor with access to update layouts.
    $this->drupalLogin($this->editorUser);

    // Override the Node layout and place a patternkit block.
    $this->drupalGet('node/2/layout');

    $this->addPatternBlock($pattern_name);

    // Wait for the JSON Editor form to load.
    $this->waitForJsonEditorForm();

    // Fill in JSON Editor fields.
    $page->fillField('root[text]', 'Pattern block title');
    $page->fillField('root[hidden]', 'My hidden text');
    $page->fillField('root[formatted_text]', 'Pattern block body');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');

    $this->drupalLogin($this->translatorUser);
    $this->drupalGet('fr/node/2/translations/add/en/fr');
    $page->fillField('edit-title-0-value', 'Test Page French');
    $page->fillField('edit-body-0-value', 'Test Page French');

    $page->pressButton('Save (this translation)');

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('/admin/reports/pattern-usage');

    // Array of Node titles and their expected languages.
    $nodes = [
      'Test node title (English)' => 'English',
      'Test Page French' => 'French',
    ];

    // Loop through each Node title and its expected language.
    foreach ($nodes as $title => $expectedLanguage) {
      // XPath query to find the row containing the Node title.
      $xpath = "//table/tbody/tr[td/a[contains(text(),'$title')]]";

      // Find the row.
      $row = $page->find('xpath', $xpath);

      // Ensure the row is found.
      $this->assertNotNull($row, "The row containing the title '$title' was not found.");

      // Find the language cell within the row.
      $languageCell = $row->find('xpath', 'td[4]');

      // Ensure the language cell is found.
      $this->assertNotNull($languageCell, "The language cell for the title '$title' was not found.");

      // Assert the language cell contains the expected language.
      $this->assertEquals($expectedLanguage, $languageCell->getText(), "The language cell for '$title' does not contain the expected language '$expectedLanguage'.");
    }

    // Verifying original node in the usage report.
    $page->clickLink('Test node title (English)');
    $currentUrl = $this->getUrl();
    $this->assertStringContainsString('/node/2', $currentUrl);
    $assert->pageTextContains('Test node body(English)');
    $assert->pageTextContains('Pattern block title');

    $this->drupalGet('/admin/reports/pattern-usage');

    // Verifying translated node in the usage report.
    $page->clickLink('Test Page French');
    $currentUrl = $this->getUrl();
    $this->assertStringContainsString('/fr/node/2', $currentUrl);
    $assert->pageTextContains('Test Page French');
    $assert->pageTextContains('Pattern block title');
  }

}
