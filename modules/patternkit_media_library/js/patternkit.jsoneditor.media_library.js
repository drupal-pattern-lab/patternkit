(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

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

/**
 * @file
 * Provides media selection editor integration with JSON editor.
 */

/*globals Console:false */

/*globals Drupal:false */

/*globals jQuery:false */

/*globals JSONEditor:false */

/**
 * DrupalMediaEditor class.
 *
 * An abstract implementation for editor fields using a media selector.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */
var DrupalMediaEditor = /*#__PURE__*/function (_JSONEditor$AbstractE) {
  _inherits(DrupalMediaEditor, _JSONEditor$AbstractE);

  var _super = _createSuper(DrupalMediaEditor);

  function DrupalMediaEditor() {
    _classCallCheck(this, DrupalMediaEditor);

    return _super.apply(this, arguments);
  }

  _createClass(DrupalMediaEditor, [{
    key: "register",
    value: function register() {
      _get(_getPrototypeOf(DrupalMediaEditor.prototype), "register", this).call(this);

      if (!this.input) return;

      if (this.jsoneditor.options.use_name_attributes) {
        this.input.setAttribute('name', this.formname);
      }

      this.input.setAttribute('aria-label', this.formname);
    }
  }, {
    key: "getNumColumns",
    value: function getNumColumns() {
      return 4;
    }
    /**
     * Parse the attributes from a <drupal-media> token.
     *
     * @param {string} value
     *   The <drupal-media> token for the selected media element.
     *
     * @returns {object}
     *   An object keyed by attributes on the media token. Any attributes prefixed
     *   with 'data-' have this trimmed off.
     */

  }, {
    key: "parseMediaToken",
    value: function parseMediaToken(value) {
      var temp = document.createElement('div');
      temp.innerHTML = this.value;
      var element = temp.firstChild;
      var attributeNames = element.getAttributeNames();
      return attributeNames.reduce(function (accumulator, name) {
        var key = name;

        if (name.startsWith('data-')) {
          key = name.slice(5);
        }

        return _objectSpread(_objectSpread({}, accumulator), {}, _defineProperty({}, key, element.getAttribute(name)));
      }, {});
    }
    /**
     * Request a rendered preview of the selected media via AJAX.
     *
     * @param {string} value
     *   The field value to be previewed.
     */

  }, {
    key: "getPreview",
    value: function getPreview(value) {
      if (value.indexOf('<drupal-media') >= 0) {
        this.previewMediaToken(value);
      } else {
        this.previewMediaUrl(value);
      }
    }
    /**
     * Create an image preview element for the value at the given url.
     *
     * @param {string} url
     *   The image address to be previewed.
     */

  }, {
    key: "previewMediaUrl",
    value: function previewMediaUrl(url) {
      var _this = this;

      if (url) {
        var img = document.createElement('img');
        img.style.maxWidth = '100%';
        img.style.maxHeight = '100px';

        img.onload = function (event) {
          _this.preview.appendChild(img);
        };

        img.onerror = function (error) {
          console.error('upload error', error, _this);
        };

        img.src = url;
      }
    }
    /**
     * Request a rendered preview of the media token via AJAX.
     *
     * @param {string} token
     *   The <drupal-media> token for the selected media entity.
     */

  }, {
    key: "previewMediaToken",
    value: function previewMediaToken(token) {
      var attributes = this.parseMediaToken(token);
      console.log(attributes);
      var uuid = attributes['entity-uuid'];

      if (uuid != null) {
        var preview_url = '/patternkit_media_library/preview/' + uuid;
        var ajax_options = {
          url: preview_url,
          base: this.container_id,
          wrapper: this.container_id + '--preview',
          method: 'html'
        };
        Drupal.ajax(ajax_options).execute();
      }
    }
    /**
     * Get URL encoded media library settings.
     *
     * @returns {string}
     *   URL encoded media library settings.
     */

  }, {
    key: "getMediaLibrarySettings",
    value: function getMediaLibrarySettings() {
      // @todo Handle scenarios where no media types are enabled.
      var allowedTypes = this.options.media_types; // Start of declaring the opener we'll be using.

      var mediaLibrarySettings = 'media_library_opener_id=patternkit.opener.jsonlibrary'; // Add allowed types to the settings.

      allowedTypes.forEach(function (value, index) {
        var component = encodeURIComponent('media_library_allowed_types[' + index + ']') + '=' + value;
        mediaLibrarySettings += '&' + encodeURIComponent('media_library_allowed_types[' + index + ']') + '=' + value;
      }); // Default to the first media type for the selected type.

      var selectedType = allowedTypes[0];
      mediaLibrarySettings += '&media_library_selected_type=' + selectedType; // Add remaining opener options.

      mediaLibrarySettings += '&media_library_remaining=1' + '&' + encodeURIComponent('media_library_opener_parameters[field_widget_id]') + '=' + this.path;
      return mediaLibrarySettings;
    }
  }, {
    key: "build",
    value: function build() {
      this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      this.addUploader();
      this.addPreview();
    }
  }, {
    key: "afterInputReady",
    value: function afterInputReady() {
      if (this.value) {
        this.getPreview(this.value);
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

        _get(_getPrototypeOf(DrupalMediaEditor.prototype), "enable", this).call(this);
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

      _get(_getPrototypeOf(DrupalMediaEditor.prototype), "disable", this).call(this, always_disabled);
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

      _get(_getPrototypeOf(DrupalMediaEditor.prototype), "destroy", this).call(this);
    }
    /**
     * Open the media library dialog modal.
     */

  }, {
    key: "openModal",
    value: function openModal() {
      // @see /core/misc/dialog/dialog.ajax.es6.js
      var $dialog = jQuery('#drupal-modal');

      if (!$dialog.length) {
        // Create the element if needed.
        $dialog = jQuery("<div id=\"drupal-modal\" class=\"ui-front\"/>").appendTo('body');
      }

      $dialog.addClass('patternkit-media-library'); // Calculate modal dimensions to assign.

      var modalWidth = window.innerWidth >= 900 ? 900 : window.innerWidth;
      var modalHeight = window.innerHeight >= 900 ? 900 : window.innerHeight; // Place a loading indicator as a placeholder.

      $dialog.append(jQuery('<span>', {
        id: 'patternkit_dialog_loading'
      })); // Create the modal.

      var modalOptions = {
        width: modalWidth,
        height: modalHeight,
        title: this.options.title
      };
      this.dialog = Drupal.dialog($dialog, modalOptions);
      var ajaxOptions = {
        url: this.options.library_url + '?' + this.getMediaLibrarySettings(),
        base: 'drupal-modal',
        wrapper: 'patternkit_dialog_loading'
      }; // Show the modal and replace the load placeholder with an AJAX callback
      // from the media library controller.

      this.dialog.showModal();
      Drupal.ajax(ajaxOptions).execute();
    }
    /**
     * Add the media selection button to the field.
     */

  }, {
    key: "addUploader",
    value: function addUploader() {
      var _this2 = this;

      // Don't show uploader if this is readonly
      if (!this.schema.readOnly && !this.schema.readonly) {
        this.input = this.theme.getFormInputField('text');
        this.button = this.getButton(this.path + '-media', 'upload', this.options.button_text);
        this.input.addEventListener('change', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this2.setValue(e.target.value);
        });
        this.button.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          _this2.openModal();
        });
      }
    }
    /**
     * Add the preview container for selected media.
     */

  }, {
    key: "addPreview",
    value: function addPreview() {
      var _this3 = this;

      var description = this.schema.description || '';
      this.container_id = 'je--' + this.path.replace(/\./g, '_');
      this.preview = this.theme.getFormInputDescription(description);
      this.preview.classList.add('preview');
      this.preview.id = this.container_id + '--preview';
      this.container.appendChild(this.preview);
      this.control = this.theme.getFormControl(this.label, this.input, this.preview);
      this.container.appendChild(this.control);

      if (this.button) {
        this.container.appendChild(this.button);
      }

      window.requestAnimationFrame(function () {
        _this3.refreshPreview();
      });
    }
  }]);

  return DrupalMediaEditor;
}(JSONEditor.AbstractEditor);
/**
 * DrupalImageEditor class.
 *
 * A field editor implementation to expose image media selection options on
 * image fields.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */


var DrupalImageEditor = /*#__PURE__*/function (_DrupalMediaEditor) {
  _inherits(DrupalImageEditor, _DrupalMediaEditor);

  var _super2 = _createSuper(DrupalImageEditor);

  function DrupalImageEditor() {
    _classCallCheck(this, DrupalImageEditor);

    return _super2.apply(this, arguments);
  }

  _createClass(DrupalImageEditor, [{
    key: "build",
    value: function build() {
      // Editor options.
      this.options = jQuery.extend({}, {
        'title': Drupal.t('Select image'),
        'icon': '',
        'button_text': Drupal.t('Select/Upload image')
      }, this.defaults.options.drupal_image_media || {}, this.options.drupal_image_media || {});

      _get(_getPrototypeOf(DrupalImageEditor.prototype), "build", this).call(this);
    }
  }]);

  return DrupalImageEditor;
}(DrupalMediaEditor);
/**
 * DrupalVideoEditor class.
 *
 * A field editor implementation to expose video media selection options on
 * video fields.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */


var DrupalVideoEditor = /*#__PURE__*/function (_DrupalMediaEditor2) {
  _inherits(DrupalVideoEditor, _DrupalMediaEditor2);

  var _super3 = _createSuper(DrupalVideoEditor);

  function DrupalVideoEditor() {
    _classCallCheck(this, DrupalVideoEditor);

    return _super3.apply(this, arguments);
  }

  _createClass(DrupalVideoEditor, [{
    key: "build",
    value: function build() {
      // Editor options.
      this.options = jQuery.extend({}, {
        'title': Drupal.t('Browse'),
        'icon': '',
        'button_text': Drupal.t('Select/Upload video')
      }, JSONEditor.defaults.options.drupal_video_media || {}, this.options.drupal_video_media || {});

      _get(_getPrototypeOf(DrupalVideoEditor.prototype), "build", this).call(this);
    }
  }]);

  return DrupalVideoEditor;
}(DrupalMediaEditor);
/**
 * DrupalImageUrlEditor class.
 *
 * A field editor implementation to expose support legacy behavior using image
 * urls for selected image media.
 *
 * @external Drupal
 * @external jQuery
 * @external JSONEditor
 */


var DrupalImageUrlEditor = /*#__PURE__*/function (_DrupalMediaEditor3) {
  _inherits(DrupalImageUrlEditor, _DrupalMediaEditor3);

  var _super4 = _createSuper(DrupalImageUrlEditor);

  function DrupalImageUrlEditor() {
    _classCallCheck(this, DrupalImageUrlEditor);

    return _super4.apply(this, arguments);
  }

  _createClass(DrupalImageUrlEditor, [{
    key: "build",
    value: function build() {
      // Editor options.
      this.options = jQuery.extend({}, {
        'title': Drupal.t('Browse'),
        'icon': '',
        'button_text': Drupal.t('Select/Upload image')
      }, JSONEditor.defaults.options.drupal_image_url || {}, this.options.drupal_image_url || {});

      _get(_getPrototypeOf(DrupalImageUrlEditor.prototype), "build", this).call(this);
    }
  }, {
    key: "getMediaLibrarySettings",
    value: function getMediaLibrarySettings() {
      var settings = _get(_getPrototypeOf(DrupalImageUrlEditor.prototype), "getMediaLibrarySettings", this).call(this); // Add the parameter value to disable returning a media token.


      settings += '&' + encodeURIComponent('media_library_opener_parameters[media_token]') + '=0';
      return settings;
    }
  }]);

  return DrupalImageUrlEditor;
}(DrupalMediaEditor);

(function ($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditorMediaLibrary = {
    attach: function attach(context, settings) {
      if (!window.JSONEditor || !settings.patternkitEditor.imageUrl || !settings.patternkitEditor.videoUrl) {
        return;
      }

      var image_options = {
        title: Drupal.t('Select image'),
        button_text: Drupal.t('Select/Upload image'),
        library_url: settings.patternkitEditor.imageUrl,
        media_types: settings.patternkitEditor.mediaMapping.image_types
      };
      var video_options = {
        title: Drupal.t('Select video'),
        button_text: Drupal.t('Select/Upload video'),
        library_url: settings.patternkitEditor.videoUrl,
        media_types: settings.patternkitEditor.mediaMapping.video_types
      };
      JSONEditor.defaults.options.drupal_image_media = image_options;
      JSONEditor.defaults.options.drupal_image_url = image_options;
      JSONEditor.defaults.options.drupal_video_media = video_options;
      JSONEditor.defaults.editors.drupal_image_media = DrupalImageEditor;
      JSONEditor.defaults.editors.drupal_image_url = DrupalImageUrlEditor;
      JSONEditor.defaults.editors.drupal_video_media = DrupalVideoEditor;
      JSONEditor.defaults.resolvers.unshift(function (schema) {
        if (schema.type === 'string') {
          switch (schema.format) {
            case 'image':
              return 'drupal_image_url';

            case 'image-media':
              return 'drupal_image_media';

            case 'video-media':
              return 'drupal_video_media';
          }
        }
      });
    }
  };
})(jQuery, Drupal, JSONEditor);

},{}]},{},[1]);
