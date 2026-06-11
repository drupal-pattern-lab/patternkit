/**
 * @file
 * Token and Linkit placeholder detection and JSON Editor error filtering.
 *
 * Extracted from patternkit.jsoneditor.js so the filter logic can be
 * unit-tested independently of the Drupal/jQuery/JSONEditor environment.
 */

/**
 * Matches a bare Drupal token, e.g. [node:url] or [site:name].
 *
 * Used by filterTokenErrors to detect token values and suppress client-side
 * format validation errors that would block form submission. Pattern errors
 * are never suppressed — the server-side TokenValidationDataPreProcessor only
 * bypasses format constraints, not pattern constraints.
 *
 * @type {RegExp}
 */
export const TOKEN_REGEX = /^\[[\w-]+:[\w:.-]+\]$/;

/**
 * Matches a Linkit double-bracket metadata value, e.g.
 * [[url-nid:42;title:Read more]] or [[url-nid:42;title:'Example']].
 *
 * Linkit stores node references in this format in format-constrained fields
 * (typically format: uri). JSON Editor's URI validator rejects these values
 * because they are not valid URIs. This regex detects the canonical supported
 * shape so filterTokenErrors can suppress the resulting format error.
 *
 * Shape: [[url-nid:<digits>;title:<non-empty-text>]]
 * - Requires url-nid: prefix (scopes to confirmed Linkit shape).
 * - Requires title: key with at least one character not containing ] ([^\]]+).
 * - Malformed variants without title text (e.g. [[url-nid:42;title]]) do not
 *   match and remain blocked client-side. The server-side
 *   TokenValidationDataPreProcessor accepts those values via Token::scan()
 *   detection; this asymmetry is a documented known edge case for a format
 *   Linkit UI does not produce in practice.
 *
 * @type {RegExp}
 */
export const LINKIT_REGEX = /^\[\[url-nid:\d+;title:[^\]]+\]\]$/;

/**
 * Filters JSONEditor validation errors, removing format errors only for fields
 * whose current value is a bypassed placeholder class or an empty string.
 *
 * Bypassed placeholder classes:
 *   - Bare Drupal tokens (TOKEN_REGEX): e.g. [node:url], [site:name].
 *   - Linkit double-bracket metadata (LINKIT_REGEX): e.g.
 *     [[url-nid:42;title:Read more]]. These are stored in format: uri fields
 *     by the Linkit module and resolved to real URLs at render time.
 *
 * JSONEditor's ipValidator blocks hostname and ipv4 fields that contain token
 * values. Its URI validator blocks Linkit values in format: uri fields.
 * Both validators fire at the submission gate via validate(). This helper
 * removes the resulting format errors so placeholder-bearing fields do not
 * block form submission, matching the server-side format bypass in
 * TokenValidationDataPreProcessor.
 *
 * Pattern errors are always preserved. TokenValidationDataPreProcessor bypasses
 * format constraints only — not pattern. A token may resolve to a value that
 * does not match the schema author's pattern regex, so the client must not
 * suppress pattern validation for token values.
 *
 * Empty strings in optional format-constrained fields are also suppressed for
 * format errors. JSON Editor serializes unfilled optional fields as empty
 * strings; without this bypass, leaving any optional format-constrained field
 * blank would block submission even though the server accepts empty values.
 * This mirrors the empty-string bypass already applied server-side.
 *
 * Field values are read directly from the visible DOM input (bracket-notation
 * name attribute) rather than via editor.getEditor(), avoiding a dependency on
 * JSONEditor's internal path-key consistency.
 *
 * Non-placeholder, non-empty values are not affected: format errors for real
 * values are preserved and continue to block submission.
 *
 * @param {Array} errors
 *   The array of error objects returned by jsonEditor.validate().
 *
 * @return {Array}
 *   A filtered copy of errors with placeholder-related and empty-field format
 *   errors removed.
 */
export function filterTokenErrors(errors) {
  return errors.filter((error) => {
    if (error.property !== 'format') {
      return true;
    }
    // Convert dot-notation error path (e.g. "root.hostname") to the HTML
    // input name in bracket notation (e.g. "root[hostname]") that JSONEditor
    // assigns to visible form inputs. Reading directly from the DOM avoids a
    // dependency on getEditor() path-key consistency across JSONEditor versions.
    const inputName = error.path.replace(/\.([^.]+)/g, '[$1]');
    const input = document.querySelector(`[name="${inputName}"]`);
    if (!input) {
      return true;
    }
    const { value } = input;
    if (typeof value !== 'string') {
      return true;
    }
    // Suppress format errors for empty optional fields, mirroring the
    // server-side bypass in TokenValidationDataPreProcessor.
    if (value === '') {
      return false;
    }
    return !TOKEN_REGEX.test(value) && !LINKIT_REGEX.test(value);
  });
}
