<?php

namespace Drupal\patternkit\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\Exception\SchemaValidationException;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;
use Swaggest\JsonSchema\InvalidValue;

/**
 * An event triggered when a pattern in use is being updated.
 *
 * @event
 *
 * @see \Drupal\patternkit\PatternkitEvents::PATTERN_UPDATE
 */
class PatternUpdateEvent extends Event {

  /**
   * The Pattern entity revision being used prior to updating.
   *
   * @var \Drupal\patternkit\Entity\PatternInterface
   */
  protected PatternInterface $oldPattern;

  /**
   * The Pattern entity revision to be used after updating.
   *
   * @var \Drupal\patternkit\Entity\PatternInterface
   */
  protected PatternInterface $newPattern;

  /**
   * Content for the pattern instance at the time of updating.
   *
   * @var array
   */
  protected array $originalContent;

  /**
   * Content for the pattern instance that may have been altered.
   *
   * @var array
   */
  protected array $content;

  /**
   * A flag to track whether pattern content has been altered.
   *
   * @var bool
   */
  protected bool $isAltered = FALSE;

  /**
   * The block plugin for the component being updated.
   *
   * @var \Drupal\patternkit\Plugin\Block\PatternkitBlock
   */
  protected PatternkitBlock $plugin;

  /**
   * Create a new PatternUpdateEvent instance.
   *
   * @param \Drupal\patternkit\Plugin\Block\PatternkitBlock $plugin
   *   The patternkit block plugin for the component being updated.
   * @param \Drupal\patternkit\Entity\PatternInterface $oldPattern
   *   The original pattern instance being updated.
   * @param \Drupal\patternkit\Entity\PatternInterface $newPattern
   *   The new pattern instance being updated to.
   */
  public function __construct(PatternkitBlock $plugin, PatternInterface $oldPattern, PatternInterface $newPattern) {
    $this->plugin = $plugin;
    $this->oldPattern = $oldPattern;
    $this->newPattern = $newPattern;

    // Save the starting content for reference.
    $blockEntity = $plugin->getBlockEntity();
    $this->originalContent = $blockEntity->getData();

    // Seed a working copy of the content to be altered.
    $this->content = $this->originalContent;
  }

  /**
   * Get the working copy of the component's pattern content.
   *
   * @return array
   *   The working copy of the component content.
   */
  public function getContent(): array {
    return $this->content;
  }

  /**
   * Get a copy of the original component content before alteration.
   *
   * @return array
   *   A copy of the original component content.
   */
  public function getOriginalContent(): array {
    return $this->originalContent;
  }

  /**
   * Determine if content for a component has been altered.
   *
   * @return bool
   *   True if the content has been altered. False otherwise.
   */
  public function isAltered(): bool {
    return $this->isAltered;
  }

  /**
   * Assign new content for the component.
   *
   * @param array $content
   *   Content values to be assigned to the pattern component.
   *
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   *   Throws an exception when the provided content does not validate
   *   against the updated pattern schema.
   */
  public function setContent(array $content): void {
    if ($this->validateContent($content)) {
      $this->content = $content;
      $this->isAltered = TRUE;
    }
    else {
      throw new SchemaValidationException('Assigned content does not validate successfully.');
    }
  }

  /**
   * Get the updated pattern entity.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The updated pattern entity.
   */
  public function getPattern(): PatternInterface {
    return $this->newPattern;
  }

  /**
   * Get the original pattern entity.
   *
   * @return \Drupal\patternkit\Entity\PatternInterface
   *   The original pattern entity.
   */
  public function getOriginalPattern(): PatternInterface {
    return $this->oldPattern;
  }

  /**
   * Get the plugin being updated.
   *
   * @return \Drupal\patternkit\Plugin\Block\PatternkitBlock
   *   The block plugin being updated.
   */
  public function getPlugin(): PatternkitBlock {
    return $this->plugin;
  }

  /**
   * Validate content being assigned to the pattern component.
   *
   * @param array $content
   *   The content values being assigned to the pattern component.
   *
   * @return bool
   *   True if the content validates successfully. False otherwise.
   *
   * @todo Expose this somewhere as a standard validation solution.
   */
  protected function validateContent(array $content): bool {
    $schema_string = $this->newPattern->getSchema();

    /** @var \Drupal\patternkit\Schema\SchemaFactory $schema_factory */
    $schema_factory = \Drupal::service('patternkit.schema.schema_factory');
    $schema = $schema_factory->createInstance($schema_string);

    /** @var \Drupal\patternkit\Schema\ContextBuilder $context_builder */
    $context_builder = \Drupal::service('patternkit.schema.context_builder');
    $context = $context_builder->getDefaultContext();

    try {
      $schema->in($content, $context);
    }
    catch (InvalidValue $exception) {
      return FALSE;
    }

    return TRUE;
  }

}
