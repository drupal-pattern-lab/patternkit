<?php

namespace Drupal\patternkit\Utility;

use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternRepository;
use Drupal\patternkit\Schema\ContextBuilder;
use Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection;
use Drupal\patternkit\Schema\DataPreProcessorFactory;
use Drupal\patternkit\Schema\SchemaFactory;

/**
 * A service for identifying patterns nested within the content of a pattern.
 */
class PatternDetector {

  /**
   * The pattern repository service.
   *
   * @var \Drupal\patternkit\PatternRepository
   */
  protected PatternRepository $patternRepository;

  /**
   * The schema factory service.
   *
   * @var \Drupal\patternkit\Schema\SchemaFactory
   */
  protected SchemaFactory $schemaFactory;

  /**
   * The schema context builder service.
   *
   * @var \Drupal\patternkit\Schema\ContextBuilder
   */
  protected ContextBuilder $contextBuilder;

  /**
   * The data preprocessor factory service.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessorFactory
   */
  protected DataPreProcessorFactory $preProcessorFactory;

  /**
   * The collection of default schema data preprocessors.
   *
   * @var \Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection
   */
  protected DataPreProcessorCollection $defaultProcessors;

  /**
   * Constructs a PatternDetector object.
   *
   * @param \Drupal\patternkit\PatternRepository $repository
   *   The pattern repository service.
   * @param \Drupal\patternkit\Schema\SchemaFactory $schema_factory
   *   The schema factory service.
   * @param \Drupal\patternkit\Schema\ContextBuilder $context_builder
   *   The schema context builder service.
   * @param \Drupal\patternkit\Schema\DataPreProcessorFactory $factory
   *   The data preprocessor factory service.
   * @param \Drupal\patternkit\Schema\DataPreProcessor\DataPreProcessorCollection $default_processors
   *   The default schema data preprocessors.
   */
  public function __construct(
    PatternRepository $repository,
    SchemaFactory $schema_factory,
    ContextBuilder $context_builder,
    DataPreProcessorFactory $factory,
    DataPreProcessorCollection $default_processors,
  ) {
    $this->patternRepository = $repository;
    $this->schemaFactory = $schema_factory;
    $this->contextBuilder = $context_builder;
    $this->preProcessorFactory = $factory;
    $this->defaultProcessors = $default_processors;
  }

  /**
   * Detect pattern usages present within the provided content.
   *
   * @param \Drupal\patternkit\Entity\PatternInterface $pattern
   *   The loaded pattern entity.
   * @param string $content
   *   The JSON string content for the pattern to be scanned for pattern usages.
   *
   * @return string[]
   *   An array of external pattern usages within the given content. No matter
   *   how many times a pattern usage is detected within content, the returned
   *   list will only list it once.
   *
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public function detect(PatternInterface $pattern, string $content): array {
    // Ensure we have the unbundled version of the schema to mitigate detecting
    // references within the same document instead of as external pattern
    // references.
    // @todo Conditionally skip this once the bundled flag can be trusted.
    //   When a pattern is loaded from the database as a revision, the stored
    //   schema is used which may already be bundled. Either way, the flag for
    //   the pattern being bundled is left as FALSE since there is no way to
    //   determine if it was previously bundled.
    // Warning: This may force a new schema version to be loaded that doesn't
    // match the content of the cached schema for the pattern revision in
    // question if the pattern revision is outdated.
    $pattern = $this->patternRepository->getPattern($pattern->getAssetId(), FALSE);

    // Load the schema value as an executable schema.
    $schema = $this->schemaFactory->createInstance($pattern->getSchema());

    // Start from the default processors to ensure common failure issues like
    // object casting, token values, and the like are accounted for during the
    // validation process.
    $preprocessor_collection = $this->defaultProcessors;
    assert($preprocessor_collection instanceof DataPreProcessorCollection);

    // Prepare the validation context with a reference observer to capture all
    // pattern references encountered.
    /** @var \Drupal\patternkit\Schema\DataPreProcessor\SchemaRefObserver $observer */
    $observer = $this->preProcessorFactory->getPreProcessor('ref_observer');
    $preprocessor_collection->addPreProcessor($observer);
    $context = $this->contextBuilder->getDefaultContext();
    $context->setDataPreProcessor($preprocessor_collection);

    // Run the validation operation using 'out()' to ensure the observer runs
    // outside the import context.
    $schema->out(json_decode($content), $context);

    // Return the full list of external references detected within the content.
    return $observer->getExternalReferences();
  }

  /**
   * Detect pattern usages present within the provided content.
   *
   * @param string $pattern
   *   The asset name of the pattern the provided content uses.
   * @param string $content
   *   The JSON string content for the pattern to be scanned for pattern usages.
   *
   * @return string[]
   *   An array of external pattern usages within the given content. No matter
   *   how many times a pattern usage is detected within content, the returned
   *   list will only list it once.
   *
   * @throws \Swaggest\JsonSchema\InvalidValue
   */
  public function detectInPattern(string $pattern, string $content): array {
    // Ensure we have the unbundled version of the schema to mitigate detecting
    // references within the same document instead of as external pattern
    // references.
    $pattern = $this->patternRepository->getPattern($pattern, FALSE);

    return $this->detect($pattern, $content);
  }

}
