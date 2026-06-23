<?php

namespace Drupal\patternkit\Schema\DataPreProcessor;

use Drupal\Core\Utility\Token;
use Swaggest\JsonSchema\DataPreProcessor;
use Swaggest\JsonSchema\Schema;

/**
 * Bypasses format validation for Drupal token values and empty optional fields.
 *
 * Drupal tokens (e.g. [node:url], [user:mail]) are placeholders that are
 * replaced with their final values at render time. A token stored in a
 * format-constrained field will fail JSON Schema format validation because the
 * raw token string does not conform to the target format. This processor
 * detects token values and temporarily removes the format constraint from the
 * schema so validation passes during import/storage.
 *
 * Empty strings submitted for optional format-constrained fields are similarly
 * bypassed. JSON Editor always serializes unfilled optional fields as empty
 * strings; without the bypass, fields such as url or email would fail strict
 * Swaggest format validation whenever the editor leaves them blank.
 *
 * Linkit double-bracket metadata values (e.g. [[url-nid:42;title:Read more]])
 * are also bypassed as a side effect. Token::scan() detects the inner
 * [url-nid:VALUE] substring as a token type, so the format bypass applies
 * without requiring additional code. The client-side counterpart in
 * patternkit.jsoneditor.js uses LINKIT_REGEX to explicitly match the supported
 * shape; the server relies on Token::scan() detection rather than a dedicated
 * Linkit regex.
 *
 * The bypass applies to all JSON Schema draft-04 format keywords that
 * Swaggest validates by default: uri, email, hostname, ipv4, date, date-time.
 * Pattern constraints (regex) are not affected; no runtime bypass is possible
 * for those without knowing the final token value.
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
   * The bypass applies regardless of whether the field is declared in the
   * parent schema's required array. The DataPreProcessor interface receives
   * only the individual property schema — there is no access to the parent
   * object schema or field path, so required-aware filtering is not possible
   * at this layer. JSON Schema's required keyword enforces field presence, not
   * format validity; strict enforcement for required format-constrained fields
   * (e.g., "must be a non-empty valid URI") requires a separate constraint
   * such as minLength: 1 or a dedicated validation step.
   *
   * Note: setFormat(NULL) mutates the Swaggest Schema object in place.
   * SchemaFactory uses instance-scoped caches, so the mutation cannot
   * persist across HTTP requests. Within a single request, however,
   * the shared DI service instance holds one cache: if the same schema
   * is validated twice in one request and the first call triggers this
   * bypass, subsequent calls against that cached schema will see a
   * NULL format and skip format enforcement for non-token values.
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
    $bypassFormats = ['uri', 'email', 'hostname', 'ipv4', 'date', 'date-time'];
    if ($schema->type === 'string' && in_array($schema->format, $bypassFormats, TRUE)) {
      if ($data === '' || $this->token->scan($data)) {
        $schema->setFormat(NULL);
      }
    }

    return $data;
  }

}
