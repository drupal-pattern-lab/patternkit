<?php

namespace Drupal\patternkit\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\layout_builder\Form\TranslateBlockForm;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\TranslatableSectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides of the layout builder block form.
 */
class PatternkitTranslateBlockForm extends TranslateBlockForm {

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new TranslateBlockForm.
   */
  public function __construct(
    LayoutTempstoreRepositoryInterface $layout_tempstore_repository,
    ModuleHandlerInterface $module_handler,
    TypedConfigManagerInterface $typed_config_manager,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    parent::__construct($layout_tempstore_repository, $module_handler, $typed_config_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('patternkit');

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('module_handler'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?TranslatableSectionStorageInterface $section_storage = NULL, $delta = NULL, $region = NULL, $uuid = NULL) {
    $form = parent::buildForm($form, $form_state, $section_storage, $delta, $region, $uuid);
    // Move the to the different tree so so the JS element can pick up
    // the key element name=settings[instance_config].
    $form['translation']['translation']['#parents'] = ['settings'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('settings');
    if ($settings) {
      $decode_value = Json::decode($settings['instance_config']);
      if (!empty($settings['patternkit_block_id'])) {
        /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $block_storage */
        $block_storage = $this->entityTypeManager->getStorage('patternkit_block');
        /** @var \Drupal\patternkit\Entity\PatternkitBlock $patternkit_block */
        $rid = $block_storage->getLatestRevisionId($settings['patternkit_block_id']);
        if (!$rid) {
          $this->logger->notice('The patternkit block revision id does not exist for block id @id.', ['@id' => $settings['patternkit_block_id']]);
          parent::submitForm($form, $form_state);
          return;
        }
        $patternkit_block = $block_storage->loadRevision($rid);
        $settings['patternkit_block_rid'] = $rid;
      }
      if (!empty($patternkit_block)) {
        $values = [
          'data' => $settings['instance_config'],
          'pattern_id' => $decode_value['name'],
          'published' => TRUE,
          'reusable' => $settings['reusable'],
        ];

        if (!$patternkit_block->hasTranslation($settings['langcode'])) {
          $patternkit_block->addTranslation($settings['langcode'], $values);
        }
        $patternkit_block = $patternkit_block->getTranslation($settings['langcode']);
        foreach (array_keys($patternkit_block->getTranslatableFields()) as $key) {
          if (isset($values[$key])) {
            $patternkit_block->set($key, $values[$key]);
          }
        }

        // Language needs to be set after being check, else the hasTranslation
        // be affected by the change.
        $patternkit_block->setRevisionTranslationAffected(NULL);
        $patternkit_block->setNewRevision();
        $patternkit_block->save();
        $settings['patternkit_block_rid'] = $patternkit_block->getRevisionId();
      }
      // Save the block.
      $form_state->setValue('translation', $settings);
      // Revert the parent back to the translate tree to prevent other blocks
      // from causing errors.
      $form['translation']['translation']['#parents'] = ['translation'];
    }
    parent::submitForm($form, $form_state);
  }

}
