(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

/*globals Console:false */

/*globals Drupal:false */

/*globals jQuery:false */

/*globals JSONEditor:false */

/**
 * @file
 * Provides Twig Pattern Library Editing Functionality.
 *
 * @external Drupal
 *
 * @external jQuery
 *
 * @external JSONEditor
 *
 * @todo .editor-shadow-injection-target .card all: initial
 */
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

},{}]},{},[1]);
