/*globals Console:false */
/*globals Drupal:false */
/*globals jQuery:false */
/*globals JSONEditor:false */
///<reference path="typings/jquery/jquery.d.ts" />
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
              var media_library_opener_parameters = {
                field_widget_id: this.urlfield.id
              };
              var opener_encoded = encodeURIComponent(JSON.stringify(media_library_opener_parameters));

              this.urlfield.addEventListener('change',function(e) {
                e.preventDefault();
                e.stopPropagation();

                Drupal.dialog(jQuery('<div>', {id: 'patternkit_jsonlibrary_image_dialog'}).append(jQuery('<span>', {id: 'patternkit_image_dialog_loading'})), { title: Drupal.t('Choose Image'), width: 900, height: 900 }).showModal();
                Drupal.ajax({ url: settings.patternkitEditor.imageUrl + "&media_library_opener_parameters=" + opener_encoded, base: 'patternkit_jsonlibrary_image_dialog', wrapper: 'patternkit_image_dialog_loading' }).execute();
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

            window.requestAnimationFrame(function() {
              if (self.value) {
                var img = document.createElement('img');
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100px';
                img.onload = function (event) {
                  self.preview.appendChild(img);
                };
                img.onerror = function(error) {
                  Console.error('upload error', error);
                };
                img.src = self.container.querySelector('a').href;
              }
            });

          },
          refreshPreview: function() {
            if (this.last_preview === this.preview_value) {
              return;
            }
            this.last_preview = this.preview_value;

            this.preview.innerHTML = '';

            if (!this.preview_value) {
              return;
            }

            var self = this;

            var mime = this.preview_value.match(/^data:([^;,]+)[;,]/);
            if (mime) {
              mime = mime[1];
            }
            else {
              mime = 'unknown';
            }

            var file = this.urlfield.files[0];

            this.preview.innerHTML = '<strong>Type:</strong> '+mime+', <strong>Size:</strong> '+file.size+' bytes';
            if(mime.substr(0,5)==="image") {
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
            uploadButton.addEventListener('click',function(event) {
              event.preventDefault();

              uploadButton.setAttribute("disabled", "disabled");
              self.theme.removeInputError(self.uploader);

              if (self.theme.getProgressBar) {
                self.progressBar = self.theme.getProgressBar();
                self.preview.appendChild(self.progressBar);
              }

              self.jsoneditor.options.upload(self.path, file, {
                success: function(url) {
                  self.setValue(url);

                  if (self.parent) {
                    self.parent.onChildEditorChange(self);
                  }
                  else {
                    self.jsoneditor.onChange();
                  }

                  if (self.progressBar) {
                    self.preview.removeChild(self.progressBar);
                  }
                  uploadButton.removeAttribute("disabled");
                },
                failure: function(error) {
                  self.theme.addInputError(self.uploader, error);
                  if (self.progressBar) {
                    self.preview.removeChild(self.progressBar);
                  }
                  uploadButton.removeAttribute("disabled");
                },
                updateProgress: function(progress) {
                  if (self.progressBar) {
                    if (progress) {
                      self.theme.updateProgressBar(self.progressBar, progress);
                    }
                    else {
                      self.theme.updateProgressBarUnknown(self.progressBar);
                    }
                  }
                }
              });
            });

            if(this.jsoneditor.options.auto_upload || this.schema.options.auto_upload) {
              uploadButton.dispatchEvent(new MouseEvent('click'));
              this.preview.removeChild(uploadButton);
            }
          },
          enable: function() {
            if(!this.always_disabled) {
              if(this.urlfield) {
                this.urlfield.disabled = false;
              }
              this._super();
            }
          },
          disable: function(always_disabled) {
            if(always_disabled) {
              this.always_disabled = true;
            }
            if(this.urlfield) {
              this.urlfield.disabled = true;
            }
            this._super();
          },
          setValue: function(val) {
            if(this.value !== val) {
              this.value = val;
              this.urlfield.value = this.value;
              this.onChange();
            }
          },
          destroy: function() {
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
        JSONEditor.defaults.resolvers.unshift(function(schema) {
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
        var editor = new JSONEditor($target[0].shadowRoot.getElementById('editor_holder'), config);
        JSONEditor.plugins.sceditor.emoticonsEnabled = false;
        var saveSchema = function () {
          $('#schema_instance_config').val(JSON.stringify(editor.getValue()));
          if (window.M) {
            window.M.updateTextFields();
          }
        };

        editor.on('ready', function () {
          if (window.M) {
            window.M.updateTextFields();
          }
        });
        editor.on('change', saveSchema);
        $('[data-drupal-selector="edit-actions-submit"]').on('input', function (e) {
          saveSchema();
        });
        // Drupal triggers Ajax submit via input events.
        // This is before allowing other events, so we need to add a pre-hook
        // to trigger the editor update with latest field values.
        // @TODO Add handling for AJAX errors and re-attach.
        Drupal.Ajax.prototype.beforeSubmit = function(formValues, element, options) {
          editor.disable();
          saveSchema();
          for (var v = 0; v < formValues.length; v++) {
            if (formValues[v].name === 'settings[instance_config]') {
              formValues[v].value = JSON.stringify(editor.getValue());
            }
          }
          editor.destroy();
        };
      });
    }
  };
})(jQuery, Drupal);
