class cygnetTheme extends JSONEditor.AbstractTheme {
  getFormInputLabel (text, req) {
    const el = super.getFormInputLabel(text, req)
    el.classList.add('je-cygnet-form-input-label')
    return el
  }

  getFormInputDescription (text) {
    const el = super.getFormInputDescription(text)
    el.classList.add('je-cygnet-form-input-label')
    return el
  }

  getIndentedPanel () {
    const el = super.getIndentedPanel()
    el.classList.add('je-cygnet-indented-panel')
    el.style = el.style || {};
    return el
  }

  getTopIndentedPanel () {
    return this.getIndentedPanel()
  }

  getChildEditorHolder () {
    const el = super.getChildEditorHolder()
    el.classList.add('je-cygnet-child-editor-holder')
    return el
  }

  // If no title, use the text as title so that we have can use the
  // title attr as a CSS selector to style the collapse/expand state.
  setButtonText (button, text, icon, title) {
    if (!title && text) {
      title = text;
    }

    if (text == "Object Properties") {
      text = "Properties";
    }

    return super.setButtonText(button, text, icon, title);
  }

  getHeaderButtonHolder () {
    const el = this.getButtonHolder()
    el.classList.add('je-cygnet-header-button-holder')
    el.style.display = 'block';
    return el
  }

  getTable () {
    const el = super.getTable()
    el.classList.add('je-table')
    return el
  }

  setGridColumnSize (el, size) {
    el.className = 'col-md-'+size;
  }

  addInputError (input, text) {
    const group = this.closest(input, '.form-control') || input.controlgroup

    if (!input.errmsg) {
      input.errmsg = document.createElement('div')
      input.errmsg.setAttribute('class', 'errmsg')
      input.errmsg.style = input.errmsg.style || {}
      input.errmsg.style.color = 'red'
      group.appendChild(input.errmsg)
    } else {
      input.errmsg.style.display = 'block'
    }

    input.errmsg.innerHTML = ''
    input.errmsg.appendChild(document.createTextNode(text))
  }

  removeInputError (input) {
    if (input.style) {
      input.style.borderColor = ''
    }
    if (input.errmsg) input.errmsg.style.display = 'none'
  }

  getTabHolder (propertyName) {
    var pName = typeof propertyName === 'undefined' ? '' : propertyName;
    var el = document.createElement('div');
    el.classList.add('je-cygnet-tabs');
    el.innerHTML = "<div class='je-tabholder tabs je-cygnet-tabs__holder'></div><div class='content je-cygnet-tabs__content' id='".concat(pName, "'></div><div class='je-tabholder--clear'></div>");
    return el;
  }

  getTab (span, tabId) {
    const el = document.createElement('div')
    el.appendChild(span)
    el.id = tabId
    el.style = el.style || {}
    el.classList.add('je-cygnet-tab');
    return el
  }

  markTabActive (row) {
    row.tab.classList.remove('je-cygnet-tab--inactive');
    row.tab.classList.add('je-cygnet-tab--active');

    if (typeof row.rowPane !== 'undefined') {
      row.rowPane.style.display = ''
    } else {
      row.container.style.display = ''
    }
  }

  markTabInactive (row) {
    row.tab.classList.remove('je-cygnet-tab--active');
    row.tab.classList.add('je-cygnet-tab--inactive');

    if (typeof row.rowPane !== 'undefined') {
      row.rowPane.style.display = 'none'
    } else {
      row.container.style.display = 'none'
    }
  }

}

export function patternkitEditorCygnet($, Drupal, JSONEditor) {
  'use strict';
  Drupal.behaviors.patternkitEditorCygnet = {
    attach: function (context, settings) {
      if (!window.JSONEditor) {
        return;
      }
      cygnetTheme.rules = { }
      JSONEditor.defaults.themes.cygnet = cygnetTheme;
    }
  }
}
