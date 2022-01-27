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
 * @file PatternkitJsoneditorEditorObject class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */
class PatternkitJsoneditorEditorObject extends JSONEditor.defaults.editors.object {
  /**
   * Overrides json-editor object build() method.
   *
   * Overrides JSONEditor's build() method for objects. The only change is to
   * trigger toggle of section if user clicks on the label/title, not just on
   * the expand/collapse button. Makes hiding/showing sections much easier.
   */
  build () {
    const isCategoriesFormat = (this.format === 'categories')
    this.rows = []
    this.active_tab = null

    /* If the object should be rendered as a table row */
    if (this.options.table_row) {
      this.editor_holder = this.container
      Object.entries(this.editors).forEach(([key, editor]) => {
        const holder = this.theme.getTableCell()
        this.editor_holder.appendChild(holder)

        editor.setContainer(holder)
        editor.build()
        editor.postBuild()
        editor.setOptInCheckbox(editor.header)

        if (this.editors[key].options.hidden) {
          holder.style.display = 'none'
        }
        if (this.editors[key].options.input_width) {
          holder.style.width = this.editors[key].options.input_width
        }
      })
      /* If the object should be rendered as a table */
    } else if (this.options.table) {
      /* TODO: table display format */
      throw new Error('Not supported yet')
      /* If the object should be rendered as a div */
    } else {
      this.header = ''
      if (!this.options.compact) {
        this.header = document.createElement('label')
        this.header.textContent = this.getTitle()
      }
      this.title = this.theme.getHeader(this.header, this.getPathDepth())
      this.title.classList.add('je-object__title')
      this.controls = this.theme.getButtonHolder()
      this.controls.classList.add('je-object__controls')

      this.container.appendChild(this.title)
      this.container.appendChild(this.controls)
      this.container.classList.add('je-object__container')

      /* Edit JSON modal */
      this.editjson_holder = this.theme.getModal()
      this.editjson_textarea = this.theme.getTextareaInput()
      this.editjson_textarea.classList.add('je-edit-json--textarea')
      this.editjson_save = this.getButton('button_save', 'save', 'button_save')
      this.editjson_save.classList.add('json-editor-btntype-save')
      this.editjson_save.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()
        this.saveJSON()
      })
      this.editjson_copy = this.getButton('button_copy', 'copy', 'button_copy')
      this.editjson_copy.classList.add('json-editor-btntype-copy')
      this.editjson_copy.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()
        this.copyJSON()
      })
      this.editjson_cancel = this.getButton('button_cancel', 'cancel', 'button_cancel')
      this.editjson_cancel.classList.add('json-editor-btntype-cancel')
      this.editjson_cancel.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()
        this.hideEditJSON()
      })
      this.editjson_holder.appendChild(this.editjson_textarea)
      this.editjson_holder.appendChild(this.editjson_save)
      this.editjson_holder.appendChild(this.editjson_copy)
      this.editjson_holder.appendChild(this.editjson_cancel)

      /* Manage Properties modal */
      this.addproperty_holder = this.theme.getModal()
      this.addproperty_list = document.createElement('div')
      this.addproperty_list.classList.add('property-selector')
      this.addproperty_add = this.getButton('button_add', 'add', 'button_add')
      this.addproperty_add.classList.add('json-editor-btntype-add')

      this.addproperty_input = this.theme.getFormInputField('text')
      this.addproperty_input.setAttribute('placeholder', 'Property name...')
      this.addproperty_input.classList.add('property-selector-input')
      this.addproperty_add.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()
        if (this.addproperty_input.value) {
          if (this.editors[this.addproperty_input.value]) {
            window.alert('there is already a property with that name')
            return
          }

          this.addObjectProperty(this.addproperty_input.value)
          if (this.editors[this.addproperty_input.value]) {
            this.editors[this.addproperty_input.value].disable()
          }
          this.onChange(true)
        }
      })
      this.addproperty_input.addEventListener('input', (e) => {
        e.target.previousSibling.childNodes.forEach((value) => {
          if (value.innerText.includes(e.target.value)) {
            value.style.display = ''
          } else {
            value.style.display = 'none'
          }
        })
      })
      this.addproperty_holder.appendChild(this.addproperty_list)
      this.addproperty_holder.appendChild(this.addproperty_input)
      this.addproperty_holder.appendChild(this.addproperty_add)
      const spacer = document.createElement('div')
      spacer.style.clear = 'both'
      this.addproperty_holder.appendChild(spacer)

      /* Close properties modal if clicked outside modal */
      document.addEventListener('click', this.onOutsideModalClick.bind(this))

      /* Description */
      if (this.schema.description) {
        this.description = this.theme.getDescription(this.translateProperty(this.schema.description))
        this.container.appendChild(this.description)
      }

      /* Validation error placeholder area */
      this.error_holder = document.createElement('div')
      this.container.appendChild(this.error_holder)

      /* Container for child editor area */
      this.editor_holder = this.theme.getIndentedPanel()
      this.container.appendChild(this.editor_holder)

      /* Container for rows of child editors */
      this.row_container = this.theme.getGridContainer()

      if (isCategoriesFormat) {
        this.tabs_holder = this.theme.getTopTabHolder(this.getValidId(this.translateProperty(this.schema.title)))
        this.tabPanesContainer = this.theme.getTopTabContentHolder(this.tabs_holder)
        this.editor_holder.appendChild(this.tabs_holder)
      } else {
        this.tabs_holder = this.theme.getTabHolder(this.getValidId(this.translateProperty(this.schema.title)))
        this.tabPanesContainer = this.theme.getTabContentHolder(this.tabs_holder)
        this.editor_holder.appendChild(this.row_container)
      }

      Object.values(this.editors).forEach(editor => {
        const aPane = this.theme.getTabContent()
        const holder = this.theme.getGridColumn()
        const isObjOrArray = !!((editor.schema && (editor.schema.type === 'object' || editor.schema.type === 'array')))
        aPane.isObjOrArray = isObjOrArray

        if (isCategoriesFormat) {
          if (isObjOrArray) {
            const singleRowContainer = this.theme.getGridContainer()
            singleRowContainer.appendChild(holder)
            aPane.appendChild(singleRowContainer)
            this.tabPanesContainer.appendChild(aPane)
            this.row_container = singleRowContainer
          } else {
            if (typeof this.row_container_basic === 'undefined') {
              this.row_container_basic = this.theme.getGridContainer()
              aPane.appendChild(this.row_container_basic)
              if (this.tabPanesContainer.childElementCount === 0) {
                this.tabPanesContainer.appendChild(aPane)
              } else {
                this.tabPanesContainer.insertBefore(aPane, this.tabPanesContainer.childNodes[1])
              }
            }
            this.row_container_basic.appendChild(holder)
          }

          this.addRow(editor, this.tabs_holder, aPane)

          aPane.id = this.getValidId(editor.schema.title) /* editor.schema.path//tab_text.textContent */
        } else {
          this.row_container.appendChild(holder)
        }

        editor.setContainer(holder)
        editor.build()
        editor.postBuild()
        editor.setOptInCheckbox(editor.header)
      })

      if (this.rows[0]) {
        trigger(this.rows[0].tab, 'click')
      }

      /* Show/Hide button */
      this.collapsed = false
      this.collapse_control = this.getButton('', 'collapse', 'button_collapse')
      this.collapse_control.classList.add('json-editor-btntype-toggle')
      this.title.insertBefore(this.collapse_control, this.title.childNodes[0])

      // <!-- Start PatternKit overrides. -->
      // Replaces the click handler on the button (element `this.collapse_control`),
      // so that the section is toggled if you click either on the button or its label
      // (i.e., if you clicked anywhere on the title).
      this.title.classList.add('patternkit-jsoneditor-clickable');
      this.title.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()
        if (this.collapsed) {
          this.editor_holder.style.display = ''
          this.collapsed = false
          this.setButtonText(this.collapse_control, '', 'collapse', 'button_collapse')
        } else {
          this.editor_holder.style.display = 'none'
          this.collapsed = true
          this.setButtonText(this.collapse_control, '', 'expand', 'button_expand')
        }
      });
      // <!-- End PatternKit overrides. -->

      /* If it should start collapsed */
      if (this.options.collapsed) {
        trigger(this.collapse_control, 'click')
      }

      /* Collapse button disabled */
      if (this.schema.options && typeof this.schema.options.disable_collapse !== 'undefined') {
        if (this.schema.options.disable_collapse) this.collapse_control.style.display = 'none'
      } else if (this.jsoneditor.options.disable_collapse) {
        this.collapse_control.style.display = 'none'
      }

      /* Edit JSON Button */
      this.editjson_control = this.getButton('JSON', 'edit', 'button_edit_json')
      this.editjson_control.classList.add('json-editor-btntype-editjson')
      this.editjson_control.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()
        this.toggleEditJSON()
      })
      this.controls.appendChild(this.editjson_control)
      this.controls.insertBefore(this.editjson_holder, this.controls.childNodes[0])

      /* Edit JSON Buttton disabled */
      if (this.schema.options && typeof this.schema.options.disable_edit_json !== 'undefined') {
        if (this.schema.options.disable_edit_json) this.editjson_control.style.display = 'none'
      } else if (this.jsoneditor.options.disable_edit_json) {
        this.editjson_control.style.display = 'none'
      }

      /* Object Properties Button */
      this.addproperty_button = this.getButton('properties', 'edit_properties', 'button_object_properties')
      this.addproperty_button.classList.add('json-editor-btntype-properties')
      this.addproperty_button.addEventListener('click', (e) => {
        e.preventDefault()
        e.stopPropagation()
        this.toggleAddProperty()
      })
      this.controls.appendChild(this.addproperty_button)
      this.controls.insertBefore(this.addproperty_holder, this.controls.childNodes[1])

      this.refreshAddProperties()

      /* non required properties start deactivated */
      this.deactivateNonRequiredProperties()
    }

    /* Fix table cell ordering */
    if (this.options.table_row) {
      this.editor_holder = this.container
      this.property_order.forEach(key => {
        this.editor_holder.appendChild(this.editors[key].container)
      })
      /* Layout object editors in grid if needed */
    } else {
      /* Initial layout */
      this.layoutEditors()
      /* Do it again now that we know the approximate heights of elements */
      this.layoutEditors()
    }
  }
}

export function patternkitEditorObject($, Drupal, JSONEditor) {
  'use strict';
  Drupal.behaviors.patternkitEditorObject = {
    attach: function (context, settings) {
      if (!window.JSONEditor) {
        return;
      }
      JSONEditor.defaults.editors.patternkit_editor_object = PatternkitJsoneditorEditorObject;
      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'object') {
          return 'patternkit_editor_object';
        }
      });
    }
  }
}
