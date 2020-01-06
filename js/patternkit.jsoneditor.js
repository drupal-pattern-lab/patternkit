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

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.patternkitEditor = {
    attach: function (context, settings) {
      if (!window.JSONEditor) {
        return;
      }

      // Ajax command response to allow updating Editor field values.
      Drupal.AjaxCommands.prototype.patternkitEditorUpdate = function(ajax, response, status) {
        window.patternkitEditor.getEditor(response.selector).setValue(response.value);
      };

      var saveSchema = function () {
        $('#schema_instance_config').val(JSON.stringify(window.patternkitEditor.getValue()));
        if (window.M) {
          window.M.updateTextFields();
        }
      };

      var $target = $('#editor-shadow-injection-target', context);
      $target.once('patternkit-editor').each(function () {
        var shadow = this.attachShadow({mode: 'open'});
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
        }
        // @todo Re-eval with this shadow dom webfont bug: https://bugs.chromium.org/p/chromium/issues/detail?id=336876
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

        var data = {};
        data.schema = JSON.parse(settings.patternkitEditor.schemaJson);
        data.starting = JSON.parse(settings.patternkitEditor.startingJson);

        JSONEditor.defaults.options.theme = settings.patternkitEditor.theme;
        JSONEditor.defaults.options.iconlib = settings.patternkitEditor.icons;
        JSONEditor.defaults.options.keep_oneof_values = false;
        JSONEditor.defaults.options.disable_edit_json = true;
        JSONEditor.defaults.options.disable_collapse = false;
        JSONEditor.defaults.options.collapse = false;
        JSONEditor.defaults.options.ajax = true;

        // Drupal Image Editor.
        JSONEditor.defaults.editors.drupal_image = JSONEditor.AbstractEditor.extend({
          getNumColumns: function() {
            return 4;
          },
          build: function() {
            var self = this;
            this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
            // Don't show uploader if this is readonly
            if(!this.schema.readOnly && !this.schema.readonly) {
              this.urlfield = this.theme.getFormInputField('text');
              this.button = this.getButton(this.path + '-media', 'upload', Drupal.t('Select/Upload Media'));
              // @todo: Add support for multiple file/image URL editors.
              var media_library_settings = 'media_library_opener_id=patternkit.opener.jsonlibrary' +
                '&' + encodeURIComponent('media_library_allowed_types[0]') + '=image' +
                '&media_library_selected_type=image' +
                '&media_library_remaining=1' +
                '&' + encodeURIComponent('media_library_opener_parameters[field_widget_id]') + '=' + this.path;

              this.urlfield.addEventListener('change', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.value = this.value;
                self.refreshPreview();
              });
              this.button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                this.dialog = Drupal.dialog($('#drupal-modal').append($('<span>', {id: 'patternkit_image_dialog_loading'})), { title: Drupal.t('Choose Image'), width: 900, height: 900 }).showModal();
                Drupal.ajax({ url: settings.patternkitEditor.imageUrl + '?' + media_library_settings, base: 'drupal-modal', wrapper: 'patternkit_image_dialog_loading' }).execute();
              });
            }

            var description = this.schema.description;
            if (!description) {
              description = '';
            }

            this.preview = this.theme.getFormInputDescription(description);
            this.container.appendChild(this.preview);

            this.control = this.theme.getFormControl(this.label, this.urlfield||this.input, this.preview);
            this.container.appendChild(this.control);

            if (this.button) {
              this.container.appendChild(this.button);
            }

            window.requestAnimationFrame(function () {
              if (self.value) {
                var img = document.createElement('img');
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100px';
                img.onload = function (event) {
                  self.preview.appendChild(img);
                };
                img.src = self.container.querySelector('a').href;
              }
            });
          },
          refreshPreview: function () {
            if (this.last_preview === this.value) {
              return;
            }
            this.last_preview = this.value;
            this.preview.innerHTML = '';
            if (!this.value) {
              return;
            }

            var img = document.createElement('img');
            img.style.maxWidth = '100%';
            img.style.maxHeight = '100px';
            img.src = this.value;
            this.preview.appendChild(img);
          },
          enable: function () {
            if (!this.always_disabled) {
              if (this.urlfield) {
                this.urlfield.disabled = false;
              }
              this._super();
            }
          },
          disable: function (always_disabled) {
            if (always_disabled) {
              this.always_disabled = true;
            }
            if (this.urlfield) {
              this.urlfield.disabled = true;
            }
            if (this.button) {
              this.button.disabled = true;
            }
            this._super();
          },
          setValue: function (val) {
            if (this.value !== val) {
              this.value = val;
              this.urlfield.value = this.value;
              this.refreshPreview();
              this.onChange(true);
            }
          },
          destroy: function () {
            if(this.preview && this.preview.parentNode) {
              this.preview.parentNode.removeChild(this.preview);
            }
            if(this.title && this.title.parentNode) {
              this.title.parentNode.removeChild(this.title);
            }
            if(this.input && this.input.parentNode) {
              this.input.parentNode.removeChild(this.input);
            }
            if(this.urlfield && this.urlfield.parentNode) {
              this.urlfield.parentNode.removeChild(this.urlfield);
            }

            this._super();
          }
        });
        JSONEditor.defaults.resolvers.unshift(function (schema) {
          if (schema.type === 'string' && schema.format === 'image') {
            return 'drupal_image';
          }
        });

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
            var uri = settings.path.baseUrl + url + '/schema';

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
        var config = {
          schema: data.schema,
          refs: { }
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
        var parent_call = Drupal.Ajax.prototype.beforeSubmit;
        Drupal.Ajax.prototype.beforeSubmit = function (formValues, elementSettings, options) {
          if (window.patternkitEditor) {
            window.patternkitEditor.disable();
            saveSchema();
            var index = formValues.findIndex(function (o) { return o.name === "settings[instance_config]"; });
            formValues[index] = {
              name: "settings[instance_config]",
              value: JSON.stringify(window.patternkitEditor.getValue()),
              type: "hidden",
              required: false
            };
            window.patternkitEditor.destroy();
            delete window.patternkitEditor;
          }
          parent_call.call(this, formValues, elementSettings, options);
        };
        $('[data-drupal-selector="edit-actions-submit"]').parent('form').once().each(function () {
          $(this).submit(function (e) {
            e.preventDefault();
            window.patternkitEditor.disable();
            saveSchema();
            window.patternkitEditor.destroy();
            delete window.patternkitEditor;
            $(this).unbind('submit').submit();
          });
        });
      });
    }
  };
})(jQuery, Drupal);
