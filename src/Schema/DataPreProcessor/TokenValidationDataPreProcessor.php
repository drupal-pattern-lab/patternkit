<?php

namespace Drupal\patternkit\Schema\DataPreProcessor;

use Drupal\Core\Utility\Token;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Schema;

/**
 * Preprocess data to loosen format validation on Uris if needed.
 *
 * When using tokens in content, sometimes the token value representing the
 * final content won't pass validation. For example, a '[node:entity_url]' token
 * will fail validation in a string field with a 'uri' format, but the final
 * replaced value would pass. This processor tests the data and an assigned
 * format to determine if any loosening of constraints is needed to allow for
 * token usage.
 */
class TokenValidationDataPreProcessor implements DataPreProcessor {

  /**
   * Token utility service to scan for token content.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * Create a new TokenValidationDataPreProcessor instance.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility service.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * Process the provided schema data.
   *
   * @param mixed $data
   *   The original data prior to processing.
   * @param \Swaggest\JsonSchema\Schema $schema
   *   The related schema for the data being processed.
   * @param bool $import
   *   A boolean flag indicating if schema data is currently being imported.
   *
   * @return mixed
   *   Processed data.
   */
  public function process($data, Schema $schema, $import = TRUE) {
    if ($schema->type === 'string' && $schema->format === 'uri') {
      // Does it contain a token?
      // @todo Should this use a regex directly for better performance?
      if ($this->token->scan($data)) {
        // Disable format checking.
        $schema->setFormat(NULL);
      }
    }

    return $data;
  }

}
