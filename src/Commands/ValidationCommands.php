<?php

namespace Drupal\patternkit\Commands;

use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\patternkit\Exception\UnknownPatternException;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\PatternValidationTrait;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Input\InputOption;

/**
 * Drush commands to test validation with Patternkit patterns.
 */
final class ValidationCommands extends DrushCommands {

  use PatternValidationTrait;

  /**
   * Constructs a ValidationCommands object.
   *
   * @param \Drupal\patternkit\PatternRepository $patternRepository
   *   The pattern repository service for loading pattern data.
   */
  public function __construct(
    protected readonly PatternRepository $patternRepository,
  ) {
    parent::__construct();
  }

  /**
   * Validate content against a designated pattern.
   *
   * @todo Add autocompletion for pattern names.
   */
  #[CLI\Command(name: 'patternkit:validate', aliases: ['pkv'])]
  #[CLI\Option(name: 'pattern', description: 'The name of the pattern to be validated against.')]
  #[CLI\Option(name: 'schema', description: 'A JSON string for the schema to be used for validation.')]
  #[CLI\Option(name: 'schema-file', description: 'The path to a file containing the schema to be used for validation.')]
  #[CLI\Option(name: 'content', description: 'A JSON string for the content to be tested for validity.')]
  #[CLI\Option(name: 'content-file', description: 'The path to a file containing the content to be tested for validity.')]
  #[CLI\Usage(name: 'drush patternkit:validate --pattern=\'@patternkit/atoms/example/src/example\' --content=\'{"text": "my text"}\'', description: 'Validate a content string against a pattern.')]
  #[CLI\Usage(name: 'drush patternkit:validate --schema=\'{"properties": { "text": { "type": "string" } }, "required": ["text"]}\' --content=\'{"text": "my text"}\'', description: 'Validate content against a given schema.')]
  #[CLI\Usage(name: 'drush patternkit:validate --pattern=\'@patternkit/atoms/example/src/example\' --content-file=\'path/to/my/file.json\'', description: 'Validate content in a JSON file against a pattern.')]
  #[CLI\Usage(name: 'drush patternkit:validate --schema-file=\'path/to/my/schema.json\' --content-file=\'path/to/my/file.json\'', description: 'Validate content in a JSON file against a specified schema file.')]
  public function validateCommand(
    $options = [
      'pattern' => InputOption::VALUE_REQUIRED,
      'schema' => InputOption::VALUE_REQUIRED,
      'schema-file' => InputOption::VALUE_REQUIRED,
      'content' => InputOption::VALUE_REQUIRED,
      'content-file' => InputOption::VALUE_REQUIRED,
    ],
  ): int {
    try {
      // Ensure all necessary data has been provided in a valid combination of
      // command options.
      $this->validateOptionCombinations($options);

      [
        'schema' => $schema,
        'content' => $content,
      ] = $this->parseOptions($options);
    }
    catch (CommandFailedException $exception) {
      return self::EXIT_FAILURE;
    }

    $isValid = FALSE;
    try {
      $isValid = $this->validateContent($schema, $content);
    }
    catch (\Throwable $e) {
      $this->logger()->error($e->getMessage());
    }

    if ($isValid) {
      $this->io()->success("Content validated successfully.");
    }
    else {
      $exceptions = $this->getExceptions();

      $this->io()->error('Content failed to validate.');
      foreach ($exceptions as $exception) {
        if ($exception instanceof SchemaValidationException) {
          $exception = $exception->getPrevious();
        }
        $this->logger()->error("\t" . $exception->getMessage());
      }
    }

    return $isValid ? self::EXIT_SUCCESS : self::EXIT_FAILURE_WITH_CLARITY;
  }

  /**
   * Validate and warn for provided option combinations as needed.
   *
   * @param array<string, mixed> $options
   *   An associative array of command options keyed by option name as provided
   *   by Drush.
   *
   * @throws \Drush\Exceptions\CommandFailedException
   *   Throws exception if invalid option combinations are provided.
   */
  protected function validateOptionCombinations(array &$options): void {
    // Warn for conflicting options with clearly designated priority.
    if (is_string($options['pattern']) && is_string($options['schema'])) {
      $this->logger()->warning('The "--pattern" and "--schema" options may not be used together. The schema data from the pattern definition will be used.');
      unset($options['schema']);
    }
    if (is_string($options['pattern']) && is_string($options['schema-file'])) {
      $this->logger()->warning('The "--pattern" and "--schema-file" options may not be used together. The schema data from the pattern definition will be used.');
      unset($options['schema-file']);
    }
    if (is_string($options['schema']) && is_string($options['schema-file'])) {
      $this->logger()->warning('The "--schema" and "--schema-file" options may not be used together. The schema data from the "--schema" option will be used.');
      unset($options['schema-file']);
    }
    if (is_string($options['content']) && is_string($options['content-file'])) {
      $this->logger()->warning('The "--content" and "--content-file" options may not be used together. The content data from the "--content" option will be used.');
      unset($options['content-file']);
    }

    // Fail if not enough configuration was provided.
    $fail = FALSE;
    if (!is_string($options['pattern']) && !is_string($options['schema']) && !is_string($options['schema-file'])) {
      $this->logger()->error('Schema data for validation must be provided. At least one of the "--pattern", "--schema", or "--schema-file" options is required.');
      $fail = TRUE;
    }
    if (!is_string($options['content']) && !is_string($options['content-file'])) {
      $this->logger()->error('Content data for validation must be provided. At least one of the "--content" or "--content-file" options is required.');
      $fail = TRUE;
    }

    if ($fail) {
      $this->logger()->error('Not enough data was provided.');
      throw new CommandFailedException('Not enough data was provided.');
    }
  }

  /**
   * Parse provided command options to identify validation components.
   *
   * @param array<string, mixed> $options
   *   An associative array of command options keyed by option name as provided
   *   by Drush.
   *
   * @return array{'pattern': \Drupal\patternkit\Entity\PatternInterface|null,'schema': String|null, 'schema-file': String|null, 'content': String|null, 'content-file': String|null }
   *   An associative array of the loaded content strings as parsed from
   *   provided options.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drush\Exceptions\CommandFailedException
   *
   * @todo Validate JSON provided in each option.
   */
  protected function parseOptions(array $options): array {
    $return = [
      'pattern' => NULL,
      'schema' => NULL,
      'schema-file' => NULL,
      'content' => NULL,
      'content-file' => NULL,
    ];

    // Filter to only our options with a value provided.
    $defined_options = array_intersect_key($options, $return);
    $defined_options = array_filter($defined_options, fn ($option) => !is_null($option));

    // Parse each option.
    foreach ($defined_options as $key => $option) {
      // Fail for empty string values in any option.
      if (!is_string($option) || $option === '') {
        $this->logger->error("Invalid value provided for --$key option.");
        throw new CommandFailedException("Invalid value provided for --$key option.");
      }

      switch ($key) {
        case 'pattern':
          try {
            $return['pattern'] = $patternEntity = $this->patternRepository->getPattern($option);
            $return['schema'] = $patternEntity->getSchema();
          }
          catch (UnknownPatternException) {
            $this->logger->error("Unable to find pattern: $option");
            throw new CommandFailedException('Unknown pattern: ' . $option);
          }
          break;

        case 'schema':
          if ($this->isValidJson($option)) {
            $return['schema'] = $option;
          }
          else {
            $this->logger()->error('Schema data for validation must be a valid JSON object. Invalid value provided for --schema option.');
            throw new CommandFailedException('Invalid value provided for --schema option.');
          }
          break;

        case 'schema-file':
          $file_path = DRUPAL_ROOT . '/' . $option;
          if (file_exists($file_path)) {
            $return['schema-file'] = $file_path;
            $return['schema'] = file_get_contents($file_path);
          }
          else {
            $this->logger->error("Unable to find schema file: $file_path");
            throw new CommandFailedException("Unable to find schema file: $file_path");
          }

          // Fail if the loaded schema is not a valid JSON structure.
          if (!$this->isValidJson($return['schema'])) {
            $this->logger()->error('Schema data for validation must be a valid JSON object. Content loaded from the schema file was not a valid JSON object.');
            throw new CommandFailedException('Content loaded from the schema file was not a valid JSON object.');
          }
          break;

        case 'content':
          // Fail if the loaded content is not a valid JSON structure.
          if ($this->isValidJson($option)) {
            $return['content'] = $option;
          }
          else {
            $this->logger()->error('Content data for validation must be a valid JSON object. Invalid value provided for --content option.');
            throw new CommandFailedException('Content in the --content option was not a valid JSON object.');
          }
          break;

        case 'content-file':
          $file_path = DRUPAL_ROOT . '/' . $option;
          if (file_exists($file_path)) {
            $return['content-file'] = $file_path;
            $return['content'] = file_get_contents($file_path);
          }
          else {
            $this->logger->error("Unable to find content file: $file_path");
            throw new CommandFailedException("Unable to find content file: $file_path");
          }

          // Fail if the loaded content is not a valid JSON structure.
          if (!$this->isValidJson($return['content'])) {
            $this->logger()->error('Content data for validation must be a valid JSON object. Content loaded from the content file was not a valid JSON object.');
            throw new CommandFailedException('Content loaded from the content file was not a valid JSON object.');
          }
          break;
      }
    }

    return $return;
  }

  /**
   * Test a JSON string to ensure it is valid and decode-able JSON.
   *
   * @param string $json
   *   The string value to be tested as JSON.
   *
   * @return bool
   *   True if the string is valid JSON, false otherwise.
   */
  private function isValidJson(string $json): bool {
    $decoded = json_decode($json);
    return json_last_error() === JSON_ERROR_NONE && is_object($decoded);
  }

}
