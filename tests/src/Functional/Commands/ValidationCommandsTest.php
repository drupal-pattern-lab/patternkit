<?php

namespace Drupal\Tests\patternkit\Functional\Commands;

use Drupal\Tests\patternkit\Functional\PatternkitBrowserTestBase;
use Drupal\Tests\patternkit\Traits\PatternkitDrushTestTrait;
use Drupal\Tests\patternkit\Traits\SchemaFixtureTrait;
use Drush\Commands\DrushCommands;

/**
 * Functional tests for the patternkit:validate Drush command.
 *
 * @group patternkit
 * @coversDefaultClass \Drupal\patternkit\Commands\ValidationCommands
 */
class ValidationCommandsTest extends PatternkitBrowserTestBase {

  use PatternkitDrushTestTrait;
  use SchemaFixtureTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'patternkit_example',
  ];

  /**
   * Confirm expected warnings and failures for invalid option combinations.
   *
   * @param array<string, string> $options
   *   An associative array of command options with values for testing. The key
   *   is the option name, and the value is the provided option value.
   * @param string[] $expectedOutput
   *   An array of lines expected to be included in command output. All lines
   *   will be trimmed before comparison, so leading and trailing whitespace
   *   are irrelevant.
   *
   * @dataProvider providerTestInvalidOptionCombinations
   */
  public function testInvalidOptionCombinations(array $options, array $expectedOutput): void {
    $this->drush('patternkit:validate', options: $options, expected_return: 1);
    $this->assertErrorOutputContainsLines($expectedOutput);
  }

  /**
   * Data provider for testInvalidOptionCombinations().
   */
  public static function providerTestInvalidOptionCombinations(): array {
    return [
      'missing schema source' => [
        [
          'content' => '{ "text": "My text value" }',
        ],
        [
          '[error] Schema data for validation must be provided. At least one of the "--pattern", "--schema", or "--schema-file" options is required.',
          '[error] Not enough data was provided.',
        ],
      ],
      'missing content source' => [
        [
          'schema' => '{}',
        ],
        [
          '[error] Content data for validation must be provided. At least one of the "--content" or "--content-file" options is required.',
          '[error] Not enough data was provided.',
        ],
      ],
      'conflicting schema source (pattern and schema)' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'schema' => '{}',
        ],
        [
          '[warning] The "--pattern" and "--schema" options may not be used together. The schema data from the pattern definition will be used.',
          '[error] Content data for validation must be provided. At least one of the "--content" or "--content-file" options is required.',
          '[error] Not enough data was provided.',
        ],
      ],
      'conflicting schema source (pattern and schema-file)' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'schema-file' => 'modules/custom/patternkit/tests/fixtures/schema/required_content.json',
        ],
        [
          '[warning] The "--pattern" and "--schema-file" options may not be used together. The schema data from the pattern definition will be used.',
          '[error] Content data for validation must be provided. At least one of the "--content" or "--content-file" options is required.',
          '[error] Not enough data was provided.',
        ],
      ],
      'conflicting schema source (schema and schema-file)' => [
        [
          'schema' => '{}',
          'schema-file' => 'modules/custom/patternkit/tests/fixtures/schema/required_content.json',
        ],
        [
          '[warning] The "--schema" and "--schema-file" options may not be used together. The schema data from the "--schema" option will be used.',
          '[error] Content data for validation must be provided. At least one of the "--content" or "--content-file" options is required.',
          '[error] Not enough data was provided.',
        ],
      ],
      'conflicting content source (content and content-file)' => [
        [
          'content' => '{}',
          'content-file' => 'modules/custom/patternkit/tests/fixtures/content/required_content__valid.json',
        ],
        [
          '[warning] The "--content" and "--content-file" options may not be used together. The content data from the "--content" option will be used.',
          '[error] Schema data for validation must be provided. At least one of the "--pattern", "--schema", or "--schema-file" options is required.',
          '[error] Not enough data was provided.',
        ],
      ],
      'unknown pattern name' => [
        [
          'pattern' => '@unknown/pattern',
          'content' => '{}',
        ],
        [
          '[error] Unable to find pattern: @unknown/pattern',
        ],
      ],
      'invalid JSON for schema option' => [
        [
          'schema' => '{"json": invalid',
          'content' => '{}',
        ],
        [
          '[error] Schema data for validation must be a valid JSON object. Invalid value provided for --schema option.',
        ],
      ],
      'invalid JSON for content option' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'content' => '{"json": invalid',
        ],
        [
          '[error] Content data for validation must be a valid JSON object. Invalid value provided for --content option.',
        ],
      ],
    ];
  }

  /**
   * Test command results with provided input options.
   *
   * @param array<string, string> $options
   *   An associative array of command options with values for testing. The key
   *   is the option name, and the value is the provided option value.
   * @param int $expectedResult
   *   The Drush return code expected for the result.
   * @param array|null $expectedErrorOutput
   *   (Optional) If provided, specific lines of error output will be searched
   *   for from the command.
   *
   * @dataProvider providerTestValidateCommand
   */
  public function testValidateCommand(
    array $options,
    int $expectedResult,
    ?array $expectedErrorOutput = NULL,
  ): void {
    $this->drush(
      'patternkit:validate',
      options: $options,
      expected_return: $expectedResult,
    );

    // Expect successful validation.
    if ($expectedResult === DrushCommands::EXIT_SUCCESS) {
      $this->assertOutputEquals('[OK] Content validated successfully.');
      // Expect no error messages to be output on success.
      $this->assertErrorOutputEquals('');
    }
    // Expect command completed without error, but validation failed.
    elseif ($expectedResult === DrushCommands::EXIT_FAILURE_WITH_CLARITY) {
      // Assume default output and behavior if no separate error output is
      // defined.
      if ($expectedErrorOutput === NULL) {
        $this->assertOutputEquals('[ERROR] Content failed to validate.');
      }
      // Expect validation failure details to be output, so the error output
      // should not be empty.
      $this->assertNotEmpty($this->getSimplifiedErrorOutput());
    }
    // Expect a failure during execution prior to validation completing.
    elseif ($expectedResult === DrushCommands::EXIT_FAILURE) {
      if (is_array($expectedErrorOutput)) {
        $this->assertErrorOutputContainsLines($expectedErrorOutput);
      }
    }
    else {
      $this->fail('Unknown exit code expected: ' . $expectedResult);
    }
  }

  /**
   * Data provider for testValidateCommand().
   */
  public static function providerTestValidateCommand(): array {
    $fixture_path = 'modules/custom/patternkit/tests/fixtures/';
    $root = static::getDrupalRoot();

    return [
      'valid schema content' => [
        [
          'schema' => static::loadFixture('schemas/required_content.json'),
          'content' => static::loadFixture('content/required_content__valid.json'),
        ],
        DrushCommands::EXIT_SUCCESS,
      ],
      'invalid schema content' => [
        [
          'schema' => static::loadFixture('schemas/required_content.json'),
          'content' => static::loadFixture('content/required_content__invalid.json'),
        ],
        DrushCommands::EXIT_FAILURE_WITH_CLARITY,
      ],
      'valid schema-file content' => [
        [
          'schema-file' => $fixture_path . 'schemas/required_content.json',
          'content' => static::loadFixture('content/required_content__valid.json'),
        ],
        DrushCommands::EXIT_SUCCESS,
      ],
      'invalid schema-file content' => [
        [
          'schema-file' => $fixture_path . 'schemas/required_content.json',
          'content' => static::loadFixture('content/required_content__invalid.json'),
        ],
        DrushCommands::EXIT_FAILURE_WITH_CLARITY,
      ],
      'valid content-file content with schema-file' => [
        [
          'schema-file' => $fixture_path . 'schemas/required_content.json',
          'content-file' => $fixture_path . 'content/required_content__valid.json',
        ],
        DrushCommands::EXIT_SUCCESS,
      ],
      'invalid content-file content with schema-file' => [
        [
          'schema-file' => $fixture_path . 'schemas/required_content.json',
          'content-file' => $fixture_path . 'content/required_content__invalid.json',
        ],
        DrushCommands::EXIT_FAILURE_WITH_CLARITY,
      ],
      'valid content-file content with schema string' => [
        [
          'schema' => static::loadFixture('schemas/required_content.json'),
          'content-file' => $fixture_path . 'content/required_content__valid.json',
        ],
        DrushCommands::EXIT_SUCCESS,
      ],
      'pattern with valid content-file content' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'content-file' => $fixture_path . 'content/required_content__valid.json',
        ],
        DrushCommands::EXIT_SUCCESS,
      ],
      'pattern with invalid content-file content' => [
        [
          'pattern' => '@patternkit/components/media_combined_embed/media_combined_embed',
          'content-file' => $fixture_path . 'content/required_content__invalid.json',
        ],
        DrushCommands::EXIT_FAILURE_WITH_CLARITY,
      ],
      'pattern with "tolerateStrings" number conversion' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'content' => '{ "text": 123 }',
        ],
        DrushCommands::EXIT_FAILURE_WITH_CLARITY,
      ],
      'pattern with missing required content' => [
        [
          'pattern' => '@patternkit/components/media_combined_embed/media_combined_embed',
          'content' => '{"text": ""}',
        ],
        DrushCommands::EXIT_FAILURE_WITH_CLARITY,
      ],
      'valid content with "tolerateStrings" number conversion' => [
        [
          'schema' => '{"properties": { "text": { "type": "string" } }, "required": ["text"]}',
          'content' => '{"text": 123}',
        ],
        DrushCommands::EXIT_SUCCESS,
      ],
      'empty string against pattern schema' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'content' => '""',
        ],
        DrushCommands::EXIT_FAILURE,
      ],
      'invalid JSON in content option value' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'content' => '{text: "missing quotes"}',
        ],
        DrushCommands::EXIT_FAILURE,
        [
          'Content data for validation must be a valid JSON object.',
        ],
      ],
      'invalid JSON in content option value against custom schema' => [
        [
          'schema' => '{"properties": { "text": { "type": "string" } } }',
          'content' => '{text: "missing quotes"}',
        ],
        DrushCommands::EXIT_FAILURE,
        [
          'Content data for validation must be a valid JSON object.',
        ],
      ],
      'valid content against a pattern' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'content' => '{"text": "my text"}',
        ],
        DrushCommands::EXIT_SUCCESS,
      ],
      'empty content object against a pattern' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'content' => '{}',
        ],
        DrushCommands::EXIT_SUCCESS,
      ],
      'extra fields against a pattern' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'content' => '{"text": "valid text", "extra": "unexpected"}',
        ],
        DrushCommands::EXIT_SUCCESS,
      ],
      'missing content-file against schema string' => [
        [
          'schema' => '{"properties": { "text": { "type": "string" } } }',
          'content-file' => 'my/missing/file',
        ],
        DrushCommands::EXIT_FAILURE,
        [
          "Unable to find content file: $root/my/missing/file",
        ],
      ],
      'missing content-file against schema file' => [
        [
          'schema-file' => $fixture_path . 'schemas/required_content.json',
          'content-file' => 'my/missing/file',
        ],
        DrushCommands::EXIT_FAILURE,
        [
          "Unable to find content file: $root/my/missing/file",
        ],
      ],
      'missing content-file against pattern' => [
        [
          'pattern' => '@patternkit/atoms/example/src/example',
          'content-file' => 'my/missing/file',
        ],
        DrushCommands::EXIT_FAILURE,
        [
          "Unable to find content file: $root/my/missing/file",
        ],
      ],
    ];
  }

}
