(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.DrupalImageEditor = void 0;

/*globals JSONEditor:false */

/**
 * @file DrupalImageEditor class.
 *
 * @external Drupal
 *
 * @external JSONEditor
 */
var DrupalImageEditor = JSONEditor.AbstractEditor.extend({
  getNumColumns: function getNumColumns() {
    return 4;
  },
  build: function build() {
    var _this = this;

    this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired()); // Don't show uploader if this is readonly

    if (!this.schema.readOnly && !this.schema.readonly) {
      this.urlfield = this.theme.getFormInputField('text');
      var media_library_opener_parameters = {
        field_widget_id: this.urlfield.id
      };
      var opener_encoded = encodeURIComponent(JSON.stringify(media_library_opener_parameters));
      this.urlfield.addEventListener('change', function (e) {
        e.preventDefault();
        e.stopPropagation();
        Drupal.dialog(jQuery('<div>', {
          id: 'patternkit_jsonlibrary_image_dialog'
        }).append(jQuery('<span>', {
          id: 'patternkit_image_dialog_loading'
        })), {
          title: Drupal.t('Choose Image'),
          width: 900,
          height: 900
        }).showModal();
        Drupal.ajax({
          url: settings.imageUrl + "&media_library_opener_parameters=" + opener_encoded,
          base: 'patternkit_jsonlibrary_image_dialog',
          wrapper: 'patternkit_image_dialog_loading'
        }).execute({});
      });
    }

    var description = this.schema.description || '';
    this.preview = this.theme.getFormInputDescription(description);
    this.container.appendChild(this.preview);
    this.control = this.theme.getFormControl(this.label, this.urlfield || this.input, this.preview);
    this.container.appendChild(this.control);
    window.requestAnimationFrame(function () {
      if (_this.value) {
        var img = document.createElement('img');
        img.style.maxWidth = '100%';
        img.style.maxHeight = '100px';

        img.onload = function (event) {
          _this.preview.appendChild(img);
        };

        img.onerror = function (error) {
          console.error('upload error', error);
        };

        img.src = _this.container.querySelector('a').href;
      }
    });
  },
  refreshPreview: function refreshPreview() {
    var _this2 = this;

    if (this.last_preview === this.preview_value) {
      return;
    }

    this.last_preview = this.preview_value;
    this.preview.innerHTML = '';

    if (!this.preview_value) {
      return;
    }

    var mime = this.preview_value.match(/^data:([^;,]+)[;,]/);

    if (mime) {
      mime = mime[1];
    } else {
      mime = 'unknown';
    }

    var file = this.urlfield.files[0];
    this.preview.innerHTML = '<strong>Type:</strong> ' + mime + ', <strong>Size:</strong> ' + file.size + ' bytes';

    if (mime.substr(0, 5) === "image") {
      this.preview.innerHTML += '<br>';
      var img = document.createElement('img');
      img.style.maxWidth = '100%';
      img.style.maxHeight = '100px';
      img.src = this.preview_value;
      this.preview.appendChild(img);
    }

    this.preview.innerHTML += '<br>';
    var uploadButton = this.getButton('Upload', 'upload', 'Upload');
    this.preview.appendChild(uploadButton);
    uploadButton.addEventListener('click', function (event) {
      event.preventDefault();
      uploadButton.setAttribute("disabled", "disabled");

      _this2.theme.removeInputError(_this2.uploader);

      if (_this2.theme.getProgressBar) {
        _this2.progressBar = _this2.theme.getProgressBar();

        _this2.preview.appendChild(_this2.progressBar);
      }

      _this2.jsoneditor.options.upload(_this2.path, file, {
        success: function success(url) {
          _this2.setValue(url);

          if (_this2.parent) {
            _this2.parent.onChildEditorChange(_this2);
          } else {
            _this2.jsoneditor.onChange();
          }

          if (_this2.progressBar) {
            _this2.preview.removeChild(_this2.progressBar);
          }

          uploadButton.removeAttribute("disabled");
        },
        failure: function failure(error) {
          _this2.theme.addInputError(_this2.uploader, error);

          if (_this2.progressBar) {
            _this2.preview.removeChild(_this2.progressBar);
          }

          uploadButton.removeAttribute("disabled");
        },
        updateProgress: function updateProgress(progress) {
          if (_this2.progressBar) {
            if (progress) {
              _this2.theme.updateProgressBar(_this2.progressBar, progress);
            } else {
              _this2.theme.updateProgressBarUnknown(_this2.progressBar);
            }
          }
        }
      });
    });

    if (this.jsoneditor.options.auto_upload || this.schema.options.auto_upload) {
      uploadButton.dispatchEvent(new MouseEvent('click'));
      this.preview.removeChild(uploadButton);
    }
  },
  enable: function enable() {
    if (!this.always_disabled) {
      if (this.urlfield) {
        this.urlfield.disabled = false;
      }

      this._super();
    }
  },
  disable: function disable(always_disabled) {
    if (always_disabled) {
      this.always_disabled = true;
    }

    if (this.urlfield) {
      this.urlfield.disabled = true;
    }

    this._super();
  },
  setValue: function setValue(val) {
    if (this.value !== val) {
      this.value = val;
      this.urlfield.value = this.value;
      this.onChange();
    }
  },
  destroy: function destroy() {
    if (this.preview && this.preview.parentNode) {
      this.preview.parentNode.removeChild(this.preview);
    }

    if (this.title && this.title.parentNode) {
      this.title.parentNode.removeChild(this.title);
    }

    if (this.input && this.input.parentNode) {
      this.input.parentNode.removeChild(this.input);
    }

    if (this.urlfield && this.urlfield.parentNode) {
      this.urlfield.parentNode.removeChild(this.urlfield);
    }

    this._super();
  }
});
exports.DrupalImageEditor = DrupalImageEditor;

},{}],2:[function(require,module,exports){
"use strict";

var _DrupalImageEditor = require("./DrupalImageEditor.es6");

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

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
        JSONEditor.defaults.options.ajax = true;
        JSONEditor.defaults.editors.drupal_image = _DrupalImageEditor.DrupalImageEditor;
        JSONEditor.defaults.resolvers.unshift(function (schema) {
          if (schema.type === 'string' && schema.format === 'image') {
            return 'drupal_image';
          }
        }); // Override how references are resolved.

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
            var uri = settings.path.baseUrl + url + '/schema';
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
                }

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
          refs: {},
          startval: {}
        };

        if (_typeof(data.starting) === 'object' && !$.isEmptyObject(data.starting)) {
          config.startval = data.starting;
        }

        window.patternkitEditor = new JSONEditor($target[0].shadowRoot.getElementById('editor_holder'), config);
        JSONEditor.plugins.sceditor.emoticonsEnabled = false;
        window.patternkitEditor.on('ready', function () {
          if (window.M) {
            window.M.updateTextFields();
          }
        });
        window.patternkitEditor.on('change', saveSchema); // Drupal triggers Ajax submit via input events.
        // This is before allowing other events, so we need to add a pre-hook
        // to trigger the editor update with latest field values.
        // @TODO Add handling for AJAX errors and re-attach.

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

},{"./DrupalImageEditor.es6":1}]},{},[2]);
