(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.patternkitEditorCKEditor5 = patternkitEditorCKEditor5;
exports.DrupalCKEditor5 = void 0;

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

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
 */
var DrupalCKEditor5 = /*#__PURE__*/function (_JSONEditor$defaults$) {
  _inherits(DrupalCKEditor5, _JSONEditor$defaults$);

  var _super = _createSuper(DrupalCKEditor5);

  function DrupalCKEditor5() {
    _classCallCheck(this, DrupalCKEditor5);

    return _super.apply(this, arguments);
  }

  _createClass(DrupalCKEditor5, [{
    key: "build",
    value: function build() {
      // Override the format when building the base string editor.
      this.options.format = 'textarea';

      _get(_getPrototypeOf(DrupalCKEditor5.prototype), "build", this).call(this);

      this.input_type = this.schema.format;
      this.input.setAttribute('data-schemaformat', this.input_type);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      if (window.CKEditor5) {
        // Editor options.
        // @todo Replace JSONEditor.defaults with this.defaults.
        this.options = jQuery.extend({}, JSONEditor.defaults.options.drupal_ckeditor || {}, this.options.drupal_ckeditor || {});
        this.format = drupalSettings.editor.formats[this.options.ckeditor_config.selected_toolbar];
        Drupal.editorAttach(this.input, this.format); // Wait for the CKEditor instance to be created and attached to make it
        // available for attaching event listeners.

        var self = this;
        var promise = new Promise(function (resolve) {
          var editor;
          var intervalId;
          intervalId = setInterval(function () {
            editor = self.getCkeditorInstance();

            if (editor) {
              // Cancel the interval checking since the instance has been loaded.
              clearInterval(intervalId);
              resolve(editor);
            }
          }, 500);
        }); // Attach event listeners after the editor has been loaded.

        promise.then(this.attachListeners.bind(this));
        this.theme.afterInputReady(this.input);
      } else {
        _get(_getPrototypeOf(DrupalCKEditor5.prototype), "afterInputReady", this).call(this);
      }
    }
  }, {
    key: "attachListeners",
    value: function attachListeners() {
      var self = this;
      var editor = this.ckeditor_instance;
      editor.once('destroy', function () {
        self.ckeditor_instance = null;
      }); // This change handler mimics the attachment handler from Core's CKEditor 5
      // implementation without overriding it in order to maintain the support
      // it provides for bubbling form change events.
      // @see Drupal.editors.ckeditor5.attach

      var updateCallback = this.saveEditorContent.bind(this);

      var onChange = function onChange() {
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

        Drupal.debounce(updateCallback, 400)();
      }; // Attach the listener for change events in the CKEditor content.


      editor.model.document.on('change:data', onChange); // To avoid race conditions when the form is destroyed, the editor content
      // needs to be saved to the source field before values are collected for
      // AJAX submission.

      window.patternkitEditor.beforeSerialize(function () {
        var isInSourceMode = self.isInSourceMode(); // Disable the change event listener to prevent an infinite loop if the
        // editor happens to be in source mode.

        if (isInSourceMode) {
          editor.model.document.off('change:data', onChange);
        }

        self.saveEditorContent(); // If an ajax request is triggered that doesn't result in detaching the
        // form, such as opening a media selection modal, the change listener
        // needs to be re-attached.

        if (isInSourceMode) {
          // Set a timeout to delay re-attaching in order to determine whether the
          // form is being destroyed. Blindly re-attaching the listener sometimes
          // results in value lookup failures since the editor is in the process
          // of being detached.
          setTimeout(function () {
            if (window.patternkitEditor && !window.patternkitEditor.detaching) {
              editor.model.document.on('change:data', onChange);
            }
          }, 500);
        }
      });
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.ckeditor_instance) {
        Drupal.editorDetach(this.input, this.format, 'unload');
      }

      _get(_getPrototypeOf(DrupalCKEditor5.prototype), "destroy", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(always_disabled) {
      if (always_disabled) {
        this.always_disabled = true;
      }

      if (this.ckeditor_instance) {
        this.ckeditor_instance.enableReadOnlyMode('patternkit-json-editor');
      }

      _get(_getPrototypeOf(DrupalCKEditor5.prototype), "disable", this).call(this, always_disabled);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (this.always_disabled) {
        return;
      }

      if (this.ckeditor_instance) {
        this.ckeditor_instance.disableReadOnlyMode('patternkit-json-editor');
      }

      _get(_getPrototypeOf(DrupalCKEditor5.prototype), "enable", this).call(this);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 6;
    }
  }, {
    key: "setValue",
    value: function setValue(val, initial, from_template) {
      var input = _get(_getPrototypeOf(DrupalCKEditor5.prototype), "setValue", this).call(this, val, initial, from_template);

      var ckeditor_instance = this.getCkeditorInstance();

      if (input !== undefined && input.changed && ckeditor_instance) {
        ckeditor_instance.setData(input.value);
        this.refreshWatchedFieldValues();
        this.onChange(true);
      }
    }
  }, {
    key: "saveEditorContent",
    value: function saveEditorContent() {
      // If the source editor is open when the form is submitted, the data
      // change event may be delayed in firing causing it to call this function
      // after the JSON Editor has already begun destroying the form and the
      // values may no longer be updated. In this case, editor has already been
      // detached, and we'll abort here.
      var editor = this.getCkeditorInstance();

      if (!this.jsoneditor || !editor) {
        return;
      }

      editor.updateSourceElement();
      this.refreshValue(); // Dirty means display cache is invalidated for string editors.

      this.is_dirty = true;
      this.onChange(true);
    }
  }, {
    key: "getCkeditorInstance",
    value: function getCkeditorInstance() {
      if (this.ckeditor_instance === undefined) {
        var id = this.input.getAttribute('data-ckeditor5-id');
        this.ckeditor_instance = Drupal.CKEditor5Instances.get(id);
      }

      return this.ckeditor_instance;
    }
  }, {
    key: "isInSourceMode",
    value: function isInSourceMode() {
      var editor = this.ckeditor_instance;

      if (editor.plugins && editor.plugins.has('SourceEditing')) {
        return editor.plugins.get('SourceEditing').isSourceEditingMode;
      } else {
        return false;
      }
    }
  }]);

  return DrupalCKEditor5;
}(JSONEditor.defaults.editors.string);

exports.DrupalCKEditor5 = DrupalCKEditor5;

function patternkitEditorCKEditor5($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditorCKEditor = {
    attach: function attach(context, settings) {
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
        if (schema.type === 'string' && schema.format === 'html' && schema.options && schema.options.wysiwyg && settings.patternkitEditor.wysiwygEditorName === 'ckeditor5' // Ensures the Text format with CKEditor profile loaded okay.
        && settings.patternkitEditor.patternkitCKEditorConfig) {
          return 'drupal_ckeditor';
        }
      });
    }
  };
}

},{}],2:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.patternkitEditorCygnet = patternkitEditorCygnet;

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

var cygnetTheme = /*#__PURE__*/function (_JSONEditor$AbstractT) {
  _inherits(cygnetTheme, _JSONEditor$AbstractT);

  var _super = _createSuper(cygnetTheme);

  function cygnetTheme() {
    _classCallCheck(this, cygnetTheme);

    return _super.apply(this, arguments);
  }

  _createClass(cygnetTheme, [{
    key: "getFormInputLabel",
    value: function getFormInputLabel(text, req) {
      var el = _get(_getPrototypeOf(cygnetTheme.prototype), "getFormInputLabel", this).call(this, text, req);

      el.classList.add('je-cygnet-form-input-label');
      return el;
    }
  }, {
    key: "getFormInputDescription",
    value: function getFormInputDescription(text) {
      var el = _get(_getPrototypeOf(cygnetTheme.prototype), "getFormInputDescription", this).call(this, text);

      el.classList.add('je-cygnet-form-input-label');
      el.innerHTML = text;
      return el;
    }
  }, {
    key: "getIndentedPanel",
    value: function getIndentedPanel() {
      var el = _get(_getPrototypeOf(cygnetTheme.prototype), "getIndentedPanel", this).call(this);

      el.classList.add('je-cygnet-indented-panel');
      el.style = el.style || {};
      return el;
    }
  }, {
    key: "getTopIndentedPanel",
    value: function getTopIndentedPanel() {
      return this.getIndentedPanel();
    }
  }, {
    key: "getChildEditorHolder",
    value: function getChildEditorHolder() {
      var el = _get(_getPrototypeOf(cygnetTheme.prototype), "getChildEditorHolder", this).call(this);

      el.classList.add('je-cygnet-child-editor-holder');
      return el;
    }
  }, {
    key: "getDescription",
    value: function getDescription(text) {
      var el = _get(_getPrototypeOf(cygnetTheme.prototype), "getDescription", this).call(this, text);

      el.classList.add('je-cygnet-description');
      el.innerHTML = text;
      return el;
    } // If no title, use the text as title so that we have can use the
    // title attr as a CSS selector to style the collapse/expand state.

  }, {
    key: "setButtonText",
    value: function setButtonText(button, text, icon, title) {
      if (!title && text) {
        title = text;
      }

      return _get(_getPrototypeOf(cygnetTheme.prototype), "setButtonText", this).call(this, button, text, icon, title);
    }
  }, {
    key: "getHeaderButtonHolder",
    value: function getHeaderButtonHolder() {
      var el = this.getButtonHolder();
      el.classList.add('je-cygnet-header-button-holder');
      el.style.display = 'block';
      return el;
    }
  }, {
    key: "getTable",
    value: function getTable() {
      var el = _get(_getPrototypeOf(cygnetTheme.prototype), "getTable", this).call(this);

      el.classList.add('je-table');
      return el;
    }
  }, {
    key: "setGridColumnSize",
    value: function setGridColumnSize(el, size) {
      el.className = 'col-md-' + size;
    }
  }, {
    key: "addInputError",
    value: function addInputError(input, text) {
      var group = this.closest(input, '.form-control') || input.controlgroup;

      if (!input.errmsg) {
        input.errmsg = document.createElement('div');
        input.errmsg.setAttribute('class', 'errmsg');
        input.errmsg.style = input.errmsg.style || {};
        input.errmsg.style.color = 'red';
        group.appendChild(input.errmsg);
      } else {
        input.errmsg.style.display = 'block';
      }

      input.errmsg.innerHTML = '';
      input.errmsg.appendChild(document.createTextNode(text));
    }
  }, {
    key: "removeInputError",
    value: function removeInputError(input) {
      if (input.style) {
        input.style.borderColor = '';
      }

      if (input.errmsg) input.errmsg.style.display = 'none';
    }
  }, {
    key: "getTabHolder",
    value: function getTabHolder(propertyName) {
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      var el = document.createElement('div');
      el.classList.add('je-cygnet-tabs');
      el.innerHTML = "<div class='je-tabholder tabs je-cygnet-tabs__holder'></div><div class='content je-cygnet-tabs__content' id='".concat(pName, "'></div><div class='je-tabholder--clear'></div>");
      return el;
    }
  }, {
    key: "getTab",
    value: function getTab(span, tabId) {
      var el = document.createElement('div');
      el.appendChild(span);
      el.id = tabId;
      el.style = el.style || {};
      el.classList.add('je-cygnet-tab');
      return el;
    }
  }, {
    key: "markTabActive",
    value: function markTabActive(row) {
      row.tab.classList.remove('je-cygnet-tab--inactive');
      row.tab.classList.add('je-cygnet-tab--active');

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.style.display = '';
      } else {
        row.container.style.display = '';
      }
    }
  }, {
    key: "markTabInactive",
    value: function markTabInactive(row) {
      row.tab.classList.remove('je-cygnet-tab--active');
      row.tab.classList.add('je-cygnet-tab--inactive');

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.style.display = 'none';
      } else {
        row.container.style.display = 'none';
      }
    }
  }]);

  return cygnetTheme;
}(JSONEditor.AbstractTheme);

function patternkitEditorCygnet($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditorCygnet = {
    attach: function attach(context, settings) {
      if (!window.JSONEditor) {
        return;
      }

      cygnetTheme.rules = {};
      JSONEditor.defaults.themes.cygnet = cygnetTheme;
    }
  };
}

},{}],3:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.patternkitEditorArray = patternkitEditorArray;

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

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
var trigger = function trigger(el, event) {
  var e = document.createEvent('HTMLEvents');
  e.initEvent(event, true, true);
  el.dispatchEvent(e);
};
/**
 * @file PatternkitJsoneditorEditorArray class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */


var PatternkitJsoneditorEditorArray = /*#__PURE__*/function (_JSONEditor$defaults$) {
  _inherits(PatternkitJsoneditorEditorArray, _JSONEditor$defaults$);

  var _super = _createSuper(PatternkitJsoneditorEditorArray);

  function PatternkitJsoneditorEditorArray() {
    _classCallCheck(this, PatternkitJsoneditorEditorArray);

    return _super.apply(this, arguments);
  }

  _createClass(PatternkitJsoneditorEditorArray, [{
    key: "_createToggleButton",

    /**
     * Overrides json-editor array _createToggleButton() method.
     *
     * Overrides JSONEditor's _createToggleButton() method for arrays. The only change is to
     * trigger toggle of section if user clicks on the label/title, not just on
     * the expand/collapse button. Makes hiding/showing sections much easier.
     */
    value: function _createToggleButton() {
      var _this = this;

      var button = this.getButton('', 'collapse', 'button_collapse');
      button.classList.add('json-editor-btntype-toggle');
      this.title.insertBefore(button, this.title.childNodes[0]);
      var rowHolderDisplay = this.row_holder.style.display;
      var controlsDisplay = this.controls.style.display; // <!-- Start PatternKit overrides. -->
      // Replaces the click handler on the button (element `this.collapse_control`),
      // so that the section is toggled if you click either on the button or its label
      // (i.e., if you clicked anywhere on the title).

      this.title.classList.add('patternkit-jsoneditor-clickable');
      this.title.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (_this.collapsed) {
          _this.collapsed = false;
          if (_this.panel) _this.panel.style.display = '';
          _this.row_holder.style.display = rowHolderDisplay;
          if (_this.tabs_holder) _this.tabs_holder.style.display = '';
          _this.controls.style.display = controlsDisplay;

          _this.setButtonText(_this.toggle_button, '', 'collapse', _this.translate('button_collapse'));
        } else {
          _this.collapsed = true;
          _this.row_holder.style.display = 'none';
          if (_this.tabs_holder) _this.tabs_holder.style.display = 'none';
          _this.controls.style.display = 'none';
          if (_this.panel) _this.panel.style.display = 'none';

          _this.setButtonText(_this.toggle_button, '', 'expand', _this.translate('button_expand'));
        }
      }); // <!-- End PatternKit overrides. -->

      return button;
    }
  }]);

  return PatternkitJsoneditorEditorArray;
}(JSONEditor.defaults.editors.array);

function patternkitEditorArray($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditorArray = {
    attach: function attach(context, settings) {
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
  };
}

},{}],4:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.patternkitEditorObject = patternkitEditorObject;

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

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
var trigger = function trigger(el, event) {
  var e = document.createEvent('HTMLEvents');
  e.initEvent(event, true, true);
  el.dispatchEvent(e);
};
/**
 * @file PatternkitJsoneditorEditorObject class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */


var PatternkitJsoneditorEditorObject = /*#__PURE__*/function (_JSONEditor$defaults$) {
  _inherits(PatternkitJsoneditorEditorObject, _JSONEditor$defaults$);

  var _super = _createSuper(PatternkitJsoneditorEditorObject);

  function PatternkitJsoneditorEditorObject() {
    _classCallCheck(this, PatternkitJsoneditorEditorObject);

    return _super.apply(this, arguments);
  }

  _createClass(PatternkitJsoneditorEditorObject, [{
    key: "build",

    /**
     * Overrides json-editor object build() method.
     *
     * Overrides JSONEditor's build() method for objects. The only change is to
     * trigger toggle of section if user clicks on the label/title, not just on
     * the expand/collapse button. Makes hiding/showing sections much easier.
     */
    value: function build() {
      var _this = this;

      var isCategoriesFormat = this.format === 'categories';
      this.rows = [];
      this.active_tab = null;
      /* If the object should be rendered as a table row */

      if (this.options.table_row) {
        this.editor_holder = this.container;
        Object.entries(this.editors).forEach(function (_ref) {
          var _ref2 = _slicedToArray(_ref, 2),
              key = _ref2[0],
              editor = _ref2[1];

          var holder = _this.theme.getTableCell();

          _this.editor_holder.appendChild(holder);

          editor.setContainer(holder);
          editor.build();
          editor.postBuild();
          editor.setOptInCheckbox(editor.header);

          if (_this.editors[key].options.hidden) {
            holder.style.display = 'none';
          }

          if (_this.editors[key].options.input_width) {
            holder.style.width = _this.editors[key].options.input_width;
          }
        });
        /* If the object should be rendered as a table */
      } else if (this.options.table) {
        /* TODO: table display format */
        throw new Error('Not supported yet');
        /* If the object should be rendered as a div */
      } else {
        this.header = '';

        if (!this.options.compact) {
          this.header = document.createElement('label');
          this.header.textContent = this.getTitle();
        }

        this.title = this.theme.getHeader(this.header, this.getPathDepth());
        this.title.classList.add('je-object__title');
        this.controls = this.theme.getButtonHolder();
        this.controls.classList.add('je-object__controls');
        this.container.appendChild(this.title);
        this.container.appendChild(this.controls);
        this.container.classList.add('je-object__container');
        /* Edit JSON modal */

        this.editjson_holder = this.theme.getModal();
        this.editjson_textarea = this.theme.getTextareaInput();
        this.editjson_textarea.classList.add('je-edit-json--textarea');
        this.editjson_save = this.getButton('button_save', 'save', 'button_save');
        this.editjson_save.classList.add('json-editor-btntype-save');
        this.editjson_save.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this.saveJSON();
        });
        this.editjson_copy = this.getButton('button_copy', 'copy', 'button_copy');
        this.editjson_copy.classList.add('json-editor-btntype-copy');
        this.editjson_copy.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this.copyJSON();
        });
        this.editjson_cancel = this.getButton('button_cancel', 'cancel', 'button_cancel');
        this.editjson_cancel.classList.add('json-editor-btntype-cancel');
        this.editjson_cancel.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this.hideEditJSON();
        });
        this.editjson_holder.appendChild(this.editjson_textarea);
        this.editjson_holder.appendChild(this.editjson_save);
        this.editjson_holder.appendChild(this.editjson_copy);
        this.editjson_holder.appendChild(this.editjson_cancel);
        /* Manage Properties modal */

        this.addproperty_holder = this.theme.getModal();
        this.addproperty_list = document.createElement('div');
        this.addproperty_list.classList.add('property-selector');
        this.addproperty_add = this.getButton('button_add', 'add', 'button_add');
        this.addproperty_add.classList.add('json-editor-btntype-add');
        this.addproperty_input = this.theme.getFormInputField('text');
        this.addproperty_input.setAttribute('placeholder', 'Property name...');
        this.addproperty_input.classList.add('property-selector-input');
        this.addproperty_add.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          if (_this.addproperty_input.value) {
            if (_this.editors[_this.addproperty_input.value]) {
              window.alert('there is already a property with that name');
              return;
            }

            _this.addObjectProperty(_this.addproperty_input.value);

            if (_this.editors[_this.addproperty_input.value]) {
              _this.editors[_this.addproperty_input.value].disable();
            }

            _this.onChange(true);
          }
        });
        this.addproperty_input.addEventListener('input', function (e) {
          e.target.previousSibling.childNodes.forEach(function (value) {
            if (value.innerText.includes(e.target.value)) {
              value.style.display = '';
            } else {
              value.style.display = 'none';
            }
          });
        });
        this.addproperty_holder.appendChild(this.addproperty_list);
        this.addproperty_holder.appendChild(this.addproperty_input);
        this.addproperty_holder.appendChild(this.addproperty_add);
        var spacer = document.createElement('div');
        spacer.style.clear = 'both';
        this.addproperty_holder.appendChild(spacer);
        /* Close properties modal if clicked outside modal */

        document.addEventListener('click', this.onOutsideModalClick.bind(this));
        /* Description */

        if (this.schema.description) {
          this.description = this.theme.getDescription(this.translateProperty(this.schema.description));
          this.container.appendChild(this.description);
        }
        /* Validation error placeholder area */


        this.error_holder = document.createElement('div');
        this.container.appendChild(this.error_holder);
        /* Container for child editor area */

        this.editor_holder = this.theme.getIndentedPanel();
        this.container.appendChild(this.editor_holder);
        /* Container for rows of child editors */

        this.row_container = this.theme.getGridContainer();

        if (isCategoriesFormat) {
          this.tabs_holder = this.theme.getTopTabHolder(this.getValidId(this.translateProperty(this.schema.title)));
          this.tabPanesContainer = this.theme.getTopTabContentHolder(this.tabs_holder);
          this.editor_holder.appendChild(this.tabs_holder);
        } else {
          this.tabs_holder = this.theme.getTabHolder(this.getValidId(this.translateProperty(this.schema.title)));
          this.tabPanesContainer = this.theme.getTabContentHolder(this.tabs_holder);
          this.editor_holder.appendChild(this.row_container);
        }

        Object.values(this.editors).forEach(function (editor) {
          var aPane = _this.theme.getTabContent();

          var holder = _this.theme.getGridColumn();

          var isObjOrArray = !!(editor.schema && (editor.schema.type === 'object' || editor.schema.type === 'array'));
          aPane.isObjOrArray = isObjOrArray;

          if (isCategoriesFormat) {
            if (isObjOrArray) {
              var singleRowContainer = _this.theme.getGridContainer();

              singleRowContainer.appendChild(holder);
              aPane.appendChild(singleRowContainer);

              _this.tabPanesContainer.appendChild(aPane);

              _this.row_container = singleRowContainer;
            } else {
              if (typeof _this.row_container_basic === 'undefined') {
                _this.row_container_basic = _this.theme.getGridContainer();
                aPane.appendChild(_this.row_container_basic);

                if (_this.tabPanesContainer.childElementCount === 0) {
                  _this.tabPanesContainer.appendChild(aPane);
                } else {
                  _this.tabPanesContainer.insertBefore(aPane, _this.tabPanesContainer.childNodes[1]);
                }
              }

              _this.row_container_basic.appendChild(holder);
            }

            _this.addRow(editor, _this.tabs_holder, aPane);

            aPane.id = _this.getValidId(editor.schema.title);
            /* editor.schema.path//tab_text.textContent */
          } else {
            _this.row_container.appendChild(holder);
          }

          editor.setContainer(holder);
          editor.build();
          editor.postBuild();
          editor.setOptInCheckbox(editor.header);
        });

        if (this.rows[0]) {
          trigger(this.rows[0].tab, 'click');
        }
        /* Show/Hide button */


        this.collapsed = false;
        this.collapse_control = this.getButton('', 'collapse', 'button_collapse');
        this.collapse_control.classList.add('json-editor-btntype-toggle');
        this.title.insertBefore(this.collapse_control, this.title.childNodes[0]); // <!-- Start PatternKit overrides. -->
        // Replaces the click handler on the button (element `this.collapse_control`),
        // so that the section is toggled if you click either on the button or its label
        // (i.e., if you clicked anywhere on the title).

        this.title.classList.add('patternkit-jsoneditor-clickable');
        this.title.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          if (_this.collapsed) {
            _this.editor_holder.style.display = '';
            _this.collapsed = false;

            _this.setButtonText(_this.collapse_control, '', 'collapse', 'button_collapse');
          } else {
            _this.editor_holder.style.display = 'none';
            _this.collapsed = true;

            _this.setButtonText(_this.collapse_control, '', 'expand', 'button_expand');
          }
        }); // <!-- End PatternKit overrides. -->

        /* If it should start collapsed */

        if (this.options.collapsed) {
          trigger(this.collapse_control, 'click');
        }
        /* Collapse button disabled */


        if (this.schema.options && typeof this.schema.options.disable_collapse !== 'undefined') {
          if (this.schema.options.disable_collapse) this.collapse_control.style.display = 'none';
        } else if (this.jsoneditor.options.disable_collapse) {
          this.collapse_control.style.display = 'none';
        }
        /* Edit JSON Button */


        this.editjson_control = this.getButton('JSON', 'edit', 'button_edit_json');
        this.editjson_control.classList.add('json-editor-btntype-editjson');
        this.editjson_control.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this.toggleEditJSON();
        });
        this.controls.appendChild(this.editjson_control);
        this.controls.insertBefore(this.editjson_holder, this.controls.childNodes[0]);
        /* Edit JSON Buttton disabled */

        if (this.schema.options && typeof this.schema.options.disable_edit_json !== 'undefined') {
          if (this.schema.options.disable_edit_json) this.editjson_control.style.display = 'none';
        } else if (this.jsoneditor.options.disable_edit_json) {
          this.editjson_control.style.display = 'none';
        }
        /* Object Properties Button */


        this.addproperty_button = this.getButton('properties', 'edit_properties', 'button_object_properties');
        this.addproperty_button.classList.add('json-editor-btntype-properties');
        this.addproperty_button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this.toggleAddProperty();
        });
        this.controls.appendChild(this.addproperty_button);
        this.controls.insertBefore(this.addproperty_holder, this.controls.childNodes[1]);
        this.refreshAddProperties();
        /* non required properties start deactivated */

        this.deactivateNonRequiredProperties();
      }
      /* Fix table cell ordering */


      if (this.options.table_row) {
        this.editor_holder = this.container;
        this.property_order.forEach(function (key) {
          _this.editor_holder.appendChild(_this.editors[key].container);
        });
        /* Layout object editors in grid if needed */
      } else {
        /* Initial layout */
        this.layoutEditors();
        /* Do it again now that we know the approximate heights of elements */

        this.layoutEditors();
      }
    }
  }]);

  return PatternkitJsoneditorEditorObject;
}(JSONEditor.defaults.editors.object);

function patternkitEditorObject($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditorObject = {
    attach: function attach(context, settings) {
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
  };
}

},{}],5:[function(require,module,exports){
"use strict";

var _patternkitJsoneditorCkeditor = require("./patternkit.jsoneditor.ckeditor5.es6");

var _patternkitJsoneditorCygnetEs = require("./patternkit.jsoneditor.cygnet.es6.js");

var _patternkitJsoneditorEditorObject = require("./patternkit.jsoneditor.editor.object.es6");

var _patternkitJsoneditorEditorArray = require("./patternkit.jsoneditor.editor.array.es6");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

// Instantiates wysiwyg plugins.
(0, _patternkitJsoneditorCkeditor.patternkitEditorCKEditor5)(jQuery, Drupal, JSONEditor);
(0, _patternkitJsoneditorCygnetEs.patternkitEditorCygnet)(jQuery, Drupal, JSONEditor); // Overrides the object and array json-editor editors, to customize certain
// methods.  The only use case so far is to trigger toggling of items by
// clicking on the button's title instead of just the small button itself.

(0, _patternkitJsoneditorEditorObject.patternkitEditorObject)(jQuery, Drupal, JSONEditor);
(0, _patternkitJsoneditorEditorArray.patternkitEditorArray)(jQuery, Drupal, JSONEditor);

(function ($, Drupal, JSONEditor, once) {
  'use strict';

  var PatternkitJsonEditor = /*#__PURE__*/function () {
    function PatternkitJsonEditor(target, settings, defaults) {
      _classCallCheck(this, PatternkitJsonEditor);

      this.target = target;
      this.settings = settings;
      this.editor_root = document;
      this.beforeSerializeCallbacks = [];
      this.beforeSubmitCallbacks = [];
      this.data = {
        schema: JSON.parse(settings.schemaJson),
        starting: JSON.parse(settings.startingJson)
      };
    }

    _createClass(PatternkitJsonEditor, [{
      key: "loadTheme",
      value: function loadTheme() {
        var theme_js = this.settings.themeJS;

        if (typeof theme_js === 'string') {
          theme_js = [theme_js];
        }

        for (var i = 0; theme_js && i < theme_js.length; i++) {
          var script_element = document.createElement('script');
          script_element.type = "text/javascript";
          script_element.src = theme_js[i];
          document.getElementsByTagName('head')[0].appendChild(script_element);
        }

        var editor_dom = '';

        if (this.settings.themeStylesheet) {
          var theme_element = document.createElement('link');
          theme_element.rel = "stylesheet";
          theme_element.id = "theme_stylesheet";
          theme_element.href = this.settings.themeStylesheet;
          document.getElementsByTagName('head')[0].appendChild(theme_element);
          editor_dom = '<link rel="stylesheet" id="theme_stylesheet" href="' + this.settings.themeStylesheet + '">';
        } // @todo Re-eval with this shadow dom webfont bug:
        // https://bugs.chromium.org/p/chromium/issues/detail?id=336876


        if (this.settings.iconStylesheet) {
          var icons_element = document.createElement('link');
          icons_element.rel = "stylesheet";
          icons_element.id = "icon_stylesheet";
          icons_element.href = this.settings.iconStylesheet;
          document.getElementsByTagName('head')[0].appendChild(icons_element);
          editor_dom += '<link rel="stylesheet" id="icon_stylesheet" href="' + this.settings.iconStylesheet + '">';
        }

        editor_dom += '<div id="editor_holder"></div>';
        this.target.html(editor_dom);
      }
    }, {
      key: "setDefaults",
      value: function setDefaults() {
        JSONEditor.defaults.options.theme = this.settings.theme;
        JSONEditor.defaults.options.iconlib = this.settings.icons;
        JSONEditor.defaults.options.keep_oneof_values = false;
        JSONEditor.defaults.options.disable_edit_json = this.settings.disableJsonEditButton;
        JSONEditor.defaults.options.disable_collapse = false;
        JSONEditor.defaults.options.collapse = false;
        JSONEditor.defaults.options.ajax = true;
        JSONEditor.defaults.options.ajax_cache_responses = this.settings.ajaxCacheResponses;
        JSONEditor.defaults.options.ajax_cache_buster = this.settings.ajaxCacheBuster;
        JSONEditor.defaults.options.disable_properties = this.settings.disablePropertiesButtons; // Overrides alert message when removing an item.

        JSONEditor.defaults.languages.en.button_delete_node_warning = Drupal.t('Are you sure you want to remove this item?'); // Override object properties button text for user friendliness.

        JSONEditor.defaults.languages.en.button_properties = Drupal.t('Properties');
        JSONEditor.defaults.languages.en.button_object_properties = Drupal.t('Properties'); // Uses Handlebars template engine so that we can use logic in
        // `headerTemplate` property in schemas.

        JSONEditor.defaults.options.template = 'handlebars'; // @todo Loop through all editor plugins and add them at runtime.
        // Override how references are resolved.

        JSONEditor.prototype._loadExternalRefs = function (schema, callback) {
          var _this = this;

          var refs = this._getExternalRefs(schema);

          var done = 0,
              waiting = 0,
              callback_fired = false;
          $.each(refs, function (url) {
            if (_this.refs[url]) {
              return;
            }

            if (!_this.options.ajax) {
              throw "Must set ajax option to true to load external ref " + url;
            }

            _this.refs[url] = 'loading';
            waiting++;
            var r = new XMLHttpRequest();
            var uri = settings.path.baseUrl + url;
            r.open("GET", uri, true);

            r.onreadystatechange = function () {
              if (r.readyState !== 4) {
                return;
              } // Request succeeded.


              if (r.status === 200) {
                var response;

                try {
                  response = JSON.parse(r.responseText);
                } catch (e) {
                  window.console.log(e);
                  throw "Failed to parse external ref " + url;
                } // @todo Actually validate the schema so we can throw an error.


                if (!response || _typeof(response) !== "object") {
                  throw "External ref does not contain a valid schema - " + url;
                }

                _this.refs[url] = response;

                _this._loadExternalRefs(response, function () {
                  done++;

                  if (done >= waiting && !callback_fired) {
                    callback_fired = true;
                    callback();
                  }
                });
              } // Request failed.
              else {
                  window.console.log(r);
                  throw "Failed to fetch ref via ajax- " + url;
                }
            };

            r.send();
          });

          if (!waiting) {
            callback();
          }
        };
      }
    }, {
      key: "loadEditor",
      value: function loadEditor() {
        // Initialize the editor with a JSON schema.
        var config = {
          schema: this.data.schema,
          refs: {}
        };
        window.patternkitEditor = new JSONEditor(this.editor_root.getElementById('editor_holder'), config);
        window.patternkitEditor.patternkit = this;
        window.patternkitEditor.on('ready', this.onReady.bind(this));
        window.patternkitEditor.on('change', this.saveSchema.bind(this)); // Expose methods for editors to register AJAX event cycle callbacks.

        var self = this;

        window.patternkitEditor.beforeSerialize = function (callback) {
          self.beforeSerializeCallbacks.push(callback);
        };

        window.patternkitEditor.beforeSubmit = function (callback) {
          self.beforeSubmitCallbacks.push(callback);
        };
      }
    }, {
      key: "saveSchema",
      value: function saveSchema() {
        $('#schema_instance_config').val(JSON.stringify(window.patternkitEditor.getValue()));
      }
    }, {
      key: "onReady",
      value: function onReady() {
        try {
          // If we provide starting JSON as a value, JSON Editor hides all
          // non-required fields, which is desired behavior by most users of
          // the library. For patterns, we want to include any new schema
          // fields in our values, so they are displayed by default, optional or
          // not. This also allows us to pre-populate based on the schema
          // provided.
          if (_typeof(this.data.starting) === 'object' && !$.isEmptyObject(this.data.starting)) {
            window.patternkitEditor.setValue(_objectSpread(_objectSpread({}, window.patternkitEditor.getValue()), this.data.starting));
          }
        } catch (e) {
          window.console.error(e);
        }

        this.hideLoadingIndicator();
      }
    }, {
      key: "beforeSerialize",
      value: function beforeSerialize() {
        var callbacks = this.beforeSerializeCallbacks;
        callbacks.forEach(function (callback) {
          callback();
        });
      }
    }, {
      key: "beforeSubmit",
      value: function beforeSubmit() {
        var callbacks = this.beforeSubmitCallbacks;
        callbacks.forEach(function (callback) {
          callback();
        });
      }
    }, {
      key: "onSubmit",
      value: function onSubmit(e) {
        // Call all serialize and submission handlers since this function is only
        // triggered outside an AJAX content and those handlers would not be
        // called otherwise.
        this.beforeSerialize();
        this.beforeSubmit();
        this.saveSchema();
        window.patternkitEditor.destroy();
        delete window.patternkitEditor;
        $(once.remove('patternkit-editor', $('#patternkit-editor-target')));
        $(this).off('submit');
      }
    }, {
      key: "showLoadingIndicator",
      value: function showLoadingIndicator() {
        var loadingIndicatorClass = 'js-patternkit-loading__on'; // Only add the indicator if it isn't already present.

        var $indicator = $('.' + loadingIndicatorClass);

        if ($indicator.length === 0) {
          this.target.before("<div class=\"".concat(loadingIndicatorClass, "\"></div>"));
        }
      }
    }, {
      key: "hideLoadingIndicator",
      value: function hideLoadingIndicator() {
        var loadingIndicatorClass = 'js-patternkit-loading__on';
        $('.' + loadingIndicatorClass).remove();
      }
    }]);

    return PatternkitJsonEditor;
  }();

  Drupal.behaviors.patternkitEditor = {
    attach: function attach(context, settings) {
      if (!window.JSONEditor) {
        return;
      } // Ajax command response to allow updating Editor field values.


      Drupal.AjaxCommands.prototype.patternkitEditorUpdate = function (ajax, response, status) {
        window.patternkitEditor.getEditor(response.selector).setValue(response.value);
      };

      var $target = $('#patternkit-editor-target', context);
      var editor = new PatternkitJsonEditor($target, settings.patternkitEditor);
      $(once('patternkit-editor', $target)).each(function () {
        editor.showLoadingIndicator();
        editor.loadTheme();
        editor.setDefaults();
        editor.loadEditor();
        var $submitButton = $('[data-drupal-selector="edit-actions-submit"]');
        var $form = $submitButton.parent('form'); // Get the ajax handler instance.

        var id = $submitButton.attr('id');
        var ajax = Drupal.ajax.instances.find(function (element) {
          return element && element.selector && element.selector === '#' + id;
        }); // If there is no AJAX handler for the submit button, we're most likely
        // viewing the form directly without an AJAX interface. In this scenario,
        // we need to capture and react to the form submit event directly since
        // there will be no AJAX event cycle to hook into.

        if (ajax === undefined) {
          $(once('json-editor-processed', $form, context)).each(function () {
            $(this).on('submit', editor.onSubmit.bind(editor));
          }); // Exit here since there is no more handling needed.

          return;
        } // Drupal triggers Ajax submit via input events before the form events
        // bubble up for listeners, so we need to add a pre-hook within this
        // event cycle to trigger the editor update with latest field values
        // before the values are finalized and submitted.


        if (!ajax.hasOwnProperty('beforeSerialize')) {
          var beforeSerialize = ajax.beforeSerialize;

          ajax.beforeSerialize = function (element, options) {
            if (window.patternkitEditor) {
              editor.beforeSerialize();
              editor.saveSchema();
            }

            beforeSerialize.apply(this, [element, options]);
          };
        } // Drupal triggers Ajax submit via input events.
        // This is before allowing other events, so we need to add a pre-hook
        // to trigger the editor update with latest field values.
        // @todo Add handling for AJAX errors and re-attach.


        if (!ajax.hasOwnProperty('beforeSubmit')) {
          var beforeSubmit = ajax.beforeSubmit;

          ajax.beforeSubmit = function (formValues, elementSettings, options) {
            if (window.patternkitEditor) {
              // Run any registered beforeSubmit handlers before destroying the
              // JSONEditor form.
              editor.beforeSubmit(); // Ensure the submitted form values are updated with all changes.
              // If the submit button was clicked without clicking out of a text
              // field, for example, the change event from that field will not
              // have fired, and the serialized form values will not have been
              // updated. In this case, we need to make sure the submitted config
              // value is updated.

              var index = formValues.findIndex(function (o) {
                return o.name === "settings[instance_config]";
              });

              if (index !== -1) {
                window.patternkitEditor.disable();
                formValues[index].value = JSON.stringify(window.patternkitEditor.getValue());
              }
            }

            beforeSubmit.apply(this, [formValues, elementSettings, options]);
          };
        }
      });
    },
    detach: function detach(context, settings, trigger) {
      var editors;

      if (trigger === 'serialize') {
        editors = once.filter('patternkit-editor', '#patternkit-editor-target', context);
      } else {
        editors = once.remove('patternkit-editor', '#patternkit-editor-target', context);
      }

      editors.forEach(function (editor) {
        if (window.patternkitEditor) {
          if (trigger === 'serialize') {
            window.patternkitEditor.patternkit.saveSchema();
          } else {
            // Add a status flag to easily check when the editor is being
            // destroyed.
            window.patternkitEditor.detaching = true;
            window.patternkitEditor.disable();
            window.patternkitEditor.destroy();
            delete window.patternkitEditor;
          }
        }
      });
    }
  };
})(jQuery, Drupal, JSONEditor, once);

},{"./patternkit.jsoneditor.ckeditor5.es6":1,"./patternkit.jsoneditor.cygnet.es6.js":2,"./patternkit.jsoneditor.editor.array.es6":3,"./patternkit.jsoneditor.editor.object.es6":4}]},{},[5]);
