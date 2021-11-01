/*globals Console:false */
/*globals Drupal:false */
/*globals jQuery:false */
/*globals JSONEditor:false */
/*globals Quill:false */
/**
 * @file DrupalQuill class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 * @external Quill
 */

class DrupalQuill extends JSONEditor.defaults.editors.string {

  build() {
    // Override the format when building the base string editor.
    this.options.format = 'textarea';
    super.build();
    this.input_type = this.schema.format;
    this.input.setAttribute('data-schemaformat', this.input_type);
  }

  afterInputReady() {
    // Editor options.
    // @todo Replace JSONEditor.defaults with this.defaults.
    this.options = jQuery.extend({}, JSONEditor.defaults.options.drupal_quill || {}, this.options.drupal_quill || {});
    this.options.quill_config = {
      modules: {
        toolbar: [
          [{ header: [1, 2, 3, 4, 5, 6, 7, false] }],
          ['bold', 'italic', 'underline'],
          ['image', 'code-block', 'video'],
          [{ 'indent': '-1' }, { 'indent': '+1' }],
          [{ 'align': [] }]
        ]
      },
      placeholder: '',
      theme: 'snow'
    };

    this.quill_container = document.createElement('div');
    this.quill_container.style.width = '100%';
    this.quill_container.style.position = 'relative';

    this.input.style.display = 'none';

    this.input.parentNode.insertBefore(this.quill_container, this.input);
    this.quill_instance = new Quill(this.quill_container, this.options.quill_config);
    this.quill_instance.setHTML = (html) => {
      this.quill_instance.root.innerHTML = html;
    }
    this.quill_instance.getHTML = () => {
      return this.quill_instance.root.innerHTML;
    }
    this.quill_instance.setHTML(this.getValue());
    if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
      this.quill_instance.disable();
    }

    this.quill_instance.on('text-change', () => {
      this.input.value = this.quill_instance.getHTML();
      this.refreshValue();
      // Dirty means display cache is invalidated for string editors.
      this.is_dirty = true;
      this.onChange(true);
    });

    this.theme.afterInputReady(this.input);
  }

  destroy() {
    if(this.quill_instance) {
      this.quill_instance = null;
    }
    super.destroy();
  }

  disable(always_disabled) {
    if (always_disabled) {
      this.always_disabled = true;
    }
    if (this.quill_instance) {
      this.quill_instance.disable();
    }
    super.disable(always_disabled);
  }

  enable() {
    if (this.always_disabled) {
      return;
    }
    if (this.quill_instance) {
      this.quill_instance.enable(true);
    }
    super.enable();
  }

  getNumColumns() {
    return 6;
  }

  setValue(val, initial, from_template) {
    const input = super.setValue(val, initial, from_template);
    if (input !== undefined && input.changed && this.quill_instance) {
      this.quill_instance.setHTML(input.value);
      this.refreshWatchedFieldValues();
      this.onChange(true);
    }
  }
}

export function patternkitEditorQuill($, Drupal, JSONEditor) {
  'use strict';
  Drupal.behaviors.patternkitEditorQuill = {
    attach: function (context, settings) {
      if (!window.JSONEditor) {
        return;
      }
      JSONEditor.defaults.options.drupal_quill = {
        quill_config: settings.patternkitEditor.patternkitQuillConfig || {}
      };
      JSONEditor.defaults.editors.drupal_quill = DrupalQuill;
      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'string'
          && schema.format === 'html'
          && schema.options
          && schema.options.wysiwyg
          && ! ['html', 'cygnet'].includes(settings.patternkitEditor.theme)) {
          return 'drupal_quill';
        }
      });
    }
  }
}
