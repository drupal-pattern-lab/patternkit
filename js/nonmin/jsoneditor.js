/*!
 * /**
 * * @name JSON Editor
 * * @description JSON Schema Based Editor
 * * This library is the continuation of jdorn's great work (see also https://github.com/jdorn/json-editor/issues/800)
 * * @version "2.3.0"
 * * @author Jeremy Dorn
 * * @see https://github.com/jdorn/json-editor/
 * * @see https://github.com/json-editor/json-editor
 * * @license MIT
 * * @example see README.md and docs/ for requirements, examples and usage info
 * * /
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else {
		var a = factory();
		for(var i in a) (typeof exports === 'object' ? exports : root)[i] = a[i];
	}
})(window, function() {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/dist/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/core.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/core.js":
/*!*********************!*\
  !*** ./src/core.js ***!
  \*********************/
/*! exports provided: JSONEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "JSONEditor", function() { return JSONEditor; });
/* harmony import */ var _defaults_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./defaults.js */ "./src/defaults.js");
/* harmony import */ var _validator_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./validator.js */ "./src/validator.js");
/* harmony import */ var _schemaloader_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./schemaloader.js */ "./src/schemaloader.js");
/* harmony import */ var _editors_index_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./editors/index.js */ "./src/editors/index.js");
/* harmony import */ var _templates_index_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./templates/index.js */ "./src/templates/index.js");
/* harmony import */ var _iconlibs_index_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./iconlibs/index.js */ "./src/iconlibs/index.js");
/* harmony import */ var _themes_index_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./themes/index.js */ "./src/themes/index.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./utilities.js */ "./src/utilities.js");
/* harmony import */ var _editor__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./editor */ "./src/editor.js");
/* harmony import */ var _theme__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./theme */ "./src/theme.js");
/* harmony import */ var _iconlib__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./iconlib */ "./src/iconlib.js");
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }












var JSONEditor = /*#__PURE__*/function () {
  function JSONEditor(element) {
    var _this = this;

    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    _classCallCheck(this, JSONEditor);

    if (!(element instanceof Element)) throw new Error('element should be an instance of Element');
    this.element = element;
    this.options = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_7__["extend"])({}, JSONEditor.defaults.options, options);
    this.ready = false;
    this.copyClipboard = null;
    this.schema = this.options.schema;
    this.template = this.options.template;
    this.translate = this.options.translate || JSONEditor.defaults.translate;
    this.uuid = 0;
    this.__data = {};
    var themeName = this.options.theme || JSONEditor.defaults.theme;
    var themeClass = JSONEditor.defaults.themes[themeName];
    /* Load editors and selected theme style rules */

    if (!themeClass) throw new Error("Unknown theme ".concat(themeName));
    this.element.setAttribute('data-theme', themeName); // eslint-disable-next-line new-cap

    this.theme = new themeClass(this);
    var rules = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_7__["extend"])(themeClass.rules, this.getEditorsRules());

    if (!this.theme.options.disable_theme_rules) {
      /* Attempt to locate a shadowRoot parent (i.e. in Web Components) */
      var shadowRoot = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_7__["getShadowParent"])(this.element);
      /* Call addNewStyleRulesToShadowRoot if shadowRoot is found, otherwise call addNewStyleRules */

      this[shadowRoot ? 'addNewStyleRulesToShadowRoot' : 'addNewStyleRules'](themeName, rules, shadowRoot);
    }
    /* Init icon class */


    var iconClass = JSONEditor.defaults.iconlibs[this.options.iconlib || JSONEditor.defaults.iconlib]; // eslint-disable-next-line new-cap

    if (iconClass) this.iconlib = new iconClass();
    this.root_container = this.theme.getContainer();
    this.element.appendChild(this.root_container);
    /* Fetch all external refs via ajax */

    var fetchUrl = document.location.origin + document.location.pathname.toString();
    var loader = new _schemaloader_js__WEBPACK_IMPORTED_MODULE_2__["SchemaLoader"](this.options);
    var location = document.location.toString();

    this.expandSchema = function (schema, fileBase) {
      return loader.expandSchema(schema, fileBase);
    };

    this.expandRefs = function (schema, fileBase) {
      return loader.expandRefs(schema, fileBase);
    };

    this.refs = loader.refs;
    loader.load(this.schema, function (schema) {
      var validatorOptions = _this.options.custom_validators ? {
        custom_validators: _this.options.custom_validators
      } : {};
      _this.validator = new _validator_js__WEBPACK_IMPORTED_MODULE_1__["Validator"](_this, null, validatorOptions, JSONEditor.defaults);

      var editorClass = _this.getEditorClass(schema);

      _this.root = _this.createEditor(editorClass, {
        jsoneditor: _this,
        schema: schema,
        required: true,
        container: _this.root_container
      });

      _this.root.preBuild();

      _this.root.build();

      _this.root.postBuild();
      /* Starting data */


      if (Object(_utilities_js__WEBPACK_IMPORTED_MODULE_7__["hasOwnProperty"])(_this.options, 'startval')) _this.root.setValue(_this.options.startval);
      _this.validation_results = _this.validator.validate(_this.root.getValue());

      _this.root.showValidationErrors(_this.validation_results);

      _this.ready = true;
      /* Fire ready event asynchronously */

      window.requestAnimationFrame(function () {
        if (!_this.ready) return;
        _this.validation_results = _this.validator.validate(_this.root.getValue());

        _this.root.showValidationErrors(_this.validation_results);

        _this.trigger('ready');

        _this.trigger('change');
      });
    }, fetchUrl, location);
  }

  _createClass(JSONEditor, [{
    key: "getValue",
    value: function getValue() {
      if (!this.ready) throw new Error("JSON Editor not ready yet.  Listen for 'ready' event before getting the value");
      return this.root.getValue();
    }
  }, {
    key: "setValue",
    value: function setValue(value) {
      if (!this.ready) throw new Error("JSON Editor not ready yet.  Listen for 'ready' event before setting the value");
      this.root.setValue(value);
      return this;
    }
  }, {
    key: "validate",
    value: function validate(value) {
      if (!this.ready) throw new Error("JSON Editor not ready yet.  Listen for 'ready' event before validating");
      /* Custom value */

      if (arguments.length === 1) {
        return this.validator.validate(value);
        /* Current value (use cached result) */
      } else {
        return this.validation_results;
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.destroyed) return;
      if (!this.ready) return;
      this.schema = null;
      this.options = null;
      this.root.destroy();
      this.root = null;
      this.root_container = null;
      this.validator = null;
      this.validation_results = null;
      this.theme = null;
      this.iconlib = null;
      this.template = null;
      this.__data = null;
      this.ready = false;
      this.element.innerHTML = '';
      this.element.removeAttribute('data-theme');
      this.destroyed = true;
    }
  }, {
    key: "on",
    value: function on(event, callback) {
      this.callbacks = this.callbacks || {};
      this.callbacks[event] = this.callbacks[event] || [];
      this.callbacks[event].push(callback);
      return this;
    }
  }, {
    key: "off",
    value: function off(event, callback) {
      /* Specific callback */
      if (event && callback) {
        this.callbacks = this.callbacks || {};
        this.callbacks[event] = this.callbacks[event] || [];
        var newcallbacks = [];

        for (var i = 0; i < this.callbacks[event].length; i++) {
          if (this.callbacks[event][i] === callback) continue;
          newcallbacks.push(this.callbacks[event][i]);
        }

        this.callbacks[event] = newcallbacks;
      } else if (event) {
        /* All callbacks for a specific event */
        this.callbacks = this.callbacks || {};
        this.callbacks[event] = [];
      } else {
        /* All callbacks for all events */
        this.callbacks = {};
      }

      return this;
    }
  }, {
    key: "trigger",
    value: function trigger(event, editor) {
      if (this.callbacks && this.callbacks[event] && this.callbacks[event].length) {
        for (var i = 0; i < this.callbacks[event].length; i++) {
          this.callbacks[event][i].apply(this, [editor]);
        }
      }

      return this;
    }
  }, {
    key: "setOption",
    value: function setOption(option, value) {
      if (option === 'show_errors') {
        this.options.show_errors = value;
        this.onChange();
      } else {
        /* Only the `show_errors` option is supported for now */
        throw new Error("Option ".concat(option, " must be set during instantiation and cannot be changed later"));
      }

      return this;
    }
  }, {
    key: "getEditorsRules",
    value: function getEditorsRules() {
      var extendRule = function extendRule(rules, editorClass) {
        return editorClass.rules ? Object(_utilities_js__WEBPACK_IMPORTED_MODULE_7__["extend"])(rules, editorClass.rules) : rules;
      };

      return Object.values(JSONEditor.defaults.editors).reduce(extendRule, {});
    }
  }, {
    key: "getEditorClass",
    value: function getEditorClass(schema) {
      var classname;
      schema = this.expandSchema(schema);
      JSONEditor.defaults.resolvers.find(function (resolver) {
        classname = resolver(schema);
        return classname && JSONEditor.defaults.editors[classname];
      });
      if (!classname) throw new Error("Unknown editor for schema ".concat(JSON.stringify(schema)));
      if (!JSONEditor.defaults.editors[classname]) throw new Error("Unknown editor ".concat(classname));
      return JSONEditor.defaults.editors[classname];
    }
  }, {
    key: "createEditor",
    value: function createEditor(editorClass, options) {
      var depthCounter = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 1;
      options = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_7__["extend"])({}, editorClass.options || {}, options); // eslint-disable-next-line new-cap

      return new editorClass(options, JSONEditor.defaults, depthCounter);
    }
  }, {
    key: "onChange",
    value: function onChange() {
      var _this2 = this;

      if (!this.ready) return;
      if (this.firing_change) return;
      this.firing_change = true;
      window.requestAnimationFrame(function () {
        _this2.firing_change = false;
        if (!_this2.ready) return;
        /* Validate and cache results */

        _this2.validation_results = _this2.validator.validate(_this2.root.getValue());

        if (_this2.options.show_errors !== 'never') {
          _this2.root.showValidationErrors(_this2.validation_results);
        } else {
          _this2.root.showValidationErrors([]);
        }
        /* Fire change event */


        _this2.trigger('change');
      });
      return this;
    }
  }, {
    key: "compileTemplate",
    value: function compileTemplate(template) {
      var name = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : JSONEditor.defaults.template;
      var engine;
      /* Specifying a preset engine */

      if (typeof name === 'string') {
        if (!JSONEditor.defaults.templates[name]) throw new Error("Unknown template engine ".concat(name));
        engine = JSONEditor.defaults.templates[name]();
        if (!engine) throw new Error("Template engine ".concat(name, " missing required library."));
      } else {
        /* Specifying a custom engine */
        engine = name;
      }

      if (!engine) throw new Error('No template engine set');
      if (!engine.compile) throw new Error('Invalid template engine set');
      return engine.compile(template);
    }
  }, {
    key: "_data",
    value: function _data(el, key, value) {
      /* Setting data */
      if (arguments.length === 3) {
        var uuid;

        if (el.hasAttribute("data-jsoneditor-".concat(key))) {
          uuid = el.getAttribute("data-jsoneditor-".concat(key));
        } else {
          uuid = this.uuid++;
          el.setAttribute("data-jsoneditor-".concat(key), uuid);
        }

        this.__data[uuid] = value;
      } else {
        /* Getting data */

        /* No data stored */
        if (!el.hasAttribute("data-jsoneditor-".concat(key))) return null;
        return this.__data[el.getAttribute("data-jsoneditor-".concat(key))];
      }
    }
  }, {
    key: "registerEditor",
    value: function registerEditor(editor) {
      this.editors = this.editors || {};
      this.editors[editor.path] = editor;
      return this;
    }
  }, {
    key: "unregisterEditor",
    value: function unregisterEditor(editor) {
      this.editors = this.editors || {};
      this.editors[editor.path] = null;
      return this;
    }
  }, {
    key: "getEditor",
    value: function getEditor(path) {
      if (!this.editors) return;
      return this.editors[path];
    }
  }, {
    key: "watch",
    value: function watch(path, callback) {
      this.watchlist = this.watchlist || {};
      this.watchlist[path] = this.watchlist[path] || [];
      this.watchlist[path].push(callback);
      return this;
    }
  }, {
    key: "unwatch",
    value: function unwatch(path, callback) {
      if (!this.watchlist || !this.watchlist[path]) return this;
      /* If removing all callbacks for a path */

      if (!callback) {
        this.watchlist[path] = null;
        return this;
      }

      var newlist = [];

      for (var i = 0; i < this.watchlist[path].length; i++) {
        if (this.watchlist[path][i] === callback) continue;else newlist.push(this.watchlist[path][i]);
      }

      this.watchlist[path] = newlist.length ? newlist : null;
      return this;
    }
  }, {
    key: "notifyWatchers",
    value: function notifyWatchers(path) {
      if (!this.watchlist || !this.watchlist[path]) return this;

      for (var i = 0; i < this.watchlist[path].length; i++) {
        this.watchlist[path][i]();
      }
    }
  }, {
    key: "isEnabled",
    value: function isEnabled() {
      return !this.root || this.root.isEnabled();
    }
  }, {
    key: "enable",
    value: function enable() {
      this.root.enable();
    }
  }, {
    key: "disable",
    value: function disable() {
      this.root.disable();
    }
  }, {
    key: "setCopyClipboardContents",
    value: function setCopyClipboardContents(value) {
      this.copyClipboard = value;
    }
  }, {
    key: "getCopyClipboardContents",
    value: function getCopyClipboardContents() {
      return this.copyClipboard;
    }
  }, {
    key: "addNewStyleRules",
    value: function addNewStyleRules(themeName, rules) {
      var styleTag = document.querySelector("#theme-".concat(themeName));

      if (!styleTag) {
        styleTag = document.createElement('style');
        styleTag.setAttribute('id', "theme-".concat(themeName));
        styleTag.appendChild(document.createTextNode(''));
        document.head.appendChild(styleTag);
      }

      var sheet = styleTag.sheet ? styleTag.sheet : styleTag.styleSheet;
      var qualifier = this.element.nodeName.toLowerCase();
      Object.keys(rules).forEach(function (selector) {
        var sel = "".concat(qualifier, "[data-theme=\"").concat(themeName, "\"] ").concat(selector); // all browsers, except IE before version 9

        if (sheet.insertRule) sheet.insertRule(sel + ' {' + decodeURIComponent(rules[selector]) + '}', 0); // Internet Explorer before version 9
        else if (sheet.addRule) sheet.addRule(sel, decodeURIComponent(rules[selector]), 0);
      });
    }
  }, {
    key: "addNewStyleRulesToShadowRoot",
    value: function addNewStyleRulesToShadowRoot(themeName, rules, shadowRoot) {
      var qualifier = this.element.nodeName.toLowerCase();
      var cssText = '';
      Object.keys(rules).forEach(function (selector) {
        var sel = "".concat(qualifier, "[data-theme=\"").concat(themeName, "\"] ").concat(selector);
        cssText += sel + ' {' + decodeURIComponent(rules[selector]) + '}' + '\n';
      });
      var styleSheet = new CSSStyleSheet();
      styleSheet.replaceSync(cssText);
      shadowRoot.adoptedStyleSheets = [].concat(_toConsumableArray(shadowRoot.adoptedStyleSheets), [styleSheet]);
    }
  }]);

  return JSONEditor;
}();
JSONEditor.defaults = _defaults_js__WEBPACK_IMPORTED_MODULE_0__["defaults"];
JSONEditor.AbstractEditor = _editor__WEBPACK_IMPORTED_MODULE_8__["AbstractEditor"];
JSONEditor.AbstractTheme = _theme__WEBPACK_IMPORTED_MODULE_9__["AbstractTheme"];
JSONEditor.AbstractIconLib = _iconlib__WEBPACK_IMPORTED_MODULE_10__["AbstractIconLib"];
Object.assign(JSONEditor.defaults.themes, _themes_index_js__WEBPACK_IMPORTED_MODULE_6__["themes"]);
Object.assign(JSONEditor.defaults.editors, _editors_index_js__WEBPACK_IMPORTED_MODULE_3__["editors"]);
Object.assign(JSONEditor.defaults.templates, _templates_index_js__WEBPACK_IMPORTED_MODULE_4__["templates"]);
Object.assign(JSONEditor.defaults.iconlibs, _iconlibs_index_js__WEBPACK_IMPORTED_MODULE_5__["iconlibs"]);

/***/ }),

/***/ "./src/defaults.js":
/*!*************************!*\
  !*** ./src/defaults.js ***!
  \*************************/
/*! exports provided: defaults */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "defaults", function() { return defaults; });
/* harmony import */ var _resolvers_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./resolvers.js */ "./src/resolvers.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }


/* default theme */

var theme = 'html';
/* default template engine */

var template = 'default';
/* Global callback list */

var callbacks = {};
var themes = {};
var templates = {};
var iconlibs = {};
var editors = {};
var languages = {}; // eslint-disable-next-line camelcase

var custom_validators = [];
/* Translation strings and default languages */
// eslint-disable-next-line camelcase

var default_language = 'en'; // eslint-disable-next-line camelcase

var language = default_language;
languages.en = {
  /**
   * When a property is not set
   */
  error_notset: 'Property must be set',

  /**
  * When a string must not be empty
  */
  error_notempty: 'Value required',

  /**
  * When a value is not one of the enumerated values
  */
  error_enum: 'Value must be one of the enumerated values',

  /**
  * When a value doesn't validate any schema of a 'anyOf' combination
  */
  error_anyOf: 'Value must validate against at least one of the provided schemas',

  /**
  * When a value doesn't validate
  * @variables This key takes one variable: The number of schemas the value does not validate
  */
  error_oneOf: 'Value must validate against exactly one of the provided schemas. It currently validates against {{0}} of the schemas.',

  /**
  * When a value does not validate a 'not' schema
  */
  error_not: 'Value must not validate against the provided schema',

  /**
  * When a value does not match any of the provided types
  */
  error_type_union: 'Value must be one of the provided types',

  /**
  * When a value does not match the given type
  * @variables This key takes one variable: The type the value should be of
  */
  error_type: 'Value must be of type {{0}}',

  /**
  *  When the value validates one of the disallowed types
  */
  error_disallow_union: 'Value must not be one of the provided disallowed types',

  /**
  *  When the value validates a disallowed type
  * @variables This key takes one variable: The type the value should not be of
  */
  error_disallow: 'Value must not be of type {{0}}',

  /**
  * When a value is not a multiple of or divisible by a given number
  * @variables This key takes one variable: The number mentioned above
  */
  error_multipleOf: 'Value must be a multiple of {{0}}',

  /**
  * When a value is greater than it's supposed to be (exclusive)
  * @variables This key takes one variable: The maximum
  */
  error_maximum_excl: 'Value must be less than {{0}}',

  /**
  * When a value is greater than it's supposed to be (inclusive
  * @variables This key takes one variable: The maximum
  */
  error_maximum_incl: 'Value must be at most {{0}}',

  /**
  * When a value is lesser than it's supposed to be (exclusive)
  * @variables This key takes one variable: The minimum
  */
  error_minimum_excl: 'Value must be greater than {{0}}',

  /**
  * When a value is lesser than it's supposed to be (inclusive)
  * @variables This key takes one variable: The minimum
  */
  error_minimum_incl: 'Value must be at least {{0}}',

  /**
  * When a value have too many characters
  * @variables This key takes one variable: The maximum character count
  */
  error_maxLength: 'Value must be at most {{0}} characters long',

  /**
  * When a value does not have enough characters
  * @variables This key takes one variable: The minimum character count
  */
  error_minLength: 'Value must be at least {{0}} characters long',

  /**
  * When a value does not match a given pattern
  */
  error_pattern: 'Value must match the pattern {{0}}',

  /**
  * When an array has additional items whereas it is not supposed to
  */
  error_additionalItems: 'No additional items allowed in this array',

  /**
  * When there are to many items in an array
  * @variables This key takes one variable: The maximum item count
  */
  error_maxItems: 'Value must have at most {{0}} items',

  /**
  * When there are not enough items in an array
  * @variables This key takes one variable: The minimum item count
  */
  error_minItems: 'Value must have at least {{0}} items',

  /**
  * When an array is supposed to have unique items but has duplicates
  */
  error_uniqueItems: 'Array must have unique items',

  /**
  * When there are too many properties in an object
  * @variables This key takes one variable: The maximum property count
  */
  error_maxProperties: 'Object must have at most {{0}} properties',

  /**
  * When there are not enough properties in an object
  * @variables This key takes one variable: The minimum property count
  */
  error_minProperties: 'Object must have at least {{0}} properties',

  /**
  * When a required property is not defined
  * @variables This key takes one variable: The name of the missing property
  */
  error_required: "Object is missing the required property '{{0}}'",

  /**
  * When there is an additional property is set whereas there should be none
  * @variables This key takes one variable: The name of the additional property
  */
  error_additional_properties: 'No additional properties allowed, but property {{0}} is set',

  /**
  * When a dependency is not resolved
  * @variables This key takes one variable: The name of the missing property for the dependency
  */
  error_dependency: 'Must have property {{0}}',

  /**
  * When a date is in incorrect format
  * @variables This key takes one variable: The valid format
  */
  error_date: 'Date must be in the format {{0}}',

  /**
  * When a time is in incorrect format
  * @variables This key takes one variable: The valid format
  */
  error_time: 'Time must be in the format {{0}}',

  /**
  * When a datetime-local is in incorrect format
  * @variables This key takes one variable: The valid format
  */
  error_datetime_local: 'Datetime must be in the format {{0}}',

  /**
  * When a integer date is less than 1 January 1970
  */
  error_invalid_epoch: 'Date must be greater than 1 January 1970',

  /**
  * When an IPv4 is in incorrect format
  */
  error_ipv4: 'Value must be a valid IPv4 address in the form of 4 numbers between 0 and 255, separated by dots',

  /**
  * When an IPv6 is in incorrect format
  */
  error_ipv6: 'Value must be a valid IPv6 address',

  /**
  * When a hostname is in incorrect format
  */
  error_hostname: 'The hostname has the wrong format',

  /**
  * Text on Delete All buttons
  */
  button_delete_all: 'All',

  /**
  * Title on Delete All buttons
  */
  button_delete_all_title: 'Delete All',

  /**
  * Text on Delete Last buttons
  * @variable This key takes one variable: The title of object to delete
  */
  button_delete_last: 'Last {{0}}',

  /**
  * Title on Delete Last buttons
  * @variable This key takes one variable: The title of object to delete
  */
  button_delete_last_title: 'Delete Last {{0}}',

  /**
  * Title on Add Row buttons
  * @variable This key takes one variable: The title of object to add
  */
  button_add_row_title: 'Add {{0}}',

  /**
  * Title on Move Down buttons
  */
  button_move_down_title: 'Move down',

  /**
  * Title on Move Up buttons
  */
  button_move_up_title: 'Move up',

  /**
  * Title on Object Properties buttons
  */
  button_object_properties: 'Object Properties',

  /**
  * Title on Delete Row buttons
  * @variable This key takes one variable: The title of object to delete
  */
  button_delete_row_title: 'Delete {{0}}',

  /**
  * Title on Delete Row buttons, short version (no parameter with the object title)
  */
  button_delete_row_title_short: 'Delete',

  /**
  * Title on Copy Row buttons, short version (no parameter with the object title)
  */
  button_copy_row_title_short: 'Copy',

  /**
  * Title on Collapse buttons
  */
  button_collapse: 'Collapse',

  /**
  * Title on Expand buttons
  */
  button_expand: 'Expand',

  /**
  * Title on Flatpickr toggle buttons
  */
  flatpickr_toggle_button: 'Toggle',

  /**
  * Title on Flatpickr clear buttons
  */
  flatpickr_clear_button: 'Clear',

  /**
  * Choices input field placeholder text
  */
  choices_placeholder_text: 'Start typing to add value',

  /**
  * Default title for array items
  */
  default_array_item_title: 'item',

  /**
  * Warning when deleting a node
  */
  button_delete_node_warning: 'Are you sure you want to remove this node?'
};
/* Default per-editor options */

Object.entries(editors).forEach(function (_ref) {
  var _ref2 = _slicedToArray(_ref, 2),
      i = _ref2[0],
      editor = _ref2[1];

  editors[i].options = editor.options || {};
});
/* Default upload handler */

function upload(type, file, cbs) {
  console.log('Upload handler required for upload editor');
}
/* String translate function */


function translate(key, variables) {
  var lang = defaults.languages[defaults.language];
  if (!lang) throw new Error("Unknown language ".concat(defaults.language));
  var string = lang[key] || defaults.languages[default_language][key];
  if (typeof string === 'undefined') throw new Error("Unknown translate string ".concat(key));

  if (variables) {
    for (var i = 0; i < variables.length; i++) {
      string = string.replace(new RegExp("\\{\\{".concat(i, "}}"), 'g'), variables[i]);
    }
  }

  return string;
}
/* Default options when initializing JSON Editor */


var options = {
  upload: upload,
  prompt_before_delete: true,
  use_default_values: true,
  max_depth: 0
};
/* This assignment was previously in index.js but makes more sense here */

var defaults = {
  options: options,
  theme: theme,
  template: template,
  themes: themes,
  callbacks: callbacks,
  templates: templates,
  iconlibs: iconlibs,
  editors: editors,
  languages: languages,
  resolvers: _resolvers_js__WEBPACK_IMPORTED_MODULE_0__["resolvers"],
  custom_validators: custom_validators,
  default_language: default_language,
  language: language,
  translate: translate
};

/***/ }),

/***/ "./src/editor.js":
/*!***********************!*\
  !*** ./src/editor.js ***!
  \***********************/
/*! exports provided: AbstractEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "AbstractEditor", function() { return AbstractEditor; });
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utilities.js */ "./src/utilities.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }


/**
 * All editors should extend from this class
 */

var AbstractEditor = /*#__PURE__*/function () {
  function AbstractEditor(options, defaults) {
    _classCallCheck(this, AbstractEditor);

    this.defaults = defaults;
    this.jsoneditor = options.jsoneditor;
    this.theme = this.jsoneditor.theme;
    this.template_engine = this.jsoneditor.template;
    this.iconlib = this.jsoneditor.iconlib;
    this.translate = this.jsoneditor.translate || this.defaults.translate;
    this.original_schema = options.schema;
    this.schema = this.jsoneditor.expandSchema(this.original_schema);
    this.active = true;
    this.options = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["extend"])({}, this.options || {}, this.schema.options || {}, options.schema.options || {}, options);
    if (!options.path && !this.schema.id) this.schema.id = 'root';
    this.path = options.path || 'root';
    this.formname = options.formname || this.path.replace(/\.([^.]+)/g, '[$1]');
    if (this.jsoneditor.options.form_name_root) this.formname = this.formname.replace(/^root\[/, "".concat(this.jsoneditor.options.form_name_root, "["));
    this.parent = options.parent;
    this.key = this.parent !== undefined ? this.path.split('.').slice(this.parent.path.split('.').length).join('.') : this.path;
    this.link_watchers = [];
    this.watchLoop = false;
    if (options.container) this.setContainer(options.container);
    this.registerDependencies();
  }

  _createClass(AbstractEditor, [{
    key: "onChildEditorChange",
    value: function onChildEditorChange(editor) {
      this.onChange(true);
    }
  }, {
    key: "notify",
    value: function notify() {
      if (this.path) this.jsoneditor.notifyWatchers(this.path);
    }
  }, {
    key: "change",
    value: function change() {
      if (this.parent) this.parent.onChildEditorChange(this);else if (this.jsoneditor) this.jsoneditor.onChange();
    }
  }, {
    key: "onChange",
    value: function onChange(bubble) {
      this.notify();
      if (this.watch_listener) this.watch_listener();
      if (bubble) this.change();
    }
  }, {
    key: "register",
    value: function register() {
      this.jsoneditor.registerEditor(this);
      this.onChange();
    }
  }, {
    key: "unregister",
    value: function unregister() {
      if (!this.jsoneditor) return;
      this.jsoneditor.unregisterEditor(this);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 12;
    }
  }, {
    key: "isActive",
    value: function isActive() {
      return this.active;
    }
  }, {
    key: "activate",
    value: function activate() {
      this.active = true;
      this.optInCheckbox.checked = true;
      this.enable();
      this.change();
    }
  }, {
    key: "deactivate",
    value: function deactivate() {
      /* only non required properties can be deactivated. */
      if (!this.isRequired()) {
        this.active = false;
        this.optInCheckbox.checked = false;
        this.disable();
        this.change();
      }
    }
  }, {
    key: "registerDependencies",
    value: function registerDependencies() {
      var _this = this;

      this.dependenciesFulfilled = true;
      var deps = this.options.dependencies;

      if (!deps) {
        return;
      }

      Object.keys(deps).forEach(function (dependency) {
        var path = _this.path.split('.');

        path[path.length - 1] = dependency;
        path = path.join('.');
        var choices = deps[dependency];

        _this.jsoneditor.watch(path, function () {
          _this.checkDependency(path, choices);
        });
      });
    }
  }, {
    key: "checkDependency",
    value: function checkDependency(path, choices) {
      var _this2 = this;

      var wrapper = this.container || this.control;

      if (this.path === path || !wrapper || this.jsoneditor === null) {
        return;
      }

      var editor = this.jsoneditor.getEditor(path);
      var value = editor ? editor.getValue() : undefined;
      var previousStatus = this.dependenciesFulfilled;
      this.dependenciesFulfilled = false;

      if (!editor || !editor.dependenciesFulfilled) {
        this.dependenciesFulfilled = false;
      } else if (Array.isArray(choices)) {
        choices.some(function (choice) {
          if (value === choice) {
            _this2.dependenciesFulfilled = true;
            return true;
          }
        });
      } else if (_typeof(choices) === 'object') {
        if (_typeof(value) !== 'object') {
          this.dependenciesFulfilled = choices === value;
        } else {
          Object.keys(choices).some(function (key) {
            if (!Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["hasOwnProperty"])(choices, key)) {
              return false;
            }

            if (!Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["hasOwnProperty"])(value, key) || choices[key] !== value[key]) {
              _this2.dependenciesFulfilled = false;
              return true;
            }

            _this2.dependenciesFulfilled = true;
          });
        }
      } else if (typeof choices === 'string' || typeof choices === 'number') {
        this.dependenciesFulfilled = value === choices;
      } else if (typeof choices === 'boolean') {
        if (choices) {
          this.dependenciesFulfilled = value || value.length > 0;
        } else {
          this.dependenciesFulfilled = !value || value.length === 0;
        }
      }

      if (this.dependenciesFulfilled !== previousStatus) {
        this.notify();
      }

      var displayMode = this.dependenciesFulfilled ? 'block' : 'none';

      if (wrapper.tagName === 'TD') {
        Object.keys(wrapper.childNodes).forEach(function (child) {
          return wrapper.childNodes[child].style.display = displayMode;
        });
      } else wrapper.style.display = displayMode;
    }
  }, {
    key: "setContainer",
    value: function setContainer(container) {
      this.container = container;
      if (this.schema.id) this.container.setAttribute('data-schemaid', this.schema.id);
      if (this.schema.type && typeof this.schema.type === 'string') this.container.setAttribute('data-schematype', this.schema.type);
      this.container.setAttribute('data-schemapath', this.path);
    }
  }, {
    key: "setOptInCheckbox",
    value: function setOptInCheckbox(header) {
      var _this3 = this;

      /* the active/deactive checbox control. */
      this.optInCheckbox = document.createElement('input');
      this.optInCheckbox.setAttribute('type', 'checkbox');
      this.optInCheckbox.setAttribute('style', 'margin: 0 10px 0 0;');
      this.optInCheckbox.classList.add('json-editor-opt-in');
      this.optInCheckbox.addEventListener('click', function () {
        if (_this3.isActive()) {
          _this3.deactivate();
        } else {
          _this3.activate();
        }
      });
      /* append active/deactive checkbox if show_opt_in is true */

      if (this.jsoneditor.options.show_opt_in || this.options.show_opt_in) {
        /* and control to type object editors if they are not required */
        if (this.parent && this.parent.schema.type === 'object' && !this.isRequired() && this.header) {
          this.header.appendChild(this.optInCheckbox);
          this.header.insertBefore(this.optInCheckbox, this.header.firstChild);
        }
      }
    }
  }, {
    key: "preBuild",
    value: function preBuild() {}
  }, {
    key: "build",
    value: function build() {}
  }, {
    key: "postBuild",
    value: function postBuild() {
      this.setupWatchListeners();
      this.addLinks();
      this.setValue(this.getDefault(), true);
      this.updateHeaderText();
      this.register();
      this.onWatchedFieldChange();
    }
  }, {
    key: "setupWatchListeners",
    value: function setupWatchListeners() {
      var _this4 = this;

      /* Watched fields */
      this.watched = {};
      if (this.schema.vars) this.schema.watch = this.schema.vars;
      this.watched_values = {};

      this.watch_listener = function () {
        if (_this4.refreshWatchedFieldValues()) {
          _this4.onWatchedFieldChange();
        }
      };

      if (Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["hasOwnProperty"])(this.schema, 'watch')) {
        var path;
        var pathParts;
        var first;
        var root;
        var adjustedPath;
        var myPath = this.container.getAttribute('data-schemapath');
        Object.keys(this.schema.watch).forEach(function (name) {
          path = _this4.schema.watch[name];

          if (Array.isArray(path)) {
            if (path.length < 2) return;
            pathParts = [path[0]].concat(path[1].split('.'));
          } else {
            pathParts = path.split('.');
            if (!_this4.theme.closest(_this4.container, "[data-schemaid=\"".concat(pathParts[0], "\"]"))) pathParts.unshift('#');
          }

          first = pathParts.shift();
          if (first === '#') first = _this4.jsoneditor.schema.id || 'root';
          /* Find the root node for this template variable */

          root = _this4.theme.closest(_this4.container, "[data-schemaid=\"".concat(first, "\"]"));
          if (!root) throw new Error("Could not find ancestor node with id ".concat(first));
          /* Keep track of the root node and path for use when rendering the template */

          adjustedPath = "".concat(root.getAttribute('data-schemapath'), ".").concat(pathParts.join('.'));
          if (myPath.startsWith(adjustedPath)) _this4.watchLoop = true;

          _this4.jsoneditor.watch(adjustedPath, _this4.watch_listener);

          _this4.watched[name] = adjustedPath;
        });
      }
      /* Dynamic header */


      if (this.schema.headerTemplate) {
        this.header_template = this.jsoneditor.compileTemplate(this.schema.headerTemplate, this.template_engine);
      }
    }
  }, {
    key: "addLinks",
    value: function addLinks() {
      /* Add links */
      if (!this.no_link_holder) {
        this.link_holder = this.theme.getLinksHolder();
        /* if description element exists, insert the link before */

        if (typeof this.description !== 'undefined') this.description.parentNode.insertBefore(this.link_holder, this.description);
        /* otherwise just insert link at bottom of container */
        else this.container.appendChild(this.link_holder);

        if (this.schema.links) {
          for (var i = 0; i < this.schema.links.length; i++) {
            this.addLink(this.getLink(this.schema.links[i]));
          }
        }
      }
    }
  }, {
    key: "onMove",
    value: function onMove() {}
  }, {
    key: "getButton",
    value: function getButton(text, icon, title) {
      var btnClass = "json-editor-btn-".concat(icon);
      if (!this.iconlib) icon = null;else icon = this.iconlib.getIcon(icon);

      if (!icon && title) {
        text = title;
        title = null;
      }

      var btn = this.theme.getButton(text, icon, title);
      btn.classList.add(btnClass);
      return btn;
    }
  }, {
    key: "setButtonText",
    value: function setButtonText(button, text, icon, title) {
      if (!this.iconlib) icon = null;else icon = this.iconlib.getIcon(icon);

      if (!icon && title) {
        text = title;
        title = null;
      }

      return this.theme.setButtonText(button, text, icon, title);
    }
  }, {
    key: "addLink",
    value: function addLink(link) {
      if (this.link_holder) this.link_holder.appendChild(link);
    }
  }, {
    key: "getLink",
    value: function getLink(data) {
      var holder;
      var link;
      /* Get mime type of the link */

      var mime = data.mediaType || 'application/javascript';
      var type = mime.split('/')[0];
      /* Template to generate the link href */

      var href = this.jsoneditor.compileTemplate(data.href, this.template_engine);
      var relTemplate = this.jsoneditor.compileTemplate(data.rel ? data.rel : data.href, this.template_engine);
      /* Template to generate the link's download attribute */

      var download = null;
      if (data.download) download = data.download;

      if (download && download !== true) {
        download = this.jsoneditor.compileTemplate(download, this.template_engine);
      }
      /* Image links */


      if (type === 'image') {
        holder = this.theme.getBlockLinkHolder();
        link = document.createElement('a');
        link.setAttribute('target', '_blank');
        var image = document.createElement('img');
        this.theme.createImageLink(holder, link, image);
        /* When a watched field changes, update the url */

        this.link_watchers.push(function (vars) {
          var url = href(vars);
          var rel = relTemplate(vars);
          link.setAttribute('href', url);
          link.setAttribute('title', rel || url);
          image.setAttribute('src', url);
        });
        /* Audio/Video links */
      } else if (['audio', 'video'].includes(type)) {
        holder = this.theme.getBlockLinkHolder();
        link = this.theme.getBlockLink();
        link.setAttribute('target', '_blank');
        var media = document.createElement(type);
        media.setAttribute('controls', 'controls');
        this.theme.createMediaLink(holder, link, media);
        /* When a watched field changes, update the url */

        this.link_watchers.push(function (vars) {
          var url = href(vars);
          var rel = relTemplate(vars);
          link.setAttribute('href', url);
          link.textContent = rel || url;
          media.setAttribute('src', url);
        });
        /* Text links or blank link */
      } else {
        link = holder = this.theme.getBlockLink();
        holder.setAttribute('target', '_blank');
        holder.textContent = data.rel;
        holder.style.display = 'none';
        /* Prevent blank links from showing up when using custom view */

        /* When a watched field changes, update the url */

        this.link_watchers.push(function (vars) {
          var url = href(vars);
          var rel = relTemplate(vars);
          if (url) holder.style.display = '';
          holder.setAttribute('href', url);
          holder.textContent = rel || url;
        });
      }

      if (download && link) {
        if (download === true) {
          link.setAttribute('download', '');
        } else {
          this.link_watchers.push(function (vars) {
            link.setAttribute('download', download(vars));
          });
        }
      }

      if (data["class"]) link.classList.add(data["class"]);
      return holder;
    }
  }, {
    key: "refreshWatchedFieldValues",
    value: function refreshWatchedFieldValues() {
      var _this5 = this;

      if (!this.watched_values) return;
      var watched = {};
      var changed = false;

      if (this.watched) {
        Object.keys(this.watched).forEach(function (name) {
          var editor = _this5.jsoneditor.getEditor(_this5.watched[name]);

          var val = editor ? editor.getValue() : null;
          if (_this5.watched_values[name] !== val) changed = true;
          watched[name] = val;
        });
      }

      watched.self = this.getValue();
      if (this.watched_values.self !== watched.self) changed = true;
      this.watched_values = watched;
      return changed;
    }
  }, {
    key: "getWatchedFieldValues",
    value: function getWatchedFieldValues() {
      return this.watched_values;
    }
  }, {
    key: "updateHeaderText",
    value: function updateHeaderText() {
      if (this.header) {
        var headerText = this.getHeaderText();
        /* If the header has children, only update the text node's value */

        if (this.header.children.length) {
          for (var i = 0; i < this.header.childNodes.length; i++) {
            if (this.header.childNodes[i].nodeType === 3) {
              this.header.childNodes[i].nodeValue = this.cleanText(headerText);
              break;
            }
          }
          /* Otherwise, just update the entire node */

        } else {
          if (window.DOMPurify) this.header.innerHTML = window.DOMPurify.sanitize(headerText);else this.header.textContent = this.cleanText(headerText);
        }
      }
    }
  }, {
    key: "getHeaderText",
    value: function getHeaderText(titleOnly) {
      if (this.header_text) return this.header_text;else if (titleOnly) return this.schema.title;else return this.getTitle();
    }
  }, {
    key: "cleanText",
    value: function cleanText(txt) {
      /* Clean out HTML tags from txt */
      var tmp = document.createElement('div');
      tmp.innerHTML = txt;
      return tmp.textContent || tmp.innerText;
    }
  }, {
    key: "onWatchedFieldChange",
    value: function onWatchedFieldChange() {
      var vars;

      if (this.header_template) {
        vars = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["extend"])(this.getWatchedFieldValues(), {
          key: this.key,
          i: this.key,
          i0: this.key * 1,
          i1: this.key * 1 + 1,
          title: this.getTitle()
        });
        var headerText = this.header_template(vars);

        if (headerText !== this.header_text) {
          this.header_text = headerText;
          this.updateHeaderText();
          this.notify();
          /* this.fireChangeHeaderEvent(); */
        }
      }

      if (this.link_watchers.length) {
        vars = this.getWatchedFieldValues();

        for (var i = 0; i < this.link_watchers.length; i++) {
          this.link_watchers[i](vars);
        }
      }
    }
  }, {
    key: "setValue",
    value: function setValue(value) {
      this.value = value;
    }
  }, {
    key: "getValue",
    value: function getValue() {
      if (!this.dependenciesFulfilled) {
        return undefined;
      }

      return this.value;
    }
  }, {
    key: "refreshValue",
    value: function refreshValue() {}
  }, {
    key: "getChildEditors",
    value: function getChildEditors() {
      return false;
    }
  }, {
    key: "destroy",
    value: function destroy() {
      var _this6 = this;

      this.unregister(this);

      if (this.watched) {
        Object.values(this.watched).forEach(function (adjustedPath) {
          return _this6.jsoneditor.unwatch(adjustedPath, _this6.watch_listener);
        });
      }

      this.watched = null;
      this.watched_values = null;
      this.watch_listener = null;
      this.header_text = null;
      this.header_template = null;
      this.value = null;
      if (this.container && this.container.parentNode) this.container.parentNode.removeChild(this.container);
      this.container = null;
      this.jsoneditor = null;
      this.schema = null;
      this.path = null;
      this.key = null;
      this.parent = null;
    }
  }, {
    key: "isDefaultRequired",
    value: function isDefaultRequired() {
      return this.isRequired() || !!this.jsoneditor.options.use_default_values;
    }
  }, {
    key: "getDefault",
    value: function getDefault() {
      if (typeof this.schema["default"] !== 'undefined') {
        return this.schema["default"];
      }

      if (typeof this.schema["enum"] !== 'undefined') {
        return this.schema["enum"][0];
      }

      var type = this.schema.type || this.schema.oneOf;
      if (type && Array.isArray(type)) type = type[0];
      if (type && _typeof(type) === 'object') type = type.type;
      if (type && Array.isArray(type)) type = type[0];

      if (typeof type === 'string') {
        if (type === 'number') return this.isDefaultRequired() ? 0.0 : undefined;
        if (type === 'boolean') return this.isDefaultRequired() ? false : undefined;
        if (type === 'integer') return this.isDefaultRequired() ? 0 : undefined;
        if (type === 'string') return '';
        if (type === 'object') return {};
        if (type === 'array') return [];
      }

      return null;
    }
  }, {
    key: "getTitle",
    value: function getTitle() {
      return this.schema.title || this.key;
    }
  }, {
    key: "enable",
    value: function enable() {
      this.disabled = false;
    }
  }, {
    key: "disable",
    value: function disable() {
      this.disabled = true;
    }
  }, {
    key: "isEnabled",
    value: function isEnabled() {
      return !this.disabled;
    }
  }, {
    key: "isRequired",
    value: function isRequired() {
      if (typeof this.schema.required === 'boolean') return this.schema.required;else if (this.parent && this.parent.schema && Array.isArray(this.parent.schema.required)) return this.parent.schema.required.includes(this.key);else if (this.jsoneditor.options.required_by_default) return true;else return false;
    }
  }, {
    key: "getDisplayText",
    value: function getDisplayText(arr) {
      var disp = [];
      var used = {};
      /* Determine how many times each attribute name is used. */

      /* This helps us pick the most distinct display text for the schemas. */

      arr.forEach(function (el) {
        if (el.title) {
          used[el.title] = used[el.title] || 0;
          used[el.title]++;
        }

        if (el.description) {
          used[el.description] = used[el.description] || 0;
          used[el.description]++;
        }

        if (el.format) {
          used[el.format] = used[el.format] || 0;
          used[el.format]++;
        }

        if (el.type) {
          used[el.type] = used[el.type] || 0;
          used[el.type]++;
        }
      });
      /* Determine display text for each element of the array */

      arr.forEach(function (el) {
        var name;
        /* If it's a simple string */

        if (typeof el === 'string') name = el;
        /* Object */
        else if (el.title && used[el.title] <= 1) name = el.title;else if (el.format && used[el.format] <= 1) name = el.format;else if (el.type && used[el.type] <= 1) name = el.type;else if (el.description && used[el.description] <= 1) name = el.descripton;else if (el.title) name = el.title;else if (el.format) name = el.format;else if (el.type) name = el.type;else if (el.description) name = el.description;else if (JSON.stringify(el).length < 500) name = JSON.stringify(el);else name = 'type';
        disp.push(name);
      });
      /* Replace identical display text with "text 1", "text 2", etc. */

      var inc = {};
      disp.forEach(function (name, i) {
        inc[name] = inc[name] || 0;
        inc[name]++;
        if (used[name] > 1) disp[i] = "".concat(name, " ").concat(inc[name]);
      });
      return disp;
    }
    /* Replace space(s) with "-" to create valid id value */

  }, {
    key: "getValidId",
    value: function getValidId(id) {
      id = id === undefined ? '' : id.toString();
      return id.replace(/\s+/g, '-');
    }
  }, {
    key: "setInputAttributes",
    value: function setInputAttributes(inputAttribute) {
      var _this7 = this;

      if (this.schema.options && this.schema.options.inputAttributes) {
        var inputAttributes = this.schema.options.inputAttributes;
        var protectedAttributes = ['name', 'type'].concat(inputAttribute);
        Object.keys(inputAttributes).forEach(function (key) {
          if (!protectedAttributes.includes(key.toLowerCase())) {
            _this7.input.setAttribute(key, inputAttributes[key]);
          }
        });
      }
    }
  }, {
    key: "expandCallbacks",
    value: function expandCallbacks(scope, options) {
      var _this8 = this;

      var callback = this.defaults.callbacks[scope];
      Object.entries(options).forEach(function (_ref) {
        var _ref2 = _slicedToArray(_ref, 2),
            key = _ref2[0],
            value = _ref2[1];

        if (value === Object(value)) {
          options[key] = _this8.expandCallbacks(scope, value);
        } else if (typeof value === 'string' && _typeof(callback) === 'object' && typeof callback[value] === 'function') {
          options[key] = callback[value].bind(null, _this8);
        }
      });
      return options;
    }
  }, {
    key: "showValidationErrors",
    value: function showValidationErrors(errors) {}
  }]);

  return AbstractEditor;
}();

/***/ }),

/***/ "./src/editors/ace.js":
/*!****************************!*\
  !*** ./src/editors/ace.js ***!
  \****************************/
/*! exports provided: AceEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "AceEditor", function() { return AceEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var AceEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(AceEditor, _StringEditor);

  var _super = _createSuper(AceEditor);

  function AceEditor() {
    _classCallCheck(this, AceEditor);

    return _super.apply(this, arguments);
  }

  _createClass(AceEditor, [{
    key: "setValue",
    value: function setValue(value, initial, fromTemplate) {
      var res = _get(_getPrototypeOf(AceEditor.prototype), "setValue", this).call(this, value, initial, fromTemplate);

      if (res !== undefined && res.changed && this.ace_editor_instance) {
        this.ace_editor_instance.setValue(res.value);
        this.ace_editor_instance.session.getSelection().clearSelection();
        this.ace_editor_instance.resize();
      }
    }
  }, {
    key: "build",
    value: function build() {
      this.options.format = 'textarea';
      /* Force format into "textarea" */

      _get(_getPrototypeOf(AceEditor.prototype), "build", this).call(this);

      this.input_type = this.schema.format;
      /* Restore original format */

      this.input.setAttribute('data-schemaformat', this.input_type);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      var options;

      if (window.ace) {
        var mode = this.input_type;
        /* aliases for c/cpp */

        if (mode === 'cpp' || mode === 'c++' || mode === 'c') mode = 'c_cpp';
        /* Get options, either global options from "this.defaults.options.ace" or */

        /* single property options from schema "options.ace" */

        options = this.expandCallbacks('ace', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
          selectionStyle: 'text',
          minLines: 30,
          maxLines: 30
        }, this.defaults.options.ace || {}, this.options.ace || {}, {
          mode: "ace/mode/".concat(mode)
        }));
        this.ace_container = document.createElement('div');
        this.ace_container.style.width = '100%';
        this.ace_container.style.position = 'relative';
        /* this.ace_container.style.height = '400px'; */

        this.input.parentNode.insertBefore(this.ace_container, this.input);
        this.input.style.display = 'none';
        this.ace_editor_instance = window.ace.edit(this.ace_container, options);
        this.ace_editor_instance.setValue(this.getValue());
        this.ace_editor_instance.session.getSelection().clearSelection();
        this.ace_editor_instance.resize();

        if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
          this.ace_editor_instance.setReadOnly(true);
        }
        /* Listen for changes */


        this.ace_editor_instance.on('change', function () {
          _this.input.value = _this.ace_editor_instance.getValue();

          _this.refreshValue();

          _this.is_dirty = true;

          _this.onChange(true);
        });
        this.theme.afterInputReady(this.input);
      } else _get(_getPrototypeOf(AceEditor.prototype), "afterInputReady", this).call(this);
      /* Library not loaded, so just treat this as a string */

    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 6;
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled && this.ace_editor_instance) this.ace_editor_instance.setReadOnly(false);

      _get(_getPrototypeOf(AceEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.ace_editor_instance) this.ace_editor_instance.setReadOnly(true);

      _get(_getPrototypeOf(AceEditor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.ace_editor_instance) {
        this.ace_editor_instance.destroy();
        this.ace_editor_instance = null;
      }

      _get(_getPrototypeOf(AceEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return AceEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/array.js":
/*!******************************!*\
  !*** ./src/editors/array.js ***!
  \******************************/
/*! exports provided: ArrayEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ArrayEditor", function() { return ArrayEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var ArrayEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(ArrayEditor, _AbstractEditor);

  var _super = _createSuper(ArrayEditor);

  function ArrayEditor() {
    _classCallCheck(this, ArrayEditor);

    return _super.apply(this, arguments);
  }

  _createClass(ArrayEditor, [{
    key: "askConfirmation",
    value: function askConfirmation() {
      if (this.jsoneditor.options.prompt_before_delete === true) {
        if (window.confirm(this.translate('button_delete_node_warning')) === false) {
          return false;
        }
      }

      return true;
    }
  }, {
    key: "getDefault",
    value: function getDefault() {
      return this.schema["default"] || [];
    }
  }, {
    key: "register",
    value: function register() {
      _get(_getPrototypeOf(ArrayEditor.prototype), "register", this).call(this);

      if (this.rows) {
        for (var i = 0; i < this.rows.length; i++) {
          this.rows[i].register();
        }
      }
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(ArrayEditor.prototype), "unregister", this).call(this);

      if (this.rows) {
        for (var i = 0; i < this.rows.length; i++) {
          this.rows[i].unregister();
        }
      }
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      var info = this.getItemInfo(0);
      /* Tabs require extra horizontal space */

      if (this.tabs_holder && this.schema.format !== 'tabs-top') {
        return Math.max(Math.min(12, info.width + 2), 4);
      }

      return info.width;
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        if (this.add_row_button) this.add_row_button.disabled = false;
        if (this.remove_all_rows_button) this.remove_all_rows_button.disabled = false;
        if (this.delete_last_row_button) this.delete_last_row_button.disabled = false;
        if (this.copy_button) this.copy_button.disabled = false;
        /* if(this.toggle_button) this.toggle_button.disabled = false; */

        if (this.delete_button) this.delete_button.disabled = false;
        if (this.moveup_button) this.moveup_button.disabled = false;
        if (this.movedown_button) this.movedown_button.disabled = false;

        if (this.rows) {
          for (var i = 0; i < this.rows.length; i++) {
            this.rows[i].enable();
            if (this.rows[i].add_row_button) this.rows[i].add_row_button.disabled = false;
            if (this.rows[i].remove_all_rows_button) this.rows[i].remove_all_rows_button.disabled = false;
            if (this.rows[i].delete_last_row_button) this.rows[i].delete_last_row_button.disabled = false;
            if (this.rows[i].copy_button) this.rows[i].copy_button.disabled = false;
            /* if(this.rows[i].toggle_button) this.rows[i].toggle_button.disabled = false; */

            if (this.rows[i].delete_button) this.rows[i].delete_button.disabled = false;
            if (this.rows[i].moveup_button) this.rows[i].moveup_button.disabled = false;
            if (this.rows[i].movedown_button) this.rows[i].movedown_button.disabled = false;
          }
        }

        _get(_getPrototypeOf(ArrayEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;
      if (this.add_row_button) this.add_row_button.disabled = true;
      if (this.remove_all_rows_button) this.remove_all_rows_button.disabled = true;
      if (this.delete_last_row_button) this.delete_last_row_button.disabled = true;
      if (this.copy_button) this.copy_button.disabled = true;
      /* if(this.toggle_button) this.toggle_button.disabled = true; */

      if (this.delete_button) this.delete_button.disabled = true;
      if (this.moveup_button) this.moveup_button.disabled = true;
      if (this.movedown_button) this.movedown_button.disabled = true;

      if (this.rows) {
        for (var i = 0; i < this.rows.length; i++) {
          this.rows[i].disable(alwaysDisabled);
          if (this.rows[i].add_row_button) this.rows[i].add_row_button.disabled = true;
          if (this.rows[i].remove_all_rows_button) this.rows[i].remove_all_rows_button.disabled = true;
          if (this.rows[i].delete_last_row_button) this.rows[i].delete_last_row_button.disabled = true;
          if (this.rows[i].copy_button) this.rows[i].copy_button.disabled = true;
          /* if(this.rows[i].toggle_button) this.rows[i].toggle_button.disabled = true; */

          if (this.rows[i].delete_button) this.rows[i].delete_button.disabled = true;
          if (this.rows[i].moveup_button) this.rows[i].moveup_button.disabled = true;
          if (this.rows[i].movedown_button) this.rows[i].movedown_button.disabled = true;
        }
      }

      _get(_getPrototypeOf(ArrayEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "preBuild",
    value: function preBuild() {
      _get(_getPrototypeOf(ArrayEditor.prototype), "preBuild", this).call(this);

      this.rows = [];
      this.row_cache = [];
      this.hide_delete_buttons = this.options.disable_array_delete || this.jsoneditor.options.disable_array_delete;
      this.hide_delete_all_rows_buttons = this.hide_delete_buttons || this.options.disable_array_delete_all_rows || this.jsoneditor.options.disable_array_delete_all_rows;
      this.hide_delete_last_row_buttons = this.hide_delete_buttons || this.options.disable_array_delete_last_row || this.jsoneditor.options.disable_array_delete_last_row;
      this.hide_move_buttons = this.options.disable_array_reorder || this.jsoneditor.options.disable_array_reorder;
      this.hide_add_button = this.options.disable_array_add || this.jsoneditor.options.disable_array_add;
      this.show_copy_button = this.options.enable_array_copy || this.jsoneditor.options.enable_array_copy;
      this.array_controls_top = this.options.array_controls_top || this.jsoneditor.options.array_controls_top;
    }
  }, {
    key: "build",
    value: function build() {
      if (!this.options.compact) {
        this.header = document.createElement('label');
        this.header.textContent = this.getTitle();
        this.title = this.theme.getHeader(this.header);
        this.container.appendChild(this.title);
        this.title_controls = this.theme.getHeaderButtonHolder();
        this.title.appendChild(this.title_controls);

        if (this.schema.description) {
          this.description = this.theme.getDescription(this.schema.description);
          this.container.appendChild(this.description);
        }

        this.error_holder = document.createElement('div');
        this.container.appendChild(this.error_holder);

        if (this.schema.format === 'tabs-top') {
          this.controls = this.theme.getHeaderButtonHolder();
          this.title.appendChild(this.controls);
          this.tabs_holder = this.theme.getTopTabHolder(this.getValidId(this.getItemTitle()));
          this.container.appendChild(this.tabs_holder);
          this.row_holder = this.theme.getTopTabContentHolder(this.tabs_holder);
          this.active_tab = null;
        } else if (this.schema.format === 'tabs') {
          this.controls = this.theme.getHeaderButtonHolder();
          this.title.appendChild(this.controls);
          this.tabs_holder = this.theme.getTabHolder(this.getValidId(this.getItemTitle()));
          this.container.appendChild(this.tabs_holder);
          this.row_holder = this.theme.getTabContentHolder(this.tabs_holder);
          this.active_tab = null;
        } else {
          this.panel = this.theme.getIndentedPanel();
          this.container.appendChild(this.panel);
          this.row_holder = document.createElement('div');
          this.panel.appendChild(this.row_holder);
          this.controls = this.theme.getButtonHolder();

          if (this.array_controls_top) {
            this.title.appendChild(this.controls);
          } else {
            this.panel.appendChild(this.controls);
          }
        }
      } else {
        /* compact mode */
        this.title = this.theme.getHeader('');
        this.container.appendChild(this.title);
        this.panel = this.theme.getIndentedPanel();
        this.container.appendChild(this.panel);
        this.title_controls = this.theme.getHeaderButtonHolder();
        this.title.appendChild(this.title_controls);
        this.controls = this.theme.getHeaderButtonHolder();
        this.title.appendChild(this.controls);
        this.row_holder = document.createElement('div');
        this.panel.appendChild(this.row_holder);
      }
      /* Add controls */


      this.addControls();
    }
  }, {
    key: "onChildEditorChange",
    value: function onChildEditorChange(editor) {
      this.refreshValue();
      this.refreshTabs(true);

      _get(_getPrototypeOf(ArrayEditor.prototype), "onChildEditorChange", this).call(this, editor);
    }
  }, {
    key: "getItemTitle",
    value: function getItemTitle() {
      if (!this.item_title) {
        if (this.schema.items && !Array.isArray(this.schema.items)) {
          var tmp = this.jsoneditor.expandRefs(this.schema.items);
          this.item_title = tmp.title || this.translate('default_array_item_title');
        } else {
          this.item_title = this.translate('default_array_item_title');
        }
      }

      return this.cleanText(this.item_title);
    }
  }, {
    key: "getItemSchema",
    value: function getItemSchema(i) {
      if (Array.isArray(this.schema.items)) {
        if (i >= this.schema.items.length) {
          if (this.schema.additionalItems === true) {
            return {};
          } else if (this.schema.additionalItems) {
            return Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.schema.additionalItems);
          }
        } else {
          return Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.schema.items[i]);
        }
      } else if (this.schema.items) {
        return Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.schema.items);
      } else {
        return {};
      }
    }
  }, {
    key: "getItemInfo",
    value: function getItemInfo(i) {
      var schema = this.getItemSchema(i);
      /* Check if it's cached */

      this.item_info = this.item_info || {};
      var stringified = JSON.stringify(schema);
      if (typeof this.item_info[stringified] !== 'undefined') return this.item_info[stringified];
      /* Get the schema for this item */

      schema = this.jsoneditor.expandRefs(schema);
      this.item_info[stringified] = {
        title: schema.title || this.translate('default_array_item_title'),
        "default": schema["default"],
        width: 12,
        child_editors: schema.properties || schema.items
      };
      return this.item_info[stringified];
    }
  }, {
    key: "getElementEditor",
    value: function getElementEditor(i) {
      var itemInfo = this.getItemInfo(i);
      var schema = this.getItemSchema(i);
      schema = this.jsoneditor.expandRefs(schema);
      schema.title = "".concat(itemInfo.title, " ").concat(i + 1);
      var editor = this.jsoneditor.getEditorClass(schema);
      var holder;

      if (this.tabs_holder) {
        if (this.schema.format === 'tabs-top') {
          holder = this.theme.getTopTabContent();
        } else {
          holder = this.theme.getTabContent();
        }

        holder.id = "".concat(this.path, ".").concat(i);
      } else if (itemInfo.child_editors) {
        holder = this.theme.getChildEditorHolder();
      } else {
        holder = this.theme.getIndentedPanel();
      }

      this.row_holder.appendChild(holder);
      var ret = this.jsoneditor.createEditor(editor, {
        jsoneditor: this.jsoneditor,
        schema: schema,
        container: holder,
        path: "".concat(this.path, ".").concat(i),
        parent: this,
        required: true
      });
      ret.preBuild();
      ret.build();
      ret.postBuild();

      if (!ret.title_controls) {
        ret.array_controls = this.theme.getButtonHolder();
        holder.appendChild(ret.array_controls);
      }

      return ret;
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this.empty(true);
      if (this.title && this.title.parentNode) this.title.parentNode.removeChild(this.title);
      if (this.description && this.description.parentNode) this.description.parentNode.removeChild(this.description);
      if (this.row_holder && this.row_holder.parentNode) this.row_holder.parentNode.removeChild(this.row_holder);
      if (this.controls && this.controls.parentNode) this.controls.parentNode.removeChild(this.controls);
      if (this.panel && this.panel.parentNode) this.panel.parentNode.removeChild(this.panel);
      this.rows = this.row_cache = this.title = this.description = this.row_holder = this.panel = this.controls = null;

      _get(_getPrototypeOf(ArrayEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "empty",
    value: function empty(hard) {
      var _this = this;

      if (!this.rows) return;
      this.rows.forEach(function (row, i) {
        if (hard) {
          if (row.tab && row.tab.parentNode) row.tab.parentNode.removeChild(row.tab);

          _this.destroyRow(row, true);

          _this.row_cache[i] = null;
        }

        _this.rows[i] = null;
      });
      this.rows = [];
      if (hard) this.row_cache = [];
    }
  }, {
    key: "destroyRow",
    value: function destroyRow(row, hard) {
      var holder = row.container;

      if (hard) {
        row.destroy();
        if (holder.parentNode) holder.parentNode.removeChild(holder);
        if (row.tab && row.tab.parentNode) row.tab.parentNode.removeChild(row.tab);
      } else {
        if (row.tab) row.tab.style.display = 'none';
        holder.style.display = 'none';
        row.unregister();
      }
    }
  }, {
    key: "getMax",
    value: function getMax() {
      if (Array.isArray(this.schema.items) && this.schema.additionalItems === false) {
        return Math.min(this.schema.items.length, this.schema.maxItems || Infinity);
      }

      return this.schema.maxItems || Infinity;
    }
  }, {
    key: "refreshTabs",
    value: function refreshTabs(refreshHeaders) {
      var _this2 = this;

      this.rows.forEach(function (row) {
        if (!row.tab) return;

        if (refreshHeaders) {
          row.tab_text.textContent = row.getHeaderText();
        } else if (row.tab === _this2.active_tab) {
          _this2.theme.markTabActive(row);
        } else {
          _this2.theme.markTabInactive(row);
        }
      });
    }
  }, {
    key: "setValue",
    value: function setValue() {
      var _this3 = this;

      var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
      var initial = arguments.length > 1 ? arguments[1] : undefined;
      if (!Array.isArray(value)) value = [value];
      var serialized = JSON.stringify(value);
      if (serialized === this.serialized) return;
      /* Make sure value has between minItems and maxItems items in it */

      if (this.schema.minItems) {
        while (value.length < this.schema.minItems) {
          value.push(this.getItemInfo(value.length)["default"]);
        }
      }

      if (this.getMax() && value.length > this.getMax()) {
        value = value.slice(0, this.getMax());
      }

      value.forEach(function (val, i) {
        if (_this3.rows[i]) {
          /* TODO: don't set the row's value if it hasn't changed */
          _this3.rows[i].setValue(val, initial);
        } else if (_this3.row_cache[i]) {
          _this3.rows[i] = _this3.row_cache[i];

          _this3.rows[i].setValue(val, initial);

          _this3.rows[i].container.style.display = '';
          if (_this3.rows[i].tab) _this3.rows[i].tab.style.display = '';

          _this3.rows[i].register();

          _this3.jsoneditor.trigger('addRow', _this3.rows[i]);
        } else {
          var editor = _this3.addRow(val, initial);

          _this3.jsoneditor.trigger('addRow', editor);
        }
      });

      for (var j = value.length; j < this.rows.length; j++) {
        this.destroyRow(this.rows[j]);
        this.rows[j] = null;
      }

      this.rows = this.rows.slice(0, value.length);
      /* Set the active tab */

      var row = this.rows.find(function (row) {
        return row.tab === _this3.active_tab;
      });
      var newActiveTab = typeof row !== 'undefined' ? row.tab : null;
      if (!newActiveTab && this.rows.length) newActiveTab = this.rows[0].tab;
      this.active_tab = newActiveTab;
      this.refreshValue(initial);
      this.refreshTabs(true);
      this.refreshTabs();
      this.onChange();
      /* TODO: sortable */
    }
  }, {
    key: "refreshValue",
    value: function refreshValue(force) {
      var _this4 = this;

      var oldi = this.value ? this.value.length : 0;
      /* Get the value for this editor */

      this.value = this.rows.map(function (editor) {
        return editor.getValue();
      });

      if (oldi !== this.value.length || force) {
        /* If we currently have minItems items in the array */
        var minItems = this.schema.minItems && this.schema.minItems >= this.rows.length;
        this.rows.forEach(function (editor, i) {
          /* Hide the move down button for the last row */
          if (editor.movedown_button) {
            if (i === _this4.rows.length - 1) {
              editor.movedown_button.style.display = 'none';
            } else {
              editor.movedown_button.style.display = '';
            }
          }
          /* Hide the delete button if we have minItems items */


          if (editor.delete_button) {
            if (minItems) {
              editor.delete_button.style.display = 'none';
            } else {
              editor.delete_button.style.display = '';
            }
          }
          /* Get the value for this editor */


          _this4.value[i] = editor.getValue();
        });
        var controlsNeeded = false;

        if (!this.value.length) {
          this.delete_last_row_button.style.display = 'none';
          this.remove_all_rows_button.style.display = 'none';
        } else if (this.value.length === 1) {
          this.remove_all_rows_button.style.display = 'none';
          /* If there are minItems items in the array, or configured to hide the delete_last_row button, hide the delete button beneath the rows */

          if (minItems || this.hide_delete_last_row_buttons) {
            this.delete_last_row_button.style.display = 'none';
          } else {
            this.delete_last_row_button.style.display = '';
            controlsNeeded = true;
          }
        } else {
          if (minItems || this.hide_delete_last_row_buttons) {
            this.delete_last_row_button.style.display = 'none';
          } else {
            this.delete_last_row_button.style.display = '';
            controlsNeeded = true;
          }

          if (minItems || this.hide_delete_all_rows_buttons) {
            this.remove_all_rows_button.style.display = 'none';
          } else {
            this.remove_all_rows_button.style.display = '';
            controlsNeeded = true;
          }
        }
        /* If there are maxItems in the array, hide the add button beneath the rows */


        if (this.getMax() && this.getMax() <= this.rows.length || this.hide_add_button) {
          this.add_row_button.style.display = 'none';
        } else {
          this.add_row_button.style.display = '';
          controlsNeeded = true;
        }

        if (!this.collapsed && controlsNeeded) {
          this.controls.style.display = 'inline-block';
        } else {
          this.controls.style.display = 'none';
        }
      }
    }
  }, {
    key: "addRow",
    value: function addRow(value, initial) {
      var _this5 = this;

      var i = this.rows.length;
      this.rows[i] = this.getElementEditor(i);
      this.row_cache[i] = this.rows[i];

      if (this.tabs_holder) {
        this.rows[i].tab_text = document.createElement('span');
        this.rows[i].tab_text.textContent = this.rows[i].getHeaderText();

        if (this.schema.format === 'tabs-top') {
          this.rows[i].tab = this.theme.getTopTab(this.rows[i].tab_text, this.getValidId(this.rows[i].path));
          this.theme.addTopTab(this.tabs_holder, this.rows[i].tab);
        } else {
          this.rows[i].tab = this.theme.getTab(this.rows[i].tab_text, this.getValidId(this.rows[i].path));
          this.theme.addTab(this.tabs_holder, this.rows[i].tab);
        }

        this.rows[i].tab.addEventListener('click', function (e) {
          _this5.active_tab = _this5.rows[i].tab;

          _this5.refreshTabs();

          e.preventDefault();
          e.stopPropagation();
        });
      }

      var controlsHolder = this.rows[i].title_controls || this.rows[i].array_controls;
      /* Buttons to delete row, move row up, and move row down */

      if (!this.hide_delete_buttons) {
        this.rows[i].delete_button = this.getButton(this.getItemTitle(), 'delete', this.translate('button_delete_row_title', [this.getItemTitle()]));
        this.rows[i].delete_button.classList.add('delete', 'json-editor-btntype-delete');
        this.rows[i].delete_button.setAttribute('data-i', i);
        this.rows[i].delete_button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          if (!_this5.askConfirmation()) {
            return false;
          }

          var i = e.currentTarget.getAttribute('data-i') * 1;

          var newval = _this5.getValue().filter(function (row, j) {
            return j !== i;
          });

          var newActiveTab = null;
          var editor = _this5.rows[i];

          _this5.setValue(newval);

          if (_this5.rows[i]) {
            newActiveTab = _this5.rows[i].tab;
          } else if (_this5.rows[i - 1]) {
            newActiveTab = _this5.rows[i - 1].tab;
          }

          if (newActiveTab) {
            _this5.active_tab = newActiveTab;

            _this5.refreshTabs();
          }

          _this5.onChange(true);

          _this5.jsoneditor.trigger('deleteRow', editor);
        });

        if (controlsHolder) {
          controlsHolder.appendChild(this.rows[i].delete_button);
        }
      }
      /* Button to copy an array element and add it as last element */


      if (this.show_copy_button) {
        this.rows[i].copy_button = this.getButton(this.getItemTitle(), 'copy', "Copy ".concat(this.getItemTitle()));
        this.rows[i].copy_button.classList.add('copy', 'json-editor-btntype-copy');
        this.rows[i].copy_button.setAttribute('data-i', i);
        this.rows[i].copy_button.addEventListener('click', function (e) {
          var value = _this5.getValue();

          e.preventDefault();
          e.stopPropagation();
          var i = e.currentTarget.getAttribute('data-i') * 1;
          value.forEach(function (row, j) {
            if (j === i) {
              value.push(row);
            }
          });

          _this5.setValue(value);

          _this5.refreshValue(true);

          _this5.onChange(true);
        });
        controlsHolder.appendChild(this.rows[i].copy_button);
      }

      if (i && !this.hide_move_buttons) {
        this.rows[i].moveup_button = this.getButton('', this.schema.format === 'tabs-top' ? 'moveleft' : 'moveup', this.translate('button_move_up_title'));
        this.rows[i].moveup_button.classList.add('moveup', 'json-editor-btntype-move');
        this.rows[i].moveup_button.setAttribute('data-i', i);
        this.rows[i].moveup_button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var i = e.currentTarget.getAttribute('data-i') * 1;
          if (i <= 0) return;

          var rows = _this5.getValue();

          var tmp = rows[i - 1];
          rows[i - 1] = rows[i];
          rows[i] = tmp;

          _this5.setValue(rows);

          _this5.active_tab = _this5.rows[i - 1].tab;

          _this5.refreshTabs();

          _this5.onChange(true);

          _this5.jsoneditor.trigger('moveRow', _this5.rows[i - 1]);
        });

        if (controlsHolder) {
          controlsHolder.appendChild(this.rows[i].moveup_button);
        }
      }

      if (!this.hide_move_buttons) {
        this.rows[i].movedown_button = this.getButton('', this.schema.format === 'tabs-top' ? 'moveright' : 'movedown', this.translate('button_move_down_title'));
        this.rows[i].movedown_button.classList.add('movedown', 'json-editor-btntype-move');
        this.rows[i].movedown_button.setAttribute('data-i', i);
        this.rows[i].movedown_button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var i = e.currentTarget.getAttribute('data-i') * 1;

          var rows = _this5.getValue();

          if (i >= rows.length - 1) return;
          var tmp = rows[i + 1];
          rows[i + 1] = rows[i];
          rows[i] = tmp;

          _this5.setValue(rows);

          _this5.active_tab = _this5.rows[i + 1].tab;

          _this5.refreshTabs();

          _this5.onChange(true);

          _this5.jsoneditor.trigger('moveRow', _this5.rows[i + 1]);
        });

        if (controlsHolder) {
          controlsHolder.appendChild(this.rows[i].movedown_button);
        }
      }

      if (value) this.rows[i].setValue(value, initial);
      this.refreshTabs();
      return this.rows[i];
    }
  }, {
    key: "addControls",
    value: function addControls() {
      var _this6 = this;

      this.collapsed = false;
      this.toggle_button = this.getButton('', 'collapse', this.translate('button_collapse'));
      this.toggle_button.classList.add('json-editor-btntype-toggle');
      this.toggle_button.style.margin = '0 10px 0 0';
      this.title.insertBefore(this.toggle_button, this.title.childNodes[0]);
      var rowHolderDisplay = this.row_holder.style.display;
      var controlsDisplay = this.controls.style.display;
      this.toggle_button.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (_this6.collapsed) {
          _this6.collapsed = false;
          if (_this6.panel) _this6.panel.style.display = '';
          _this6.row_holder.style.display = rowHolderDisplay;
          if (_this6.tabs_holder) _this6.tabs_holder.style.display = '';
          _this6.controls.style.display = controlsDisplay;

          _this6.setButtonText(e.currentTarget, '', 'collapse', _this6.translate('button_collapse'));
        } else {
          _this6.collapsed = true;
          _this6.row_holder.style.display = 'none';
          if (_this6.tabs_holder) _this6.tabs_holder.style.display = 'none';
          _this6.controls.style.display = 'none';
          if (_this6.panel) _this6.panel.style.display = 'none';

          _this6.setButtonText(e.currentTarget, '', 'expand', _this6.translate('button_expand'));
        }
      });
      /* If it should start collapsed */

      if (this.options.collapsed) {
        Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["trigger"])(this.toggle_button, 'click');
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
        var i = _this6.rows.length;
        var editor;

        if (_this6.row_cache[i]) {
          editor = _this6.rows[i] = _this6.row_cache[i];

          _this6.rows[i].setValue(_this6.rows[i].getDefault(), true);

          _this6.rows[i].container.style.display = '';
          if (_this6.rows[i].tab) _this6.rows[i].tab.style.display = '';

          _this6.rows[i].register();
        } else {
          editor = _this6.addRow();
        }

        _this6.active_tab = _this6.rows[i].tab;

        _this6.refreshTabs();

        _this6.refreshValue();

        _this6.onChange(true);

        _this6.jsoneditor.trigger('addRow', editor);
      });
      this.controls.appendChild(this.add_row_button);
      this.delete_last_row_button = this.getButton(this.translate('button_delete_last', [this.getItemTitle()]), 'subtract', this.translate('button_delete_last_title', [this.getItemTitle()]));
      this.delete_last_row_button.classList.add('json-editor-btntype-deletelast');
      this.delete_last_row_button.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!_this6.askConfirmation()) {
          return false;
        }

        var rows = _this6.getValue();

        var newActiveTab = null;
        var editor = rows.pop();

        _this6.setValue(rows);

        if (_this6.rows[_this6.rows.length - 1]) {
          newActiveTab = _this6.rows[_this6.rows.length - 1].tab;
        }

        if (newActiveTab) {
          _this6.active_tab = newActiveTab;

          _this6.refreshTabs();
        }

        _this6.onChange(true);

        _this6.jsoneditor.trigger('deleteRow', editor);
      });
      this.controls.appendChild(this.delete_last_row_button);
      this.remove_all_rows_button = this.getButton(this.translate('button_delete_all'), 'delete', this.translate('button_delete_all_title'));
      this.remove_all_rows_button.classList.add('json-editor-btntype-deleteall');
      this.remove_all_rows_button.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!_this6.askConfirmation()) {
          return false;
        }

        _this6.empty(true);

        _this6.setValue([]);

        _this6.onChange(true);

        _this6.jsoneditor.trigger('deleteAllRows');
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
  }, {
    key: "showValidationErrors",
    value: function showValidationErrors(errors) {
      var _this7 = this;

      /* Get all the errors that pertain to this editor */
      var myErrors = [];
      var otherErrors = [];
      errors.forEach(function (error) {
        if (error.path === _this7.path) {
          myErrors.push(error);
        } else {
          otherErrors.push(error);
        }
      });
      /* Show errors for this editor */

      if (this.error_holder) {
        if (myErrors.length) {
          this.error_holder.innerHTML = '';
          this.error_holder.style.display = '';
          myErrors.forEach(function (error) {
            _this7.error_holder.appendChild(_this7.theme.getErrorMessage(error.message));
          });
          /* Hide error area */
        } else {
          this.error_holder.style.display = 'none';
        }
      }
      /* Show errors for child editors */


      this.rows.forEach(function (row) {
        return row.showValidationErrors(otherErrors);
      });
    }
  }]);

  return ArrayEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/array/choices.js":
/*!**************************************!*\
  !*** ./src/editors/array/choices.js ***!
  \**************************************/
/*! exports provided: ArrayChoicesEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ArrayChoicesEditor", function() { return ArrayChoicesEditor; });
/* harmony import */ var _multiselect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../multiselect.js */ "./src/editors/multiselect.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var ArrayChoicesEditor = /*#__PURE__*/function (_MultiSelectEditor) {
  _inherits(ArrayChoicesEditor, _MultiSelectEditor);

  var _super = _createSuper(ArrayChoicesEditor);

  function ArrayChoicesEditor() {
    _classCallCheck(this, ArrayChoicesEditor);

    return _super.apply(this, arguments);
  }

  _createClass(ArrayChoicesEditor, [{
    key: "setValue",
    value: function setValue(value, initial) {
      if (this.choices_instance) {
        /* Make sure we are dealing with an array of strings so we can check for strict equality */
        value = [].concat(value).map(function (e) {
          return "".concat(e);
        });
        this.updateValue(value);
        /* Sets this.value to sanitized value */

        this.choices_instance.removeActiveItems();
        /* Remove existing selection */

        this.choices_instance.setChoiceByValue(this.value);
        /* Set new selection */

        this.onChange(true);
      } else _get(_getPrototypeOf(ArrayChoicesEditor.prototype), "setValue", this).call(this, value, initial);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      if (window.Choices && !this.choices_instance) {
        /* Get options, either global options from "this.defaults.options.choices" or */

        /* single property options from schema "options.choices" */
        var options = this.expandCallbacks('choices', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
          removeItems: true,
          removeItemButton: true
        }, this.defaults.options.choices || {}, this.options.choices || {}, {
          addItems: true,
          editItems: false,
          duplicateItemsAllowed: false
        }));
        /* New items are allowed if option "addItems" is true and items type is "string" */

        /* this.newEnumAllowed = options.addItems = !!options.addItems && this.schema.items && this.schema.items.type == 'string'; */

        /* Choices doesn't support adding new items to select type input */

        this.newEnumAllowed = false;
        this.choices_instance = new window.Choices(this.input, options);
        /* Remove change handler set in parent class (src/multiselect.js) */

        this.control.removeEventListener('change', this.multiselectChangeHandler);
        /* Create a new change handler */

        this.multiselectChangeHandler = function (e) {
          var value = _this.choices_instance.getValue(true);

          _this.updateValue(value);

          _this.onChange(true);
        };

        this.control.addEventListener('change', this.multiselectChangeHandler, false);
      }

      _get(_getPrototypeOf(ArrayChoicesEditor.prototype), "afterInputReady", this).call(this);
    }
  }, {
    key: "updateValue",
    value: function updateValue(value) {
      value = [].concat(value);
      var changed = false;
      var newValue = [];

      for (var i = 0; i < value.length; i++) {
        if (!this.select_values["".concat(value[i])]) {
          changed = true;

          if (this.newEnumAllowed) {
            if (!this.addNewOption(value[i])) continue;
          } else continue;
        }

        var sanitized = this.sanitize(this.select_values[value[i]]);
        newValue.push(sanitized);
        if (sanitized !== value[i]) changed = true;
      }

      this.value = newValue;
      return changed;
    }
  }, {
    key: "addNewOption",
    value: function addNewOption(value) {
      /* Add new value and label */
      this.option_keys.push("".concat(value));
      this.option_titles.push("".concat(value));
      this.select_values["".concat(value)] = value;
      /* Update Schema enum to prevent triggering "Value must be one of the enumerated values" */

      this.schema.items["enum"].push(value);
      /* Add new value and label to choices */

      this.choices_instance.setChoices([{
        value: "".concat(value),
        label: "".concat(value)
      }], 'value', 'label', false);
      return true;
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled && this.choices_instance) this.choices_instance.enable();

      _get(_getPrototypeOf(ArrayChoicesEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.choices_instance) this.choices_instance.disable();

      _get(_getPrototypeOf(ArrayChoicesEditor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.choices_instance) {
        this.choices_instance.destroy();
        this.choices_instance = null;
      }

      _get(_getPrototypeOf(ArrayChoicesEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return ArrayChoicesEditor;
}(_multiselect_js__WEBPACK_IMPORTED_MODULE_0__["MultiSelectEditor"]);

/***/ }),

/***/ "./src/editors/array/select2.js":
/*!**************************************!*\
  !*** ./src/editors/array/select2.js ***!
  \**************************************/
/*! exports provided: ArraySelect2Editor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ArraySelect2Editor", function() { return ArraySelect2Editor; });
/* harmony import */ var _multiselect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../multiselect.js */ "./src/editors/multiselect.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var ArraySelect2Editor = /*#__PURE__*/function (_MultiSelectEditor) {
  _inherits(ArraySelect2Editor, _MultiSelectEditor);

  var _super = _createSuper(ArraySelect2Editor);

  function ArraySelect2Editor() {
    _classCallCheck(this, ArraySelect2Editor);

    return _super.apply(this, arguments);
  }

  _createClass(ArraySelect2Editor, [{
    key: "setValue",
    value: function setValue(value, initial) {
      if (this.select2_instance) {
        /* Make sure we are dealing with an array of strings so we can check for strict equality */
        value = [].concat(value).map(function (e) {
          return "".concat(e);
        });
        this.updateValue(value);
        /* Sets this.value to sanitized value */

        if (this.select2v4) this.select2_instance.val(this.value).change();else this.select2_instance.select2('val', this.value);
        this.onChange(true);
      } else _get(_getPrototypeOf(ArraySelect2Editor.prototype), "setValue", this).call(this, value, initial);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      var options;

      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && !this.select2_instance) {
        /* Get options, either global options from "this.defaults.options.select2" or */

        /* single property options from schema "options.select2" */
        options = this.expandCallbacks('select2', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
          tags: true,
          width: '100%'
        }, this.defaults.options.select2 || {}, this.options.select2 || {}));
        /* New items are allowed if option "tags" is true and items type is "string" */

        this.newEnumAllowed = options.tags = !!options.tags && this.schema.items && this.schema.items.type === 'string';
        this.select2_instance = window.jQuery(this.input).select2(options);
        this.select2v4 = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["hasOwnProperty"])(this.select2_instance.select2, 'amd');

        this.selectChangeHandler = function () {
          var value = _this.select2v4 ? _this.select2_instance.val() : _this.select2_instance.select2('val');

          _this.updateValue(value);

          _this.onChange(true);
        };
        /* Add event handler. */

        /* Note: Must use the "on()" method and not addEventListener() */


        this.select2_instance.on('select2-blur', this.selectChangeHandler);
        this.select2_instance.on('change', this.selectChangeHandler);
      }

      _get(_getPrototypeOf(ArraySelect2Editor.prototype), "afterInputReady", this).call(this);
    }
  }, {
    key: "updateValue",
    value: function updateValue(value) {
      value = [].concat(value);
      var changed = false;
      var newValue = [];

      for (var i = 0; i < value.length; i++) {
        /*      if (!this.select_options[value[i]+'']) { */
        if (!this.select_values["".concat(value[i])]) {
          changed = true;

          if (this.newEnumAllowed) {
            if (!this.addNewOption(value[i])) continue;
          } else continue;
        }

        var sanitized = this.sanitize(this.select_values[value[i]]);
        newValue.push(sanitized);
        if (sanitized !== value[i]) changed = true;
      }

      this.value = newValue;
      return changed;
    }
  }, {
    key: "addNewOption",
    value: function addNewOption(value) {
      /* Add new value and label */
      this.option_keys.push("".concat(value));
      this.option_titles.push("".concat(value));
      this.select_values["".concat(value)] = value;
      /* Update Schema enum to prevent triggering "Value must be one of the enumerated values" */

      this.schema.items["enum"].push(value);
      var optionTag = this.input.querySelector("option[value=\"".concat(value, "\"]"));
      /* Remove data attribute to make option tag permanent. (user input) */

      if (optionTag) optionTag.removeAttribute('data-select2-tag');
      /* Create new option tag (setValue) */
      else this.input.appendChild(new Option(value, value, false, false)).trigger('change');
      return true;
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled && this.select2_instance) {
        if (this.select2v4) this.select2_instance.prop('disabled', false);else this.select2_instance.select2('enable', true);
      }

      _get(_getPrototypeOf(ArraySelect2Editor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.select2_instance) {
        if (this.select2v4) this.select2_instance.prop('disabled', true);else this.select2_instance.select2('enable', false);
      }

      _get(_getPrototypeOf(ArraySelect2Editor.prototype), "disable", this).call(this);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.select2_instance) {
        this.select2_instance.select2('destroy');
        this.select2_instance = null;
      }

      _get(_getPrototypeOf(ArraySelect2Editor.prototype), "destroy", this).call(this);
    }
  }]);

  return ArraySelect2Editor;
}(_multiselect_js__WEBPACK_IMPORTED_MODULE_0__["MultiSelectEditor"]);

/***/ }),

/***/ "./src/editors/array/selectize.js":
/*!****************************************!*\
  !*** ./src/editors/array/selectize.js ***!
  \****************************************/
/*! exports provided: ArraySelectizeEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ArraySelectizeEditor", function() { return ArraySelectizeEditor; });
/* harmony import */ var _multiselect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../multiselect.js */ "./src/editors/multiselect.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var ArraySelectizeEditor = /*#__PURE__*/function (_MultiSelectEditor) {
  _inherits(ArraySelectizeEditor, _MultiSelectEditor);

  var _super = _createSuper(ArraySelectizeEditor);

  function ArraySelectizeEditor() {
    _classCallCheck(this, ArraySelectizeEditor);

    return _super.apply(this, arguments);
  }

  _createClass(ArraySelectizeEditor, [{
    key: "setValue",
    value: function setValue(value, initial) {
      if (this.selectize_instance) {
        /* Make sure we are dealing with an array of strings so we can check for strict equality */
        value = [].concat(value).map(function (e) {
          return "".concat(e);
        });
        this.updateValue(value);
        /* Sets this.value to sanitized value */

        this.selectize_instance.setValue(this.value);
        this.onChange(true);
      } else _get(_getPrototypeOf(ArraySelectizeEditor.prototype), "setValue", this).call(this, value, initial);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      var options;

      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.selectize && !this.selectize_instance) {
        /* Get options, either global options from "this.defaults.options.selectize" or */

        /* single property options from schema "options.selectize" */
        options = this.expandCallbacks('selectize', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
          plugins: ['remove_button'],
          delimiter: false,
          createOnBlur: true,
          create: true
        }, this.defaults.options.selectize || {}, this.options.selectize || {}));
        /* New items are allowed if option "create" is true and items type is "string" */

        this.newEnumAllowed = options.create = !!options.create && this.schema.items && this.schema.items.type === 'string';
        this.selectize_instance = window.jQuery(this.input).selectize(options)[0].selectize;
        /* Remove change handler set in parent class (src/multiselect.js) */

        this.control.removeEventListener('change', this.multiselectChangeHandler);
        /* Create a new change handler */

        this.multiselectChangeHandler = function (e) {
          var value = _this.selectize_instance.getValue();

          _this.updateValue(value);

          _this.onChange(true);
        };
        /* Add new event handler. */

        /* Note: Must use the "on()" method and not addEventListener() */


        this.selectize_instance.on('change', this.multiselectChangeHandler);
      }

      _get(_getPrototypeOf(ArraySelectizeEditor.prototype), "afterInputReady", this).call(this);
    }
  }, {
    key: "updateValue",
    value: function updateValue(value) {
      value = [].concat(value);
      var changed = false;
      var newValue = [];

      for (var i = 0; i < value.length; i++) {
        /*      if (!this.select_options[value[i]+'']) { */
        if (!this.select_values["".concat(value[i])]) {
          changed = true;

          if (this.newEnumAllowed) {
            if (!this.addNewOption(value[i])) continue;
          } else continue;
        }

        var sanitized = this.sanitize(this.select_values[value[i]]);
        newValue.push(sanitized);
        if (sanitized !== value[i]) changed = true;
      }

      this.value = newValue;
      return changed;
    }
  }, {
    key: "addNewOption",
    value: function addNewOption(value) {
      /* Add new value and label */
      this.option_keys.push("".concat(value));
      this.option_titles.push("".concat(value));
      this.select_values["".concat(value)] = value;
      /* Update Schema enum to prevent triggering "Value must be one of the enumerated values" */

      this.schema.items["enum"].push(value);
      /* Add new value and label to selectize */

      this.selectize_instance.addOption({
        text: value,
        value: value
      });
      return true;
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled && this.selectize_instance) this.selectize_instance.unlock();

      _get(_getPrototypeOf(ArraySelectizeEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.selectize_instance) this.selectize_instance.lock();

      _get(_getPrototypeOf(ArraySelectizeEditor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.selectize_instance) {
        this.selectize_instance.destroy();
        this.selectize_instance = null;
      }

      _get(_getPrototypeOf(ArraySelectizeEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return ArraySelectizeEditor;
}(_multiselect_js__WEBPACK_IMPORTED_MODULE_0__["MultiSelectEditor"]);

/***/ }),

/***/ "./src/editors/autocomplete.js":
/*!*************************************!*\
  !*** ./src/editors/autocomplete.js ***!
  \*************************************/
/*! exports provided: AutocompleteEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "AutocompleteEditor", function() { return AutocompleteEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var AutocompleteEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(AutocompleteEditor, _StringEditor);

  var _super = _createSuper(AutocompleteEditor);

  function AutocompleteEditor() {
    _classCallCheck(this, AutocompleteEditor);

    return _super.apply(this, arguments);
  }

  _createClass(AutocompleteEditor, [{
    key: "postBuild",
    value: function postBuild() {
      if (window.Autocomplete) {
        /* create wrapper container */
        this.autocomplete_wrapper = document.createElement('div');
        /* insert wrapper after this.input in the DOM tree */

        this.input.parentNode.insertBefore(this.autocomplete_wrapper, this.input.nextSibling);
        /* move this.input into wrapper */

        this.autocomplete_wrapper.appendChild(this.input);
        /* create dropdown container */

        this.autocomplete_dropdown = document.createElement('ul');
        /* insert dropdown after this.input in the DOM tree */

        this.input.parentNode.insertBefore(this.autocomplete_dropdown, this.input.nextSibling);
      }

      _get(_getPrototypeOf(AutocompleteEditor.prototype), "postBuild", this).call(this);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var options;

      if (window.Autocomplete && !this.autocomplete_instance) {
        /* Get options, either global options from "this.defaults.options.autocomplete" or */

        /* single property options from schema "options.autocomplete" */
        options = this.expandCallbacks('autocomplete', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
          search: function search(jseditor, input) {
            console.log("No \"search\" callback defined for autocomplete in property \"".concat(jseditor.key, "\""));
            return [];
          },
          baseClass: 'autocomplete'
        }, this.defaults.options.autocomplete || {}, this.options.autocomplete || {}));
        this.autocomplete_wrapper.classList.add(options.baseClass);
        this.autocomplete_dropdown.classList.add("".concat(options.baseClass, "-result-list"));
        /* this.input.classList.add(options.baseClass + '-input'); */

        this.autocomplete_instance = new window.Autocomplete(this.autocomplete_wrapper, options);
      }

      _get(_getPrototypeOf(AutocompleteEditor.prototype), "afterInputReady", this).call(this);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.autocomplete_instance) {
        if (this.input && this.input.parentNode) this.input.parentNode.removeChild(this.input);
        if (this.autocomplete_dropdown && this.autocomplete_dropdown.parentNode) this.autocomplete_dropdown.parentNode.removeChild(this.autocomplete_dropdown);
        if (this.autocomplete_wrapper && this.autocomplete_wrapper.parentNode) this.autocomplete_wrapper.parentNode.removeChild(this.autocomplete_wrapper);
        this.autocomplete_instance = null;
      }

      _get(_getPrototypeOf(AutocompleteEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return AutocompleteEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/base64.js":
/*!*******************************!*\
  !*** ./src/editors/base64.js ***!
  \*******************************/
/*! exports provided: Base64Editor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Base64Editor", function() { return Base64Editor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var Base64Editor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(Base64Editor, _AbstractEditor);

  var _super = _createSuper(Base64Editor);

  function Base64Editor() {
    _classCallCheck(this, Base64Editor);

    return _super.apply(this, arguments);
  }

  _createClass(Base64Editor, [{
    key: "getNumColumns",
    value: function getNumColumns() {
      return 4;
    }
  }, {
    key: "setFileReaderListener",
    value: function setFileReaderListener(frMultiple) {
      var _this = this;

      frMultiple.addEventListener('load', function (event) {
        if (_this.count === _this.current_item_index) {
          /* Overwrite existing file by default, leave other properties unchanged */
          _this.value[_this.count][_this.key] = event.target.result;
        } else {
          var tempObject = {};
          /* Create empty object */

          for (var key in _this.parent.schema.properties) {
            tempObject[key] = '';
          }
          /* Set object media file */


          tempObject[_this.key] = event.target.result;

          _this.value.splice(_this.count, 0, tempObject);
          /* insert new file object */

        }
        /* Increment using the listener and not the 'for' loop as the listener will be processed asynchronously */


        _this.count += 1;
        /* When all files have been processed, update the value of the editor */

        if (_this.count === _this.total + _this.current_item_index) {
          _this.arrayEditor.setValue(_this.value);
        }
      });
    }
  }, {
    key: "build",
    value: function build() {
      var _this2 = this;

      this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      if (this.options.infoText) this.infoButton = this.theme.getInfoButton(this.options.infoText);
      /* Input that holds the base64 string */

      this.input = this.theme.getFormInputField('hidden');
      this.container.appendChild(this.input);
      /* Don't show uploader if this is readonly */

      if (!this.schema.readOnly && !this.schema.readonly) {
        if (!window.FileReader) throw new Error('FileReader required for base64 editor');
        /* File uploader */

        this.uploader = this.theme.getFormInputField('file');
        /* Set attribute of file input field to 'multiple' if: */

        /* 'multiple' key has been set to 'true' in the schema */

        /* and the parent object is of type 'object' */

        /* and the parent of the parent type has been set to 'array' */

        if (this.schema.options && this.schema.options.multiple && this.schema.options.multiple === true && this.parent && this.parent.schema.type === 'object' && this.parent.parent && this.parent.parent.schema.type === 'array') {
          this.uploader.setAttribute('multiple', '');
        }

        this.uploader.addEventListener('change', function (e) {
          e.preventDefault();
          e.stopPropagation();

          if (e.currentTarget.files && e.currentTarget.files.length) {
            /* Check the amount of files uploaded. */

            /* If 1, use the regular upload, otherwise use the multiple upload method */
            if (e.currentTarget.files.length > 1 && _this2.schema.options && _this2.schema.options.multiple && _this2.schema.options.multiple === true && _this2.parent && _this2.parent.schema.type === 'object' && _this2.parent.parent && _this2.parent.parent.schema.type === 'array') {
              /* Load editor of parent.parent to get the array */
              _this2.arrayEditor = _this2.jsoneditor.getEditor(_this2.parent.parent.path);
              /* Check the current value of this editor */

              _this2.value = _this2.arrayEditor.getValue();
              /* Set variables for amount of files, index of current array item and */

              /* count value containing current status of processed files */

              _this2.total = e.currentTarget.files.length;
              _this2.current_item_index = parseInt(_this2.parent.key);
              _this2.count = _this2.current_item_index;

              for (var i = 0; i < _this2.total; i++) {
                var frMultiple = new FileReader();

                _this2.setFileReaderListener(frMultiple);

                frMultiple.readAsDataURL(e.currentTarget.files[i]);
              }
            } else {
              var fr = new FileReader();

              fr.onload = function (evt) {
                _this2.value = evt.target.result;

                _this2.refreshPreview();

                _this2.onChange(true);

                fr = null;
              };

              fr.readAsDataURL(e.currentTarget.files[0]);
            }
          }
        });
      }

      this.preview = this.theme.getFormInputDescription(this.schema.description);
      this.container.appendChild(this.preview);
      this.control = this.theme.getFormControl(this.label, this.uploader || this.input, this.preview, this.infoButton);
      this.container.appendChild(this.control);
    }
  }, {
    key: "refreshPreview",
    value: function refreshPreview() {
      if (this.last_preview === this.value) return;
      this.last_preview = this.value;
      this.preview.innerHTML = '';
      if (!this.value) return;
      var mime = this.value.match(/^data:([^;,]+)[;,]/);
      if (mime) mime = mime[1];

      if (!mime) {
        this.preview.innerHTML = '<em>Invalid data URI</em>';
      } else {
        this.preview.innerHTML = "<strong>Type:</strong> ".concat(mime, ", <strong>Size:</strong> ").concat(Math.floor((this.value.length - this.value.split(',')[0].length - 1) / 1.33333), " bytes");

        if (mime.substr(0, 5) === 'image') {
          this.preview.innerHTML += '<br>';
          var img = document.createElement('img');
          img.style.maxWidth = '100%';
          img.style.maxHeight = '100px';
          img.src = this.value;
          this.preview.appendChild(img);
        }
      }
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        if (this.uploader) this.uploader.disabled = false;

        _get(_getPrototypeOf(Base64Editor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;
      if (this.uploader) this.uploader.disabled = true;

      _get(_getPrototypeOf(Base64Editor.prototype), "disable", this).call(this);
    }
  }, {
    key: "setValue",
    value: function setValue(val) {
      if (this.value !== val) {
        this.value = val;
        this.input.value = this.value;
        this.refreshPreview();
        this.onChange();
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.preview && this.preview.parentNode) this.preview.parentNode.removeChild(this.preview);
      if (this.title && this.title.parentNode) this.title.parentNode.removeChild(this.title);
      if (this.input && this.input.parentNode) this.input.parentNode.removeChild(this.input);
      if (this.uploader && this.uploader.parentNode) this.uploader.parentNode.removeChild(this.uploader);

      _get(_getPrototypeOf(Base64Editor.prototype), "destroy", this).call(this);
    }
  }]);

  return Base64Editor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/button.js":
/*!*******************************!*\
  !*** ./src/editors/button.js ***!
  \*******************************/
/*! exports provided: ButtonEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ButtonEditor", function() { return ButtonEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/* Non-Active editor for displaying buttons in form */


var ButtonEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(ButtonEditor, _AbstractEditor);

  var _super = _createSuper(ButtonEditor);

  function ButtonEditor(options, defaults) {
    var _this;

    _classCallCheck(this, ButtonEditor);

    _this = _super.call(this, options, defaults);
    _this.active = false;
    /* Set field to required in schema otherwise it will not be displayed */

    if (_this.parent && _this.parent.schema) {
      if (Array.isArray(_this.parent.schema.required)) {
        if (!_this.parent.schema.required.includes(_this.key)) {
          _this.parent.schema.required.push(_this.key);
        }
      } else {
        _this.parent.schema.required = [_this.key];
      }
    }

    return _this;
  }

  _createClass(ButtonEditor, [{
    key: "build",
    value: function build() {
      var _this2 = this;

      this.options.compact = true;
      /* Get options, either global options from "this.defaults.options.button" or */

      /* single property options from schema "options.button" */

      var title = this.schema.title || this.key;
      var options = this.expandCallbacks('button', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
        icon: '',
        validated: false,
        align: 'left',
        action: function action(jseditor, e) {
          window.alert("No button action defined for \"".concat(jseditor.path, "\""));
        }
      }, this.defaults.options.button || {}, this.options.button || {}));
      this.input = this.theme.getFormButton(title, options.icon, title);
      this.input.addEventListener('click', options.action, false);

      if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
        this.always_disabled = true;
        this.input.setAttribute('readonly', 'true');
      }
      /* Set custom attributes on input element. Parameter is array of protected keys. Empty array if none. */


      this.setInputAttributes(['readonly']);
      this.control = this.theme.getFormButtonHolder(options.align);
      this.control.appendChild(this.input);
      this.container.appendChild(this.control);

      this.changeHandler = function () {
        if (_this2.jsoneditor.validate(_this2.jsoneditor.getValue()).length > 0) _this2.disable();else _this2.enable();
      };
      /* Enable/disable the button depending on form validation */


      if (options.validated) this.jsoneditor.on('change', this.changeHandler);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        this.input.disabled = false;

        _get(_getPrototypeOf(ButtonEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;
      this.input.disabled = true;

      _get(_getPrototypeOf(ButtonEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 2;
    }
  }, {
    key: "activate",
    value: function activate() {
      this.active = false;
      this.enable();
    }
  }, {
    key: "deactivate",
    value: function deactivate() {
      /* only non required properties can be deactivated. */
      if (!this.isRequired()) {
        this.active = false;
        this.disable();
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this.jsoneditor.off('change', this.changeHandler);
      this.changeHandler = null;

      _get(_getPrototypeOf(ButtonEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return ButtonEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/checkbox.js":
/*!*********************************!*\
  !*** ./src/editors/checkbox.js ***!
  \*********************************/
/*! exports provided: CheckboxEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "CheckboxEditor", function() { return CheckboxEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var CheckboxEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(CheckboxEditor, _AbstractEditor);

  var _super = _createSuper(CheckboxEditor);

  function CheckboxEditor() {
    _classCallCheck(this, CheckboxEditor);

    return _super.apply(this, arguments);
  }

  _createClass(CheckboxEditor, [{
    key: "setValue",
    value: function setValue(value, initial) {
      value = !!value;
      var changed = this.getValue() !== value;
      this.value = value;
      this.input.checked = this.value;
      this.onChange(changed);
    }
  }, {
    key: "register",
    value: function register() {
      _get(_getPrototypeOf(CheckboxEditor.prototype), "register", this).call(this);

      if (!this.input) return;
      this.input.setAttribute('name', this.formname);
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(CheckboxEditor.prototype), "unregister", this).call(this);

      if (!this.input) return;
      this.input.removeAttribute('name');
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return Math.min(12, Math.max(this.getTitle().length / 7, 2));
    }
  }, {
    key: "build",
    value: function build() {
      var _this = this;

      if (!this.parent.options.table_row) {
        this.label = this.header = this.theme.getCheckboxLabel(this.getTitle(), this.isRequired());
        this.label.htmlFor = this.formname;
      }

      if (this.schema.description) this.description = this.theme.getFormInputDescription(this.schema.description);
      if (this.options.infoText && !this.options.compact) this.infoButton = this.theme.getInfoButton(this.options.infoText);
      if (this.options.compact) this.container.classList.add('compact');
      this.input = this.theme.getCheckbox();
      this.input.id = this.formname;
      this.control = this.theme.getFormControl(this.label, this.input, this.description, this.infoButton);

      if (this.schema.readOnly || this.schema.readonly) {
        this.always_disabled = true;
        this.input.disabled = true;
      }

      this.input.addEventListener('change', function (e) {
        e.preventDefault();
        e.stopPropagation();
        _this.value = e.currentTarget.checked;

        _this.onChange(true);
      });
      this.container.appendChild(this.control);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        this.input.disabled = false;

        _get(_getPrototypeOf(CheckboxEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;
      this.input.disabled = true;

      _get(_getPrototypeOf(CheckboxEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.label && this.label.parentNode) this.label.parentNode.removeChild(this.label);
      if (this.description && this.description.parentNode) this.description.parentNode.removeChild(this.description);
      if (this.input && this.input.parentNode) this.input.parentNode.removeChild(this.input);

      _get(_getPrototypeOf(CheckboxEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "showValidationErrors",
    value: function showValidationErrors(errors) {
      var _this2 = this;

      if (this.jsoneditor.options.show_errors === 'always') {} else if (!this.is_dirty && this.previous_error_setting === this.jsoneditor.options.show_errors) {
        return;
      }

      this.previous_error_setting = this.jsoneditor.options.show_errors;

      var addMessage = function addMessage(messages, error) {
        if (error.path === _this2.path) {
          messages.push(error.message);
        }

        return messages;
      };

      var messages = errors.reduce(addMessage, []);
      this.input.controlgroup = this.control;

      if (messages.length) {
        this.theme.addInputError(this.input, "".concat(messages.join('. '), "."));
      } else {
        this.theme.removeInputError(this.input);
      }
    }
  }]);

  return CheckboxEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/choices.css.js":
/*!************************************!*\
  !*** ./src/editors/choices.css.js ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* eslint-disable */
/* harmony default export */ __webpack_exports__["default"] = ({
  ".choices > *": "box-sizing:border-box"
});
/* eslint-enable */

/***/ }),

/***/ "./src/editors/choices.js":
/*!********************************!*\
  !*** ./src/editors/choices.js ***!
  \********************************/
/*! exports provided: ChoicesEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ChoicesEditor", function() { return ChoicesEditor; });
/* harmony import */ var _select_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./select.js */ "./src/editors/select.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
/* harmony import */ var _choices_css_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./choices.css.js */ "./src/editors/choices.css.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }




var ChoicesEditor = /*#__PURE__*/function (_SelectEditor) {
  _inherits(ChoicesEditor, _SelectEditor);

  var _super = _createSuper(ChoicesEditor);

  function ChoicesEditor() {
    _classCallCheck(this, ChoicesEditor);

    return _super.apply(this, arguments);
  }

  _createClass(ChoicesEditor, [{
    key: "setValue",
    value: function setValue(value, initial) {
      if (this.choices_instance) {
        /* Sanitize value before setting it */
        var sanitized = this.typecast(value || '');
        if (!this.enum_values.includes(sanitized)) sanitized = this.enum_values[0];
        if (this.value === sanitized) return;
        if (initial) this.is_dirty = false;else if (this.jsoneditor.options.show_errors === 'change') this.is_dirty = true;
        this.input.value = this.enum_options[this.enum_values.indexOf(sanitized)];
        this.choices_instance.setChoiceByValue(this.input.value);
        this.value = sanitized;
        this.onChange();
      } else _get(_getPrototypeOf(ChoicesEditor.prototype), "setValue", this).call(this, value, initial);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      if (window.Choices && !this.choices_instance) {
        /* Get options, either global options from "this.defaults.options.choices" or */

        /* single property options from schema "options.choices" */
        var options = this.expandCallbacks('choices', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.defaults.options.choices || {}, this.options.choices || {}));
        this.choices_instance = new window.Choices(this.input, options);
      }

      _get(_getPrototypeOf(ChoicesEditor.prototype), "afterInputReady", this).call(this);
    }
  }, {
    key: "onWatchedFieldChange",
    value: function onWatchedFieldChange() {
      var _this = this;

      _get(_getPrototypeOf(ChoicesEditor.prototype), "onWatchedFieldChange", this).call(this);

      if (this.choices_instance) {
        var choicesList = this.enum_options.map(function (v, i) {
          return {
            value: v,
            label: _this.enum_display[i]
          };
        });
        this.choices_instance.setChoices(choicesList, 'value', 'label', true);
        this.choices_instance.setChoiceByValue("".concat(this.value));
        /* Set new selection */
      }
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled && this.choices_instance) this.choices_instance.enable();

      _get(_getPrototypeOf(ChoicesEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.choices_instance) this.choices_instance.disable();

      _get(_getPrototypeOf(ChoicesEditor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.choices_instance) {
        this.choices_instance.destroy();
        this.choices_instance = null;
      }

      _get(_getPrototypeOf(ChoicesEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return ChoicesEditor;
}(_select_js__WEBPACK_IMPORTED_MODULE_0__["SelectEditor"]);
ChoicesEditor.rules = _choices_css_js__WEBPACK_IMPORTED_MODULE_2__["default"];

/***/ }),

/***/ "./src/editors/colorpicker.js":
/*!************************************!*\
  !*** ./src/editors/colorpicker.js ***!
  \************************************/
/*! exports provided: ColorEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ColorEditor", function() { return ColorEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/*

Edtended handling of  oolor type fields.

Has optional support for using https://github.com/Sphinxxxx/vanilla-picker.

*/


var ColorEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(ColorEditor, _StringEditor);

  var _super = _createSuper(ColorEditor);

  function ColorEditor() {
    _classCallCheck(this, ColorEditor);

    return _super.apply(this, arguments);
  }

  _createClass(ColorEditor, [{
    key: "postBuild",
    value: function postBuild() {
      if (window.Picker) {
        this.input.type = 'text';
      }

      this.input.style.padding = '3px';
    }
  }, {
    key: "setValue",
    value: function setValue(value, initial, fromTemplate) {
      var res = _get(_getPrototypeOf(ColorEditor.prototype), "setValue", this).call(this, value, initial, fromTemplate);

      if (this.picker_instance && this.picker_instance.domElement && res && res.changed) {
        this.picker_instance.setColor(res.value, true);
      }

      return res;
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 2;
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      _get(_getPrototypeOf(ColorEditor.prototype), "afterInputReady", this).call(this);

      this.createPicker(true);
    }
  }, {
    key: "disable",
    value: function disable() {
      _get(_getPrototypeOf(ColorEditor.prototype), "disable", this).call(this);

      if (this.picker_instance && this.picker_instance.domElement) {
        /* Disable picker cursor dragging */
        this.picker_instance.domElement.style.pointerEvents = 'none';
        /* Disable picker buttons */

        var buttons = this.picker_instance.domElement.querySelectorAll('button');

        for (var i = 0; i < buttons.length; i++) {
          buttons[i].disabled = true;
        }
      }
    }
  }, {
    key: "enable",
    value: function enable() {
      _get(_getPrototypeOf(ColorEditor.prototype), "enable", this).call(this);

      if (this.picker_instance && this.picker_instance.domElement) {
        /* Enable picker cursor dragging */
        this.picker_instance.domElement.style.pointerEvents = 'auto';
        /* Enable picker buttons */

        var buttons = this.picker_instance.domElement.querySelectorAll('button');

        for (var i = 0; i < buttons.length; i++) {
          buttons[i].disabled = false;
        }
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this.createPicker(false);

      _get(_getPrototypeOf(ColorEditor.prototype), "destroy", this).call(this);
    }
    /* helper functions */

  }, {
    key: "createPicker",
    value: function createPicker(create) {
      var _this = this;

      if (create) {
        /* create vanilla-picker */
        if (window.Picker && !this.picker_instance) {
          /* do when vanilla-picker loaded */
          var options = this.expandCallbacks('colorpicker', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
            editor: false,

            /* default no editor */
            alpha: false,

            /* default no alpha */
            color: this.value,
            popup: 'bottom'
            /* show in the bottom */

          }, this.defaults.options.colorpicker || {}, this.options.colorpicker || {}, {
            parent: this.container
          }));

          var updateHandler = function updateHandler(color) {
            var format = _this.picker_instance.settings.editorFormat;
            var isAlpha = _this.picker_instance.settings.alpha;

            _this.setValue(format === 'hex' ? isAlpha ? color.hex : color.hex.slice(0, 7) : color["".concat(format + (isAlpha ? 'a' : ''), "String")]);
          };

          if (!options.popup && typeof options.onChange !== 'function') options.onChange = updateHandler;else if (options.popup && typeof options.onDone !== 'function') options.onDone = updateHandler;
          this.picker_instance = new window.Picker(options);
          /* this.picker_instance.openHandler() */

          if (!options.popup) {
            /* use inline colorPicker */
            this.input.style.display = 'none';
            this.theme.afterInputReady(this.picker_instance.domElement);
          }
        }
      } else {
        /* destroy vanilla-picker */
        if (this.picker_instance) {
          this.picker_instance.destroy();
          this.picker_instance = null;
          this.input.style.display = '';
        }
      }
    }
  }]);

  return ColorEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/datetime.js":
/*!*********************************!*\
  !*** ./src/editors/datetime.js ***!
  \*********************************/
/*! exports provided: DatetimeEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DatetimeEditor", function() { return DatetimeEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/*

Edtended handling of date, time and datetime-local type fields.

Works with both string and integer data types. (default only support string type)

Has optional support for using flatpickr datepicker.
All flatpickr options is supported with a few minor differences.
- "enableTime" and "noCalendar" are set automatically, based on the data type.
- Extra config option "errorDateFormat". If this is set, it will replace the format displayed in error messages.
- It is not possible to use "inline" and "wrap" options together.
- When using the "wrap" option, "toggle" and "clear" buttons are automatically added to markup. 2 extra boolean options ("showToggleButton" and "showClearButton") are available to control which buttons to display. Note: not all frameworks supports this. (Works in: Bootstrap and Foundation)
- When using the "inline" option, an extra boolean option ("inlineHideInput") is available to hide the original input field.
- If "mode" is set to either "multiple" or "range", only string data type is supported. Also the result from these is returned as a string not an array.

ToDo:
 - Improve Handling of flatpicker "multiple" and "range" modes. (Currently the values are just added as string values, but the optimal scenario would be to save those as array if possible)

*/

var DatetimeEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(DatetimeEditor, _StringEditor);

  var _super = _createSuper(DatetimeEditor);

  function DatetimeEditor() {
    _classCallCheck(this, DatetimeEditor);

    return _super.apply(this, arguments);
  }

  _createClass(DatetimeEditor, [{
    key: "build",
    value: function build() {
      _get(_getPrototypeOf(DatetimeEditor.prototype), "build", this).call(this);

      if (!this.input) return;

      if (window.flatpickr && _typeof(this.options.flatpickr) === 'object') {
        /* Make sure that flatpickr settings matches the input type */
        this.options.flatpickr.enableTime = this.schema.format !== 'date';
        this.options.flatpickr.noCalendar = this.schema.format === 'time';
        /* Curently only string can contain range or multiple values */

        if (this.schema.type === 'integer') this.options.flatpickr.mode = 'single';
        /* Attribute for flatpicker */

        this.input.setAttribute('data-input', '');
        var input = this.input;

        if (this.options.flatpickr.wrap === true) {
          /* Create buttons for input group */
          var buttons = [];

          if (this.options.flatpickr.showToggleButton !== false) {
            var toggleButton = this.getButton('', this.schema.format === 'time' ? 'time' : 'calendar', this.translate('flatpickr_toggle_button'));
            /* Attribute for flatpicker */

            toggleButton.setAttribute('data-toggle', '');
            buttons.push(toggleButton);
          }

          if (this.options.flatpickr.showClearButton !== false) {
            var clearButton = this.getButton('', 'clear', this.translate('flatpickr_clear_button'));
            /* Attribute for flatpicker */

            clearButton.setAttribute('data-clear', '');
            buttons.push(clearButton);
          }
          /* Save position of input field */


          var parentNode = this.input.parentNode;
          var nextSibling = this.input.nextSibling;
          var buttonContainer = this.theme.getInputGroup(this.input, buttons);

          if (buttonContainer !== undefined) {
            /* Make sure "inline" option is turned off */
            this.options.flatpickr.inline = false;
            /* Insert container at same position as input field */

            parentNode.insertBefore(buttonContainer, nextSibling);
            input = buttonContainer;
          } else {
            this.options.flatpickr.wrap = false;
          }
        }

        this.flatpickr = window.flatpickr(input, this.options.flatpickr);

        if (this.options.flatpickr.inline === true && this.options.flatpickr.inlineHideInput === true) {
          this.input.setAttribute('type', 'hidden');
        }
      }
    }
  }, {
    key: "getValue",
    value: function getValue() {
      if (!this.dependenciesFulfilled) {
        return undefined;
      }

      if (this.schema.type === 'string') {
        return this.value;
      }

      if (this.value === '' || this.value === undefined) {
        return undefined;
      }

      var value = this.schema.format === 'time' ? "1970-01-01 ".concat(this.value) : this.value;
      return parseInt(new Date(value).getTime() / 1000);
    }
  }, {
    key: "setValue",
    value: function setValue(value, initial, fromTemplate) {
      if (this.schema.type === 'string') {
        _get(_getPrototypeOf(DatetimeEditor.prototype), "setValue", this).call(this, value, initial, fromTemplate);

        if (this.flatpickr) this.flatpickr.setDate(value);
      } else if (value > 0) {
        var dateObj = new Date(value * 1000);
        var year = dateObj.getFullYear();
        var month = this.zeroPad(dateObj.getMonth() + 1);
        var day = this.zeroPad(dateObj.getDate());
        var hour = this.zeroPad(dateObj.getHours());
        var min = this.zeroPad(dateObj.getMinutes());
        var sec = this.zeroPad(dateObj.getSeconds());
        var date = [year, month, day].join('-');
        var time = [hour, min, sec].join(':');
        var dateValue = "".concat(date, "T").concat(time);
        if (this.schema.format === 'date') dateValue = date;else if (this.schema.format === 'time') dateValue = time;
        this.input.value = dateValue;
        this.refreshValue();
        if (this.flatpickr) this.flatpickr.setDate(dateValue);
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.flatpickr) this.flatpickr.destroy();
      this.flatpickr = null;

      _get(_getPrototypeOf(DatetimeEditor.prototype), "destroy", this).call(this);
    }
    /* helper function */

  }, {
    key: "zeroPad",
    value: function zeroPad(value) {
      return "0".concat(value).slice(-2);
    }
  }]);

  return DatetimeEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/describedby.js":
/*!************************************!*\
  !*** ./src/editors/describedby.js ***!
  \************************************/
/*! exports provided: DescribedByEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "DescribedByEditor", function() { return DescribedByEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/* hyper-link describeBy Editor */


var DescribedByEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(DescribedByEditor, _AbstractEditor);

  var _super = _createSuper(DescribedByEditor);

  function DescribedByEditor() {
    _classCallCheck(this, DescribedByEditor);

    return _super.apply(this, arguments);
  }

  _createClass(DescribedByEditor, [{
    key: "register",
    value: function register() {
      if (this.editors) {
        for (var i = 0; i < this.editors.length; i++) {
          if (!this.editors[i]) continue;
          this.editors[i].unregister();
        }

        if (this.editors[this.currentEditor]) this.editors[this.currentEditor].register();
      }

      _get(_getPrototypeOf(DescribedByEditor.prototype), "register", this).call(this);
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(DescribedByEditor.prototype), "unregister", this).call(this);

      if (this.editors) {
        for (var i = 0; i < this.editors.length; i++) {
          if (!this.editors[i]) continue;
          this.editors[i].unregister();
        }
      }
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      if (!this.editors[this.currentEditor]) return 4;
      return Math.max(this.editors[this.currentEditor].getNumColumns(), 4);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (this.editors) {
        for (var i = 0; i < this.editors.length; i++) {
          if (!this.editors[i]) continue;
          this.editors[i].enable();
        }
      }

      _get(_getPrototypeOf(DescribedByEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable() {
      if (this.editors) {
        for (var i = 0; i < this.editors.length; i++) {
          if (!this.editors[i]) continue;
          this.editors[i].disable();
        }
      }

      _get(_getPrototypeOf(DescribedByEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "switchEditor",
    value: function switchEditor() {
      var _this = this;

      var vars = this.getWatchedFieldValues();
      if (!vars) return;
      /* var ref = this.template.fillFromObject(vars); */

      /* var ref = this.template(vars); */

      var ref = document.location.origin + document.location.pathname + this.template(vars);

      if (!this.editors[this.refs[ref]]) {
        this.buildChildEditor(ref);
      }

      this.currentEditor = this.refs[ref];
      this.register();
      this.editors.forEach(function (editor, ref) {
        if (!editor) return;

        if (_this.currentEditor === ref) {
          editor.container.style.display = '';
        } else {
          editor.container.style.display = 'none';
        }
      });
      this.refreshValue();
      this.onChange(true);
    }
  }, {
    key: "buildChildEditor",
    value: function buildChildEditor(ref) {
      this.refs[ref] = this.editors.length;
      var holder = this.theme.getChildEditorHolder();
      this.editor_holder.appendChild(holder);
      var schema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.schema, this.jsoneditor.refs[ref]);
      var editorClass = this.jsoneditor.getEditorClass(schema, this.jsoneditor);
      var editor = this.jsoneditor.createEditor(editorClass, {
        jsoneditor: this.jsoneditor,
        schema: schema,
        container: holder,
        path: this.path,
        parent: this,
        required: true
      });
      this.editors.push(editor);
      editor.preBuild();
      editor.build();
      editor.postBuild();
    }
  }, {
    key: "preBuild",
    value: function preBuild() {
      this.refs = {};
      this.editors = [];
      this.currentEditor = '';
      var i;

      for (i = 0; i < this.schema.links.length; i++) {
        if (this.schema.links[i].rel.toLowerCase() === 'describedby') {
          /* this.template = new UriTemplate(this.schema.links[i].href); */
          this.template = this.jsoneditor.compileTemplate(this.schema.links[i].href, this.template_engine);
          break;
        }
      }
      /* this.template.fill(function(varName) {
        this.schema.watch = this.schema.watch || {};
        this.schema.watch[varName] = varName;
        return '';
      }); */


      this.schema.links = this.schema.links.slice(0, i).concat(this.schema.links.slice(i + 1));
      if (this.schema.links.length === 0) delete this.schema.links;
      this.baseSchema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.schema);
    }
  }, {
    key: "build",
    value: function build() {
      this.editor_holder = document.createElement('div');
      this.container.appendChild(this.editor_holder);
      this.switchEditor();
    }
  }, {
    key: "onWatchedFieldChange",
    value: function onWatchedFieldChange() {
      this.switchEditor();
    }
  }, {
    key: "onChildEditorChange",
    value: function onChildEditorChange(editor) {
      if (this.editors[this.currentEditor]) {
        this.refreshValue();
      }

      _get(_getPrototypeOf(DescribedByEditor.prototype), "onChildEditorChange", this).call(this, editor);
    }
  }, {
    key: "refreshValue",
    value: function refreshValue() {
      if (this.editors[this.currentEditor]) {
        this.value = this.editors[this.currentEditor].getValue();
      }
    }
  }, {
    key: "setValue",
    value: function setValue(val, initial) {
      if (this.editors[this.currentEditor]) {
        this.editors[this.currentEditor].setValue(val, initial);
        this.refreshValue();
        this.onChange();
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this.editors.forEach(function (editor) {
        if (editor) editor.destroy();
      });

      if (this.editor_holder && this.editor_holder.parentNode) {
        this.editor_holder.parentNode.removeChild(this.editor_holder);
      }

      _get(_getPrototypeOf(DescribedByEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "showValidationErrors",
    value: function showValidationErrors(errors) {
      this.editors.forEach(function (editor) {
        if (!editor) return;
        editor.showValidationErrors(errors);
      });
    }
  }]);

  return DescribedByEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/enum.js":
/*!*****************************!*\
  !*** ./src/editors/enum.js ***!
  \*****************************/
/*! exports provided: EnumEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "EnumEditor", function() { return EnumEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
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

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/* Enum Editor (used for objects and arrays with enumerated values) */

var EnumEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(EnumEditor, _AbstractEditor);

  var _super = _createSuper(EnumEditor);

  function EnumEditor() {
    _classCallCheck(this, EnumEditor);

    return _super.apply(this, arguments);
  }

  _createClass(EnumEditor, [{
    key: "getNumColumns",
    value: function getNumColumns() {
      return 4;
    }
  }, {
    key: "build",
    value: function build() {
      var _this = this;

      this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      this.container.appendChild(this.title);
      this.options.enum_titles = this.options.enum_titles || [];
      this["enum"] = this.schema["enum"];
      this.selected = 0;
      this.select_options = [];
      this.html_values = [];

      for (var i = 0; i < this["enum"].length; i++) {
        this.select_options[i] = this.options.enum_titles[i] || "Value ".concat(i + 1);
        this.html_values[i] = this.getHTML(this["enum"][i]);
      }
      /* Switcher */


      this.switcher = this.theme.getSwitcher(this.select_options);
      this.container.appendChild(this.switcher);
      /* Display area */

      this.display_area = this.theme.getIndentedPanel();
      this.container.appendChild(this.display_area);
      if (this.options.hide_display) this.display_area.style.display = 'none';
      this.switcher.addEventListener('change', function (e) {
        _this.selected = _this.select_options.indexOf(e.currentTarget.value);
        _this.value = _this["enum"][_this.selected];

        _this.refreshValue();

        _this.onChange(true);
      });
      this.value = this["enum"][0];
      this.refreshValue();
      if (this["enum"].length === 1) this.switcher.style.display = 'none';
    }
  }, {
    key: "refreshValue",
    value: function refreshValue() {
      var _this2 = this;

      this.selected = -1;
      var stringified = JSON.stringify(this.value);
      this["enum"].forEach(function (el, i) {
        if (stringified === JSON.stringify(el)) {
          _this2.selected = i;
          return false;
        }
      });

      if (this.selected < 0) {
        this.setValue(this["enum"][0]);
        return;
      }

      this.switcher.value = this.select_options[this.selected];
      this.display_area.innerHTML = this.html_values[this.selected];
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        this.switcher.disabled = false;

        _get(_getPrototypeOf(EnumEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;
      this.switcher.disabled = true;

      _get(_getPrototypeOf(EnumEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "getHTML",
    value: function getHTML(el) {
      var _this3 = this;

      var each = function each(obj, callback) {
        if (Array.isArray(obj) || typeof obj.length === 'number' && obj.length > 0 && obj.length - 1 in obj) {
          Array.from(obj).forEach(function (e, i) {
            return callback(i, e);
          });
        } else {
          Object.entries(obj).forEach(function (_ref) {
            var _ref2 = _slicedToArray(_ref, 2),
                key = _ref2[0],
                value = _ref2[1];

            return callback(key, value);
          });
        }
      };

      if (el === null) {
        return '<em>null</em>';
        /* Array or Object */
      } else if (_typeof(el) === 'object') {
        /* TODO: use theme */
        var ret = '';

        var callback = function callback(i, child) {
          var html = _this3.getHTML(child);
          /* Add the keys to object children */


          if (!Array.isArray(el)) {
            /* TODO: use theme */
            html = "<div><em>".concat(i, "</em>: ").concat(html, "</div>");
          }
          /* TODO: use theme */


          ret += "<li>".concat(html, "</li>");
        };

        each(el, callback);
        if (Array.isArray(el)) ret = "<ol>".concat(ret, "</ol>");else ret = "<ul style='margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0;'>".concat(ret, "</ul>");
        return ret;
        /* Boolean */
      } else if (typeof el === 'boolean') {
        return el ? 'true' : 'false';
        /* String */
      } else if (typeof el === 'string') {
        return el.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        /* Number */
      }

      return el;
    }
  }, {
    key: "setValue",
    value: function setValue(val) {
      if (this.value !== val) {
        this.value = val;
        this.refreshValue();
        this.onChange();
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.display_area && this.display_area.parentNode) this.display_area.parentNode.removeChild(this.display_area);
      if (this.title && this.title.parentNode) this.title.parentNode.removeChild(this.title);
      if (this.switcher && this.switcher.parentNode) this.switcher.parentNode.removeChild(this.switcher);

      _get(_getPrototypeOf(EnumEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return EnumEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/hidden.js":
/*!*******************************!*\
  !*** ./src/editors/hidden.js ***!
  \*******************************/
/*! exports provided: HiddenEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "HiddenEditor", function() { return HiddenEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/**
 * Created by Mehmet Baker on 12.04.2017
 */

var HiddenEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(HiddenEditor, _AbstractEditor);

  var _super = _createSuper(HiddenEditor);

  function HiddenEditor() {
    _classCallCheck(this, HiddenEditor);

    return _super.apply(this, arguments);
  }

  _createClass(HiddenEditor, [{
    key: "register",
    value: function register() {
      _get(_getPrototypeOf(HiddenEditor.prototype), "register", this).call(this);

      if (!this.input) return;
      this.input.setAttribute('name', this.formname);
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(HiddenEditor.prototype), "unregister", this).call(this);

      if (!this.input) return;
      this.input.removeAttribute('name');
    }
  }, {
    key: "setValue",
    value: function setValue(value, initial, fromTemplate) {
      if (this.template && !fromTemplate) {
        return;
      }

      if (value === null || typeof value === 'undefined') value = '';else if (_typeof(value) === 'object') value = JSON.stringify(value);else if (typeof value !== 'string') value = "".concat(value);
      if (value === this.serialized) return;
      /* Sanitize value before setting it */

      var sanitized = this.sanitize(value);

      if (this.input.value === sanitized) {
        return;
      }

      this.input.value = sanitized;
      var changed = fromTemplate || this.getValue() !== value;
      this.refreshValue();
      if (initial) this.is_dirty = false;else if (this.jsoneditor.options.show_errors === 'change') this.is_dirty = true;
      if (this.adjust_height) this.adjust_height(this.input);
      /* Bubble this setValue to parents if the value changed */

      this.onChange(changed);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 2;
    }
  }, {
    key: "enable",
    value: function enable() {
      _get(_getPrototypeOf(HiddenEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable() {
      _get(_getPrototypeOf(HiddenEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "refreshValue",
    value: function refreshValue() {
      this.value = this.input.value;
      if (typeof this.value !== 'string') this.value = '';
      this.serialized = this.value;
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this.template = null;
      if (this.input && this.input.parentNode) this.input.parentNode.removeChild(this.input);
      if (this.label && this.label.parentNode) this.label.parentNode.removeChild(this.label);
      if (this.description && this.description.parentNode) this.description.parentNode.removeChild(this.description);

      _get(_getPrototypeOf(HiddenEditor.prototype), "destroy", this).call(this);
    }
    /**
     * This is overridden in derivative editors
     */

  }, {
    key: "sanitize",
    value: function sanitize(value) {
      return value;
    }
    /**
     * Re-calculates the value if needed
     */

  }, {
    key: "onWatchedFieldChange",
    value: function onWatchedFieldChange() {
      var vars;
      /* If this editor needs to be rendered by a macro template */

      if (this.template) {
        vars = this.getWatchedFieldValues();
        this.setValue(this.template(vars), false, true);
      }

      _get(_getPrototypeOf(HiddenEditor.prototype), "onWatchedFieldChange", this).call(this);
    }
  }, {
    key: "build",
    value: function build() {
      this.format = this.schema.format;

      if (!this.format && this.options.default_format) {
        this.format = this.options.default_format;
      }

      if (this.options.format) {
        this.format = this.options.format;
      }

      this.input_type = 'hidden';
      this.input = this.theme.getFormInputField(this.input_type);
      if (this.format) this.input.setAttribute('data-schemaformat', this.format);
      this.container.appendChild(this.input);
      /* Compile and store the template */

      if (this.schema.template) {
        var callback = this.expandCallbacks('template', {
          template: this.schema.template
        });
        if (typeof callback.template === 'function') this.template = callback.template;else this.template = this.jsoneditor.compileTemplate(this.schema.template, this.template_engine);
        this.refreshValue();
      } else {
        this.refreshValue();
      }
    }
  }]);

  return HiddenEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/index.js":
/*!******************************!*\
  !*** ./src/editors/index.js ***!
  \******************************/
/*! exports provided: editors */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "editors", function() { return editors; });
/* harmony import */ var _ace_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ace.js */ "./src/editors/ace.js");
/* harmony import */ var _array_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./array.js */ "./src/editors/array.js");
/* harmony import */ var _array_choices_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./array/choices.js */ "./src/editors/array/choices.js");
/* harmony import */ var _array_select2_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./array/select2.js */ "./src/editors/array/select2.js");
/* harmony import */ var _array_selectize_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./array/selectize.js */ "./src/editors/array/selectize.js");
/* harmony import */ var _autocomplete_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./autocomplete.js */ "./src/editors/autocomplete.js");
/* harmony import */ var _base64_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./base64.js */ "./src/editors/base64.js");
/* harmony import */ var _button_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./button.js */ "./src/editors/button.js");
/* harmony import */ var _checkbox_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./checkbox.js */ "./src/editors/checkbox.js");
/* harmony import */ var _choices_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./choices.js */ "./src/editors/choices.js");
/* harmony import */ var _datetime_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./datetime.js */ "./src/editors/datetime.js");
/* harmony import */ var _describedby_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./describedby.js */ "./src/editors/describedby.js");
/* harmony import */ var _enum_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./enum.js */ "./src/editors/enum.js");
/* harmony import */ var _hidden_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./hidden.js */ "./src/editors/hidden.js");
/* harmony import */ var _info_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./info.js */ "./src/editors/info.js");
/* harmony import */ var _integer_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./integer.js */ "./src/editors/integer.js");
/* harmony import */ var _ip_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./ip.js */ "./src/editors/ip.js");
/* harmony import */ var _jodit_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./jodit.js */ "./src/editors/jodit.js");
/* harmony import */ var _multiple_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./multiple.js */ "./src/editors/multiple.js");
/* harmony import */ var _multiselect_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./multiselect.js */ "./src/editors/multiselect.js");
/* harmony import */ var _null_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./null.js */ "./src/editors/null.js");
/* harmony import */ var _number_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./number.js */ "./src/editors/number.js");
/* harmony import */ var _object_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./object.js */ "./src/editors/object.js");
/* harmony import */ var _radio_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./radio.js */ "./src/editors/radio.js");
/* harmony import */ var _sceditor_js__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./sceditor.js */ "./src/editors/sceditor.js");
/* harmony import */ var _select_js__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./select.js */ "./src/editors/select.js");
/* harmony import */ var _select2_js__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ./select2.js */ "./src/editors/select2.js");
/* harmony import */ var _selectize_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ./selectize.js */ "./src/editors/selectize.js");
/* harmony import */ var _signature_js__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./signature.js */ "./src/editors/signature.js");
/* harmony import */ var _simplemde_js__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./simplemde.js */ "./src/editors/simplemde.js");
/* harmony import */ var _starrating_js__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! ./starrating.js */ "./src/editors/starrating.js");
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _table_js__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! ./table.js */ "./src/editors/table.js");
/* harmony import */ var _upload_js__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ./upload.js */ "./src/editors/upload.js");
/* harmony import */ var _uuid_js__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ./uuid.js */ "./src/editors/uuid.js");
/* harmony import */ var _colorpicker_js__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! ./colorpicker.js */ "./src/editors/colorpicker.js");
/* Internal helper function called only here so we won't export as part of class */

/* Previously the assignment to the JSONEditor.defaults.editors was done in each of the editor */

/* files but doing it this way removes each of the editors' dependency on JSONEditor */




































var editors = {
  ace: _ace_js__WEBPACK_IMPORTED_MODULE_0__["AceEditor"],
  array: _array_js__WEBPACK_IMPORTED_MODULE_1__["ArrayEditor"],
  arrayChoices: _array_choices_js__WEBPACK_IMPORTED_MODULE_2__["ArrayChoicesEditor"],
  arraySelect2: _array_select2_js__WEBPACK_IMPORTED_MODULE_3__["ArraySelect2Editor"],
  arraySelectize: _array_selectize_js__WEBPACK_IMPORTED_MODULE_4__["ArraySelectizeEditor"],
  autocomplete: _autocomplete_js__WEBPACK_IMPORTED_MODULE_5__["AutocompleteEditor"],
  base64: _base64_js__WEBPACK_IMPORTED_MODULE_6__["Base64Editor"],
  button: _button_js__WEBPACK_IMPORTED_MODULE_7__["ButtonEditor"],
  checkbox: _checkbox_js__WEBPACK_IMPORTED_MODULE_8__["CheckboxEditor"],
  choices: _choices_js__WEBPACK_IMPORTED_MODULE_9__["ChoicesEditor"],
  datetime: _datetime_js__WEBPACK_IMPORTED_MODULE_10__["DatetimeEditor"],
  describedBy: _describedby_js__WEBPACK_IMPORTED_MODULE_11__["DescribedByEditor"],
  "enum": _enum_js__WEBPACK_IMPORTED_MODULE_12__["EnumEditor"],
  hidden: _hidden_js__WEBPACK_IMPORTED_MODULE_13__["HiddenEditor"],
  info: _info_js__WEBPACK_IMPORTED_MODULE_14__["InfoEditor"],
  integer: _integer_js__WEBPACK_IMPORTED_MODULE_15__["IntegerEditor"],
  ip: _ip_js__WEBPACK_IMPORTED_MODULE_16__["IpEditor"],
  jodit: _jodit_js__WEBPACK_IMPORTED_MODULE_17__["JoditEditor"],
  multiple: _multiple_js__WEBPACK_IMPORTED_MODULE_18__["MultipleEditor"],
  multiselect: _multiselect_js__WEBPACK_IMPORTED_MODULE_19__["MultiSelectEditor"],
  "null": _null_js__WEBPACK_IMPORTED_MODULE_20__["NullEditor"],
  number: _number_js__WEBPACK_IMPORTED_MODULE_21__["NumberEditor"],
  object: _object_js__WEBPACK_IMPORTED_MODULE_22__["ObjectEditor"],
  radio: _radio_js__WEBPACK_IMPORTED_MODULE_23__["RadioEditor"],
  sceditor: _sceditor_js__WEBPACK_IMPORTED_MODULE_24__["ScEditor"],
  select: _select_js__WEBPACK_IMPORTED_MODULE_25__["SelectEditor"],
  select2: _select2_js__WEBPACK_IMPORTED_MODULE_26__["Select2Editor"],
  selectize: _selectize_js__WEBPACK_IMPORTED_MODULE_27__["SelectizeEditor"],
  signature: _signature_js__WEBPACK_IMPORTED_MODULE_28__["SignatureEditor"],
  simplemde: _simplemde_js__WEBPACK_IMPORTED_MODULE_29__["SimplemdeEditor"],
  starrating: _starrating_js__WEBPACK_IMPORTED_MODULE_30__["StarratingEditor"],
  string: _string_js__WEBPACK_IMPORTED_MODULE_31__["StringEditor"],
  table: _table_js__WEBPACK_IMPORTED_MODULE_32__["TableEditor"],
  upload: _upload_js__WEBPACK_IMPORTED_MODULE_33__["UploadEditor"],
  uuid: _uuid_js__WEBPACK_IMPORTED_MODULE_34__["UuidEditor"],
  colorpicker: _colorpicker_js__WEBPACK_IMPORTED_MODULE_35__["ColorEditor"]
};

/***/ }),

/***/ "./src/editors/info.js":
/*!*****************************!*\
  !*** ./src/editors/info.js ***!
  \*****************************/
/*! exports provided: InfoEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "InfoEditor", function() { return InfoEditor; });
/* harmony import */ var _button_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./button.js */ "./src/editors/button.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/* Non-Active editor for displaying text blocks in form */

var InfoEditor = /*#__PURE__*/function (_ButtonEditor) {
  _inherits(InfoEditor, _ButtonEditor);

  var _super = _createSuper(InfoEditor);

  function InfoEditor() {
    _classCallCheck(this, InfoEditor);

    return _super.apply(this, arguments);
  }

  _createClass(InfoEditor, [{
    key: "build",
    value: function build() {
      this.options.compact = false;
      this.header = this.label = this.theme.getFormInputLabel(this.getTitle());
      this.description = this.theme.getDescription(this.schema.description || '');
      this.control = this.theme.getFormControl(this.label, this.description, null);
      this.container.appendChild(this.control);
    }
  }, {
    key: "getTitle",
    value: function getTitle() {
      return this.schema.title;
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 12;
    }
  }]);

  return InfoEditor;
}(_button_js__WEBPACK_IMPORTED_MODULE_0__["ButtonEditor"]);

/***/ }),

/***/ "./src/editors/integer.js":
/*!********************************!*\
  !*** ./src/editors/integer.js ***!
  \********************************/
/*! exports provided: IntegerEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "IntegerEditor", function() { return IntegerEditor; });
/* harmony import */ var _number_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./number.js */ "./src/editors/number.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var IntegerEditor = /*#__PURE__*/function (_NumberEditor) {
  _inherits(IntegerEditor, _NumberEditor);

  var _super = _createSuper(IntegerEditor);

  function IntegerEditor() {
    _classCallCheck(this, IntegerEditor);

    return _super.apply(this, arguments);
  }

  _createClass(IntegerEditor, [{
    key: "getNumColumns",
    value: function getNumColumns() {
      return 2;
    }
  }, {
    key: "getValue",
    value: function getValue() {
      if (!this.dependenciesFulfilled) {
        return undefined;
      }

      return Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["isInteger"])(this.value) ? parseInt(this.value) : this.value;
    }
  }]);

  return IntegerEditor;
}(_number_js__WEBPACK_IMPORTED_MODULE_0__["NumberEditor"]);

/***/ }),

/***/ "./src/editors/ip.js":
/*!***************************!*\
  !*** ./src/editors/ip.js ***!
  \***************************/
/*! exports provided: IpEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "IpEditor", function() { return IpEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var IpEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(IpEditor, _StringEditor);

  var _super = _createSuper(IpEditor);

  function IpEditor() {
    _classCallCheck(this, IpEditor);

    return _super.apply(this, arguments);
  }

  _createClass(IpEditor, [{
    key: "preBuild",
    value: function preBuild() {
      _get(_getPrototypeOf(IpEditor.prototype), "preBuild", this).call(this);
      /* Create schema options object if deesn't exist */


      if (!this.schema.options) {
        this.schema.options = {};
      }
      /* Create cleave options if they don't exist */


      if (!this.schema.options.cleave) {
        switch (this.format) {
          case 'ipv6':
            this.schema.options.cleave = {
              delimiters: [':'],
              blocks: [4, 4, 4, 4, 4, 4, 4, 4],
              uppercase: true
            };
            break;

          case 'ipv4':
            this.schema.options.cleave = {
              delimiters: ['.'],
              blocks: [3, 3, 3, 3],
              numericOnly: true
            };
            break;
        }
      }
      /* Update options object */


      this.options = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])(this.options, this.schema.options || {});
    }
  }]);

  return IpEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/jodit.js":
/*!******************************!*\
  !*** ./src/editors/jodit.js ***!
  \******************************/
/*! exports provided: JoditEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "JoditEditor", function() { return JoditEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var JoditEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(JoditEditor, _StringEditor);

  var _super = _createSuper(JoditEditor);

  function JoditEditor() {
    _classCallCheck(this, JoditEditor);

    return _super.apply(this, arguments);
  }

  _createClass(JoditEditor, [{
    key: "setValue",
    value: function setValue(value, initial, fromTemplate) {
      var res = _get(_getPrototypeOf(JoditEditor.prototype), "setValue", this).call(this, value, initial, fromTemplate);

      if (res !== undefined && res.changed && this.jodit_instance) this.jodit_instance.setEditorValue(res.value);
    }
  }, {
    key: "build",
    value: function build() {
      this.options.format = 'textarea';
      /* Force format into "textarea" */

      _get(_getPrototypeOf(JoditEditor.prototype), "build", this).call(this);

      this.input_type = this.schema.format;
      /* Restore original format */

      this.input.setAttribute('data-schemaformat', this.input_type);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      var options;

      if (window.Jodit) {
        /* Get options, either global options from "this.defaults.options.jodit" or */

        /* single property options from schema "options.jodit" */
        options = this.expandCallbacks('jodit', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
          height: 300
        }, this.defaults.options.jodit || {}, this.options.jodit || {}));
        this.jodit_instance = new window.Jodit(this.input, options);

        if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
          this.jodit_instance.setReadOnly(true);
        }

        this.jodit_instance.events.on('change', function () {
          _this.value = _this.jodit_instance.getEditorValue();
          _this.is_dirty = true;

          _this.onChange(true);
        });
        this.theme.afterInputReady(this.input);
      } else _get(_getPrototypeOf(JoditEditor.prototype), "afterInputReady", this).call(this);
      /* Library not loaded, so just treat this as a string */

    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 6;
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled && this.jodit_instance) this.jodit_instance.setReadOnly(false);

      _get(_getPrototypeOf(JoditEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.jodit_instance) this.jodit_instance.setReadOnly(true);

      _get(_getPrototypeOf(JoditEditor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.jodit_instance) {
        this.jodit_instance.destruct();
        this.jodit_instance = null;
      }

      _get(_getPrototypeOf(JoditEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return JoditEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/multiple.js":
/*!*********************************!*\
  !*** ./src/editors/multiple.js ***!
  \*********************************/
/*! exports provided: MultipleEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "MultipleEditor", function() { return MultipleEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
/* harmony import */ var _validator_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../validator.js */ "./src/validator.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/* Multiple Editor (for when `type` is an array, also when `oneOf` is present) */



var MultipleEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(MultipleEditor, _AbstractEditor);

  var _super = _createSuper(MultipleEditor);

  function MultipleEditor() {
    _classCallCheck(this, MultipleEditor);

    return _super.apply(this, arguments);
  }

  _createClass(MultipleEditor, [{
    key: "register",
    value: function register() {
      if (this.editors) {
        for (var i = 0; i < this.editors.length; i++) {
          if (!this.editors[i]) continue;
          this.editors[i].unregister();
        }

        if (this.editors[this.type]) this.editors[this.type].register();
      }

      _get(_getPrototypeOf(MultipleEditor.prototype), "register", this).call(this);
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(MultipleEditor.prototype), "unregister", this).call(this);

      if (this.editors) {
        for (var i = 0; i < this.editors.length; i++) {
          if (!this.editors[i]) continue;
          this.editors[i].unregister();
        }
      }
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      if (!this.editors[this.type]) return 4;
      return Math.max(this.editors[this.type].getNumColumns(), 4);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        if (this.editors) {
          for (var i = 0; i < this.editors.length; i++) {
            if (!this.editors[i]) continue;
            this.editors[i].enable();
          }
        }

        this.switcher.disabled = false;

        _get(_getPrototypeOf(MultipleEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;

      if (this.editors) {
        for (var i = 0; i < this.editors.length; i++) {
          if (!this.editors[i]) continue;
          this.editors[i].disable(alwaysDisabled);
        }
      }

      this.switcher.disabled = true;

      _get(_getPrototypeOf(MultipleEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "switchEditor",
    value: function switchEditor(i) {
      var _this = this;

      if (!this.editors[i]) {
        this.buildChildEditor(i);
      }

      var currentValue = this.getValue();
      this.type = i;
      this.register();
      this.editors.forEach(function (editor, type) {
        if (!editor) return;

        if (_this.type === type) {
          if (_this.keep_values) editor.setValue(currentValue, true);
          editor.container.style.display = '';
        } else editor.container.style.display = 'none';
      });
      this.refreshValue();
      this.refreshHeaderText();
    }
  }, {
    key: "buildChildEditor",
    value: function buildChildEditor(i) {
      var _this2 = this;

      var type = this.types[i];
      var holder = this.theme.getChildEditorHolder();
      this.editor_holder.appendChild(holder);
      var schema;

      if (typeof type === 'string') {
        schema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_2__["extend"])({}, this.schema);
        schema.type = type;
      } else {
        schema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_2__["extend"])({}, this.schema, type);
        schema = this.jsoneditor.expandRefs(schema);
        /* If we need to merge `required` arrays */

        if (type && type.required && Array.isArray(type.required) && this.schema.required && Array.isArray(this.schema.required)) {
          schema.required = this.schema.required.concat(type.required);
        }
      }

      var editor = this.jsoneditor.getEditorClass(schema);
      this.editors[i] = this.jsoneditor.createEditor(editor, {
        jsoneditor: this.jsoneditor,
        schema: schema,
        container: holder,
        path: this.path,
        parent: this,
        required: true
      });
      this.editors[i].preBuild();
      this.editors[i].build();
      this.editors[i].postBuild();
      if (this.editors[i].header) this.editors[i].header.style.display = 'none';
      this.editors[i].option = this.switcher_options[i];
      holder.addEventListener('change_header_text', function () {
        _this2.refreshHeaderText();
      });
      if (i !== this.type) holder.style.display = 'none';
    }
  }, {
    key: "preBuild",
    value: function preBuild() {
      this.types = [];
      this.type = 0;
      this.editors = [];
      this.validators = [];
      this.keep_values = true;
      if (typeof this.jsoneditor.options.keep_oneof_values !== 'undefined') this.keep_values = this.jsoneditor.options.keep_oneof_values;
      if (typeof this.options.keep_oneof_values !== 'undefined') this.keep_values = this.options.keep_oneof_values;

      if (this.schema.oneOf) {
        this.oneOf = true;
        this.types = this.schema.oneOf;
        delete this.schema.oneOf;
      } else if (this.schema.anyOf) {
        this.anyOf = true;
        this.types = this.schema.anyOf;
        delete this.schema.anyOf;
      } else {
        if (!this.schema.type || this.schema.type === 'any') {
          this.types = ['string', 'number', 'integer', 'boolean', 'object', 'array', 'null'];
          /* If any of these primitive types are disallowed */

          if (this.schema.disallow) {
            var disallow = this.schema.disallow;

            if (_typeof(disallow) !== 'object' || !Array.isArray(disallow)) {
              disallow = [disallow];
            }

            var allowedTypes = [];
            this.types.forEach(function (type) {
              if (!disallow.includes(type)) allowedTypes.push(type);
            });
            this.types = allowedTypes;
          }
        } else if (Array.isArray(this.schema.type)) {
          this.types = this.schema.type;
        } else {
          this.types = [this.schema.type];
        }

        delete this.schema.type;
      }

      this.display_text = this.getDisplayText(this.types);
    }
  }, {
    key: "build",
    value: function build() {
      var _this3 = this;

      var container = this.container;
      this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      this.container.appendChild(this.header);
      this.switcher = this.theme.getSwitcher(this.display_text);
      container.appendChild(this.switcher);
      this.switcher.addEventListener('change', function (e) {
        e.preventDefault();
        e.stopPropagation();

        _this3.switchEditor(_this3.display_text.indexOf(e.currentTarget.value));

        _this3.onChange(true);
      });
      this.editor_holder = document.createElement('div');
      container.appendChild(this.editor_holder);
      var validatorOptions = {};

      if (this.jsoneditor.options.custom_validators) {
        validatorOptions.custom_validators = this.jsoneditor.options.custom_validators;
      }

      this.switcher_options = this.theme.getSwitcherOptions(this.switcher);
      this.types.forEach(function (type, i) {
        _this3.editors[i] = false;
        var schema;

        if (typeof type === 'string') {
          schema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_2__["extend"])({}, _this3.schema);
          schema.type = type;
        } else {
          schema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_2__["extend"])({}, _this3.schema, type);
          /* If we need to merge `required` arrays */

          if (type.required && Array.isArray(type.required) && _this3.schema.required && Array.isArray(_this3.schema.required)) {
            schema.required = _this3.schema.required.concat(type.required);
          }
        }

        _this3.validators[i] = new _validator_js__WEBPACK_IMPORTED_MODULE_1__["Validator"](_this3.jsoneditor, schema, validatorOptions, _this3.defaults);
      });
      this.switchEditor(0);
    }
  }, {
    key: "onChildEditorChange",
    value: function onChildEditorChange(editor) {
      if (this.editors[this.type]) {
        this.refreshValue();
        this.refreshHeaderText();
      }

      _get(_getPrototypeOf(MultipleEditor.prototype), "onChildEditorChange", this).call(this);
    }
  }, {
    key: "refreshHeaderText",
    value: function refreshHeaderText() {
      var displayText = this.getDisplayText(this.types);
      Array.from(this.switcher_options).forEach(function (option, i) {
        option.textContent = displayText[i];
      });
    }
  }, {
    key: "refreshValue",
    value: function refreshValue() {
      this.value = this.editors[this.type].getValue();
    }
  }, {
    key: "setValue",
    value: function setValue(val, initial) {
      var _this4 = this;

      /* Determine type by getting the first one that validates */
      var prevType = this.type;
      /* find the best match one */

      var fitTestVal = {
        match: 0,
        extra: 0,
        i: this.type
      };
      var validVal = {
        match: 0,
        i: null
      };
      this.validators.forEach(function (validator, i) {
        var fitTestResult = null;

        if (typeof _this4.anyOf !== 'undefined' && _this4.anyOf) {
          fitTestResult = validator.fitTest(val);

          if (fitTestVal.match < fitTestResult.match) {
            fitTestVal = fitTestResult;
            fitTestVal.i = i;
          } else if (fitTestVal.match === fitTestResult.match) {
            if (fitTestVal.extra > fitTestResult.extra) {
              fitTestVal = fitTestResult;
              fitTestVal.i = i;
            }
          }
        }

        if (!validator.validate(val).length && validVal.i === null) {
          validVal.i = i;

          if (fitTestResult !== null) {
            validVal.match = fitTestResult.match;
          }
        }
      });
      var finalI = validVal.i;
      /* if the best fit schema has more match properties, then use the best fit schema. */

      /* usually the value could be */

      if (typeof this.anyOf !== 'undefined' && this.anyOf) {
        if (validVal.match < fitTestVal.match) {
          finalI = fitTestVal.i;
        }
      }

      if (finalI === null) {
        finalI = this.type;
      }

      this.type = finalI;
      this.switcher.value = this.display_text[finalI];
      var typeChanged = this.type !== prevType;

      if (typeChanged) {
        this.switchEditor(this.type);
      }

      this.editors[this.type].setValue(val, initial);
      this.refreshValue();
      this.onChange(typeChanged);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this.editors.forEach(function (editor) {
        if (editor) editor.destroy();
      });
      if (this.editor_holder && this.editor_holder.parentNode) this.editor_holder.parentNode.removeChild(this.editor_holder);
      if (this.switcher && this.switcher.parentNode) this.switcher.parentNode.removeChild(this.switcher);

      _get(_getPrototypeOf(MultipleEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "showValidationErrors",
    value: function showValidationErrors(errors) {
      var _this5 = this;

      /* oneOf and anyOf error paths need to remove the oneOf[i] part before passing to child editors */
      if (this.oneOf || this.anyOf) {
        var checkPart = this.oneOf ? 'oneOf' : 'anyOf';
        this.editors.forEach(function (editor, i) {
          if (!editor) return;
          var check = "".concat(_this5.path, ".").concat(checkPart, "[").concat(i, "]");

          var filterError = function filterError(newErrors, error) {
            if (error.path === check.substr(0, error.path.length)) {
              var newError = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_2__["extend"])({}, error);
              newError.path = _this5.path + newError.path.substr(check.length);
              newErrors.push(newError);
            }

            return newErrors;
          };

          editor.showValidationErrors(errors.reduce(filterError, []));
        });
      } else {
        this.editors.forEach(function (editor) {
          if (!editor) return;
          editor.showValidationErrors(errors);
        });
      }
    }
  }, {
    key: "addLinks",
    value: function addLinks() {// multiple editor itself don't create links
    }
  }]);

  return MultipleEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/multiselect.js":
/*!************************************!*\
  !*** ./src/editors/multiselect.js ***!
  \************************************/
/*! exports provided: MultiSelectEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "MultiSelectEditor", function() { return MultiSelectEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var MultiSelectEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(MultiSelectEditor, _AbstractEditor);

  var _super = _createSuper(MultiSelectEditor);

  function MultiSelectEditor() {
    _classCallCheck(this, MultiSelectEditor);

    return _super.apply(this, arguments);
  }

  _createClass(MultiSelectEditor, [{
    key: "onInputChange",
    value: function onInputChange() {
      this.value = this.input.value;
      this.onChange(true);
    }
  }, {
    key: "register",
    value: function register() {
      _get(_getPrototypeOf(MultiSelectEditor.prototype), "register", this).call(this);

      if (!this.input) return;
      this.input.setAttribute('name', this.formname);
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(MultiSelectEditor.prototype), "unregister", this).call(this);

      if (!this.input) return;
      this.input.removeAttribute('name');
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      var _this = this;

      var longestText = this.getTitle().length;
      Object.keys(this.select_values).forEach(function (i) {
        return longestText = Math.max(longestText, "".concat(_this.select_values[i]).length + 4);
      });
      return Math.min(12, Math.max(longestText / 7, 2));
    }
  }, {
    key: "preBuild",
    value: function preBuild() {
      _get(_getPrototypeOf(MultiSelectEditor.prototype), "preBuild", this).call(this);

      this.select_options = {};
      this.select_values = {};
      this.option_keys = [];
      this.option_titles = [];
      var i;
      var itemsSchema = this.jsoneditor.expandRefs(this.schema.items || {});
      var e = itemsSchema["enum"] || [];
      var t = itemsSchema.options ? itemsSchema.options.enum_titles || [] : [];

      for (i = 0; i < e.length; i++) {
        /* If the sanitized value is different from the enum value, don't include it */
        if (this.sanitize(e[i]) !== e[i]) continue;
        this.option_keys.push("".concat(e[i]));
        this.option_titles.push("".concat(t[i] || e[i]));
        this.select_values["".concat(e[i])] = e[i];
      }
    }
  }, {
    key: "build",
    value: function build() {
      var _this2 = this;

      var i;
      if (!this.options.compact) this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      if (this.schema.description) this.description = this.theme.getFormInputDescription(this.schema.description);
      if (this.options.infoText) this.infoButton = this.theme.getInfoButton(this.options.infoText);
      if (this.options.compact) this.container.classList.add('compact');

      if (!this.schema.format && this.option_keys.length < 8 || this.schema.format === 'checkbox') {
        this.input_type = 'checkboxes';
        this.inputs = {};
        this.controls = {};

        for (i = 0; i < this.option_keys.length; i++) {
          var id = this.formname + i.toString();
          this.inputs[this.option_keys[i]] = this.theme.getCheckbox();
          this.inputs[this.option_keys[i]].id = id;
          this.select_options[this.option_keys[i]] = this.inputs[this.option_keys[i]];
          var label = this.theme.getCheckboxLabel(this.option_titles[i]);
          label.htmlFor = id;
          this.controls[this.option_keys[i]] = this.theme.getFormControl(label, this.inputs[this.option_keys[i]]);
        }

        this.control = this.theme.getMultiCheckboxHolder(this.controls, this.label, this.description, this.infoButton);
        this.inputs.controlgroup = this.inputs.controls = this.control;
        /* Enable error messages for checkboxes */
      } else {
        this.input_type = 'select';
        this.input = this.theme.getSelectInput(this.option_keys, true);
        this.theme.setSelectOptions(this.input, this.option_keys, this.option_titles);
        /* this.input.multiple = true; */

        this.input.setAttribute('multiple', 'multiple');
        this.input.size = Math.min(10, this.option_keys.length);

        for (i = 0; i < this.option_keys.length; i++) {
          this.select_options[this.option_keys[i]] = this.input.children[i];
        }

        this.control = this.theme.getFormControl(this.label, this.input, this.description, this.infoButton);
      }

      if (this.schema.readOnly || this.schema.readonly) this.disable(true);
      this.container.appendChild(this.control);

      this.multiselectChangeHandler = function (e) {
        var newValue = [];

        for (i = 0; i < _this2.option_keys.length; i++) {
          if (_this2.select_options[_this2.option_keys[i]] && (_this2.select_options[_this2.option_keys[i]].selected || _this2.select_options[_this2.option_keys[i]].checked)) newValue.push(_this2.select_values[_this2.option_keys[i]]);
        }

        _this2.updateValue(newValue);

        _this2.onChange(true);
      };

      this.control.addEventListener('change', this.multiselectChangeHandler, false);
      /* Any special formatting that needs to happen after the input is added to the dom */

      window.requestAnimationFrame(function () {
        _this2.afterInputReady();
      });
    }
  }, {
    key: "postBuild",
    value: function postBuild() {
      _get(_getPrototypeOf(MultiSelectEditor.prototype), "postBuild", this).call(this);
      /* this.theme.afterInputReady(this.input || this.inputs); */

    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      this.theme.afterInputReady(this.input || this.inputs);
    }
  }, {
    key: "setValue",
    value: function setValue(value, initial) {
      var _this3 = this;

      value = value || [];
      if (!Array.isArray(value)) value = [value];
      /* Make sure we are dealing with an array of strings so we can check for strict equality */

      value = value.map(function (e) {
        return "".concat(e);
      });
      /* Update selected status of options */

      Object.keys(this.select_options).forEach(function (i) {
        _this3.select_options[i][_this3.input_type === 'select' ? 'selected' : 'checked'] = value.includes(i);
      });
      this.updateValue(value);
      this.onChange(true);
    }
  }, {
    key: "removeValue",
    value: function removeValue(value) {
      /* Remove from existing value(s) */
      value = [].concat(value);
      this.setValue(this.getValue().filter(function (item) {
        return !value.includes(item);
      }));
    }
  }, {
    key: "addValue",
    value: function addValue(value) {
      /* Add to existing value(s) */
      this.setValue(this.getValue().concat(value));
    }
  }, {
    key: "updateValue",
    value: function updateValue(value) {
      var changed = false;
      var newValue = [];

      for (var i = 0; i < value.length; i++) {
        if (!this.select_options["".concat(value[i])]) {
          changed = true;
          continue;
        }

        var sanitized = this.sanitize(this.select_values[value[i]]);
        newValue.push(sanitized);
        if (sanitized !== value[i]) changed = true;
      }

      this.value = newValue;
      return changed;
    }
  }, {
    key: "sanitize",
    value: function sanitize(value) {
      if (this.schema.items.type === 'boolean') return !!value;else if (this.schema.items.type === 'number') return 1 * value || 0;else if (this.schema.items.type === 'integer') return Math.floor(value * 1 || 0);
      return "".concat(value);
    }
  }, {
    key: "enable",
    value: function enable() {
      var _this4 = this;

      if (!this.always_disabled) {
        if (this.input) {
          this.input.disabled = false;
        } else if (this.inputs) {
          Object.keys(this.inputs).forEach(function (i) {
            return _this4.inputs[i].disabled = false;
          });
        }

        _get(_getPrototypeOf(MultiSelectEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      var _this5 = this;

      if (alwaysDisabled) this.always_disabled = true;

      if (this.input) {
        this.input.disabled = true;
      } else if (this.inputs) {
        Object.keys(this.inputs).forEach(function (i) {
          return _this5.inputs[i].disabled = true;
        });
      }

      _get(_getPrototypeOf(MultiSelectEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      _get(_getPrototypeOf(MultiSelectEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "escapeRegExp",
    value: function escapeRegExp(string) {
      return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
  }, {
    key: "showValidationErrors",
    value: function showValidationErrors(errors) {
      var regexPath = new RegExp("^".concat(this.escapeRegExp(this.path), "(\\.\\d+)?$"));

      var addMessage = function addMessage(messages, error) {
        if (error.path.match(regexPath)) {
          messages.push(error.message);
        }

        return messages;
      };

      var messages = errors.reduce(addMessage, []);

      if (messages.length) {
        this.theme.addInputError(this.input || this.inputs, "".concat(messages.join('. '), "."));
      } else {
        this.theme.removeInputError(this.input || this.inputs);
      }
    }
  }]);

  return MultiSelectEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/null.js":
/*!*****************************!*\
  !*** ./src/editors/null.js ***!
  \*****************************/
/*! exports provided: NullEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "NullEditor", function() { return NullEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var NullEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(NullEditor, _AbstractEditor);

  var _super = _createSuper(NullEditor);

  function NullEditor() {
    _classCallCheck(this, NullEditor);

    return _super.apply(this, arguments);
  }

  _createClass(NullEditor, [{
    key: "getValue",
    value: function getValue() {
      if (!this.dependenciesFulfilled) {
        return undefined;
      }

      return null;
    }
  }, {
    key: "setValue",
    value: function setValue() {
      this.onChange();
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 2;
    }
  }]);

  return NullEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/number.js":
/*!*******************************!*\
  !*** ./src/editors/number.js ***!
  \*******************************/
/*! exports provided: NumberEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "NumberEditor", function() { return NumberEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _utilities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var NumberEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(NumberEditor, _StringEditor);

  var _super = _createSuper(NumberEditor);

  function NumberEditor() {
    _classCallCheck(this, NumberEditor);

    return _super.apply(this, arguments);
  }

  _createClass(NumberEditor, [{
    key: "build",
    value: function build() {
      _get(_getPrototypeOf(NumberEditor.prototype), "build", this).call(this);

      if (typeof this.schema.minimum !== 'undefined') {
        var minimum = this.schema.minimum;

        if (typeof this.schema.exclusiveMinimum !== 'undefined') {
          minimum += 1;
        }

        this.input.setAttribute('min', minimum);
      }

      if (typeof this.schema.maximum !== 'undefined') {
        var maximum = this.schema.maximum;

        if (typeof this.schema.exclusiveMaximum !== 'undefined') {
          maximum -= 1;
        }

        this.input.setAttribute('max', maximum);
      }

      if (typeof this.schema.step !== 'undefined') {
        var step = this.schema.step || 1;
        this.input.setAttribute('step', step);
      }
      /* Set custom attributes on input element. Parameter is array of protected keys. Empty array if none. */


      this.setInputAttributes(['maxlength', 'pattern', 'readonly', 'min', 'max', 'step']);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 2;
    }
  }, {
    key: "getValue",
    value: function getValue() {
      if (!this.dependenciesFulfilled) {
        return undefined;
      }

      return Object(_utilities__WEBPACK_IMPORTED_MODULE_1__["isNumber"])(this.value) ? parseFloat(this.value) : this.value;
    }
  }]);

  return NumberEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/object.js":
/*!*******************************!*\
  !*** ./src/editors/object.js ***!
  \*******************************/
/*! exports provided: ObjectEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ObjectEditor", function() { return ObjectEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var ObjectEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(ObjectEditor, _AbstractEditor);

  var _super = _createSuper(ObjectEditor);

  function ObjectEditor(options, defaults, depth) {
    var _this;

    _classCallCheck(this, ObjectEditor);

    _this = _super.call(this, options, defaults);
    _this.currentDepth = depth;
    return _this;
  }

  _createClass(ObjectEditor, [{
    key: "getDefault",
    value: function getDefault() {
      return Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.schema["default"] || {});
    }
  }, {
    key: "getChildEditors",
    value: function getChildEditors() {
      return this.editors;
    }
  }, {
    key: "register",
    value: function register() {
      _get(_getPrototypeOf(ObjectEditor.prototype), "register", this).call(this);

      if (this.editors) {
        Object.values(this.editors).forEach(function (e) {
          return e.register();
        });
      }
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(ObjectEditor.prototype), "unregister", this).call(this);

      if (this.editors) {
        Object.values(this.editors).forEach(function (e) {
          return e.unregister();
        });
      }
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return Math.max(Math.min(12, this.maxwidth), 3);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        if (this.editjson_control) this.editjson_control.disabled = false;
        if (this.addproperty_button) this.addproperty_button.disabled = false;

        _get(_getPrototypeOf(ObjectEditor.prototype), "enable", this).call(this);

        if (this.editors) {
          Object.values(this.editors).forEach(function (e) {
            if (e.isActive()) {
              e.enable();
            }

            e.optInCheckbox.disabled = false;
          });
        }
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;
      if (this.editjson_control) this.editjson_control.disabled = true;
      if (this.addproperty_button) this.addproperty_button.disabled = true;
      this.hideEditJSON();

      _get(_getPrototypeOf(ObjectEditor.prototype), "disable", this).call(this);

      if (this.editors) {
        Object.values(this.editors).forEach(function (e) {
          if (e.isActive()) {
            e.disable(alwaysDisabled);
          }

          e.optInCheckbox.disabled = true;
        });
      }
    }
  }, {
    key: "layoutEditors",
    value: function layoutEditors() {
      var _this2 = this;

      var i;
      var j;
      if (!this.row_container) return;
      /* Sort editors by propertyOrder */

      this.property_order = Object.keys(this.editors);
      this.property_order = this.property_order.sort(function (a, b) {
        var ordera = _this2.editors[a].schema.propertyOrder;
        var orderb = _this2.editors[b].schema.propertyOrder;
        if (typeof ordera !== 'number') ordera = 1000;
        if (typeof orderb !== 'number') orderb = 1000;
        return ordera - orderb;
      });
      var container;
      var isCategoriesFormat = this.format === 'categories';
      var rows = [];
      var key = null;
      var editor = null;
      var row;

      if (this.format === 'grid-strict') {
        var rowIndex = 0;
        row = [];
        this.property_order.forEach(function (key) {
          var editor = _this2.editors[key];

          if (editor.property_removed) {
            return;
          }

          var width = editor.options.hidden ? 0 : editor.options.grid_columns || editor.getNumColumns();
          var offset = editor.options.hidden ? 0 : editor.options.grid_offset || 0;
          var gridBreak = editor.options.hidden ? false : editor.options.grid_break || false;
          var height = editor.options.hidden ? 0 : editor.container.offsetHeight;
          var column = {
            key: key,
            width: width,
            offset: offset,
            height: height
          };
          row.push(column);
          rows[rowIndex] = row;

          if (gridBreak) {
            rowIndex++;
            row = [];
          }
        });
        /* layout hasn't changed */

        if (this.layout === JSON.stringify(rows)) return false;
        this.layout = JSON.stringify(rows);
        /* Layout the form */

        container = document.createElement('div');

        for (i = 0; i < rows.length; i++) {
          row = this.theme.getGridRow();
          container.appendChild(row);

          for (j = 0; j < rows[i].length; j++) {
            key = rows[i][j].key;
            editor = this.editors[key];

            if (editor.options.hidden) {
              editor.container.style.display = 'none';
            } else {
              this.theme.setGridColumnSize(editor.container, rows[i][j].width, rows[i][j].offset);
            }

            row.appendChild(editor.container);
          }
        }
      } else if (this.format === 'grid') {
        this.property_order.forEach(function (key) {
          var editor = _this2.editors[key];
          if (editor.property_removed) return;
          var found = false;
          var width = editor.options.hidden ? 0 : editor.options.grid_columns || editor.getNumColumns();
          var height = editor.options.hidden ? 0 : editor.container.offsetHeight;
          /* See if the editor will fit in any of the existing rows first */

          for (var _i = 0; _i < rows.length; _i++) {
            /* If the editor will fit in the row horizontally */
            if (rows[_i].width + width <= 12) {
              /* If the editor is close to the other elements in height */

              /* i.e. Don't put a really tall editor in an otherwise short row or vice versa */
              if (!height || rows[_i].minh * 0.5 < height && rows[_i].maxh * 2 > height) {
                found = _i;
              }
            }
          }
          /* If there isn't a spot in any of the existing rows, start a new row */


          if (found === false) {
            rows.push({
              width: 0,
              minh: 999999,
              maxh: 0,
              editors: []
            });
            found = rows.length - 1;
          }

          rows[found].editors.push({
            key: key,

            /* editor: editor, */
            width: width,
            height: height
          });
          rows[found].width += width;
          rows[found].minh = Math.min(rows[found].minh, height);
          rows[found].maxh = Math.max(rows[found].maxh, height);
        });
        /* Make almost full rows width 12 */

        /* Do this by increasing all editors' sizes proprotionately */

        /* Any left over space goes to the biggest editor */

        /* Don't touch rows with a width of 6 or less */

        for (i = 0; i < rows.length; i++) {
          if (rows[i].width < 12) {
            var biggest = false;
            var newWidth = 0;

            for (j = 0; j < rows[i].editors.length; j++) {
              if (biggest === false) biggest = j;else if (rows[i].editors[j].width > rows[i].editors[biggest].width) biggest = j;
              rows[i].editors[j].width *= 12 / rows[i].width;
              rows[i].editors[j].width = Math.floor(rows[i].editors[j].width);
              newWidth += rows[i].editors[j].width;
            }

            if (newWidth < 12) rows[i].editors[biggest].width += 12 - newWidth;
            rows[i].width = 12;
          }
        }
        /* layout hasn't changed */


        if (this.layout === JSON.stringify(rows)) return false;
        this.layout = JSON.stringify(rows);
        /* Layout the form */

        container = document.createElement('div');

        for (i = 0; i < rows.length; i++) {
          row = this.theme.getGridRow();
          container.appendChild(row);

          for (j = 0; j < rows[i].editors.length; j++) {
            key = rows[i].editors[j].key;
            editor = this.editors[key];
            if (editor.options.hidden) editor.container.style.display = 'none';else this.theme.setGridColumnSize(editor.container, rows[i].editors[j].width);
            row.appendChild(editor.container);
          }
        }
        /* Normal layout */

      } else {
        container = document.createElement('div');

        if (isCategoriesFormat) {
          /* A container for properties not object nor arrays */
          var containerSimple = document.createElement('div');
          /* This will be the place to (re)build tabs and panes */

          /* tabs_holder has 2 childs, [0]: ul.nav.nav-tabs and [1]: div.tab-content */

          var newTabsHolder = this.theme.getTopTabHolder(this.schema.title);
          /* child [1] of previous, stores panes */

          var newTabPanesContainer = this.theme.getTopTabContentHolder(newTabsHolder);
          this.property_order.forEach(function (key) {
            var editor = _this2.editors[key];
            if (editor.property_removed) return;

            var aPane = _this2.theme.getTabContent();

            var isObjOrArray = editor.schema && (editor.schema.type === 'object' || editor.schema.type === 'array');
            /* mark the pane */

            aPane.isObjOrArray = isObjOrArray;

            var gridRow = _this2.theme.getGridRow();
            /* this happens with added properties, they don't have a tab */


            if (!editor.tab) {
              /* Pass the pane which holds the editor */
              if (typeof _this2.basicPane === 'undefined') {
                /* There is no basicPane yet, so aPane will be it */
                _this2.addRow(editor, newTabsHolder, aPane);
              } else {
                _this2.addRow(editor, newTabsHolder, _this2.basicPane);
              }
            }

            aPane.id = _this2.getValidId(editor.tab_text.textContent);
            /* For simple properties, add them on the same panel (Basic) */

            if (!isObjOrArray) {
              containerSimple.appendChild(gridRow);
              /* There are already some panes */

              if (newTabPanesContainer.childElementCount > 0) {
                /* If first pane is object or array, insert before a simple pane */
                if (newTabPanesContainer.firstChild.isObjOrArray) {
                  /* Append pane for simple properties */
                  aPane.appendChild(containerSimple);
                  newTabPanesContainer.insertBefore(aPane, newTabPanesContainer.firstChild);
                  /* Add "Basic" tab */

                  _this2.theme.insertBasicTopTab(editor.tab, newTabsHolder);
                  /* newTabs_holder.firstChild.insertBefore(editor.tab,newTabs_holder.firstChild.firstChild); */

                  /* Update the basicPane */


                  editor.basicPane = aPane;
                } else {}
                /* We already have a first "Basic" pane, just add the new property to it, so */

                /* do nothing; */

                /* There is no pane, so add the first (simple) pane */

              } else {
                /* Append pane for simple properties */
                aPane.appendChild(containerSimple);
                newTabPanesContainer.appendChild(aPane);
                /* Add "Basic" tab */

                /* newTabs_holder.firstChild.appendChild(editor.tab); */

                _this2.theme.addTopTab(newTabsHolder, editor.tab);
                /* Update the basicPane */


                editor.basicPane = aPane;
              }
              /* Objects and arrays earn their own panes */

            } else {
              aPane.appendChild(gridRow);
              newTabPanesContainer.appendChild(aPane);
              /* newTabs_holder.firstChild.appendChild(editor.tab); */

              _this2.theme.addTopTab(newTabsHolder, editor.tab);
            }

            if (editor.options.hidden) editor.container.style.display = 'none';else _this2.theme.setGridColumnSize(editor.container, 12);
            /* Now, add the property editor to the row */

            gridRow.appendChild(editor.container);
            /* Update the rowPane (same as this.rows[x].rowPane) */

            editor.rowPane = aPane;
          });
          /* Erase old panes */

          while (this.tabPanesContainer.firstChild) {
            this.tabPanesContainer.removeChild(this.tabPanesContainer.firstChild);
          }
          /* Erase old tabs and set the new ones */


          var parentTabsHolder = this.tabs_holder.parentNode;
          parentTabsHolder.removeChild(parentTabsHolder.firstChild);
          parentTabsHolder.appendChild(newTabsHolder);
          this.tabPanesContainer = newTabPanesContainer;
          this.tabs_holder = newTabsHolder;
          /* Activate the first tab */

          var firstTab = this.theme.getFirstTab(this.tabs_holder);

          if (firstTab) {
            Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["trigger"])(firstTab, 'click');
          }

          return;
          /* Normal layout */
        }

        this.property_order.forEach(function (key) {
          var editor = _this2.editors[key];
          if (editor.property_removed) return;
          row = _this2.theme.getGridRow();
          container.appendChild(row);
          if (editor.options.hidden) editor.container.style.display = 'none';else _this2.theme.setGridColumnSize(editor.container, 12);
          row.appendChild(editor.container);
        });
      }
      /* for grid and normal layout */


      while (this.row_container.firstChild) {
        this.row_container.removeChild(this.row_container.firstChild);
      }

      this.row_container.appendChild(container);
    }
  }, {
    key: "getPropertySchema",
    value: function getPropertySchema(key) {
      var _this3 = this;

      /* Schema declared directly in properties */
      var schema = this.schema.properties[key] || {};
      schema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, schema);
      var matched = !!this.schema.properties[key];
      /* Any matching patternProperties should be merged in */

      if (this.schema.patternProperties) {
        Object.keys(this.schema.patternProperties).forEach(function (i) {
          var regex = new RegExp(i);

          if (regex.test(key)) {
            schema.allOf = schema.allOf || [];
            schema.allOf.push(_this3.schema.patternProperties[i]);
            matched = true;
          }
        });
      }
      /* Hasn't matched other rules, use additionalProperties schema */


      if (!matched && this.schema.additionalProperties && _typeof(this.schema.additionalProperties) === 'object') {
        schema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.schema.additionalProperties);
      }

      return schema;
    }
  }, {
    key: "preBuild",
    value: function preBuild() {
      var _this4 = this;

      _get(_getPrototypeOf(ObjectEditor.prototype), "preBuild", this).call(this);

      this.editors = {};
      this.cached_editors = {};
      this.format = this.options.layout || this.options.object_layout || this.schema.format || this.jsoneditor.options.object_layout || 'normal';
      this.schema.properties = this.schema.properties || {};
      this.minwidth = 0;
      this.maxwidth = 0;
      /* If the object should be rendered as a table row */

      if (this.options.table_row) {
        Object.entries(this.schema.properties).forEach(function (_ref) {
          var _ref2 = _slicedToArray(_ref, 2),
              key = _ref2[0],
              schema = _ref2[1];

          var editor = _this4.jsoneditor.getEditorClass(schema);

          _this4.editors[key] = _this4.jsoneditor.createEditor(editor, {
            jsoneditor: _this4.jsoneditor,
            schema: schema,
            path: "".concat(_this4.path, ".").concat(key),
            parent: _this4,
            compact: true,
            required: true
          }, _this4.currentDepth + 1);

          _this4.editors[key].preBuild();

          var width = _this4.editors[key].options.hidden ? 0 : _this4.editors[key].options.grid_columns || _this4.editors[key].getNumColumns();
          _this4.minwidth += width;
          _this4.maxwidth += width;
        });
        this.no_link_holder = true;
        /* If the object should be rendered as a table */
      } else if (this.options.table) {
        /* TODO: table display format */
        throw new Error('Not supported yet');
        /* If the object should be rendered as a div */
      } else {
        if (!this.schema.defaultProperties) {
          if (this.jsoneditor.options.display_required_only || this.options.display_required_only) {
            this.schema.defaultProperties = Object.keys(this.schema.properties).filter(function (k) {
              return _this4.isRequiredObject({
                key: k,
                schema: _this4.schema.properties[k]
              });
            });
          } else {
            this.schema.defaultProperties = Object.keys(this.schema.properties);
          }
        }
        /* Increase the grid width to account for padding */


        this.maxwidth += 1;
        this.schema.defaultProperties.forEach(function (key) {
          _this4.addObjectProperty(key, true);

          if (_this4.editors[key]) {
            _this4.minwidth = Math.max(_this4.minwidth, _this4.editors[key].options.grid_columns || _this4.editors[key].getNumColumns());
            _this4.maxwidth += _this4.editors[key].options.grid_columns || _this4.editors[key].getNumColumns();
          }
        });
      }
      /* Sort editors by propertyOrder */


      this.property_order = Object.keys(this.editors);
      this.property_order = this.property_order.sort(function (a, b) {
        var ordera = _this4.editors[a].schema.propertyOrder;
        var orderb = _this4.editors[b].schema.propertyOrder;
        if (typeof ordera !== 'number') ordera = 1000;
        if (typeof orderb !== 'number') orderb = 1000;
        return ordera - orderb;
      });
    }
    /* "Borrow" from arrays code */

  }, {
    key: "addTab",
    value: function addTab(idx) {
      var _this5 = this;

      var isObjOrArray = this.rows[idx].schema && (this.rows[idx].schema.type === 'object' || this.rows[idx].schema.type === 'array');

      if (this.tabs_holder) {
        this.rows[idx].tab_text = document.createElement('span');

        if (!isObjOrArray) {
          this.rows[idx].tab_text.textContent = typeof this.schema.basicCategoryTitle === 'undefined' ? 'Basic' : this.schema.basicCategoryTitle;
        } else {
          this.rows[idx].tab_text.textContent = this.rows[idx].getHeaderText();
        }

        this.rows[idx].tab = this.theme.getTopTab(this.rows[idx].tab_text, this.getValidId(this.rows[idx].tab_text.textContent));
        this.rows[idx].tab.addEventListener('click', function (e) {
          _this5.active_tab = _this5.rows[idx].tab;

          _this5.refreshTabs();

          e.preventDefault();
          e.stopPropagation();
        });
      }
    }
  }, {
    key: "addRow",
    value: function addRow(editor, tabHolder, aPane) {
      var rowsLen = this.rows.length;
      var isObjOrArray = editor.schema.type === 'object' || editor.schema.type === 'array';
      /* Add a row */

      this.rows[rowsLen] = editor;
      /* rowPane stores the editor corresponding pane to set the display style when refreshing Tabs */

      this.rows[rowsLen].rowPane = aPane;

      if (!isObjOrArray) {
        /* This is the first simple property to be added, */

        /* add a ("Basic") tab for it and save it's row number */
        if (typeof this.basicTab === 'undefined') {
          this.addTab(rowsLen);
          /* Store the index row of the first simple property added */

          this.basicTab = rowsLen;
          this.basicPane = aPane;
          this.theme.addTopTab(tabHolder, this.rows[rowsLen].tab);
        } else {
          /* Any other simple property gets the same tab (and the same pane) as the first one, */

          /* so, when 'click' event is fired from a row, it gets the correct ("Basic") tab */
          this.rows[rowsLen].tab = this.rows[this.basicTab].tab;
          this.rows[rowsLen].tab_text = this.rows[this.basicTab].tab_text;
          this.rows[rowsLen].rowPane = this.rows[this.basicTab].rowPane;
        }
      } else {
        this.addTab(rowsLen);
        this.theme.addTopTab(tabHolder, this.rows[rowsLen].tab);
      }
    }
    /* Mark the active tab and make visible the corresponding pane, hide others */

  }, {
    key: "refreshTabs",
    value: function refreshTabs(refreshHeaders) {
      var _this6 = this;

      var basicTabPresent = typeof this.basicTab !== 'undefined';
      var basicTabRefreshed = false;
      this.rows.forEach(function (row) {
        /* If it's an orphan row (some property which has been deleted), return */
        if (!row.tab || !row.rowPane || !row.rowPane.parentNode) return;
        if (basicTabPresent && row.tab === _this6.rows[_this6.basicTab].tab && basicTabRefreshed) return;

        if (refreshHeaders) {
          row.tab_text.textContent = row.getHeaderText();
        } else {
          /* All rows of simple properties point to the same tab, so refresh just once */
          if (basicTabPresent && row.tab === _this6.rows[_this6.basicTab].tab) basicTabRefreshed = true;

          if (row.tab === _this6.active_tab) {
            _this6.theme.markTabActive(row);
          } else {
            _this6.theme.markTabInactive(row);
          }
        }
      });
    }
  }, {
    key: "build",
    value: function build() {
      var _this7 = this;

      var isCategoriesFormat = this.format === 'categories';
      this.rows = [];
      this.active_tab = null;
      /* If the object should be rendered as a table row */

      if (this.options.table_row) {
        this.editor_holder = this.container;
        Object.entries(this.editors).forEach(function (_ref3) {
          var _ref4 = _slicedToArray(_ref3, 2),
              key = _ref4[0],
              editor = _ref4[1];

          var holder = _this7.theme.getTableCell();

          _this7.editor_holder.appendChild(holder);

          editor.setContainer(holder);
          editor.build();
          editor.postBuild();
          editor.setOptInCheckbox(editor.header);

          if (_this7.editors[key].options.hidden) {
            holder.style.display = 'none';
          }

          if (_this7.editors[key].options.input_width) {
            holder.style.width = _this7.editors[key].options.input_width;
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

          _this7.saveJSON();
        });
        this.editjson_copy = this.getButton('Copy', 'copy', 'Copy');
        this.editjson_copy.classList.add('json-editor-btntype-copy');
        this.editjson_copy.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this7.copyJSON();
        });
        this.editjson_cancel = this.getButton('Cancel', 'cancel', 'Cancel');
        this.editjson_cancel.classList.add('json-editor-btntype-cancel');
        this.editjson_cancel.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this7.hideEditJSON();
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

          if (_this7.addproperty_input.value) {
            if (_this7.editors[_this7.addproperty_input.value]) {
              window.alert('there is already a property with that name');
              return;
            }

            _this7.addObjectProperty(_this7.addproperty_input.value);

            if (_this7.editors[_this7.addproperty_input.value]) {
              _this7.editors[_this7.addproperty_input.value].disable();
            }

            _this7.onChange(true);
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
          var aPane = _this7.theme.getTabContent();

          var holder = _this7.theme.getGridColumn();

          var isObjOrArray = !!(editor.schema && (editor.schema.type === 'object' || editor.schema.type === 'array'));
          aPane.isObjOrArray = isObjOrArray;

          if (isCategoriesFormat) {
            if (isObjOrArray) {
              var singleRowContainer = _this7.theme.getGridContainer();

              singleRowContainer.appendChild(holder);
              aPane.appendChild(singleRowContainer);

              _this7.tabPanesContainer.appendChild(aPane);

              _this7.row_container = singleRowContainer;
            } else {
              if (typeof _this7.row_container_basic === 'undefined') {
                _this7.row_container_basic = _this7.theme.getGridContainer();
                aPane.appendChild(_this7.row_container_basic);

                if (_this7.tabPanesContainer.childElementCount === 0) {
                  _this7.tabPanesContainer.appendChild(aPane);
                } else {
                  _this7.tabPanesContainer.insertBefore(aPane, _this7.tabPanesContainer.childNodes[1]);
                }
              }

              _this7.row_container_basic.appendChild(holder);
            }

            _this7.addRow(editor, _this7.tabs_holder, aPane);

            aPane.id = _this7.getValidId(editor.schema.title);
            /* editor.schema.path//tab_text.textContent */
          } else {
            _this7.row_container.appendChild(holder);
          }

          editor.setContainer(holder);
          editor.build();
          editor.postBuild();
          editor.setOptInCheckbox(editor.header);
        });

        if (this.rows[0]) {
          Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["trigger"])(this.rows[0].tab, 'click');
        }
        /* Show/Hide button */


        this.collapsed = false;
        this.collapse_control = this.getButton('', 'collapse', this.translate('button_collapse'));
        this.collapse_control.style.margin = '0 10px 0 0';
        this.collapse_control.classList.add('json-editor-btntype-toggle');
        this.title.insertBefore(this.collapse_control, this.title.childNodes[0]);
        this.collapse_control.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          if (_this7.collapsed) {
            _this7.editor_holder.style.display = '';
            _this7.collapsed = false;

            _this7.setButtonText(_this7.collapse_control, '', 'collapse', _this7.translate('button_collapse'));
          } else {
            _this7.editor_holder.style.display = 'none';
            _this7.collapsed = true;

            _this7.setButtonText(_this7.collapse_control, '', 'expand', _this7.translate('button_expand'));
          }
        });
        /* If it should start collapsed */

        if (this.options.collapsed) {
          Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["trigger"])(this.collapse_control, 'click');
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

          _this7.toggleEditJSON();
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

          _this7.toggleAddProperty();
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
          _this7.editor_holder.appendChild(_this7.editors[key].container);
        });
        /* Layout object editors in grid if needed */
      } else {
        /* Initial layout */
        this.layoutEditors();
        /* Do it again now that we know the approximate heights of elements */

        this.layoutEditors();
      }
    }
  }, {
    key: "deactivateNonRequiredProperties",
    value: function deactivateNonRequiredProperties() {
      var _this8 = this;

      /* the show_opt_in editor option is for backward compatibility */
      if (this.jsoneditor.options.show_opt_in || this.options.show_opt_in) {
        Object.entries(this.editors).forEach(function (_ref5) {
          var _ref6 = _slicedToArray(_ref5, 2),
              key = _ref6[0],
              editor = _ref6[1];

          if (!_this8.isRequiredObject(editor)) {
            _this8.editors[key].deactivate();
          }
        });
      }
    }
  }, {
    key: "showEditJSON",
    value: function showEditJSON() {
      if (!this.editjson_holder) return;
      this.hideAddProperty();
      /* Position the form directly beneath the button */

      /* TODO: edge detection */

      this.editjson_holder.style.left = "".concat(this.editjson_control.offsetLeft, "px");
      this.editjson_holder.style.top = "".concat(this.editjson_control.offsetTop + this.editjson_control.offsetHeight, "px");
      /* Start the textarea with the current value */

      this.editjson_textarea.value = JSON.stringify(this.getValue(), null, 2);
      /* Disable the rest of the form while editing JSON */

      this.disable();
      this.editjson_holder.style.display = '';
      this.editjson_control.disabled = false;
      this.editing_json = true;
    }
  }, {
    key: "hideEditJSON",
    value: function hideEditJSON() {
      if (!this.editjson_holder) return;
      if (!this.editing_json) return;
      this.editjson_holder.style.display = 'none';
      this.enable();
      this.editing_json = false;
    }
  }, {
    key: "copyJSON",
    value: function copyJSON() {
      if (!this.editjson_holder) return;
      var ta = document.createElement('textarea');
      ta.value = this.editjson_textarea.value;
      ta.setAttribute('readonly', '');
      ta.style.position = 'absolute';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
    }
  }, {
    key: "saveJSON",
    value: function saveJSON() {
      if (!this.editjson_holder) return;

      try {
        var json = JSON.parse(this.editjson_textarea.value);
        this.setValue(json);
        this.hideEditJSON();
        this.onChange(true);
      } catch (e) {
        window.alert('invalid JSON');
        throw e;
      }
    }
  }, {
    key: "toggleEditJSON",
    value: function toggleEditJSON() {
      if (this.editing_json) this.hideEditJSON();else this.showEditJSON();
    }
  }, {
    key: "insertPropertyControlUsingPropertyOrder",
    value: function insertPropertyControlUsingPropertyOrder(property, control, container) {
      var propertyOrder;

      if (this.schema.properties[property]) {
        propertyOrder = this.schema.properties[property].propertyOrder;
      }

      if (typeof propertyOrder !== 'number') propertyOrder = 1000;
      control.propertyOrder = propertyOrder;

      for (var i = 0; i < container.childNodes.length; i++) {
        var child = container.childNodes[i];

        if (control.propertyOrder < child.propertyOrder) {
          this.addproperty_list.insertBefore(control, child);
          control = null;
          break;
        }
      }

      if (control) {
        this.addproperty_list.appendChild(control);
      }
    }
  }, {
    key: "addPropertyCheckbox",
    value: function addPropertyCheckbox(key) {
      var _this9 = this;

      var labelText;
      var checkbox = this.theme.getCheckbox();
      checkbox.style.width = 'auto';

      if (this.schema.properties[key] && this.schema.properties[key].title) {
        labelText = this.schema.properties[key].title;
      } else {
        labelText = key;
      }

      var label = this.theme.getCheckboxLabel(labelText);
      var control = this.theme.getFormControl(label, checkbox);
      control.style.paddingBottom = control.style.marginBottom = control.style.paddingTop = control.style.marginTop = 0;
      control.style.height = 'auto';
      /* control.style.overflowY = 'hidden'; */

      this.insertPropertyControlUsingPropertyOrder(key, control, this.addproperty_list);
      checkbox.checked = key in this.editors;
      checkbox.addEventListener('change', function () {
        if (checkbox.checked) {
          _this9.addObjectProperty(key);
        } else {
          _this9.removeObjectProperty(key);
        }

        _this9.onChange(true);
      });
      this.addproperty_checkboxes[key] = checkbox;
      return checkbox;
    }
  }, {
    key: "showAddProperty",
    value: function showAddProperty() {
      if (!this.addproperty_holder) return;
      this.hideEditJSON();
      /* Position the form directly beneath the button */

      /* TODO: edge detection */

      this.addproperty_holder.style.left = "".concat(this.addproperty_button.offsetLeft, "px");
      this.addproperty_holder.style.top = "".concat(this.addproperty_button.offsetTop + this.addproperty_button.offsetHeight, "px");
      /* Disable the rest of the form while editing JSON */

      this.disable();
      this.adding_property = true;
      this.addproperty_button.disabled = false;
      this.addproperty_holder.style.display = '';
      this.refreshAddProperties();
    }
  }, {
    key: "hideAddProperty",
    value: function hideAddProperty() {
      if (!this.addproperty_holder) return;
      if (!this.adding_property) return;
      this.addproperty_holder.style.display = 'none';
      this.enable();
      this.adding_property = false;
    }
  }, {
    key: "toggleAddProperty",
    value: function toggleAddProperty() {
      if (this.adding_property) this.hideAddProperty();else this.showAddProperty();
    }
  }, {
    key: "removeObjectProperty",
    value: function removeObjectProperty(property) {
      if (this.editors[property]) {
        this.editors[property].unregister();
        delete this.editors[property];
        this.refreshValue();
        this.layoutEditors();
      }
    }
  }, {
    key: "getSchemaOnMaxDepth",
    value: function getSchemaOnMaxDepth(schema) {
      return Object.keys(schema).reduce(function (acc, key) {
        switch (key) {
          case '$ref':
            return acc;

          case 'properties':
          case 'items':
            return _objectSpread(_objectSpread({}, acc), {}, _defineProperty({}, key, {}));

          case 'additionalProperties':
            return _objectSpread(_objectSpread({}, acc), {}, _defineProperty({}, key, true));

          default:
            return _objectSpread(_objectSpread({}, acc), {}, _defineProperty({}, key, schema[key]));
        }
      }, {});
    }
  }, {
    key: "addObjectProperty",
    value: function addObjectProperty(name, prebuildOnly) {
      /* Property is already added */
      if (this.editors[name]) return;
      /* Property was added before and is cached */

      if (this.cached_editors[name]) {
        this.editors[name] = this.cached_editors[name];
        if (prebuildOnly) return;
        this.editors[name].register();
        /* New property */
      } else {
        if (!this.canHaveAdditionalProperties() && (!this.schema.properties || !this.schema.properties[name])) {
          return;
        }

        var schema = this.getPropertySchema(name);

        if (typeof schema.propertyOrder !== 'number') {
          /* if the propertyOrder undefined, then set a smart default value. */
          schema.propertyOrder = Object.keys(this.editors).length + 1000;
        }
        /* Add the property */


        var editor = this.jsoneditor.getEditorClass(schema);
        var maxDepth = this.jsoneditor.options.max_depth;
        this.editors[name] = this.jsoneditor.createEditor(editor, {
          jsoneditor: this.jsoneditor,
          schema: !!maxDepth && this.currentDepth >= maxDepth ? this.getSchemaOnMaxDepth(schema) : schema,
          path: "".concat(this.path, ".").concat(name),
          parent: this
        }, this.currentDepth + 1);
        this.editors[name].preBuild();

        if (!prebuildOnly) {
          var holder = this.theme.getChildEditorHolder();
          this.editor_holder.appendChild(holder);
          this.editors[name].setContainer(holder);
          this.editors[name].build();
          this.editors[name].postBuild();
          this.editors[name].setOptInCheckbox(editor.header);
          this.editors[name].activate();
        }

        this.cached_editors[name] = this.editors[name];
      }
      /* If we're only prebuilding the editors, don't refresh values */


      if (!prebuildOnly) {
        this.refreshValue();
        this.layoutEditors();
      }
    }
  }, {
    key: "onOutsideModalClick",
    value: function onOutsideModalClick(e) {
      if (this.addproperty_holder && !this.addproperty_holder.contains(e.path[0] || e.composedPath()[0]) && this.adding_property) {
        e.preventDefault();
        e.stopPropagation();
        this.toggleAddProperty();
      }
    }
  }, {
    key: "onChildEditorChange",
    value: function onChildEditorChange(editor) {
      this.refreshValue();

      _get(_getPrototypeOf(ObjectEditor.prototype), "onChildEditorChange", this).call(this, editor);
    }
  }, {
    key: "canHaveAdditionalProperties",
    value: function canHaveAdditionalProperties() {
      if (typeof this.schema.additionalProperties === 'boolean') {
        return this.schema.additionalProperties;
      }

      return !this.jsoneditor.options.no_additional_properties;
    }
  }, {
    key: "destroy",
    value: function destroy() {
      Object.values(this.cached_editors).forEach(function (el) {
        return el.destroy();
      });
      if (this.editor_holder) this.editor_holder.innerHTML = '';
      if (this.title && this.title.parentNode) this.title.parentNode.removeChild(this.title);
      if (this.error_holder && this.error_holder.parentNode) this.error_holder.parentNode.removeChild(this.error_holder);
      this.editors = null;
      this.cached_editors = null;
      if (this.editor_holder && this.editor_holder.parentNode) this.editor_holder.parentNode.removeChild(this.editor_holder);
      this.editor_holder = null;
      document.removeEventListener('click', this.onOutsideModalClick);

      _get(_getPrototypeOf(ObjectEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "getValue",
    value: function getValue() {
      if (!this.dependenciesFulfilled) {
        return undefined;
      }

      var result = _get(_getPrototypeOf(ObjectEditor.prototype), "getValue", this).call(this);

      var isEmpty = function isEmpty(obj) {
        return typeof obj === 'undefined' || obj === '' || obj === Object(obj) && Object.keys(obj).length === 0 && obj.constructor === Object;
      };

      if (result && (this.jsoneditor.options.remove_empty_properties || this.options.remove_empty_properties)) {
        Object.keys(result).forEach(function (key) {
          if (isEmpty(result[key])) {
            delete result[key];
          }
        });
      }

      return result;
    }
  }, {
    key: "refreshValue",
    value: function refreshValue() {
      var _this10 = this;

      this.value = {};
      Object.keys(this.editors).forEach(function (i) {
        if (_this10.editors[i].isActive()) {
          _this10.value[i] = _this10.editors[i].getValue();
        }
      });
      if (this.adding_property) this.refreshAddProperties();
    }
  }, {
    key: "refreshAddProperties",
    value: function refreshAddProperties() {
      var _this11 = this;

      if (this.options.disable_properties || this.options.disable_properties !== false && this.jsoneditor.options.disable_properties) {
        this.addproperty_button.style.display = 'none';
        return;
      }

      var canAdd = false;
      var numProps = 0;
      var showModal = false;
      /* Get number of editors */

      Object.keys(this.editors).forEach(function (i) {
        return numProps++;
      });
      /* Determine if we can add back removed properties */

      canAdd = this.canHaveAdditionalProperties() && !(typeof this.schema.maxProperties !== 'undefined' && numProps >= this.schema.maxProperties);

      if (this.addproperty_checkboxes) {
        this.addproperty_list.innerHTML = '';
      }

      this.addproperty_checkboxes = {};
      /* Check for which editors can't be removed or added back */

      Object.keys(this.cached_editors).forEach(function (i) {
        _this11.addPropertyCheckbox(i);

        if (_this11.isRequiredObject(_this11.cached_editors[i]) && i in _this11.editors) {
          _this11.addproperty_checkboxes[i].disabled = true;
        }

        if (typeof _this11.schema.minProperties !== 'undefined' && numProps <= _this11.schema.minProperties) {
          _this11.addproperty_checkboxes[i].disabled = _this11.addproperty_checkboxes[i].checked;
          if (!_this11.addproperty_checkboxes[i].checked) showModal = true;
        } else if (!(i in _this11.editors)) {
          if (!canAdd && !Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["hasOwnProperty"])(_this11.schema.properties, i)) {
            _this11.addproperty_checkboxes[i].disabled = true;
          } else {
            _this11.addproperty_checkboxes[i].disabled = false;
            showModal = true;
          }
        } else {
          showModal = true;
        }
      });

      if (this.canHaveAdditionalProperties()) {
        showModal = true;
      }
      /* Additional addproperty checkboxes not tied to a current editor */


      Object.keys(this.schema.properties).forEach(function (i) {
        if (_this11.cached_editors[i]) return;
        showModal = true;

        _this11.addPropertyCheckbox(i);
      });
      /* If no editors can be added or removed, hide the modal button */

      if (!showModal) {
        this.hideAddProperty();
        this.addproperty_button.style.display = 'none';
        /* If additional properties are disabled */
      } else if (!this.canHaveAdditionalProperties()) {
        this.addproperty_add.style.display = 'none';
        this.addproperty_input.style.display = 'none';
        /* If no new properties can be added */
      } else if (!canAdd) {
        this.addproperty_add.disabled = true;
        /* If new properties can be added */
      } else {
        this.addproperty_add.disabled = false;
      }
    }
  }, {
    key: "isRequiredObject",
    value: function isRequiredObject(editor) {
      if (!editor) {
        return;
      }

      if (typeof editor.schema.required === 'boolean') return editor.schema.required;else if (Array.isArray(this.schema.required)) return this.schema.required.includes(editor.key);else if (this.jsoneditor.options.required_by_default) return true;
      return false;
    }
  }, {
    key: "setValue",
    value: function setValue(value, initial) {
      var _this12 = this;

      value = value || {};
      if (_typeof(value) !== 'object' || Array.isArray(value)) value = {};
      /* First, set the values for all of the defined properties */

      Object.entries(this.cached_editors).forEach(function (_ref7) {
        var _ref8 = _slicedToArray(_ref7, 2),
            i = _ref8[0],
            editor = _ref8[1];

        /* Value explicitly set */
        if (typeof value[i] !== 'undefined') {
          _this12.addObjectProperty(i);

          editor.setValue(value[i], initial);
          /* Otherwise, remove value unless this is the initial set or it's required */
        } else if (!initial && !_this12.isRequiredObject(editor)) {
          _this12.removeObjectProperty(i);
          /* Otherwise, set the value to the default */

        } else {
          editor.setValue(editor.getDefault(), initial);
        }
      });
      Object.entries(value).forEach(function (_ref9) {
        var _ref10 = _slicedToArray(_ref9, 2),
            i = _ref10[0],
            val = _ref10[1];

        if (!_this12.cached_editors[i]) {
          _this12.addObjectProperty(i);

          if (_this12.editors[i]) _this12.editors[i].setValue(val, initial);
        }
      });
      this.refreshValue();
      this.layoutEditors();
      this.onChange();
    }
  }, {
    key: "showValidationErrors",
    value: function showValidationErrors(errors) {
      var _this13 = this;

      /* Get all the errors that pertain to this editor */
      var myErrors = [];
      var otherErrors = [];
      errors.forEach(function (error) {
        if (error.path === _this13.path) {
          myErrors.push(error);
        } else {
          otherErrors.push(error);
        }
      });
      /* Show errors for this editor */

      if (this.error_holder) {
        if (myErrors.length) {
          this.error_holder.innerHTML = '';
          this.error_holder.style.display = '';
          myErrors.forEach(function (error) {
            if (error.errorcount && error.errorcount > 1) error.message += " (".concat(error.errorcount, " errors)");

            _this13.error_holder.appendChild(_this13.theme.getErrorMessage(error.message));
          });
          /* Hide error area */
        } else {
          this.error_holder.style.display = 'none';
        }
      }
      /* Show error for the table row if this is inside a table */


      if (this.options.table_row) {
        if (myErrors.length) {
          this.theme.addTableRowError(this.container);
        } else {
          this.theme.removeTableRowError(this.container);
        }
      }
      /* Show errors for child editors */


      Object.values(this.editors).forEach(function (editor) {
        editor.showValidationErrors(otherErrors);
      });
    }
  }]);

  return ObjectEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/radio.js":
/*!******************************!*\
  !*** ./src/editors/radio.js ***!
  \******************************/
/*! exports provided: RadioEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "RadioEditor", function() { return RadioEditor; });
/* harmony import */ var _select_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./select.js */ "./src/editors/select.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var RadioEditor = /*#__PURE__*/function (_SelectEditor) {
  _inherits(RadioEditor, _SelectEditor);

  var _super = _createSuper(RadioEditor);

  function RadioEditor() {
    _classCallCheck(this, RadioEditor);

    return _super.apply(this, arguments);
  }

  _createClass(RadioEditor, [{
    key: "preBuild",
    value: function preBuild() {
      this.schema.required = true;
      /* force editor into required mode to prevent creation of empty radio button */

      _get(_getPrototypeOf(RadioEditor.prototype), "preBuild", this).call(this);
    }
  }, {
    key: "build",
    value: function build() {
      var _this = this;

      this.label = '';
      if (!this.options.compact) this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      if (this.schema.description) this.description = this.theme.getFormInputDescription(this.schema.description);
      if (this.options.infoText) this.infoButton = this.theme.getInfoButton(this.options.infoText);
      if (this.options.compact) this.container.classList.add('compact');
      this.radioContainer = document.createElement('div');
      this.radioGroup = [];

      var radioInputEventhandler = function radioInputEventhandler(e) {
        _this.setValue(e.currentTarget.value);

        _this.onChange(true);
      };

      for (var i = 0; i < this.enum_values.length; i++) {
        /* form radio elements */
        this.input = this.theme.getFormRadio({
          name: this.formname,
          id: "".concat(this.formname, "[").concat(i, "]"),
          value: this.enum_values[i]
        });
        /* Set custom attributes on input element. Parameter is array of protected keys. Empty array if none. */

        this.setInputAttributes(['id', 'value', 'name']);
        this.input.addEventListener('change', radioInputEventhandler, false);
        this.radioGroup.push(this.input);
        /* form-label for radio elements */

        var radioLabel = this.theme.getFormRadioLabel(this.enum_display[i]);
        radioLabel.htmlFor = this.input.id;
        var control = this.theme.getFormRadioControl(radioLabel, this.input, !!(this.options.layout === 'horizontal' || this.options.compact));
        this.radioContainer.appendChild(control);
      }

      if (this.schema.readOnly || this.schema.readonly) {
        this.always_disabled = true;

        for (var j = 0; j < this.radioGroup.length; j++) {
          this.radioGroup[j].disabled = true;
        }

        this.radioContainer.classList.add('readonly');
      }

      var radioContainerWrapper = this.theme.getContainer();
      radioContainerWrapper.appendChild(this.radioContainer);
      radioContainerWrapper.dataset.containerFor = 'radio';
      this.input = radioContainerWrapper;
      this.control = this.theme.getFormControl(this.label, radioContainerWrapper, this.description, this.infoButton);
      this.container.appendChild(this.control);
      /* Any special formatting that needs to happen after the input is added to the dom */

      window.requestAnimationFrame(function () {
        if (_this.input.parentNode) _this.afterInputReady();
      });
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        for (var i = 0; i < this.radioGroup.length; i++) {
          this.radioGroup[i].disabled = false;
        }

        this.radioContainer.classList.remove('readonly');

        _get(_getPrototypeOf(RadioEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;

      for (var i = 0; i < this.radioGroup.length; i++) {
        this.radioGroup[i].disabled = true;
      }

      this.radioContainer.classList.add('readonly');

      _get(_getPrototypeOf(RadioEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.radioContainer.parentNode && this.radioContainer.parentNode.parentNode) this.radioContainer.parentNode.parentNode.removeChild(this.radioContainer.parentNode);
      if (this.label && this.label.parentNode) this.label.parentNode.removeChild(this.label);
      if (this.description && this.description.parentNode) this.description.parentNode.removeChild(this.description);

      _get(_getPrototypeOf(RadioEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 2;
    }
  }, {
    key: "setValue",
    value: function setValue(val) {
      for (var i = 0; i < this.radioGroup.length; i++) {
        if (this.radioGroup[i].value === val) {
          this.radioGroup[i].checked = true;
          this.value = val;
          this.onChange();
          break;
        }
      }
    }
  }]);

  return RadioEditor;
}(_select_js__WEBPACK_IMPORTED_MODULE_0__["SelectEditor"]);

/***/ }),

/***/ "./src/editors/sceditor.js":
/*!*********************************!*\
  !*** ./src/editors/sceditor.js ***!
  \*********************************/
/*! exports provided: ScEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ScEditor", function() { return ScEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var ScEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(ScEditor, _StringEditor);

  var _super = _createSuper(ScEditor);

  function ScEditor() {
    _classCallCheck(this, ScEditor);

    return _super.apply(this, arguments);
  }

  _createClass(ScEditor, [{
    key: "setValue",
    value: function setValue(value, initial, fromTemplate) {
      var res = _get(_getPrototypeOf(ScEditor.prototype), "setValue", this).call(this, value, initial, fromTemplate);

      if (res !== undefined && res.changed && this.sceditor_instance) this.sceditor_instance.val(res.value);
    }
  }, {
    key: "build",
    value: function build() {
      this.options.format = 'textarea';
      /* Force format into "textarea" */

      _get(_getPrototypeOf(ScEditor.prototype), "build", this).call(this);

      this.input_type = this.schema.format;
      /* Restore original format */

      this.input.setAttribute('data-schemaformat', this.input_type);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      if (window.sceditor) {
        /* Get options, either global options from "this.defaults.options.sceditor" or */

        /* single property options from schema "options.sceditor" */
        var options = this.expandCallbacks('sceditor', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
          format: this.input_type,
          emoticonsEnabled: false,
          width: '100%',
          height: 300,
          readOnly: this.schema.readOnly || this.schema.readonly || this.schema.template
        }, this.defaults.options.sceditor || {}, this.options.sceditor || {}, {
          element: this.input
        }));
        var instance = window.sceditor.instance(this.input);

        if (instance === undefined) {
          window.sceditor.create(this.input, options);
          /* Create doesn't return instance. */
        }

        this.sceditor_instance = instance || window.sceditor.instance(this.input);
        /* Listen for changes */

        this.sceditor_instance.blur(function () {
          _this.value = _this.sceditor_instance.val();

          _this.sceditor_instance.updateOriginal();

          _this.is_dirty = true;

          _this.onChange(true);
        });
        this.theme.afterInputReady(this.input);
      } else _get(_getPrototypeOf(ScEditor.prototype), "afterInputReady", this).call(this);
      /* Library not loaded, so just treat this as a string */

    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 6;
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled && this.sceditor_instance) this.sceditor_instance.readOnly(false);

      _get(_getPrototypeOf(ScEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.sceditor_instance) this.sceditor_instance.readOnly(true);

      _get(_getPrototypeOf(ScEditor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.sceditor_instance) {
        this.sceditor_instance.destroy();
        this.sceditor_instance = null;
      }

      _get(_getPrototypeOf(ScEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return ScEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/select.js":
/*!*******************************!*\
  !*** ./src/editors/select.js ***!
  \*******************************/
/*! exports provided: SelectEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SelectEditor", function() { return SelectEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var SelectEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(SelectEditor, _AbstractEditor);

  var _super = _createSuper(SelectEditor);

  function SelectEditor() {
    _classCallCheck(this, SelectEditor);

    return _super.apply(this, arguments);
  }

  _createClass(SelectEditor, [{
    key: "setValue",
    value: function setValue(value, initial) {
      /* Sanitize value before setting it */
      var sanitized = this.typecast(value);
      var haveToUseDefaultValue = !!this.jsoneditor.options.use_default_values || typeof this.schema["default"] !== 'undefined';

      if (!this.enum_values.includes(sanitized) || initial && !this.isRequired() && !haveToUseDefaultValue) {
        sanitized = this.enum_values[0];
      }

      if (this.value === sanitized) return;
      if (initial) this.is_dirty = false;else if (this.jsoneditor.options.show_errors === 'change') this.is_dirty = true;
      this.input.value = this.enum_options[this.enum_values.indexOf(sanitized)];
      this.value = sanitized;
      this.onChange();
      this.change();
    }
  }, {
    key: "register",
    value: function register() {
      _get(_getPrototypeOf(SelectEditor.prototype), "register", this).call(this);

      if (!this.input) return;
      this.input.setAttribute('name', this.formname);
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(SelectEditor.prototype), "unregister", this).call(this);

      if (!this.input) return;
      this.input.removeAttribute('name');
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      if (!this.enum_options) return 3;
      var longestText = this.getTitle().length;

      for (var i = 0; i < this.enum_options.length; i++) {
        longestText = Math.max(longestText, this.enum_options[i].length + 4);
      }

      return Math.min(12, Math.max(longestText / 7, 2));
    }
  }, {
    key: "typecast",
    value: function typecast(value) {
      if (this.schema.type === 'boolean') return value === 'undefined' || value === undefined ? undefined : !!value;else if (this.schema.type === 'number') return 1 * value || 0;else if (this.schema.type === 'integer') return Math.floor(value * 1 || 0);else if (this.schema["enum"] && value === undefined) return undefined;
      return "".concat(value);
    }
  }, {
    key: "getValue",
    value: function getValue() {
      if (!this.dependenciesFulfilled) {
        return undefined;
      }

      return this.typecast(this.value);
    }
  }, {
    key: "preBuild",
    value: function preBuild() {
      var _this = this;

      this.input_type = 'select';
      this.enum_options = [];
      this.enum_values = [];
      this.enum_display = [];
      var i;
      var callback;
      /* Enum options enumerated */

      if (this.schema["enum"]) {
        var display = this.schema.options && this.schema.options.enum_titles || [];
        this.schema["enum"].forEach(function (option, i) {
          _this.enum_options[i] = "".concat(option);
          _this.enum_display[i] = "".concat(display[i] || option);
          _this.enum_values[i] = _this.typecast(option);
        });

        if (!this.isRequired()) {
          this.enum_display.unshift(' ');
          this.enum_options.unshift('undefined');
          this.enum_values.unshift(undefined);
        }
        /* Boolean */

      } else if (this.schema.type === 'boolean') {
        this.enum_display = this.schema.options && this.schema.options.enum_titles || ['true', 'false'];
        this.enum_options = ['1', ''];
        this.enum_values = [true, false];

        if (!this.isRequired()) {
          this.enum_display.unshift(' ');
          this.enum_options.unshift('undefined');
          this.enum_values.unshift(undefined);
        }
        /* Dynamic Enum */

      } else if (this.schema.enumSource) {
        this.enumSource = [];
        this.enum_display = [];
        this.enum_options = [];
        this.enum_values = [];
        /* Shortcut declaration for using a single array */

        if (!Array.isArray(this.schema.enumSource)) {
          if (this.schema.enumValue) {
            this.enumSource = [{
              source: this.schema.enumSource,
              value: this.schema.enumValue
            }];
          } else {
            this.enumSource = [{
              source: this.schema.enumSource
            }];
          }
        } else {
          for (i = 0; i < this.schema.enumSource.length; i++) {
            /* Shorthand for watched variable */
            if (typeof this.schema.enumSource[i] === 'string') {
              this.enumSource[i] = {
                source: this.schema.enumSource[i]
              };
              /* Make a copy of the schema */
            } else if (!Array.isArray(this.schema.enumSource[i])) {
              this.enumSource[i] = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.schema.enumSource[i]);
            } else {
              this.enumSource[i] = this.schema.enumSource[i];
            }
          }
        }
        /* Now, enumSource is an array of sources */

        /* Walk through this array and fix up the values */


        for (i = 0; i < this.enumSource.length; i++) {
          if (this.enumSource[i].value) {
            callback = this.expandCallbacks('template', {
              template: this.enumSource[i].value
            });
            if (typeof callback.template === 'function') this.enumSource[i].value = callback.template;else this.enumSource[i].value = this.jsoneditor.compileTemplate(this.enumSource[i].value, this.template_engine);
          }

          if (this.enumSource[i].title) {
            callback = this.expandCallbacks('template', {
              template: this.enumSource[i].title
            });
            if (typeof callback.template === 'function') this.enumSource[i].title = callback.template;else this.enumSource[i].title = this.jsoneditor.compileTemplate(this.enumSource[i].title, this.template_engine);
          }

          if (this.enumSource[i].filter && this.enumSource[i].value) {
            callback = this.expandCallbacks('template', {
              template: this.enumSource[i].filter
            });
            if (typeof callback.template === 'function') this.enumSource[i].filter = callback.template;else this.enumSource[i].filter = this.jsoneditor.compileTemplate(this.enumSource[i].filter, this.template_engine);
          }
        }
        /* Other, not supported */

      } else {
        throw new Error("'select' editor requires the enum property to be set.");
      }
    }
  }, {
    key: "build",
    value: function build() {
      var _this2 = this;

      if (!this.options.compact) this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      if (this.schema.description) this.description = this.theme.getFormInputDescription(this.schema.description);
      if (this.options.infoText) this.infoButton = this.theme.getInfoButton(this.options.infoText);
      if (this.options.compact) this.container.classList.add('compact');
      this.input = this.theme.getSelectInput(this.enum_options, false);
      this.theme.setSelectOptions(this.input, this.enum_options, this.enum_display);

      if (this.schema.readOnly || this.schema.readonly) {
        this.always_disabled = true;
        this.input.disabled = true;
      }
      /* Set custom attributes on input element. Parameter is array of protected keys. Empty array if none. */


      this.setInputAttributes([]);
      this.input.addEventListener('change', function (e) {
        e.preventDefault();
        e.stopPropagation();

        _this2.onInputChange();
      });
      this.control = this.theme.getFormControl(this.label, this.input, this.description, this.infoButton);
      this.container.appendChild(this.control);
      this.value = this.enum_values[0];
      /* Any special formatting that needs to happen after the input is added to the dom */

      window.requestAnimationFrame(function () {
        if (_this2.input.parentNode) _this2.afterInputReady();
      });
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      this.theme.afterInputReady(this.input);
    }
  }, {
    key: "onInputChange",
    value: function onInputChange() {
      var val = this.typecast(this.input.value);
      var newVal;
      /* Invalid option, use first option instead */

      if (!this.enum_values.includes(val)) {
        newVal = this.enum_values[0];
      } else {
        newVal = this.enum_values[this.enum_values.indexOf(val)];
      }
      /* If valid hasn't changed */


      if (newVal === this.value) return;
      this.is_dirty = true;
      /* Store new value and propogate change event */

      this.value = newVal;
      this.onChange(true);
    }
  }, {
    key: "onWatchedFieldChange",
    value: function onWatchedFieldChange() {
      var vars;
      var j;
      var selectOptions = [];
      var selectTitles = [];
      /* If this editor uses a dynamic select box */

      if (this.enumSource) {
        vars = this.getWatchedFieldValues();

        for (var i = 0; i < this.enumSource.length; i++) {
          /* Constant values */
          if (Array.isArray(this.enumSource[i])) {
            selectOptions = selectOptions.concat(this.enumSource[i]);
            selectTitles = selectTitles.concat(this.enumSource[i]);
          } else {
            var items = [];
            /* Static list of items */

            if (Array.isArray(this.enumSource[i].source)) {
              items = this.enumSource[i].source;
              /* A watched field */
            } else {
              items = vars[this.enumSource[i].source];
            }

            if (items) {
              /* Only use a predefined part of the array */
              if (this.enumSource[i].slice) {
                items = Array.prototype.slice.apply(items, this.enumSource[i].slice);
              }
              /* Filter the items */


              if (this.enumSource[i].filter) {
                var newItems = [];

                for (j = 0; j < items.length; j++) {
                  if (this.enumSource[i].filter({
                    i: j,
                    item: items[j],
                    watched: vars
                  })) newItems.push(items[j]);
                }

                items = newItems;
              }

              var itemTitles = [];
              var itemValues = [];

              for (j = 0; j < items.length; j++) {
                var item = items[j];
                /* Rendered value */

                if (this.enumSource[i].value) {
                  itemValues[j] = this.typecast(this.enumSource[i].value({
                    i: j,
                    item: item
                  }));
                  /* Use value directly */
                } else {
                  itemValues[j] = items[j];
                }
                /* Rendered title */


                if (this.enumSource[i].title) {
                  itemTitles[j] = this.enumSource[i].title({
                    i: j,
                    item: item
                  });
                  /* Use value as the title also */
                } else {
                  itemTitles[j] = itemValues[j];
                }
              }

              if (this.enumSource[i].sort) {
                (function (itemValues, itemTitles, order) {
                  itemValues.map(function (v, i) {
                    return {
                      v: v,
                      t: itemTitles[i]
                    };
                  }).sort(function (a, b) {
                    return a.v < b.v ? -order : a.v === b.v ? 0 : order;
                  }).forEach(function (v, i) {
                    itemValues[i] = v.v;
                    itemTitles[i] = v.t;
                  });
                }).bind(null, itemValues, itemTitles, this.enumSource[i].sort === 'desc' ? 1 : -1)();
              }

              selectOptions = selectOptions.concat(itemValues);
              selectTitles = selectTitles.concat(itemTitles);
            }
          }
        }

        var prevValue = this.value;
        this.theme.setSelectOptions(this.input, selectOptions, selectTitles);
        this.enum_options = selectOptions;
        this.enum_display = selectTitles;
        this.enum_values = selectOptions;
        /* If the previous value is still in the new select options */

        /* or if global option "enum_source_value_auto_select" is true, stick with it */

        if (selectOptions.includes(prevValue) || this.jsoneditor.options.enum_source_value_auto_select !== false) {
          this.input.value = prevValue;
          this.value = prevValue;
          /* Otherwise, set the value to the first select option */
        } else {
          this.input.value = selectOptions[0];
          this.value = this.typecast(selectOptions[0] || '');
          if (this.parent && !this.watchLoop) this.parent.onChildEditorChange(this);else this.jsoneditor.onChange();
          this.jsoneditor.notifyWatchers(this.path);
        }
      }

      _get(_getPrototypeOf(SelectEditor.prototype), "onWatchedFieldChange", this).call(this);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        this.input.disabled = false;
      }

      _get(_getPrototypeOf(SelectEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;
      this.input.disabled = true;

      _get(_getPrototypeOf(SelectEditor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.label && this.label.parentNode) this.label.parentNode.removeChild(this.label);
      if (this.description && this.description.parentNode) this.description.parentNode.removeChild(this.description);
      if (this.input && this.input.parentNode) this.input.parentNode.removeChild(this.input);

      _get(_getPrototypeOf(SelectEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "showValidationErrors",
    value: function showValidationErrors(errors) {
      var _this3 = this;

      this.previous_error_setting = this.jsoneditor.options.show_errors;

      var addMessage = function addMessage(messages, error) {
        if (error.path === _this3.path) {
          messages.push(error.message);
        }

        return messages;
      };

      var messages = errors.reduce(addMessage, []);

      if (messages.length) {
        this.theme.addInputError(this.input, "".concat(messages.join('. '), "."));
      } else {
        this.theme.removeInputError(this.input);
      }
    }
  }]);

  return SelectEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/select2.js":
/*!********************************!*\
  !*** ./src/editors/select2.js ***!
  \********************************/
/*! exports provided: Select2Editor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Select2Editor", function() { return Select2Editor; });
/* harmony import */ var _select_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./select.js */ "./src/editors/select.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var Select2Editor = /*#__PURE__*/function (_SelectEditor) {
  _inherits(Select2Editor, _SelectEditor);

  var _super = _createSuper(Select2Editor);

  function Select2Editor() {
    _classCallCheck(this, Select2Editor);

    return _super.apply(this, arguments);
  }

  _createClass(Select2Editor, [{
    key: "setValue",
    value: function setValue(value, initial) {
      if (this.select2_instance) {
        if (initial) this.is_dirty = false;else if (this.jsoneditor.options.show_errors === 'change') this.is_dirty = true;
        var sanitized = this.updateValue(value);
        /* Sets this.value to sanitized value */

        this.input.value = sanitized;
        if (this.select2v4) this.select2_instance.val(sanitized).trigger('change');else this.select2_instance.select2('val', sanitized);
        this.onChange(true);
      } else _get(_getPrototypeOf(Select2Editor.prototype), "setValue", this).call(this, value, initial);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && !this.select2_instance) {
        /* Get options, either global options from "this.defaults.options.select2" or */

        /* single property options from schema "options.select2" */
        var options = this.expandCallbacks('select2', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.defaults.options.select2 || {}, this.options.select2 || {}));
        /* New items are allowed if option "tags" is true and type is "string" */

        this.newEnumAllowed = options.tags = !!options.tags && this.schema.type === 'string';
        this.select2_instance = window.jQuery(this.input).select2(options);
        this.select2v4 = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["hasOwnProperty"])(this.select2_instance.select2, 'amd');
        /* Create change handler */

        this.selectChangeHandler = function () {
          var value = _this.select2v4 ? _this.select2_instance.val() : _this.select2_instance.select2('val');

          _this.updateValue(value);

          _this.onChange(true);
        };
        /* Add event handler. */

        /* Note: Must use the "on()" method and not addEventListener() */


        this.select2_instance.on('change', this.selectChangeHandler);
        this.select2_instance.on('select2-blur', this.selectChangeHandler);
      }

      _get(_getPrototypeOf(Select2Editor.prototype), "afterInputReady", this).call(this);
    }
  }, {
    key: "updateValue",
    value: function updateValue(value) {
      var sanitized = this.enum_values[0];
      value = this.typecast(value || '');

      if (!this.enum_values.includes(value)) {
        if (this.newEnumAllowed) {
          sanitized = this.addNewOption(value) ? value : sanitized;
        }
      } else sanitized = value;

      this.value = sanitized;
      return sanitized;
    }
  }, {
    key: "addNewOption",
    value: function addNewOption(value) {
      var sanitized = this.typecast(value);
      var res = false;
      var optionTag;

      if (!this.enum_values.includes(sanitized) && sanitized !== '') {
        /* Add to list of valid enum values */
        this.enum_options.push("".concat(sanitized));
        this.enum_display.push("".concat(sanitized));
        this.enum_values.push(sanitized);
        /* Update Schema enum to prevent triggering error */

        /* "Value must be one of the enumerated values" */

        this.schema["enum"].push(sanitized);
        optionTag = this.input.querySelector("option[value=\"".concat(sanitized, "\"]"));

        if (optionTag) {
          /* Remove data attribute to make option tag permanent. */
          optionTag.removeAttribute('data-select2-tag');
        } else {
          this.input.appendChild(new Option(sanitized, sanitized, false, false)).trigger('change');
        }

        res = true;
      }

      return res;
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        if (this.select2_instance) {
          if (this.select2v4) this.select2_instance.prop('disabled', false);else this.select2_instance.select2('enable', true);
        }
      }

      _get(_getPrototypeOf(Select2Editor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.select2_instance) {
        if (this.select2v4) this.select2_instance.prop('disabled', true);else this.select2_instance.select2('enable', false);
      }

      _get(_getPrototypeOf(Select2Editor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.select2_instance) {
        this.select2_instance.select2('destroy');
        this.select2_instance = null;
      }

      _get(_getPrototypeOf(Select2Editor.prototype), "destroy", this).call(this);
    }
  }]);

  return Select2Editor;
}(_select_js__WEBPACK_IMPORTED_MODULE_0__["SelectEditor"]);

/***/ }),

/***/ "./src/editors/selectize.js":
/*!**********************************!*\
  !*** ./src/editors/selectize.js ***!
  \**********************************/
/*! exports provided: SelectizeEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SelectizeEditor", function() { return SelectizeEditor; });
/* harmony import */ var _select_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./select.js */ "./src/editors/select.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var SelectizeEditor = /*#__PURE__*/function (_SelectEditor) {
  _inherits(SelectizeEditor, _SelectEditor);

  var _super = _createSuper(SelectizeEditor);

  function SelectizeEditor() {
    _classCallCheck(this, SelectizeEditor);

    return _super.apply(this, arguments);
  }

  _createClass(SelectizeEditor, [{
    key: "setValue",
    value: function setValue(value, initial) {
      if (this.selectize_instance) {
        if (initial) this.is_dirty = false;else if (this.jsoneditor.options.show_errors === 'change') this.is_dirty = true;
        var sanitized = this.updateValue(value);
        /* Sets this.value to sanitized value */

        this.input.value = sanitized;
        this.selectize_instance.clear(true);
        this.selectize_instance.setValue(sanitized);
        this.onChange(true);
      } else _get(_getPrototypeOf(SelectizeEditor.prototype), "setValue", this).call(this, value, initial);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.selectize && !this.selectize_instance) {
        /* Get options, either global options from "this.defaults.options.selectize" or */

        /* single property options from schema "options.selectize" */
        var options = this.expandCallbacks('selectize', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.defaults.options.selectize || {}, this.options.selectize || {}));
        /* New items are allowed if option "create" is true and type is "string" */

        this.newEnumAllowed = options.create = !!options.create && this.schema.type === 'string';
        this.selectize_instance = window.jQuery(this.input).selectize(options)[0].selectize;
        /* Remove change handler set in parent class (src/multiselect.js) */

        this.control.removeEventListener('change', this.multiselectChangeHandler);
        /* Create a new change handler */

        this.multiselectChangeHandler = function (value) {
          /* var value = this.selectize_instance.getValue(true); */

          /* this.value = value; */
          _this.updateValue(value);

          _this.onChange(true);
        };
        /* Add new event handler. */

        /* Note: Must use the "on()" method and not addEventListener() */


        this.selectize_instance.on('change', this.multiselectChangeHandler);
      }

      _get(_getPrototypeOf(SelectizeEditor.prototype), "afterInputReady", this).call(this);
    }
  }, {
    key: "updateValue",
    value: function updateValue(value) {
      var sanitized = this.enum_values[0];
      value = this.typecast(value || '');

      if (!this.enum_values.includes(value)) {
        if (this.newEnumAllowed) {
          sanitized = this.addNewOption(value) ? value : sanitized;
        }
      } else sanitized = value;

      this.value = sanitized;
      return sanitized;
    }
  }, {
    key: "addNewOption",
    value: function addNewOption(value) {
      var sanitized = this.typecast(value);
      var res = false;

      if (!this.enum_values.includes(sanitized) && sanitized !== '') {
        /* Add to list of valid enum values */
        this.enum_options.push("".concat(sanitized));
        this.enum_display.push("".concat(sanitized));
        this.enum_values.push(sanitized);
        /* Update Schema enum to prevent triggering error */

        /* "Value must be one of the enumerated values" */

        this.schema["enum"].push(sanitized);
        /* Add selectize item */

        this.selectize_instance.addItem(sanitized);
        this.selectize_instance.refreshOptions(false);
        res = true;
      }

      return res;
    }
  }, {
    key: "onWatchedFieldChange",
    value: function onWatchedFieldChange() {
      var _this2 = this;

      _get(_getPrototypeOf(SelectizeEditor.prototype), "onWatchedFieldChange", this).call(this);

      if (this.selectize_instance) {
        this.selectize_instance.clear(true);
        /* Clear selection */

        this.selectize_instance.clearOptions(true);
        /* Remove all options */

        this.enum_options.forEach(function (value, i) {
          _this2.selectize_instance.addOption({
            value: value,
            text: _this2.enum_display[i]
          });
        });
        this.selectize_instance.addItem("".concat(this.value), true);
        /* Set new selection */
      }
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled && this.selectize_instance) this.selectize_instance.unlock();

      _get(_getPrototypeOf(SelectizeEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.selectize_instance) this.selectize_instance.lock();

      _get(_getPrototypeOf(SelectizeEditor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.selectize_instance) {
        this.selectize_instance.destroy();
        this.selectize_instance = null;
      }

      _get(_getPrototypeOf(SelectizeEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return SelectizeEditor;
}(_select_js__WEBPACK_IMPORTED_MODULE_0__["SelectEditor"]);

/***/ }),

/***/ "./src/editors/signature.js":
/*!**********************************!*\
  !*** ./src/editors/signature.js ***!
  \**********************************/
/*! exports provided: SignatureEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SignatureEditor", function() { return SignatureEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/* This editor is using the signature pad editor from https://github.com/szimek/signature_pad */

/* Credits for the pad itself go to https://github.com/szimek */

var SignatureEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(SignatureEditor, _StringEditor);

  var _super = _createSuper(SignatureEditor);

  function SignatureEditor() {
    _classCallCheck(this, SignatureEditor);

    return _super.apply(this, arguments);
  }

  _createClass(SignatureEditor, [{
    key: "build",
    value: function build() {
      var _this = this;

      if (!this.options.compact) this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      if (this.schema.description) this.description = this.theme.getFormInputDescription(this.schema.description);
      var formname = this.formname.replace(/\W/g, '');

      if (typeof SignaturePad === 'function') {
        /* Dynamically add the required CSS the first time this editor is used */
        this.input = this.theme.getFormInputField('hidden');
        this.container.appendChild(this.input);
        /* Required to keep height */

        var signatureContainer = document.createElement('div');
        signatureContainer.classList.add('signature-container');
        /* Create canvas for signature pad */

        var canvas = document.createElement('canvas');
        canvas.setAttribute('name', formname);
        canvas.classList.add('signature');
        signatureContainer.appendChild(canvas);
        this.signaturePad = new window.SignaturePad(canvas, {
          onEnd: function onEnd() {
            /* check if the signature is not empty before setting a value */
            if (!this.signaturePad.isEmpty()) {
              this.input.value = this.signaturePad.toDataURL();
            } else {
              this.input.value = '';
            }

            this.is_dirty = true;
            this.refreshValue();
            this.watch_listener();
            this.jsoneditor.notifyWatchers(this.path);
            if (this.parent) this.parent.onChildEditorChange(this);else this.jsoneditor.onChange();
          }
        });
        /* create button containers and add clear signature button */

        var buttons = document.createElement('div');
        var clearButton = document.createElement('button');
        clearButton.classList.add('tiny', 'button');
        clearButton.innerHTML = 'Clear signature';
        buttons.appendChild(clearButton);
        signatureContainer.appendChild(buttons);
        if (this.options.compact) this.container.setAttribute('class', "".concat(this.container.getAttribute('class'), " compact"));

        if (this.schema.readOnly || this.schema.readonly) {
          this.always_disabled = true;
          Array.from(this.inputs).forEach(function (input) {
            canvas.setAttribute('readOnly', 'readOnly');
            input.disabled = true;
          });
        }
        /* add listener to the clear button. when clicked, trigger a canvas change after emptying the canvas */


        clearButton.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this.signaturePad.clear();
          /* trigger stroke end to let signaturePad update the dataURL */


          _this.signaturePad.strokeEnd();
        });
        this.control = this.theme.getFormControl(this.label, signatureContainer, this.description);
        this.container.appendChild(this.control);
        this.refreshValue();
        /* signature canvas will stretch to signatureContainer width */

        canvas.width = signatureContainer.offsetWidth;

        if (this.options && this.options.canvas_height) {
          canvas.height = this.options.canvas_height;
        } else {
          canvas.height = '300';
          /* Set to default height of 300px; */
        }
      } else {
        var message = document.createElement('p');
        message.innerHTML = 'Signature pad is not available, please include SignaturePad from https://github.com/szimek/signature_pad';
        this.container.appendChild(message);
      }
    }
  }, {
    key: "setValue",
    value: function setValue(val) {
      if (typeof SignaturePad === 'function') {
        var sanitized = this.sanitize(val);

        if (this.value === sanitized) {
          return;
        }

        this.value = sanitized;
        this.input.value = this.value;
        this.signaturePad.clear();
        /* only set contents if value != '' */

        if (val && val !== '') {
          this.signaturePad.fromDataURL(val);
        }

        this.watch_listener();
        this.jsoneditor.notifyWatchers(this.path);
        return false;
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this.signaturePad.off();
      delete this.signaturePad;
    }
  }]);

  return SignatureEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/simplemde.js":
/*!**********************************!*\
  !*** ./src/editors/simplemde.js ***!
  \**********************************/
/*! exports provided: SimplemdeEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SimplemdeEditor", function() { return SimplemdeEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var SimplemdeEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(SimplemdeEditor, _StringEditor);

  var _super = _createSuper(SimplemdeEditor);

  function SimplemdeEditor() {
    _classCallCheck(this, SimplemdeEditor);

    return _super.apply(this, arguments);
  }

  _createClass(SimplemdeEditor, [{
    key: "setValue",
    value: function setValue(value, initial, fromTemplate) {
      var res = _get(_getPrototypeOf(SimplemdeEditor.prototype), "setValue", this).call(this, value, initial, fromTemplate);

      if (res !== undefined && res.changed && this.simplemde_instance) this.simplemde_instance.value(res.value);
    }
  }, {
    key: "build",
    value: function build() {
      this.options.format = 'textarea';
      /* Force format into "textarea" */

      _get(_getPrototypeOf(SimplemdeEditor.prototype), "build", this).call(this);

      this.input_type = this.schema.format;
      /* Restore original format */

      this.input.setAttribute('data-schemaformat', this.input_type);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this = this;

      var options;

      if (window.SimpleMDE) {
        /* Get options, either global options from "this.defaults.options.simplemde" or */

        /* single property options from schema "options.simplemde" */
        options = this.expandCallbacks('simplemde', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
          height: 300
        }, this.defaults.options.simplemde || {}, this.options.simplemde || {}, {
          element: this.input
        }));
        this.simplemde_instance = new window.SimpleMDE(options);

        if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
          this.simplemde_instance.codemirror.options.readOnly = true;
        }
        /* Listen for changes */


        this.simplemde_instance.codemirror.on('change', function () {
          _this.value = _this.simplemde_instance.value();
          _this.is_dirty = true;

          _this.onChange(true);
        });
        /* This will prevent SimpleMDE content from being hidden until focus in Chrome */

        /* if SimpleMDE is not visible (Like when placed inside Tabs) */

        if (options.autorefresh) {
          this.startListening(this.simplemde_instance.codemirror, this.simplemde_instance.codemirror.state.autoRefresh = {
            delay: 250
          });
        }

        this.theme.afterInputReady(this.input);
      } else _get(_getPrototypeOf(SimplemdeEditor.prototype), "afterInputReady", this).call(this);
      /* Library not loaded, so just treat this as a string */

    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 6;
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled && this.simplemde_instance) this.simplemde_instance.codemirror.options.readOnly = false;

      _get(_getPrototypeOf(SimplemdeEditor.prototype), "enable", this).call(this);
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (this.simplemde_instance) this.simplemde_instance.codemirror.options.readOnly = true;

      _get(_getPrototypeOf(SimplemdeEditor.prototype), "disable", this).call(this, alwaysDisabled);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.simplemde_instance) {
        this.simplemde_instance.toTextArea();
        this.simplemde_instance = null;
      }

      _get(_getPrototypeOf(SimplemdeEditor.prototype), "destroy", this).call(this);
    }
    /* Ported from https://codemirror.net/addon/display/autorefresh.js */

  }, {
    key: "startListening",
    value: function startListening(cm, state) {
      function check() {
        if (cm.display.wrapper.offsetHeight) {
          this.stopListening(cm, state);

          if (cm.display.lastWrapHeight !== cm.display.wrapper.clientHeight) {
            cm.refresh();
          }
        } else {
          state.timeout = window.setTimeout(check, state.delay);
        }
      }

      state.timeout = window.setTimeout(check, state.delay);

      state.hurry = function () {
        window.clearTimeout(state.timeout);
        state.timeout = window.setTimeout(check, 50);
      };

      cm.on(window, 'mouseup', state.hurry);
      cm.on(window, 'keyup', state.hurry);
    }
  }, {
    key: "stopListening",
    value: function stopListening(cm, state) {
      window.clearTimeout(state.timeout);
      cm.off(window, 'mouseup', state.hurry);
      cm.off(window, 'keyup', state.hurry);
    }
  }]);

  return SimplemdeEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);

/***/ }),

/***/ "./src/editors/starrating.css.js":
/*!***************************************!*\
  !*** ./src/editors/starrating.css.js ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* eslint-disable */
/* harmony default export */ __webpack_exports__["default"] = ({
  ".starrating": "direction:rtl;display:inline-block;white-space:nowrap",
  ".starrating > input": "display:none",
  ".starrating > label:before": "content:'%5C2606';margin:1px;font-size:18px;font-style:normal;font-weight:400;line-height:1;font-family:'Arial';display:inline-block",
  ".starrating > label": "color:%23888;cursor:pointer;margin:8px%200%202px%200",
  ".starrating > label.starrating-display-enabled": "margin:1px%200%200%200",
  ".starrating > input:checked ~ label": "color:%23ffca08",
  ".starrating:not(.readonly) > input:hover ~ label": "color:%23ffca08",
  ".starrating > input:checked ~ label:before": "content:'%5C2605';text-shadow:0%200%201px%20rgba(0%2C20%2C20%2C1)",
  ".starrating:not(.readonly) > input:hover ~ label:before": "content:'%5C2605';text-shadow:0%200%201px%20rgba(0%2C20%2C20%2C1)",
  ".starrating .starrating-display": "position:relative;direction:rtl;text-align:center;font-size:10px;line-height:0px"
});
/* eslint-enable */

/***/ }),

/***/ "./src/editors/starrating.js":
/*!***********************************!*\
  !*** ./src/editors/starrating.js ***!
  \***********************************/
/*! exports provided: StarratingEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "StarratingEditor", function() { return StarratingEditor; });
/* harmony import */ var _string_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./string.js */ "./src/editors/string.js");
/* harmony import */ var _starrating_css_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./starrating.css.js */ "./src/editors/starrating.css.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var StarratingEditor = /*#__PURE__*/function (_StringEditor) {
  _inherits(StarratingEditor, _StringEditor);

  var _super = _createSuper(StarratingEditor);

  function StarratingEditor() {
    _classCallCheck(this, StarratingEditor);

    return _super.apply(this, arguments);
  }

  _createClass(StarratingEditor, [{
    key: "build",
    value: function build() {
      var _this = this;

      if (!this.options.compact) this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      if (this.schema.description) this.description = this.theme.getFormInputDescription(this.schema.description);
      if (this.options.infoText) this.infoButton = this.theme.getInfoButton(this.options.infoText);
      if (this.options.compact) this.container.classList.add('compact');
      this.ratingContainer = document.createElement('div');
      this.ratingContainer.classList.add('starrating');
      /* Emulate the old "rating" editor parameters */

      if (this.schema["enum"] === undefined) {
        var max = this.schema.maximum ? this.schema.maximum : 5;
        if (this.schema.exclusiveMaximum) max--;
        this.enum_values = [];

        for (var k = 0; k < max; k++) {
          this.enum_values.push(k + 1);
        }
      } else this.enum_values = this.schema["enum"];

      this.radioGroup = [];

      var radioInputEventhandler = function radioInputEventhandler(e) {
        e.preventDefault();
        e.stopPropagation();

        _this.setValue(e.currentTarget.value);

        _this.onChange(true);
      };

      for (var i = this.enum_values.length - 1; i > -1; i--) {
        var id = this.formname + (i + 1);
        /* form radio elements */

        var radioInput = this.theme.getFormInputField('radio');
        radioInput.name = "".concat(this.formname, "[starrating]");
        radioInput.value = this.enum_values[i];
        radioInput.id = id;
        radioInput.addEventListener('change', radioInputEventhandler, false);
        this.radioGroup.push(radioInput);
        /* form-label for radio elements */

        var radioLabel = document.createElement('label');
        radioLabel.htmlFor = id;
        radioLabel.title = this.enum_values[i];

        if (this.options.displayValue) {
          radioLabel.classList.add('starrating-display-enabled');
        }

        this.ratingContainer.appendChild(radioInput);
        this.ratingContainer.appendChild(radioLabel);
      }

      if (this.options.displayValue) {
        this.displayRating = document.createElement('div');
        this.displayRating.classList.add('starrating-display');
        this.displayRating.innerText = this.enum_values[0];
        this.ratingContainer.appendChild(this.displayRating);
      }

      if (this.schema.readOnly || this.schema.readonly) {
        this.always_disabled = true;

        for (var j = 0; j < this.radioGroup.length; j++) {
          this.radioGroup[j].disabled = true;
        }

        this.ratingContainer.classList.add('readonly');
      }

      var ratingsContainerWrapper = this.theme.getContainer();
      ratingsContainerWrapper.appendChild(this.ratingContainer);
      this.input = ratingsContainerWrapper;
      this.control = this.theme.getFormControl(this.label, ratingsContainerWrapper, this.description, this.infoButton);
      this.container.appendChild(this.control);
      this.refreshValue();
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        for (var i = 0; i < this.radioGroup.length; i++) {
          this.radioGroup[i].disabled = false;
        }

        this.ratingContainer.classList.remove('readonly');

        _get(_getPrototypeOf(StarratingEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;

      for (var i = 0; i < this.radioGroup.length; i++) {
        this.radioGroup[i].disabled = true;
      }

      this.ratingContainer.classList.add('readonly');

      _get(_getPrototypeOf(StarratingEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.ratingContainer.parentNode && this.ratingContainer.parentNode.parentNode) this.ratingContainer.parentNode.parentNode.removeChild(this.ratingContainer.parentNode);
      if (this.label && this.label.parentNode) this.label.parentNode.removeChild(this.label);
      if (this.description && this.description.parentNode) this.description.parentNode.removeChild(this.description);

      _get(_getPrototypeOf(StarratingEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 2;
    }
  }, {
    key: "getValue",
    value: function getValue() {
      if (!this.dependenciesFulfilled) {
        return undefined;
      }

      if (this.schema.type === 'integer') {
        return this.value === '' ? undefined : this.value * 1;
      }

      return this.value;
    }
  }, {
    key: "setValue",
    value: function setValue(value) {
      for (var i = 0; i < this.radioGroup.length; i++) {
        if (this.radioGroup[i].value === "".concat(value)) {
          this.radioGroup[i].checked = true;
          this.value = value;
          if (this.options.displayValue) this.displayRating.innerHTML = this.value;
          this.onChange(true);
          break;
        }
      }
    }
  }]);

  return StarratingEditor;
}(_string_js__WEBPACK_IMPORTED_MODULE_0__["StringEditor"]);
StarratingEditor.rules = _starrating_css_js__WEBPACK_IMPORTED_MODULE_1__["default"];

/***/ }),

/***/ "./src/editors/string.js":
/*!*******************************!*\
  !*** ./src/editors/string.js ***!
  \*******************************/
/*! exports provided: StringEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "StringEditor", function() { return StringEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var StringEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(StringEditor, _AbstractEditor);

  var _super = _createSuper(StringEditor);

  function StringEditor() {
    _classCallCheck(this, StringEditor);

    return _super.apply(this, arguments);
  }

  _createClass(StringEditor, [{
    key: "register",
    value: function register() {
      _get(_getPrototypeOf(StringEditor.prototype), "register", this).call(this);

      if (!this.input) return;
      this.input.setAttribute('name', this.formname);
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(StringEditor.prototype), "unregister", this).call(this);

      if (!this.input) return;
      this.input.removeAttribute('name');
    }
  }, {
    key: "setValue",
    value: function setValue(value, initial, fromTemplate) {
      if (this.template && !fromTemplate) return;
      if (value === null || typeof value === 'undefined') value = '';else if (_typeof(value) === 'object') value = JSON.stringify(value);else if (typeof value !== 'string') value = "".concat(value);
      if (value === this.serialized) return;
      /* Sanitize value before setting it */

      var sanitized = this.sanitize(value);
      if (this.input.value === sanitized) return;
      this.input.value = sanitized;

      if (this.format === 'range') {
        var output = this.control.querySelector('output');

        if (output) {
          output.value = sanitized;
        }
      }

      var changed = fromTemplate || this.getValue() !== value;
      this.refreshValue();
      if (initial) this.is_dirty = false;else if (this.jsoneditor.options.show_errors === 'change') this.is_dirty = true;
      if (this.adjust_height) this.adjust_height(this.input);
      /* Bubble this setValue to parents if the value changed */

      this.onChange(changed);
      /* Return object with changed state and sanitized value for use in editors that extend this */

      return {
        changed: changed,
        value: sanitized
      };
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      var min = Math.ceil(Math.max(this.getTitle().length, this.schema.maxLength || 0, this.schema.minLength || 0) / 5);
      var num;
      if (this.input_type === 'textarea') num = 6;else if (['text', 'email'].includes(this.input_type)) num = 4;else num = 2;
      return Math.min(12, Math.max(min, num));
    }
  }, {
    key: "build",
    value: function build() {
      var _this = this;

      if (!this.options.compact) this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      if (this.schema.description) this.description = this.theme.getFormInputDescription(this.schema.description);
      if (this.options.infoText) this.infoButton = this.theme.getInfoButton(this.options.infoText);
      this.format = this.schema.format;

      if (!this.format && this.schema.media && this.schema.media.type) {
        this.format = this.schema.media.type.replace(/(^(application|text)\/(x-)?(script\.)?)|(-source$)/g, '');
      }

      if (!this.format && this.options.default_format) {
        this.format = this.options.default_format;
      }

      if (this.options.format) {
        this.format = this.options.format;
      }
      /* Specific format */


      if (this.format) {
        /* Text Area */
        if (this.format === 'textarea') {
          this.input_type = 'textarea';
          this.input = this.theme.getTextareaInput();
          /* Range Input */
        } else if (this.format === 'range') {
          this.input_type = 'range';
          var min = this.schema.minimum || 0;
          var max = this.schema.maximum || Math.max(100, min + 1);
          var step = 1;

          if (this.schema.multipleOf) {
            if (min % this.schema.multipleOf) min = Math.ceil(min / this.schema.multipleOf) * this.schema.multipleOf;
            if (max % this.schema.multipleOf) max = Math.floor(max / this.schema.multipleOf) * this.schema.multipleOf;
            step = this.schema.multipleOf;
          }

          this.input = this.theme.getRangeInput(min, max, step);
          /* HTML5 Input type */
        } else {
          this.input_type = 'text';

          if (['button', 'checkbox', 'color', 'date', 'datetime-local', 'email', 'file', 'hidden', 'image', 'month', 'number', 'password', 'radio', 'reset', 'search', 'submit', 'tel', 'text', 'time', 'url', 'week'].includes(this.format)) {
            this.input_type = this.format;
          }

          this.input = this.theme.getFormInputField(this.input_type);
        }
        /* Normal text input */

      } else {
        this.input_type = 'text';
        this.input = this.theme.getFormInputField(this.input_type);
      }
      /* minLength, maxLength, and pattern */


      if (typeof this.schema.maxLength !== 'undefined') this.input.setAttribute('maxlength', this.schema.maxLength);
      if (typeof this.schema.pattern !== 'undefined') this.input.setAttribute('pattern', this.schema.pattern);else if (typeof this.schema.minLength !== 'undefined') this.input.setAttribute('pattern', ".{".concat(this.schema.minLength, ",}"));

      if (this.options.compact) {
        this.container.classList.add('compact');
      } else if (this.options.input_width) this.input.style.width = this.options.input_width;

      if (this.schema.readOnly || this.schema.readonly || this.schema.template) {
        this.always_disabled = true;
        this.input.setAttribute('readonly', 'true');
      }
      /* Set custom attributes on input element. Parameter is array of protected keys. Empty array if none. */


      this.setInputAttributes(['maxlength', 'pattern', 'readonly', 'min', 'max', 'step']);
      this.input.addEventListener('change', function (e) {
        e.preventDefault();
        e.stopPropagation();
        /* Don't allow changing if this field is a template */

        if (_this.schema.template) {
          e.currentTarget.value = _this.value;
          return;
        }

        var val = e.currentTarget.value;
        /* sanitize value */

        var sanitized = _this.sanitize(val);

        if (val !== sanitized) {
          e.currentTarget.value = sanitized;
        }

        _this.is_dirty = true;

        _this.refreshValue();

        _this.onChange(true);
      });
      if (this.options.input_height) this.input.style.height = this.options.input_height;

      if (this.options.expand_height) {
        this.adjust_height = function (el) {
          if (!el) return;
          var i;
          var ch = el.offsetHeight;
          /* Input too short */

          if (el.offsetHeight < el.scrollHeight) {
            i = 0;

            while (el.offsetHeight < el.scrollHeight + 3) {
              if (i > 100) break;
              i++;
              ch++;
              el.style.height = "".concat(ch, "px");
            }
          } else {
            i = 0;

            while (el.offsetHeight >= el.scrollHeight + 3) {
              if (i > 100) break;
              i++;
              ch--;
              el.style.height = "".concat(ch, "px");
            }

            el.style.height = "".concat(ch + 1, "px");
          }
        };

        this.input.addEventListener('keyup', function (e) {
          _this.adjust_height(e.currentTarget);
        });
        this.input.addEventListener('change', function (e) {
          _this.adjust_height(e.currentTarget);
        });
        this.adjust_height();
      }

      if (this.format) this.input.setAttribute('data-schemaformat', this.format);
      var input = this.input;

      if (this.format === 'range') {
        input = this.theme.getRangeControl(this.input, this.theme.getRangeOutput(this.input, this.schema["default"] || Math.max(this.schema.minimum || 0, 0)));
      }

      this.control = this.theme.getFormControl(this.label, input, this.description, this.infoButton);
      this.container.appendChild(this.control);
      /* Any special formatting that needs to happen after the input is added to the dom */

      window.requestAnimationFrame(function () {
        /* Skip in case the input is only a temporary editor, */

        /* otherwise, in the case of an ace_editor creation, */

        /* it will generate an error trying to append it to the missing parentNode */
        if (_this.input.parentNode) _this.afterInputReady();
        if (_this.adjust_height) _this.adjust_height(_this.input);
      });
      /* Compile and store the template */

      if (this.schema.template) {
        var callback = this.expandCallbacks('template', {
          template: this.schema.template
        });
        if (typeof callback.template === 'function') this.template = callback.template;else this.template = this.jsoneditor.compileTemplate(this.schema.template, this.template_engine);
        this.refreshValue();
      } else {
        this.refreshValue();
      }
    }
  }, {
    key: "setupCleave",
    value: function setupCleave(el) {
      /* Enable cleave.js support if library is loaded and config is available */
      var options = this.expandCallbacks('cleave', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.defaults.options.cleave || {}, this.options.cleave || {}));

      if (_typeof(options) === 'object' && Object.keys(options).length > 0) {
        this.cleave_instance = new window.Cleave(el, options);
      }
    }
  }, {
    key: "setupImask",
    value: function setupImask(el) {
      /* Enable imask.js support if library is loaded and config is available */
      var options = this.expandCallbacks('imask', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.defaults.options.imask || {}, this.options.imask || {}));

      if (_typeof(options) === 'object' && Object.keys(options).length > 0) {
        this.imask_instance = window.IMask(el, this.ajustIMaskOptions(options));
      }
    }
  }, {
    key: "ajustIMaskOptions",
    value: function ajustIMaskOptions(obj) {
      var _this2 = this;

      /* iMask config format is not JSON friendly, so function and regex based mask */

      /* properties have to be adjusted from string to the correct format */
      Object.keys(obj).forEach(function (prop) {
        if (obj[prop] === Object(obj[prop])) obj[prop] = _this2.ajustIMaskOptions(obj[prop]);else if (prop === 'mask') {
          if (obj[prop].substr(0, 6) === 'regex:') {
            var regExMatch = obj[prop].match(/^regex:\/(.*)\/([gimsuy]*)$/);

            if (regExMatch !== null) {
              try {
                obj[prop] = new RegExp(regExMatch[1], regExMatch[2]);
              } catch (e) {}
            }
          } else obj[prop] = _this2.getGlobalPropertyFromString(obj[prop]);
        }
      });
      return obj;
    }
  }, {
    key: "getGlobalPropertyFromString",
    value: function getGlobalPropertyFromString(strValue) {
      if (!strValue.includes('.')) {
        if (typeof window[strValue] !== 'undefined') {
          return window[strValue];
        }
      } else {
        var arrParts = strValue.split('.');
        var obj = arrParts[0];
        var prop = arrParts[1];

        if (typeof window[obj] !== 'undefined' && typeof window[obj][prop] !== 'undefined') {
          return window[obj][prop];
        }
      }
      /* just a string */


      return strValue;
    }
  }, {
    key: "getValue",
    value: function getValue() {
      if (this.imask_instance && this.dependenciesFulfilled && this.options.imask.returnUnmasked) {
        return this.imask_instance.unmaskedValue;
      }

      return _get(_getPrototypeOf(StringEditor.prototype), "getValue", this).call(this);
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        this.input.disabled = false;

        _get(_getPrototypeOf(StringEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;
      this.input.disabled = true;

      _get(_getPrototypeOf(StringEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      this.theme.afterInputReady(this.input);
      if (window.Cleave && !this.cleave_instance) this.setupCleave(this.input);else if (window.IMask && !this.imask_instance) this.setupImask(this.input);
    }
  }, {
    key: "refreshValue",
    value: function refreshValue() {
      this.value = this.input.value;
      if (typeof this.value !== 'string') this.value = '';
      this.serialized = this.value;
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.cleave_instance) this.cleave_instance.destroy();
      if (this.imask_instance) this.imask_instance.destroy();
      this.template = null;
      if (this.input && this.input.parentNode) this.input.parentNode.removeChild(this.input);
      if (this.label && this.label.parentNode) this.label.parentNode.removeChild(this.label);
      if (this.description && this.description.parentNode) this.description.parentNode.removeChild(this.description);

      _get(_getPrototypeOf(StringEditor.prototype), "destroy", this).call(this);
    }
    /**
     * This is overridden in derivative editors
     */

  }, {
    key: "sanitize",
    value: function sanitize(value) {
      return value;
    }
    /**
     * Re-calculates the value if needed
     */

  }, {
    key: "onWatchedFieldChange",
    value: function onWatchedFieldChange() {
      var vars;
      /* If this editor needs to be rendered by a macro template */

      if (this.template) {
        vars = this.getWatchedFieldValues();
        this.setValue(this.template(vars), false, true);
      }

      _get(_getPrototypeOf(StringEditor.prototype), "onWatchedFieldChange", this).call(this);
    }
  }, {
    key: "showValidationErrors",
    value: function showValidationErrors(errors) {
      var _this3 = this;

      if (this.jsoneditor.options.show_errors === 'always') {} else if (!this.is_dirty && this.previous_error_setting === this.jsoneditor.options.show_errors) return;

      this.previous_error_setting = this.jsoneditor.options.show_errors;

      var addMessage = function addMessage(messages, error) {
        if (error.path === _this3.path) {
          messages.push(error.message);
        }

        return messages;
      };

      var messages = errors.reduce(addMessage, []);

      if (messages.length) {
        this.theme.addInputError(this.input, "".concat(messages.join('. '), "."));
      } else {
        this.theme.removeInputError(this.input);
      }
    }
  }]);

  return StringEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/table.js":
/*!******************************!*\
  !*** ./src/editors/table.js ***!
  \******************************/
/*! exports provided: TableEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "TableEditor", function() { return TableEditor; });
/* harmony import */ var _array_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./array.js */ "./src/editors/array.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var TableEditor = /*#__PURE__*/function (_ArrayEditor) {
  _inherits(TableEditor, _ArrayEditor);

  var _super = _createSuper(TableEditor);

  function TableEditor() {
    _classCallCheck(this, TableEditor);

    return _super.apply(this, arguments);
  }

  _createClass(TableEditor, [{
    key: "register",
    value: function register() {
      _get(_getPrototypeOf(TableEditor.prototype), "register", this).call(this);

      if (this.rows) {
        for (var i = 0; i < this.rows.length; i++) {
          this.rows[i].register();
        }
      }
    }
  }, {
    key: "unregister",
    value: function unregister() {
      _get(_getPrototypeOf(TableEditor.prototype), "unregister", this).call(this);

      if (this.rows) {
        for (var i = 0; i < this.rows.length; i++) {
          this.rows[i].unregister();
        }
      }
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return Math.max(Math.min(12, this.width), 3);
    }
  }, {
    key: "preBuild",
    value: function preBuild() {
      var itemSchema = this.jsoneditor.expandRefs(this.schema.items || {});
      this.item_title = itemSchema.title || 'row';
      this.item_default = itemSchema["default"] || null;
      this.item_has_child_editors = itemSchema.properties || itemSchema.items;
      this.width = 12;

      _get(_getPrototypeOf(TableEditor.prototype), "preBuild", this).call(this);
    }
  }, {
    key: "build",
    value: function build() {
      this.table = this.theme.getTable();
      this.container.appendChild(this.table);
      this.thead = this.theme.getTableHead();
      this.table.appendChild(this.thead);
      this.header_row = this.theme.getTableRow();
      this.thead.appendChild(this.header_row);
      this.row_holder = this.theme.getTableBody();
      this.table.appendChild(this.row_holder);
      /* Determine the default value of array element */

      var tmp = this.getElementEditor(0, true);
      this.item_default = tmp.getDefault();
      this.width = tmp.getNumColumns() + 2;

      if (!this.options.compact) {
        this.header = document.createElement('label');
        this.header.textContent = this.getTitle();
        this.title = this.theme.getHeader(this.header);
        this.container.appendChild(this.title);
        this.title_controls = this.theme.getHeaderButtonHolder();
        this.title.appendChild(this.title_controls);

        if (this.schema.description) {
          this.description = this.theme.getDescription(this.schema.description);
          this.container.appendChild(this.description);
        }

        this.panel = this.theme.getIndentedPanel();
        this.container.appendChild(this.panel);
        this.error_holder = document.createElement('div');
        this.panel.appendChild(this.error_holder);
      } else {
        this.panel = document.createElement('div');
        this.container.appendChild(this.panel);
      }

      this.panel.appendChild(this.table);
      this.controls = this.theme.getButtonHolder();
      this.panel.appendChild(this.controls);

      if (this.item_has_child_editors) {
        var ce = tmp.getChildEditors();
        var order = tmp.property_order || Object.keys(ce);

        for (var i = 0; i < order.length; i++) {
          var th = this.theme.getTableHeaderCell(ce[order[i]].getTitle());
          if (ce[order[i]].options.hidden) th.style.display = 'none';
          this.header_row.appendChild(th);
        }
      } else {
        this.header_row.appendChild(this.theme.getTableHeaderCell(this.item_title));
      }

      tmp.destroy();
      this.row_holder.innerHTML = '';
      /* Row Controls column */

      this.controls_header_cell = this.theme.getTableHeaderCell(' ');
      this.header_row.appendChild(this.controls_header_cell);
      /* Add controls */

      this.addControls();
    }
  }, {
    key: "onChildEditorChange",
    value: function onChildEditorChange(editor) {
      this.refreshValue();

      _get(_getPrototypeOf(TableEditor.prototype), "onChildEditorChange", this).call(this);
    }
  }, {
    key: "getItemDefault",
    value: function getItemDefault() {
      return Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
        "default": this.item_default
      })["default"];
    }
  }, {
    key: "getItemTitle",
    value: function getItemTitle() {
      return this.item_title;
    }
  }, {
    key: "getElementEditor",
    value: function getElementEditor(i, ignore) {
      var schemaCopy = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.schema.items);
      var editor = this.jsoneditor.getEditorClass(schemaCopy, this.jsoneditor);
      var row = this.row_holder.appendChild(this.theme.getTableRow());
      var holder = row;

      if (!this.item_has_child_editors) {
        holder = this.theme.getTableCell();
        row.appendChild(holder);
      }

      var ret = this.jsoneditor.createEditor(editor, {
        jsoneditor: this.jsoneditor,
        schema: schemaCopy,
        container: holder,
        path: "".concat(this.path, ".").concat(i),
        parent: this,
        compact: true,
        table_row: true
      });
      ret.preBuild();

      if (!ignore) {
        ret.build();
        ret.postBuild();
        ret.controls_cell = row.appendChild(this.theme.getTableCell());
        ret.row = row;
        ret.table_controls = this.theme.getButtonHolder();
        ret.controls_cell.appendChild(ret.table_controls);
        ret.table_controls.style.margin = 0;
        ret.table_controls.style.padding = 0;
      }

      return ret;
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this.innerHTML = '';
      if (this.title && this.title.parentNode) this.title.parentNode.removeChild(this.title);
      if (this.description && this.description.parentNode) this.description.parentNode.removeChild(this.description);
      if (this.row_holder && this.row_holder.parentNode) this.row_holder.parentNode.removeChild(this.row_holder);
      if (this.table && this.table.parentNode) this.table.parentNode.removeChild(this.table);
      if (this.panel && this.panel.parentNode) this.panel.parentNode.removeChild(this.panel);
      this.rows = this.title = this.description = this.row_holder = this.table = this.panel = null;

      _get(_getPrototypeOf(TableEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "setValue",
    value: function setValue() {
      var _this = this;

      var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
      var initial = arguments.length > 1 ? arguments[1] : undefined;

      /* Make sure value has between minItems and maxItems items in it */
      if (this.schema.minItems) {
        while (value.length < this.schema.minItems) {
          value.push(this.getItemDefault());
        }
      }

      if (this.schema.maxItems && value.length > this.schema.maxItems) {
        value = value.slice(0, this.schema.maxItems);
      }

      var serialized = JSON.stringify(value);
      if (serialized === this.serialized) return;
      var numrowsChanged = false;
      value.forEach(function (val, i) {
        if (_this.rows[i]) {
          /* TODO: don't set the row's value if it hasn't changed */
          _this.rows[i].setValue(val);
        } else {
          _this.addRow(val);

          numrowsChanged = true;
        }
      });

      for (var j = value.length; j < this.rows.length; j++) {
        var holder = this.rows[j].container;

        if (!this.item_has_child_editors) {
          this.rows[j].row.parentNode.removeChild(this.rows[j].row);
        }

        this.rows[j].destroy();
        if (holder.parentNode) holder.parentNode.removeChild(holder);
        this.rows[j] = null;
        numrowsChanged = true;
      }

      this.rows = this.rows.slice(0, value.length);
      this.refreshValue();
      if (numrowsChanged || initial) this.refreshRowButtons();
      this.onChange();
      /* TODO: sortable */
    }
  }, {
    key: "refreshRowButtons",
    value: function refreshRowButtons() {
      var _this2 = this;

      /* If we currently have minItems items in the array */
      var minItems = this.schema.minItems && this.schema.minItems >= this.rows.length;
      /* If we currently have maxItems items in the array */

      var maxItems = this.schema.maxItems && this.schema.maxItems <= this.rows.length;
      var needRowButtons = false;
      this.rows.forEach(function (editor, i) {
        if (editor.delete_button) {
          /* Hide the delete button if we have minItems items */
          if (minItems) {
            editor.delete_button.style.display = 'none';
          } else {
            needRowButtons = true;
            editor.delete_button.style.display = '';
          }
        }

        if (editor.copy_button) {
          /* Hide the copy button if we have maxItems items */
          if (maxItems) {
            editor.copy_button.style.display = 'none';
          } else {
            needRowButtons = true;
            editor.copy_button.style.display = '';
          }
        }

        if (editor.moveup_button) {
          /* Hide the moveup button for the first row */
          if (i === 0) {
            editor.moveup_button.style.display = 'none';
          } else {
            needRowButtons = true;
            editor.moveup_button.style.display = '';
          }
        }

        if (editor.movedown_button) {
          /* Hide the movedown button for the last row */
          if (i === _this2.rows.length - 1) {
            editor.movedown_button.style.display = 'none';
          } else {
            needRowButtons = true;
            editor.movedown_button.style.display = '';
          }
        }
      });
      /* Show/hide controls column in table */

      this.rows.forEach(function (editor) {
        if (needRowButtons) {
          editor.controls_cell.style.display = '';
        } else {
          editor.controls_cell.style.display = 'none';
        }
      });

      if (needRowButtons) {
        this.controls_header_cell.style.display = '';
      } else {
        this.controls_header_cell.style.display = 'none';
      }

      if (!this.value.length) {
        this.table.style.display = 'none';
      } else {
        this.table.style.display = '';
      }

      var controlsNeeded = false;
      /* If there are maxItems items in the array, or configured to hide the add_row_button button, hide the button beneath the rows */

      if (maxItems || this.hide_add_button) {
        this.add_row_button.style.display = 'none';
      } else {
        this.add_row_button.style.display = '';
        controlsNeeded = true;
      }
      /* If there are minItems items in the array, or configured to hide the delete_last_row button, hide the button beneath the rows */


      if (!this.value.length || minItems || this.hide_delete_last_row_buttons) {
        this.delete_last_row_button.style.display = 'none';
      } else {
        this.delete_last_row_button.style.display = '';
        controlsNeeded = true;
      }
      /* If there are minItems items in the array, or configured to hide the remove_all_rows_button button, hide the button beneath the rows */


      if (this.value.length <= 1 || minItems || this.hide_delete_all_rows_buttons) {
        this.remove_all_rows_button.style.display = 'none';
      } else {
        this.remove_all_rows_button.style.display = '';
        controlsNeeded = true;
      }

      if (!controlsNeeded) {
        this.controls.style.display = 'none';
      } else {
        this.controls.style.display = '';
      }
    }
  }, {
    key: "refreshValue",
    value: function refreshValue() {
      var _this3 = this;

      this.value = [];
      this.rows.forEach(function (editor, i) {
        /* Get the value for this editor */
        _this3.value[i] = editor.getValue();
      });
      this.serialized = JSON.stringify(this.value);
    }
  }, {
    key: "addRow",
    value: function addRow(value) {
      var _this4 = this;

      var i = this.rows.length;
      this.rows[i] = this.getElementEditor(i);
      var controlsHolder = this.rows[i].table_controls;
      /* Buttons to delete row, copy row, move row up, and move row down */

      if (!this.hide_delete_buttons) {
        this.rows[i].delete_button = this.getButton('', 'delete', this.translate('button_delete_row_title_short'));
        this.rows[i].delete_button.classList.add('delete', 'json-editor-btntype-delete');
        this.rows[i].delete_button.setAttribute('data-i', i);
        this.rows[i].delete_button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          if (!_this4.askConfirmation()) {
            return false;
          }

          var j = e.currentTarget.getAttribute('data-i') * 1;

          var value = _this4.getValue();

          value.splice(j, 1);

          _this4.setValue(value);

          _this4.onChange(true);

          _this4.jsoneditor.trigger('deleteRow', _this4.rows[j]);
        });
        controlsHolder.appendChild(this.rows[i].delete_button);
      }

      if (this.show_copy_button) {
        this.rows[i].copy_button = this.getButton('', 'copy', this.translate('button_copy_row_title_short'));
        this.rows[i].copy_button.classList.add('copy', 'json-editor-btntype-copy');
        this.rows[i].copy_button.setAttribute('data-i', i);
        this.rows[i].copy_button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var j = e.currentTarget.getAttribute('data-i') * 1;

          var value = _this4.getValue();

          value.splice(j + 1, 0, value[j]);

          _this4.setValue(value);

          _this4.onChange(true);

          _this4.jsoneditor.trigger('copyRow', _this4.rows[j + 1]);
        });
        controlsHolder.appendChild(this.rows[i].copy_button);
      }

      if (!this.hide_move_buttons) {
        this.rows[i].moveup_button = this.getButton('', 'moveup', this.translate('button_move_up_title'));
        this.rows[i].moveup_button.classList.add('moveup', 'json-editor-btntype-move');
        this.rows[i].moveup_button.setAttribute('data-i', i);
        this.rows[i].moveup_button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var j = e.currentTarget.getAttribute('data-i') * 1;

          var value = _this4.getValue();

          value.splice(j - 1, 0, value.splice(j, 1)[0]);

          _this4.setValue(value);

          _this4.onChange(true);

          _this4.jsoneditor.trigger('moveRow', _this4.rows[j - 1]);
        });
        controlsHolder.appendChild(this.rows[i].moveup_button);
      }

      if (!this.hide_move_buttons) {
        this.rows[i].movedown_button = this.getButton('', 'movedown', this.translate('button_move_down_title'));
        this.rows[i].movedown_button.classList.add('movedown', 'json-editor-btntype-move');
        this.rows[i].movedown_button.setAttribute('data-i', i);
        this.rows[i].movedown_button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var j = e.currentTarget.getAttribute('data-i') * 1;

          var value = _this4.getValue();

          value.splice(j + 1, 0, value.splice(j, 1)[0]);

          _this4.setValue(value);

          _this4.onChange(true);

          _this4.jsoneditor.trigger('moveRow', _this4.rows[j + 1]);
        });
        controlsHolder.appendChild(this.rows[i].movedown_button);
      }

      if (value) this.rows[i].setValue(value);
    }
  }, {
    key: "addControls",
    value: function addControls() {
      var _this5 = this;

      this.collapsed = false;
      this.toggle_button = this.getButton('', 'collapse', this.translate('button_collapse'));
      this.toggle_button.classList.add('json-editor-btntype-toggle');
      this.toggle_button.style.margin = '0 10px 0 0';

      if (this.title_controls) {
        this.title.insertBefore(this.toggle_button, this.title.childNodes[0]);
        this.toggle_button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          if (_this5.collapsed) {
            _this5.collapsed = false;
            _this5.panel.style.display = '';

            _this5.setButtonText(e.currentTarget, '', 'collapse', _this5.translate('button_collapse'));
          } else {
            _this5.collapsed = true;
            _this5.panel.style.display = 'none';

            _this5.setButtonText(e.currentTarget, '', 'expand', _this5.translate('button_expand'));
          }
        });
        /* If it should start collapsed */

        if (this.options.collapsed) {
          Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["trigger"])(this.toggle_button, 'click');
        }
        /* Collapse button disabled */


        if (this.schema.options && typeof this.schema.options.disable_collapse !== 'undefined') {
          if (this.schema.options.disable_collapse) this.toggle_button.style.display = 'none';
        } else if (this.jsoneditor.options.disable_collapse) {
          this.toggle_button.style.display = 'none';
        }
      }
      /* Add "new row" and "delete last" buttons below editor */


      this.add_row_button = this.getButton(this.getItemTitle(), 'add', this.translate('button_add_row_title', [this.getItemTitle()]));
      this.add_row_button.classList.add('json-editor-btntype-add');
      this.add_row_button.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var editor = _this5.addRow();

        _this5.refreshValue();

        _this5.refreshRowButtons();

        _this5.onChange(true);

        _this5.jsoneditor.trigger('addRow', editor);
      });
      this.controls.appendChild(this.add_row_button);
      this.delete_last_row_button = this.getButton(this.translate('button_delete_last', [this.getItemTitle()]), 'subtract', this.translate('button_delete_last_title', [this.getItemTitle()]));
      this.delete_last_row_button.classList.add('json-editor-btntype-deletelast');
      this.delete_last_row_button.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!_this5.askConfirmation()) {
          return false;
        }

        var rows = _this5.getValue();

        var editor = rows.pop();

        _this5.setValue(rows);

        _this5.onChange(true);

        _this5.jsoneditor.trigger('deleteRow', editor);
      });
      this.controls.appendChild(this.delete_last_row_button);
      this.remove_all_rows_button = this.getButton(this.translate('button_delete_all'), 'delete', this.translate('button_delete_all_title'));
      this.remove_all_rows_button.classList.add('json-editor-btntype-deleteall');
      this.remove_all_rows_button.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!_this5.askConfirmation()) {
          return false;
        }

        _this5.setValue([]);

        _this5.onChange(true);

        _this5.jsoneditor.trigger('deleteAllRows');
      });
      this.controls.appendChild(this.remove_all_rows_button);
    }
  }]);

  return TableEditor;
}(_array_js__WEBPACK_IMPORTED_MODULE_0__["ArrayEditor"]);

/***/ }),

/***/ "./src/editors/upload.js":
/*!*******************************!*\
  !*** ./src/editors/upload.js ***!
  \*******************************/
/*! exports provided: UploadEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "UploadEditor", function() { return UploadEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var UploadEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(UploadEditor, _AbstractEditor);

  var _super = _createSuper(UploadEditor);

  function UploadEditor() {
    _classCallCheck(this, UploadEditor);

    return _super.apply(this, arguments);
  }

  _createClass(UploadEditor, [{
    key: "getNumColumns",
    value: function getNumColumns() {
      return 4;
    }
  }, {
    key: "build",
    value: function build() {
      var _this = this;

      if (!this.options.compact) this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      if (this.schema.description) this.description = this.theme.getFormInputDescription(this.schema.description);
      if (this.options.infoText) this.infoButton = this.theme.getInfoButton(this.options.infoText);
      /* Editor options */

      this.options = this.expandCallbacks('upload', Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, {
        title: 'Browse',
        icon: '',
        auto_upload: false,

        /* Trigger file upload button automatically */
        hide_input: false,

        /* Hide the Browse button and name display (Only works if 'enable_drag_drop' is true) */
        enable_drag_drop: false,

        /* Enable Drag&Drop uploading */
        drop_zone_text: 'Drag & Drop file here',

        /* Text displayed in dropzone box */
        drop_zone_top: false,

        /* Position of dropzone. true=before button input, false=after button input */
        alt_drop_zone: '',

        /* Alternate DropZone DOM selector (Can be created inside another property) */
        mime_type: '',

        /* If set, restricts to mime type(s). Can be either a string or an array */
        max_upload_size: 0,

        /* Maximum file size allowed. 0 = no limit */
        upload_handler: function upload_handler(jseditor, type, file, cbs) {
          /* Default dummy test upload handler */
          window.alert("No upload_handler defined for \"".concat(jseditor.path, "\". You must create your own handler to enable upload to server"));
        }
      }, this.defaults.options.upload || {}, this.options.upload || {}));
      this.options.mime_type = this.options.mime_type ? [].concat(this.options.mime_type) : [];
      /* Input that holds the base64 string */

      this.input = this.theme.getFormInputField('hidden');
      this.container.appendChild(this.input);
      /* Don't show uploader if this is readonly */

      if (!this.schema.readOnly && !this.schema.readonly) {
        if (typeof this.options.upload_handler !== 'function') throw new Error('Upload handler required for upload editor');
        /* File uploader */

        this.uploader = this.theme.getFormInputField('file');
        this.uploader.style.display = 'none';
        if (this.options.mime_type.length) this.uploader.setAttribute('accept', this.options.mime_type);

        if (!(this.options.enable_drag_drop === true && this.options.hide_input === true)) {
          /* Pass click to this.uploader element */
          this.clickHandler = function (e) {
            _this.uploader.dispatchEvent(new window.MouseEvent('click', {
              view: window,
              bubbles: true,
              cancelable: false
            }));
          };
          /* Browse button */


          this.browseButton = this.getButton(this.options.title, this.options.icon, this.options.title);
          this.browseButton.addEventListener('click', this.clickHandler);
          /* Display field */

          this.fileDisplay = this.theme.getFormInputField('input');
          this.fileDisplay.setAttribute('readonly', true);
          this.fileDisplay.value = 'No file selected.';
          this.fileDisplay.addEventListener('dblclick', this.clickHandler);
          this.fileUploadGroup = this.theme.getInputGroup(this.fileDisplay, [this.browseButton]);

          if (!this.fileUploadGroup) {
            /* Themes that doesn't support input grouping */
            this.fileUploadGroup = document.createElement('div');
            this.fileUploadGroup.appendChild(this.fileDisplay);
            this.fileUploadGroup.appendChild(this.browseButton);
          }
        }
        /* Drag&Drop upload enabled */


        if (this.options.enable_drag_drop === true) {
          /* Alternate DropZone defined */
          if (this.options.alt_drop_zone !== '') {
            this.altDropZone = document.querySelector(this.options.alt_drop_zone);
            if (this.altDropZone) this.dropZone = this.altDropZone;else throw new Error("Error: alt_drop_zone selector \"".concat(this.options.alt_drop_zone, "\" not found!"));
          } else this.dropZone = this.theme.getDropZone(this.options.drop_zone_text);

          if (this.dropZone) {
            this.dropZone.classList.add('upload-dropzone');
            this.dropZone.addEventListener('dblclick', this.clickHandler);
          }
        }
        /* Triggered after file have been selected */


        this.uploadHandler = function (e) {
          e.preventDefault();
          e.stopPropagation();
          var files = e.target.files || e.dataTransfer.files;

          if (files && files.length) {
            if (_this.options.max_upload_size !== 0 && files[0].size > _this.options.max_upload_size) {
              _this.theme.addInputError(_this.uploader, "Filesize too large. Max size is ".concat(_this.options.max_upload_size));
            } else if (_this.options.mime_type.length !== 0 && !_this.isValidMimeType(files[0].type, _this.options.mime_type)) {
              _this.theme.addInputError(_this.uploader, "Wrong file format. Allowed format(s): ".concat(_this.options.mime_type.toString()));
            } else {
              if (_this.fileDisplay) _this.fileDisplay.value = files[0].name;
              var fr = new window.FileReader();

              fr.onload = function (evt) {
                _this.preview_value = evt.target.result;

                _this.refreshPreview(files);

                _this.onChange(true);

                fr = null;
              };

              fr.readAsDataURL(files[0]);
            }
          }
        };

        this.uploader.addEventListener('change', this.uploadHandler);
        /* Drag&Drop Event Handler */

        this.dragHandler = function (e) {
          var files = e.dataTransfer.items || e.dataTransfer.files;

          var validType = files && files.length && (_this.options.mime_type.length === 0 || _this.isValidMimeType(files[0].type, _this.options.mime_type));

          var validZone = e.currentTarget.classList && e.currentTarget.classList.contains('upload-dropzone') && validType;

          switch ((e.currentTarget === window ? 'w_' : 'e_') + e.type) {
            case 'w_drop':
            case 'w_dragover':
              /* prevent default browser action if dropped outside dropzone */
              if (!validZone) e.dataTransfer.dropEffect = 'none';
              break;

            case 'e_dragenter':
              {
                if (validZone) {
                  _this.dropZone.classList.add('valid-dropzone');

                  e.dataTransfer.dropEffect = 'copy';
                } else _this.dropZone.classList.add('invalid-dropzone');

                break;
              }

            case 'e_dragover':
              {
                if (validZone) e.dataTransfer.dropEffect = 'copy';
                break;
              }

            case 'e_dragleave':
              _this.dropZone.classList.remove('valid-dropzone', 'invalid-dropzone');

              break;

            case 'e_drop':
              {
                _this.dropZone.classList.remove('valid-dropzone', 'invalid-dropzone');

                if (validZone) _this.uploadHandler(e);
                break;
              }
          }

          if (!validZone) e.preventDefault();
        };
        /* Set Drag'n'Drop handlers */


        if (this.options.enable_drag_drop === true) {
          ['dragover', 'drop'].forEach(function (ev) {
            window.addEventListener(ev, _this.dragHandler, true);
          });
          ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (ev) {
            _this.dropZone.addEventListener(ev, _this.dragHandler, true);
          });
        }
      }

      this.preview = document.createElement('div');
      this.control = this.input.controlgroup = this.theme.getFormControl(this.label, this.uploader || this.input, this.description, this.infoButton);
      if (this.uploader) this.uploader.controlgroup = this.control;
      var inputNode = this.uploader || this.input;
      var elements = document.createElement('div');
      if (this.dropZone && !this.altDropZone && this.options.drop_zone_top === true) elements.appendChild(this.dropZone);
      if (this.fileUploadGroup) elements.appendChild(this.fileUploadGroup);
      if (this.dropZone && !this.altDropZone && this.options.drop_zone_top !== true) elements.appendChild(this.dropZone);
      elements.appendChild(this.preview);
      inputNode.parentNode.insertBefore(elements, inputNode.nextSibling);
      this.container.appendChild(this.control);
      /* Any special formatting that needs to happen after the input is added to the dom */

      window.requestAnimationFrame(function () {
        _this.afterInputReady();
      });
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      var _this2 = this;

      if (this.value) {
        var img = document.createElement('img');
        img.style.maxWidth = '100%';
        img.style.maxHeight = '100px';

        img.onload = function (event) {
          _this2.preview.appendChild(img);
        };

        img.onerror = function (error) {
          console.error('upload error', error, error.currentTarget);
        };

        img.src = this.container.querySelector('a').href;
      }

      this.theme.afterInputReady(this.input);
    }
  }, {
    key: "refreshPreview",
    value: function refreshPreview(files) {
      var _this3 = this;

      if (this.last_preview === this.preview_value) return;
      this.last_preview = this.preview_value;
      this.preview.innerHTML = '';
      if (!this.preview_value) return;
      var file = files[0];
      /* mime type extracted from file data. More exact than the one in the file object */

      var mime = this.preview_value.match(/^data:([^;,]+)[;,]/);
      file.mimeType = mime ? mime[1] : 'unknown';

      if (file.size > 0) {
        /* Format bytes as KB/MB etc. with 2 decimals */
        var i = Math.floor(Math.log(file.size) / Math.log(1024));
        file.formattedSize = "".concat(parseFloat((file.size / Math.pow(1024, i)).toFixed(2)), " ").concat(['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][i]);
      } else file.formattedSize = '0 Bytes';

      var uploadButton = this.getButton('Upload', 'upload', 'Upload');
      uploadButton.addEventListener('click', function (event) {
        event.preventDefault();
        uploadButton.setAttribute('disabled', 'disabled');

        _this3.theme.removeInputError(_this3.uploader);

        if (_this3.theme.getProgressBar) {
          _this3.progressBar = _this3.theme.getProgressBar();

          _this3.preview.appendChild(_this3.progressBar);
        }

        _this3.options.upload_handler(_this3.path, file, {
          success: function success(url) {
            this.setValue(url);
            if (this.parent) this.parent.onChildEditorChange(this);else this.jsoneditor.onChange();
            if (this.progressBar) this.preview.removeChild(this.progressBar);
            uploadButton.removeAttribute('disabled');
          },
          failure: function failure(error) {
            this.theme.addInputError(this.uploader, error);
            if (this.progressBar) this.preview.removeChild(this.progressBar);
            uploadButton.removeAttribute('disabled');
          },
          updateProgress: function updateProgress(progress) {
            if (this.progressBar) {
              if (progress) this.theme.updateProgressBar(this.progressBar, progress);else this.theme.updateProgressBarUnknown(this.progressBar);
            }
          }
        });
      });
      this.preview.appendChild(this.theme.getUploadPreview(file, uploadButton, this.preview_value));

      if (this.options.auto_upload) {
        uploadButton.dispatchEvent(new window.MouseEvent('click'));
        this.preview.removeChild(uploadButton);
      }
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        if (this.uploader) this.uploader.disabled = false;

        _get(_getPrototypeOf(UploadEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(alwaysDisabled) {
      if (alwaysDisabled) this.always_disabled = true;
      if (this.uploader) this.uploader.disabled = true;

      _get(_getPrototypeOf(UploadEditor.prototype), "disable", this).call(this);
    }
  }, {
    key: "setValue",
    value: function setValue(val) {
      if (this.value !== val) {
        this.value = val;
        this.input.value = this.value;
        this.onChange();
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      var _this4 = this;

      /* Remove Drag'n'Drop handlers */
      if (this.options.enable_drag_drop === true) {
        ['dragover', 'drop'].forEach(function (ev) {
          window.removeEventListener(ev, _this4.dragHandler, true);
        });
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (ev) {
          _this4.dropZone.removeEventListener(ev, _this4.dragHandler, true);
        });
        this.dropZone.removeEventListener('dblclick', this.clickHandler);
        if (this.dropZone && this.dropZone.parentNode) this.dropZone.parentNode.removeChild(this.dropZone);
      }

      if (this.uploader && this.uploader.parentNode) {
        this.uploader.removeEventListener('change', this.uploadHandler);
        this.uploader.parentNode.removeChild(this.uploader);
      }

      if (this.browseButton && this.browseButton.parentNode) {
        this.browseButton.removeEventListener('click', this.clickHandler);
        this.browseButton.parentNode.removeChild(this.browseButton);
      }

      if (this.fileDisplay && this.fileDisplay.parentNode) {
        this.fileDisplay.removeEventListener('dblclick', this.clickHandler);
        this.fileDisplay.parentNode.removeChild(this.fileDisplay);
      }

      if (this.fileUploadGroup && this.fileUploadGroup.parentNode) this.fileUploadGroup.parentNode.removeChild(this.fileUploadGroup);
      if (this.preview && this.preview.parentNode) this.preview.parentNode.removeChild(this.preview);
      if (this.header && this.header.parentNode) this.header.parentNode.removeChild(this.header);
      if (this.input && this.input.parentNode) this.input.parentNode.removeChild(this.input);

      _get(_getPrototypeOf(UploadEditor.prototype), "destroy", this).call(this);
    }
  }, {
    key: "isValidMimeType",
    value: function isValidMimeType(mimeType, mimeTypesList) {
      return mimeTypesList.reduce(function (a, v) {
        return a || new RegExp(v.replace(/\*/g, '.*'), 'gi').test(mimeType);
      }, false);
    }
  }]);

  return UploadEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/editors/uuid.js":
/*!*****************************!*\
  !*** ./src/editors/uuid.js ***!
  \*****************************/
/*! exports provided: UuidEditor */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "UuidEditor", function() { return UuidEditor; });
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../editor.js */ "./src/editor.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var UuidEditor = /*#__PURE__*/function (_AbstractEditor) {
  _inherits(UuidEditor, _AbstractEditor);

  var _super = _createSuper(UuidEditor);

  function UuidEditor() {
    _classCallCheck(this, UuidEditor);

    return _super.apply(this, arguments);
  }

  _createClass(UuidEditor, [{
    key: "preBuild",
    value: function preBuild() {
      _get(_getPrototypeOf(UuidEditor.prototype), "preBuild", this).call(this);
      /* Use Schema "default" for setting autogenerated uuid */


      this.schema["default"] = this.uuid = this.getUuid();
      /* Force pattern validation */

      this.jsoneditor.validator.schema.properties[this.key].pattern = this.schema.pattern = '^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$';
      /* Set cleave options if no existing options is present */

      if (!this.schema.options) this.schema.options = {};

      if (!this.schema.options.cleave) {
        this.schema.options.cleave = {
          delimiters: ['-'],
          blocks: [8, 4, 4, 4, 12]
        };
      }
      /* Set field to readonly and hide field, label and description */

      /* this.schema.readonly = this.options.compact = this.options.hidden = true; */

    }
  }, {
    key: "sanitize",
    value: function sanitize(value) {
      if (!this.testUuid(value)) value = this.uuid;
      return value;
    }
  }, {
    key: "setValue",
    value: function setValue(value, initial, fromTemplate) {
      if (!this.testUuid(value)) value = this.uuid;
      this.uuid = value;

      _get(_getPrototypeOf(UuidEditor.prototype), "setValue", this).call(this, value, initial, fromTemplate);
    }
  }, {
    key: "getUuid",
    value: function getUuid() {
      /* https://stackoverflow.com/questions/105034/create-guid-uuid-in-javascript */
      var d = new Date().getTime();

      if (typeof performance !== 'undefined' && typeof performance.now === 'function') {
        d += performance.now();
        /* use high-precision timer if available */
      }

      return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = (d + Math.random() * 16) % 16 | 0;
        d = Math.floor(d / 16);
        return (c === 'x' ? r : r & 0x3 | 0x8).toString(16);
      });
    }
  }, {
    key: "testUuid",
    value: function testUuid(value) {
      return /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value);
    }
  }]);

  return UuidEditor;
}(_editor_js__WEBPACK_IMPORTED_MODULE_0__["AbstractEditor"]);

/***/ }),

/***/ "./src/iconlib.js":
/*!************************!*\
  !*** ./src/iconlib.js ***!
  \************************/
/*! exports provided: AbstractIconLib */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "AbstractIconLib", function() { return AbstractIconLib; });
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var defaultMapping = {
  collapse: '',
  expand: '',
  "delete": '',
  edit: '',
  add: '',
  cancel: '',
  save: '',
  moveup: '',
  movedown: ''
};
var AbstractIconLib = /*#__PURE__*/function () {
  function AbstractIconLib() {
    var iconPrefix = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    var mapping = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : defaultMapping;

    _classCallCheck(this, AbstractIconLib);

    this.mapping = mapping;
    this.icon_prefix = iconPrefix;
  }

  _createClass(AbstractIconLib, [{
    key: "getIconClass",
    value: function getIconClass(key) {
      return this.mapping[key] ? this.icon_prefix + this.mapping[key] : null;
    }
  }, {
    key: "getIcon",
    value: function getIcon(key) {
      var _i$classList;

      var iconclass = this.getIconClass(key);
      if (!iconclass) return null;
      var i = document.createElement('i');

      (_i$classList = i.classList).add.apply(_i$classList, _toConsumableArray(iconclass.split(' ')));

      return i;
    }
  }]);

  return AbstractIconLib;
}();

/***/ }),

/***/ "./src/iconlibs/bootstrap3.js":
/*!************************************!*\
  !*** ./src/iconlibs/bootstrap3.js ***!
  \************************************/
/*! exports provided: bootstrap3Iconlib */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "bootstrap3Iconlib", function() { return bootstrap3Iconlib; });
/* harmony import */ var _iconlib_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../iconlib.js */ "./src/iconlib.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var iconPrefix = 'glyphicon glyphicon-';
var mapping = {
  collapse: 'chevron-down',
  expand: 'chevron-right',
  "delete": 'trash',
  edit: 'pencil',
  add: 'plus',
  subtract: 'minus',
  cancel: 'floppy-remove',
  save: 'floppy-saved',
  moveup: 'arrow-up',
  moveright: 'arrow-right',
  movedown: 'arrow-down',
  moveleft: 'arrow-left',
  copy: 'copy',
  clear: 'remove-circle',
  time: 'time',
  calendar: 'calendar',
  edit_properties: 'list'
};
var bootstrap3Iconlib = /*#__PURE__*/function (_AbstractIconLib) {
  _inherits(bootstrap3Iconlib, _AbstractIconLib);

  var _super = _createSuper(bootstrap3Iconlib);

  function bootstrap3Iconlib() {
    _classCallCheck(this, bootstrap3Iconlib);

    return _super.call(this, iconPrefix, mapping);
  }

  return bootstrap3Iconlib;
}(_iconlib_js__WEBPACK_IMPORTED_MODULE_0__["AbstractIconLib"]);

/***/ }),

/***/ "./src/iconlibs/fontawesome3.js":
/*!**************************************!*\
  !*** ./src/iconlibs/fontawesome3.js ***!
  \**************************************/
/*! exports provided: fontawesome3Iconlib */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "fontawesome3Iconlib", function() { return fontawesome3Iconlib; });
/* harmony import */ var _iconlib_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../iconlib.js */ "./src/iconlib.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var iconPrefix = 'icon-';
var mapping = {
  collapse: 'chevron-down',
  expand: 'chevron-right',
  "delete": 'trash',
  edit: 'pencil',
  add: 'plus',
  subtract: 'minus',
  cancel: 'ban-circle',
  save: 'save',
  moveup: 'arrow-up',
  moveright: 'arrow-right',
  movedown: 'arrow-down',
  moveleft: 'arrow-left',
  copy: 'copy',
  clear: 'remove-circle',
  time: 'time',
  calendar: 'calendar',
  edit_properties: 'list'
};
var fontawesome3Iconlib = /*#__PURE__*/function (_AbstractIconLib) {
  _inherits(fontawesome3Iconlib, _AbstractIconLib);

  var _super = _createSuper(fontawesome3Iconlib);

  function fontawesome3Iconlib() {
    _classCallCheck(this, fontawesome3Iconlib);

    return _super.call(this, iconPrefix, mapping);
  }

  return fontawesome3Iconlib;
}(_iconlib_js__WEBPACK_IMPORTED_MODULE_0__["AbstractIconLib"]);

/***/ }),

/***/ "./src/iconlibs/fontawesome4.js":
/*!**************************************!*\
  !*** ./src/iconlibs/fontawesome4.js ***!
  \**************************************/
/*! exports provided: fontawesome4Iconlib */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "fontawesome4Iconlib", function() { return fontawesome4Iconlib; });
/* harmony import */ var _iconlib_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../iconlib.js */ "./src/iconlib.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var iconPrefix = 'fa fa-';
var mapping = {
  collapse: 'caret-square-o-down',
  expand: 'caret-square-o-right',
  "delete": 'times',
  edit: 'pencil',
  add: 'plus',
  subtract: 'minus',
  cancel: 'ban',
  save: 'save',
  moveup: 'arrow-up',
  moveright: 'arrow-right',
  movedown: 'arrow-down',
  moveleft: 'arrow-left',
  copy: 'files-o',
  clear: 'times-circle-o',
  time: 'clock-o',
  calendar: 'calendar',
  edit_properties: 'list'
};
var fontawesome4Iconlib = /*#__PURE__*/function (_AbstractIconLib) {
  _inherits(fontawesome4Iconlib, _AbstractIconLib);

  var _super = _createSuper(fontawesome4Iconlib);

  function fontawesome4Iconlib() {
    _classCallCheck(this, fontawesome4Iconlib);

    return _super.call(this, iconPrefix, mapping);
  }

  return fontawesome4Iconlib;
}(_iconlib_js__WEBPACK_IMPORTED_MODULE_0__["AbstractIconLib"]);

/***/ }),

/***/ "./src/iconlibs/fontawesome5.js":
/*!**************************************!*\
  !*** ./src/iconlibs/fontawesome5.js ***!
  \**************************************/
/*! exports provided: fontawesome5Iconlib */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "fontawesome5Iconlib", function() { return fontawesome5Iconlib; });
/* harmony import */ var _iconlib_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../iconlib.js */ "./src/iconlib.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var iconPrefix = 'fas fa-';
var mapping = {
  collapse: 'caret-down',
  expand: 'caret-right',
  "delete": 'trash',
  edit: 'pen',
  add: 'plus',
  subtract: 'minus',
  cancel: 'ban',
  save: 'save',
  moveup: 'arrow-up',
  moveright: 'arrow-right',
  movedown: 'arrow-down',
  moveleft: 'arrow-left',
  copy: 'copy',
  clear: 'times-circle',
  time: 'clock',
  calendar: 'calendar',
  edit_properties: 'list'
};
var fontawesome5Iconlib = /*#__PURE__*/function (_AbstractIconLib) {
  _inherits(fontawesome5Iconlib, _AbstractIconLib);

  var _super = _createSuper(fontawesome5Iconlib);

  function fontawesome5Iconlib() {
    _classCallCheck(this, fontawesome5Iconlib);

    return _super.call(this, iconPrefix, mapping);
  }

  return fontawesome5Iconlib;
}(_iconlib_js__WEBPACK_IMPORTED_MODULE_0__["AbstractIconLib"]);

/***/ }),

/***/ "./src/iconlibs/index.js":
/*!*******************************!*\
  !*** ./src/iconlibs/index.js ***!
  \*******************************/
/*! exports provided: iconlibs */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "iconlibs", function() { return iconlibs; });
/* harmony import */ var _bootstrap3_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./bootstrap3.js */ "./src/iconlibs/bootstrap3.js");
/* harmony import */ var _fontawesome3_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./fontawesome3.js */ "./src/iconlibs/fontawesome3.js");
/* harmony import */ var _fontawesome4_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./fontawesome4.js */ "./src/iconlibs/fontawesome4.js");
/* harmony import */ var _fontawesome5_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./fontawesome5.js */ "./src/iconlibs/fontawesome5.js");
/* harmony import */ var _jqueryui_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./jqueryui.js */ "./src/iconlibs/jqueryui.js");
/* harmony import */ var _spectre_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./spectre.js */ "./src/iconlibs/spectre.js");
// import  { bootstrap2Iconlib } from  './bootstrap2.js'



 // import  { foundation2Iconlib } from  './foundation2.js'
// import  { foundation3Iconlib } from  './foundation3.js'

 // import  { materialiconsIconlib } from  './materialicons.js'


var iconlibs = {
  // bootstrap2: bootstrap2Iconlib,
  bootstrap3: _bootstrap3_js__WEBPACK_IMPORTED_MODULE_0__["bootstrap3Iconlib"],
  fontawesome3: _fontawesome3_js__WEBPACK_IMPORTED_MODULE_1__["fontawesome3Iconlib"],
  fontawesome4: _fontawesome4_js__WEBPACK_IMPORTED_MODULE_2__["fontawesome4Iconlib"],
  fontawesome5: _fontawesome5_js__WEBPACK_IMPORTED_MODULE_3__["fontawesome5Iconlib"],
  // foundation2: foundation2Iconlib,
  // foundation3: foundation3Iconlib,
  jqueryui: _jqueryui_js__WEBPACK_IMPORTED_MODULE_4__["jqueryuiIconlib"],
  // materialicons: materialiconsIconlib,
  spectre: _spectre_js__WEBPACK_IMPORTED_MODULE_5__["spectreIconlib"]
};

/***/ }),

/***/ "./src/iconlibs/jqueryui.js":
/*!**********************************!*\
  !*** ./src/iconlibs/jqueryui.js ***!
  \**********************************/
/*! exports provided: jqueryuiIconlib */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "jqueryuiIconlib", function() { return jqueryuiIconlib; });
/* harmony import */ var _iconlib_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../iconlib.js */ "./src/iconlib.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var iconPrefix = 'ui-icon ui-icon-';
var mapping = {
  collapse: 'triangle-1-s',
  expand: 'triangle-1-e',
  "delete": 'trash',
  edit: 'pencil',
  add: 'plusthick',
  subtract: 'minusthick',
  cancel: 'closethick',
  save: 'disk',
  moveup: 'arrowthick-1-n',
  moveright: 'arrowthick-1-e',
  movedown: 'arrowthick-1-s',
  moveleft: 'arrowthick-1-w',
  copy: 'copy',
  clear: 'circle-close',
  time: 'time',
  calendar: 'calendar',
  edit_properties: 'note'
};
var jqueryuiIconlib = /*#__PURE__*/function (_AbstractIconLib) {
  _inherits(jqueryuiIconlib, _AbstractIconLib);

  var _super = _createSuper(jqueryuiIconlib);

  function jqueryuiIconlib() {
    _classCallCheck(this, jqueryuiIconlib);

    return _super.call(this, iconPrefix, mapping);
  }

  return jqueryuiIconlib;
}(_iconlib_js__WEBPACK_IMPORTED_MODULE_0__["AbstractIconLib"]);

/***/ }),

/***/ "./src/iconlibs/spectre.js":
/*!*********************************!*\
  !*** ./src/iconlibs/spectre.js ***!
  \*********************************/
/*! exports provided: spectreIconlib */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "spectreIconlib", function() { return spectreIconlib; });
/* harmony import */ var _iconlib_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../iconlib.js */ "./src/iconlib.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var iconPrefix = 'icon icon-';
var mapping = {
  collapse: 'arrow-down',
  expand: 'arrow-right',
  "delete": 'delete',
  edit: 'edit',
  add: 'plus',
  subtract: 'minus',
  cancel: 'cross',
  save: 'check',
  moveup: 'upward',
  moveright: 'forward',
  movedown: 'downward',
  moveleft: 'back',
  copy: 'copy',
  clear: 'close',
  time: 'time',
  calendar: 'bookmark',
  edit_properties: 'menu'
};
var spectreIconlib = /*#__PURE__*/function (_AbstractIconLib) {
  _inherits(spectreIconlib, _AbstractIconLib);

  var _super = _createSuper(spectreIconlib);

  function spectreIconlib() {
    _classCallCheck(this, spectreIconlib);

    return _super.call(this, iconPrefix, mapping);
  }

  return spectreIconlib;
}(_iconlib_js__WEBPACK_IMPORTED_MODULE_0__["AbstractIconLib"]);

/***/ }),

/***/ "./src/resolvers.js":
/*!**************************!*\
  !*** ./src/resolvers.js ***!
  \**************************/
/*! exports provided: resolvers */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "resolvers", function() { return resolvers; });
/* Use "multiple" as a fall back for everything */
var defaultResolver = function defaultResolver(schema) {
  return typeof schema.type !== 'string' && 'multiple';
};
/* If the type is not set but properties are defined, we can infer the type is actually object */


var object = function object(schema) {
  return !schema.type && schema.properties && 'object';
};
/* If the type is set and it's a basic type, use the primitive editor */


var primitive = function primitive(schema) {
  return typeof schema.type === 'string' && schema.type;
};
/* Use specialized editor for signatures */


var signature = function signature(schema) {
  return schema.type === 'string' && schema.format === 'signature' && 'signature';
};
/* Use the select editor for all boolean values */


var _boolean = function _boolean(schema) {
  if (schema.type === 'boolean') {
    /* If explicitly set to 'checkbox', use that */
    if (schema.format === 'checkbox' || schema.options && schema.options.checkbox) return 'checkbox';
    /* Otherwise, default to select menu */

    if (schema.format === 'select2') return 'select2';
    if (schema.format === 'selectize') return 'selectize';
    if (schema.format === 'choices') return 'choices';
    return 'select';
  }
};
/* Use the multiple editor for schemas where the `type` is set to "any" */


var any = function any(schema) {
  return schema.type === 'any' && 'multiple';
};
/* Editor for base64 encoded files */


var base64 = function base64(schema) {
  return schema.type === 'string' && schema.media && schema.media.binaryEncoding === 'base64' && 'base64';
};
/* Editor for uploading files */


var upload = function upload(schema) {
  return schema.type === 'string' && schema.format === 'url' && window.FileReader && schema.options && schema.options.upload === Object(schema.options.upload) && 'upload';
};
/* Use the table editor for arrays with the format set to `table` */


var table = function table(schema) {
  return schema.type === 'array' && schema.format === 'table' && 'table';
};
/* Use the `select` editor for dynamic enumSource enums */


var enumSource = function enumSource(schema) {
  if (schema.enumSource) {
    if (schema.format === 'radio') return 'radio';
    if (schema.format === 'select2') return 'select2';
    if (schema.format === 'selectize') return 'selectize';
    if (schema.format === 'choices') return 'choices';
    return 'select';
  }
};
/* Use the `enum` or `select` editors for schemas with enumerated properties */


var enumeratedProperties = function enumeratedProperties(schema) {
  if (schema["enum"]) {
    if (schema.type === 'array' || schema.type === 'object') return 'enum';

    if (schema.type === 'number' || schema.type === 'integer' || schema.type === 'string') {
      if (schema.format === 'radio') return 'radio';
      if (schema.format === 'select2') return 'select2';
      if (schema.format === 'selectize') return 'selectize';
      if (schema.format === 'choices') return 'choices';
      return 'select';
    }
  }
};
/* Specialized editors for arrays of strings */


var arraysOfStrings = function arraysOfStrings(schema) {
  if (schema.type === 'array' && schema.items && !Array.isArray(schema.items) && ['string', 'number', 'integer'].includes(schema.items.type)) {
    if (schema.format === 'choices') return 'arrayChoices';

    if (schema.uniqueItems) {
      /* if 'selectize' enabled it is expected to be selectized control */
      if (schema.format === 'selectize') return 'arraySelectize';
      if (schema.format === 'select2') return 'arraySelect2';
      if (schema.format !== 'table') return 'multiselect';
      /* otherwise it is select */
    }
  }
};
/* Use the multiple editor for schemas with `oneOf` or `anyOf` set */


var oneOf = function oneOf(schema) {
  return (schema.oneOf || schema.anyOf) && 'multiple';
};
/* Specialized editor for date, time and datetime-local formats */


var date = function date(schema) {
  return ['string', 'integer'].includes(schema.type) && ['date', 'time', 'datetime-local'].includes(schema.format) && 'datetime';
};
/* Use a specialized editor for starratings */


var starratings = function starratings(schema) {
  return ['string', 'integer'].includes(schema.type) && ['starrating', 'rating'].includes(schema.format) && 'starrating';
};
/* Hyper-link describeBy resolver */


var describeBy = function describeBy(schema) {
  if (schema.links) {
    for (var i = 0; i < schema.links.length; i++) {
      if (schema.links[i].rel && schema.links[i].rel.toLowerCase() === 'describedby') return 'describedBy';
    }
  }
};
/* Enable custom editor type */


var button = function button(schema) {
  return schema.format === 'button' && 'button';
};

var info = function info(schema) {
  return schema.format === 'info' && 'info';
};

var uuid = function uuid(schema) {
  return schema.type === 'string' && schema.format === 'uuid' && 'uuid';
};

var autoComplete = function autoComplete(schema) {
  return schema.type === 'string' && schema.format === 'autocomplete' && 'autocomplete';
};

var jodit = function jodit(schema) {
  return schema.type === 'string' && schema.format === 'jodit' && 'jodit';
};

var markdown = function markdown(schema) {
  return schema.type === 'string' && schema.format === 'markdown' && 'simplemde';
};

var xhtml = function xhtml(schema) {
  return schema.type === 'string' && ['xhtml', 'bbcode'].includes(schema.format) && 'sceditor';
};
/* Use the ace editor for schemas with format equals any of ace editor modes */


var aceModes = ['actionscript', 'batchfile', 'c', 'c++', 'cpp', 'coffee', 'csharp', 'css', 'dart', 'django', 'ejs', 'erlang', 'golang', 'groovy', 'handlebars', 'haskell', 'haxe', 'html', 'ini', 'jade', 'java', 'javascript', 'json', 'less', 'lisp', 'lua', 'makefile', 'matlab', 'mysql', 'objectivec', 'pascal', 'perl', 'pgsql', 'php', 'python', 'r', 'ruby', 'sass', 'scala', 'scss', 'smarty', 'sql', 'sqlserver', 'stylus', 'svg', 'twig', 'vbscript', 'xml', 'yaml'];

var ace = function ace(schema) {
  return schema.type === 'string' && aceModes.includes(schema.format) && 'ace';
};

var ip = function ip(schema) {
  return schema.type === 'string' && ['ip', 'ipv4', 'ipv6', 'hostname'].includes(schema.format) && 'ip';
};

var colorPicker = function colorPicker(schema) {
  return schema.type === 'string' && schema.format === 'color' && 'colorpicker';
};
/* Export resolvers in order of discovery, first to last */


var resolvers = [colorPicker, ip, ace, xhtml, markdown, jodit, autoComplete, uuid, info, button, describeBy, starratings, date, oneOf, arraysOfStrings, enumeratedProperties, enumSource, table, upload, base64, any, _boolean, signature, primitive, object, defaultResolver];

/***/ }),

/***/ "./src/schemaloader.js":
/*!*****************************!*\
  !*** ./src/schemaloader.js ***!
  \*****************************/
/*! exports provided: SchemaLoader */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SchemaLoader", function() { return SchemaLoader; });
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utilities.js */ "./src/utilities.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }


var SchemaLoader = /*#__PURE__*/function () {
  function SchemaLoader(options) {
    _classCallCheck(this, SchemaLoader);

    this.options = options || {};
    this.refs = this.options.refs || {};
    this.refs_with_info = {};
    this.refs_prefix = '#/counter/';
    this.refs_counter = 1;
    this._subSchema1 = {
      /* Version 3 `type` */
      type: function type(schema) {
        if (_typeof(schema.type) === 'object') {
          schema.type = this._expandSubSchema(schema.type);
        }
      },

      /* Version 3 `disallow` */
      disallow: function disallow(schema) {
        if (_typeof(schema.disallow) === 'object') {
          schema.disallow = this._expandSubSchema(schema.disallow);
        }
      },

      /* Version 4 `anyOf` */
      anyOf: function anyOf(schema) {
        var _this = this;

        Object.entries(schema.anyOf).forEach(function (_ref) {
          var _ref2 = _slicedToArray(_ref, 2),
              key = _ref2[0],
              value = _ref2[1];

          schema.anyOf[key] = _this.expandSchema(value);
        });
      },

      /* Version 4 `dependencies` (schema dependencies) */
      dependencies: function dependencies(schema) {
        var _this2 = this;

        Object.entries(schema.dependencies).forEach(function (_ref3) {
          var _ref4 = _slicedToArray(_ref3, 2),
              key = _ref4[0],
              value = _ref4[1];

          if (_typeof(value) === 'object' && !Array.isArray(value)) {
            schema.dependencies[key] = _this2.expandSchema(value);
          }
        });
      },

      /* Version 4 `not` */
      not: function not(schema) {
        schema.not = this.expandSchema(schema.not);
      }
    };
    this._subSchema2 = {
      /* allOf schemas should be merged into the parent */
      allOf: function allOf(schema, extended) {
        var _this3 = this;

        var _extended = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["extend"])({}, extended);

        Object.entries(schema.allOf).forEach(function (_ref5) {
          var _ref6 = _slicedToArray(_ref5, 2),
              key = _ref6[0],
              value = _ref6[1];

          schema.allOf[key] = _this3.expandRefs(value, true);
          _extended = _this3.extendSchemas(_extended, _this3.expandSchema(value));
        });
        delete _extended.allOf;
        return _extended;
      },

      /* extends schemas should be merged into parent */
      "extends": function _extends(schema, extended) {
        var _this4 = this;

        var _extended;
        /* If extends is a schema */


        if (!Array.isArray(schema["extends"])) {
          _extended = this.extendSchemas(extended, this.expandSchema(schema["extends"]));
        } else {
          /* If extends is an array of schemas */
          _extended = schema["extends"].reduce(function (e, s, i) {
            return _this4.extendSchemas(e, _this4.expandSchema(s));
          }, extended);
        }

        delete _extended["extends"];
        return _extended;
      },

      /* parent should be merged into oneOf schemas */
      oneOf: function oneOf(schema, extended) {
        var _this5 = this;

        var tmp = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["extend"])({}, extended);
        delete tmp.oneOf;
        schema.oneOf.reduce(function (e, s, i) {
          e.oneOf[i] = _this5.extendSchemas(_this5.expandSchema(s), tmp);
          return e;
        }, extended);
        return extended;
      }
    };
  }

  _createClass(SchemaLoader, [{
    key: "load",
    value: function load(schema, callback, fetchUrl, location) {
      var _this6 = this;

      this._loadExternalRefs(schema, function () {
        _this6._getDefinitions(schema, "".concat(fetchUrl, "#/definitions/"));

        callback(_this6.expandRefs(schema));
      }, fetchUrl, this._getFileBase(location));
    }
  }, {
    key: "expandRefs",
    value: function expandRefs(schema, recurseAllOf) {
      var _this7 = this;

      var _schema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["extend"])({}, schema);

      if (!_schema.$ref) return _schema;
      var refObj = this.refs_with_info[_schema.$ref];
      delete _schema.$ref;
      var fetchUrl = refObj.$ref.startsWith('#') ? refObj.fetchUrl : '';

      var ref = this._getRef(fetchUrl, refObj);

      if (!this.refs[ref]) {
        /* if reference not found */
        console.warn("reference:'".concat(ref, "' not found!"));
      } else if (recurseAllOf && Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["hasOwnProperty"])(this.refs[ref], 'allOf')) {
        var allOf = this.refs[ref].allOf;
        Object.keys(allOf).forEach(function (key) {
          allOf[key] = _this7.expandRefs(allOf[key], true);
        });
      }

      return this.extendSchemas(_schema, this.expandSchema(this.refs[ref]));
    }
  }, {
    key: "expandSchema",
    value: function expandSchema(schema, fileBase) {
      var _this8 = this;

      Object.entries(this._subSchema1).forEach(function (_ref7) {
        var _ref8 = _slicedToArray(_ref7, 2),
            key = _ref8[0],
            func = _ref8[1];

        if (schema[key]) {
          func.call(_this8, schema);
        }
      });
      var extended = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["extend"])({}, schema);
      Object.entries(this._subSchema2).forEach(function (_ref9) {
        var _ref10 = _slicedToArray(_ref9, 2),
            key = _ref10[0],
            func = _ref10[1];

        if (schema[key]) {
          extended = func.call(_this8, schema, extended);
        }
      });
      return this.expandRefs(extended);
    }
  }, {
    key: "_getRef",
    value: function _getRef(fetchUrl, refObj) {
      var ref = fetchUrl + refObj;
      return this.refs[ref] ? ref : fetchUrl + decodeURIComponent(refObj.$ref);
    }
  }, {
    key: "_expandSubSchema",
    value: function _expandSubSchema(subschema) {
      var _this9 = this;

      /* Array of types */
      if (Array.isArray(subschema)) return subschema.map(function (m) {
        return (typeof value === "undefined" ? "undefined" : _typeof(value)) === 'object' ? _this9.expandSchema(m) : m;
      });
      /* Schema */

      return this.expandSchema(subschema);
    }
  }, {
    key: "_getDefinitions",
    value: function _getDefinitions(schema, path) {
      var _this10 = this;

      if (schema.definitions) {
        Object.keys(schema.definitions).forEach(function (i) {
          _this10.refs[path + i] = schema.definitions[i];

          if (schema.definitions[i].definitions) {
            _this10._getDefinitions(schema.definitions[i], "".concat(path + i, "/definitions/"));
          }
        });
      }
    }
  }, {
    key: "_getExternalRefs",
    value: function _getExternalRefs(schema, fetchUrl) {
      var _this11 = this;

      var refs = {};

      var mergeRefs = function mergeRefs(newrefs) {
        return Object.keys(newrefs).forEach(function (i) {
          refs[i] = true;
        });
      };

      if (schema.$ref && _typeof(schema.$ref) !== 'object') {
        var refCounter = this.refs_prefix + this.refs_counter++;

        if (schema.$ref.substr(0, 1) !== '#' && !this.refs[schema.$ref]) {
          refs[schema.$ref] = true;
        }

        this.refs_with_info[refCounter] = {
          fetchUrl: fetchUrl,
          $ref: schema.$ref
        };
        schema.$ref = refCounter;
      }

      Object.values(schema).forEach(function (value) {
        if (!value || _typeof(value) !== 'object') return;

        if (Array.isArray(value)) {
          Object.values(value).forEach(function (e) {
            if (e && _typeof(e) === 'object') {
              mergeRefs(_this11._getExternalRefs(e, fetchUrl));
            }
          });
        } else {
          mergeRefs(_this11._getExternalRefs(value, fetchUrl));
        }
      });
      return refs;
    }
  }, {
    key: "_getFileBase",
    value: function _getFileBase(location) {
      var ajaxBase = this.options.ajaxBase;
      return typeof ajaxBase === 'undefined' ? this._getFileBaseFromFileLocation(location) : ajaxBase;
    }
  }, {
    key: "_getFileBaseFromFileLocation",
    value: function _getFileBaseFromFileLocation(fileLocationString) {
      var pathItems = fileLocationString.split('/');
      pathItems.pop();
      return "".concat(pathItems.join('/'), "/");
    }
  }, {
    key: "_isLocalUrl",
    value: function _isLocalUrl(url, fileBase) {
      return fileBase !== url.substr(0, fileBase.length) && url.substr(0, 4) !== 'http' && url.substr(0, 1) !== '/';
    }
  }, {
    key: "_loadExternalRefs",
    value: function _loadExternalRefs(schema, callback, fetchUrl, fileBase) {
      var _this12 = this;

      var refs = this._getExternalRefs(schema, fetchUrl);

      var done = 0;
      var waiting = 0;
      var callbackFired = false;
      Object.keys(refs).forEach(function (url) {
        if (_this12.refs[url]) return;
        if (!_this12.options.ajax) throw new Error("Must set ajax option to true to load external ref ".concat(url));
        _this12.refs[url] = 'loading';
        waiting++;
        var fetchUrl = _this12._isLocalUrl(url, fileBase) ? fileBase + url : url;
        var r = new XMLHttpRequest();
        r.overrideMimeType('application/json');
        r.open('GET', fetchUrl, true);
        if (_this12.options.ajaxCredentials) r.withCredentials = _this12.options.ajaxCredentials;

        r.onreadystatechange = function () {
          if (r.readyState !== 4) return;
          /* Request succeeded */

          if (r.status === 200) {
            var response;

            try {
              response = JSON.parse(r.responseText);
            } catch (e) {
              window.console.log(e);
              throw new Error("Failed to parse external ref ".concat(fetchUrl));
            }

            if (!(typeof response === 'boolean' || _typeof(response) === 'object') || response === null || Array.isArray(response)) {
              throw new Error("External ref does not contain a valid schema - ".concat(fetchUrl));
            }

            _this12.refs[url] = response;

            var _fileBase = _this12._getFileBaseFromFileLocation(fetchUrl);

            _this12._getDefinitions(response, "".concat(fetchUrl, "#/definitions/"));

            _this12._loadExternalRefs(response, function () {
              done++;

              if (done >= waiting && !callbackFired) {
                callbackFired = true;
                callback();
              }
            }, fetchUrl, _fileBase);
          } else {
            /* Request failed */
            window.console.log(r);
            throw new Error("Failed to fetch ref via ajax- ".concat(url));
          }
        };

        r.send();
      });

      if (!waiting) {
        callback();
      }
    }
  }, {
    key: "extendSchemas",
    value: function extendSchemas(obj1, obj2) {
      var _this13 = this;

      obj1 = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["extend"])({}, obj1);
      obj2 = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_0__["extend"])({}, obj2);
      var extended = {};

      var isRequiredOrDefaultProperties = function isRequiredOrDefaultProperties(prop, val) {
        return (prop === 'required' || prop === 'defaultProperties') && _typeof(val) === 'object' && Array.isArray(val);
      };

      var merge = function merge(prop, val) {
        /* Required and defaultProperties arrays should be unioned together */
        if (isRequiredOrDefaultProperties(prop, val)) {
          /* Union arrays and unique */
          extended[prop] = val.concat(obj2[prop]).reduce(function (p, c) {
            if (!p.includes(c)) p.push(c);
            return p;
          }, []);
        } else if (prop === 'type' && (typeof val === 'string' || Array.isArray(val))) {
          mergeType(val);
        } else if (_typeof(val) === 'object' && !Array.isArray(val) && val !== null) {
          /* Objects should be recursively merged */
          extended[prop] = _this13.extendSchemas(val, obj2[prop]);
        } else {
          /* Otherwise, use the first value */
          extended[prop] = val;
        }
      };

      var mergeType = function mergeType(val) {
        /* Type should be intersected and is either an array or string */

        /* Make sure we're dealing with arrays */
        if (typeof val === 'string') val = [val];
        if (typeof obj2.type === 'string') obj2.type = [obj2.type];
        /* If type is only defined in the first schema, keep it */

        if (!obj2.type || !obj2.type.length) {
          extended.type = val;
        } else {
          /* If type is defined in both schemas, do an intersect */
          extended.type = val.filter(function (n) {
            return obj2.type.includes(n);
          });
        }
        /* If there's only 1 type and it's a primitive, use a string instead of array */


        if (extended.type.length === 1 && typeof extended.type[0] === 'string') {
          extended.type = extended.type[0];
        } else if (extended.type.length === 0) {
          /* Remove the type property if it's empty */
          delete extended.type;
        }
      };

      Object.entries(obj1).forEach(function (_ref11) {
        var _ref12 = _slicedToArray(_ref11, 2),
            prop = _ref12[0],
            val = _ref12[1];

        /* If this key is also defined in obj2, merge them */
        if (typeof obj2[prop] !== 'undefined') {
          merge(prop, val);
        } else {
          /* Otherwise, just use the one in obj1 */
          extended[prop] = val;
        }
      });
      /* Properties in obj2 that aren't in obj1 */

      Object.entries(obj2).forEach(function (_ref13) {
        var _ref14 = _slicedToArray(_ref13, 2),
            prop = _ref14[0],
            val = _ref14[1];

        if (typeof obj1[prop] === 'undefined') {
          extended[prop] = val;
        }
      });
      return extended;
    }
  }]);

  return SchemaLoader;
}();

/***/ }),

/***/ "./src/templates/default.js":
/*!**********************************!*\
  !*** ./src/templates/default.js ***!
  \**********************************/
/*! exports provided: defaultTemplate */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "defaultTemplate", function() { return defaultTemplate; });
var defaultTemplate = function defaultTemplate() {
  return {
    compile: function compile(template) {
      var matches = template.match(/{{\s*([a-zA-Z0-9\-_ .]+)\s*}}/g);
      var l = matches && matches.length;
      /* Shortcut if the template contains no variables */

      if (!l) return function () {
        return template;
      };
      /* Pre-compute the search/replace functions */

      /* This drastically speeds up template execution */

      var replacements = [];

      var getReplacement = function getReplacement(i) {
        var p = matches[i].replace(/[{}]+/g, '').trim().split('.');
        var n = p.length;
        var func;

        if (n > 1) {
          var cur;

          func = function func(vars) {
            cur = vars;

            for (i = 0; i < n; i++) {
              cur = cur[p[i]];
              if (!cur) break;
            }

            return cur;
          };
        } else {
          p = p[0];

          func = function func(vars) {
            return vars[p];
          };
        }

        replacements.push({
          s: matches[i],
          r: func
        });
      };

      for (var i = 0; i < l; i++) {
        getReplacement(i);
      }
      /* The compiled function */


      return function (vars) {
        var ret = "".concat(template);
        var r;

        for (i = 0; i < l; i++) {
          r = replacements[i];
          ret = ret.replace(r.s, r.r(vars));
        }

        return ret;
      };
    }
  };
};

/***/ }),

/***/ "./src/templates/ejs.js":
/*!******************************!*\
  !*** ./src/templates/ejs.js ***!
  \******************************/
/*! exports provided: ejsTemplate */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ejsTemplate", function() { return ejsTemplate; });
var ejsTemplate = function ejsTemplate() {
  if (!window.EJS) return false;
  return {
    compile: function compile(template) {
      var compiled = new window.EJS({
        text: template
      });
      return function (context) {
        return compiled.render(context);
      };
    }
  };
};

/***/ }),

/***/ "./src/templates/handlebars.js":
/*!*************************************!*\
  !*** ./src/templates/handlebars.js ***!
  \*************************************/
/*! exports provided: handlebarsTemplate */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "handlebarsTemplate", function() { return handlebarsTemplate; });
var handlebarsTemplate = function handlebarsTemplate() {
  return window.Handlebars;
};

/***/ }),

/***/ "./src/templates/hogan.js":
/*!********************************!*\
  !*** ./src/templates/hogan.js ***!
  \********************************/
/*! exports provided: hoganTemplate */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "hoganTemplate", function() { return hoganTemplate; });
var hoganTemplate = function hoganTemplate() {
  if (!window.Hogan) return false;
  return {
    compile: function compile(template) {
      var compiled = window.Hogan.compile(template);
      return function (context) {
        return compiled.render(context);
      };
    }
  };
};

/***/ }),

/***/ "./src/templates/index.js":
/*!********************************!*\
  !*** ./src/templates/index.js ***!
  \********************************/
/*! exports provided: templates */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "templates", function() { return templates; });
/* harmony import */ var _default_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./default.js */ "./src/templates/default.js");
/* harmony import */ var _ejs_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ejs.js */ "./src/templates/ejs.js");
/* harmony import */ var _handlebars_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./handlebars.js */ "./src/templates/handlebars.js");
/* harmony import */ var _hogan_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./hogan.js */ "./src/templates/hogan.js");
/* harmony import */ var _lodash_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./lodash.js */ "./src/templates/lodash.js");
/* harmony import */ var _markup_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./markup.js */ "./src/templates/markup.js");
/* harmony import */ var _mustache_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./mustache.js */ "./src/templates/mustache.js");
/* harmony import */ var _swig_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./swig.js */ "./src/templates/swig.js");
/* harmony import */ var _underscore_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./underscore.js */ "./src/templates/underscore.js");









var templates = {
  "default": _default_js__WEBPACK_IMPORTED_MODULE_0__["defaultTemplate"],
  ejs: _ejs_js__WEBPACK_IMPORTED_MODULE_1__["ejsTemplate"],
  handlebars: _handlebars_js__WEBPACK_IMPORTED_MODULE_2__["handlebarsTemplate"],
  hogan: _hogan_js__WEBPACK_IMPORTED_MODULE_3__["hoganTemplate"],
  lodash: _lodash_js__WEBPACK_IMPORTED_MODULE_4__["lodashTemplate"],
  markup: _markup_js__WEBPACK_IMPORTED_MODULE_5__["markupTemplate"],
  mustache: _mustache_js__WEBPACK_IMPORTED_MODULE_6__["mustacheTemplate"],
  swig: _swig_js__WEBPACK_IMPORTED_MODULE_7__["swigTemplate"],
  underscore: _underscore_js__WEBPACK_IMPORTED_MODULE_8__["underscoreTemplate"]
};

/***/ }),

/***/ "./src/templates/lodash.js":
/*!*********************************!*\
  !*** ./src/templates/lodash.js ***!
  \*********************************/
/*! exports provided: lodashTemplate */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "lodashTemplate", function() { return lodashTemplate; });
var lodashTemplate = function lodashTemplate() {
  if (!window._) return false;
  return {
    compile: function compile(template) {
      return function (context) {
        return window._.template(template)(context);
      };
    }
  };
};

/***/ }),

/***/ "./src/templates/markup.js":
/*!*********************************!*\
  !*** ./src/templates/markup.js ***!
  \*********************************/
/*! exports provided: markupTemplate */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "markupTemplate", function() { return markupTemplate; });
var markupTemplate = function markupTemplate() {
  if (!window.Mark || !window.Mark.up) return false;
  return {
    compile: function compile(template) {
      return function (context) {
        return window.Mark.up(template, context);
      };
    }
  };
};

/***/ }),

/***/ "./src/templates/mustache.js":
/*!***********************************!*\
  !*** ./src/templates/mustache.js ***!
  \***********************************/
/*! exports provided: mustacheTemplate */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "mustacheTemplate", function() { return mustacheTemplate; });
var mustacheTemplate = function mustacheTemplate() {
  if (!window.Mustache) return false;
  return {
    compile: function compile(template) {
      return function (view) {
        return window.Mustache.render(template, view);
      };
    }
  };
};

/***/ }),

/***/ "./src/templates/swig.js":
/*!*******************************!*\
  !*** ./src/templates/swig.js ***!
  \*******************************/
/*! exports provided: swigTemplate */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "swigTemplate", function() { return swigTemplate; });
var swigTemplate = function swigTemplate() {
  return window.swig;
};

/***/ }),

/***/ "./src/templates/underscore.js":
/*!*************************************!*\
  !*** ./src/templates/underscore.js ***!
  \*************************************/
/*! exports provided: underscoreTemplate */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "underscoreTemplate", function() { return underscoreTemplate; });
var underscoreTemplate = function underscoreTemplate() {
  if (!window._) return false;
  return {
    compile: function compile(template) {
      return function (context) {
        return window._.template(template)(context);
      };
    }
  };
};

/***/ }),

/***/ "./src/theme.js":
/*!**********************!*\
  !*** ./src/theme.js ***!
  \**********************/
/*! exports provided: AbstractTheme */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "AbstractTheme", function() { return AbstractTheme; });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var matchKey = ['matches', 'webkitMatchesSelector', 'mozMatchesSelector', 'msMatchesSelector', 'oMatchesSelector'].find(function (key) {
  return key in document.documentElement;
});
var AbstractTheme = /*#__PURE__*/function () {
  function AbstractTheme(jsoneditor) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {
      disable_theme_rules: false
    };

    _classCallCheck(this, AbstractTheme);

    this.jsoneditor = jsoneditor;
    /* Theme config options that allows changing various aspects of the output */

    this.options = options;
  }

  _createClass(AbstractTheme, [{
    key: "getContainer",
    value: function getContainer() {
      return document.createElement('div');
    }
  }, {
    key: "getFloatRightLinkHolder",
    value: function getFloatRightLinkHolder() {
      var el = document.createElement('div');
      el.style = el.style || {};
      el.style.cssFloat = 'right';
      el.style.marginLeft = '10px';
      return el;
    }
  }, {
    key: "getModal",
    value: function getModal() {
      var el = document.createElement('div');
      el.style.backgroundColor = 'white';
      el.style.border = '1px solid black';
      el.style.boxShadow = '3px 3px black';
      el.style.position = 'absolute';
      el.style.zIndex = '10';
      el.style.display = 'none';
      return el;
    }
  }, {
    key: "getGridContainer",
    value: function getGridContainer() {
      var el = document.createElement('div');
      return el;
    }
  }, {
    key: "getGridRow",
    value: function getGridRow() {
      var el = document.createElement('div');
      el.classList.add('row');
      return el;
    }
  }, {
    key: "getGridColumn",
    value: function getGridColumn() {
      var el = document.createElement('div');
      return el;
    }
  }, {
    key: "setGridColumnSize",
    value: function setGridColumnSize(el, size) {}
  }, {
    key: "getLink",
    value: function getLink(text) {
      var el = document.createElement('a');
      el.setAttribute('href', '#');
      el.appendChild(document.createTextNode(text));
      return el;
    }
  }, {
    key: "disableHeader",
    value: function disableHeader(header) {
      header.style.color = '#ccc';
    }
  }, {
    key: "disableLabel",
    value: function disableLabel(label) {
      label.style.color = '#ccc';
    }
  }, {
    key: "enableHeader",
    value: function enableHeader(header) {
      header.style.color = '';
    }
  }, {
    key: "enableLabel",
    value: function enableLabel(label) {
      label.style.color = '';
    }
  }, {
    key: "getInfoButton",
    value: function getInfoButton(text) {
      var icon = document.createElement('span');
      icon.innerText = 'ⓘ';
      icon.style.fontSize = '16px';
      icon.style.fontWeight = 'bold';
      icon.style.padding = '.25rem';
      icon.style.position = 'relative';
      icon.style.display = 'inline-block';
      var tooltip = document.createElement('span');
      tooltip.style.fontSize = '12px';
      icon.style.fontWeight = 'normal';
      tooltip.style['font-family'] = 'sans-serif';
      tooltip.style.visibility = 'hidden';
      tooltip.style['background-color'] = 'rgba(50, 50, 50, .75)';
      tooltip.style.margin = '0 .25rem';
      tooltip.style.color = '#FAFAFA';
      tooltip.style.padding = '.5rem 1rem';
      tooltip.style['border-radius'] = '.25rem';
      tooltip.style.width = '20rem';
      tooltip.style.position = 'absolute';
      tooltip.innerText = text;

      icon.onmouseover = function () {
        tooltip.style.visibility = 'visible';
      };

      icon.onmouseleave = function () {
        tooltip.style.visibility = 'hidden';
      };

      icon.appendChild(tooltip);
      return icon;
    }
  }, {
    key: "getFormInputLabel",
    value: function getFormInputLabel(text, req) {
      var el = document.createElement('label');
      el.appendChild(document.createTextNode(text));
      if (req) el.classList.add('required');
      return el;
    }
  }, {
    key: "getHeader",
    value: function getHeader(text) {
      var el = document.createElement('h3');

      if (typeof text === 'string') {
        el.textContent = text;
      } else {
        el.appendChild(text);
      }

      el.style.display = 'inline-block';
      return el;
    }
  }, {
    key: "getCheckbox",
    value: function getCheckbox() {
      var el = this.getFormInputField('checkbox');
      el.style.display = 'inline-block';
      el.style.width = 'auto';
      return el;
    }
  }, {
    key: "getCheckboxLabel",
    value: function getCheckboxLabel(text, req) {
      var el = document.createElement('label');
      el.appendChild(document.createTextNode("\xA0".concat(text)));
      if (req) el.classList.add('required');
      return el;
    }
  }, {
    key: "getMultiCheckboxHolder",
    value: function getMultiCheckboxHolder(controls, label, description, infoText) {
      var el = document.createElement('div');
      el.classList.add('control-group');

      if (label) {
        label.style.display = 'block';
        el.appendChild(label);
        if (infoText) label.appendChild(infoText);
      }

      Object.values(controls).forEach(function (control) {
        control.style.display = 'inline-block';
        control.style.marginRight = '20px';
        el.appendChild(control);
      });
      if (description) el.appendChild(description);
      return el;
    }
  }, {
    key: "getFormCheckboxControl",
    value: function getFormCheckboxControl(label, input, compact) {
      var el = document.createElement('div');
      el.appendChild(label);
      input.style.width = 'auto';
      label.insertBefore(input, label.firstChild);

      if (compact) {
        this.applyStyles(el, {
          display: 'inline-block',
          marginRight: '1rem'
        });
      }

      return el;
    }
  }, {
    key: "getFormRadio",
    value: function getFormRadio(attributes) {
      var el = this.getFormInputField('radio');

      for (var key in attributes) {
        el.setAttribute(key, attributes[key]);
      }

      el.style.display = 'inline-block';
      el.style.width = 'auto';
      return el;
    }
  }, {
    key: "getFormRadioLabel",
    value: function getFormRadioLabel(text, req) {
      var el = document.createElement('label');
      el.appendChild(document.createTextNode("\xA0".concat(text)));
      if (req) el.classList.add('required');
      return el;
    }
  }, {
    key: "getFormRadioControl",
    value: function getFormRadioControl(label, input, compact) {
      var el = document.createElement('div');
      el.appendChild(label);
      input.style.width = 'auto';
      label.insertBefore(input, label.firstChild);

      if (compact) {
        this.applyStyles(el, {
          display: 'inline-block',
          marginRight: '1rem'
        });
      }

      return el;
    }
  }, {
    key: "getSelectInput",
    value: function getSelectInput(options, multiple) {
      var select = document.createElement('select');
      if (options) this.setSelectOptions(select, options);
      return select;
    }
  }, {
    key: "getSwitcher",
    value: function getSwitcher(options) {
      var switcher = this.getSelectInput(options, false);
      switcher.style.backgroundColor = 'transparent';
      switcher.style.display = 'inline-block';
      switcher.style.fontStyle = 'italic';
      switcher.style.fontWeight = 'normal';
      switcher.style.height = 'auto';
      switcher.style.marginBottom = 0;
      switcher.style.marginLeft = '5px';
      switcher.style.padding = '0 0 0 3px';
      switcher.style.width = 'auto';
      return switcher;
    }
  }, {
    key: "getSwitcherOptions",
    value: function getSwitcherOptions(switcher) {
      return switcher.getElementsByTagName('option');
    }
  }, {
    key: "setSwitcherOptions",
    value: function setSwitcherOptions(switcher, options, titles) {
      this.setSelectOptions(switcher, options, titles);
    }
  }, {
    key: "setSelectOptions",
    value: function setSelectOptions(select, options) {
      var titles = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
      select.innerHTML = '';

      for (var i = 0; i < options.length; i++) {
        var option = document.createElement('option');
        option.setAttribute('value', options[i]);
        option.textContent = titles[i] || options[i];
        select.appendChild(option);
      }
    }
  }, {
    key: "getTextareaInput",
    value: function getTextareaInput() {
      var el = document.createElement('textarea');
      el.style = el.style || {};
      el.style.width = '100%';
      el.style.height = '300px';
      el.style.boxSizing = 'border-box';
      return el;
    }
  }, {
    key: "getRangeInput",
    value: function getRangeInput(min, max, step) {
      var el = this.getFormInputField('range');
      el.setAttribute('min', min);
      el.setAttribute('max', max);
      el.setAttribute('step', step);
      return el;
    }
  }, {
    key: "getRangeOutput",
    value: function getRangeOutput(input, startvalue) {
      var output = document.createElement('output');
      output.value = startvalue || 0;

      var updateOutput = function updateOutput(e) {
        output.value = e.currentTarget.value;
      };

      input.addEventListener('change', updateOutput, false);
      input.addEventListener('input', updateOutput, false);
      return output;
    }
  }, {
    key: "getRangeControl",
    value: function getRangeControl(input, output) {
      var el = document.createElement('div');
      el.style.textAlign = 'center';
      if (output) el.appendChild(output);
      el.appendChild(input);
      return el;
    }
  }, {
    key: "getFormInputField",
    value: function getFormInputField(type) {
      var el = document.createElement('input');
      el.setAttribute('type', type);
      return el;
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady(input) {}
  }, {
    key: "getFormControl",
    value: function getFormControl(label, input, description, infoText) {
      var el = document.createElement('div');
      el.classList.add('form-control');
      if (label) el.appendChild(label);

      if ((input.type === 'checkbox' || input.type === 'radio') && label) {
        input.style.width = 'auto';
        label.insertBefore(input, label.firstChild);
        if (infoText) label.appendChild(infoText);
      } else {
        if (infoText && label) label.appendChild(infoText);
        el.appendChild(input);
      }

      if (description) el.appendChild(description);
      return el;
    }
  }, {
    key: "getIndentedPanel",
    value: function getIndentedPanel() {
      var el = document.createElement('div');
      el.style = el.style || {};
      el.style.paddingLeft = '10px';
      el.style.marginLeft = '10px';
      el.style.borderLeft = '1px solid #ccc';
      return el;
    }
  }, {
    key: "getTopIndentedPanel",
    value: function getTopIndentedPanel() {
      var el = document.createElement('div');
      el.style = el.style || {};
      el.style.paddingLeft = '10px';
      el.style.marginLeft = '10px';
      return el;
    }
  }, {
    key: "getChildEditorHolder",
    value: function getChildEditorHolder() {
      return document.createElement('div');
    }
  }, {
    key: "getDescription",
    value: function getDescription(text) {
      var el = document.createElement('p');
      if (window.DOMPurify) el.innerHTML = window.DOMPurify.sanitize(text);else el.textContent = this.cleanText(text);
      return el;
    }
  }, {
    key: "getCheckboxDescription",
    value: function getCheckboxDescription(text) {
      return this.getDescription(text);
    }
  }, {
    key: "getFormInputDescription",
    value: function getFormInputDescription(text) {
      return this.getDescription(text);
    }
  }, {
    key: "getButtonHolder",
    value: function getButtonHolder() {
      return document.createElement('span');
    }
  }, {
    key: "getHeaderButtonHolder",
    value: function getHeaderButtonHolder() {
      return this.getButtonHolder();
    }
  }, {
    key: "getFormButtonHolder",
    value: function getFormButtonHolder(buttonAlign) {
      return this.getButtonHolder();
    }
  }, {
    key: "getButton",
    value: function getButton(text, icon, title) {
      var el = document.createElement('button');
      el.type = 'button';
      this.setButtonText(el, text, icon, title);
      return el;
    }
  }, {
    key: "getFormButton",
    value: function getFormButton(text, icon, title) {
      return this.getButton(text, icon, title);
    }
  }, {
    key: "setButtonText",
    value: function setButtonText(button, text, icon, title) {
      /* Clear previous contents. https://jsperf.com/innerhtml-vs-removechild/37 */
      while (button.firstChild) {
        button.removeChild(button.firstChild);
      }

      if (icon) {
        button.appendChild(icon);
        text = " ".concat(text);
      }

      if (!this.jsoneditor.options.iconlib || !this.jsoneditor.options.remove_button_labels || !icon) {
        var spanEl = document.createElement('span');
        spanEl.appendChild(document.createTextNode(text));
        button.appendChild(spanEl);
      }

      if (title) button.setAttribute('title', title);
    }
    /* Table functions */

  }, {
    key: "getTable",
    value: function getTable() {
      return document.createElement('table');
    }
  }, {
    key: "getTableRow",
    value: function getTableRow() {
      return document.createElement('tr');
    }
  }, {
    key: "getTableHead",
    value: function getTableHead() {
      return document.createElement('thead');
    }
  }, {
    key: "getTableBody",
    value: function getTableBody() {
      return document.createElement('tbody');
    }
  }, {
    key: "getTableHeaderCell",
    value: function getTableHeaderCell(text) {
      var el = document.createElement('th');
      el.textContent = text;
      return el;
    }
  }, {
    key: "getTableCell",
    value: function getTableCell() {
      var el = document.createElement('td');
      return el;
    }
  }, {
    key: "getErrorMessage",
    value: function getErrorMessage(text) {
      var el = document.createElement('p');
      el.style = el.style || {};
      el.style.color = 'red';
      el.appendChild(document.createTextNode(text));
      return el;
    }
  }, {
    key: "addInputError",
    value: function addInputError(input, text) {}
  }, {
    key: "removeInputError",
    value: function removeInputError(input) {}
  }, {
    key: "addTableRowError",
    value: function addTableRowError(row) {}
  }, {
    key: "removeTableRowError",
    value: function removeTableRowError(row) {}
  }, {
    key: "getTabHolder",
    value: function getTabHolder(propertyName) {
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      var el = document.createElement('div');
      el.innerHTML = "<div style='float: left; width: 130px;' class='tabs'></div><div class='content' style='margin-left: 120px;' id='".concat(pName, "'></div><div style='clear:both;'></div>");
      return el;
    }
  }, {
    key: "getTopTabHolder",
    value: function getTopTabHolder(propertyName) {
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      var el = document.createElement('div');
      el.innerHTML = "<div class='tabs' style='margin-left: 10px;'></div><div style='clear:both;'></div><div class='content' id='".concat(pName, "'></div>");
      return el;
    }
  }, {
    key: "applyStyles",
    value: function applyStyles(el, styles) {
      Object.keys(styles).forEach(function (i) {
        return el.style[i] = styles[i];
      });
    }
  }, {
    key: "closest",
    value: function closest(elem, selector) {
      while (elem && elem !== document) {
        if (elem[matchKey]) {
          if (elem[matchKey](selector)) {
            return elem;
          } else {
            elem = elem.parentNode;
          }
        } else {
          return false;
        }
      }

      return false;
    }
  }, {
    key: "insertBasicTopTab",
    value: function insertBasicTopTab(tab, newTabsHolder) {
      newTabsHolder.firstChild.insertBefore(tab, newTabsHolder.firstChild.firstChild);
    }
  }, {
    key: "getTab",
    value: function getTab(span, tabId) {
      var el = document.createElement('div');
      el.appendChild(span);
      el.id = tabId;
      el.style = el.style || {};
      this.applyStyles(el, {
        border: '1px solid #ccc',
        borderWidth: '1px 0 1px 1px',
        textAlign: 'center',
        lineHeight: '30px',
        borderRadius: '5px',
        borderBottomRightRadius: 0,
        borderTopRightRadius: 0,
        fontWeight: 'bold',
        cursor: 'pointer'
      });
      return el;
    }
  }, {
    key: "getTopTab",
    value: function getTopTab(span, tabId) {
      var el = document.createElement('div');
      el.id = tabId;
      el.appendChild(span);
      el.style = el.style || {};
      this.applyStyles(el, {
        "float": 'left',
        border: '1px solid #ccc',
        borderWidth: '1px 1px 0px 1px',
        textAlign: 'center',
        lineHeight: '30px',
        borderRadius: '5px',
        paddingLeft: '5px',
        paddingRight: '5px',
        borderBottomRightRadius: 0,
        borderBottomLeftRadius: 0,
        fontWeight: 'bold',
        cursor: 'pointer'
      });
      return el;
    }
  }, {
    key: "getTabContentHolder",
    value: function getTabContentHolder(tabHolder) {
      return tabHolder.children[1];
    }
  }, {
    key: "getTopTabContentHolder",
    value: function getTopTabContentHolder(tabHolder) {
      return tabHolder.children[1];
    }
  }, {
    key: "getTabContent",
    value: function getTabContent() {
      return this.getIndentedPanel();
    }
  }, {
    key: "getTopTabContent",
    value: function getTopTabContent() {
      return this.getTopIndentedPanel();
    }
  }, {
    key: "markTabActive",
    value: function markTabActive(row) {
      this.applyStyles(row.tab, {
        opacity: 1,
        background: 'white'
      });

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.style.display = '';
      } else {
        row.container.style.display = '';
      }
    }
  }, {
    key: "markTabInactive",
    value: function markTabInactive(row) {
      this.applyStyles(row.tab, {
        opacity: 0.5,
        background: ''
      });

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.style.display = 'none';
      } else {
        row.container.style.display = 'none';
      }
    }
  }, {
    key: "addTab",
    value: function addTab(holder, tab) {
      holder.children[0].appendChild(tab);
    }
  }, {
    key: "addTopTab",
    value: function addTopTab(holder, tab) {
      holder.children[0].appendChild(tab);
    }
  }, {
    key: "getBlockLink",
    value: function getBlockLink() {
      var link = document.createElement('a');
      link.style.display = 'block';
      return link;
    }
  }, {
    key: "getBlockLinkHolder",
    value: function getBlockLinkHolder() {
      var el = document.createElement('div');
      return el;
    }
  }, {
    key: "getLinksHolder",
    value: function getLinksHolder() {
      var el = document.createElement('div');
      return el;
    }
  }, {
    key: "createMediaLink",
    value: function createMediaLink(holder, link, media) {
      holder.appendChild(link);
      media.style.width = '100%';
      holder.appendChild(media);
    }
  }, {
    key: "createImageLink",
    value: function createImageLink(holder, link, image) {
      holder.appendChild(link);
      link.appendChild(image);
    }
  }, {
    key: "getFirstTab",
    value: function getFirstTab(holder) {
      return holder.firstChild.firstChild;
    }
  }, {
    key: "getInputGroup",
    value: function getInputGroup(input, buttons) {
      return undefined;
    }
  }, {
    key: "cleanText",
    value: function cleanText(txt) {
      /* Clean out HTML tags from txt */
      var tmp = document.createElement('div');
      tmp.innerHTML = txt;
      return tmp.textContent || tmp.innerText;
    }
  }, {
    key: "getDropZone",
    value: function getDropZone(text) {
      var el = document.createElement('div');
      el.setAttribute('data-text', text);
      el.classList.add('je-dropzone');
      return el;
    }
    /* file is an object with properties: name, type, mimeType, size amd formattedSize */

  }, {
    key: "getUploadPreview",
    value: function getUploadPreview(file, uploadButton, data) {
      var preview = document.createElement('div');
      preview.classList.add('je-upload-preview');

      if (file.mimeType.substr(0, 5) === 'image') {
        var img = document.createElement('img');
        img.src = data;
        preview.appendChild(img);
      }

      var info = document.createElement('div');
      info.innerHTML += "<strong>Name:</strong> ".concat(file.name, "<br><strong>Type:</strong> ").concat(file.type, "<br><strong>Size:</strong> ").concat(file.formattedSize);
      preview.appendChild(info);
      preview.appendChild(uploadButton);
      var clear = document.createElement('div');
      clear.style.clear = 'left';
      preview.appendChild(clear);
      return preview;
    }
  }, {
    key: "getProgressBar",
    value: function getProgressBar() {
      var max = 100;
      var start = 0;
      var progressBar = document.createElement('progress');
      progressBar.setAttribute('max', max);
      progressBar.setAttribute('value', start);
      return progressBar;
    }
  }, {
    key: "updateProgressBar",
    value: function updateProgressBar(progressBar, progress) {
      if (!progressBar) return;
      progressBar.setAttribute('value', progress);
    }
  }, {
    key: "updateProgressBarUnknown",
    value: function updateProgressBarUnknown(progressBar) {
      if (!progressBar) return;
      progressBar.removeAttribute('value');
    }
  }]);

  return AbstractTheme;
}();
/* Custom stylesheet rules. format: "selector" : "CSS rules" */

AbstractTheme.rules = {
  '.je-upload-preview img': 'float:left;margin:0 0.5rem 0.5rem 0;max-width:100%;max-height:100px'
};

/***/ }),

/***/ "./src/themes/barebones.css.js":
/*!*************************************!*\
  !*** ./src/themes/barebones.css.js ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* eslint-disable */
/* harmony default export */ __webpack_exports__["default"] = ({
  ".je-upload-preview img": "float:left;margin:0%200.5rem%200.5rem%200;max-width:100%25;max-height:5rem",
  ".je-dropzone": "position:relative;margin:0.5rem%200;border:2px%20dashed%20black;width:100%25;height:60px;background:teal;transition:all%200.5s",
  ".je-dropzone:before": "position:absolute;content:attr(data-text);color:rgba(0%2C%200%2C%200%2C%200.6);left:50%25;top:50%25;transform:translate(-50%25%2C%20-50%25)",
  ".je-dropzone.valid-dropzone": "background:green",
  ".je-dropzone.invalid-dropzone": "background:red"
});
/* eslint-enable */

/***/ }),

/***/ "./src/themes/barebones.js":
/*!*********************************!*\
  !*** ./src/themes/barebones.js ***!
  \*********************************/
/*! exports provided: barebonesTheme */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "barebonesTheme", function() { return barebonesTheme; });
/* harmony import */ var _theme_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../theme.js */ "./src/theme.js");
/* harmony import */ var _barebones_css_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./barebones.css.js */ "./src/themes/barebones.css.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var barebonesTheme = /*#__PURE__*/function (_AbstractTheme) {
  _inherits(barebonesTheme, _AbstractTheme);

  var _super = _createSuper(barebonesTheme);

  function barebonesTheme() {
    _classCallCheck(this, barebonesTheme);

    return _super.apply(this, arguments);
  }

  _createClass(barebonesTheme, [{
    key: "addInputError",
    value: function addInputError(input, text) {
      if (!input.errmsg) {
        var group = this.closest(input, '.form-control');
        input.errmsg = document.createElement('div');
        input.errmsg.setAttribute('class', 'errmsg');
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

  return barebonesTheme;
}(_theme_js__WEBPACK_IMPORTED_MODULE_0__["AbstractTheme"]);
/* Custom stylesheet rules. format: "selector" : "CSS rules" */

barebonesTheme.rules = _barebones_css_js__WEBPACK_IMPORTED_MODULE_1__["default"];

/***/ }),

/***/ "./src/themes/bootstrap3.css":
/*!***********************************!*\
  !*** ./src/themes/bootstrap3.css ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports) {



/***/ }),

/***/ "./src/themes/bootstrap3.js":
/*!**********************************!*\
  !*** ./src/themes/bootstrap3.js ***!
  \**********************************/
/*! exports provided: bootstrap3Theme */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "bootstrap3Theme", function() { return bootstrap3Theme; });
/* harmony import */ var _theme_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../theme.js */ "./src/theme.js");
/* harmony import */ var _bootstrap3_css__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./bootstrap3.css */ "./src/themes/bootstrap3.css");
/* harmony import */ var _bootstrap3_css__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_bootstrap3_css__WEBPACK_IMPORTED_MODULE_1__);
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var bootstrap3Theme = /*#__PURE__*/function (_AbstractTheme) {
  _inherits(bootstrap3Theme, _AbstractTheme);

  var _super = _createSuper(bootstrap3Theme);

  function bootstrap3Theme() {
    _classCallCheck(this, bootstrap3Theme);

    return _super.apply(this, arguments);
  }

  _createClass(bootstrap3Theme, [{
    key: "getSelectInput",
    value: function getSelectInput(options, multiple) {
      var el = _get(_getPrototypeOf(bootstrap3Theme.prototype), "getSelectInput", this).call(this, options);

      el.classList.add('form-control');
      /* el.style.width = 'auto'; */

      return el;
    }
  }, {
    key: "setGridColumnSize",
    value: function setGridColumnSize(el, size, offset) {
      el.classList.add("col-md-".concat(size));

      if (offset) {
        el.classList.add("col-md-offset-".concat(offset));
      }
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady(input) {
      if (input.controlgroup) return;
      input.controlgroup = this.closest(input, '.form-group');

      if (this.closest(input, '.compact')) {
        input.controlgroup.style.marginBottom = 0;
      }

      if (this.queuedInputErrorText) {
        var text = this.queuedInputErrorText;
        delete this.queuedInputErrorText;
        this.addInputError(input, text);
      }
      /* TODO: use bootstrap slider */

    }
  }, {
    key: "getTextareaInput",
    value: function getTextareaInput() {
      var el = document.createElement('textarea');
      el.classList.add('form-control');
      return el;
    }
  }, {
    key: "getRangeInput",
    value: function getRangeInput(min, max, step) {
      /* TODO: use better slider */
      return _get(_getPrototypeOf(bootstrap3Theme.prototype), "getRangeInput", this).call(this, min, max, step);
    }
  }, {
    key: "getFormInputField",
    value: function getFormInputField(type) {
      var el = _get(_getPrototypeOf(bootstrap3Theme.prototype), "getFormInputField", this).call(this, type);

      if (type !== 'checkbox' && type !== 'radio') {
        el.classList.add('form-control');
      }

      return el;
    }
  }, {
    key: "getFormControl",
    value: function getFormControl(label, input, description) {
      var group = document.createElement('div');

      if (label && (input.type === 'checkbox' || input.type === 'radio')) {
        group.classList.add(input.type);
        label.insertBefore(input, label.firstChild);
        group.appendChild(label);
      } else {
        group.classList.add('form-group');

        if (label) {
          label.classList.add('control-label');
          group.appendChild(label);
        }

        group.appendChild(input);
      }

      if (description) group.appendChild(description);
      return group;
    }
  }, {
    key: "getIndentedPanel",
    value: function getIndentedPanel() {
      var el = document.createElement('div');
      el.classList.add('well', 'well-sm');
      el.style.paddingBottom = 0;
      return el;
    }
  }, {
    key: "getInfoButton",
    value: function getInfoButton(text) {
      var icon = document.createElement('span');
      icon.classList.add('glyphicon', 'glyphicon-info-sign', 'pull-right');
      icon.style.padding = '.25rem';
      icon.style.position = 'relative';
      icon.style.display = 'inline-block';
      var tooltip = document.createElement('span');
      tooltip.style['font-family'] = 'sans-serif';
      tooltip.style.visibility = 'hidden';
      tooltip.style['background-color'] = 'rgba(50, 50, 50, .75)';
      tooltip.style.margin = '0 .25rem';
      tooltip.style.color = '#FAFAFA';
      tooltip.style.padding = '.5rem 1rem';
      tooltip.style['border-radius'] = '.25rem';
      tooltip.style.width = '25rem';
      tooltip.style.transform = 'translateX(-27rem) translateY(-.5rem)';
      tooltip.style.position = 'absolute';
      tooltip.innerText = text;

      icon.onmouseover = function () {
        tooltip.style.visibility = 'visible';
      };

      icon.onmouseleave = function () {
        tooltip.style.visibility = 'hidden';
      };

      icon.appendChild(tooltip);
      return icon;
    }
  }, {
    key: "getFormInputDescription",
    value: function getFormInputDescription(text) {
      var el = document.createElement('p');
      el.classList.add('help-block');
      if (window.DOMPurify) el.innerHTML = window.DOMPurify.sanitize(text);else el.textContent = this.cleanText(text);
      return el;
    }
  }, {
    key: "getHeaderButtonHolder",
    value: function getHeaderButtonHolder() {
      var el = this.getButtonHolder();
      el.style.marginLeft = '10px';
      return el;
    }
  }, {
    key: "getButtonHolder",
    value: function getButtonHolder() {
      var el = document.createElement('span');
      el.classList.add('btn-group');
      return el;
    }
  }, {
    key: "getButton",
    value: function getButton(text, icon, title) {
      var el = _get(_getPrototypeOf(bootstrap3Theme.prototype), "getButton", this).call(this, text, icon, title);

      el.classList.add('btn', 'btn-default');
      return el;
    }
  }, {
    key: "getTable",
    value: function getTable() {
      var el = document.createElement('table');
      el.classList.add('table', 'table-bordered');
      el.style.width = 'auto';
      el.style.maxWidth = 'none';
      return el;
    }
  }, {
    key: "addInputError",
    value: function addInputError(input, text) {
      if (!input.controlgroup) {
        this.queuedInputErrorText = text;
        return;
      }

      input.controlgroup.classList.add('has-error');

      if (!input.errmsg) {
        input.errmsg = document.createElement('p');
        input.errmsg.classList.add('help-block', 'errormsg');
        input.controlgroup.appendChild(input.errmsg);
      } else {
        input.errmsg.style.display = '';
      }

      input.errmsg.textContent = text;
    }
  }, {
    key: "removeInputError",
    value: function removeInputError(input) {
      if (!input.controlgroup) {
        delete this.queuedInputErrorText;
      }

      if (!input.errmsg) return;
      input.errmsg.style.display = 'none';
      input.controlgroup.classList.remove('has-error');
    }
  }, {
    key: "getTabHolder",
    value: function getTabHolder(propertyName) {
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      var el = document.createElement('div');
      el.innerHTML = "<ul class='col-md-2 nav nav-pills nav-stacked' id='".concat(pName, "' role='tablist'></ul><div class='col-md-10 tab-content well well-small'  id='").concat(pName, "'></div>");
      return el;
    }
  }, {
    key: "getTopTabHolder",
    value: function getTopTabHolder(propertyName) {
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      var el = document.createElement('div');
      el.innerHTML = "<ul class='nav nav-tabs' id='".concat(pName, "' role='tablist'></ul><div class='tab-content well well-small'  id='").concat(pName, "'></div>");
      return el;
    }
  }, {
    key: "getTab",
    value: function getTab(text, tabId) {
      var li = document.createElement('li');
      li.setAttribute('role', 'presentation');
      var a = document.createElement('a');
      a.setAttribute('href', "#".concat(tabId));
      a.appendChild(text);
      a.setAttribute('aria-controls', tabId);
      a.setAttribute('role', 'tab');
      a.setAttribute('data-toggle', 'tab');
      li.appendChild(a);
      return li;
    }
  }, {
    key: "getTopTab",
    value: function getTopTab(text, tabId) {
      var li = document.createElement('li');
      li.setAttribute('role', 'presentation');
      var a = document.createElement('a');
      a.setAttribute('href', "#".concat(tabId));
      a.appendChild(text);
      a.setAttribute('aria-controls', tabId);
      a.setAttribute('role', 'tab');
      a.setAttribute('data-toggle', 'tab');
      li.appendChild(a);
      return li;
    }
  }, {
    key: "getTabContent",
    value: function getTabContent() {
      var el = document.createElement('div');
      el.classList.add('tab-pane');
      el.setAttribute('role', 'tabpanel');
      return el;
    }
  }, {
    key: "getTopTabContent",
    value: function getTopTabContent() {
      var el = document.createElement('div');
      el.classList.add('tab-pane');
      el.setAttribute('role', 'tabpanel');
      return el;
    }
  }, {
    key: "markTabActive",
    value: function markTabActive(row) {
      row.tab.classList.add('active');

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.classList.add('active');
      } else {
        row.container.classList.add('active');
      }
    }
  }, {
    key: "markTabInactive",
    value: function markTabInactive(row) {
      row.tab.classList.remove('active');

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.classList.remove('active');
      } else {
        row.container.classList.remove('active');
      }
    }
  }, {
    key: "getProgressBar",
    value: function getProgressBar() {
      var min = 0;
      var max = 100;
      var start = 0;
      var container = document.createElement('div');
      container.classList.add('progress');
      var bar = document.createElement('div');
      bar.classList.add('progress-bar');
      bar.setAttribute('role', 'progressbar');
      bar.setAttribute('aria-valuenow', start);
      bar.setAttribute('aria-valuemin', min);
      bar.setAttribute('aria-valuenax', max);
      bar.innerHTML = "".concat(start, "%");
      container.appendChild(bar);
      return container;
    }
  }, {
    key: "updateProgressBar",
    value: function updateProgressBar(progressBar, progress) {
      if (!progressBar) return;
      var bar = progressBar.firstChild;
      var percentage = "".concat(progress, "%");
      bar.setAttribute('aria-valuenow', progress);
      bar.style.width = percentage;
      bar.innerHTML = percentage;
    }
  }, {
    key: "updateProgressBarUnknown",
    value: function updateProgressBarUnknown(progressBar) {
      if (!progressBar) return;
      var bar = progressBar.firstChild;
      progressBar.classList.add('progress', 'progress-striped', 'active');
      bar.removeAttribute('aria-valuenow');
      bar.style.width = '100%';
      bar.innerHTML = '';
    }
  }, {
    key: "getInputGroup",
    value: function getInputGroup(input, buttons) {
      if (!input) return;
      var inputGroupContainer = document.createElement('div');
      inputGroupContainer.classList.add('input-group');
      inputGroupContainer.appendChild(input);
      var inputGroup = document.createElement('div');
      inputGroup.classList.add('input-group-btn');
      inputGroupContainer.appendChild(inputGroup);

      for (var i = 0; i < buttons.length; i++) {
        inputGroup.appendChild(buttons[i]);
      }

      return inputGroupContainer;
    }
  }]);

  return bootstrap3Theme;
}(_theme_js__WEBPACK_IMPORTED_MODULE_0__["AbstractTheme"]);
/* Custom stylesheet rules. format: "selector" : "CSS rules" */

bootstrap3Theme.rules = _bootstrap3_css__WEBPACK_IMPORTED_MODULE_1___default.a;

/***/ }),

/***/ "./src/themes/bootstrap4.css.js":
/*!**************************************!*\
  !*** ./src/themes/bootstrap4.css.js ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* eslint-disable */
/* harmony default export */ __webpack_exports__["default"] = ({
  ".jsoneditor-twbs4-text-button": "background:none;padding:0;border:0;color:currentColor",
  "td > .form-group": "margin-bottom:0",
  ".json-editor-btn-upload": "margin-top:1rem",
  ".je-noindent .card": "padding:0;border:0",
  ".je-tooltip:hover::before": "display:block;position:absolute;font-size:0.8em;color:%23fff;border-radius:0.2em;content:attr(title);background-color:%23000;margin-top:-2.5em;padding:0.3em",
  ".je-tooltip:hover::after": "display:block;position:absolute;font-size:0.8em;color:%23fff",
  ".select2-container--default .select2-selection--single": "height:calc(1.5em%20%2B%200.75rem%20%2B%202px)",
  ".select2-container--default   .select2-selection--single   .select2-selection__arrow": "height:calc(1.5em%20%2B%200.75rem%20%2B%202px)",
  ".select2-container--default   .select2-selection--single   .select2-selection__rendered": "line-height:calc(1.5em%20%2B%200.75rem%20%2B%202px)",
  ".selectize-control.form-control": "padding:0",
  ".selectize-dropdown.form-control": "padding:0;height:auto",
  ".je-upload-preview img": "float:left;margin:0%200.5rem%200.5rem%200;max-width:100%25;max-height:5rem",
  ".je-dropzone": "position:relative;margin:0.5rem%200;border:2px%20dashed%20black;width:100%25;height:60px;background:teal;transition:all%200.5s",
  ".je-dropzone:before": "position:absolute;content:attr(data-text);color:rgba(0%2C%200%2C%200%2C%200.6);left:50%25;top:50%25;transform:translate(-50%25%2C%20-50%25)",
  ".je-dropzone.valid-dropzone": "background:green",
  ".je-dropzone.invalid-dropzone": "background:red"
});
/* eslint-enable */

/***/ }),

/***/ "./src/themes/bootstrap4.js":
/*!**********************************!*\
  !*** ./src/themes/bootstrap4.js ***!
  \**********************************/
/*! exports provided: bootstrap4Theme */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "bootstrap4Theme", function() { return bootstrap4Theme; });
/* harmony import */ var _theme_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../theme.js */ "./src/theme.js");
/* harmony import */ var _bootstrap4_css_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./bootstrap4.css.js */ "./src/themes/bootstrap4.css.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



/* Theme config options that allows changing various aspects of the output */

var options = {
  disable_theme_rules: false,
  input_size: 'normal',

  /* Size of input and select elements. "small", "normal", "large" */
  custom_forms: false,

  /* use twbs custom form stylings */
  object_indent: true,

  /* Indent nested object elements (use nested .card layout) */
  object_background: 'bg-light',

  /* Bootstrap 4 card background modifier class (https://getbootstrap.com/docs/4.1/getting-started/introduction/) */
  object_text: '',

  /* Bootstrap 4 card tect color modifier class (https://getbootstrap.com/docs/4.1/getting-started/introduction/) */
  table_border: false,

  /* Add border to array "table" row and cells */
  table_zebrastyle: false,

  /* Add "zebra style" to array "table" rows */
  tooltip: 'bootstrap'
  /* how to display tooltips (infoText). Can be `browser` for native `title`, `css` for simple CSS Styling, or `bootstrap` for TWBS/Popper.js handling */

};
var bootstrap4Theme = /*#__PURE__*/function (_AbstractTheme) {
  _inherits(bootstrap4Theme, _AbstractTheme);

  var _super = _createSuper(bootstrap4Theme);

  function bootstrap4Theme(jsoneditor) {
    _classCallCheck(this, bootstrap4Theme);

    return _super.call(this, jsoneditor, options);
  }

  _createClass(bootstrap4Theme, [{
    key: "getSelectInput",
    value: function getSelectInput(options, multiple) {
      var el = _get(_getPrototypeOf(bootstrap4Theme.prototype), "getSelectInput", this).call(this, options);

      el.classList.add('form-control');

      if (this.options.custom_forms === false) {
        if (this.options.input_size === 'small') el.classList.add('form-control-sm');
        if (this.options.input_size === 'large') el.classList.add('form-control-lg');
      } else {
        el.classList.remove('form-control');
        el.classList.add('custom-select');
        if (this.options.input_size === 'small') el.classList.add('custom-select-sm');
        if (this.options.input_size === 'large') el.classList.add('custom-select-lg');
      }

      return el;
    }
  }, {
    key: "getContainer",
    value: function getContainer() {
      var el = document.createElement('div');
      if (!this.options.object_indent) el.classList.add('je-noindent');
      return el;
    }
  }, {
    key: "setGridColumnSize",
    value: function setGridColumnSize(el, size, offset) {
      el.classList.add("col-md-".concat(size));

      if (offset) {
        el.classList.add("offset-md-".concat(offset));
      }
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady(input) {
      if (input.controlgroup) return;
      /* set id/for */

      /* is not working for: [type=file], [type=checkbox] */

      var id = input.name;
      input.id = id;
      /* 2x parentNode, b/c range input has an <div> wrapper */

      var label = input.parentNode.parentNode.getElementsByTagName('label')[0];

      if (label) {
        label.htmlFor = id;
      }

      input.controlgroup = this.closest(input, '.form-group');
    }
  }, {
    key: "getTextareaInput",
    value: function getTextareaInput() {
      var el = document.createElement('textarea');
      el.classList.add('form-control');
      if (this.options.input_size === 'small') el.classList.add('form-control-sm');
      if (this.options.input_size === 'large') el.classList.add('form-control-lg');
      return el;
    }
  }, {
    key: "getRangeInput",
    value: function getRangeInput(min, max, step) {
      var el = _get(_getPrototypeOf(bootstrap4Theme.prototype), "getRangeInput", this).call(this, min, max, step);

      if (this.options.custom_forms === true) {
        el.classList.remove('form-control');
        el.classList.add('custom-range');
      }

      return el;
    }
  }, {
    key: "getFormInputField",
    value: function getFormInputField(type) {
      var el = _get(_getPrototypeOf(bootstrap4Theme.prototype), "getFormInputField", this).call(this, type);

      if (type !== 'checkbox' && type !== 'radio' && type !== 'file') {
        el.classList.add('form-control');
        if (this.options.input_size === 'small') el.classList.add('form-control-sm');
        if (this.options.input_size === 'large') el.classList.add('form-control-lg');
      }

      if (type === 'file') {
        /* custom_form is not used on files, would be a bit ticky since we need more */

        /* markup. Also it contains language strings which would need be translateable? */

        /* and most of all, w/o JavaScript teh name of the file can't be displayed. */
        el.classList.add('form-control-file');
      }

      return el;
    }
  }, {
    key: "getFormControl",
    value: function getFormControl(label, input, description, infoText) {
      var group = document.createElement('div');
      group.classList.add('form-group');

      if (label && (input.type === 'checkbox' || input.type === 'radio')) {
        var check = document.createElement('div');

        if (this.options.custom_forms === false) {
          check.classList.add('form-check');
          input.classList.add('form-check-input');
          label.classList.add('form-check-label');
        } else {
          check.classList.add('custom-control');
          input.classList.add('custom-control-input');
          label.classList.add('custom-control-label');

          if (input.type === 'checkbox') {
            check.classList.add('custom-checkbox');
          } else {
            check.classList.add('custom-radio');
          }
        }

        var unique = (Date.now() * Math.random()).toFixed(0);
        input.setAttribute('id', unique);
        label.setAttribute('for', unique);
        check.appendChild(input);
        check.appendChild(label);
        if (infoText) check.appendChild(infoText);
        group.appendChild(check);
      } else {
        if (label) {
          group.appendChild(label);
          if (infoText) group.appendChild(infoText);
        }

        group.appendChild(input);
      }

      if (description) {
        group.appendChild(description);
      }

      return group;
    }
  }, {
    key: "getInfoButton",
    value: function getInfoButton(text) {
      var button = document.createElement('button');
      /* shoud be a <button> but no fitting tbws style... */

      button.type = 'button';
      button.classList.add('ml-3', 'jsoneditor-twbs4-text-button');
      button.setAttribute('data-toggle', 'tooltip');
      button.setAttribute('data-placement', 'auto');
      button.title = text;
      var icon = document.createTextNode('ⓘ');
      button.appendChild(icon);

      if (this.options.tooltip === 'bootstrap') {
        if (window.jQuery && window.jQuery().tooltip) {
          window.jQuery(button).tooltip();
        } else {
          console.warn('Could not find popper jQuery plugin of Bootstrap.');
        }
      } else if (this.options.tooltip === 'css') {
        button.classList.add('je-tooltip');
      }
      /* else -> nothing todo for native [title] handling */


      return button;
    }
    /**
     * Generates a checkbox...
     *
     * Overwriten from master theme to get rid of inline styles.
     */

  }, {
    key: "getCheckbox",
    value: function getCheckbox() {
      var el = this.getFormInputField('checkbox');
      return el;
    }
    /**
     * Multiple checkboxes in a row.
     *
     */

  }, {
    key: "getMultiCheckboxHolder",
    value: function getMultiCheckboxHolder(controls, label, description, infoText) {
      var el = document.createElement('div');
      el.classList.add('form-group');

      if (label) {
        el.appendChild(label);

        if (infoText) {
          label.appendChild(infoText);
        }
      }
      /* for inline view we need an container so it doesnt wrap in the "row" of the <label> */


      var container = document.createElement('div');
      Object.values(controls).forEach(function (c) {
        /* controls are already parsed by getFormControl() so they have an .form-group */

        /* wrapper we need to get rid of... */
        var ctrl = c.firstChild;
        /* we don't know if this should be an normal / compact view */

        /* if (this.options.custom_forms === false) {
          ctrl.classList.add('form-check-inline')
        } else {
          ctrl.classList.add('custom-control-inline')
        } */

        container.appendChild(ctrl);
      });
      el.appendChild(container);
      if (description) el.appendChild(description);
      return el;
    }
    /**
     * Single radio element
     */

  }, {
    key: "getFormRadio",
    value: function getFormRadio(attributes) {
      var el = this.getFormInputField('radio');

      for (var key in attributes) {
        el.setAttribute(key, attributes[key]);
      }

      if (this.options.custom_forms === false) {
        el.classList.add('form-check-input');
      } else {
        el.classList.add('custom-control-input');
      }

      return el;
    }
    /**
     * Add the <label> for the single radio from getFormRadio()
     *
     */

  }, {
    key: "getFormRadioLabel",
    value: function getFormRadioLabel(text, req) {
      var el = document.createElement('label');

      if (this.options.custom_forms === false) {
        el.classList.add('form-check-label');
      } else {
        el.classList.add('custom-control-label');
      }

      el.appendChild(document.createTextNode(text));
      return el;
    }
    /**
     * Stack the radios from getFormRadio()/getFormRadioLabel()
     *
     */

  }, {
    key: "getFormRadioControl",
    value: function getFormRadioControl(label, input, compact) {
      var el = document.createElement('div');

      if (this.options.custom_forms === false) {
        el.classList.add('form-check');
      } else {
        el.classList.add('custom-control', 'custom-radio');
      }

      el.appendChild(input);
      el.appendChild(label);

      if (compact) {
        if (this.options.custom_forms === false) {
          el.classList.add('form-check-inline');
        } else {
          el.classList.add('custom-control-inline');
        }
      }

      return el;
    }
  }, {
    key: "getIndentedPanel",
    value: function getIndentedPanel() {
      var el = document.createElement('div');
      el.classList.add('card', 'card-body', 'mb-3');

      if (this.options.object_background) {
        el.classList.add(this.options.object_background);
      }

      if (this.options.object_text) {
        el.classList.add(this.options.object_text);
      }
      /* for better twbs card styling we should be able to return a nested div */


      return el;
    }
  }, {
    key: "getFormInputDescription",
    value: function getFormInputDescription(text) {
      var el = document.createElement('small');
      el.classList.add('form-text');

      if (window.DOMPurify) {
        el.innerHTML = window.DOMPurify.sanitize(text);
      } else {
        el.textContent = this.cleanText(text);
      }

      return el;
    }
  }, {
    key: "getHeader",
    value: function getHeader(text) {
      /* var cardHeader = document.createElement('div') */

      /* cardHeader.classList.add('card-header') */
      var el = document.createElement('h3');
      el.classList.add('card-title');

      if (typeof text === 'string') {
        el.textContent = text;
      } else {
        el.appendChild(text);
      }

      el.style.display = 'inline-block';
      /* cardHeader.appendChild(el) */

      return el;
    }
  }, {
    key: "getHeaderButtonHolder",
    value: function getHeaderButtonHolder() {
      var el = this.getButtonHolder();
      return el;
    }
  }, {
    key: "getButtonHolder",
    value: function getButtonHolder() {
      var el = document.createElement('span');
      el.classList.add('btn-group');
      return el;
    }
  }, {
    key: "getFormButtonHolder",
    value: function getFormButtonHolder(buttonAlign) {
      var el = this.getButtonHolder();
      el.classList.add('d-block');
      if (buttonAlign === 'center') el.classList.add('text-center');else if (buttonAlign === 'right') el.classList.add('text-right');
      return el;
    }
  }, {
    key: "getButton",
    value: function getButton(text, icon, title) {
      var el = _get(_getPrototypeOf(bootstrap4Theme.prototype), "getButton", this).call(this, text, icon, title);

      el.classList.add('btn', 'btn-secondary', 'btn-sm');
      return el;
    }
  }, {
    key: "getTable",
    value: function getTable() {
      var el = document.createElement('table');
      el.classList.add('table', 'table-sm');

      if (this.options.table_border) {
        el.classList.add('table-bordered');
      }

      if (this.options.table_zebrastyle) {
        el.classList.add('table-striped');
      }

      return el;
    }
  }, {
    key: "getErrorMessage",
    value: function getErrorMessage(text) {
      var el = document.createElement('div');
      el.classList.add('alert', 'alert-danger');
      el.setAttribute('role', 'alert');
      el.appendChild(document.createTextNode(text));
      return el;
    }
    /**
     * input validation on <input>
     */

  }, {
    key: "addInputError",
    value: function addInputError(input, text) {
      if (!input.controlgroup) return;
      input.classList.add('is-invalid');

      if (!input.errmsg) {
        input.errmsg = document.createElement('p');
        input.errmsg.classList.add('invalid-feedback');
        input.controlgroup.appendChild(input.errmsg);
      } else {
        input.errmsg.style.display = '';
      }

      input.errmsg.textContent = text;
    }
  }, {
    key: "removeInputError",
    value: function removeInputError(input) {
      if (!input.errmsg) return;
      input.errmsg.style.display = 'none';
      input.classList.remove('is-invalid');
    }
  }, {
    key: "getTabHolder",
    value: function getTabHolder(propertyName) {
      var el = document.createElement('div');
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      el.innerHTML = "<div class='col-md-2' id='".concat(pName, "'><ul class='nav flex-column nav-pills'></ul></div><div class='col-md-10'><div class='tab-content' id='").concat(pName, "'></div></div>");
      el.classList.add('row');
      return el;
    }
  }, {
    key: "addTab",
    value: function addTab(holder, tab) {
      holder.children[0].children[0].appendChild(tab);
    }
  }, {
    key: "getTabContentHolder",
    value: function getTabContentHolder(tabHolder) {
      return tabHolder.children[1].children[0];
    }
  }, {
    key: "getTopTabHolder",
    value: function getTopTabHolder(propertyName) {
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      var el = document.createElement('div');
      el.classList.add('card');
      el.innerHTML = "<div class='card-header'><ul class='nav nav-tabs card-header-tabs' id='".concat(pName, "'></ul></div><div class='card-body'><div class='tab-content' id='").concat(pName, "'></div></div>");
      return el;
    }
  }, {
    key: "getTab",
    value: function getTab(text, tabId) {
      var liel = document.createElement('li');
      liel.classList.add('nav-item');
      var ael = document.createElement('a');
      ael.classList.add('nav-link');
      ael.setAttribute('href', "#".concat(tabId));
      ael.setAttribute('data-toggle', 'tab');
      ael.appendChild(text);
      liel.appendChild(ael);
      return liel;
    }
  }, {
    key: "getTopTab",
    value: function getTopTab(text, tabId) {
      var el = document.createElement('li');
      el.classList.add('nav-item');
      var a = document.createElement('a');
      a.classList.add('nav-link');
      a.setAttribute('href', "#".concat(tabId));
      a.setAttribute('data-toggle', 'tab');
      a.appendChild(text);
      el.appendChild(a);
      return el;
    }
  }, {
    key: "getTabContent",
    value: function getTabContent() {
      var el = document.createElement('div');
      el.classList.add('tab-pane');
      el.setAttribute('role', 'tabpanel');
      return el;
    }
  }, {
    key: "getTopTabContent",
    value: function getTopTabContent() {
      var el = document.createElement('div');
      el.classList.add('tab-pane');
      el.setAttribute('role', 'tabpanel');
      return el;
    }
  }, {
    key: "markTabActive",
    value: function markTabActive(row) {
      row.tab.firstChild.classList.add('active');

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.classList.add('active');
      } else {
        row.container.classList.add('active');
      }
    }
  }, {
    key: "markTabInactive",
    value: function markTabInactive(row) {
      row.tab.firstChild.classList.remove('active');

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.classList.remove('active');
      } else {
        row.container.classList.remove('active');
      }
    }
  }, {
    key: "insertBasicTopTab",
    value: function insertBasicTopTab(tab, newTabsHolder) {
      newTabsHolder.children[0].children[0].insertBefore(tab, newTabsHolder.children[0].children[0].firstChild);
    }
  }, {
    key: "addTopTab",
    value: function addTopTab(holder, tab) {
      holder.children[0].children[0].appendChild(tab);
    }
  }, {
    key: "getTopTabContentHolder",
    value: function getTopTabContentHolder(tabHolder) {
      return tabHolder.children[1].children[0];
    }
  }, {
    key: "getFirstTab",
    value: function getFirstTab(holder) {
      return holder.firstChild.firstChild.firstChild;
    }
  }, {
    key: "getProgressBar",
    value: function getProgressBar() {
      var min = 0;
      var max = 100;
      var start = 0;
      var container = document.createElement('div');
      container.classList.add('progress');
      var bar = document.createElement('div');
      bar.classList.add('progress-bar');
      bar.setAttribute('role', 'progressbar');
      bar.setAttribute('aria-valuenow', start);
      bar.setAttribute('aria-valuemin', min);
      bar.setAttribute('aria-valuenax', max);
      bar.innerHTML = "".concat(start, "%");
      container.appendChild(bar);
      return container;
    }
  }, {
    key: "updateProgressBar",
    value: function updateProgressBar(progressBar, progress) {
      if (!progressBar) return;
      var bar = progressBar.firstChild;
      var percentage = "".concat(progress, "%");
      bar.setAttribute('aria-valuenow', progress);
      bar.style.width = percentage;
      bar.innerHTML = percentage;
    }
  }, {
    key: "updateProgressBarUnknown",
    value: function updateProgressBarUnknown(progressBar) {
      if (!progressBar) return;
      var bar = progressBar.firstChild;
      progressBar.classList.add('progress', 'progress-striped', 'active');
      bar.removeAttribute('aria-valuenow');
      bar.style.width = '100%';
      bar.innerHTML = '';
    }
  }, {
    key: "getBlockLink",
    value: function getBlockLink() {
      var link = document.createElement('a');
      link.classList.add('mb-3', 'd-inline-block');
      return link;
    }
    /**
     * Link after successfull upload
     */

  }, {
    key: "getLinksHolder",
    value: function getLinksHolder() {
      var el = document.createElement('div');
      return el;
    }
  }, {
    key: "getInputGroup",
    value: function getInputGroup(input, buttons) {
      if (!input) return;
      var inputGroupContainer = document.createElement('div');
      inputGroupContainer.classList.add('input-group');
      inputGroupContainer.appendChild(input);
      var inputGroup = document.createElement('div');
      inputGroup.classList.add('input-group-append');
      inputGroupContainer.appendChild(inputGroup);

      for (var i = 0; i < buttons.length; i++) {
        /* this uses the getButton() wrapper, so we have to remove the panel/ctrl spacing for this case */
        buttons[i].classList.remove('mr-2', 'btn-secondary');
        buttons[i].classList.add('btn-outline-secondary');
        inputGroup.appendChild(buttons[i]);
      }

      return inputGroupContainer;
    }
  }]);

  return bootstrap4Theme;
}(_theme_js__WEBPACK_IMPORTED_MODULE_0__["AbstractTheme"]);
/* Custom stylesheet rules. format: "selector" : "CSS rules" */

bootstrap4Theme.rules = _bootstrap4_css_js__WEBPACK_IMPORTED_MODULE_1__["default"];

/***/ }),

/***/ "./src/themes/html.css.js":
/*!********************************!*\
  !*** ./src/themes/html.css.js ***!
  \********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* eslint-disable */
/* harmony default export */ __webpack_exports__["default"] = ({
  "je-form-input-label": "display:block;margin-bottom:3px;font-weight:bold",
  "je-form-input-description": "display:inline-block;margin:0;font-size:0.8em;font-style:italic",
  "je-indented-panel": "padding:5px;margin:10px;border-radius:3px;border:1px%20solid%20%23ddd",
  "je-child-editor-holder": "margin-bottom:8px",
  "je-header-button-holder": "display:inline-block;margin-left:10px;font-size:0.8em;vertical-align:middle",
  "je-table": "margin-bottom:5px;border-bottom:1px%20solid%20%23ccc",
  ".je-upload-preview img": "float:left;margin:0%200.5rem%200.5rem%200;max-width:100%25;max-height:5rem",
  ".je-dropzone": "position:relative;margin:0.5rem%200;border:2px%20dashed%20black;width:100%25;height:60px;background:teal;transition:all%200.5s",
  ".je-dropzone:before": "position:absolute;content:attr(data-text);color:rgba(0%2C%200%2C%200%2C%200.6);left:50%25;top:50%25;transform:translate(-50%25%2C%20-50%25)",
  ".je-dropzone.valid-dropzone": "background:green",
  ".je-dropzone.invalid-dropzone": "background:red"
});
/* eslint-enable */

/***/ }),

/***/ "./src/themes/html.js":
/*!****************************!*\
  !*** ./src/themes/html.js ***!
  \****************************/
/*! exports provided: htmlTheme */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "htmlTheme", function() { return htmlTheme; });
/* harmony import */ var _theme_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../theme.js */ "./src/theme.js");
/* harmony import */ var _html_css_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./html.css.js */ "./src/themes/html.css.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var htmlTheme = /*#__PURE__*/function (_AbstractTheme) {
  _inherits(htmlTheme, _AbstractTheme);

  var _super = _createSuper(htmlTheme);

  function htmlTheme() {
    _classCallCheck(this, htmlTheme);

    return _super.apply(this, arguments);
  }

  _createClass(htmlTheme, [{
    key: "getFormInputLabel",
    value: function getFormInputLabel(text, req) {
      var el = _get(_getPrototypeOf(htmlTheme.prototype), "getFormInputLabel", this).call(this, text, req);

      el.classList.add('je-form-input-label');
      return el;
    }
  }, {
    key: "getFormInputDescription",
    value: function getFormInputDescription(text) {
      var el = _get(_getPrototypeOf(htmlTheme.prototype), "getFormInputDescription", this).call(this, text);

      el.classList.add('je-form-input-label');
      return el;
    }
  }, {
    key: "getIndentedPanel",
    value: function getIndentedPanel() {
      var el = _get(_getPrototypeOf(htmlTheme.prototype), "getIndentedPanel", this).call(this);

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
      var el = _get(_getPrototypeOf(htmlTheme.prototype), "getChildEditorHolder", this).call(this);

      el.classList.add('je-child-editor-holder');
      return el;
    }
  }, {
    key: "getHeaderButtonHolder",
    value: function getHeaderButtonHolder() {
      var el = this.getButtonHolder();
      el.classList.add('je-header-button-holder');
      return el;
    }
  }, {
    key: "getTable",
    value: function getTable() {
      var el = _get(_getPrototypeOf(htmlTheme.prototype), "getTable", this).call(this);

      el.classList.add('je-table');
      return el;
    }
  }, {
    key: "addInputError",
    value: function addInputError(input, text) {
      input.style.borderColor = 'red';

      if (!input.errmsg) {
        var group = this.closest(input, '.form-control');
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

  return htmlTheme;
}(_theme_js__WEBPACK_IMPORTED_MODULE_0__["AbstractTheme"]);
/* Custom stylesheet rules. format: "selector" : "CSS rules" */

htmlTheme.rules = _html_css_js__WEBPACK_IMPORTED_MODULE_1__["default"];

/***/ }),

/***/ "./src/themes/index.js":
/*!*****************************!*\
  !*** ./src/themes/index.js ***!
  \*****************************/
/*! exports provided: themes */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "themes", function() { return themes; });
/* harmony import */ var _html_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./html.js */ "./src/themes/html.js");
/* harmony import */ var _bootstrap3_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./bootstrap3.js */ "./src/themes/bootstrap3.js");
/* harmony import */ var _bootstrap4_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./bootstrap4.js */ "./src/themes/bootstrap4.js");
/* harmony import */ var _jqueryui_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./jqueryui.js */ "./src/themes/jqueryui.js");
/* harmony import */ var _barebones_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./barebones.js */ "./src/themes/barebones.js");
/* harmony import */ var _spectre_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./spectre.js */ "./src/themes/spectre.js");
/* harmony import */ var _tailwind_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./tailwind.js */ "./src/themes/tailwind.js");
 // import  { bootstrap2Theme } from  './bootstrap2'


 // import  { foundationTheme, foundation3Theme, foundation4Theme, foundation5Theme, foundation6Theme } from  './foundation.js'


 // import  { materializeTheme } from  './materialize'



var themes = {
  html: _html_js__WEBPACK_IMPORTED_MODULE_0__["htmlTheme"],
  // bootstrap2: bootstrap2Theme,
  bootstrap3: _bootstrap3_js__WEBPACK_IMPORTED_MODULE_1__["bootstrap3Theme"],
  bootstrap4: _bootstrap4_js__WEBPACK_IMPORTED_MODULE_2__["bootstrap4Theme"],
  // foundation: foundationTheme,
  // foundation3: foundation3Theme,
  // foundation4: foundation4Theme,
  // foundation5: foundation5Theme,
  // foundation6: foundation6Theme,
  jqueryui: _jqueryui_js__WEBPACK_IMPORTED_MODULE_3__["jqueryuiTheme"],
  barebones: _barebones_js__WEBPACK_IMPORTED_MODULE_4__["barebonesTheme"],
  // materialize: materializeTheme,
  spectre: _spectre_js__WEBPACK_IMPORTED_MODULE_5__["spectreTheme"],
  tailwind: _tailwind_js__WEBPACK_IMPORTED_MODULE_6__["tailwindTheme"]
};

/***/ }),

/***/ "./src/themes/jqueryui.js":
/*!********************************!*\
  !*** ./src/themes/jqueryui.js ***!
  \********************************/
/*! exports provided: jqueryuiTheme */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "jqueryuiTheme", function() { return jqueryuiTheme; });
/* harmony import */ var _theme_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../theme.js */ "./src/theme.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }


var jqueryuiTheme = /*#__PURE__*/function (_AbstractTheme) {
  _inherits(jqueryuiTheme, _AbstractTheme);

  var _super = _createSuper(jqueryuiTheme);

  function jqueryuiTheme() {
    _classCallCheck(this, jqueryuiTheme);

    return _super.apply(this, arguments);
  }

  _createClass(jqueryuiTheme, [{
    key: "getTable",
    value: function getTable() {
      var el = _get(_getPrototypeOf(jqueryuiTheme.prototype), "getTable", this).call(this);

      el.setAttribute('cellpadding', 5);
      el.setAttribute('cellspacing', 0);
      return el;
    }
  }, {
    key: "getTableHeaderCell",
    value: function getTableHeaderCell(text) {
      var el = _get(_getPrototypeOf(jqueryuiTheme.prototype), "getTableHeaderCell", this).call(this, text);

      el.classList.add('ui-state-active');
      el.style.fontWeight = 'bold';
      return el;
    }
  }, {
    key: "getTableCell",
    value: function getTableCell() {
      var el = _get(_getPrototypeOf(jqueryuiTheme.prototype), "getTableCell", this).call(this);

      el.classList.add('ui-widget-content');
      return el;
    }
  }, {
    key: "getHeaderButtonHolder",
    value: function getHeaderButtonHolder() {
      var el = this.getButtonHolder();
      el.style.marginLeft = '10px';
      el.style.fontSize = '.6em';
      el.style.display = 'inline-block';
      return el;
    }
  }, {
    key: "getFormInputDescription",
    value: function getFormInputDescription(text) {
      var el = this.getDescription(text);
      el.style.marginLeft = '10px';
      el.style.display = 'inline-block';
      return el;
    }
  }, {
    key: "getFormControl",
    value: function getFormControl(label, input, description, infoText) {
      var el = _get(_getPrototypeOf(jqueryuiTheme.prototype), "getFormControl", this).call(this, label, input, description, infoText);

      if (input.type === 'checkbox') {
        el.style.lineHeight = '25px';
        el.style.padding = '3px 0';
      } else {
        el.style.padding = '4px 0 8px 0';
      }

      return el;
    }
  }, {
    key: "getDescription",
    value: function getDescription(text) {
      var el = document.createElement('span');
      el.style.fontSize = '.8em';
      el.style.fontStyle = 'italic';
      if (window.DOMPurify) el.innerHTML = window.DOMPurify.sanitize(text);else el.textContent = this.cleanText(text);
      return el;
    }
  }, {
    key: "getButtonHolder",
    value: function getButtonHolder() {
      var el = document.createElement('div');
      el.classList.add('ui-buttonset');
      el.style.fontSize = '.7em';
      return el;
    }
  }, {
    key: "getFormInputLabel",
    value: function getFormInputLabel(text, req) {
      var el = document.createElement('label');
      el.style.fontWeight = 'bold';
      el.style.display = 'block';
      el.textContent = text;
      if (req) el.classList.add('required');
      return el;
    }
  }, {
    key: "getButton",
    value: function getButton(text, icon, title) {
      var button = document.createElement('button');
      button.classList.add('ui-button', 'ui-widget', 'ui-state-default', 'ui-corner-all');
      /* Icon only */

      if (icon && !text) {
        button.classList.add('ui-button-icon-only');
        icon.classList.add('ui-button-icon-primary', 'ui-icon-primary');
        button.appendChild(icon);
        /* Icon and Text */
      } else if (icon) {
        button.classList.add('ui-button-text-icon-primary');
        icon.classList.add('ui-button-icon-primary', 'ui-icon-primary');
        button.appendChild(icon);
        /* Text only */
      } else {
        button.classList.add('ui-button-text-only');
      }

      var el = document.createElement('span');
      el.classList.add('ui-button-text');
      el.textContent = text || title || '.';
      button.appendChild(el);
      button.setAttribute('title', title);
      return button;
    }
  }, {
    key: "setButtonText",
    value: function setButtonText(button, text, icon, title) {
      button.innerHTML = '';
      button.classList.add('ui-button', 'ui-widget', 'ui-state-default', 'ui-corner-all');
      /* Icon only */

      if (icon && !text) {
        button.classList.add('ui-button-icon-only');
        icon.classList.add('ui-button-icon-primary', 'ui-icon-primary');
        button.appendChild(icon);
        /* Icon and Text */
      } else if (icon) {
        button.classList.add('ui-button-text-icon-primary');
        icon.classList.add('ui-button-icon-primary', 'ui-icon-primary');
        button.appendChild(icon);
        /* Text only */
      } else {
        button.classList.add('ui-button-text-only');
      }

      var el = document.createElement('span');
      el.classList.add('ui-button-text');
      el.textContent = text || title || '.';
      button.appendChild(el);
      button.setAttribute('title', title);
    }
  }, {
    key: "getIndentedPanel",
    value: function getIndentedPanel() {
      var el = document.createElement('div');
      el.classList.add('ui-widget-content', 'ui-corner-all');
      el.style.padding = '1em 1.4em';
      el.style.marginBottom = '20px';
      return el;
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady(input) {
      if (input.controls) return;
      input.controls = this.closest(input, '.form-control');

      if (this.queuedInputErrorText) {
        var text = this.queuedInputErrorText;
        delete this.queuedInputErrorText;
        this.addInputError(input, text);
      }
    }
  }, {
    key: "addInputError",
    value: function addInputError(input, text) {
      if (!input.controls) {
        this.queuedInputErrorText = text;
        return;
      }

      if (!input.errmsg) {
        input.errmsg = document.createElement('div');
        input.errmsg.classList.add('ui-state-error');
        input.controls.appendChild(input.errmsg);
      } else {
        input.errmsg.style.display = '';
      }

      input.errmsg.textContent = text;
    }
  }, {
    key: "removeInputError",
    value: function removeInputError(input) {
      if (!input.controls) {
        delete this.queuedInputErrorText;
      }

      if (!input.errmsg) return;
      input.errmsg.style.display = 'none';
    }
  }, {
    key: "markTabActive",
    value: function markTabActive(row) {
      row.tab.classList.remove('ui-widget-header');
      row.tab.classList.add('ui-state-active');

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.style.display = '';
      } else {
        row.container.style.display = '';
      }
    }
  }, {
    key: "markTabInactive",
    value: function markTabInactive(row) {
      row.tab.classList.add('ui-widget-header');
      row.tab.classList.remove('ui-state-active');

      if (typeof row.rowPane !== 'undefined') {
        row.rowPane.style.display = 'none';
      } else {
        row.container.style.display = 'none';
      }
    }
  }]);

  return jqueryuiTheme;
}(_theme_js__WEBPACK_IMPORTED_MODULE_0__["AbstractTheme"]);
/* Custom stylesheet rules. format: "selector" : "CSS rules" */

jqueryuiTheme.rules = {
  'div[data-schemaid="root"]:after': 'position:relative;color:red;margin:10px 0;font-weight:600;display:block;width:100%;text-align:center;content:"This is an old JSON-Editor 1.x Theme and might not display elements correctly when used with the 2.x version"'
};

/***/ }),

/***/ "./src/themes/spectre.css.js":
/*!***********************************!*\
  !*** ./src/themes/spectre.css.js ***!
  \***********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* eslint-disable */
/* harmony default export */ __webpack_exports__["default"] = ({
  "*": "--primary-color:%235755d9;--gray-color:%23bcc3ce;--light-color:%23fff",
  ".slider:focus": "box-shadow:none",
  "h4 > label + .btn-group": "margin-left:1rem",
  ".text-right > button": "margin-right:0%20!important",
  ".text-left > button": "margin-left:0%20!important",
  ".property-selector": "font-size:0.7rem;font-weight:normal;max-height:260px%20!important;width:395px%20!important",
  ".property-selector .form-checkbox": "margin:0",
  "textarea": "width:100%25;min-height:2rem;resize:vertical",
  "table": "border-collapse:collapse",
  ".table td": "padding:0.4rem%200.4rem",
  ".mr-5": "margin-right:1rem%20!important",
  "div[data-schematype]:not([data-schematype='object'])": "transition:0.5s",
  "div[data-schematype]:not([data-schematype='object']):hover": "background-color:%23eee",
  ".je-table-border td": "border:0.05rem%20solid%20%23dadee4%20!important",
  ".btn-info": "font-size:0.5rem;font-weight:bold;height:0.8rem;padding:0.15rem%200;line-height:0.8;margin:0.3rem%200%200.3rem%200.1rem",
  ".je-label + select": "min-width:5rem",
  ".je-label": "font-weight:600",
  ".btn-action.btn-info": "width:0.8rem",
  ".je-border": "border:0.05rem%20solid%20%23dadee4",
  ".je-panel": "padding:0.2rem;margin:0.2rem;background-color:rgba(218%2C%20222%2C%20228%2C%200.1)",
  ".je-panel-top": "padding:0.2rem;margin:0.2rem;background-color:rgba(218%2C%20222%2C%20228%2C%200.1)",
  ".required:after": "content:%22%20*%22;color:red;font:inherit",
  ".je-align-bottom": "margin-top:auto",
  ".je-desc": "font-size:smaller;margin:0.2rem%200",
  ".je-upload-preview img": "float:left;margin:0%200.5rem%200.5rem%200;max-width:100%25;max-height:5rem;border:3px%20solid%20white;box-shadow:0px%200px%208px%20rgba(0%2C%200%2C%200%2C%200.3);box-sizing:border-box",
  ".je-dropzone": "position:relative;margin:0.5rem%200;border:2px%20dashed%20black;width:100%25;height:60px;background:teal;transition:all%200.5s",
  ".je-dropzone:before": "position:absolute;content:attr(data-text);color:rgba(0%2C%200%2C%200%2C%200.6);left:50%25;top:50%25;transform:translate(-50%25%2C%20-50%25)",
  ".je-dropzone.valid-dropzone": "background:green",
  ".je-dropzone.invalid-dropzone": "background:red",
  ".columns .container.je-noindent": "padding-left:0;padding-right:0",
  ".selectize-control.multi .item": "background:var(--primary-color)%20!important",
  ".select2-container--default   .select2-selection--single   .select2-selection__arrow": "display:none",
  ".select2-container--default .select2-selection--single": "border:none",
  ".select2-container .select2-selection--single .select2-selection__rendered": "padding:0",
  ".select2-container .select2-search--inline .select2-search__field": "margin-top:0",
  ".select2-container--default.select2-container--focus   .select2-selection--multiple": "border:0.05rem%20solid%20var(--gray-color)",
  ".select2-container--default   .select2-selection--multiple   .select2-selection__choice": "margin:0.4rem%200.2rem%200.2rem%200;padding:2px%205px;background-color:var(--primary-color);color:var(--light-color)",
  ".select2-container--default .select2-search--inline .select2-search__field": "line-height:normal",
  ".choices": "margin-bottom:auto",
  ".choices__list--multiple .choices__item": "border:none;background-color:var(--primary-color);color:var(--light-color)",
  ".choices[data-type*='select-multiple'] .choices__button": "border-left:0.05rem%20solid%20%232826a6",
  ".choices__inner": "font-size:inherit;min-height:20px;padding:4px%207.5px%204px%203.75px",
  ".choices[data-type*='select-one'] .choices__inner": "padding-bottom:4px",
  ".choices__list--dropdown .choices__item": "font-size:inherit"
});
/* eslint-enable */

/***/ }),

/***/ "./src/themes/spectre.js":
/*!*******************************!*\
  !*** ./src/themes/spectre.js ***!
  \*******************************/
/*! exports provided: spectreTheme */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "spectreTheme", function() { return spectreTheme; });
/* harmony import */ var _theme_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../theme.js */ "./src/theme.js");
/* harmony import */ var _spectre_css_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./spectre.css.js */ "./src/themes/spectre.css.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

/* Spectre Theme using Spectre CSS framework. <https://picturepan2.github.io/spectre/index.html> */


/* Config options that allows changing various aspects of the output */

var options = {
  disable_theme_rules: false,

  /* Disable creation of Inline Style Rules */
  label_bold: true,

  /* Element labels bold */
  align_bottom: false,

  /* Align elements to bottom of flex container */
  object_indent: false,

  /* Indent nested object elements */
  object_border: false,

  /* Add border around object elements */
  table_border: false,

  /* Add border to array "table" row and cells */
  table_zebrastyle: false,

  /* Add "zebra style" to array "table" rows */
  input_size: 'normal'
  /* Size of input and select elements. "small", "normal", "large" */

};
var spectreTheme = /*#__PURE__*/function (_AbstractTheme) {
  _inherits(spectreTheme, _AbstractTheme);

  var _super = _createSuper(spectreTheme);

  function spectreTheme(jsoneditor) {
    _classCallCheck(this, spectreTheme);

    return _super.call(this, jsoneditor, options);
  }
  /* Functions for setting up the grid container, row and columns */


  _createClass(spectreTheme, [{
    key: "setGridColumnSize",
    value: function setGridColumnSize(el, size, offset) {
      el.classList.add("col-".concat(size));
      if (offset) el.classList.add('col-mx-auto');
    }
  }, {
    key: "getGridContainer",
    value: function getGridContainer() {
      var el = document.createElement('div');
      el.classList.add('container');
      if (!this.options.object_indent) el.classList.add('je-noindent');
      return el;
    }
  }, {
    key: "getGridRow",
    value: function getGridRow() {
      var el = document.createElement('div');
      el.classList.add('columns');
      return el;
    }
  }, {
    key: "getGridColumn",
    value: function getGridColumn() {
      var el = document.createElement('div');
      el.classList.add('column');
      if (this.options.align_bottom) el.classList.add('je-align-bottom');
      return el;
    }
    /* Used for "type: object" or "type: array" (except if "format: tabs-top") */

  }, {
    key: "getIndentedPanel",
    value: function getIndentedPanel() {
      var el = document.createElement('div');
      el.classList.add('je-panel');
      if (this.options.object_border) el.classList.add('je-border');
      return el;
    }
    /* Used for "type: array" with "format: tabs-top" */

  }, {
    key: "getTopIndentedPanel",
    value: function getTopIndentedPanel() {
      var el = document.createElement('div');
      el.classList.add('je-panel-top');
      if (this.options.object_border) el.classList.add('je-border');
      return el;
    }
    /* Button functions */

  }, {
    key: "getHeaderButtonHolder",
    value: function getHeaderButtonHolder() {
      var el = this.getButtonHolder();
      return el;
    }
    /* Button holder for the buttons */

  }, {
    key: "getButtonHolder",
    value: function getButtonHolder() {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getButtonHolder", this).call(this);

      el.classList.add('btn-group');
      return el;
    }
  }, {
    key: "getFormButtonHolder",
    value: function getFormButtonHolder(buttonAlign) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getFormButtonHolder", this).call(this);

      el.classList.remove('btn-group');
      el.classList.add('d-block');
      if (buttonAlign === 'center') el.classList.add('text-center');else if (buttonAlign === 'right') el.classList.add('text-right');else el.classList.add('text-left');
      return el;
    }
  }, {
    key: "getFormButton",
    value: function getFormButton(text, icon, title) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getFormButton", this).call(this, text, icon, title);

      el.classList.add('btn', 'btn-primary', 'mx-2', 'my-1');
      if (this.options.input_size !== 'small') el.classList.remove('btn-sm');
      if (this.options.input_size === 'large') el.classList.add('btn-lg');
      return el;
    }
  }, {
    key: "getButton",
    value: function getButton(text, icon, title) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getButton", this).call(this, text, icon, title);

      el.classList.add('btn', 'btn-sm', 'btn-primary', 'mr-2', 'my-1');
      return el;
    }
  }, {
    key: "getHeader",
    value: function getHeader(text) {
      var el = document.createElement('h4');

      if (typeof text === 'string') {
        el.textContent = text;
      } else {
        el.appendChild(text);
      }

      el.style.display = 'inline-block';
      return el;
    }
  }, {
    key: "getFormInputDescription",
    value: function getFormInputDescription(text) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getFormInputDescription", this).call(this, text);

      el.classList.add('je-desc', 'hide-sm');
      return el;
    }
    /* Label for all elements except checkbox and radio */

  }, {
    key: "getFormInputLabel",
    value: function getFormInputLabel(text, req) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getFormInputLabel", this).call(this, text, req);

      if (this.options.label_bold) el.classList.add('je-label');
      return el;
    }
    /* Checkbox elements */

    /* ToDo: Rename function names for consistency */

  }, {
    key: "getCheckbox",
    value: function getCheckbox() {
      var el = this.getFormInputField('checkbox');
      return el;
    }
  }, {
    key: "getCheckboxLabel",
    value: function getCheckboxLabel(text, req) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getCheckboxLabel", this).call(this, text, req);

      var icon = document.createElement('i');
      icon.classList.add('form-icon');
      el.classList.add('form-checkbox', 'mr-5');
      el.insertBefore(icon, el.firstChild);
      return el;
    }
  }, {
    key: "getFormCheckboxControl",
    value: function getFormCheckboxControl(label, input, compact) {
      label.insertBefore(input, label.firstChild);
      /* Move input into label element */

      if (compact) label.classList.add('form-inline');
      return label;
    }
  }, {
    key: "getMultiCheckboxHolder",
    value: function getMultiCheckboxHolder(controls, label, description, infoText) {
      console.log('mul');
      return _get(_getPrototypeOf(spectreTheme.prototype), "getMultiCheckboxHolder", this).call(this, controls, label, description, infoText);
    }
    /* Radio elements */

  }, {
    key: "getFormRadio",
    value: function getFormRadio(attributes) {
      var el = this.getFormInputField('radio');

      for (var key in attributes) {
        el.setAttribute(key, attributes[key]);
      }

      return el;
    }
  }, {
    key: "getFormRadioLabel",
    value: function getFormRadioLabel(text, req) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getFormRadioLabel", this).call(this, text, req);

      var icon = document.createElement('i');
      icon.classList.add('form-icon');
      el.classList.add('form-radio');
      el.insertBefore(icon, el.firstChild);
      return el;
    }
  }, {
    key: "getFormRadioControl",
    value: function getFormRadioControl(label, input, compact) {
      label.insertBefore(input, label.firstChild);
      /* Move input into label element */

      if (compact) label.classList.add('form-inline');
      return label;
    }
    /* Create input field */

  }, {
    key: "getFormInputField",
    value: function getFormInputField(type) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getFormInputField", this).call(this, type);

      if (!['checkbox', 'radio'].includes(type)) {
        el.classList.add('form-input');
      }

      return el;
    }
    /* Create input field for type="range" */

  }, {
    key: "getRangeInput",
    value: function getRangeInput(min, max, step) {
      var el = this.getFormInputField('range');
      el.classList.add('slider');
      el.classList.remove('form-input');
      el.setAttribute('oninput', 'this.setAttribute("value", this.value)');
      el.setAttribute('min', min);
      el.setAttribute('max', max);
      el.setAttribute('step', step);
      return el;
    }
  }, {
    key: "getRangeControl",
    value: function getRangeControl(input, output) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getRangeControl", this).call(this, input, output);

      el.classList.add('text-center');
      return el;
    }
    /* Create select box field */

  }, {
    key: "getSelectInput",
    value: function getSelectInput(options, multiple) {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getSelectInput", this).call(this, options);

      el.classList.add('form-select');
      return el;
    }
    /* Create textarea field */

  }, {
    key: "getTextareaInput",
    value: function getTextareaInput() {
      var el = document.createElement('textarea');
      el.classList.add('form-input');
      return el;
    }
  }, {
    key: "getFormControl",
    value: function getFormControl(label, input, description, infoText) {
      var group = document.createElement('div');
      group.classList.add('form-group');

      if (label) {
        if (input.type === 'checkbox') {
          label = this.getFormCheckboxControl(label, input, false);
        }

        label.classList.add('form-label');
        group.appendChild(label);
        if (infoText) group.insertBefore(infoText, group.firstChild);
      }

      if (this.options.input_size === 'small') input.classList.add('input-sm', 'select-sm');else if (this.options.input_size === 'large') input.classList.add('input-lg', 'select-lg');
      if (input.type !== 'checkbox') group.appendChild(input);
      if (description) group.appendChild(description);
      return group;
    }
    /* Create input group (input field with buttons) */

  }, {
    key: "getInputGroup",
    value: function getInputGroup(input, buttons) {
      if (!input) return;
      var inputGroup = document.createElement('div');
      inputGroup.classList.add('input-group');
      inputGroup.appendChild(input);

      for (var i = 0; i < buttons.length; i++) {
        buttons[i].classList.add('input-group-btn');
        buttons[i].classList.remove('btn-sm', 'mr-2', 'my-1');
        inputGroup.appendChild(buttons[i]);
      }

      return inputGroup;
    }
    /* Create button for displaying infotext tooltip */

  }, {
    key: "getInfoButton",
    value: function getInfoButton(text) {
      var popover = document.createElement('div');
      popover.classList.add('popover', 'popover-left', 'float-right');
      var button = document.createElement('button');
      button.classList.add('btn', 'btn-secondary', 'btn-info', 'btn-action', 's-circle');
      button.setAttribute('tabindex', '-1');
      /* exclude element from tab order */

      popover.appendChild(button);
      var icon = document.createTextNode('I');
      button.appendChild(icon);
      var container = document.createElement('div');
      container.classList.add('popover-container');
      popover.appendChild(container);
      var card = document.createElement('div');
      card.classList.add('card');
      container.appendChild(card);
      var cardbody = document.createElement('div');
      cardbody.classList.add('card-body');
      cardbody.innerHTML = text;
      card.appendChild(cardbody);
      return popover;
    }
    /* Functions for rendering array with format: "table" */

  }, {
    key: "getTable",
    value: function getTable() {
      var el = _get(_getPrototypeOf(spectreTheme.prototype), "getTable", this).call(this);

      el.classList.add('table', 'table-scroll');
      if (this.options.table_border) el.classList.add('je-table-border');
      if (this.options.table_zebrastyle) el.classList.add('table-striped');
      return el;
    }
    /* Function for rendering progressbar */

  }, {
    key: "getProgressBar",
    value: function getProgressBar() {
      var progressBar = _get(_getPrototypeOf(spectreTheme.prototype), "getProgressBar", this).call(this);

      progressBar.classList.add('progress');
      return progressBar;
    }
    /* Containers for array with format: "tab" */

  }, {
    key: "getTabHolder",
    value: function getTabHolder(propertyName) {
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      var el = document.createElement('div');
      el.classList.add('columns');
      el.innerHTML = "<div class=\"column col-2\"></div><div class=\"column col-10 content\" id=\"".concat(pName, "\"></div>");
      return el;
    }
    /* Containers for array with format: "tab-top" */

  }, {
    key: "getTopTabHolder",
    value: function getTopTabHolder(propertyName) {
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      var el = document.createElement('div');
      el.innerHTML = "<ul class=\"tab\"></ul><div class=\"content\" id=\"".concat(pName, "\"></div>");
      return el;
    }
    /* Tab button for array with format: "tab" */

  }, {
    key: "getTab",
    value: function getTab(span, tabId) {
      var el = document.createElement('a');
      el.classList.add('btn', 'btn-secondary', 'btn-block');
      el.setAttribute('href', "#".concat(tabId));
      el.appendChild(span);
      return el;
    }
    /* Tab button for array with format: "tab-top" */

  }, {
    key: "getTopTab",
    value: function getTopTab(span, tabId) {
      var el = document.createElement('li');
      el.id = tabId;
      el.classList.add('tab-item');
      /* Spectre needs an a tag inside the tab item, not a span */

      var a = document.createElement('a');
      a.setAttribute('href', "#".concat(tabId));
      a.appendChild(span);
      el.appendChild(a);
      return el;
    }
  }, {
    key: "markTabActive",
    value: function markTabActive(row) {
      row.tab.classList.add('active');
      if (typeof row.rowPane !== 'undefined') row.rowPane.style.display = '';else row.container.style.display = '';
    }
  }, {
    key: "markTabInactive",
    value: function markTabInactive(row) {
      row.tab.classList.remove('active');
      if (typeof row.rowPane !== 'undefined') row.rowPane.style.display = 'none';else row.container.style.display = 'none';
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady(input) {
      if (input.localName === 'select') {
        /* Selectize adjustments */
        if (input.classList.contains('selectized')) {
          var selectized = input.nextSibling;

          if (selectized) {
            /* Remove Spectre class 'form-select' as this conflicts with Selectize styling */
            selectized.classList.remove('form-select');
            Array.from(selectized.querySelectorAll('.form-select')).forEach(function (el) {
              el.classList.remove('form-select');
            });
          }
          /* Select2 ajustments */

        } else if (input.classList.contains('select2-hidden-accessible')) {
          var select2 = input.nextSibling;
          var single = select2 && select2.querySelector('.select2-selection--single');
          /* Add Spectre 'form-select' to single-select2 elements */

          if (single) select2.classList.add('form-select');
        }
      }

      if (input.controlgroup) return;
      input.controlgroup = this.closest(input, '.form-group');

      if (this.closest(input, '.compact')) {
        input.controlgroup.style.marginBottom = 0;
      }
    }
    /* Controls output of errormessages displayed in form */

  }, {
    key: "addInputError",
    value: function addInputError(input, text) {
      if (!input.controlgroup) return;
      input.controlgroup.classList.add('has-error');

      if (!input.errmsg) {
        input.errmsg = document.createElement('p');
        input.errmsg.classList.add('form-input-hint');
        input.controlgroup.appendChild(input.errmsg);
      }

      input.errmsg.classList.remove('d-hide');
      input.errmsg.textContent = text;
    }
  }, {
    key: "removeInputError",
    value: function removeInputError(input) {
      if (!input.errmsg) return;
      input.errmsg.classList.add('d-hide');
      input.controlgroup.classList.remove('has-error');
    }
  }]);

  return spectreTheme;
}(_theme_js__WEBPACK_IMPORTED_MODULE_0__["AbstractTheme"]);
/* Custom stylesheet rules. (Does not support comma separated selectors) */

/*  Will create a stylesheet in document head with the id "theme-spectre" if not exists. */

spectreTheme.rules = _spectre_css_js__WEBPACK_IMPORTED_MODULE_1__["default"];

/***/ }),

/***/ "./src/themes/tailwind.css.js":
/*!************************************!*\
  !*** ./src/themes/tailwind.css.js ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* eslint-disable */
/* harmony default export */ __webpack_exports__["default"] = ({
  ".slider": "-webkit-appearance:none;-moz-appearance:none;appearance:none;background:transparent;display:block;border:none;height:1.2rem;width:100%25",
  ".slider:focus": "box-shadow:0%200%200%200%20rgba(87%2C%2085%2C%20217%2C%200.2);outline:none",
  ".slider.tooltip:not([data-tooltip])::after": "content:attr(value)",
  ".slider::-webkit-slider-thumb": "-webkit-appearance:none;background:%23f17405;border-radius:100%25;height:0.6rem;margin-top:-0.25rem;transition:transform%200.2s;width:0.6rem",
  ".slider:active::-webkit-slider-thumb": "transform:scale(1.25);outline:none",
  ".slider::-webkit-slider-runnable-track": "background:%23b2b4b6;border-radius:0.1rem;height:0.1rem;width:100%25",
  "a.tooltips": "position:relative;display:inline",
  "a.tooltips span": "position:absolute;white-space:nowrap;width:auto;padding-left:1rem;padding-right:1rem;color:%23ffffff;background:rgba(56%2C%2056%2C%2056%2C%200.85);height:1.5rem;line-height:1.5rem;text-align:center;visibility:hidden;border-radius:3px",
  "a.tooltips span:after": "content:%22%22;position:absolute;top:50%25;left:100%25;margin-top:-5px;width:0;height:0;border-left:5px%20solid%20rgba(56%2C%2056%2C%2056%2C%200.85);border-top:5px%20solid%20transparent;border-bottom:5px%20solid%20transparent",
  "a:hover.tooltips span": "visibility:visible;opacity:0.9;font-size:0.8rem;right:100%25;top:50%25;margin-top:-12px;margin-right:10px;z-index:999",
  ".json-editor-btntype-properties + div": "font-size:0.8rem;font-weight:normal",
  "textarea": "width:100%25;min-height:2rem;resize:vertical",
  "table": "width:100%25;border-collapse:collapse",
  ".table td": "padding:0rem%200rem",
  "div[data-schematype]:not([data-schematype='object'])": "transition:0.5s",
  "div[data-schematype]:not([data-schematype='object']):hover": "background-color:%23e6f4fe",
  "div[data-schemaid='root']": "position:relative;width:inherit;display:inherit;overflow-x:hidden;z-index:10",
  "select[multiple]": "height:auto",
  "select[multiple].from-select": "height:auto",
  ".je-table-zebra:nth-child(even)": "background-color:%23f2f2f2",
  ".je-table-border": "border:0.5px%20solid%20black",
  ".je-table-hdiv": "border-bottom:1px%20solid%20black",
  ".je-border": "border:0.05rem%20solid%20%233182ce",
  ".je-panel": "width:inherit;padding:0.2rem;margin:0.2rem;background-color:rgba(218%2C%20222%2C%20228%2C%200.1)",
  ".je-panel-top": "width:100%25;padding:0.2rem;margin:0.2rem;background-color:rgba(218%2C%20222%2C%20228%2C%200.1)",
  ".required:after": "content:%22%20*%22;color:red;font:inherit;font-weight:bold",
  ".je-desc": "font-size:smaller;margin:0.2rem%200",
  ".container-xl.je-noindent": "padding-left:0;padding-right:0",
  ".json-editor-btntype-add": "color:white;margin:0.3rem;padding:0.3rem%200.8rem;background-color:%234299e1;box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-webkit-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-moz-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2)",
  ".json-editor-btntype-deletelast": "color:white;margin:0.3rem;padding:0.3rem%200.8rem;background-color:%23e53e3e;box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-webkit-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-moz-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2)",
  ".json-editor-btntype-deleteall": "color:white;margin:0.3rem;padding:0.3rem%200.8rem;background-color:%23000000;box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-webkit-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-moz-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2)",
  ".json-editor-btn-save": "float:right;color:white;margin:0.3rem;padding:0.3rem%200.8rem;background-color:%232b6cb0;box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-webkit-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-moz-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2)",
  ".json-editor-btn-back": "color:white;margin:0.3rem;padding:0.3rem%200.8rem;background-color:%232b6cb0;box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-webkit-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2);-moz-box-shadow:3px%203px%205px%201px%20rgba(4%2C%204%2C%204%2C%200.2)",
  ".json-editor-btntype-delete": "color:%23e53e3e;background-color:rgba(218%2C%20222%2C%20228%2C%200.1);margin:0.03rem;padding:0.1rem",
  ".json-editor-btntype-move": "color:%23000000;background-color:rgba(218%2C%20222%2C%20228%2C%200.1);margin:0.03rem;padding:0.1rem",
  ".json-editor-btn-collapse": "padding:0em%200.8rem;font-size:1.3rem;color:%23e53e3e;background-color:rgba(218%2C%20222%2C%20228%2C%200.1)",
  ".je-upload-preview img": "float:left;margin:0%200.5rem%200.5rem%200;max-width:100%25;max-height:5rem",
  ".je-dropzone": "position:relative;margin:0.5rem%200;border:2px%20dashed%20black;width:100%25;height:60px;background:teal;transition:all%200.5s",
  ".je-dropzone:before": "position:absolute;content:attr(data-text);color:rgba(0%2C%200%2C%200%2C%200.6);left:50%25;top:50%25;transform:translate(-50%25%2C%20-50%25)",
  ".je-dropzone.valid-dropzone": "background:green",
  ".je-dropzone.invalid-dropzone": "background:red"
});
/* eslint-enable */

/***/ }),

/***/ "./src/themes/tailwind.js":
/*!********************************!*\
  !*** ./src/themes/tailwind.js ***!
  \********************************/
/*! exports provided: tailwindTheme */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "tailwindTheme", function() { return tailwindTheme; });
/* harmony import */ var _theme_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../theme.js */ "./src/theme.js");
/* harmony import */ var _tailwind_css_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./tailwind.css.js */ "./src/themes/tailwind.css.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function () { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }



var options = {
  disable_theme_rules: false,

  /* Disable creation of Inline Style Rules */
  label_bold: false,

  /* Element labels bold */
  object_panel_default: true,

  /* Indicates whether to use rules as default or alternate style */
  object_indent: true,

  /* Indent nested object elements */
  object_border: false,

  /* Add border around object elements */
  table_border: false,

  /* Add border to array "table" row and cells */
  table_hdiv: false,

  /* Add bottom-border to array "table" cells */
  table_zebrastyle: false,

  /* Add "zebra style" to array "table" rows */
  input_size: 'small',

  /* Size of input and select elements. "small", "normal", "large" */
  enable_compact: false
};
var tailwindTheme = /*#__PURE__*/function (_AbstractTheme) {
  _inherits(tailwindTheme, _AbstractTheme);

  var _super = _createSuper(tailwindTheme);

  function tailwindTheme(jsoneditor) {
    _classCallCheck(this, tailwindTheme);

    return _super.call(this, jsoneditor, options);
  }

  _createClass(tailwindTheme, [{
    key: "getGridContainer",
    value: function getGridContainer() {
      var el = document.createElement('div');
      el.classList.add('flex', 'flex-col', 'w-full');
      if (!this.options.object_indent) el.classList.add('je-noindent');
      return el;
    }
  }, {
    key: "getGridRow",
    value: function getGridRow() {
      var el = document.createElement('div');
      el.classList.add('flex', 'flex-wrap', 'w-full');
      return el;
    }
  }, {
    key: "getGridColumn",
    value: function getGridColumn() {
      var el = document.createElement('div');
      el.classList.add('flex', 'flex-col');
      return el;
    }
  }, {
    key: "setGridColumnSize",
    value: function setGridColumnSize(el, size, offset) {
      if (size > 0 && size < 12) {
        el.classList.add("w-".concat(size, "/12"), 'px-1');
      } else el.classList.add('w-full', 'px-1');

      if (offset) el.style.marginLeft = "".concat(100 / 12 * offset, "%");
    }
  }, {
    key: "getIndentedPanel",
    value: function getIndentedPanel() {
      var el = document.createElement('div');

      if (this.options.object_panel_default) {
        el.classList.add('w-full', 'p-1');
      } else {
        el.classList.add('relative', 'flex', 'flex-col', 'rounded', 'break-words', 'border', 'bg-white', 'border-0', 'border-blue-400', 'p-1', 'shadow-md');
      }

      if (this.options.object_border) el.classList.add('je-border');
      return el;
    }
    /* Used for "type: array" with "format: tabs-top" */

  }, {
    key: "getTopIndentedPanel",
    value: function getTopIndentedPanel() {
      var el = document.createElement('div');

      if (this.options.object_panel_default) {
        el.classList.add('w-full', 'm-2');
      } else {
        el.classList.add('relative', 'flex', 'flex-col', 'rounded', 'break-words', 'border', 'bg-white', 'border-0', 'border-blue-400', 'p-1', 'shadow-md');
      }

      if (this.options.object_border) el.classList.add('je-border');
      return el;
    }
  }, {
    key: "getTitle",
    value: function getTitle() {
      return this.schema.title;
    }
  }, {
    key: "getSelectInput",
    value: function getSelectInput(options, multiple) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getSelectInput", this).call(this, options);

      if (multiple) el.classList.add('form-multiselect', 'block', 'py-0', 'h-auto', 'w-full', 'px-1', 'text-sm', 'text-black', 'leading-normal', 'bg-white', 'border', 'border-grey', 'rounded');else el.classList.add('form-select', 'block', 'py-0', 'h-6', 'w-full', 'px-1', 'text-sm', 'text-black', 'leading-normal', 'bg-white', 'border', 'border-grey', 'rounded');
      if (this.options.enable_compact) el.classList.add('compact');
      return el;
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady(input) {
      if (input.controlgroup) return;
      input.controlgroup = this.closest(input, '.form-group');

      if (this.closest(input, '.compact')) {
        input.controlgroup.style.marginBottom = 0;
      }
    }
  }, {
    key: "getTextareaInput",
    value: function getTextareaInput() {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getTextareaInput", this).call(this);

      el.classList.add('block', 'w-full', 'px-1', 'text-sm', 'leading-normal', 'bg-white', 'text-black', 'border', 'border-grey', 'rounded');
      if (this.options.enable_compact) el.classList.add('compact');
      el.style.height = 0;
      return el;
    }
    /* Create input field for type="range" */

  }, {
    key: "getRangeInput",
    value: function getRangeInput(min, max, step) {
      var el = this.getFormInputField('range');
      el.classList.add('slider');
      if (this.options.enable_compact) el.classList.add('compact');
      el.setAttribute('oninput', 'this.setAttribute("value", this.value)');
      el.setAttribute('min', min);
      el.setAttribute('max', max);
      el.setAttribute('step', step);
      return el;
    }
  }, {
    key: "getRangeControl",
    value: function getRangeControl(input, output) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getRangeControl", this).call(this, input, output);

      el.classList.add('text-center', 'text-black');
      return el;
    }
    /* Checkbox elements */

  }, {
    key: "getCheckbox",
    value: function getCheckbox() {
      var el = this.getFormInputField('checkbox');
      el.classList.add('form-checkbox', 'text-red-600');
      return el;
    }
  }, {
    key: "getCheckboxLabel",
    value: function getCheckboxLabel(text, req) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getCheckboxLabel", this).call(this, text, req);

      el.classList.add('inline-flex', 'items-center');
      return el;
    }
  }, {
    key: "getFormCheckboxControl",
    value: function getFormCheckboxControl(label, input, compact) {
      label.insertBefore(input, label.firstChild);
      /* Move input into label element */

      if (compact) label.classList.add('inline-flex flex-row');
      return label;
    }
  }, {
    key: "getMultiCheckboxHolder",
    value: function getMultiCheckboxHolder(controls, label, description, infoText) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getMultiCheckboxHolder", this).call(this, controls, label, description, infoText);

      el.classList.add('inline-flex', 'flex-col');
      return el;
    }
    /* Radio elements */

  }, {
    key: "getFormRadio",
    value: function getFormRadio(attributes) {
      var el = this.getFormInputField('radio');
      el.classList.add('form-radio', 'text-red-600');

      for (var key in attributes) {
        el.setAttribute(key, attributes[key]);
      }

      return el;
    }
  }, {
    key: "getFormRadioLabel",
    value: function getFormRadioLabel(text, req) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getFormRadioLabel", this).call(this, text, req);

      el.classList.add('inline-flex', 'items-center', 'mr-2');
      return el;
    }
  }, {
    key: "getFormRadioControl",
    value: function getFormRadioControl(label, input, compact) {
      label.insertBefore(input, label.firstChild);
      /* Move input into label element */

      if (compact) label.classList.add('form-radio');
      return label;
    }
  }, {
    key: "getRadioHolder",
    value: function getRadioHolder(schema, controls, label, description, infoText) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getRadioHolder", this).call(this, controls, label, description, infoText);

      if (schema.options.layout === 'h') el.classList.add('inline-flex', 'flex-row');else el.classList.add('inline-flex', 'flex-col');
      return el;
    }
  }, {
    key: "getFormInputLabel",
    value: function getFormInputLabel(text, req) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getFormInputLabel", this).call(this, text, req);

      if (this.options.label_bold) el.classList.add('font-bold');else el.classList.add('required');
      return el;
    }
  }, {
    key: "getFormInputField",
    value: function getFormInputField(type) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getFormInputField", this).call(this, type);

      if (!['checkbox', 'radio'].includes(type)) el.classList.add('block', 'w-full', 'px-1', 'text-black', 'text-sm', 'leading-normal', 'bg-white', 'border', 'border-grey', 'rounded');
      if (this.options.enable_compact) el.classList.add('compact');
      return el;
    }
  }, {
    key: "getFormInputDescription",
    value: function getFormInputDescription(text) {
      var el = document.createElement('p');
      el.classList.add('block', 'mt-1', 'text-xs');
      if (window.DOMPurify) el.innerHTML = window.DOMPurify.sanitize(text);else el.textContent = this.cleanText(text);
      return el;
    }
  }, {
    key: "getFormControl",
    value: function getFormControl(label, input, description, infoText) {
      var group = document.createElement('div');
      group.classList.add('form-group', 'mb-1', 'w-full');

      if (label) {
        label.classList.add('text-xs');

        if (input.type === 'checkbox') {
          input.classList.add('form-checkbox', 'text-xs', 'text-red-600', 'mr-1');
          label.classList.add('items-center', 'flex');
          label = this.getFormCheckboxControl(label, input, false, infoText);
        }

        if (input.type === 'radio') {
          input.classList.add('form-radio', 'text-red-600', 'mr-1');
          label.classList.add('items-center', 'flex');
          label = this.getFormRadioControl(label, input, false, infoText);
        }

        group.appendChild(label);
        if (!['checkbox', 'radio'].includes(input.type) && infoText) group.appendChild(infoText);
      }

      if (!['checkbox', 'radio'].includes(input.type)) {
        if (this.options.input_size === 'small') input.classList.add('text-xs');else if (this.options.input_size === 'normal') input.classList.add('text-base');else if (this.options.input_size === 'large') input.classList.add('text-xl');
        group.appendChild(input);
      }

      if (description) group.appendChild(description);
      return group;
    }
  }, {
    key: "getHeaderButtonHolder",
    value: function getHeaderButtonHolder() {
      var el = this.getButtonHolder();
      el.classList.add('text-sm');
      return el;
    }
  }, {
    key: "getButtonHolder",
    value: function getButtonHolder() {
      var el = document.createElement('div');
      el.classList.add('flex', 'relative', 'inline-flex', 'align-middle');
      return el;
    }
  }, {
    key: "getButton",
    value: function getButton(text, icon, title) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getButton", this).call(this, text, icon, title);

      el.classList.add('inline-block', 'align-middle', 'text-center', 'text-sm', 'bg-blue-700', 'text-white', 'py-1', 'pr-1', 'm-2', 'shadow', 'select-none', 'whitespace-no-wrap', 'rounded');
      return el;
    }
    /* Button for displaying infotext tooltip */

  }, {
    key: "getInfoButton",
    value: function getInfoButton(text) {
      var tooltip = document.createElement('a');
      tooltip.classList.add('tooltips', 'float-right');
      tooltip.innerHTML = 'ⓘ';
      var span = document.createElement('span');
      span.innerHTML = text;
      tooltip.appendChild(span);
      return tooltip;
    }
  }, {
    key: "getTable",
    value: function getTable() {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getTable", this).call(this);

      if (this.options.table_border) el.classList.add('je-table-border');else el.classList.add('table', 'border', 'p-0');
      return el;
    }
  }, {
    key: "getTableRow",
    value: function getTableRow() {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getTableRow", this).call(this);

      if (this.options.table_border) el.classList.add('je-table-border');
      if (this.options.table_zebrastyle) el.classList.add('je-table-zebra');
      return el;
    }
  }, {
    key: "getTableHeaderCell",
    value: function getTableHeaderCell(text) {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getTableHeaderCell", this).call(this, text);

      if (this.options.table_border) el.classList.add('je-table-border');else if (this.options.table_hdiv) el.classList.add('je-table-hdiv');else el.classList.add('text-xs', 'border', 'p-0', 'm-0');
      return el;
    }
  }, {
    key: "getTableCell",
    value: function getTableCell() {
      var el = _get(_getPrototypeOf(tailwindTheme.prototype), "getTableCell", this).call(this);

      if (this.options.table_border) el.classList.add('je-table-border');else if (this.options.table_hdiv) el.classList.add('je-table-hdiv');else el.classList.add('border-0', 'p-0', 'm-0');
      return el;
    }
  }, {
    key: "addInputError",
    value: function addInputError(input, text) {
      if (!input.controlgroup) return;
      input.controlgroup.classList.add('has-error');
      input.classList.add('bg-red-600');

      if (!input.errmsg) {
        input.errmsg = document.createElement('p');
        input.errmsg.classList.add('block', 'mt-1', 'text-xs', 'text-red');
        input.controlgroup.appendChild(input.errmsg);
      } else {
        input.errmsg.style.display = '';
      }

      input.errmsg.textContent = text;
    }
  }, {
    key: "removeInputError",
    value: function removeInputError(input) {
      if (!input.errmsg) return;
      input.errmsg.style.display = 'none';
      input.classList.remove('bg-red-600');
      input.controlgroup.classList.remove('has-error');
    }
  }, {
    key: "getTabHolder",
    value: function getTabHolder(propertyName) {
      var el = document.createElement('div');
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      el.innerHTML = "<div class='w-2/12' id='".concat(pName, "'><ul class='list-reset pl-0 mb-0'></ul></div><div class='w-10/12' id='").concat(pName, "'></div>");
      el.classList.add('flex');
      return el;
    }
  }, {
    key: "addTab",
    value: function addTab(holder, tab) {
      holder.children[0].children[0].appendChild(tab);
    }
  }, {
    key: "getTopTabHolder",
    value: function getTopTabHolder(propertyName) {
      var pName = typeof propertyName === 'undefined' ? '' : propertyName;
      var el = document.createElement('div');
      el.innerHTML = "<ul class='nav-tabs flex list-reset pl-0 mb-0 border-b border-grey-light' id='".concat(pName, "'></ul><div class='p-6 block' id='").concat(pName, "'></div>");
      return el;
    }
  }, {
    key: "getTab",
    value: function getTab(text, tabId) {
      var liel = document.createElement('li');
      liel.classList.add('nav-item', 'flex-col', 'text-center', 'text-white', 'bg-blue-500', 'shadow-md', 'border', 'p-2', 'mb-2', 'mr-2', 'hover:bg-blue-400', 'rounded');
      var ael = document.createElement('a');
      ael.classList.add('nav-link', 'text-center');
      ael.setAttribute('href', "#".concat(tabId));
      ael.setAttribute('data-toggle', 'tab');
      ael.appendChild(text);
      liel.appendChild(ael);
      return liel;
    }
  }, {
    key: "getTopTab",
    value: function getTopTab(text, tabId) {
      var el = document.createElement('li');
      el.classList.add('nav-item', 'flex', 'border-l', 'border-t', 'border-r');
      var a = document.createElement('a');
      a.classList.add('nav-link', '-mb-px', 'flex-row', 'text-center', 'bg-white', 'p-2', 'hover:bg-blue-400', 'rounded-t');
      a.setAttribute('href', "#".concat(tabId));
      a.setAttribute('data-toggle', 'tab');
      a.appendChild(text);
      el.appendChild(a);
      return el;
    }
  }, {
    key: "getTabContent",
    value: function getTabContent() {
      var el = document.createElement('div');
      el.setAttribute('role', 'tabpanel');
      return el;
    }
  }, {
    key: "getTopTabContent",
    value: function getTopTabContent() {
      var el = document.createElement('div');
      el.setAttribute('role', 'tabpanel');
      return el;
    }
  }, {
    key: "markTabActive",
    value: function markTabActive(row) {
      row.tab.firstChild.classList.add('block');

      if (row.tab.firstChild.classList.contains('border-b') === true) {
        row.tab.firstChild.classList.add('border-b-0');
        row.tab.firstChild.classList.remove('border-b');
      } else {
        row.tab.firstChild.classList.add('border-b-0');
      }

      if (row.container.classList.contains('hidden') === true) {
        row.container.classList.remove('hidden');
        row.container.classList.add('block');
      } else {
        row.container.classList.add('block');
      }
    }
  }, {
    key: "markTabInactive",
    value: function markTabInactive(row) {
      if (row.tab.firstChild.classList.contains('border-b-0') === true) {
        row.tab.firstChild.classList.add('border-b');
        row.tab.firstChild.classList.remove('border-b-0');
      } else {
        row.tab.firstChild.classList.add('border-b');
      }

      if (row.container.classList.contains('block') === true) {
        row.container.classList.remove('block');
        row.container.classList.add('hidden');
      }
    }
  }, {
    key: "getProgressBar",
    value: function getProgressBar() {
      var min = 0;
      var max = 100;
      var start = 0;
      var container = document.createElement('div');
      container.classList.add('progress');
      var bar = document.createElement('div');
      bar.classList.add('bg-blue', 'leading-none', 'py-1', 'text-xs', 'text-center', 'text-white');
      bar.setAttribute('role', 'progressbar');
      bar.setAttribute('aria-valuenow', start);
      bar.setAttribute('aria-valuemin', min);
      bar.setAttribute('aria-valuenax', max);
      bar.innerHTML = "".concat(start, "%");
      container.appendChild(bar);
      return container;
    }
  }, {
    key: "updateProgressBar",
    value: function updateProgressBar(progressBar, progress) {
      if (!progressBar) return;
      var bar = progressBar.firstChild;
      var percentage = "".concat(progress, "%");
      bar.setAttribute('aria-valuenow', progress);
      bar.style.width = percentage;
      bar.innerHTML = percentage;
    }
  }, {
    key: "updateProgressBarUnknown",
    value: function updateProgressBarUnknown(progressBar) {
      if (!progressBar) return;
      var bar = progressBar.firstChild;
      progressBar.classList.add('progress', 'bg-blue', 'leading-none', 'py-1', 'text-xs', 'text-center', 'text-white', 'block');
      bar.removeAttribute('aria-valuenow');
      bar.classList.add('w-full');
      bar.innerHTML = '';
    }
  }, {
    key: "getInputGroup",
    value: function getInputGroup(input, buttons) {
      if (!input) return;
      var inputGroupContainer = document.createElement('div');
      inputGroupContainer.classList.add('relative', 'items-stretch', 'w-full');
      inputGroupContainer.appendChild(input);
      var inputGroup = document.createElement('div');
      inputGroup.classList.add('-mr-1');
      inputGroupContainer.appendChild(inputGroup);

      for (var i = 0; i < buttons.length; i++) {
        inputGroup.appendChild(buttons[i]);
      }

      return inputGroupContainer;
    }
  }]);

  return tailwindTheme;
}(_theme_js__WEBPACK_IMPORTED_MODULE_0__["AbstractTheme"]);
/* Custom stylesheet rules. (Does not support comma separated selectors) */

/*  Will create a stylesheet in document head with the id "theme-spectre" if not exists. */

tailwindTheme.rules = _tailwind_css_js__WEBPACK_IMPORTED_MODULE_1__["default"];

/***/ }),

/***/ "./src/utilities.js":
/*!**************************!*\
  !*** ./src/utilities.js ***!
  \**************************/
/*! exports provided: isPlainObject, deepCopy, extend, trigger, getShadowParent, hasOwnProperty, isNumber, isInteger */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isPlainObject", function() { return isPlainObject; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "deepCopy", function() { return deepCopy; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "extend", function() { return extend; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "trigger", function() { return trigger; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getShadowParent", function() { return getShadowParent; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "hasOwnProperty", function() { return hasOwnProperty; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isNumber", function() { return isNumber; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isInteger", function() { return isInteger; });
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

/**
 * Taken from jQuery 2.1.3
 *
 * #### NOTE
 * Not plain objects is,
 * - Any object or value whose internal [[Class]] property is not "[object Object]"
 * - DOM nodes
 * - window
 *
 * @param {Object} obj - Variable name
 * @returns {Boolean}
 */
function isPlainObject(obj) {
  if (obj === null) return false;
  if (_typeof(obj) !== 'object' || obj.nodeType || obj === obj.window) return false;
  if (obj.constructor && !hasOwnProperty(obj.constructor.prototype, 'isPrototypeOf')) return false;
  /* Most likely |obj| is a plain object, created by {} or constructed with new Object */

  return true;
}
function deepCopy(target) {
  return isPlainObject(target) ? extend({}, target) : Array.isArray(target) ? target.map(deepCopy) : target;
}
function extend(destination) {
  for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    args[_key - 1] = arguments[_key];
  }

  args.forEach(function (source) {
    Object.keys(source).forEach(function (property) {
      if (source[property] && isPlainObject(source[property])) {
        if (!hasOwnProperty(destination, property)) destination[property] = {};
        extend(destination[property], source[property]);
      } else if (Array.isArray(source[property])) {
        destination[property] = deepCopy(source[property]);
      } else {
        destination[property] = source[property];
      }
    });
  });
  return destination;
}
function trigger(el, event) {
  var e = document.createEvent('HTMLEvents');
  e.initEvent(event, true, true);
  el.dispatchEvent(e);
}
/**
 * Helper function to locate a shadowRoot parent if at all
 *
 * @param {Element} node - Node
 */

function getShadowParent(node) {
  return node && (node.toString() === '[object ShadowRoot]' ? node : getShadowParent(node.parentNode));
}
/**
 * Helper function to check own property key
 *
 * @see https://eslint.org/docs/rules/no-prototype-builtins
 */

function hasOwnProperty(obj, key) {
  return obj && Object.prototype.hasOwnProperty.call(obj, key);
} // From https://github.com/angular/angular.js/blob/master/src/ng/directive/input.js

var NUMBER_REGEXP = /^\s*(-|\+)?(\d+|(\d*(\.\d*)))([eE][+-]?\d+)?\s*$/;
function isNumber(value) {
  if (typeof value === 'undefined' || value === null) return false;
  var match = value.match(NUMBER_REGEXP);
  var v = parseFloat(value);
  return match !== null && !isNaN(v) && isFinite(v);
}
var INTEGER_REGEXP = /^\s*(-|\+)?(\d+)\s*$/;
function isInteger(value) {
  if (typeof value === 'undefined' || value === null) return false;
  var match = value.match(INTEGER_REGEXP);
  var v = parseInt(value);
  return match !== null && !isNaN(v) && isFinite(v);
}

/***/ }),

/***/ "./src/validator.js":
/*!**************************!*\
  !*** ./src/validator.js ***!
  \**************************/
/*! exports provided: Validator */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Validator", function() { return Validator; });
/* harmony import */ var _validators_ip_validator_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./validators/ip-validator.js */ "./src/validators/ip-validator.js");
/* harmony import */ var _utilities_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utilities.js */ "./src/utilities.js");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }



var Validator = /*#__PURE__*/function () {
  function Validator(jsoneditor, schema, options, defaults) {
    _classCallCheck(this, Validator);

    this.jsoneditor = jsoneditor;
    this.schema = schema || this.jsoneditor.schema;
    this.options = options || {};
    this.translate = this.jsoneditor.translate || defaults.translate;
    this.defaults = defaults;
    this._validateSubSchema = {
      "enum": function _enum(schema, value, path) {
        var stringified = JSON.stringify(value);
        var valid = schema["enum"].some(function (e) {
          return stringified === JSON.stringify(e);
        });

        if (!valid) {
          return [{
            path: path,
            property: 'enum',
            message: this.translate('error_enum')
          }];
        }

        return [];
      },
      "extends": function _extends(schema, value, path) {
        var _this = this;

        var validate = function validate(errors, e) {
          errors.push.apply(errors, _toConsumableArray(_this._validateSchema(e, value, path)));
          return errors;
        };

        return schema["extends"].reduce(validate, []);
      },
      allOf: function allOf(schema, value, path) {
        var _this2 = this;

        var validate = function validate(errors, e) {
          errors.push.apply(errors, _toConsumableArray(_this2._validateSchema(e, value, path)));
          return errors;
        };

        return schema.allOf.reduce(validate, []);
      },
      anyOf: function anyOf(schema, value, path) {
        var _this3 = this;

        var valid = schema.anyOf.some(function (e) {
          return !_this3._validateSchema(e, value, path).length;
        });

        if (!valid) {
          return [{
            path: path,
            property: 'anyOf',
            message: this.translate('error_anyOf')
          }];
        }

        return [];
      },
      oneOf: function oneOf(schema, value, path) {
        var _this4 = this;

        var valid = 0;
        var oneofErrors = [];
        schema.oneOf.forEach(function (o, i) {
          /* Set the error paths to be path.oneOf[i].rest.of.path */
          var tmp = _this4._validateSchema(o, value, path);

          if (!tmp.length) {
            valid++;
          }

          tmp.forEach(function (e) {
            e.path = "".concat(path, ".oneOf[").concat(i, "]").concat(e.path.substr(path.length));
          });
          oneofErrors.push.apply(oneofErrors, _toConsumableArray(tmp));
        });
        var errors = [];

        if (valid !== 1) {
          errors.push({
            path: path,
            property: 'oneOf',
            message: this.translate('error_oneOf', [valid])
          });
          errors.push.apply(errors, oneofErrors);
        }

        return errors;
      },
      not: function not(schema, value, path) {
        if (!this._validateSchema(schema.not, value, path).length) {
          return [{
            path: path,
            property: 'not',
            message: this.translate('error_not')
          }];
        }

        return [];
      },
      type: function type(schema, value, path) {
        var _this5 = this;

        /* Union type */
        if (Array.isArray(schema.type)) {
          var valid = schema.type.some(function (e) {
            return _this5._checkType(e, value);
          });

          if (!valid) {
            return [{
              path: path,
              property: 'type',
              message: this.translate('error_type_union')
            }];
          }
        } else {
          /* Simple type */
          if (['date', 'time', 'datetime-local'].includes(schema.format) && schema.type === 'integer') {
            /* Hack to get validator to validate as string even if value is integer */

            /* As validation of 'date', 'time', 'datetime-local' is done in separate validator */
            if (!this._checkType('string', "".concat(value))) {
              return [{
                path: path,
                property: 'type',
                message: this.translate('error_type', [schema.format])
              }];
            }
          } else if (!this._checkType(schema.type, value)) {
            return [{
              path: path,
              property: 'type',
              message: this.translate('error_type', [schema.type])
            }];
          }
        }

        return [];
      },
      disallow: function disallow(schema, value, path) {
        var _this6 = this;

        /* Union type */
        if (Array.isArray(schema.disallow)) {
          var invalid = schema.disallow.some(function (e) {
            return _this6._checkType(e, value);
          });

          if (invalid) {
            return [{
              path: path,
              property: 'disallow',
              message: this.translate('error_disallow_union')
            }];
          }
        } else {
          /* Simple type */
          if (this._checkType(schema.disallow, value)) {
            return [{
              path: path,
              property: 'disallow',
              message: this.translate('error_disallow', [schema.disallow])
            }];
          }
        }

        return [];
      }
    };
    this._validateNumberSubSchema = {
      multipleOf: function multipleOf(schema, value, path) {
        return this._validateNumberSubSchemaMultipleDivisible(schema, value, path);
      },
      divisibleBy: function divisibleBy(schema, value, path) {
        return this._validateNumberSubSchemaMultipleDivisible(schema, value, path);
      },
      maximum: function maximum(schema, value, path) {
        /* Vanilla JS, prone to floating point rounding errors (e.g. .999999999999999 == 1) */
        var valid = schema.exclusiveMaximum ? value < schema.maximum : value <= schema.maximum;
        /* Use math.js is available */

        if (window.math) {
          valid = window.math[schema.exclusiveMaximum ? 'smaller' : 'smallerEq'](window.math.bignumber(value), window.math.bignumber(schema.maximum));
        } else if (window.Decimal) {
          /* Use Decimal.js if available */
          valid = new window.Decimal(value)[schema.exclusiveMaximum ? 'lt' : 'lte'](new window.Decimal(schema.maximum));
        }

        if (!valid) {
          return [{
            path: path,
            property: 'maximum',
            message: this.translate(schema.exclusiveMaximum ? 'error_maximum_excl' : 'error_maximum_incl', [schema.maximum])
          }];
        }

        return [];
      },
      minimum: function minimum(schema, value, path) {
        /* Vanilla JS, prone to floating point rounding errors (e.g. .999999999999999 == 1) */
        var valid = schema.exclusiveMinimum ? value > schema.minimum : value >= schema.minimum;
        /* Use math.js is available */

        if (window.math) {
          valid = window.math[schema.exclusiveMinimum ? 'larger' : 'largerEq'](window.math.bignumber(value), window.math.bignumber(schema.minimum));
          /* Use Decimal.js if available */
        } else if (window.Decimal) {
          valid = new window.Decimal(value)[schema.exclusiveMinimum ? 'gt' : 'gte'](new window.Decimal(schema.minimum));
        }

        if (!valid) {
          return [{
            path: path,
            property: 'minimum',
            message: this.translate(schema.exclusiveMinimum ? 'error_minimum_excl' : 'error_minimum_incl', [schema.minimum])
          }];
        }

        return [];
      }
    };
    this._validateStringSubSchema = {
      maxLength: function maxLength(schema, value, path) {
        var errors = [];

        if ("".concat(value).length > schema.maxLength) {
          errors.push({
            path: path,
            property: 'maxLength',
            message: this.translate('error_maxLength', [schema.maxLength])
          });
        }

        return errors;
      },

      /* `minLength` */
      minLength: function minLength(schema, value, path) {
        if ("".concat(value).length < schema.minLength) {
          return [{
            path: path,
            property: 'minLength',
            message: this.translate(schema.minLength === 1 ? 'error_notempty' : 'error_minLength', [schema.minLength])
          }];
        }

        return [];
      },

      /* `pattern` */
      pattern: function pattern(schema, value, path) {
        if (!new RegExp(schema.pattern).test(value)) {
          return [{
            path: path,
            property: 'pattern',
            message: schema.options && schema.options.patternmessage ? schema.options.patternmessage : this.translate('error_pattern', [schema.pattern])
          }];
        }

        return [];
      }
    };
    this._validateArraySubSchema = {
      items: function items(schema, value, path) {
        var _this7 = this;

        var errors = [];

        if (Array.isArray(schema.items)) {
          for (var i = 0; i < value.length; i++) {
            /* If this item has a specific schema tied to it */

            /* Validate against it */
            if (schema.items[i]) {
              console.log('-->');
              errors.push.apply(errors, _toConsumableArray(this._validateSchema(schema.items[i], value[i], "".concat(path, ".").concat(i))));
              /* If all additional items are allowed */
            } else if (schema.additionalItems === true) {
              break;
              /* If additional items is a schema */

              /* TODO: Incompatibility between version 3 and 4 of the spec */
            } else if (schema.additionalItems) {
              errors.push.apply(errors, _toConsumableArray(this._validateSchema(schema.additionalItems, value[i], "".concat(path, ".").concat(i))));
              /* If no additional items are allowed */
            } else if (schema.additionalItems === false) {
              errors.push({
                path: path,
                property: 'additionalItems',
                message: this.translate('error_additionalItems')
              });
              break;
              /* Default for `additionalItems` is an empty schema */
            } else {
              break;
            }
          }
          /* `items` is a schema */

        } else {
          /* Each item in the array must validate against the schema */
          value.forEach(function (e, i) {
            errors.push.apply(errors, _toConsumableArray(_this7._validateSchema(schema.items, e, "".concat(path, ".").concat(i))));
          });
        }

        return errors;
      },
      maxItems: function maxItems(schema, value, path) {
        if (value.length > schema.maxItems) {
          return [{
            path: path,
            property: 'maxItems',
            message: this.translate('error_maxItems', [schema.maxItems])
          }];
        }

        return [];
      },
      minItems: function minItems(schema, value, path) {
        if (value.length < schema.minItems) {
          return [{
            path: path,
            property: 'minItems',
            message: this.translate('error_minItems', [schema.minItems])
          }];
        }

        return [];
      },
      uniqueItems: function uniqueItems(schema, value, path) {
        var seen = {};

        for (var i = 0; i < value.length; i++) {
          var valid = JSON.stringify(value[i]);

          if (seen[valid]) {
            return [{
              path: path,
              property: 'uniqueItems',
              message: this.translate('error_uniqueItems')
            }];
          }

          seen[valid] = true;
        }

        return [];
      }
    };
    this._validateObjectSubSchema = {
      maxProperties: function maxProperties(schema, value, path) {
        if (Object.keys(value).length > schema.maxProperties) {
          return [{
            path: path,
            property: 'maxProperties',
            message: this.translate('error_maxProperties', [schema.maxProperties])
          }];
        }

        return [];
      },
      minProperties: function minProperties(schema, value, path) {
        if (Object.keys(value).length < schema.minProperties) {
          return [{
            path: path,
            property: 'minProperties',
            message: this.translate('error_minProperties', [schema.minProperties])
          }];
        }

        return [];
      },
      required: function required(schema, value, path) {
        var _this8 = this;

        var errors = [];

        if (Array.isArray(schema.required)) {
          schema.required.forEach(function (e) {
            if (typeof value[e] !== 'undefined') return;

            var editor = _this8.jsoneditor.getEditor("".concat(path, ".").concat(e));
            /* Ignore required error if editor is of type "button" or "info" */


            if (editor && ['button', 'info'].includes(editor.schema.format || editor.schema.type)) return;
            errors.push({
              path: path,
              property: 'required',
              message: _this8.translate('error_required', [e])
            });
          });
        }

        return errors;
      },
      properties: function properties(schema, value, path, validatedProperties) {
        var _this9 = this;

        var errors = [];
        Object.entries(schema.properties).forEach(function (_ref) {
          var _ref2 = _slicedToArray(_ref, 2),
              key = _ref2[0],
              prop = _ref2[1];

          validatedProperties[key] = true;
          errors.push.apply(errors, _toConsumableArray(_this9._validateSchema(prop, value[key], "".concat(path, ".").concat(key))));
        });
        return errors;
      },
      patternProperties: function patternProperties(schema, value, path, validatedProperties) {
        var _this10 = this;

        var errors = [];
        Object.entries(schema.patternProperties).forEach(function (_ref3) {
          var _ref4 = _slicedToArray(_ref3, 2),
              i = _ref4[0],
              prop = _ref4[1];

          var regex = new RegExp(i);
          /* Check which properties match */

          Object.entries(value).forEach(function (_ref5) {
            var _ref6 = _slicedToArray(_ref5, 2),
                j = _ref6[0],
                v = _ref6[1];

            if (regex.test(j)) {
              validatedProperties[j] = true;
              errors.push.apply(errors, _toConsumableArray(_this10._validateSchema(prop, v, "".concat(path, ".").concat(j))));
            }
          });
        });
        return errors;
      }
    };
    this._validateObjectSubSchema2 = {
      additionalProperties: function additionalProperties(schema, value, path, validatedProperties) {
        var errors = [];
        var keys = Object.keys(value);

        for (var i = 0; i < keys.length; i++) {
          var k = keys[i];
          if (validatedProperties[k]) continue;
          /* No extra properties allowed */

          if (!schema.additionalProperties) {
            errors.push({
              path: path,
              property: 'additionalProperties',
              message: this.translate('error_additional_properties', [k])
            });
            break;
            /* Allowed */
          } else if (schema.additionalProperties === true) {
            break;
            /* Must match schema */

            /* TODO: incompatibility between version 3 and 4 of the spec */
          } else {
            errors.push.apply(errors, _toConsumableArray(this._validateSchema(schema.additionalProperties, value[k], "".concat(path, ".").concat(k))));
          }
        }

        return errors;
      },
      dependencies: function dependencies(schema, value, path) {
        var _this11 = this;

        var errors = [];
        Object.entries(schema.dependencies).forEach(function (_ref7) {
          var _ref8 = _slicedToArray(_ref7, 2),
              i = _ref8[0],
              dep = _ref8[1];

          /* Doesn't need to meet the dependency */
          if (typeof value[i] === 'undefined') return;
          /* Property dependency */

          if (Array.isArray(dep)) {
            dep.forEach(function (d) {
              if (typeof value[d] === 'undefined') {
                errors.push({
                  path: path,
                  property: 'dependencies',
                  message: _this11.translate('error_dependency', [d])
                });
              }
            });
            /* Schema dependency */
          } else {
            errors.push.apply(errors, _toConsumableArray(_this11._validateSchema(dep, value, path)));
          }
        });
        return errors;
      }
    };
  }

  _createClass(Validator, [{
    key: "fitTest",
    value: function fitTest(value, givenSchema) {
      var weight = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 10000000;
      var fit = {
        match: 0,
        extra: 0
      };

      if (_typeof(value) === 'object' && value !== null) {
        /* Work on a copy of the schema */
        var properties = this._getSchema(givenSchema).properties;

        for (var i in properties) {
          if (!Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["hasOwnProperty"])(properties, i)) {
            fit.extra += weight;
            continue;
          }

          if (_typeof(value[i]) === 'object' && _typeof(properties[i]) === 'object' && _typeof(properties[i].properties) === 'object') {
            var result = this.fitTest(value[i], properties[i], weight / 100);
            fit.match += result.match;
            fit.extra += result.extra;
          }

          if (typeof value[i] !== 'undefined') {
            fit.match += weight;
          }
        }
      }

      return fit;
    }
  }, {
    key: "_getSchema",
    value: function _getSchema(schema) {
      return typeof schema === 'undefined' ? Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.jsoneditor.expandRefs(this.schema)) : schema;
    }
  }, {
    key: "validate",
    value: function validate(value) {
      return this._validateSchema(this.schema, value);
    }
  }, {
    key: "_validateSchema",
    value: function _validateSchema(schema, value, path) {
      var _this12 = this;

      var errors = [];
      path = path || 'root';
      /* Work on a copy of the schema */

      schema = Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, this.jsoneditor.expandRefs(schema));
      /*
       * Type Agnostic Validation
       */

      /* Version 3 `required` and `required_by_default` */

      if (typeof value === 'undefined') {
        return this._validateV3Required(schema, value, path);
      }

      Object.keys(schema).forEach(function (key) {
        if (_this12._validateSubSchema[key]) {
          errors.push.apply(errors, _toConsumableArray(_this12._validateSubSchema[key].call(_this12, schema, value, path)));
        }
      });
      /*
       * Type Specific Validation
       */

      errors.push.apply(errors, _toConsumableArray(this._validateByValueType(schema, value, path)));

      if (schema.links) {
        schema.links.forEach(function (s, m) {
          if (s.rel && s.rel.toLowerCase() === 'describedby') {
            schema = _this12._expandSchemaLink(schema, m);
            errors.push.apply(errors, _toConsumableArray(_this12._validateSchema(schema, value, path, _this12.translate)));
          }
        });
      }
      /* date, time and datetime-local validation */


      if (['date', 'time', 'datetime-local'].includes(schema.format)) {
        errors.push.apply(errors, _toConsumableArray(this._validateDateTimeSubSchema(schema, value, path)));
      }
      /* custom validator */


      errors.push.apply(errors, _toConsumableArray(this._validateCustomValidator(schema, value, path)));
      /* Remove duplicate errors and add "errorcount" property */

      return this._removeDuplicateErrors(errors);
    }
  }, {
    key: "_expandSchemaLink",
    value: function _expandSchemaLink(schema, m) {
      var href = schema.links[m].href;
      var data = this.jsoneditor.root.getValue();
      var template = this.jsoneditor.compileTemplate(href, this.jsoneditor.template);
      var ref = document.location.origin + document.location.pathname + template(data);
      schema.links = schema.links.slice(0, m).concat(schema.links.slice(m + 1));
      return Object(_utilities_js__WEBPACK_IMPORTED_MODULE_1__["extend"])({}, schema, this.jsoneditor.refs[ref]);
    }
  }, {
    key: "_validateV3Required",
    value: function _validateV3Required(schema, value, path) {
      if (typeof schema.required !== 'undefined' && schema.required === true || typeof schema.required === 'undefined' && this.jsoneditor.options.required_by_default === true) {
        return [{
          path: path,
          property: 'required',
          message: this.translate('error_notset')
        }];
      }

      return [];
    }
  }, {
    key: "_validateByValueType",
    value: function _validateByValueType(schema, value, path) {
      var _this13 = this;

      var errors = [];
      if (value === null) return errors;
      /* Number Specific Validation */

      if (typeof value === 'number') {
        /* `multipleOf` and `divisibleBy` */

        /* `maximum` */

        /* `minimum` */
        Object.keys(schema).forEach(function (key) {
          if (_this13._validateNumberSubSchema[key]) {
            errors.push.apply(errors, _toConsumableArray(_this13._validateNumberSubSchema[key].call(_this13, schema, value, path)));
          }
        });
        /* String specific validation */
      } else if (typeof value === 'string') {
        /* `maxLength` */

        /* `minLength` */

        /* `pattern` */
        Object.keys(schema).forEach(function (key) {
          if (_this13._validateStringSubSchema[key]) {
            errors.push.apply(errors, _toConsumableArray(_this13._validateStringSubSchema[key].call(_this13, schema, value, path)));
          }
        });
        /* Array specific validation */
      } else if (Array.isArray(value)) {
        /* `items` and `additionalItems`
        /* `maxItems`
        /* `minItems`
        /* `uniqueItems` */
        Object.keys(schema).forEach(function (key) {
          if (_this13._validateArraySubSchema[key]) {
            errors.push.apply(errors, _toConsumableArray(_this13._validateArraySubSchema[key].call(_this13, schema, value, path)));
          }
        });
        /* Object specific validation */
      } else if (_typeof(value) === 'object') {
        var validatedProperties = {};
        /* `maxProperties`
        /* `minProperties`
        /*  Version 4 `required`
        /* `properties`
        /* `patternProperties` */

        Object.keys(schema).forEach(function (key) {
          if (_this13._validateObjectSubSchema[key]) {
            errors.push.apply(errors, _toConsumableArray(_this13._validateObjectSubSchema[key].call(_this13, schema, value, path, validatedProperties)));
          }
        });
        /* The no_additional_properties option currently doesn't work with extended schemas that use oneOf or anyOf or allOf */

        if (typeof schema.additionalProperties === 'undefined' && this.jsoneditor.options.no_additional_properties && !schema.oneOf && !schema.anyOf && !schema.allOf) {
          schema.additionalProperties = false;
        }
        /* `additionalProperties` */

        /* `dependencies` */


        Object.keys(schema).forEach(function (key) {
          if (typeof _this13._validateObjectSubSchema2[key] !== 'undefined') {
            errors.push.apply(errors, _toConsumableArray(_this13._validateObjectSubSchema2[key].call(_this13, schema, value, path, validatedProperties)));
          }
        });
      }

      return errors;
    }
  }, {
    key: "_validateNumberSubSchemaMultipleDivisible",
    value: function _validateNumberSubSchemaMultipleDivisible(schema, value, path) {
      var divisor = schema.multipleOf || schema.divisibleBy;
      /* Vanilla JS, prone to floating point rounding errors (e.g. 1.14 / .01 == 113.99999) */

      var valid = value / divisor === Math.floor(value / divisor);
      /* Use math.js is available */

      if (window.math) {
        valid = window.math.mod(window.math.bignumber(value), window.math.bignumber(divisor)).equals(0);
      } else if (window.Decimal) {
        /* Use decimal.js is available */
        valid = new window.Decimal(value).mod(new window.Decimal(divisor)).equals(0);
      }

      if (!valid) {
        return [{
          path: path,
          property: schema.multipleOf ? 'multipleOf' : 'divisibleBy',
          message: this.translate('error_multipleOf', [divisor])
        }];
      }

      return [];
    }
  }, {
    key: "_validateDateTimeSubSchema",
    value: function _validateDateTimeSubSchema(schema, value, path) {
      var _this14 = this;

      var _validateInteger = function _validateInteger(schema, value, path) {
        /* The value is a timestamp */
        if (value * 1 < 1) {
          /* If value is less than 1, then it's an invalid epoch date before 00:00:00 UTC Thursday, 1 January 1970 */
          return [{
            path: path,
            property: 'format',
            message: _this14.translate('error_invalid_epoch')
          }];
        } else if (value !== Math.abs(parseInt(value))) {
          /* not much to check for, so we assume value is ok if it's a positive number */
          return [{
            path: path,
            property: 'format',
            message: _this14.translate("error_".concat(schema.format.replace(/-/g, '_')), [dateFormat])
          }];
        }

        return [];
      };

      var _validateFlatPicker = function _validateFlatPicker(schema, value, path, editor) {
        if (value !== '') {
          var compareValue;

          if (editor.flatpickr.config.mode !== 'single') {
            var seperator = editor.flatpickr.config.mode === 'range' ? editor.flatpickr.l10n.rangeSeparator : ', ';
            var selectedDates = editor.flatpickr.selectedDates.map(function (val) {
              return editor.flatpickr.formatDate(val, editor.flatpickr.config.dateFormat);
            });
            compareValue = selectedDates.join(seperator);
          }

          try {
            if (compareValue) {
              /* Not the best validation method, but range and multiple mode are special */

              /* Optimal solution would be if it is possible to change the return format from string/integer to array */
              if (compareValue !== value) throw new Error("".concat(editor.flatpickr.config.mode, " mismatch"));
            } else if (editor.flatpickr.formatDate(editor.flatpickr.parseDate(value, editor.flatpickr.config.dateFormat), editor.flatpickr.config.dateFormat) !== value) {
              throw new Error('mismatch');
            }
          } catch (err) {
            var errorDateFormat = editor.flatpickr.config.errorDateFormat !== undefined ? editor.flatpickr.config.errorDateFormat : editor.flatpickr.config.dateFormat;
            return [{
              path: path,
              property: 'format',
              message: _this14.translate("error_".concat(editor.format.replace(/-/g, '_')), [errorDateFormat])
            }];
          }
        }

        return [];
      };

      var validatorRx = {
        date: /^(\d{4}\D\d{2}\D\d{2})?$/,
        time: /^(\d{2}:\d{2}(?::\d{2})?)?$/,
        'datetime-local': /^(\d{4}\D\d{2}\D\d{2}[ T]\d{2}:\d{2}(?::\d{2})?)?$/
      };
      var format = {
        date: '"YYYY-MM-DD"',
        time: '"HH:MM"',
        'datetime-local': '"YYYY-MM-DD HH:MM"'
      };
      var editor = this.jsoneditor.getEditor(path);
      var dateFormat = editor && editor.flatpickr ? editor.flatpickr.config.dateFormat : format[schema.format];

      if (schema.type === 'integer') {
        return _validateInteger(schema, value, path);
      } else if (!editor || !editor.flatpickr) {
        /* Standard string input, without flatpickr */
        if (!validatorRx[schema.format].test(value)) {
          return [{
            path: path,
            property: 'format',
            message: this.translate("error_".concat(schema.format.replace(/-/g, '_')), [dateFormat])
          }];
        }
      } else if (editor) {
        /* Flatpickr validation */
        return _validateFlatPicker(schema, value, path, editor);
      }

      return [];
    }
  }, {
    key: "_validateCustomValidator",
    value: function _validateCustomValidator(schema, value, path) {
      var _this15 = this;

      var errors = [];
      /* Internal validators using the custom validator format */

      errors.push.apply(errors, _toConsumableArray(_validators_ip_validator_js__WEBPACK_IMPORTED_MODULE_0__["ipValidator"].call(this, schema, value, path, this.translate)));

      var validate = function validate(validator) {
        errors.push.apply(errors, _toConsumableArray(validator.call(_this15, schema, value, path)));
      };
      /* Custom type validation (global) */


      this.defaults.custom_validators.forEach(validate);
      /* Custom type validation (instance specific) */

      if (this.options.custom_validators) {
        this.options.custom_validators.forEach(validate);
      }

      return errors;
    }
  }, {
    key: "_removeDuplicateErrors",
    value: function _removeDuplicateErrors(errors) {
      return errors.reduce(function (err, obj) {
        var first = true;
        if (!err) err = [];
        err.forEach(function (a) {
          if (a.message === obj.message && a.path === obj.path && a.property === obj.property) {
            a.errorcount++;
            first = false;
          }
        });

        if (first) {
          obj.errorcount = 1;
          err.push(obj);
        }

        return err;
      }, []);
    }
  }, {
    key: "_checkType",
    value: function _checkType(type, value) {
      var types = {
        string: function string(value) {
          return typeof value === 'string';
        },
        number: function number(value) {
          return typeof value === 'number';
        },
        integer: function integer(value) {
          return typeof value === 'number' && value === Math.floor(value);
        },
        "boolean": function boolean(value) {
          return typeof value === 'boolean';
        },
        array: function array(value) {
          return Array.isArray(value);
        },
        object: function object(value) {
          return value !== null && !Array.isArray(value) && _typeof(value) === 'object';
        },
        "null": function _null(value) {
          return value === null;
        }
      };
      /* Simple types */

      if (typeof type === 'string') {
        if (types[type]) {
          return types[type](value);
        } else return true;
        /* Schema */

      } else {
        return !this._validateSchema(type, value).length;
      }
    }
  }]);

  return Validator;
}();

/***/ }),

/***/ "./src/validators/ip-validator.js":
/*!****************************************!*\
  !*** ./src/validators/ip-validator.js ***!
  \****************************************/
/*! exports provided: ipValidator */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ipValidator", function() { return ipValidator; });
/* Implements ipv4, ipv6 and hostname format validations as per https://tools.ietf.org/html/draft-fge-json-schema-validation-00#section-7.3.4 */
var validateIpv4 = function validateIpv4(value) {
  var parts = value.split('.');

  if (parts.length !== 4) {
    throw new Error('error_ipv4');
  }

  parts.forEach(function (part) {
    if (isNaN(+part) || +part < 0 || +part > 255) {
      throw new Error('error_ipv4');
    }
  });
};

var validateIpv6 = function validateIpv6(value) {
  if (!value.match('^(?:(?:(?:[a-fA-F0-9]{1,4}:){6}|(?=(?:[a-fA-F0-9]{0,4}:){2,6}(?:[0-9]{1,3}.){3}[0-9]{1,3}$)(([0-9a-fA-F]{1,4}:){1,5}|:)((:[0-9a-fA-F]{1,4}){1,5}:|:)|::(?:[a-fA-F0-9]{1,4}:){5})(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])|(?:[a-fA-F0-9]{1,4}:){7}[a-fA-F0-9]{1,4}|(?=(?:[a-fA-F0-9]{0,4}:){0,7}[a-fA-F0-9]{0,4}$)(([0-9a-fA-F]{1,4}:){1,7}|:)((:[0-9a-fA-F]{1,4}){1,7}|:)|(?:[a-fA-F0-9]{1,4}:){7}:|:(:[a-fA-F0-9]{1,4}){7})$')) {
    throw new Error('error_ipv6');
  }
};

var validateHostname = function validateHostname(value) {
  if (!value.match('(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{0,62}[a-zA-Z0-9].)+[a-zA-Z]{2,63}$)')) {
    throw new Error('error_hostname');
  }
};

function ipValidator(schema, value, path, translate) {
  try {
    switch (schema.format) {
      case 'ipv4':
        validateIpv4(value);
        break;

      case 'ipv6':
        validateIpv6(value);
        break;

      case 'hostname':
        validateHostname(value);
        break;
    }

    return [];
  } catch (err) {
    return [{
      path: path,
      property: 'format',
      message: translate(err.message)
    }];
  }
}

/***/ })

/******/ });
});
/*
//@ sourceMappingURL=jsoneditor.js.map
*/