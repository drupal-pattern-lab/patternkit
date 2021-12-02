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

import {EditorState} from "prosemirror-state"
import {EditorView} from "prosemirror-view"
import {Schema, DOMParser} from "prosemirror-model"
import {schema} from "prosemirror-schema-basic"
import {addListNodes} from "prosemirror-schema-list"
import {exampleSetup} from "prosemirror-example-setup"

class DrupalProseMirror extends JSONEditor.defaults.editors.string {

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
    this.options = jQuery.extend({}, JSONEditor.defaults.options.drupal_prosemirror || {}, this.options.drupal_prosemirror || {});

    const editorSchema = new Schema({
      nodes: addListNodes(schema.spec.nodes, "paragraph block*", "block"),
      marks: schema.spec.marks
    })

    this.prosemirror_container = document.createElement('div');
    this.prosemirror_container.style.width = '100%';
    this.prosemirror_container.style.position = 'relative';

    this.input.style.display = 'none';

    this.prosemirror_instance = new EditorView(this.prosemirror_container, {
      state: EditorState.create({
        doc: DOMParser.fromSchema(editorSchema).parse(this.getValue()),
        plugins: exampleSetup({schema: editorSchema})
      })
    });

    this.input.parentNode.insertBefore(this.prosemirror_container, this.input);

    if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
      // Set all EditorProps editable to false;
    }

    // Handle editor change.

    this.theme.afterInputReady(this.input);
  }

  destroy() {
    if(this.prosemirror_instance) {
      this.prosemirror_instance.destroy();
      this.prosemirror_instance = null;
    }
    super.destroy();
  }

  disable(always_disabled) {
    if (always_disabled) {
      this.always_disabled = true;
    }
    if (this.prosemirror_instance) {
      this.prosemirror_instance.setProps({editable: false});
    }
    super.disable(always_disabled);
  }

  enable() {
    if (this.always_disabled) {
      return;
    }
    if (this.prosemirror_instance) {
      this.prosemirror_instance.setProps({editable: true});
    }
    super.enable();
  }

  getNumColumns() {
    return 6;
  }

  setValue(val, initial, from_template) {
    const input = super.setValue(val, initial, from_template);
    if (input !== undefined && input.changed && this.ckeditor_instance) {
      this.prosemirror_instance.updateState({doc: input.value});
      this.refreshWatchedFieldValues();
      this.onChange(true);
    }
  }
}

export function patternkitEditorProseMirror($, Drupal, JSONEditor) {
  'use strict';
  Drupal.behaviors.patternkitEditorProseMirror = {
    attach: function (context, settings) {
      if (!window.JSONEditor) {
        return;
      }
      JSONEditor.defaults.options.drupal_prosemirror = {
        ckeditor_config: settings.patternkitEditor.patternkitProseMirrorConfig
      };
      JSONEditor.defaults.editors.drupal_prosemirror = DrupalProseMirror;
      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'string'
          && schema.format === 'html'
          && schema.options
          && schema.options.wysiwyg
          && settings.patternkitEditor.wysiwygEditorName === 'prosemirror') {
          return 'drupal_prosemirror';
        }
      });
    }
  }
}
