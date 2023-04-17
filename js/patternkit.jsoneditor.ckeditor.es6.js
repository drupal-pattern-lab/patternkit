/*globals Console:false */
/*globals Drupal:false */
/*globals jQuery:false */
/*globals JSONEditor:false */
/**
 * @file DrupalCKEditor class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */

import {DrupalCKEditor5} from './patternkit.jsoneditor.ckeditor5.es6';

class DrupalCKEditor extends JSONEditor.defaults.editors.string {

  build() {
    // Override the format when building the base string editor.
    this.options.format = 'textarea';
    super.build();
    this.input_type = this.schema.format;
    this.input.setAttribute('data-schemaformat', this.input_type);
  }

  /**
   * Post-load after CKEditor has created a successful instance.
   */
  ckeditorPostLoad() {
    this.ckeditor_instance.setData(this.getValue());
    if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
      this.ckeditor_instance.setReadOnly(true);
    }

    const saveEditorContent = Drupal.debounce(() => {
      this.input.value = this.ckeditor_instance.getData();
      this.refreshValue();
      // Dirty means display cache is invalidated for string editors.
      this.is_dirty = true;
      this.onChange(true);
    }, 400);

    this.ckeditor_instance.on('change', saveEditorContent);
    // In "source" mode (e.g., by clicking the "Source" button), CKEditor's
    // "change" event does not fire, so we need to listen on the "input"
    // event.
    // See https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_editor.html#event-change
    this.ckeditor_instance.on('mode', () => {
      if (this.ckeditor_instance.mode === 'source') {
        const editable = this.ckeditor_instance.editable();
        editable.attachListener(editable, 'input', saveEditorContent);
      }
    });
    this.ckeditor_instance.on('blur', () => {
      if (this.ckeditor_instance.mode === 'wysiwyg') {
        saveEditorContent();
      }
    });
    this.theme.afterInputReady(this.input);
  }

  afterInputReady() {
    if (window.CKEDITOR) {
      // Editor options.
      // @todo Replace JSONEditor.defaults with this.defaults.
      this.options = jQuery.extend({}, JSONEditor.defaults.options.drupal_ckeditor || {}, this.options.drupal_ckeditor || {});

      // Copies logic from Drupal.editors.ckeditor.attach(), so that certain
      // buttons (e.g., DrupalLink) work.
      this.options.ckeditor_config.drupal = {
        format: this.options.ckeditor_config.selected_toolbar,
      };

      // The schema can determine which HTML tags are allowed and disallowed.
      // These settings are passed as-is to CKEditor's ACF (content filtering)
      // system. See https://ckeditor.com/docs/ckeditor4/latest/guide/dev_acf.html.
      // (Clears existing settings in case they were set previously in this request
      // by prior schema.)
      const allowedContent = this.options.ckeditor_config.allowedContent || null;
      const disallowedContent = this.options.ckeditor_config.disallowedContent || null;
      delete this.options.ckeditor_config.allowedContent;
      delete this.options.ckeditor_config.disallowedContent;
      if (this.schema.options.allowedContent) {
        this.options.ckeditor_config.allowedContent = this.schema.options.allowedContent;
      }
      else if (allowedContent) {
        this.options.ckeditor_config.allowedContent = allowedContent;
      }

      if (this.schema.options.disallowedContent) {
        this.options.ckeditor_config.disallowedContent = this.schema.options.disallowedContent;
      }
      else if (disallowedContent) {
        this.options.ckeditor_config.disallowedContent = disallowedContent;
      }

      // @see Drupal.editors.ckeditor._loadExternalPlugins
      const externalPlugins = this.options.ckeditor_config.drupalExternalPlugins;
      if (externalPlugins) {
        Object.keys(externalPlugins || {}).forEach(pluginName => {
          CKEDITOR.plugins.addExternal(
            pluginName,
            externalPlugins[pluginName],
            '',
          );
        });
        delete this.options.ckeditor_config.drupalExternalPlugins;
      }

      this.ckeditor_container = document.createElement('div');
      this.ckeditor_container.style.width = '100%';
      this.ckeditor_container.style.position = 'relative';
      this.input.style.display = 'none';
      this.input.parentNode.insertBefore(this.ckeditor_container, this.input);
      // Using the delay if delayIfDetached and delayIfDetached_callback,
      // was getting an error code: editor-delayed-creation-success, and still
      // needed to be in the timeout to work.
      setTimeout(() => {
        // The input element may be removed due to changes made via the
        // jsoneditor properties selections.
        if (this.jsoneditor.editors[this.path]) {
          this.ckeditor_instance = window.CKEDITOR.replace(this.ckeditor_container, this.options.ckeditor_config);
          this.ckeditorPostLoad();
        }
        else {
          // Store any unloaded textarea that doesn't have a CKEditor instance
          // loaded yet.
          if (typeof window.DrupalCKEditors == 'undefined' ) {
            window.DrupalCKEditors = {};
          }
          window.DrupalCKEditors[this.path] = {
            ckeditor_container: this.ckeditor_container,
            ckeditor_config: this.options.ckeditor_config,
          }
          this.input.style.display = 'block';
        }
      }, 1000);
    }
    else {
      super.afterInputReady();
    }
  }

  destroy() {
    if (this.ckeditor_instance) {
      this.ckeditor_instance.destroy(true);
      window.CKEDITOR.remove(this.ckeditor_instance);
      this.ckeditor_instance = null;
    }
    if (window.DrupalCKEditors) {
      delete window.DrupalCKEditors;
    }
    super.destroy();
  }

  disable(always_disabled) {
    if (always_disabled) {
      this.always_disabled = true;
    }
    if (this.ckeditor_instance) {
      this.ckeditor_instance.setReadOnly(true);
    }
    super.disable(always_disabled);
  }

  enable() {
    if (this.always_disabled) {
      return;
    }

    if (this.ckeditor_instance) {
      this.ckeditor_instance.setReadOnly(false);
    }

    // Handle cases where the Ckeditor instance is removed before binding. Once
    // enabled by properties it will run only once. After being able to bind to
    // a DOM element it should be fine since it now has the instance created.
    if (window.DrupalCKEditors && window.DrupalCKEditors[this.path] && !this.ckeditor_instance) {
      const editor_config = window.DrupalCKEditors[this.path];
      this.ckeditor_instance = window.CKEDITOR.replace(editor_config.ckeditor_container, editor_config.ckeditor_config);
      this.ckeditorPostLoad();
      this.input.style.display = 'none';
    }
    super.enable();
  }

  getNumColumns() {
    return 6;
  }

  setValue(val, initial, from_template) {
    const input = super.setValue(val, initial, from_template);
    if (input !== undefined && input.changed && this.ckeditor_instance) {
      this.ckeditor_instance.setData(input.value);
      this.refreshWatchedFieldValues();
      this.onChange(true);
    }
  }
}

export function patternkitEditorCKEditor($, Drupal, JSONEditor) {
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
      else {
        JSONEditor.defaults.editors.drupal_ckeditor = DrupalCKEditor;
      }

      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'string'
          && schema.format === 'html'
          && schema.options
          && schema.options.wysiwyg
          && (settings.patternkitEditor.wysiwygEditorName === 'ckeditor' || settings.patternkitEditor.wysiwygEditorName === 'ckeditor5')
          // Ensures the Text format with CKEditor profile loaded okay.
          && settings.patternkitEditor.patternkitCKEditorConfig) {
          return 'drupal_ckeditor';
        }
      });
    }
  }
}
