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

(function ($, Drupal) {
  Drupal.behaviors.patternkitEditor = {
    attach: function (context, settings) {
      var $target = $('#editor-shadow-injection-target', context);
      $target.once('patternkit-editor').each(function () {
        var shadow = this.attachShadow({mode: 'open'});
        var theme_js = drupalSettings.patternkitEditor.themeJS;
        if (typeof theme_js == 'string') {
          theme_js = [theme_js];
        }
        for (var i = 0; theme_js && i < theme_js.length; i++) {
          var script_element = document.createElement('script');
          script_element.type = "text/javascript";
          script_element.src = theme_js[i];
          document.getElementsByTagName('head')[0].appendChild(script_element);
        }
        if (drupalSettings.patternkitEditor.themeStylesheet) {
          var editor_dom = '<link rel="stylesheet" id="theme_stylesheet" href="' + drupalSettings.patternkitEditor.themeStylesheet + '">';
        }
        // @todo Re-eval with this shadow dom webfont bug: https://bugs.chromium.org/p/chromium/issues/detail?id=336876
        if (drupalSettings.patternkitEditor.iconStylesheet) {
          var icons_element = document.createElement('link');
          icons_element.rel = "stylesheet";
          icons_element.id = "icon_stylesheet";
          icons_element.href = drupalSettings.patternkitEditor.iconStylesheet;
          document.getElementsByTagName('head')[0].appendChild(icons_element);
          editor_dom += '<link rel="stylesheet" id="icon_stylesheet" href="' + drupalSettings.patternkitEditor.iconStylesheet + '">';
        }
        editor_dom += '<div id="editor_holder"></div>';
        shadow.innerHTML += editor_dom;

        var data = {};
        data.schema = JSON.parse(drupalSettings.patternkitEditor.schemaJson);
        data.starting = JSON.parse(drupalSettings.patternkitEditor.startingJson);
        data.icons = drupalSettings.patternkitEditor.icons;

        if (data.starting !== null) {
          JSONEditor.defaults.options.startval = data.starting;
        }
        JSONEditor.defaults.options.theme = drupalSettings.patternkitEditor.theme;
        JSONEditor.defaults.options.iconlib = drupalSettings.patternkitEditor.icons;
        JSONEditor.defaults.options.keep_oneof_values = false;
        JSONEditor.defaults.options.disable_edit_json = true;
        JSONEditor.defaults.options.disable_collapse = false;
        JSONEditor.defaults.options.collapse = false;
        JSONEditor.defaults.options.ajax = true;

        // Override how references are resolved.
        JSONEditor.prototype._loadExternalRefs = function (schema, callback) {
          var self = this;
          var refs = this._getExternalRefs(schema);

          var done = 0, waiting = 0, callback_fired = false;

          $.each(refs, function (url) {
            if (self.refs[url]) {
              return;
            }
            if (!self.options.ajax) {
              throw "Must set ajax option to true to load external ref " + url;
            }
            self.refs[url] = 'loading';
            waiting++;

            var r = new XMLHttpRequest();
            var uri = drupalSettings.path.baseUrl + url + '/schema';

            r.open("GET", uri, true);
            r.onreadystatechange = function () {
              if (r.readyState !== 4) {
                return;
              }
              // Request succeeded.
              if (r.status === 200) {
                var response;
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

                self.refs[url] = response;
                self._loadExternalRefs(response,function () {
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
        var editor = new JSONEditor(
          $target[0].shadowRoot.getElementById('editor_holder'), {
            schema: data.schema,
            refs: { }
          }
        );
        JSONEditor.plugins.sceditor.emoticonsEnabled = false;

        editor.on('ready', function () {
          if (window.M) {
            window.M.updateTextFields();
          }
        });
        editor.on('change', function () {
          document.getElementById('schema_instance_config').value = JSON.stringify(editor.getValue());
          if (window.M) {
            window.M.updateTextFields();
          }
        });
      });
    }
  };
})(jQuery, Drupal);
