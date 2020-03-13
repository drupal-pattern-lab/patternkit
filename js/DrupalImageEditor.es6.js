/*globals JSONEditor:false */
/**
 * @file DrupalImageEditor class.
 *
 * @external Drupal
 *
 * @external JSONEditor
 */

export var DrupalImageEditor = JSONEditor.AbstractEditor.extend({
  getNumColumns: function () {
    return 4;
  },

  build: function () {
    this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
    // Don't show uploader if this is readonly
    if (!this.schema.readOnly && !this.schema.readonly) {
      this.urlfield = this.theme.getFormInputField('text');
      let media_library_opener_parameters = {
        field_widget_id: this.urlfield.id
      };
      let opener_encoded = encodeURIComponent(JSON.stringify(media_library_opener_parameters));
      this.urlfield.addEventListener('change', function (e) {
        e.preventDefault();
        e.stopPropagation();
        Drupal.dialog(jQuery('<div>', {id: 'patternkit_jsonlibrary_image_dialog'})
          .append(jQuery('<span>', {id: 'patternkit_image_dialog_loading'})), {
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
    let description = this.schema.description || '';
    this.preview = this.theme.getFormInputDescription(description);
    this.container.appendChild(this.preview);
    this.control = this.theme.getFormControl(this.label, this.urlfield || this.input, this.preview);
    this.container.appendChild(this.control);
    window.requestAnimationFrame(() => {
      if (this.value) {
        let img = document.createElement('img');
        img.style.maxWidth = '100%';
        img.style.maxHeight = '100px';
        img.onload = (event) => {
          this.preview.appendChild(img);
        };
        img.onerror = (error) => {
          console.error('upload error', error);
        };
        img.src = this.container.querySelector('a').href;
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
    let mime = this.preview_value.match(/^data:([^;,]+)[;,]/);
    if (mime) {
      mime = mime[1];
    }
    else {
      mime = 'unknown';
    }
    let file = this.urlfield.files[0];
    this.preview.innerHTML = '<strong>Type:</strong> ' + mime + ', <strong>Size:</strong> ' + file.size + ' bytes';
    if (mime.substr(0, 5) === "image") {
      this.preview.innerHTML += '<br>';
      let img = document.createElement('img');
      img.style.maxWidth = '100%';
      img.style.maxHeight = '100px';
      img.src = this.preview_value;
      this.preview.appendChild(img);
    }
    this.preview.innerHTML += '<br>';
    let uploadButton = this.getButton('Upload', 'upload', 'Upload');
    this.preview.appendChild(uploadButton);
    uploadButton.addEventListener('click', (event) => {
      event.preventDefault();
      uploadButton.setAttribute("disabled", "disabled");
      this.theme.removeInputError(this.uploader);
      if (this.theme.getProgressBar) {
        this.progressBar = this.theme.getProgressBar();
        this.preview.appendChild(this.progressBar);
      }
      this.jsoneditor.options.upload(this.path, file, {
        success: (url) => {
          this.setValue(url);
          if (this.parent) {
            this.parent.onChildEditorChange(this);
          }
          else {
            this.jsoneditor.onChange();
          }
          if (this.progressBar) {
            this.preview.removeChild(this.progressBar);
          }
          uploadButton.removeAttribute("disabled");
        },
        failure: (error) => {
          this.theme.addInputError(this.uploader, error);
          if (this.progressBar) {
            this.preview.removeChild(this.progressBar);
          }
          uploadButton.removeAttribute("disabled");
        },
        updateProgress: (progress) => {
          if (this.progressBar) {
            if (progress) {
              this.theme.updateProgressBar(this.progressBar, progress);
            }
            else {
              this.theme.updateProgressBarUnknown(this.progressBar);
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
    this._super();
  },

  setValue: function (val) {
    if (this.value !== val) {
      this.value = val;
      this.urlfield.value = this.value;
      this.onChange();
    }
  },

  destroy: function () {
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
