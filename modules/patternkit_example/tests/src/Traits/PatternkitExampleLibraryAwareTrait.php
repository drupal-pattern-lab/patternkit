<?php

namespace Drupal\Tests\patternkit_example\Traits;

/**
 * A helper trait for tests to validate against known library components.
 *
 * This trait is only intended for usage in tests.
 */
trait PatternkitExampleLibraryAwareTrait {

  /**
   * The pattern namespace for the example library.
   *
   * @var string
   */
  protected string $namespace = '@patternkit';

  /**
   * A collection of paths for patterns contained within the example library.
   *
   * @var string[]
   */
  protected array $examplePatterns = [
    'example' => 'atoms/example/src/example',
    'example_filtered' => 'atoms/example_filtered/src/example_filtered',
    'example_filtered_multiple' => 'atoms/example_filtered_multiple/src/example_filtered_multiple',
    'example_ref' => 'atoms/example_ref/src/example_ref',
  ];

  /**
   * Get paths for all the patterns within the example library.
   *
   * @return string[]
   *   An array of pattern paths within the example library folder.
   */
  protected function getPatternPaths(): array {
    return $this->examplePatterns;
  }

  /**
   * Get the namespaced names of all patterns within the example library.
   *
   * @return string[]
   *   An array of pattern names within the example library.
   */
  protected function getPatternNames(): array {
    $patterns = [];
    foreach ($this->examplePatterns as $pattern_name) {
      $patterns[] = $this->namespace . '/' . $pattern_name;
    }

    return $patterns;
  }

  /**
   * Get the assembled library information expected for tests.
   *
   * @param string $root
   *   The path to the library files. This will be prefixed in front of all
   *   pattern file paths.
   * @param array $defaults
   *   (Optional) A set of default values to configure on every pattern unless
   *   specifically set for a given pattern.
   */
  protected function getExpectedLibraryInformation(string $root, array $defaults = []): array {
    $root = $root ?? $this->vfsRoot->url();
    $defaults = $defaults + [
      'category' => 'atom',
      'library' => 'patternkit',
      'libraryPluginId' => 'twig',
      'version' => FALSE,
    ];

    $expected = [
      'atoms/example/src/example' => [
        'name' => 'Example',
        'title' => 'Example',
        'assets' => [
          'twig' => $root . '/atoms/example/src/example.twig',
          'json' => $root . '/atoms/example/src/example.json',
        ],
        'path' => 'atoms/example/src/example',
      ] + $defaults,
      'atoms/example_filtered/src/example_filtered' => [
        'name' => 'Example with filtered content',
        'title' => 'Example with filtered content',
        'assets' => [
          'twig' => $root . '/atoms/example_filtered/src/example_filtered.twig',
          'json' => $root . '/atoms/example_filtered/src/example_filtered.json',
        ],
        'path' => 'atoms/example_filtered/src/example_filtered',
      ] + $defaults,
      'atoms/example_ref/src/example_ref' => [
        'name' => 'Example with Reference',
        'title' => 'Example with Reference',
        'assets' => [
          'twig' => $root . '/atoms/example_ref/src/example_ref.twig',
          'json' => $root . '/atoms/example_ref/src/example_ref.json',
        ],
        'path' => 'atoms/example_ref/src/example_ref',
      ] + $defaults,
      'atoms/example_filtered_multiple/src/example_filtered_multiple' => [
        'name' => 'Example with multiple filtered content blocks',
        'title' => 'Example with multiple filtered content blocks',
        'assets' => [
          'twig' => $root . '/atoms/example_filtered_multiple/src/example_filtered_multiple.twig',
          'json' => $root . '/atoms/example_filtered_multiple/src/example_filtered_multiple.json',
        ],
        'path' => 'atoms/example_filtered_multiple/src/example_filtered_multiple',
      ] + $defaults,
    ];

    return $expected;
  }

  /**
   * Get a list of expected files for discovery.
   *
   * @param string $root
   *   The file path to prefix for all pattern files.
   * @param string[] $extensions
   *   (Optional) An array of file extensions to include in the list for each
   *   pattern. Defaults to 'json' and 'twig'.
   *
   * @return array
   *   An array of expected files for each known pattern.
   */
  protected function getExpectedFiles(string $root, array $extensions = [
    'json',
    'twig',
  ]): array {
    $expected = [];
    foreach ($this->examplePatterns as $name => $pattern_path) {
      $pattern = [];
      foreach ($extensions as $ext) {
        $pattern[$ext] = "$root/$pattern_path.$ext";
      }
      $expected[$name] = $pattern;
    }

    return $expected;
  }

}
