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
   * Overrides json-editor array addControls() method.
   *
   * Overrides JSONEditor's addControls() method for arrays. The only change is to
   * trigger toggle of section if user clicks on the label/title, not just on
   * the expand/collapse button. Makes hiding/showing sections much easier.
   */
  addControls() {
    this.collapsed = false
    this.toggle_button = this.getButton('', 'collapse', this.translate('button_collapse'))
    this.toggle_button.classList.add('json-editor-btntype-toggle')
    this.toggle_button.style.margin = '0 10px 0 0'
    this.title.insertBefore(this.toggle_button, this.title.childNodes[0])

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

    /* If it should start collapsed */
    if (this.options.collapsed) {
      trigger(this.toggle_button, 'click')
    }

    /* Collapse button disabled */
    if (this.schema.options && typeof this.schema.options.disable_collapse !== 'undefined') {
      if (this.schema.options.disable_collapse) this.toggle_button.style.display = 'none'
    } else if (this.jsoneditor.options.disable_collapse) {
      this.toggle_button.style.display = 'none'
    }

    /* Add "new row" and "delete last" buttons below editor */
    this.add_row_button = this.getButton(this.getItemTitle(), 'add', this.translate('button_add_row_title', [this.getItemTitle()]))
    this.add_row_button.classList.add('json-editor-btntype-add')
    this.add_row_button.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()
      const i = this.rows.length
      let editor
      if (this.row_cache[i]) {
        editor = this.rows[i] = this.row_cache[i]
        this.rows[i].setValue(this.rows[i].getDefault(), true)
        this.rows[i].container.style.display = ''
        if (this.rows[i].tab) this.rows[i].tab.style.display = ''
        this.rows[i].register()
      } else {
        editor = this.addRow()
      }
      this.active_tab = this.rows[i].tab
      this.refreshTabs()
      this.refreshValue()
      this.onChange(true)
      this.jsoneditor.trigger('addRow', editor)
    })
    this.controls.appendChild(this.add_row_button)

    this.delete_last_row_button = this.getButton(this.translate('button_delete_last', [this.getItemTitle()]), 'subtract', this.translate('button_delete_last_title', [this.getItemTitle()]))
    this.delete_last_row_button.classList.add('json-editor-btntype-deletelast')
    this.delete_last_row_button.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()

      if (!this.askConfirmation()) {
        return false
      }

      const rows = this.getValue()
      let newActiveTab = null

      const editor = rows.pop()

      this.setValue(rows)

      if (this.rows[this.rows.length - 1]) {
        newActiveTab = this.rows[this.rows.length - 1].tab
      }

      if (newActiveTab) {
        this.active_tab = newActiveTab
        this.refreshTabs()
      }

      this.onChange(true)
      this.jsoneditor.trigger('deleteRow', editor)
    })
    this.controls.appendChild(this.delete_last_row_button)

    this.remove_all_rows_button = this.getButton(this.translate('button_delete_all'), 'delete', this.translate('button_delete_all_title'))
    this.remove_all_rows_button.classList.add('json-editor-btntype-deleteall')
    this.remove_all_rows_button.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()

      if (!this.askConfirmation()) {
        return false
      }

      this.empty(true)
      this.setValue([])
      this.onChange(true)
      this.jsoneditor.trigger('deleteAllRows')
    })
    this.controls.appendChild(this.remove_all_rows_button)

    if (this.tabs) {
      this.add_row_button.style.width = '100%'
      this.add_row_button.style.textAlign = 'left'
      this.add_row_button.style.marginBottom = '3px'

      this.delete_last_row_button.style.width = '100%'
      this.delete_last_row_button.style.textAlign = 'left'
      this.delete_last_row_button.style.marginBottom = '3px'

      this.remove_all_rows_button.style.width = '100%'
      this.remove_all_rows_button.style.textAlign = 'left'
      this.remove_all_rows_button.style.marginBottom = '3px'
    }
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
