<?php

namespace Drupal\Tests\patternkit\FunctionalJavascript;

use Drupal\node\NodeInterface;

/**
 * Tests that placeholder values pass the client-side JSON Editor submit gate.
 *
 * Verifies that bare Drupal tokens entered in format-constrained Patternkit
 * block fields are allowed through the client-side filterTokenErrors() submit
 * gate, and that invalid non-placeholder values remain blocked as expected.
 *
 * The render_validation_test pattern is used as the shared fixture base. Its
 * format-constrained fields (hostname, ipv4, uri, email, date-time) and
 * nested link object cover the scenarios defined in Drupal.org #3585847.
 *
 * JSON Editor validation scope: the bundled ipValidator enforces format:
 * hostname, ipv4, and ipv6. Format keywords uri, email, and date-time are not
 * validated by JSON Editor's built-in validators. Tests for those formats
 * exercise the full save-and-render pipeline rather than the
 * filterTokenErrors() bypass path specifically. The bypass logic for uri
 * fields (TOKEN_REGEX, LINKIT_REGEX) is covered by unit-level regex tests
 * and remains available for environments that add custom uri validators.
 *
 * Future placeholder value classes (Linkit metadata, Drupal media placeholders,
 * mixed-content embeddings) should add test methods to this class following
 * the same structure, annotated with @group patternkit_token_validation.
 *
 * @group patternkit
 * @group patternkit_token_validation
 * @see https://www.drupal.org/project/patternkit/issues/3585847
 */
class PlaceholderValueValidationTest extends PatternkitBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_test',
  ];

  /**
   * Display name of the render_validation_test pattern in Layout Builder.
   */
  const RENDER_VALIDATION_TEST_PATTERN = '[Patternkit] Render validation test';

  /**
   * The test node whose layout is used across all test methods.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $testNode;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // Capture the node created by the parent setUp so we can build its URL
    // reliably rather than assuming a fixed NID.
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'bundle_with_layout_enabled']);
    $this->testNode = reset($nodes);
  }

  /**
   * Fills format-constrained fields with valid values to prevent interference.
   *
   * The render_validation_test fixture includes hostname, ipv4, and
   * date_pattern fields that generate blocking errors when empty: hostname and
   * ipv4 produce
   * format errors, date_pattern produces a pattern error. Tests targeting a
   * specific field must fill the remaining constrained fields with valid values
   * so the only errors present are the ones under test.
   *
   * Uses fillField() to ensure Mink triggers proper browser focus and change
   * events so JSON Editor's internal model is updated before submit.
   *
   * @param array $exclude
   *   Field names (root[...] input selectors without the outer brackets) to
   *   skip — typically the field the calling test is actively exercising.
   */
  protected function fillSupportingFields(array $exclude = []): void {
    $page = $this->getSession()->getPage();
    $defaults = [
      'hostname' => 'example.com',
      'ipv4' => '127.0.0.1',
      'date_pattern' => '2024-01-01',
    ];
    foreach ($defaults as $field => $value) {
      if (!in_array($field, $exclude, TRUE)) {
        $page->fillField('root[' . $field . ']', $value);
      }
    }
  }

  /**
   * Confirms the render_validation_test pattern form submits with valid values.
   *
   * This is a control test: it verifies that the pattern block can be added
   * successfully when all format-constrained fields contain valid values.
   * Passing this test establishes that the Layout Builder AJAX submission flow
   * works for this pattern, isolating token-specific failures in the "allowed"
   * tests below.
   */
  public function testValidValuesAllowSubmission(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Confirm the block rendered: the hostname value 'example.com' set via
    // fillSupportingFields() is output directly by the Twig template.
    $this->drupalGet('node/' . $this->testNode->id());
    $assert->pageTextContains('example.com');
  }

  /**
   * Tests that a bare Drupal token passes the client-side submit gate.
   *
   * A token value in a format: hostname field is blocked by JSON Editor's
   * ipValidator under normal circumstances. filterTokenErrors() must suppress
   * that error so the AJAX submission can proceed and the block is saved.
   */
  public function testBareTokenAllowedInHostnameField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    // Fill other format-constrained fields with valid values so their errors
    // do not interfere with the hostname token bypass being tested.
    $this->fillSupportingFields(['hostname']);
    $page->fillField('root[hostname]', '[site:name]');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Confirm the block rendered on the node page. The token is resolved by
    // Drupal's TokenProcessor during render, so we assert the block container
    // is present rather than the raw token string.
    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test');
  }

  /**
   * Tests that a bare Drupal token saves in a format: uri field end-to-end.
   *
   * JSON Editor does not validate format: uri natively; this test confirms the
   * save-and-render pipeline succeeds for a token in a uri field. The
   * TOKEN_REGEX bypass in filterTokenErrors() is available for environments
   * that add custom uri validators but is not exercised by this fixture.
   */
  public function testBareTokenAllowedInUrlField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->fillField('root[url]', '[node:url]');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Confirm the block rendered on the node page. The raw token string is
    // resolved by TokenProcessor during render, so we assert the block
    // container is present rather than the literal token string.
    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test');
  }

  /**
   * Tests that a bare Drupal token saves in a format: email field end-to-end.
   *
   * JSON Editor does not validate format: email natively; this test confirms
   * the save-and-render pipeline succeeds for a token in an email field.
   */
  public function testBareTokenAllowedInEmailField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->fillField('root[email]', '[user:mail]');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // Confirm the block rendered on the node page. The raw token string is
    // resolved by TokenProcessor during render, so we assert the block
    // container is present rather than the literal token string.
    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test');
  }

  /**
   * Tests that a bare Drupal token saves in a format: date-time field.
   *
   * JSON Editor does not validate format: date-time natively (it validates
   * datetime-local, not date-time); this test confirms the save-and-render
   * pipeline succeeds for a token in a date-time field.
   */
  public function testBareTokenAllowedInDateTimeField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->fillField('root[date_time]', '[node:created]');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test');
  }

  /**
   * Tests that an invalid non-token value remains blocked in a hostname field.
   *
   * Confirms that filterTokenErrors() does not broadly suppress all validation
   * errors — only those whose field value matches the bare token regex.
   */
  public function testInvalidValueBlockedInHostnameField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    // Fill supporting fields so the only blocking error is from the hostname
    // field under test, making this assertion targeted rather than incidental.
    // Use underscore — invalid in DNS hostnames (RFC 1123), so JSON Editor
    // hostname validator rejects the value.
    $this->fillSupportingFields(['hostname']);
    $page->fillField('root[hostname]', 'not_a_hostname');
    $page->pressButton('Add block');

    // Client-side validation blocks the submission; wait for JSON Editor's
    // inline error and confirm the dialog is still open.
    $assert->waitForElement('css', '.je-error-msg', 3000);
    $assert->elementExists('css', '#drupal-off-canvas');
  }

  /**
   * Tests that a bare token in a pattern-constrained field remains blocked.
   *
   * FilterTokenErrors() suppresses only format errors, not pattern errors.
   * The render_validation_test fixture's date_pattern field uses a regex
   * pattern constraint (not a format keyword). A token value therefore
   * generates a pattern error that filterTokenErrors() must not remove.
   *
   * Confirms the known limitation documented in AC5: tokens in pattern-
   * constrained fields are not exempt from client-side validation.
   */
  public function testBareTokenBlockedInPatternConstrainedField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    // Fill hostname and ipv4 with valid values; leave date_pattern for the
    // token under test. The date_pattern field uses a regex pattern constraint
    // (e.g. YYYY-MM-DD), not a format keyword, so an empty value produces a
    // pattern error that also blocks — exclude it here to isolate the test.
    $this->fillSupportingFields(['date_pattern']);
    $page->fillField('root[date_pattern]', '[node:created]');
    $page->pressButton('Add block');

    $assert->waitForElement('css', '.je-error-msg', 3000);
    $assert->elementExists('css', '#drupal-off-canvas');
  }

  /**
   * Tests that a bare Drupal token saves in a nested format: uri field.
   *
   * The render_validation_test fixture includes a link object whose href
   * property is format: uri. JSON Editor represents this as a nested input at
   * root[link][href]. This test confirms the save-and-render pipeline succeeds
   * for a token at nested path depth — the path-to-input-name mapping is
   * exercised even though JSON Editor does not validate format: uri natively.
   *
   * Covers Drupal tokens in a nested uri field (link.href).
   */
  public function testBareTokenAllowedInNestedLinkHref(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    // Fill required supporting fields; link[href] is what is under test.
    $this->fillSupportingFields();
    $page->fillField('root[link][href]', '[node:url]');
    $page->fillField('root[link][text]', 'Read more');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    // After save the block renders: assert the link element is present.
    // Token is resolved by Drupal's TokenProcessor during render.
    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test__link');
  }

  /**
   * Tests that a Linkit double-bracket value saves in a format: uri field.
   *
   * Confirms a canonical Linkit metadata value [[url-nid:NID;title:TEXT]] can
   * be entered in a format: uri field and the block saves and renders. JSON
   * Editor does not validate format: uri natively, so no format error is
   * generated; the LINKIT_REGEX bypass in filterTokenErrors() is available for
   * environments with custom uri validators. The regex itself is covered by
   * unit-level tests.
   *
   * Covers canonical Linkit double-bracket values in a top-level uri field.
   */
  public function testLinkitValueAllowedInUrlField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    // Single-word title — exercises the base LINKIT_REGEX match.
    $this->fillSupportingFields();
    $page->fillField('root[url]', '[[url-nid:42;title:Read more]]');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test');
  }

  /**
   * Tests that a Linkit value with spaces in the title saves end-to-end.
   *
   * Confirms [[url-nid:NID;title:TEXT WITH SPACES]] saves in a format: uri
   * field and the block renders. Documents that spaces in the Linkit title are
   * acceptable in the save pipeline; LINKIT_REGEX's [^\]]+ character class
   * allows spaces and is covered by unit-level regex tests.
   *
   * Covers LINKIT_REGEX title matching for values with internal whitespace.
   */
  public function testLinkitValueWithSpacesInTitleAllowedInUrlField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->fillField('root[url]', '[[url-nid:42;title:Visit the about page]]');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test');
  }

  /**
   * Tests that a Linkit value saves in a nested format: uri field end-to-end.
   *
   * The render_validation_test fixture includes a link object whose href
   * property is format: uri. Confirms a Linkit value saves at nested path
   * depth (root[link][href]) and the block renders. JSON Editor does not
   * validate format: uri natively; the LINKIT_REGEX bypass path is available
   * for environments with custom validators.
   *
   * Covers Linkit double-bracket values in a nested uri field (link.href).
   */
  public function testLinkitValueAllowedInNestedLinkHref(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->fillField('root[link][href]', '[[url-nid:42;title:Read more]]');
    $page->fillField('root[link][text]', 'Read more');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test__link');
  }

  /**
   * Tests that an unbracketed token-like value remains blocked.
   *
   * Confirms that a colon-separated string without brackets (e.g. "node:url")
   * does not match the bare token regex and is therefore not exempted from
   * format validation.
   */
  public function testTokenLikeUnbracketedBlockedInHostnameField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    // Fill supporting fields so the only blocking error is from the hostname
    // field under test.
    $this->fillSupportingFields(['hostname']);
    $page->fillField('root[hostname]', 'node:url');
    $page->pressButton('Add block');

    $assert->waitForElement('css', '.je-error-msg', 3000);
    $assert->elementExists('css', '#drupal-off-canvas');
  }

  /**
   * Tests mixed token content saves in an unconstrained plain string field.
   *
   * Uses the render_validation_test fixture's label field (type: string,
   * no format constraint) to verify mixed token content baseline behavior.
   */
  public function testMixedTokenContentAllowedInPlainLabelField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->fillField('root[label]', 'prefix [node:title] suffix');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test__label');
    $assert->pageTextContains('prefix');
    $assert->pageTextContains('suffix');
  }

  /**
   * Tests mixed token content saves in an HTML-rich text field.
   *
   * Uses a dedicated format: html fixture field for mixed-content coverage.
   */
  public function testMixedTokenContentAllowedInHtmlRichTextField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->fillField('root[rich_text_html]', '<p>prefix [node:title] suffix</p>');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test__rich-text-html');
    $assert->pageTextContains('prefix');
    $assert->pageTextContains('suffix');
  }

  /**
   * Tests malformed mixed token-like value baseline in plain string field.
   *
   * No dedicated validator is expected for this malformed mixed-value baseline.
   */
  public function testMalformedMixedTokenLikeValueAllowedInPlainLabelField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->fillField('root[label]', 'prefix [node title] suffix');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    $this->drupalGet('node/' . $this->testNode->id());
    $assert->pageTextContains('prefix [node title] suffix');
  }

  /**
   * Tests malformed mixed token-like value baseline in HTML-rich field.
   *
   * No dedicated validator is expected for this malformed html-rich baseline.
   */
  public function testMalformedMixedTokenLikeValueAllowedInHtmlRichTextField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields();
    $page->fillField('root[rich_text_html]', '<p>prefix [node title] suffix</p>');
    $page->pressButton('Add block');
    $assert->assertWaitOnAjaxRequest();
    $this->assertSaveLayout();

    $this->drupalGet('node/' . $this->testNode->id());
    $assert->elementExists('css', '.render-validation-test__rich-text-html');
    $assert->pageTextContains('prefix');
    $assert->pageTextContains('suffix');
  }

  /**
   * Tests mixed token content is blocked in hostname field edge case.
   *
   * Documents the unsupported format-constrained edge behavior.
   */
  public function testMixedTokenContentBlockedInHostnameField(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $this->testNode->id() . '/layout');
    $this->addPatternBlock(static::RENDER_VALIDATION_TEST_PATTERN);
    $this->waitForJsonEditorForm();

    $this->fillSupportingFields(['hostname']);
    $page->fillField('root[hostname]', 'prefix [site:name] suffix');
    $page->pressButton('Add block');

    $assert->waitForElement('css', '.je-error-msg', 3000);
    $assert->elementExists('css', '#drupal-off-canvas');
  }

}
