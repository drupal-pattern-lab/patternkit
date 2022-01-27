/*globals Console:false */
/*globals Drupal:false */
/*globals jQuery:false */
/*globals JSONEditor:false */

/**
 * Duplicates json-editor trigger utility.
 *
 * Cannot figure out how to import it without errors.
 * The function is defined in @json-editor/json-editor/src/utilities.
 *
 * @param el
 * @param event
 */
const trigger = function (el, event) {
  const e = document.createEvent('HTMLEvents')
  e.initEvent(event, true, true)
  el.dispatchEvent(e)
};

/**
 * @file PatternkitJsoneditorEditorArray class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */
class PatternkitJsoneditorEditorArray extends JSONEditor.defaults.editors.array {
  /**
   * Overrides json-editor array _createToggleButton() method.
   *
   * Overrides JSONEditor's _createToggleButton() method for arrays. The only change is to
   * trigger toggle of section if user clicks on the label/title, not just on
   * the expand/collapse button. Makes hiding/showing sections much easier.
   */
  _createToggleButton () {
    const button = this.getButton('', 'collapse', 'button_collapse')
    button.classList.add('json-editor-btntype-toggle')
    this.title.insertBefore(button, this.title.childNodes[0])

    const rowHolderDisplay = this.row_holder.style.display
    const controlsDisplay = this.controls.style.display

    // <!-- Start PatternKit overrides. -->
    // Replaces the click handler on the button (element `this.collapse_control`),
    // so that the section is toggled if you click either on the button or its label
    // (i.e., if you clicked anywhere on the title).
    this.title.classList.add('patternkit-jsoneditor-clickable');
    this.title.addEventListener('click', e => {
      e.preventDefault()
      e.stopPropagation()
      if (this.collapsed) {
        this.collapsed = false
        if (this.panel) this.panel.style.display = ''
        this.row_holder.style.display = rowHolderDisplay
        if (this.tabs_holder) this.tabs_holder.style.display = ''
        this.controls.style.display = controlsDisplay
        this.setButtonText(this.toggle_button, '', 'collapse', this.translate('button_collapse'))
      } else {
        this.collapsed = true
        this.row_holder.style.display = 'none'
        if (this.tabs_holder) this.tabs_holder.style.display = 'none'
        this.controls.style.display = 'none'
        if (this.panel) this.panel.style.display = 'none'
        this.setButtonText(this.toggle_button, '', 'expand', this.translate('button_expand'))
      }
    });
    // <!-- End PatternKit overrides. -->

    return button
  }
}

export function patternkitEditorArray($, Drupal, JSONEditor) {
  'use strict';
  Drupal.behaviors.patternkitEditorArray = {
    attach: function (context, settings) {
      if (!window.JSONEditor) {
        return;
      }
      JSONEditor.defaults.editors.patternkit_editor_array = PatternkitJsoneditorEditorArray;
      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'array') {
          return 'patternkit_editor_array';
        }
      });
    }
  }
}
