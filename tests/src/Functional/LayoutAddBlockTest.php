<?php

namespace Drupal\Tests\patternkit\Functional;

/**
 * Addition of PK block with Layout Builder.
 *
 * @group patternkit
 */
class LayoutAddBlockTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ["patternkit_example"];

  /**
   * Verify successful page load after installation.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function setUp(): void {
    parent::setUp();
    $page = $this->getSession()->getPage();

    // Log in as an editor with access to update layouts.
    $this->drupalLogin($this->editorUser);
    // Override the Node layout and open the block picker.
    // Inline rather than placePatternBlockViaUi(): the block picker is opened
    // here in setUp() while the pattern link click and schema injection live in
    // the data-driven testExampleBlocks(), making the sequence impossible to
    // encapsulate in a single helper call without restructuring the test class.
    $this->drupalGet("node/1/layout");
    $page->clickLink("Add block");
  }

  /**
   * Verify successful rendering of a pattern block with layout builder.
   *
   * @param string $patternName
   *   The name of the Patternkit block.
   * @param array $dataValues
   *   Properties name to be used in JSON Editor Form.
   * @param array $assertions
   *   The assertions to be made during the test.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *
   * @dataProvider providerTestPatternkitBlock
   */
  public function testExampleBlocks(
    string $patternName,
    array $dataValues,
    array $assertions,
  ) {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Wait for the block list to load before selecting a pattern to insert.
    $assert->linkExists($patternName);
    $page->clickLink($patternName);

    // Constructing JSON data dynamically.
    $jsonData = [
      "text" => $dataValues['text'],
    ];

    // Define an array of keys outside the loop.
    $distinctBlockProperties = [
      'formatted_text',
      'formatted_text_free',
      'formatted_text_restricted',
      'image_url',
      'hidden',
      'nested_reference',
    ];

    // Adding additional field dynamically if provided in any data values array.
    foreach ($dataValues as $key => $value) {
      if (in_array($key, $distinctBlockProperties)) {
        $jsonData[$key] = $value;
      }
    }

    // Fill in the hidden fields manually since fillField() won't find hidden
    // fields.
    $page->find("css", "#schema_instance_config")->setValue(
      json_encode($jsonData)
    );

    // Set the node for context to fill in the token.
    $page->pressButton("Add block");
    // If server-side validation rejected the block config, Drupal re-renders
    // the form with an error message and "Save layout" will not be found.
    // Fail here with the validation error text for immediate diagnosis.
    $error = $page->find('css', '[role="alert"]');
    if ($error !== NULL) {
      $this->fail('Block config form rejected by server-side validation: ' . $error->getText());
    }
    $page->pressButton("Save layout");

    // Confirm visibility on a Node page.
    foreach ($assertions as $text) {
      $assert->pageTextContains($text);
    }
  }

  /**
   * Data provider for ::testExampleBlocks.
   */
  public static function providerTestPatternkitBlock(): array {
    return [
      // Test the example block.
      'example' => [
        // The pattern name to be tested.
        '[Patternkit] Example',
        // Data values to save into the block.
        [
          'text' => 'PK Example block',
          'image_url' => '',
          'hidden' => 'Hidden text',
          'formatted_text' => 'Pattern block body',
        ],
        // An array of text values to assert visibility of on the page.
        [
          'PK Example block',
          'Pattern block body',
        ],
      ],

      // Test the example with filtered content block.
      'example_with_filtered_content' => [
        // The pattern name to be tested.
        '[Patternkit] Example with filtered content',
        // Data values to save into the block.
        [
          'text' => '[Patternkit] Example with filtered content',
          'formatted_text' => 'Pattern block body',
          'image_url' => '',
          'hidden' => 'Hidden text',
        ],
        // An array of text values to assert visibility of on the page.
        [
          '[Patternkit] Example with filtered content',
          'Pattern block body',
        ],
      ],

      // Test the example with multiple filtered content block.
      'example_with_multiple_filtered_content' => [
        // The pattern name to be tested.
        '[Patternkit] Example with multiple filtered content blocks',
        // Data values to save into the block.
        [
          'text' => '[Patternkit] Example with multiple filtered content blocks',
          "formatted_text_restricted" => "Pattern block body",
          "formatted_text_free" => "Pattern block body",
          'image_url' => '',
          'hidden' => 'Hidden text',
        ],
        // An array of text values to assert visibility of on the page.
        [
          '[Patternkit] Example with multiple filtered content blocks',
          'Pattern block body',
        ],
      ],

      // Test the example with reference content block.
      'example_with_reference_content' => [
        // The pattern name to be tested.
        '[Patternkit] Example with Reference',
        // Data values to save into the block.
        [
          'text' => '[Patternkit] Example text',
          "nested_reference" => [
            "text" => "[Patternkit] Example with Reference text",
            "formatted_text" => "Pattern block body",
            "image_url" => "",
            "hidden" => "Hidden text",
          ],
        ],
        // An array of text values to assert visibility of on the page.
        [
          '[Patternkit] Example with Reference',
          'Pattern block body',
        ],
      ],
    ];
  }

}
