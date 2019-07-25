/**
 * @file
 * Provides Twig Pattern Library Editing Functionality.
 *
 * @external Drupal
 *
 * @external jQuery
 *
 * @external JSONEditor
 */

(function ($, Drupal) {
  Drupal.behaviors.patternkitEditor = {
    attach: function (context, settings) {
      var $target = $('#editor-shadow-injection-target', context);
      $target.once('patternkit-editor').each(function () {
        var shadow = this.attachShadow({mode: 'open'});
        shadow.innerHTML = '<link rel="stylesheet" id="theme_stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css"><link rel="stylesheet" id="icon_stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.css"><div id="editor_holder"></div>';

        var data = {};
        data.schema = JSON.parse(drupalSettings.patternkitEditor.schemaJson);
        data.starting = JSON.parse(drupalSettings.patternkitEditor.startingJson);

        if (data.starting !== null) {
          JSONEditor.defaults.options.startval = data.starting;
        }

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
            schema:            data.schema,
            theme:             'bootstrap3',
            iconlib:           'fontawesome4',
            keep_oneof_values: false,
            disable_edit_json: true,
            disable_collapse:  true,
            ajax:              true,
            refs: { }
          }
        );
        JSONEditor.plugins.sceditor.emoticonsEnabled = false;

        editor.on('change', function () {
          document.getElementById('schema_instance_config').value = JSON.stringify(editor.getValue());

        });
      });
    }
  };
})(jQuery, Drupal);
