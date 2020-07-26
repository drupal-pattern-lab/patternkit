(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

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
var DrupalImageEditor = /*#__PURE__*/function (_JSONEditor$AbstractE) {
  _inherits(DrupalImageEditor, _JSONEditor$AbstractE);

  var _super = _createSuper(DrupalImageEditor);

  function DrupalImageEditor() {
    _classCallCheck(this, DrupalImageEditor);

    return _super.apply(this, arguments);
  }

  _createClass(DrupalImageEditor, [{
    key: "getNumColumns",
    value: function getNumColumns() {
      return 4;
    }
  }, {
    key: "build",
    value: function build() {
      var _this = this;

      this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired()); // Editor options.
      // @todo Replace JSONEditor.defaults with this.defaults.

      this.options = jQuery.extend({}, {
        'title': 'Browse',
        'icon': '',
        'image_url': '/'
      }, JSONEditor.defaults.options.drupal_image || {}, this.options.drupal_image || {}); // Don't show uploader if this is readonly

      if (!this.schema.readOnly && !this.schema.readonly) {
        this.input = this.theme.getFormInputField('text');
        this.button = this.getButton(this.path + '-media', 'upload', Drupal.t('Select/Upload Media')); // @todo: Add support for multiple file/image URL editors.

        var media_library_settings = 'media_library_opener_id=patternkit.opener.jsonlibrary' + '&' + encodeURIComponent('media_library_allowed_types[0]') + '=image' + '&media_library_selected_type=image' + '&media_library_remaining=1' + '&' + encodeURIComponent('media_library_opener_parameters[field_widget_id]') + '=' + this.path;
        this.input.addEventListener('change', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this.setValue(e.target.value);
        });
        this.button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation(); // @see /core/misc/dialog/dialog.ajax.es6.js

          var $dialog = jQuery('#drupal-modal');

          if (!$dialog.length) {
            // Create the element if needed.
            $dialog = jQuery("<div id=\"drupal-modal\" class=\"ui-front\"/>").appendTo('body');
          }

          _this.dialog = Drupal.dialog($dialog.append(jQuery('<span>', {
            id: 'patternkit_image_dialog_loading'
          })), {
            title: Drupal.t('Choose Image'),
            width: 900,
            height: 900
          }).showModal();
          Drupal.ajax({
            url: _this.options.image_url + '?' + media_library_settings,
            base: 'drupal-modal',
            wrapper: 'patternkit_image_dialog_loading'
          }).execute();
        });
      }

      var description = this.schema.description || '';
      this.preview = this.theme.getFormInputDescription(description);
      this.container.appendChild(this.preview);
      this.control = this.theme.getFormControl(this.label, this.input, this.preview);
      this.container.appendChild(this.control);

      if (this.button) {
        this.container.appendChild(this.button);
      }

      window.requestAnimationFrame(function () {
        _this.refreshPreview();
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
          console.error('upload error', error, _this2);
        };

        img.src = this.container.querySelector('input').value;
      }

      this.theme.afterInputReady(this.input);
    }
  }, {
    key: "refreshPreview",
    value: function refreshPreview() {
      if (this.last_preview === this.value) {
        return;
      }

      this.last_preview = this.value;
      this.preview.innerHTML = '';

      if (!this.value) {
        return;
      }

      this.afterInputReady();
    }
  }, {
    key: "enable",
    value: function enable() {
      if (!this.always_disabled) {
        if (this.input) {
          this.input.disabled = false;
        }

        _get(_getPrototypeOf(DrupalImageEditor.prototype), "enable", this).call(this);
      }
    }
  }, {
    key: "disable",
    value: function disable(always_disabled) {
      if (always_disabled) {
        this.always_disabled = true;
      }

      if (this.input) {
        this.input.disabled = true;
      }

      if (this.button) {
        this.button.disabled = true;
      }

      _get(_getPrototypeOf(DrupalImageEditor.prototype), "disable", this).call(this, always_disabled);
    }
  }, {
    key: "setValue",
    value: function setValue(val) {
      if (this.value !== val) {
        this.value = val;
        this.input.value = this.value;
        this.refreshPreview();
        this.refreshWatchedFieldValues();
        this.onChange(true);
      }
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.preview && this.preview.parentNode) {
        this.preview.parentNode.removeChild(this.preview);
      }

      if (this.title && this.title.parentNode) {
        this.title.parentNode.removeChild(this.title);
      }

      if (this.input && this.input.parentNode) {
        this.input.parentNode.removeChild(this.input);
      }

      if (this.input && this.input.parentNode) {
        this.input.parentNode.removeChild(this.input);
      }

      _get(_getPrototypeOf(DrupalImageEditor.prototype), "destroy", this).call(this);
    }
  }]);

  return DrupalImageEditor;
}(JSONEditor.AbstractEditor);

(function ($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditorMediaLibrary = {
    attach: function attach(context, settings) {
      if (!window.JSONEditor || !settings.patternkitEditor.imageUrl) {
        return;
      }

      JSONEditor.defaults.options.drupal_image = {
        image_url: settings.patternkitEditor.imageUrl
      };
      JSONEditor.defaults.editors.drupal_image = DrupalImageEditor;
      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'string' && schema.format === 'image') {
          return 'drupal_image';
        }
      });
    }
  };
})(jQuery, Drupal, JSONEditor);

},{}]},{},[1]);
