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

      let format = drupalSettings.editor.formats[this.options.ckeditor_config.selected_toolbar];
      Drupal.editorAttach(this.input, format);
      let id = this.input.getAttribute('data-ckeditor5-id');
      this.ckeditor_instance = Drupal.CKEditor5Instances.get(id);

      // Attach the listener for change events in the CKEditor content.
      // @todo Determine if this is detrimentally overwriting the change default
      //   listener attached in editor.js.
      Drupal.editors.ckeditor5.onChange(this.input, this.saveEditorContent.bind(this))

      // In "source" mode (e.g., by clicking the "Source" button), CKEditor's
      // "change" event does not fire, so we need to listen on the "input"
      // event.
      // See https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_editor.html#event-change

      this.theme.afterInputReady(this.input);
    }
    else {
      super.afterInputReady();
    }
  }

  destroy() {
    if (this.ckeditor_instance) {
      let format = drupalSettings.editor.formats[this.options.ckeditor_config.selected_toolbar];

      this.ckeditor_instance.updateSourceElement();
      Drupal.editorDetach(this.input, format);
      this.ckeditor_instance = null;
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
    this.input.value = this.getCkeditorInstance().getData();
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
}
