<?php

namespace Drupal\Tests\patternkit\Kernel\Schema;

use Drupal\KernelTests\KernelTestBase;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Utility\PatternValidationHelper;
use Drupal\Tests\patternkit\Traits\JsonDecodeTrait;
use Swaggest\JsonSchema\Schema;

/**
 * Integration tests to confirm default context behaviors.
 *
 * @group patternkit
 * @group patternkit_token_validation
 * @coversNothing
 * @see https://www.drupal.org/project/patternkit/issues/3585847
 */
class ContextBuilderIntegrationTest extends KernelTestBase {

  use JsonDecodeTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit',
  ];

  /**
   * The context builder service.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder
   */
  protected ContextBuilder $contextBuilder;

  /**
   * The pattern validation helper service.
   *
   * @var \Drupal\patternkit\Utility\PatternValidationHelper
   */
  protected PatternValidationHelper $validationHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->contextBuilder = $this->container->get('patternkit.schema.context_builder');
    $this->validationHelper = $this->container->get('patternkit.helper.validation');
  }

  /**
   * Test schema validation against use cases needing the default preprocessors.
   *
   * @dataProvider providerDefaultContextSchemaValidation
   */
  public function testDefaultContextSchemaValidation(string $schema_json, $config): void {
    $default_context = $this->contextBuilder->getDefaultContext();
    $schema = Schema::import(static::decodeJson($schema_json), $default_context);

    $this->assertNotNull($schema->in($config, $default_context));
  }

  /**
   * Data provider for testDefaultContextSchemaValidation().
   *
   * Covers the full set of JSON Schema draft-04 format keywords that Swaggest
   * validates server-side, confirming that bare Drupal tokens pass the default
   * context (i.e. TokenValidationDataPreProcessor strips the format constraint
   * so Swaggest does not reject the value).
   *
   * Case naming convention: {value_class}_in_{format}_field
   */
  public static function providerDefaultContextSchemaValidation(): array {
    $cases = [];

    // Test object coercion requiring the 'objects' preprocessor.
    $object_json = <<<JSON
      {
        "type": "object",
        "properties": {
          "text": {
            "type": "string"
          }
        },
        "required": ["text"]
      }
      JSON;
    $cases['object_coercion'] = [
      $object_json,
      ['text' => 'my text'],
    ];

    // --- Bare token in each format-constrained field ---
    // Each case confirms that TokenValidationDataPreProcessor is wired for the
    // given format keyword in the default context. Real Drupal token syntax is
    // used here (unlike unit tests, no mock — Token::scan() runs against the
    // real token service in the kernel environment).
    $cases['bare_token_in_uri_field'] = [
      '{ "type": "object", "properties": { "uri_reference": { "type": "string", "format": "uri" } } }',
      (object) ['uri_reference' => '[node:url]'],
    ];

    $cases['bare_token_in_email_field'] = [
      '{ "type": "object", "properties": { "email_address": { "type": "string", "format": "email" } } }',
      (object) ['email_address' => '[user:mail]'],
    ];

    $cases['bare_token_in_hostname_field'] = [
      '{ "type": "object", "properties": { "hostname": { "type": "string", "format": "hostname" } } }',
      (object) ['hostname' => '[site:name]'],
    ];

    $cases['bare_token_in_ipv4_field'] = [
      '{ "type": "object", "properties": { "ip_address": { "type": "string", "format": "ipv4" } } }',
      (object) ['ip_address' => '[site:ip]'],
    ];

    $cases['bare_token_in_date_field'] = [
      '{ "type": "object", "properties": { "date_value": { "type": "string", "format": "date" } } }',
      (object) ['date_value' => '[node:created:date]'],
    ];

    $cases['bare_token_in_datetime_field'] = [
      '{ "type": "object", "properties": { "datetime_value": { "type": "string", "format": "date-time" } } }',
      (object) ['datetime_value' => '[node:created]'],
    ];

    // --- Linkit double-bracket metadata ---
    // Token::scan() detects [[url-nid:NID;title:TEXT]] values via the inner
    // [url-nid:VALUE] substring, so TokenValidationDataPreProcessor bypasses
    // format validation for Linkit values without any code change.
    $cases['linkit_canonical_in_uri_field'] = [
      '{ "type": "object", "properties": { "uri_reference": { "type": "string", "format": "uri" } } }',
      (object) ['uri_reference' => '[[url-nid:42;title:Read more]]'],
    ];

    $cases['linkit_long_title_in_uri_field'] = [
      '{ "type": "object", "properties": { "uri_reference": { "type": "string", "format": "uri" } } }',
      (object) ['uri_reference' => '[[url-nid:42;title:14 considerations for choosing the right AI model]]'],
    ];

    // --- Bare token in a nested uri field (link object path) ---
    // Confirms that TokenValidationDataPreProcessor applies to nested object
    // properties, not just top-level fields.
    $cases['bare_token_in_nested_uri_field'] = [
      <<<JSON
        {
          "type": "object",
          "properties": {
            "link": {
              "type": "object",
              "properties": {
                "href": { "type": "string", "format": "uri" },
                "text": { "type": "string" }
              }
            }
          }
        }
        JSON,
      (object) ['link' => (object) ['href' => '[node:url]', 'text' => 'Read more']],
    ];

    return $cases;
  }

  /**
   * Tests validateContent() against the render_validation_test fixture schema.
   *
   * This covers the JsonEditorForm::validatePatternConfig() code path:
   * PatternValidationHelper::validateContent() receives a full object-schema
   * JSON string and a JSON-encoded config object (with associative array
   * semantics) — the same way the form element validates submitted data.
   *
   * Scenarios verified:
   *   - Token in a format field with other format fields left empty (the
   *     primary reported failure mode: "Missing scheme in URI").
   *   - Tokens in all format-constrained fields.
   *   - All valid non-token values.
   *
   * @dataProvider providerFullObjectSchemaValidation
   */
  public function testFullObjectSchemaValidation(string $label, array $config, bool $expected_valid): void {
    $schema_json = file_get_contents(__DIR__ . '/../../../modules/patternkit_test/lib/patternkit_test/components/render_validation_test/render_validation_test.json');
    $this->assertNotFalse($schema_json, 'Test fixture schema file must exist.');

    $config_json = json_encode($config);
    $this->validationHelper->clearExceptions();
    $valid = $this->validationHelper
      ->validateContent($schema_json, $config_json);

    $this->assertSame($expected_valid, $valid, $label);
  }

  /**
   * Data provider for testFullObjectSchemaValidation().
   *
   * @return array
   *   An array of [label, config, expected_valid] test cases.
   */
  public static function providerFullObjectSchemaValidation(): array {
    return [
      'all_valid_values' => [
        'All valid values pass',
        [
          'label' => 'Test',
          'url' => 'https://example.com',
          'email' => 'user@example.com',
          'hostname' => 'example.com',
          'ipv4' => '127.0.0.1',
          'date_time' => '2024-01-01T00:00:00Z',
          'date_pattern' => '2024-01-01',
        ],
        TRUE,
      ],
      'token_in_hostname_empty_other_format_fields' => [
        'Token in hostname with other format fields empty passes',
        [
          'hostname' => '[site:name]',
          'url' => '',
          'email' => '',
          'date_time' => '',
          'ipv4' => '127.0.0.1',
          'date_pattern' => '2024-01-01',
        ],
        TRUE,
      ],
      'tokens_in_all_format_fields' => [
        'Tokens in all format-constrained fields pass',
        [
          'url' => '[site:url]',
          'email' => '[user:mail]',
          'hostname' => '[site:name]',
          'ipv4' => '[site:name]',
          'date_time' => '[node:created]',
          'date_pattern' => '2024-01-01',
        ],
        TRUE,
      ],
      'token_in_hostname_absent_other_fields' => [
        'Token in hostname with other format fields absent passes',
        [
          'hostname' => '[site:name]',
          'ipv4' => '127.0.0.1',
          'date_pattern' => '2024-01-01',
        ],
        TRUE,
      ],
      'all_format_fields_empty' => [
        'All optional format-constrained fields empty passes',
        [
          'url' => '',
          'email' => '',
          'hostname' => '',
          'ipv4' => '',
          'date_time' => '',
          // date_pattern is a regex pattern constraint, not a format keyword.
          // The empty-string bypass in TokenValidationDataPreProcessor only
          // applies to format-constrained fields. A valid date is required.
          'date_pattern' => '2024-01-01',
        ],
        TRUE,
      ],
      'linkit_canonical_in_url_field' => [
        'Linkit canonical value in url field passes (server bypass via Token::scan)',
        [
          'url' => '[[url-nid:42;title:Read more]]',
          'ipv4' => '127.0.0.1',
          'date_pattern' => '2024-01-01',
        ],
        TRUE,
      ],
      'linkit_canonical_in_nested_link_href' => [
        'Linkit canonical value in nested link href passes (nested object path)',
        [
          'link' => [
            'href' => '[[url-nid:42;title:Read more]]',
            'text' => 'Read more',
          ],
          'ipv4' => '127.0.0.1',
          'date_pattern' => '2024-01-01',
        ],
        TRUE,
      ],
      'token_in_nested_link_href' => [
        'Token in nested link href passes (nested object path)',
        [
          'link' => [
            'href' => '[node:url]',
            'text' => 'Read more',
          ],
          'ipv4' => '127.0.0.1',
          'date_pattern' => '2024-01-01',
        ],
        TRUE,
      ],
      'empty_nested_link_href' => [
        'Empty nested link href passes (empty-string bypass applies to nested objects)',
        [
          'link' => [
            'href' => '',
            'text' => '',
          ],
          'ipv4' => '127.0.0.1',
          'date_pattern' => '2024-01-01',
        ],
        TRUE,
      ],
    ];
  }

}
