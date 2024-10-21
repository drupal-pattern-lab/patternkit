<?php

namespace Drupal\patternkit\Plugin\PatternFieldProcessor;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Utility\Token;
use Drupal\patternkit\Attribute\PatternFieldProcessor;
use Swaggest\JsonSchema\SchemaContract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A pattern field processor for filtering WYSIWYG content fields.
 */
#[PatternFieldProcessor(
  id: 'token',
  label: new TranslatableMarkup('Token'),
  description: new TranslatableMarkup('Token replacement processor'),
)]
class TokenProcessor extends PatternFieldProcessorBase {

  /**
   * Constructor for the TokenProcessor plugin.
   *
   * @param array $configuration
   *   The plugin instance configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Config\ImmutableConfig $patternkitSettings
   *   Globally configured settings for the Patternkit module.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service for processing tokens.
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The Twig environment service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected ImmutableConfig $patternkitSettings,
    protected Token $token,
    protected TwigEnvironment $twig,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $container->get('config.factory');
    $settings = $configFactory->get('patternkit.settings');
    /** @var \Drupal\Core\Utility\Token $token */
    $token = $container->get('token');
    /** @var \Drupal\Core\Template\TwigEnvironment $twig */
    $twig = $container->get('twig');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $settings,
      $token,
      $twig,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(SchemaContract $propertySchema, $propertyValue = NULL): bool {
    return property_exists($propertySchema, 'type')
      && $propertySchema->type == 'string';
  }

  /**
   * {@inheritdoc}
   */
  public function apply(SchemaContract $propertySchema, $value, $context, BubbleableMetadata $bubbleableMetadata): string {
    $options = [
      'clear' => $this->patternkitSettings->get('patternkit_json_editor_token_clear'),
    ];
    if (property_exists($propertySchema, 'format') && $propertySchema->format == 'html') {
      return $this->token->replace($value, $context, $options, $bubbleableMetadata);
    }
    else {
      return $this->token->replacePlain($value, $context, $options, $bubbleableMetadata);
    }
  }

}
