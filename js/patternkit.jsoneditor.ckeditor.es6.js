/*globals Console:false */
/*globals Drupal:false */
/*globals jQuery:false */
/*globals JSONEditor:false */
/**
 * @file DrupalImageEditor class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */

class DrupalCKEditor extends JSONEditor.defaults.editors.string {

  build() {
    // Override the format when building the base string editor.
    this.options.format = 'textarea';
    super.build();
    this.input_type = this.schema.format;
    this.input.setAttribute('data-schemaformat', this.input_type);
  }

  afterInputReady() {
    if (window.CKEDITOR) {
      // Editor options.
      // @todo Replace JSONEditor.defaults with this.defaults.
      this.options = jQuery.extend({}, JSONEditor.defaults.options.drupal_ckeditor || {}, this.options.drupal_ckeditor || {});
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
      this.ckeditor_instance = window.CKEDITOR.replace(this.ckeditor_container, this.options.ckeditor_config);
      this.ckeditor_instance.setData(this.getValue());
      if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
        this.ckeditor_instance.setReadOnly(true);
      }

      this.ckeditor_instance.on('change', () => {
        this.input.value = this.ckeditor_instance.getData();
        this.refreshValue();
        // Dirty means display cache is invalidated for string editors.
        this.is_dirty = true;
        this.onChange(true);
      });

      this.theme.afterInputReady(this.input);
    }
    else {
      super.afterInputReady();
    }
  }

  destroy() {
    if(this.ckeditor_instance) {
      this.ckeditor_instance.destroy(true);
      window.CKEDITOR.remove(this.ckeditor_instance);
      this.ckeditor_instance = null;
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
      if (!window.JSONEditor) {
        return;
      }
      JSONEditor.defaults.options.drupal_ckeditor = {
        ckeditor_config: settings.patternkitEditor.patternkitCKEditorConfig || {}
      };
      JSONEditor.defaults.editors.drupal_ckeditor = DrupalCKEditor;
      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'string'
          && schema.format === 'html'
          && schema.options
          && schema.options.wysiwyg
          && ['html', 'cygnet'].includes(settings.patternkitEditor.theme)) {
          return 'drupal_ckeditor';
        }
      });
    }
  }
}
