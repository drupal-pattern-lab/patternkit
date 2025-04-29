<?php

namespace Drupal\patternkit\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\patternkit\Entity\PatternInterface;
use Drupal\patternkit\PatternValidationTrait;
use Drupal\patternkit\Plugin\Block\PatternkitBlock;

/**
 * An event triggered when a pattern in use is being updated.
 *
 * @event
 *
 * @see \Drupal\patternkit\PatternkitEvents::PATTERN_UPDATE
 * @see \Drupal\patternkit\EventSubscriber\PatternUpdateEventSubscriberBase
 */
class PatternUpdateEvent extends Event {

  use PatternValidationTrait {
    validateContent as traitValidateContent;
  }

  /**
   * Content for the pattern instance at the time of updating.
   *
   * @var array
   */
  protected readonly array $originalContent;

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
   * A flag to track whether the contained pattern content is valid.
   *
   * @var bool
   */
  protected bool $isValid;

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
  public function __construct(
    protected readonly PatternkitBlock $plugin,
    protected readonly PatternInterface $oldPattern,
    protected readonly PatternInterface $newPattern,
  ) {
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
   * Determine if content for a component is valid for the pattern schema.
   *
   * @return bool
   *   True if the content is valid, false otherwise.
   */
  public function isValid(): bool {
    if (!isset($this->isValid)) {
      $this->isValid = $this->validateContent($this->getContent());
    }

    return $this->isValid;
  }

  /**
   * Assign new content for the component.
   *
   * @param array $content
   *   Content values to be assigned to the pattern component.
   * @param bool $allowInvalid
   *   (Optional) A flag to allow invalid content to be set and validation to be
   *   ignored. Defaults to FALSE. The primary use case for this is when
   *   supporting sequential content updates that may produce invalid content
   *   along the way, but the final result after all updates have been executed
   *   is expected to be valid.
   *
   * @throws \Drupal\patternkit\Exception\SchemaValidationException
   *   Throws an exception when the provided content does not validate
   *   against the updated pattern schema. This is only thrown if $allowInvalid
   *   remains FALSE.
   *
   * @see \Drupal\patternkit\Event\PatternUpdateEvent::getExceptions()
   *
   * @return $this
   */
  public function setContent(array $content, bool $allowInvalid = FALSE): self {
    $isValid = $this->validateContent($content);

    // Only throw the exceptions if invalid content is not allowed.
    if (!$isValid && !$allowInvalid) {
      $encountered_exceptions = $this->getExceptions();
      assert(count($encountered_exceptions) > 0);

      // Throw the exception before content assignment if invalid content is not
      // allowed.
      throw $encountered_exceptions[0];
    }

    if ($isValid || $allowInvalid) {
      $this->content = $content;
      $this->isAltered = TRUE;
      $this->isValid = $isValid;
    }

    return $this;
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
   * Get the label for the updated pattern entity from schema data.
   *
   * @return string
   *   The label for the updated pattern entity.
   */
  public function getPatternName(): string {
    return $this->newPattern->getName();
  }

  /**
   * Get the asset ID for the updated pattern entity.
   *
   * @return string
   *   The pattern asset ID for the updated pattern entity.
   */
  public function getPatternAssetId(): string {
    return $this->newPattern->getAssetId();
  }

  /**
   * Get the label for the pre-update pattern entity from schema data.
   *
   * @return string
   *   The label for the pre-update pattern entity.
   */
  public function getOriginalPatternName(): string {
    return $this->oldPattern->getName();
  }

  /**
   * Get the asset ID for the pre-update pattern entity.
   *
   * @return string
   *   The pattern asset ID for the pre-update pattern entity.
   */
  public function getOriginalPatternAssetId(): string {
    return $this->oldPattern->getAssetId();
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
   */
  public function validateContent(array $content): bool {
    // This is overridden for convenience to automatically pass in the updated
    // pattern for reference.
    return $this->validatePattern($this->newPattern, $content);
  }

}
