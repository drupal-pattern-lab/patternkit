/*globals Console:false */
/*globals Drupal:false */
/*globals jQuery:false */
/*globals JSONEditor:false */
/**
 * @file DrupalCKEditor5 class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 *
 * cspell:ignore schemaformat
 */

export class DrupalCKEditor5 extends JSONEditor.defaults.editors.string {

  build() {
    // Override the format when building the base string editor.
    this.options.format = 'textarea';
    super.build();
    this.input_type = this.schema.format;
    this.input.setAttribute('data-schemaformat', this.input_type);
  }

  afterInputReady() {
    if (window.CKEditor5) {
      // Editor options.
      // @todo Replace JSONEditor.defaults with this.defaults.
      this.options = jQuery.extend({}, JSONEditor.defaults.options.drupal_ckeditor || {}, this.options.drupal_ckeditor || {});

      this.format = drupalSettings.editor.formats[this.options.ckeditor_config.selected_toolbar];
      Drupal.editorAttach(this.input, this.format);

      // Wait for the CKEditor instance to be created and attached to make it
      // available for attaching event listeners.
      const self = this;
      const promise = new Promise((resolve) => {
        let editor;
        let intervalId;
        intervalId = setInterval(() => {
          editor = self.getCkeditorInstance();
          if (editor) {
            // Cancel the interval checking since the instance has been loaded.
            clearInterval(intervalId);
            resolve(editor);
          }
        }, 500);
      });

      // Attach event listeners after the editor has been loaded.
      promise.then(this.attachListeners.bind(this));

      this.theme.afterInputReady(this.input);
    }
    else {
      super.afterInputReady();
    }
  }

  attachListeners() {
    const self = this;
    const editor = this.ckeditor_instance;
    editor.once('destroy', () => {
      self.ckeditor_instance = null;
    });

    // This change handler mimics the attachment handler from Core's CKEditor 5
    // implementation without overriding it in order to maintain the support
    // it provides for bubbling form change events.
    // @see Drupal.editors.ckeditor5.attach
    const updateCallback = this.saveEditorContent.bind(this);
    const debouncedUpdateCallback = Drupal.debounce(updateCallback, 400);
    const onChange = () => {
      if (editor.plugins.has('SourceEditing')) {
        // If the change:data is being called while in source editing
        // mode, it means that the form is being submitted. To avoid
        // race conditions, in this case the callback gets called
        // without decorating the callback with debounce.
        // @see https://www.drupal.org/i/3229174
        // @see Drupal.editorDetach
        if (editor.plugins.get('SourceEditing').isSourceEditingMode) {
          updateCallback();
          return;
        }
      }

      debouncedUpdateCallback();
    }

    // Attach the listener for change events in the CKEditor content.
    editor.model.document.on('change:data', onChange);

    // To avoid race conditions when the form is destroyed, the editor content
    // needs to be saved to the source field before values are collected for
    // AJAX submission.
    window.patternkitEditor.beforeSerialize(() => {
      const isInSourceMode = self.isInSourceMode();

      // Disable the change event listener to prevent an infinite loop if the
      // editor happens to be in source mode.
      if (isInSourceMode) {
        editor.model.document.off('change:data', onChange);
      }

      self.saveEditorContent();

      // If an ajax request is triggered that doesn't result in detaching the
      // form, such as opening a media selection modal, the change listener
      // needs to be re-attached.
      if (isInSourceMode) {
        // Set a timeout to delay re-attaching in order to determine whether the
        // form is being destroyed. Blindly re-attaching the listener sometimes
        // results in value lookup failures since the editor is in the process
        // of being detached.
        setTimeout(() => {
          if (window.patternkitEditor && !window.patternkitEditor.detaching) {
            editor.model.document.on('change:data', onChange);
          }
        }, 500);
      }
    });
  }

  destroy() {
    if (this.ckeditor_instance) {
      Drupal.editorDetach(this.input, this.format, 'unload');
    }

    super.destroy();
  }

  disable(always_disabled) {

    if (always_disabled) {
      this.always_disabled = true;
    }
    if (this.ckeditor_instance) {
      this.ckeditor_instance.enableReadOnlyMode('patternkit-json-editor');
    }
    super.disable(always_disabled);
  }

  enable() {
    if (this.always_disabled) {
      return;
    }

    if (this.ckeditor_instance) {
      this.ckeditor_instance.disableReadOnlyMode('patternkit-json-editor');
    }
    super.enable();
  }

  getNumColumns() {
    return 6;
  }

  /**
   * Bypass JSON Editor's default HTML purification for CKEditor fields.
   *
   * JSON Editor >= 2.16 calls purify() in setValue(), which strips all HTML
   * tags when DOMPurify is not available. CKEditor fields intentionally contain
   * HTML markup, and CKEditor's own data pipeline handles sanitization. Drupal's
   * processed_text filter provides additional sanitization during rendering.
   *
   * @param {string} value - The value to purify.
   * @returns {string} The value, unchanged.
   *
   * @see https://github.com/json-editor/json-editor/blob/master/src/editors/string.js
   */
  purify(value) {
    return value;
  }

  setValue(val, initial, from_template) {
    const input = super.setValue(val, initial, from_template);
    let ckeditor_instance = this.getCkeditorInstance();
    if (input !== undefined && input.changed && ckeditor_instance) {
      ckeditor_instance.setData(input.value);
      this.refreshWatchedFieldValues();
      this.onChange(true);
    }
  }

  saveEditorContent() {
    // If the source editor is open when the form is submitted, the data
    // change event may be delayed in firing causing it to call this function
    // after the JSON Editor has already begun destroying the form and the
    // values may no longer be updated. In this case, editor has already been
    // detached, and we'll abort here.
    const editor = this.getCkeditorInstance();
    if (!this.jsoneditor || !editor) {
      return;
    }

    if (this.isInSourceMode()) {
      // When in source editing mode, the editor model has not been updated
      // with the content from the source editing textarea. Calling
      // updateSourceElement() would write stale model data and lose the
      // source-edited changes. Instead, read the source textarea content
      // directly into the underlying input field.
      const wrapper = this.input.closest('[data-schemapath]');
      const sourceTextarea = wrapper?.querySelector('.ck-source-editing-area textarea');
      if (sourceTextarea) {
        this.input.value = sourceTextarea.value;
      }
    }
    else {
      editor.updateSourceElement();
    }

    this.refreshValue();
    // Dirty means display cache is invalidated for string editors.
    this.is_dirty = true;
    this.onChange(true);
  }

  getCkeditorInstance() {
    if (this.ckeditor_instance === undefined) {
      let id = this.input.getAttribute('data-ckeditor5-id');
      this.ckeditor_instance = Drupal.CKEditor5Instances.get(id);
    }

    return this.ckeditor_instance;
  }

  isInSourceMode() {
    const editor = this.ckeditor_instance;
    if (editor.plugins && editor.plugins.has('SourceEditing')) {
      return editor.plugins.get('SourceEditing').isSourceEditingMode;
    }
    else {
      return false;
    }
  }

}

export function patternkitEditorCKEditor5($, Drupal, JSONEditor) {
  'use strict';
  Drupal.behaviors.patternkitEditorCKEditor = {
    attach: function (context, settings) {
      if (!window.JSONEditor || !drupalSettings.patternkitEditor) {
        return;
      }

      JSONEditor.defaults.options.drupal_ckeditor = {
        ckeditor_config: settings.patternkitEditor.patternkitCKEditorConfig || {}
      };
      if (drupalSettings.patternkitEditor.wysiwygEditorName === 'ckeditor5') {
        JSONEditor.defaults.editors.drupal_ckeditor = DrupalCKEditor5;
      }

      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'string'
          && schema.format === 'html'
          && schema.options
          && schema.options.wysiwyg
          && (settings.patternkitEditor.wysiwygEditorName === 'ckeditor5')
          // Ensures the Text format with CKEditor profile loaded okay.
          && settings.patternkitEditor.patternkitCKEditorConfig) {
          return 'drupal_ckeditor';
        }
      });
    }
  }
}
