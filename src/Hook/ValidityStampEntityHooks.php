<?php

declare(strict_types=1);

namespace Drupal\patternkit\Hook;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\patternkit\Entity\PatternkitBlock;
use Drupal\patternkit\Validation\ValidityStampWriterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes orphaned validity stamps when patternkit_block entities are removed.
 *
 * @internal
 *   This is an internal utility class wrapping hook implementations.
 */
class ValidityStampEntityHooks implements ContainerInjectionInterface {

  /**
   * Constructs a new ValidityStampEntityHooks object.
   *
   * @param \Drupal\patternkit\Validation\ValidityStampWriterInterface $stampWriter
   *   The validity stamp writer service.
   */
  public function __construct(
    protected ValidityStampWriterInterface $stampWriter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('patternkit.validity.stamp_writer'),
    );
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    if (!($entity instanceof PatternkitBlock)) {
      return;
    }
    $this->stampWriter->deleteStamp((int) $entity->id(), NULL);
  }

  /**
   * Implements hook_entity_translation_delete().
   */
  #[Hook('entity_translation_delete')]
  public function entityTranslationDelete(EntityInterface $entity): void {
    if (!($entity instanceof PatternkitBlock)) {
      return;
    }
    $this->stampWriter->deleteStamp((int) $entity->id(), $entity->language()->getId());
  }

}
