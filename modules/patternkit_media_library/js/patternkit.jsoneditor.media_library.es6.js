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
class DrupalMediaEditor extends JSONEditor.AbstractEditor {

  register () {
    super.register()
    if (!this.input) return
    if (this.jsoneditor.options.use_name_attributes) {
      this.input.setAttribute('name', this.formname)
    }
    this.input.setAttribute('aria-label', this.formname)
  }

  getNumColumns() {
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
  parseMediaToken(value) {
    let temp = document.createElement('div');
    temp.innerHTML = this.value;
    const element = temp.firstChild
    const attributeNames = element.getAttributeNames();
    return attributeNames.reduce((accumulator, name) => {
      let key = name;
      if (name.startsWith('data-')) {
        key = name.slice(5);
      }
      return {...accumulator, [key]: element.getAttribute(name)};
    }, {})
  }

  /**
   * Request a rendered preview of the selected media via AJAX.
   *
   * @param {string} value
   *   The field value to be previewed.
   */
  getPreview(value) {
    if (value.indexOf('<drupal-media') >= 0) {
      this.previewMediaToken(value);
    }
    else {
      this.previewMediaUrl(value);
    }
  }

  /**
   * Create an image preview element for the value at the given url.
   *
   * @param {string} url
   *   The image address to be previewed.
   */
  previewMediaUrl(url) {
    if (url) {
      let img = document.createElement('img');
      img.style.maxWidth = '100%';
      img.style.maxHeight = '100px';
      img.onload = (event) => {
        this.preview.appendChild(img)
      };
      img.onerror = (error) => {
        console.error('upload error', error, this)
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
  previewMediaToken(token) {
    const attributes = this.parseMediaToken(token);
    const uuid = attributes['entity-uuid'];

    if (uuid != null) {
      const preview_path = 'patternkit_media_library/preview/' + uuid;
      const ajax_options = {
        url: Drupal.url(preview_path),
        base: this.container_id,
        wrapper: this.container_id + '--preview',
        method: 'html'
      }
      Drupal.ajax(ajax_options).execute();
    }
  }

  /**
   * Get URL encoded media library settings.
   *
   * @returns {string}
   *   URL encoded media library settings.
   */
  getMediaLibrarySettings() {
    // @todo Handle scenarios where no media types are enabled.
    const allowedTypes = this.options.media_types;

    // Start of declaring the opener we'll be using.
    let mediaLibrarySettings = 'media_library_opener_id=patternkit.opener.jsonlibrary';

    // Add allowed types to the settings.
    allowedTypes.forEach((value, index) => {
      const component = encodeURIComponent('media_library_allowed_types[' + index + ']') + '=' + value;
      mediaLibrarySettings += '&' + encodeURIComponent('media_library_allowed_types[' + index + ']') + '=' + value;
    })

    // Default to the first media type for the selected type.
    const selectedType = allowedTypes[0];
    mediaLibrarySettings += '&media_library_selected_type=' + selectedType;

    // Add remaining opener options.
    mediaLibrarySettings += '&media_library_remaining=1' +
      '&' + encodeURIComponent('media_library_opener_parameters[field_widget_id]') + '=' + this.path;

    return mediaLibrarySettings;
  }

  build() {
    this.title = this.header = this.label = this.theme.getFormInputLabel(this.getTitle(), this.isRequired());
    this.input = this.theme.getFormInputField('text');
    this.button = this.getButton(this.options.button_text, 'upload', this.options.button_text);

    this.addUploader();
    this.addPreview();
  }

  afterInputReady() {
    if (this.value) {
      this.getPreview(this.value);
    }
    this.theme.afterInputReady(this.input);
  }

  refreshPreview() {
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

  enable() {
    if (!this.always_disabled) {
      if (this.input) {
        this.input.disabled = false;
      }
      if (this.button) {
        this.button.disabled = false;
      }
      super.enable();
    }
  }

  disable(always_disabled) {
    if (always_disabled) {
      this.always_disabled = true;
    }
    if (this.input) {
      this.input.disabled = true;
    }
    if (this.button) {
      this.button.disabled = true;
    }
    super.disable(always_disabled);
  }

  setValue(val) {
    if (this.value !== val) {
      this.value = val;
      this.input.value = this.value;
      this.refreshPreview();
      this.refreshWatchedFieldValues();
      this.onChange(true);
    }
  }

  destroy() {
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

    super.destroy();
  }

  /**
   * Open the media library dialog modal.
   */
  openModal() {
    // @see /core/misc/dialog/dialog.ajax.es6.js
    let $dialog = jQuery('#drupal-modal');
    if (!$dialog.length) {
      // Create the element if needed.
      $dialog = jQuery(
        `<div id="drupal-modal" class="ui-front"/>`,
      ).appendTo('body');
    }
    $dialog.addClass('patternkit-media-library');

    // Calculate modal dimensions to assign.
    const modalWidth = window.innerWidth >= 900 ? 900 : window.innerWidth;
    const modalHeight = window.innerHeight >= 900 ? 900 : window.innerHeight;

    // Place a loading indicator as a placeholder.
    $dialog.append(jQuery('<span>', {id: 'patternkit_dialog_loading'}));

    // Create the modal.
    let modalOptions = {
      width: modalWidth,
      height: modalHeight,
      title: this.options.title,
    };
    this.dialog = Drupal.dialog($dialog, modalOptions);

    let ajaxOptions = {
      url: this.options.library_url + '?' + this.getMediaLibrarySettings(),
      base: 'drupal-modal',
      wrapper: 'patternkit_dialog_loading',
    };

    // Show the modal and replace the load placeholder with an AJAX callback
    // from the media library controller.
    this.dialog.showModal();
    Drupal.ajax(ajaxOptions).execute();
  }

  /**
   * Add the media selection button to the field.
   */
  addUploader() {
    // Don't show uploader if this is readonly
    if(this.shouldShowUploader()) {
      this.input.addEventListener('change', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.setValue(e.target.value);
      });
      this.button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        this.openModal();
      });
    }
  }

  /**
   * Test whether a media uploader button should be displayed.
   *
   * @returns {boolean}
   *   True if the uploader should be displayed. False otherwise.
   */
  shouldShowUploader() {
    let showUploader = true;

    // Don't show uploader if this is readonly
    if(this.schema.readOnly || this.schema.readonly) {
      showUploader = false;
    }

    // Don't show the uploader if no media types have been selected.
    let media_types = this.options.media_types;
    if (!media_types || media_types.length === 0) {
      showUploader = false;
    }

    return showUploader;
  }

  /**
   * Add the preview container for selected media.
   */
  addPreview() {
    this.container_id = 'je--' + this.path.replace(/\./g, '_');

    const description = this.schema.description || '';
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

    window.requestAnimationFrame(() => {
      this.refreshPreview();
    })
  }

}

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
class DrupalImageEditor extends DrupalMediaEditor {

  build() {
    // Editor options.
    this.options = jQuery.extend({}, {
      'title': Drupal.t('Select image'),
      'icon': '',
      'button_text': Drupal.t('Select/Upload image'),
    }, this.defaults.options.drupal_image_media || {}, this.options.drupal_image_media || {});

    super.build();
  }

}

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
class DrupalVideoEditor extends DrupalMediaEditor {

  build() {
    // Editor options.
    this.options = jQuery.extend({}, {
      'title': Drupal.t('Browse'),
      'icon': '',
      'button_text': Drupal.t('Select/Upload video'),
    }, JSONEditor.defaults.options.drupal_video_media || {}, this.options.drupal_video_media || {});

    super.build();
  }

}

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
class DrupalImageUrlEditor extends DrupalMediaEditor {

  build() {
    // Editor options.
    this.options = jQuery.extend({}, {
      'title': Drupal.t('Browse'),
      'icon': '',
      'button_text': Drupal.t('Select/Upload image'),
    }, JSONEditor.defaults.options.drupal_image_url || {}, this.options.drupal_image_url || {});

    super.build();
  }

  getMediaLibrarySettings() {
    let settings = super.getMediaLibrarySettings();

    // Add the parameter value to disable returning a media token.
    settings += '&' + encodeURIComponent('media_library_opener_parameters[media_token]') + '=0';

    return settings;
  }

}

(function ($, Drupal, JSONEditor) {
  'use strict';
  Drupal.behaviors.patternkitEditorMediaLibrary = {
    attach: function (context, settings) {
      // Abort if a URL is not available for the media library opener.
      if (!window.JSONEditor || !settings.patternkitEditor.imageUrl || !settings.patternkitEditor.videoUrl) {
        return;
      }

      // Abort if configuration is missing for allowed media types.
      let mediaMapping = settings.patternkitEditor.mediaMapping;
      let image_options = {
        title: Drupal.t('Select image'),
        button_text: Drupal.t('Select/Upload image'),
        library_url: settings.patternkitEditor.imageUrl,
        media_types: mediaMapping.image_types
      };
      let video_options = {
        title: Drupal.t('Select video'),
        button_text: Drupal.t('Select/Upload video'),
        library_url: settings.patternkitEditor.videoUrl,
        media_types: mediaMapping.video_types
      }

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
  }
})(jQuery, Drupal, JSONEditor);
