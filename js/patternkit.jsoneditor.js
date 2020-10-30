(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

var _patternkitJsoneditorQuillEs = require("./patternkit.jsoneditor.quill.es6.js");

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(0, _patternkitJsoneditorQuillEs.patternkitEditorQuill)(jQuery, Drupal, JSONEditor);

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

      var $target = $('#editor-shadow-injection-target', context);
      $target.once('patternkit-editor').each(function () {
        var shadow = this.attachShadow({
          mode: 'open'
        });
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
        shadow.innerHTML += editor_dom;
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
        JSONEditor.defaults.options.ajax = true; // @todo Loop through all editor plugins and add them at runtime.
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
        window.patternkitEditor = new JSONEditor($target[0].shadowRoot.getElementById('editor_holder'), config);
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
            }
          }

          parent_call.call(this, formValues, elementSettings, options);
        };

        $('[data-drupal-selector="edit-actions-submit"]').parent('form').once().each(function () {
          $(this).on('submit', function (e) {
            e.preventDefault();
            window.patternkitEditor.disable();
            saveSchema();
            window.patternkitEditor.destroy();
            delete window.patternkitEditor;
            $(this).off('submit');
          });
        });
      });
    }
  };
})(jQuery, Drupal, JSONEditor);

},{"./patternkit.jsoneditor.quill.es6.js":2}],2:[function(require,module,exports){
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
      console.log(this.options.quill_config);
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
        if (schema.type === 'string' && schema.format === 'xhtml' && schema.options.wysiwyg) {
          return 'drupal_quill';
        }
      });
    }
  };
}

},{}]},{},[1]);
