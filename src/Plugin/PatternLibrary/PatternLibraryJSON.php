<?php

namespace Drupal\patternkit\Plugin\PatternLibrary;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\patternkit\Asset\PatternLibraryParserInterface;
use Drupal\patternkit\Attribute\PatternLibrary;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternFormBuilderInterface;
use Drupal\patternkit\PatternLibraryPluginDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides mechanisms for parsing and rendering a JSON library of patterns.
 */
#[PatternLibrary(
  id: 'json',
  label: new TranslatableMarkup('JSON'),
)]
class PatternLibraryJSON extends PatternLibraryPluginDefault implements ContainerFactoryPluginInterface {

  /**
   * Creates a new PatternLibraryJSON plugin instance.
   *
   * @param string $root
   *   The application root path.
   *   e.g. '/var/www/docroot'.
   * @param \Drupal\patternkit\Asset\PatternLibraryParserInterface $parser
   *   Pattern library parser service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Extension config retrieval.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Serialization service.
   * @param \Drupal\patternkit\PatternFormBuilderInterface $formBuilder
   *   The pattern form builder service.
   * @param array $configuration
   *   Config.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   */
  public function __construct(
    $root,
    PatternLibraryParserInterface $parser,
    ConfigFactoryInterface $configFactory,
    protected SerializationInterface $serializer,
    protected PatternFormBuilderInterface $formBuilder,
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
  ) {
    parent::__construct(
      $root,
      $parser,
      $configFactory,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $container->getParameter('app.root'),
      $container->get('patternkit.asset.library.parser.json'),
      $container->get('config.factory'),
      $container->get('serialization.json'),
      $container->get('patternkit.jsoneditor.form_builder'),
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternEditor(PatternInterface $pattern, array $editor_config = []): array {
    return $this->formBuilder->buildEditorForm($pattern, $editor_config);
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $assets): array {
    $elements = [];
    foreach ($assets as $pattern) {
      $elements[] = [
        '#markup' => $pattern->config ?? [],
      ];
    }
    return $elements;
  }

}
