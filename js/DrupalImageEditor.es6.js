/*globals Drupal:false */
/*globals JSONEditor:false */
/*globals jQuery:false */
/**
 * @file DrupalImageEditor class.
 *
 * @external Drupal
 *
 * @external JSONEditor
 *
 * @external jQuery
 */

export const DrupalImageEditor = JSONEditor.AbstractEditor.extend({
  getNumColumns: function() {
    return 4;
  },
  build: function() {
    this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());

    // Editor options.
    // @todo Replace JSONEditor.defaults with this.defaults.
    this.options = jQuery.extend({}, {
      'title': 'Browse',
      'icon': '',
      'image_url': '/'
    }, JSONEditor.defaults.options.drupal_image || {}, this.options.drupal_image || {});

    // Don't show uploader if this is readonly
    if(!this.schema.readOnly && !this.schema.readonly) {
      this.input = this.theme.getFormInputField('text');
      this.button = this.getButton(this.path + '-media', 'upload', Drupal.t('Select/Upload Media'));
      // @todo: Add support for multiple file/image URL editors.
      const media_library_settings = 'media_library_opener_id=patternkit.opener.jsonlibrary' +
        '&' + encodeURIComponent('media_library_allowed_types[0]') + '=image' +
        '&media_library_selected_type=image' +
        '&media_library_remaining=1' +
        '&' + encodeURIComponent('media_library_opener_parameters[field_widget_id]') + '=' + this.path;

      this.input.addEventListener('change', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.setValue(e.target.value);
      });
      this.button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        // @see /core/misc/dialog/dialog.ajax.es6.js
        let $dialog = jQuery('#drupal-modal');
        if (!$dialog.length) {
          // Create the element if needed.
          $dialog = jQuery(
            `<div id="drupal-modal" class="ui-front"/>`,
          ).appendTo('body');
        }
        this.dialog = Drupal.dialog($dialog.append(jQuery('<span>', {id: 'patternkit_image_dialog_loading'})), { title: Drupal.t('Choose Image'), width: 900, height: 900 }).showModal();
        Drupal.ajax({ url: this.options.image_url + '?' + media_library_settings, base: 'drupal-modal', wrapper: 'patternkit_image_dialog_loading' }).execute();
      });
    }

    const description = this.schema.description || '';

    this.preview = this.theme.getFormInputDescription(description);
    this.container.appendChild(this.preview);

    this.control = this.theme.getFormControl(this.label, this.input, this.preview);
    this.container.appendChild(this.control);

    if (this.button) {
      this.container.appendChild(this.button);
    }

    window.requestAnimationFrame(() => {
      this.refreshPreview();
    })
  },
  afterInputReady: function () {
    if (this.value) {
      let img = document.createElement('img');
      img.style.maxWidth = '100%';
      img.style.maxHeight = '100px';
      img.onload = (event) => {
        this.preview.appendChild(img)
      };
      img.onerror = (error) => {
        console.error('upload error', error, this)
      };
      img.src = this.container.querySelector('input').value;
    }
    this.theme.afterInputReady(this.input);
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
    this.afterInputReady();
  },
  enable: function () {
    if (!this.always_disabled) {
      if (this.input) {
        this.input.disabled = false;
      }
      this._super();
    }
  },
  disable: function (always_disabled) {
    if (always_disabled) {
      this.always_disabled = true;
    }
    if (this.input) {
      this.input.disabled = true;
    }
    if (this.button) {
      this.button.disabled = true;
    }
    this._super();
  },
  setValue: function (val) {
    if (this.value !== val) {
      this.value = val;
      this.input.value = this.value;
      this.refreshPreview();
      this.refreshWatchedFieldValues();
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
    if(this.input && this.input.parentNode) {
      this.input.parentNode.removeChild(this.input);
    }

    this._super();
  }
});
