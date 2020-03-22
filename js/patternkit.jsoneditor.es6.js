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
import {DrupalImageEditor} from "./DrupalImageEditor.es6";

(function ($, Drupal, JSONEditor) {
  'use strict';
  Drupal.behaviors.patternkitEditor = {
    attach: function (context, settings) {
      if (!window.JSONEditor) {
        return;
      }
      // Ajax command response to allow updating Editor field values.
      Drupal.AjaxCommands.prototype.patternkitEditorUpdate = function (ajax, response, status) {
        window.patternkitEditor.getEditor(response.selector).setValue(response.value);
      };
      let saveSchema = function () {
        $('#schema_instance_config').val(JSON.stringify(window.patternkitEditor.getValue()));
        if (window.M) {
          window.M.updateTextFields();
        }
      };
      let $target = $('#editor-shadow-injection-target', context);
      $target.once('patternkit-editor').each(function () {
        let shadow = this.attachShadow({mode: 'open'});
        let theme_js = settings.patternkitEditor.themeJS;
        if (typeof theme_js === 'string') {
          theme_js = [theme_js];
        }
        for (let i = 0; theme_js && i < theme_js.length; i++) {
          let script_element = document.createElement('script');
          script_element.type = "text/javascript";
          script_element.src = theme_js[i];
          document.getElementsByTagName('head')[0].appendChild(script_element);
        }
        let editor_dom = '';
        if (settings.patternkitEditor.themeStylesheet) {
          editor_dom = '<link rel="stylesheet" id="theme_stylesheet" href="' + settings.patternkitEditor.themeStylesheet + '">';
        }
        // @todo Re-eval with this shadow dom webfont bug:
        // https://bugs.chromium.org/p/chromium/issues/detail?id=336876
        if (settings.patternkitEditor.iconStylesheet) {
          let icons_element = document.createElement('link');
          icons_element.rel = "stylesheet";
          icons_element.id = "icon_stylesheet";
          icons_element.href = settings.patternkitEditor.iconStylesheet;
          document.getElementsByTagName('head')[0].appendChild(icons_element);
          editor_dom += '<link rel="stylesheet" id="icon_stylesheet" href="' + settings.patternkitEditor.iconStylesheet + '">';
        }
        editor_dom += '<div id="editor_holder"></div>';
        shadow.innerHTML += editor_dom;
        let data = {
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
        JSONEditor.defaults.options.drupal_image = {
          image_url: settings.patternkitEditor.imageUrl
        };
        JSONEditor.defaults.editors.drupal_image = DrupalImageEditor;

        JSONEditor.defaults.resolvers.unshift(function (schema) {
          if (schema.type === 'string' && schema.format === 'image') {
            return 'drupal_image';
          }
        });
        // Override how references are resolved.
        JSONEditor.prototype._loadExternalRefs = function (schema, callback) {
          let refs = this._getExternalRefs(schema);
          let done = 0, waiting = 0, callback_fired = false;
          $.each(refs, (url) => {
            if (this.refs[url]) {
              return;
            }
            if (!this.options.ajax) {
              throw "Must set ajax option to true to load external ref " + url;
            }
            this.refs[url] = 'loading';
            waiting++;
            let r = new XMLHttpRequest();
            let uri = settings.path.baseUrl + url + '/schema';
            r.open("GET", uri, true);
            r.onreadystatechange = () => {
              if (r.readyState !== 4) {
                return;
              }
              // Request succeeded.
              if (r.status === 200) {
                let response;
                try {
                  response = JSON.parse(r.responseText);
                }
                catch (e) {
                  window.console.log(e);
                  throw "Failed to parse external ref " + url;
                }
                if (!response || typeof response !== "object") {
                  throw "External ref does not contain a valid schema - " + url;
                }
                this.refs[url] = response;
                this._loadExternalRefs(response, function () {
                  done++;
                  if (done >= waiting && !callback_fired) {
                    callback_fired = true;
                    callback();
                  }
                });
              }
              // Request failed.
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
        // Initialize the editor with a JSON schema.
        let config = {
          schema: data.schema,
          refs: {}
        };
        if (typeof data.starting === 'object' && !$.isEmptyObject(data.starting)) {
          config.startval = data.starting;
        }
        window.patternkitEditor = new JSONEditor($target[0].shadowRoot.getElementById('editor_holder'), config);
        JSONEditor.plugins.sceditor.emoticonsEnabled = false;
        window.patternkitEditor.on('ready', function () {
          if (window.M) {
            window.M.updateTextFields();
          }
        });
        window.patternkitEditor.on('change', saveSchema);
        // Drupal triggers Ajax submit via input events.
        // This is before allowing other events, so we need to add a pre-hook
        // to trigger the editor update with latest field values.
        // @TODO Add handling for AJAX errors and re-attach.
        let parent_call = Drupal.Ajax.prototype.beforeSubmit;
        Drupal.Ajax.prototype.beforeSubmit = function (formValues, elementSettings, options) {
          if (window.patternkitEditor) {
            let index = formValues.findIndex(function (o) {
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
