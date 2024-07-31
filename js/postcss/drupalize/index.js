/**
 * @type {import('postcss').PluginCreator}
 */


/**
 * Append suffixes to all provided selectors.
 *
 * @param {string} selector
 *   The selector to be manipulated.
 * @param {string|array<string>} suffixes
 *   A suffix or suffixes to be appended to the selector. If multiple suffixes
 *   are provided, an array of multiple selectors, each with a separate
 *   suffix, will be returned.
 *
 * @return {array<string>}
 *   An array of all new selectors with the appended suffix(es).
 */
function appendSuffixes (selector, suffixes) {
  if (!Array.isArray(suffixes)) {
    suffixes = [suffixes];
  }

  const isPseudoSelectorMatcher = /^:\S+(\(.+\))?$/;
  const pseudoElementMatcher = /(.*)(::\S+)$/;

  return suffixes.map((suffix) => {
    // Does the suffix consist of only a pseudo-selector?
    if (isPseudoSelectorMatcher.test(suffix)) {
      // Does this end in a pseudo-element selector?
      const matches = pseudoElementMatcher.exec(selector);
      if (matches !== null) {
        // Shift the pseudo-selector in front of the final pseudo-element.
        return matches[1] + suffix + matches[2];
      }
    }

    // Default to returning the selector with the suffix appended directly.
    return selector.concat(suffix);
  });
}

/**
 * Prepend prefixes to all provided selectors.
 *
 * @param {string} selector
 *   The selector to be manipulated.
 * @param {string|array<string>} prefixes
 *   A prefix or prefixes to be prepended to the selector. If multiple prefixes
 *   are provided, an array of multiple selectors, each with a separate
 *   prefix, will be returned.
 *
 * @return {array<string>}
 *   An array of all new selectors with the prepended prefix(es).
 */
function prependPrefixes (selector, prefixes) {
  if (!Array.isArray(prefixes)) {
    prefixes = [prefixes];
  }

  return prefixes.map((prefix) => `${prefix} ${selector}`);
}

/**
 * Create the postcss-drupalize plugin for processing.
 *
 * @param {?prefixes: array<string>, ?suffixes: array<string>, ?filter: string} opts
 *   An array of options for configuration of the plugin behavior. Valid options
 *   include:
 *   - prefixes: {array<string>} An array of prefixes to apply to selectors.
 *   - suffixes: {array<string>} An array of suffixes to apply to selectors. A
 *     space before the suffix is NOT assumed to allow for the addition of
 *     pseudo-classes. If a space is needed it should be included in the suffix.
 *   - filter: {string} A filter string to limit which selectors the processor
 *     should apply to. Only selectors including the filter value will be
 *     altered.
 *
 * @returns {{prepare(): ({}), postcssPlugin: string}|{}|{Rule(*): void}}
 *   The postcss-drupalize plugin creation data.
 */
module.exports = (opts = {}) => {
  // Identify prefixes to be assigned.
  const prefixes = opts.prefixes || [];
  const suffixes = opts.suffixes || [];
  const filter = opts.filter || '';

  return {
    postcssPlugin: 'postcss-drupalize',

    prepare() {
      // Don't return anything for processing if there's nothing to do.
      if (prefixes.length === 0 && suffixes.length === 0) {
        return {};
      }

      return {
        // Process each rule to duplicate and add prefixes.
        Rule(rule) {
          // Print each selector on a new line.
          rule.raws.between = '\r\n';

          let altered_selectors = [];
          rule.selectors.forEach((selector) => {
            if (filter && !selector.includes(filter)) {
              altered_selectors.push(selector);
              return;
            }

            if (prefixes.length > 0) {
              altered_selectors = altered_selectors
                .concat(prependPrefixes(selector, prefixes));
            }
            if (suffixes.length > 0) {
              altered_selectors = altered_selectors
                .concat(appendSuffixes(selector, suffixes));
            }
          });

          // Flatten all the selectors to a single layer for assignment.
          rule.selectors = altered_selectors.flat();
        }
      }
    }
  }
}

module.exports.postcss = true
