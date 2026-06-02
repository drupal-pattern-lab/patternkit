<?php

namespace Drupal\Tests\patternkit\Unit\Schema\DataPreProcessor;

use Drupal\Core\Utility\Token;
use Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Drupal\Tests\UnitTestCase;
use Swaggest\JsonSchema\Context;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;

/**
 * Tests TokenValidationDataPreProcessor via Swaggest's validation pipeline.
 *
 * Each test case calls $schema->in($data, $context) rather than calling
 * process() directly. This mirrors the real invocation path: Swaggest calls
 * the DataPreProcessor internally during in() validation, allowing the
 * preprocessor to strip format constraints before Swaggest validates.
 *
 * Observable behavior under test:
 *   - Token value in format-constrained field → validation passes (no throw).
 *   - Empty string in format-constrained field → validation passes (no throw).
 *   - Non-token, non-empty value in format-constrained field → fails (throws).
 *   - Any value in an unformatted field → validation passes.
 *
 * @group patternkit
 * @group patternkit_token_validation
 * @coversDefaultClass \Drupal\patternkit\Schema\DataPreProcessor\TokenValidationDataPreProcessor
 * @see https://www.drupal.org/project/patternkit/issues/3585847
 */
class TokenValidationDataPreProcessorTest extends UnitTestCase {

  use JsonDecodeTrait;

  /**
   * A prepared Schema context with the DataPreProcessor for testing.
   *
   * @var \Swaggest\JsonSchema\Context
   */
  protected Context $context;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Anonymous stub replaces a full Drupal Token service. It recognizes
    // [FAKE:TOKEN] as a valid token pattern (Token::scan() returns non-empty)
    // and treats all other strings as non-tokens (returns empty array).
    //
    // The stub also mirrors real Token::scan() behavior for Linkit
    // double-bracket values. Token::scan() matches the inner [url-nid:VALUE]
    // substring inside [[url-nid:NID;title:TEXT]] because the token regex
    // allows spaces in the value segment ([^\[\]]+). This stub reproduces
    // that detection so unit tests can cover the Linkit value class without
    // requiring a full kernel bootstrap.
    $token = new class extends Token {

      public function __construct() {
        // Skip parent constructor — it requires a fully-bootstrapped container.
      }

      /**
       * {@inheritdoc}
       */
      public function scan(mixed $text): array {
        if (str_contains($text, '[FAKE:TOKEN]')) {
          return ['FAKE' => ['TOKEN' => '[FAKE:TOKEN]']];
        }
        // Detect the inner [url-nid:VALUE] substring present in Linkit
        // double-bracket metadata values, mirroring real Token::scan()
        // behavior for the Linkit double-bracket value class.
        if (preg_match('/\[url-nid:[^\[\]]+\]/', $text)) {
          return ['url-nid' => []];
        }
        return [];
      }

    };

    $dataPreProcessor = new TokenValidationDataPreProcessor($token);
    $this->context = new Context();
    $this->context->setDataPreProcessor($dataPreProcessor);
  }

  /**
   * Tests that the preprocessor produces expected validation outcomes.
   *
   * Validation is exercised through Swaggest's in() pipeline so that the
   * DataPreProcessor is invoked as it would be in production.
   *
   * @dataProvider providerTokenBypassBehavior
   */
  public function testTokenBypassBehavior(string $data, string $schema_json, bool $expect_pass): void {
    $schema = Schema::import(static::decodeJson($schema_json));

    if (!$expect_pass) {
      $this->expectException(InvalidValue::class);
    }

    $result = $schema->in($data, $this->context);

    if ($expect_pass) {
      $this->assertSame($data, $result);
    }
  }

  /**
   * Data provider for testTokenBypassBehavior.
   *
   * @return array
   *   An array of test cases.
   */
  public static function providerTokenBypassBehavior(): array {
    $cases = [];

    // Case naming convention: {value_class}_{outcome}_in_{format}_field
    // Value classes: bare_token, plain_string
    // Outcome: passes (validation succeeds), fails (InvalidValue thrown)
    // [FAKE:TOKEN] is the representative bare Drupal token; kernel tests
    // use real Drupal token syntax (e.g. [node:url]).
    $schema_unformatted = '{ "type": "string" }';
    $schema_uri         = '{ "type": "string", "format": "uri" }';
    $schema_email       = '{ "type": "string", "format": "email" }';
    $schema_hostname    = '{ "type": "string", "format": "hostname" }';
    $schema_ipv4        = '{ "type": "string", "format": "ipv4" }';
    $schema_date        = '{ "type": "string", "format": "date" }';
    $schema_datetime    = '{ "type": "string", "format": "date-time" }';

    // --- Unformatted fields: always pass regardless of value ---
    $cases['plain_string_passes_in_unformatted_field'] = ['my_string', $schema_unformatted, TRUE];
    $cases['bare_token_passes_in_unformatted_field']   = ['[FAKE:TOKEN]', $schema_unformatted, TRUE];

    // --- URI format ---
    $cases['bare_token_passes_in_uri_field'] = ['[FAKE:TOKEN]', $schema_uri, TRUE];
    // A URI requires a scheme followed by ":" — a plain string without a
    // scheme is invalid. Note that "node:url" IS a valid URI (scheme=node,
    // path=url), so we use a string that cannot parse as a URI.
    $cases['plain_string_fails_in_uri_field'] = ['not a valid uri', $schema_uri, FALSE];

    // --- Email format ---
    $cases['bare_token_passes_in_email_field']   = ['[FAKE:TOKEN]', $schema_email, TRUE];
    $cases['invalid_value_fails_in_email_field'] = ['not-an-email', $schema_email, FALSE];

    // --- Hostname format ---
    $cases['bare_token_passes_in_hostname_field'] = ['[FAKE:TOKEN]', $schema_hostname, TRUE];
    // RFC 1123 hostnames may not contain underscores. Use an underscore to
    // reliably produce an invalid hostname across implementations.
    $cases['invalid_value_fails_in_hostname_field'] = ['not_a_hostname', $schema_hostname, FALSE];

    // --- IPv4 format ---
    $cases['bare_token_passes_in_ipv4_field']   = ['[FAKE:TOKEN]', $schema_ipv4, TRUE];
    $cases['invalid_value_fails_in_ipv4_field'] = ['not-an-ip', $schema_ipv4, FALSE];

    // --- Date format ---
    $cases['bare_token_passes_in_date_field']   = ['[FAKE:TOKEN]', $schema_date, TRUE];
    $cases['invalid_value_fails_in_date_field'] = ['not-a-date', $schema_date, FALSE];

    // --- Date-time format ---
    $cases['bare_token_passes_in_datetime_field']   = ['[FAKE:TOKEN]', $schema_datetime, TRUE];
    $cases['invalid_value_fails_in_datetime_field'] = ['not-a-datetime', $schema_datetime, FALSE];

    // --- Linkit double-bracket metadata bypass ---
    // Linkit stores node references in [[url-nid:NID;title:TEXT]] format in
    // format-constrained fields (typically format: uri). Token::scan() detects
    // the inner [url-nid:VALUE] substring, causing the format bypass to apply.
    // The stub's scan() mirrors this behavior for the url-nid token type.
    //
    // Only uri is tested here because Linkit values appear exclusively in
    // uri-constrained href/url fields. Server parity across all format keywords
    // is covered by kernel tests with real Token::scan().
    $cases['linkit_canonical_passes_in_uri_field'] = [
      '[[url-nid:42;title:Read more]]',
      $schema_uri,
      TRUE,
    ];
    $cases['linkit_long_title_passes_in_uri_field'] = [
      '[[url-nid:42;title:14 considerations for choosing the right AI model]]',
      $schema_uri,
      TRUE,
    ];
    $cases['linkit_quoted_title_passes_in_uri_field'] = [
      "[[url-nid:42;title:'Example with quotes']]",
      $schema_uri,
      TRUE,
    ];
    // [[url-nid:NID;title]] with no title value is also detected by
    // Token::scan() (the inner [url-nid:42;title] matches as a token).
    // Server-side bypass applies. Client-side narrow LINKIT_REGEX blocks this
    // variant (requires non-empty title text) — a documented edge case.
    $cases['linkit_malformed_no_title_value_passes_server_side'] = [
      '[[url-nid:42;title]]',
      $schema_uri,
      TRUE,
    ];

    // --- Empty string bypass ---
    // JSON Editor serializes unfilled optional fields as empty strings. Without
    // the bypass, Swaggest would reject "" as an invalid URI, email, etc. Each
    // case confirms the preprocessor clears the format constraint for "".
    $cases['empty_string_passes_in_uri_field']      = ['', $schema_uri, TRUE];
    $cases['empty_string_passes_in_email_field']    = ['', $schema_email, TRUE];
    $cases['empty_string_passes_in_hostname_field'] = ['', $schema_hostname, TRUE];
    $cases['empty_string_passes_in_ipv4_field']     = ['', $schema_ipv4, TRUE];
    $cases['empty_string_passes_in_date_field']     = ['', $schema_date, TRUE];
    $cases['empty_string_passes_in_datetime_field'] = ['', $schema_datetime, TRUE];

    return $cases;
  }

}
