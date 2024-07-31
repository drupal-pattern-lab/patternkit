(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }
function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _get() { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get.bind(); } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(arguments.length < 3 ? target : receiver); } return desc.value; }; } return _get.apply(this, arguments); }
function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }
function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }
function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }
function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }
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
  function DrupalMediaEditor() {
    _classCallCheck(this, DrupalMediaEditor);
    return _callSuper(this, DrupalMediaEditor, arguments);
  }
  _inherits(DrupalMediaEditor, _JSONEditor$AbstractE);
  return _createClass(DrupalMediaEditor, [{
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
      var uuid = attributes['entity-uuid'];
      if (uuid != null) {
        var preview_path = 'patternkit_media_library/preview/' + uuid;
        var ajax_options = {
          url: Drupal.url(preview_path),
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
      var allowedTypes = this.options.media_types;

      // Start of declaring the opener we'll be using.
      var mediaLibrarySettings = 'media_library_opener_id=patternkit.opener.jsonlibrary';

      // Add allowed types to the settings.
      allowedTypes.forEach(function (value, index) {
        var component = encodeURIComponent('media_library_allowed_types[' + index + ']') + '=' + value;
        mediaLibrarySettings += '&' + encodeURIComponent('media_library_allowed_types[' + index + ']') + '=' + value;
      });

      // Default to the first media type for the selected type.
      var selectedType = allowedTypes[0];
      mediaLibrarySettings += '&media_library_selected_type=' + selectedType;

      // Add remaining opener options.
      mediaLibrarySettings += '&media_library_remaining=1' + '&' + encodeURIComponent('media_library_opener_parameters[field_widget_id]') + '=' + this.path;
      return mediaLibrarySettings;
    }
  }, {
    key: "build",
    value: function build() {
      this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
      this.input = this.theme.getFormInputField('text');
      this.button = this.getButton(this.options.button_text, 'upload', this.options.button_text);
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
        if (this.button) {
          this.button.disabled = false;
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
      $dialog.addClass('patternkit-media-library');

      // Calculate modal dimensions to assign.
      var modalWidth = window.innerWidth >= 900 ? 900 : window.innerWidth;
      var modalHeight = window.innerHeight >= 900 ? 900 : window.innerHeight;

      // Place a loading indicator as a placeholder.
      $dialog.append(jQuery('<span>', {
        id: 'patternkit_dialog_loading'
      }));

      // Create the modal.
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
      };

      // Show the modal and replace the load placeholder with an AJAX callback
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
      if (this.shouldShowUploader()) {
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
     * Test whether a media uploader button should be displayed.
     *
     * @returns {boolean}
     *   True if the uploader should be displayed. False otherwise.
     */
  }, {
    key: "shouldShowUploader",
    value: function shouldShowUploader() {
      var showUploader = true;

      // Don't show uploader if this is readonly
      if (this.schema.readOnly || this.schema.readonly) {
        showUploader = false;
      }

      // Don't show the uploader if no media types have been selected.
      var media_types = this.options.media_types;
      if (!media_types || media_types.length === 0) {
        showUploader = false;
      }
      return showUploader;
    }

    /**
     * Add the preview container for selected media.
     */
  }, {
    key: "addPreview",
    value: function addPreview() {
      var _this3 = this;
      this.container_id = 'je--' + this.path.replace(/\./g, '_');
      var description = this.schema.description || '';
      this.description = this.theme.getFormInputDescription(description);

      // Build and attach the form control.
      this.control = this.theme.getFormControl(this.label, this.input, this.description, null, this.formname);
      this.container.appendChild(this.control);

      // Assemble and attach the preview container.
      this.preview = this.theme.getContainer();
      this.preview.classList.add('preview');
      this.preview.id = this.container_id + '--preview';
      this.container.appendChild(this.preview);
      if (this.button && this.shouldShowUploader()) {
        this.container.appendChild(this.button);
      }
      window.requestAnimationFrame(function () {
        _this3.refreshPreview();
      });
    }
  }]);
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
  function DrupalImageEditor() {
    _classCallCheck(this, DrupalImageEditor);
    return _callSuper(this, DrupalImageEditor, arguments);
  }
  _inherits(DrupalImageEditor, _DrupalMediaEditor);
  return _createClass(DrupalImageEditor, [{
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
  function DrupalVideoEditor() {
    _classCallCheck(this, DrupalVideoEditor);
    return _callSuper(this, DrupalVideoEditor, arguments);
  }
  _inherits(DrupalVideoEditor, _DrupalMediaEditor2);
  return _createClass(DrupalVideoEditor, [{
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
  function DrupalImageUrlEditor() {
    _classCallCheck(this, DrupalImageUrlEditor);
    return _callSuper(this, DrupalImageUrlEditor, arguments);
  }
  _inherits(DrupalImageUrlEditor, _DrupalMediaEditor3);
  return _createClass(DrupalImageUrlEditor, [{
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
      var settings = _get(_getPrototypeOf(DrupalImageUrlEditor.prototype), "getMediaLibrarySettings", this).call(this);

      // Add the parameter value to disable returning a media token.
      settings += '&' + encodeURIComponent('media_library_opener_parameters[media_token]') + '=0';
      return settings;
    }
  }]);
}(DrupalMediaEditor);
(function ($, Drupal, JSONEditor) {
  'use strict';

  Drupal.behaviors.patternkitEditorMediaLibrary = {
    attach: function attach(context, settings) {
      // Abort if a URL is not available for the media library opener.
      if (!window.JSONEditor || !settings.patternkitEditor.imageUrl || !settings.patternkitEditor.videoUrl) {
        return;
      }

      // Abort if configuration is missing for allowed media types.
      var mediaMapping = settings.patternkitEditor.mediaMapping;
      var image_options = {
        title: Drupal.t('Select image'),
        button_text: Drupal.t('Select/Upload image'),
        library_url: settings.patternkitEditor.imageUrl,
        media_types: mediaMapping.image_types
      };
      var video_options = {
        title: Drupal.t('Select video'),
        button_text: Drupal.t('Select/Upload video'),
        library_url: settings.patternkitEditor.videoUrl,
        media_types: mediaMapping.video_types
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
