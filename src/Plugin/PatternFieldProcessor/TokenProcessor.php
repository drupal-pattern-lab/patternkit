<?php

namespace Drupal\patternkit\Plugin\PatternFieldProcessor;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Utility\Token;
use Swaggest\JsonSchema\SchemaContract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A pattern field processor for filtering WYSIWYG content fields.
 *
 * @PatternFieldProcessor(
 *   id = "token",
 *   label = @Translation("Token"),
 *   description = @Translation("Token replacement processor.")
 * )
 */
class TokenProcessor extends PatternFieldProcessorBase {

  /**
   * Parses and replaces Drupal tokens.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Renders strings using the Twig template engine.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * Constructor for the TokenProcessor plugin.
   *
   * @param array $configuration
   *   The plugin instance configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service for processing tokens.
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The Twig environment service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, Token $token, TwigEnvironment $twig) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
    $this->twig = $twig;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Utility\Token $token */
    $token = $container->get('token');
    /** @var \Drupal\Core\Template\TwigEnvironment $twig */
    $twig = $container->get('twig');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
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
  public function apply(SchemaContract $propertySchema, $value, $context, BubbleableMetadata $bubbleableMetadata) {
    $token_groups = $this->token->scan($value);
    $template = $value;
    $template_context = [];
    foreach ($token_groups as $group => $tokens) {
      $tokenized = $this->token->generate(
        $group,
        $tokens,
        $context,
        [],
        $bubbleableMetadata
      );
      foreach ($tokens as $token) {
        // Abort token replacement if we don't have a replacement value.
        if (!isset($tokenized[$token])) {
          continue;
        }

        $placeholder = preg_replace("/[^a-z]/", '', $token);
        $template_context[$placeholder] = $tokenized[$token];
        // If the user is not using Twig templating,
        // wrap with a Twig write so we can process it.
        $token_pos = strpos($template, $token);
        $template_use_twig = strpos($template, '{{') < $token_pos
          && strpos($template, '}}', $token_pos + strlen($token)) !== FALSE;
        if (!$template_use_twig) {
          $placeholder = '{{' . $placeholder . '}}';
        }
        $template = str_replace($token, $placeholder, $template);
      }
    }
    return (string) $this->twig->renderInline($template, $template_context);
  }

}
