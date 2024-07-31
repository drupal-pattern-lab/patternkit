<?php

namespace Drupal\patternkit\Asset\PatternLibraryParser;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\MissingTemplateException;
use Drupal\patternkit\PatternLibrary;
use Webmozart\Assert\Assert;

/**
 * Parses a Twig pattern library collection into usable metadata.
 */
class TwigPatternLibraryParser extends JSONPatternLibraryParser {

  /**
   * {@inheritdoc}
   */
  protected const PLUGIN_ID = 'twig';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\patternkit\Exception\MissingTemplateException
   */
  public function fetchPatternAssets(PatternInterface $pattern): array {
    $assets = parent::fetchPatternAssets($pattern);

    // Test to ensure necessary assets were found.
    if (empty($assets['twig'])) {
      throw new MissingTemplateException(
        sprintf('Unable to find a suitable Twig template for pattern "%s"', $pattern->getAssetId()));
    }

    // Remap file extensions to purpose-based keys.
    $assets['template'] = $assets['twig'];

    return $assets;
  }

  /**
   * {@inheritdoc}
   */
  protected function processComponent(PatternLibrary $library, string $library_path, array $library_defaults, string $component_name, array $files, array &$exceptions): ?PatternInterface {
    // A twig file is required for this parser. Stop here if one isn't found.
    if (empty($files['twig']) || !file_exists($files['twig'])) {
      return NULL;
    }

    return parent::processComponent($library, $library_path, $library_defaults, $component_name, $files, $exceptions);
  }

  /**
   * {@inheritdoc}
   */
  protected function getPatternDefaults(PatternLibrary $library, string $library_path, string $component_name, array $files): array {
    Assert::keyExists($files, 'twig', 'All Twig components are expected to have a Twig file.');

    // Start from the JSON defaults.
    $defaults = parent::getPatternDefaults($library, $library_path, $component_name, $files);

    // Validate and record the twig template asset path.
    $json_asset = $defaults['assets']['json'];
    $twig_asset = substr($json_asset, 0, -strlen('json')) . 'twig';
    Assert::eq($files['twig'], "$this->root/$twig_asset",
      sprintf('Twig templates are required to be located within the ' .
        'same directory with the same name as JSON schemas. Expected to find ' .
        'a template at "%s".', "$this->root/$twig_asset"));
    $defaults['assets']['twig'] = $twig_asset;

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFileExtensions(PatternLibrary $library): array {
    return [
      'json',
      'twig',
    ];
  }

}
