(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = _default;

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
 * Overrides json-editor arrpy addControls() method.
 *
 * Overrides JSONEditor's addControls() method for arrays. The only change is to
 * trigger toggle of section if user clicks on the label/title, not just on
 * the expand/collapse button. Makes hiding/showing sections much easier.
 */


function _default() {
  var _this = this;

  this.collapsed = false;
  this.toggle_button = this.getButton('', 'collapse', this.translate('button_collapse'));
  this.toggle_button.classList.add('json-editor-btntype-toggle');
  this.toggle_button.style.margin = '0 10px 0 0';
  this.title.insertBefore(this.toggle_button, this.title.childNodes[0]);
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

  /* If it should start collapsed */

  if (this.options.collapsed) {
    trigger(this.toggle_button, 'click');
  }
  /* Collapse button disabled */


  if (this.schema.options && typeof this.schema.options.disable_collapse !== 'undefined') {
    if (this.schema.options.disable_collapse) this.toggle_button.style.display = 'none';
  } else if (this.jsoneditor.options.disable_collapse) {
    this.toggle_button.style.display = 'none';
  }
  /* Add "new row" and "delete last" buttons below editor */


  this.add_row_button = this.getButton(this.getItemTitle(), 'add', this.translate('button_add_row_title', [this.getItemTitle()]));
  this.add_row_button.classList.add('json-editor-btntype-add');
  this.add_row_button.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var i = _this.rows.length;
    var editor;

    if (_this.row_cache[i]) {
      editor = _this.rows[i] = _this.row_cache[i];

      _this.rows[i].setValue(_this.rows[i].getDefault(), true);

      _this.rows[i].container.style.display = '';
      if (_this.rows[i].tab) _this.rows[i].tab.style.display = '';

      _this.rows[i].register();
    } else {
      editor = _this.addRow();
    }

    _this.active_tab = _this.rows[i].tab;

    _this.refreshTabs();

    _this.refreshValue();

    _this.onChange(true);

    _this.jsoneditor.trigger('addRow', editor);
  });
  this.controls.appendChild(this.add_row_button);
  this.delete_last_row_button = this.getButton(this.translate('button_delete_last', [this.getItemTitle()]), 'subtract', this.translate('button_delete_last_title', [this.getItemTitle()]));
  this.delete_last_row_button.classList.add('json-editor-btntype-deletelast');
  this.delete_last_row_button.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    if (!_this.askConfirmation()) {
      return false;
    }

    var rows = _this.getValue();

    var newActiveTab = null;
    var editor = rows.pop();

    _this.setValue(rows);

    if (_this.rows[_this.rows.length - 1]) {
      newActiveTab = _this.rows[_this.rows.length - 1].tab;
    }

    if (newActiveTab) {
      _this.active_tab = newActiveTab;

      _this.refreshTabs();
    }

    _this.onChange(true);

    _this.jsoneditor.trigger('deleteRow', editor);
  });
  this.controls.appendChild(this.delete_last_row_button);
  this.remove_all_rows_button = this.getButton(this.translate('button_delete_all'), 'delete', this.translate('button_delete_all_title'));
  this.remove_all_rows_button.classList.add('json-editor-btntype-deleteall');
  this.remove_all_rows_button.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    if (!_this.askConfirmation()) {
      return false;
    }

    _this.empty(true);

    _this.setValue([]);

    _this.onChange(true);

    _this.jsoneditor.trigger('deleteAllRows');
  });
  this.controls.appendChild(this.remove_all_rows_button);

  if (this.tabs) {
    this.add_row_button.style.width = '100%';
    this.add_row_button.style.textAlign = 'left';
    this.add_row_button.style.marginBottom = '3px';
    this.delete_last_row_button.style.width = '100%';
    this.delete_last_row_button.style.textAlign = 'left';
    this.delete_last_row_button.style.marginBottom = '3px';
    this.remove_all_rows_button.style.width = '100%';
    this.remove_all_rows_button.style.textAlign = 'left';
    this.remove_all_rows_button.style.marginBottom = '3px';
  }
}

},{}],2:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = _default;

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

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
 * Overrides json-editor object build() method.
 *
 * Overrides JSONEditor's build() method for objects. The only change is to
 * trigger toggle of section if user clicks on the label/title, not just on
 * the expand/collapse button. Makes hiding/showing sections much easier.
 */


function _default() {
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

    this.title = this.theme.getHeader(this.header);
    this.controls = this.theme.getButtonHolder();
    this.controls.style.margin = '0 0 0 10px';
    this.container.appendChild(this.title);
    this.container.appendChild(this.controls);
    this.container.style.position = 'relative';
    /* Edit JSON modal */

    this.editjson_holder = this.theme.getModal();
    this.editjson_textarea = this.theme.getTextareaInput();
    this.editjson_textarea.style.height = '170px';
    this.editjson_textarea.style.width = '300px';
    this.editjson_textarea.style.display = 'block';
    this.editjson_save = this.getButton('Save', 'save', 'Save');
    this.editjson_save.classList.add('json-editor-btntype-save');
    this.editjson_save.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      _this.saveJSON();
    });
    this.editjson_copy = this.getButton('Copy', 'copy', 'Copy');
    this.editjson_copy.classList.add('json-editor-btntype-copy');
    this.editjson_copy.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      _this.copyJSON();
    });
    this.editjson_cancel = this.getButton('Cancel', 'cancel', 'Cancel');
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
    this.addproperty_list.style.width = '295px';
    this.addproperty_list.style.maxHeight = '160px';
    this.addproperty_list.style.padding = '5px 0';
    this.addproperty_list.style.overflowY = 'auto';
    this.addproperty_list.style.overflowX = 'hidden';
    this.addproperty_list.style.paddingLeft = '5px';
    this.addproperty_list.setAttribute('class', 'property-selector');
    this.addproperty_add = this.getButton('add', 'add', 'add');
    this.addproperty_add.classList.add('json-editor-btntype-add');
    this.addproperty_input = this.theme.getFormInputField('text');
    this.addproperty_input.setAttribute('placeholder', 'Property name...');
    this.addproperty_input.style.width = '220px';
    this.addproperty_input.style.marginBottom = '0';
    this.addproperty_input.style.display = 'inline-block';
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

    document.addEventListener('click', this.onOutsideModalClick);
    /* Description */

    if (this.schema.description) {
      this.description = this.theme.getDescription(this.schema.description);
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
      this.tabs_holder = this.theme.getTopTabHolder(this.getValidId(this.schema.title));
      this.tabPanesContainer = this.theme.getTopTabContentHolder(this.tabs_holder);
      this.editor_holder.appendChild(this.tabs_holder);
    } else {
      this.tabs_holder = this.theme.getTabHolder(this.getValidId(this.schema.title));
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
    this.collapse_control = this.getButton('', 'collapse', this.translate('button_collapse'));
    this.collapse_control.style.margin = '0 10px 0 0';
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

        _this.setButtonText(_this.collapse_control, '', 'collapse', _this.translate('button_collapse'));
      } else {
        _this.editor_holder.style.display = 'none';
        _this.collapsed = true;

        _this.setButtonText(_this.collapse_control, '', 'expand', _this.translate('button_expand'));
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


    this.editjson_control = this.getButton('JSON', 'edit', 'Edit JSON');
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


    this.addproperty_button = this.getButton('Properties', 'edit_properties', this.translate('button_object_properties'));
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

},{}],3:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.patternkitEditorCKEditor = patternkitEditorCKEditor;

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
 * @file DrupalImageEditor class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */
var DrupalCKEditor = /*#__PURE__*/function (_JSONEditor$defaults$) {
  _inherits(DrupalCKEditor, _JSONEditor$defaults$);

  var _super = _createSuper(DrupalCKEditor);

  function DrupalCKEditor() {
    _classCallCheck(this, DrupalCKEditor);

    return _super.apply(this, arguments);
  }

  _createClass(DrupalCKEditor, [{
    key: "build",
    value: function build() {
      // Override the format when building the base string editor.
      this.options.format = 'textarea';

      _get(_getPrototypeOf(DrupalCKEditor.prototype), "build", this).call(this);

      this.input_type = this.schema.format;
      this.input.setAttribute('data-schemaformat', this.input_type);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      if (window.CKEDITOR) {
        // Editor options.
        // @todo Replace JSONEditor.defaults with this.defaults.
        this.options = jQuery.extend({}, JSONEditor.defaults.options.drupal_ckeditor || {}, this.options.drupal_ckeditor || {}); // Copies logic from Drupal.editors.ckeditor.attach(), so that certain
        // buttons (e.g., DrupalLink) work.

        this.options.ckeditor_config.drupal = {
          format: this.options.ckeditor_config.selected_toolbar
        }; // @see Drupal.editors.ckeditor._loadExternalPlugins

        var externalPlugins = this.options.ckeditor_config.drupalExternalPlugins;

        if (externalPlugins) {
          Object.keys(externalPlugins || {}).forEach(function (pluginName) {
            CKEDITOR.plugins.addExternal(pluginName, externalPlugins[pluginName], '');
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

        this.ckeditor_instance.on('change', function () {
          _this.input.value = _this.ckeditor_instance.getData();

          _this.refreshValue(); // Dirty means display cache is invalidated for string editors.


          _this.is_dirty = true;

          _this.onChange(true);
        });
        this.theme.afterInputReady(this.input);
      } else {
        _get(_getPrototypeOf(DrupalCKEditor.prototype), "afterInputReady", this).call(this);
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.ckeditor_instance) {
        this.ckeditor_instance.destroy(true);
        window.CKEDITOR.remove(this.ckeditor_instance);
        this.ckeditor_instance = null;
      }

      _get(_getPrototypeOf(DrupalCKEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(always_disabled) {
      if (always_disabled) {
        this.always_disabled = true;
      }

      if (this.ckeditor_instance) {
        this.ckeditor_instance.setReadOnly(true);
      }

      _get(_getPrototypeOf(DrupalCKEditor.prototype), "disable", this).call(this, always_disabled);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (this.always_disabled) {
        return;
      }

      if (this.ckeditor_instance) {
        this.ckeditor_instance.setReadOnly(false);
      }

      _get(_getPrototypeOf(DrupalCKEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 6;
    }
  }, {
    key: "setValue",
    value: function setValue(val, initial, from_template) {
      var input = _get(_getPrototypeOf(DrupalCKEditor.prototype), "setValue", this).call(this, val, initial, from_template);

      if (input !== undefined && input.changed && this.ckeditor_instance) {
        this.ckeditor_instance.setData(input.value);
        this.refreshWatchedFieldValues();
        this.onChange(true);
      }
    }
  }]);

  return DrupalCKEditor;
}(JSONEditor.defaults.editors.string);

function patternkitEditorCKEditor($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditorCKEditor = {
    attach: function attach(context, settings) {
      if (!window.JSONEditor) {
        return;
      }

      JSONEditor.defaults.options.drupal_ckeditor = {
        ckeditor_config: settings.patternkitEditor.patternkitCKEditorConfig || {}
      };
      JSONEditor.defaults.editors.drupal_ckeditor = DrupalCKEditor;
      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'string' && schema.format === 'html' && schema.options && schema.options.wysiwyg && settings.patternkitEditor.wysiwygEditorName === 'ckeditor') {
          return 'drupal_ckeditor';
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

      el.classList.add('je-form-input-label');
      return el;
    }
  }, {
    key: "getFormInputDescription",
    value: function getFormInputDescription(text) {
      var el = _get(_getPrototypeOf(cygnetTheme.prototype), "getFormInputDescription", this).call(this, text);

      el.classList.add('je-form-input-label');
      return el;
    }
  }, {
    key: "getIndentedPanel",
    value: function getIndentedPanel() {
      var el = _get(_getPrototypeOf(cygnetTheme.prototype), "getIndentedPanel", this).call(this);

      el.classList.add('je-indented-panel');
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

      el.classList.add('je-child-editor-holder');
      return el;
    } // If no title, use the text as title so that we have can use the
    // title attr as a CSS selector to style the collapse/expand state.

  }, {
    key: "setButtonText",
    value: function setButtonText(button, text, icon, title) {
      if (!title && text) {
        title = text;
      }

      if (text == "Object Properties") {
        text = "Properties";
      }

      return _get(_getPrototypeOf(cygnetTheme.prototype), "setButtonText", this).call(this, button, text, icon, title);
    }
  }, {
    key: "getHeaderButtonHolder",
    value: function getHeaderButtonHolder() {
      var el = this.getButtonHolder();
      el.classList.add('je-header-button-holder');
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

},{}],5:[function(require,module,exports){
"use strict";

var _patternkitJsoneditorQuillEs = require("./patternkit.jsoneditor.quill.es6.js");

var _patternkitJsoneditorCkeditor = require("./patternkit.jsoneditor.ckeditor.es6");

var _patternkitJsoneditorCygnetEs = require("./patternkit.jsoneditor.cygnet.es6.js");

var _BuildOverride = _interopRequireDefault(require("./json-editor-overrides/src/editors/object/BuildOverride"));

var _AddControlsOverride = _interopRequireDefault(require("./json-editor-overrides/src/editors/array/AddControlsOverride"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(0, _patternkitJsoneditorQuillEs.patternkitEditorQuill)(jQuery, Drupal, JSONEditor);
(0, _patternkitJsoneditorCkeditor.patternkitEditorCKEditor)(jQuery, Drupal, JSONEditor);
(0, _patternkitJsoneditorCygnetEs.patternkitEditorCygnet)(jQuery, Drupal, JSONEditor); // TODO Why is patternkitEditorProseMirror not included here?

(function ($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditor = {
    attach: function attach(context, settings) {
      if (!window.JSONEditor) {
        return;
      } // Ajax command response to allow updating Editor field values.


      Drupal.AjaxCommands.prototype.patternkitEditorUpdate = function (ajax, response, status) {
        window.patternkitEditor.getEditor(response.selector).setValue(response.value);
      };

      var saveSchema = function saveSchema() {
        $('#schema_instance_config').val(JSON.stringify(window.patternkitEditor.getValue()));

        if (window.M) {
          window.M.updateTextFields();
        }
      };

      var $target = $('#patternkit-editor-target', context);
      $target.once('patternkit-editor').each(function () {
        var theme_js = settings.patternkitEditor.themeJS;

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

        if (settings.patternkitEditor.themeStylesheet) {
          var theme_element = document.createElement('link');
          theme_element.rel = "stylesheet";
          theme_element.id = "theme_stylesheet";
          theme_element.href = settings.patternkitEditor.themeStylesheet;
          document.getElementsByTagName('head')[0].appendChild(theme_element);
          editor_dom = '<link rel="stylesheet" id="theme_stylesheet" href="' + settings.patternkitEditor.themeStylesheet + '">';
        } // @todo Re-eval with this shadow dom webfont bug:
        // https://bugs.chromium.org/p/chromium/issues/detail?id=336876


        if (settings.patternkitEditor.iconStylesheet) {
          var icons_element = document.createElement('link');
          icons_element.rel = "stylesheet";
          icons_element.id = "icon_stylesheet";
          icons_element.href = settings.patternkitEditor.iconStylesheet;
          document.getElementsByTagName('head')[0].appendChild(icons_element);
          editor_dom += '<link rel="stylesheet" id="icon_stylesheet" href="' + settings.patternkitEditor.iconStylesheet + '">';
        }

        editor_dom += '<div id="editor_holder"></div>';
        var editor_root = document; // We need to use a Shadow Dom to use themes, which has its own complications
        // with other JS libraries trying to access editor components, for example WYSIWYG.

        if (settings.patternkitEditor.useShadowDom) {
          var shadow = this.attachShadow({
            mode: 'open'
          });
          shadow.innerHTML += editor_dom;
          editor_root = $target[0].shadowRoot;
        } else {
          $target.html(editor_dom);
        }

        var data = {
          schema: JSON.parse(settings.patternkitEditor.schemaJson),
          starting: JSON.parse(settings.patternkitEditor.startingJson)
        };
        JSONEditor.defaults.options.theme = settings.patternkitEditor.theme;
        JSONEditor.defaults.options.iconlib = settings.patternkitEditor.icons;
        JSONEditor.defaults.options.keep_oneof_values = false;
        JSONEditor.defaults.options.disable_edit_json = true;
        JSONEditor.defaults.options.disable_collapse = false;
        JSONEditor.defaults.options.collapse = false;
        JSONEditor.defaults.options.ajax = true;
        JSONEditor.defaults.options.disable_properties = settings.patternkitEditor.disablePropertiesButtons; // @todo Loop through all editor plugins and add them at runtime.
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
        }; // Initialize the editor with a JSON schema.


        var config = {
          schema: data.schema,
          refs: {}
        };
        window.patternkitEditor = new JSONEditor(editor_root.getElementById('editor_holder'), config);
        window.patternkitEditor.on('ready', function () {
          // If we provide starting JSON as a value, JSON Editor hides all
          // non-required fields, which is desired behavior by most users of the
          // library. For patterns, we want to include any new schema fields in
          // our values so they are displayed by default, optional or not.
          // This also allows us to pre-populate based on the schema provided.
          if (_typeof(data.starting) === 'object' && !$.isEmptyObject(data.starting)) {
            window.patternkitEditor.setValue(_objectSpread(_objectSpread({}, window.patternkitEditor.getValue()), data.starting));
          } // Material Design JS doesn't update fields on ready event.
          // We call it to make up for that gap.


          if (window.M) {
            window.M.updateTextFields();
          }
        });
        window.patternkitEditor.on('change', saveSchema); // Drupal triggers Ajax submit via input events.
        // This is before allowing other events, so we need to add a pre-hook
        // to trigger the editor update with latest field values.
        // @todo Add handling for AJAX errors and re-attach.

        var parent_call = Drupal.Ajax.prototype.beforeSubmit;

        Drupal.Ajax.prototype.beforeSubmit = function (formValues, elementSettings, options) {
          if (window.patternkitEditor) {
            var index = formValues.findIndex(function (o) {
              return o.name === "settings[instance_config]";
            });

            if (index !== -1) {
              window.patternkitEditor.disable();
              saveSchema();
              formValues[index] = {
                name: "settings[instance_config]",
                value: JSON.stringify(window.patternkitEditor.getValue()),
                type: "hidden",
                required: false
              };
              window.patternkitEditor.destroy();
              delete window.patternkitEditor;
              $target.removeOnce('patternkit-editor');
            }
          }

          parent_call.call(this, formValues, elementSettings, options);
        };

        $('[data-drupal-selector="edit-actions-submit"]').parent('form').once().each(function () {
          var _this2 = this;

          $(this).on('submit', function (e) {
            window.patternkitEditor.disable();
            saveSchema();
            window.patternkitEditor.destroy();
            delete window.patternkitEditor;
            $target.removeOnce('patternkit-editor');
            $(_this2).off('submit');
          });
        });
      }); // If user closes the Layout Builder dialog without saving, this cleans
      // up Patternkit (and, in turn) CKEditor instance.  Code stolen from
      // `Drupal.behaviors.offCanvasEvents`.

      $(window).once('patternkit-jsoneditor-off-canvas').on('dialog:beforeclose', function (event, dialog, $element) {
        // The editor may have already been destroyed if its form was
        // submitted. The following code only destroys the editor if the modal
        // gets closed without submitting the form.
        if (window.patternkitEditor) {
          window.patternkitEditor.disable();
          window.patternkitEditor.destroy();
          delete window.patternkitEditor;
        }
      });
    }
  }; // Overrides specific methods of schema-specific editors. The only use case
  // so far is to trigger toggling of items by clicking on the button's title
  // instead of just the small button itself.

  JSONEditor.defaults.editors.object.prototype.build = _BuildOverride["default"];
  JSONEditor.defaults.editors.array.prototype.addControls = _AddControlsOverride["default"]; // Uses Handlebars template engine so that we can use logic in
  // `headerTemplate` property in schemas.

  JSONEditor.defaults.options.template = 'handlebars';
})(jQuery, Drupal, JSONEditor);

},{"./json-editor-overrides/src/editors/array/AddControlsOverride":1,"./json-editor-overrides/src/editors/object/BuildOverride":2,"./patternkit.jsoneditor.ckeditor.es6":3,"./patternkit.jsoneditor.cygnet.es6.js":4,"./patternkit.jsoneditor.quill.es6.js":6}],6:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.patternkitEditorQuill = patternkitEditorQuill;

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

/*globals Quill:false */

/**
 * @file DrupalQuill class.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 * @external Quill
 */
var DrupalQuill = /*#__PURE__*/function (_JSONEditor$defaults$) {
  _inherits(DrupalQuill, _JSONEditor$defaults$);

  var _super = _createSuper(DrupalQuill);

  function DrupalQuill() {
    _classCallCheck(this, DrupalQuill);

    return _super.apply(this, arguments);
  }

  _createClass(DrupalQuill, [{
    key: "build",
    value: function build() {
      // Override the format when building the base string editor.
      this.options.format = 'textarea';

      _get(_getPrototypeOf(DrupalQuill.prototype), "build", this).call(this);

      this.input_type = this.schema.format;
      this.input.setAttribute('data-schemaformat', this.input_type);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      // Editor options.
      // @todo Replace JSONEditor.defaults with this.defaults.
      this.options = jQuery.extend({}, JSONEditor.defaults.options.drupal_quill || {}, this.options.drupal_quill || {});
      this.options.quill_config = {
        modules: {
          toolbar: [[{
            header: [1, 2, 3, 4, 5, 6, 7, false]
          }], ['bold', 'italic', 'underline'], ['image', 'code-block', 'video'], [{
            'indent': '-1'
          }, {
            'indent': '+1'
          }], [{
            'align': []
          }]]
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

      this.quill_instance.setHTML = function (html) {
        _this.quill_instance.root.innerHTML = html;
      };

      this.quill_instance.getHTML = function () {
        return _this.quill_instance.root.innerHTML;
      };

      this.quill_instance.setHTML(this.getValue());

      if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
        this.quill_instance.disable();
      }

      this.quill_instance.on('text-change', function () {
        _this.input.value = _this.quill_instance.getHTML();

        _this.refreshValue(); // Dirty means display cache is invalidated for string editors.


        _this.is_dirty = true;

        _this.onChange(true);
      });
      this.theme.afterInputReady(this.input);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.quill_instance) {
        this.quill_instance = null;
      }

      _get(_getPrototypeOf(DrupalQuill.prototype), "destroy", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(always_disabled) {
      if (always_disabled) {
        this.always_disabled = true;
      }

      if (this.quill_instance) {
        this.quill_instance.disable();
      }

      _get(_getPrototypeOf(DrupalQuill.prototype), "disable", this).call(this, always_disabled);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (this.always_disabled) {
        return;
      }

      if (this.quill_instance) {
        this.quill_instance.enable(true);
      }

      _get(_getPrototypeOf(DrupalQuill.prototype), "enable", this).call(this);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 6;
    }
  }, {
    key: "setValue",
    value: function setValue(val, initial, from_template) {
      var input = _get(_getPrototypeOf(DrupalQuill.prototype), "setValue", this).call(this, val, initial, from_template);

      if (input !== undefined && input.changed && this.quill_instance) {
        this.quill_instance.setHTML(input.value);
        this.refreshWatchedFieldValues();
        this.onChange(true);
      }
    }
  }]);

  return DrupalQuill;
}(JSONEditor.defaults.editors.string);

function patternkitEditorQuill($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditorQuill = {
    attach: function attach(context, settings) {
      if (!window.JSONEditor) {
        return;
      }

      JSONEditor.defaults.options.drupal_quill = {
        quill_config: settings.patternkitEditor.patternkitQuillConfig || {}
      };
      JSONEditor.defaults.editors.drupal_quill = DrupalQuill;
      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'string' && schema.format === 'html' && schema.options && schema.options.wysiwyg && settings.patternkitEditor.wysiwygEditorName == 'quill') {
          return 'drupal_quill';
        }
      });
    }
  };
}

},{}]},{},[5]);
