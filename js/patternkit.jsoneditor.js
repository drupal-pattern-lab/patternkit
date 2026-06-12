/*globals Console:false */
/*globals Drupal:false */
/*globals jQuery:false */
/*globals JSONEditor:false */
/*globals once:false */
/**
 * @file
 * Provides Twig Pattern Library Editing Functionality.
 *
 * @external Drupal
 *
 * @external jQuery
 *
 * @external JSONEditor
 *
 * @external once
 *
 * @todo .patternkit-editor-target .card all: initial
 *
 * cspell:ignore iconlib startval
 */

import { patternkitEditorCKEditor5 } from './patternkit.jsoneditor.ckeditor5.js';
import { patternkitEditorCygnet } from './patternkit.jsoneditor.cygnet.js';
import { patternkitEditorObject } from './patternkit.jsoneditor.editor.object.js';
import { patternkitEditorArray } from './patternkit.jsoneditor.editor.array.js';
import {
  TOKEN_REGEX,
  LINKIT_REGEX,
  filterTokenErrors,
} from './src/patternkit.tokenFilter.js';

// Instantiates wysiwyg plugins.
patternkitEditorCKEditor5(jQuery, Drupal, JSONEditor);
patternkitEditorCygnet(jQuery, Drupal, JSONEditor);

// Overrides the object and array json-editor editors, to customize certain
// methods.  The only use case so far is to trigger toggling of items by
// clicking on the button's title instead of just the small button itself.
patternkitEditorObject(jQuery, Drupal, JSONEditor);
patternkitEditorArray(jQuery, Drupal, JSONEditor);

(function ($, Drupal, JSONEditor, once) {
  'use strict';

  class PatternkitJsonEditor {
    constructor(target, settings, defaults) {
      this.target = target;
      this.settings = settings;
      this.editor_root = document;
      this.beforeSerializeCallbacks = [];
      this.beforeSubmitCallbacks = [];

      this.data = {
        schema: JSON.parse(settings.schemaJson),
      };

      // Only assign a starting value if a non-empty value was provided,
      // otherwise don't provide a value so a default may be assumed.
      const startVal = settings.startingJson;
      if (startVal !== '{}' && Boolean(startVal) === true) {
        this.data.starting = JSON.parse(startVal);
      }
    }

    /**
     * Prepare default option values for JSONEditor behaviors.
     */
    setDefaults() {
      // Add a custom validator that enforces non-empty values for required
      // string fields. JSON Schema's "required" keyword only checks that a
      // property exists in the object; it does not enforce non-empty values.
      // This validator ensures that required string fields with empty values
      // produce a "Value required" error, which matches user expectations for
      // form validation behavior.
      // Note: Parity with server-side validation (swaggest/php-json-schema) is
      // not in place: the server only checks required-property presence, so
      // empty or whitespace-only required strings may pass server-side.
      if (!JSONEditor.defaults.options.patternkitRequiredStringValidator) {
        JSONEditor.defaults.custom_validators.push(
          function (schema, value, path) {
            const errors = [];
            const translate =
              typeof this.translate === 'function'
                ? this.translate.bind(this)
                : JSONEditor.defaults.translate;
            if (
              schema.required &&
              Array.isArray(schema.required) &&
              typeof value === 'object' &&
              value !== null
            ) {
              schema.required.forEach((requiredField) => {
                const fieldSchema =
                  schema.properties && schema.properties[requiredField];
                if (fieldSchema && fieldSchema.type === 'string') {
                  const fieldValue = value[requiredField];
                  if (
                    typeof fieldValue === 'string' &&
                    fieldValue.trim() === ''
                  ) {
                    // Skip when minLength >= 1 is set: the built-in minLength
                    // validator already emits error_notempty for this case, and
                    // _removeDuplicateErrors does not deduplicate across different
                    // property values ('minLength' vs 'required').
                    if (
                      typeof fieldSchema.minLength === 'number' &&
                      fieldSchema.minLength >= 1
                    ) {
                      return;
                    }
                    errors.push({
                      path: `${path}.${requiredField}`,
                      property: 'required',
                      message: translate('error_notempty', null, fieldSchema),
                    });
                  }
                }
              });
            }
            return errors;
          },
        );

        JSONEditor.defaults.options.patternkitRequiredStringValidator = true;
      }

      JSONEditor.defaults.options.theme = this.settings.theme;
      JSONEditor.defaults.options.iconlib = this.settings.icons;
      // cspell:disable-next-line
      JSONEditor.defaults.options.keep_oneof_values = false;
      JSONEditor.defaults.options.disable_edit_json =
        this.settings.disableJsonEditButton;
      JSONEditor.defaults.options.disable_collapse = false;
      JSONEditor.defaults.options.collapse = false;
      JSONEditor.defaults.options.ajax = true;
      JSONEditor.defaults.options.ajax_cache_responses =
        this.settings.ajaxCacheResponses;
      JSONEditor.defaults.options.ajax_cache_buster =
        this.settings.ajaxCacheBuster;
      JSONEditor.defaults.options.disable_properties =
        this.settings.disablePropertiesButtons;

      // Overrides alert message when removing an item.
      JSONEditor.defaults.languages.en.button_delete_node_warning = Drupal.t(
        'Are you sure you want to remove this item?',
      );

      // Override object properties button text for user friendliness.
      JSONEditor.defaults.languages.en.button_properties =
        Drupal.t('Properties');
      JSONEditor.defaults.languages.en.button_object_properties =
        Drupal.t('Properties');

      // Uses Handlebars template engine so that we can use logic in
      // `headerTemplate` property in schemas.
      JSONEditor.defaults.options.template = 'handlebars';

      // @todo Loop through all editor plugins and add them at runtime.

      // Override how references are resolved.
      JSONEditor.prototype._loadExternalRefs = function (schema, callback) {
        let refs = this._getExternalRefs(schema);
        let done = 0,
          waiting = 0,
          callback_fired = false;
        $.each(refs, (url) => {
          if (this.refs[url]) {
            return;
          }
          if (!this.options.ajax) {
            throw 'Must set ajax option to true to load external ref ' + url;
          }
          this.refs[url] = 'loading';
          waiting++;
          let r = new XMLHttpRequest();
          let uri = settings.path.baseUrl + url;
          r.open('GET', uri, true);
          r.onreadystatechange = () => {
            if (r.readyState !== 4) {
              return;
            }
            // Request succeeded.
            if (r.status === 200) {
              let response;
              try {
                response = JSON.parse(r.responseText);
              } catch (e) {
                window.console.log(e);
                throw 'Failed to parse external ref ' + url;
              }
              // @todo Actually validate the schema so we can throw an error.
              if (!response || typeof response !== 'object') {
                throw 'External ref does not contain a valid schema - ' + url;
              }
              this.refs[url] = response;
              this._loadExternalRefs(response, function () {
                done++;
                if (done >= waiting && !callback_fired) {
                  callback_fired = true;
                  callback();
                }
              });
            }
            // Request failed.
            else {
              window.console.log(r);
              throw 'Failed to fetch ref via ajax- ' + url;
            }
          };
          r.send();
        });
        if (!waiting) {
          callback();
        }
      };
    }

    loadEditor() {
      // Initialize the editor with a JSON schema.
      let config = {
        schema: this.data.schema,
        refs: {},
      };

      // Conditionally specify the start value only if data already exists.
      // Starting with an empty string or object results in all form fields
      // starting hidden by default.
      if (this.data.starting) {
        config.startval = this.data.starting;
      }

      window.patternkitEditor = new JSONEditor(
        this.editor_root.getElementById('editor_holder'),
        config,
      );
      window.patternkitEditor.patternkit = this;

      window.patternkitEditor.on('ready', this.onReady.bind(this));
      window.patternkitEditor.on('change', this.saveSchema.bind(this));

      // Expose methods for editors to register AJAX event cycle callbacks.
      const self = this;
      window.patternkitEditor.beforeSerialize = (callback) => {
        self.beforeSerializeCallbacks.push(callback);
      };
      window.patternkitEditor.beforeSubmit = (callback) => {
        self.beforeSubmitCallbacks.push(callback);
      };
    }

    /**
     * Save configured schema values to the related form field for server-side
     * form submission.
     */
    saveSchema() {
      $('#schema_instance_config').val(
        JSON.stringify(window.patternkitEditor.getValue()),
      );
    }

    /**
     * Initialize form field values once the editor form is loaded and ready.
     */
    onReady() {
      try {
        // If we provide starting JSON as a value, JSON Editor hides all
        // non-required fields, which is desired behavior by most users of
        // the library. For patterns, we want to include any new schema
        // fields in our values, so they are displayed by default, optional or
        // not. This also allows us to pre-populate based on the schema
        // provided.
        if (
          typeof this.data.starting === 'object' &&
          !$.isEmptyObject(this.data.starting)
        ) {
          window.patternkitEditor.setValue({
            ...window.patternkitEditor.getValue(),
            ...this.data.starting,
          });
        }
      } catch (e) {
        window.console.error(e);
      }

      this.hideLoadingIndicator();
    }

    /**
     * Execute any beforeSerialize callbacks registered by nested editors.
     */
    beforeSerialize() {
      // Test if the active element is a text input field that doesn't trigger
      // change events until it loses focus and manually trigger the change event
      // prior to ajax serialization for submission if so.
      if (this.isDelayedChangeElement(document.activeElement)) {
        // Dispatch a new 'change' event to trigger the update process before
        // serializing values and sending them in the AJAX submission.
        document.activeElement.dispatchEvent(new Event('change'));
      }

      const callbacks = this.beforeSerializeCallbacks;
      callbacks.forEach((callback) => {
        callback();
      });
    }

    /**
     * Execute any beforeSubmit callbacks registered by nested editors.
     */
    beforeSubmit() {
      const callbacks = this.beforeSubmitCallbacks;
      callbacks.forEach((callback) => {
        callback();
      });
    }

    /**
     * Test if an element only fires change events once it loses focus.
     *
     * @param {Element} element
     *   The DOM element to be tested.
     *
     * @return boolean
     *   True if the element only triggers change events when it loses focus,
     *   false otherwise.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement/change_event
     */
    isDelayedChangeElement(element) {
      const tag = element.tagName.toLowerCase();
      if (tag === 'textarea') {
        return true;
      } else if (tag === 'input') {
        const type = element.getAttribute('type');
        const delayedTypes = [
          'text',
          'search',
          'url',
          'tel',
          'email',
          'password',
        ];

        return delayedTypes.includes(type);
      }

      return false;
    }

    /**
     * Trigger all submit handling for the form.
     *
     * This is only executed in contexts where the form is submitted natively
     * rather than via AJAX as handled in the off-canvas tray. As such, this
     * handler will only execute in full-page views of the JSON Editor form.
     *
     * @param {Event} e
     *   The submit event.
     */
    onSubmit(e) {
      // Validate before allowing non-AJAX form submission.
      if (window.patternkitEditor) {
        const rawErrors = window.patternkitEditor.validate(
          window.patternkitEditor.getValue(),
        );
        const validationErrors = filterTokenErrors(rawErrors);
        if (validationErrors.length > 0) {
          e.preventDefault();
          window.patternkitEditor.root.showValidationErrors(validationErrors);
          return;
        }
        // Clear any stale inline errors from a prior failed submit attempt
        // so the editor does not display errors while the form navigates away.
        window.patternkitEditor.root.showValidationErrors([]);
      }

      // Call all serialize and submission handlers since this function is only
      // triggered outside an AJAX content and those handlers would not be
      // called otherwise.
      this.beforeSerialize();
      this.beforeSubmit();

      this.saveSchema();
      window.patternkitEditor.destroy();
      delete window.patternkitEditor;
      $(once.remove('patternkit-editor', $('#patternkit-editor-target')));
      $(this).off('submit');
    }

    /**
     * Validate editor content and block AJAX submission if errors are found.
     *
     * When validation fails, this method displays inline errors on form fields
     * and resets the Drupal AJAX system state so that subsequent submit
     * attempts are not silently ignored.
     *
     * @param {object} ajax
     *   The Drupal AJAX handler instance for the submit button.
     * @param {jQuery} $submitButton
     *   The jQuery-wrapped submit button element.
     *
     * @return {boolean}
     *   TRUE if validation passed and submission may proceed,
     *   FALSE if validation failed and submission was blocked.
     */
    validateBeforeAjaxSubmit(ajax, $submitButton) {
      const jsonEditor = window.patternkitEditor;
      if (!jsonEditor) {
        return true;
      }

      // Pass the current value to validate() to force fresh validation.
      // Calling validate() with no arguments returns cached results from
      // the last requestAnimationFrame cycle, which may be stale if the
      // user just edited a field and immediately clicked submit.
      const rawErrors = jsonEditor.validate(jsonEditor.getValue());
      const errors = filterTokenErrors(rawErrors);
      if (errors.length === 0) {
        jsonEditor.root.showValidationErrors([]);
        return true;
      }

      // Display inline validation errors on the form fields.
      jsonEditor.root.showValidationErrors(errors);

      // Reset the AJAX system's "in progress" flag. Drupal sets
      // ajax.ajaxing = true before calling beforeSubmit; when we abort
      // by returning false the success/error callbacks never fire and
      // the flag is never cleared.
      ajax.ajaxing = false;

      // Re-enable the submit button and remove any progress indicator.
      $submitButton.prop('disabled', false).removeClass('is-disabled');
      $('.ajax-progress', $submitButton.closest('.form-actions')).remove();

      return false;
    }

    /**
     * Display the form loading indicator while the form is being prepared.
     */
    showLoadingIndicator() {
      const loadingIndicatorClass = 'js-patternkit-loading__on';

      // Only add the indicator if it isn't already present.
      let $indicator = $('.' + loadingIndicatorClass);
      if ($indicator.length === 0) {
        this.target.before(`<div class="${loadingIndicatorClass}"></div>`);
      }
    }

    /**
     * Hide the loading indicator once the form is fully prepared.
     */
    hideLoadingIndicator() {
      const loadingIndicatorClass = 'js-patternkit-loading__on';
      $('.' + loadingIndicatorClass).remove();
    }
  }

  Drupal.behaviors.patternkitEditor = {
    attach: function (context, settings) {
      if (!window.JSONEditor) {
        return;
      }
      // Ajax command response to allow updating Editor field values.
      Drupal.AjaxCommands.prototype.patternkitEditorUpdate = function (
        ajax,
        response,
        status,
      ) {
        if (!window.patternkitEditor) {
          window.console?.debug(
            'patternkitEditorUpdate skipped: JSON editor no longer attached.',
          );
          return;
        }
        let editor = window.patternkitEditor.getEditor(response.selector);

        if (editor) {
          editor.setValue(response.value);
        } else {
          window.console?.debug(
            'Unable to find an editor at "%s" to assign value "%s".',
            response.selector,
            response.value,
          );
        }
      };

      let $target = $('#patternkit-editor-target', context);
      let editor = new PatternkitJsonEditor($target, settings.patternkitEditor);

      $(once('patternkit-editor', $target)).each(function () {
        editor.showLoadingIndicator();
        editor.setDefaults();
        editor.loadEditor();

        let $submitButton = $('[data-drupal-selector="edit-actions-submit"]');
        let $form = $submitButton.parent('form');

        // Get the ajax handler instance attached to the current submit element.
        // Avoid matching stale instances from a prior form render that reused
        // the same selector/id after an AJAX replacement.
        const submitElement = $submitButton.get(0);
        let ajax = Drupal.ajax.instances.find((instance) => {
          // Check that both instance and submitElement exist before proceeding.
          if (!instance || !submitElement) {
            return false;
          }
          // Check if instance.element matches the actual submit button node.
          if (instance.element && instance.element === submitElement) {
            return true;
          }
          // Check if instance.$element is a jQuery object and its first element
          // matches submitElement.
          if (instance.$element && instance.$element[0] === submitElement) {
            return true;
          }
          // If none of the above, not a match for this submit element.
          return false;
        });

        // If there is no AJAX handler for the submit button, we're most likely
        // viewing the form directly without an AJAX interface. In this scenario,
        // we need to capture and react to the form submit event directly since
        // there will be no AJAX event cycle to hook into.
        if (ajax === undefined) {
          $(once('json-editor-processed', $form, context)).each(function () {
            $(this).on('submit', editor.onSubmit.bind(editor));
          });

          // Exit here since there is no more handling needed.
          return;
        }

        // Drupal triggers Ajax submit via input events before the form
        // events bubble up for listeners, so we need to add a pre-hook
        // within this event cycle to trigger the editor update with
        // latest field values before they are finalized and submitted.
        //
        // Only wrap once per ajax instance to avoid chaining wrappers
        // if attach runs again (e.g. after DOM replacement).
        if (!ajax._patternkitBeforeSerializeWrapped) {
          ajax._patternkitBeforeSerializeWrapped = true;
          const previousBeforeSerialize = ajax.beforeSerialize;
          ajax.beforeSerialize = function (element, options) {
            if (window.patternkitEditor && !window.patternkitEditor.detaching) {
              editor.beforeSerialize();
              editor.saveSchema();
            }
            if (typeof previousBeforeSerialize === 'function') {
              previousBeforeSerialize.apply(this, [element, options]);
            }
          };
        }

        // Drupal triggers Ajax submit via input events before other
        // form events bubble up. We wrap beforeSubmit to intercept this
        // early in the cycle, adding validation and ensuring submitted
        // values reflect the latest editor state.
        //
        // Only wrap once per ajax instance to avoid chaining wrappers.
        // @todo Add handling for AJAX errors and re-attach.
        if (!ajax._patternkitBeforeSubmitWrapped) {
          ajax._patternkitBeforeSubmitWrapped = true;
          const previousBeforeSubmit = ajax.beforeSubmit;
          ajax.beforeSubmit = function (formValues, elementSettings, options) {
            const activeEditor = window.patternkitEditor;
            const activePatternkit = activeEditor && activeEditor.patternkit;

            if (activeEditor && !activeEditor.detaching && activePatternkit) {
              // Block submission when validation fails.
              if (
                !activePatternkit.validateBeforeAjaxSubmit(ajax, $submitButton)
              ) {
                return false;
              }

              // Run any registered beforeSubmit callbacks.
              activePatternkit.beforeSubmit();

              // Ensure submitted form values reflect the latest editor
              // state, in case the user clicked submit without blurring
              // a text field first.
              let index = formValues.findIndex(function (o) {
                return o.name === 'settings[configuration]';
              });
              if (index !== -1) {
                activeEditor.disable();
                formValues[index].value = JSON.stringify(
                  activeEditor.getValue(),
                );
              }
            }

            if (typeof previousBeforeSubmit === 'function') {
              return previousBeforeSubmit.apply(this, [
                formValues,
                elementSettings,
                options,
              ]);
            }
          };
        }
      });
    },

    detach(context, settings, trigger) {
      let editors;
      if (trigger === 'serialize') {
        editors = once.filter(
          'patternkit-editor',
          '#patternkit-editor-target',
          context,
        );
      } else {
        editors = once.remove(
          'patternkit-editor',
          '#patternkit-editor-target',
          context,
        );
      }

      editors.forEach((editor) => {
        if (window.patternkitEditor) {
          if (trigger === 'serialize') {
            window.patternkitEditor.patternkit.saveSchema();
          } else {
            // Add a status flag to easily check when the editor is being
            // destroyed.
            window.patternkitEditor.detaching = true;
            window.patternkitEditor.disable();
            window.patternkitEditor.destroy();
            delete window.patternkitEditor;
          }
        }
      });
    },
  };
})(jQuery, Drupal, JSONEditor, once);
