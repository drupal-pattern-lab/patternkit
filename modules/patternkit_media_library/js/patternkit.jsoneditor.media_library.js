/* eslint-disable max-classes-per-file */
/* globals JSONEditor:false */
/**
 * @file
 * Provides media selection editor integration with JSON editor.
 *
 * cspell:ignore formname
 */

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
  /**
   * Display validation errors on the media editor field.
   *
   * The parent AbstractEditor has a no-op implementation, so this override
   * is necessary to display inline validation errors on media fields.
   *
   * @param {Array} errors
   *   An array of validation error objects from JSON Editor's validate().
   */
  showValidationErrors(errors) {
    // Abort if the input element is not available.
    if (!this.input) {
      return;
    }

    const messages = errors.reduce((msgs, error) => {
      if (error.path === this.path) {
        msgs.push(error.message);
      }
      return msgs;
    }, []);

    if (messages.length) {
      this.theme.addInputError(this.input, `${messages.join('. ')}.`);
    } else {
      this.theme.removeInputError(this.input);
    }
  }

  register() {
    super.register();
    if (!this.input) return;
    if (this.jsoneditor.options.use_name_attributes) {
      this.input.setAttribute('name', this.formname);
    }
    this.input.setAttribute('aria-label', this.formname);
  }

  // eslint-disable-next-line class-methods-use-this -- JSON Editor API expects this override.
  getNumColumns() {
    return 4;
  }

  /**
   * Parse the attributes from a <drupal-media> token.
   *

   * @param {string} token
   *   The <drupal-media> token for the selected media element.
   *
   * @return {object}
   *   An object keyed by attributes on the media token. Any attributes prefixed
   *   with 'data-' have this trimmed off.
   */
  static parseMediaToken(token) {
    const temp = document.createElement('div');
    temp.innerHTML = token;
    const element = temp.firstChild;
    const attributeNames = element.getAttributeNames();
    return attributeNames.reduce((accumulator, name) => {
      let key = name;
      if (name.startsWith('data-')) {
        key = name.slice(5);
      }
      return { ...accumulator, [key]: element.getAttribute(name) };
    }, {});
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
    } else {
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
      const img = document.createElement('img');
      img.style.maxWidth = '100%';
      img.style.maxHeight = '100px';
      img.onload = () => {
        this.preview.appendChild(img);
      };
      img.onerror = (error) => {
        if (window.console && typeof window.console.error === 'function') {
          // eslint-disable-next-line no-console -- optional load failure diagnostics
          window.console.error('upload error', error, this);
        }
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
    const attributes = DrupalMediaEditor.parseMediaToken(token);
    const uuid = attributes['entity-uuid'];

    if (uuid != null) {
      const previewPath = `patternkit_media_library/preview/${uuid}`;
      const ajaxOptions = {
        url: Drupal.url(previewPath),
        base: this.container_id,
        wrapper: `${this.container_id}--preview`,
        method: 'html',
      };
      Drupal.ajax(ajaxOptions).execute();
    }
  }

  /**
   * Get URL encoded media library settings.
   *
   * @return {string}
   *   URL encoded media library settings.
   */
  getMediaLibrarySettings() {
    // @todo Handle scenarios where no media types are enabled.
    const allowedTypes = this.options.media_types;

    // Start of declaring the opener we'll be using.
    let mediaLibrarySettings =
      'media_library_opener_id=patternkit.opener.jsonlibrary';

    // Add allowed types to the settings.
    allowedTypes.forEach((value, index) => {
      mediaLibrarySettings += `&${encodeURIComponent(
        `media_library_allowed_types[${index}]`,
      )}=${value}`;
    });

    // Default to the first media type for the selected type.
    const selectedType = allowedTypes[0];
    mediaLibrarySettings += `&media_library_selected_type=${selectedType}`;

    // Add remaining opener options.
    mediaLibrarySettings +=
      `&media_library_remaining=1` +
      `&${encodeURIComponent('media_library_opener_parameters[field_widget_id]')}=${this.path}`;

    return mediaLibrarySettings;
  }

  build() {
    const label = this.theme.getFormInputLabel(
      this.getTitle(),
      this.isRequired(),
    );
    this.title = label;
    this.header = label;
    this.label = label;
    this.input = this.theme.getFormInputField('text');
    this.button = this.getButton(
      this.options.button_text,
      'upload',
      this.options.button_text,
    );

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

  disable(alwaysDisabled) {
    if (alwaysDisabled) {
      this.always_disabled = true;
    }
    if (this.input) {
      this.input.disabled = true;
    }
    if (this.button) {
      this.button.disabled = true;
    }
    super.disable(alwaysDisabled);
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
    if (this.preview && this.preview.parentNode) {
      this.preview.parentNode.removeChild(this.preview);
    }
    if (this.title && this.title.parentNode) {
      this.title.parentNode.removeChild(this.title);
    }
    if (this.input && this.input.parentNode) {
      this.input.parentNode.removeChild(this.input);
    }
    if (this.input && this.input.parentNode) {
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
      $dialog = jQuery(`<div id="drupal-modal" class="ui-front"/>`).appendTo(
        'body',
      );
    }
    $dialog.addClass('patternkit-media-library');

    // Calculate modal dimensions to assign.
    const modalWidth = window.innerWidth >= 900 ? 900 : window.innerWidth;
    const modalHeight = window.innerHeight >= 900 ? 900 : window.innerHeight;

    // Place a loading indicator as a placeholder.
    $dialog.append(jQuery('<span>', { id: 'patternkit_dialog_loading' }));

    // Create the modal.
    const modalOptions = {
      width: modalWidth,
      height: modalHeight,
      title: this.options.title,
    };
    this.dialog = Drupal.dialog($dialog, modalOptions);

    const ajaxOptions = {
      url: `${this.options.library_url}?${this.getMediaLibrarySettings()}`,
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
    if (this.shouldShowUploader()) {
      this.input.addEventListener('change', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.setValue(e.target.value);
      });

      // Detect URL paste/input for auto-populating attributes when field loses focus.
      this.input.addEventListener('blur', (e) => {
        const url = e.target.value;
        if (url && this.isImageSrcField()) {
          this.fetchAttributesByUrl(url);
        }
      });

      this.button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        this.openModal();
      });
    }
  }

  /**
   * Check if this field is an image src field (ends with .src).
   *
   * @return {boolean}
   *   True if this is an image src field.
   */
  isImageSrcField() {
    return this.path && this.path.endsWith('.src');
  }

  /**
   * Get the base path for sibling image fields (removes .src suffix).
   *
   * @return {string}
   *   The base path for sibling fields.
   */
  getBaseImagePath() {
    if (!this.isImageSrcField()) {
      return null;
    }
    return this.path.slice(0, -4); // Remove '.src'
  }

  /**
   * Fetch media attributes for a given URL.
   *
   * @param {string} url
   *   The file URL to fetch attributes for.
   */
  fetchAttributesByUrl(url) {
    if (!url || !this.isImageSrcField()) {
      return;
    }

    const endpoint = Drupal.url('patternkit_media_library/attributes_by_url');
    const query = new URLSearchParams({
      url,
      container_id: this.container_id,
    });

    // Use Drupal AJAX for compatibility with test framework.
    // Create a hidden element to serve as the AJAX base.
    const ajaxId = `${this.container_id}--attributes-ajax`;
    let ajaxElement = document.getElementById(ajaxId);
    if (!ajaxElement) {
      ajaxElement = document.createElement('div');
      ajaxElement.id = ajaxId;
      ajaxElement.style.display = 'none';
      ajaxElement.setAttribute('data-drupal-ajax', 'true');
      this.container.appendChild(ajaxElement);
    }

    const ajaxOptions = {
      url: `${endpoint}?${query.toString()}`,
      base: ajaxId,
      wrapper: ajaxId,
      method: 'html',
      error: (response, status) => {
        // Silently fail - log for debugging only when console is available.
        if (window.console && typeof window.console.debug === 'function') {
          // eslint-disable-next-line no-console -- optional diagnostics
          window.console.debug('Failed to fetch media attributes:', status);
        }
      },
    };

    Drupal.ajax(ajaxOptions).execute();
  }

  /**
   * Populate sibling image fields with fetched attributes.
   *
   * @param {object} attributes
   *   An object containing alt, width, height, and/or title attributes.
   */
  populateImageAttributes(attributes) {
    const basePath = this.getBaseImagePath();
    if (!basePath) {
      return;
    }

    const attributeFields = ['alt', 'width', 'height', 'title'];
    attributeFields.forEach((attr) => {
      if (
        attributes[attr] !== undefined &&
        attributes[attr] !== null &&
        attributes[attr] !== ''
      ) {
        const fieldPath = `${basePath}.${attr}`;

        // Try to get the editor for this field path.
        // If a field is not found in the schema client-side, fail silently.
        try {
          const editor = this.jsoneditor.getEditor(fieldPath);
          if (editor) {
            // For alt text and title fields, only populate if the field is currently empty.
            if (attr === 'alt' || attr === 'title') {
              const currentValue = editor.getValue();
              if (
                currentValue === undefined ||
                currentValue === null ||
                currentValue === ''
              ) {
                editor.setValue(attributes[attr]);
              }
            } else {
              editor.setValue(attributes[attr]);
            }
          }
        } catch (error) {
          // Field doesn't exist in schema - silently skip.
          if (window.console && typeof window.console.debug === 'function') {
            // eslint-disable-next-line no-console -- optional diagnostics
            window.console.debug('Field not found in schema:', fieldPath);
          }
        }
      }
    });
  }

  /**
   * Test whether a media uploader button should be displayed.
   *
   * @return {boolean}
   *   True if the uploader should be displayed. False otherwise.
   */
  shouldShowUploader() {
    let showUploader = true;

    // Don't show uploader if this is readonly
    if (this.schema.readOnly || this.schema.readonly) {
      showUploader = false;
    }

    // Don't show the uploader if no media types have been selected.
    const { media_types: mediaTypes } = this.options;
    if (!mediaTypes || mediaTypes.length === 0) {
      showUploader = false;
    }

    return showUploader;
  }

  /**
   * Add the preview container for selected media.
   */
  addPreview() {
    this.container_id = `je--${this.path.replace(/\./g, '_')}`;

    // Ensure the container has the correct ID for AJAX command lookup.
    if (!this.container.id) {
      this.container.id = this.container_id;
    }

    const description = this.schema.description || '';
    this.description = this.theme.getFormInputDescription(description);

    // Build and attach the form control.
    this.control = this.theme.getFormControl(
      this.label,
      this.input,
      this.description,
      null,
      this.formname,
    );
    this.container.appendChild(this.control);

    // Store a reference to this editor instance on the container for AJAX commands.
    this.container.setAttribute('data-patternkit-editor', 'true');
    this.container.patternkitEditor = this;

    // Assemble and attach the preview container.
    this.preview = this.theme.getContainer();
    this.preview.classList.add('preview');
    this.preview.id = `${this.container_id}--preview`;
    this.container.appendChild(this.preview);

    if (this.button && this.shouldShowUploader()) {
      this.container.appendChild(this.button);
    }

    window.requestAnimationFrame(() => {
      this.refreshPreview();
    });
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
    this.options = jQuery.extend(
      {},
      {
        title: Drupal.t('Select image'),
        icon: '',
        button_text: Drupal.t('Select/Upload image'),
      },
      this.defaults.options.drupal_image_media || {},
      this.options.drupal_image_media || {},
    );

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
    this.options = jQuery.extend(
      {},
      {
        title: Drupal.t('Browse'),
        icon: '',
        button_text: Drupal.t('Select/Upload video'),
      },
      JSONEditor.defaults.options.drupal_video_media || {},
      this.options.drupal_video_media || {},
    );

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
    this.options = jQuery.extend(
      {},
      {
        title: Drupal.t('Browse'),
        icon: '',
        button_text: Drupal.t('Select/Upload image'),
      },
      JSONEditor.defaults.options.drupal_image_url || {},
      this.options.drupal_image_url || {},
    );

    super.build();
  }

  getMediaLibrarySettings() {
    let settings = super.getMediaLibrarySettings();

    // Add the parameter value to disable returning a media token.
    settings += `&${encodeURIComponent(
      'media_library_opener_parameters[media_token]',
    )}=0`;

    return settings;
  }
}

// eslint-disable-next-line func-names -- standard Drupal IIFE wrapper pattern.
(function ($, Drupal, JSONEditor) {
  Drupal.behaviors.patternkitEditorMediaLibrary = {
    attach(context, settings) {
      // Register custom AJAX command for media attributes.
      // eslint-disable-next-line func-names -- assigned Drupal Ajax command handler pattern.
      Drupal.AjaxCommands.prototype.patternkitMediaAttributes = function (
        _ajax,
        response,
      ) {
        if (
          response.attributes &&
          Object.keys(response.attributes).length > 0
        ) {
          // Find the editor instance by container ID.
          const containerElement = document.getElementById(
            response.containerId,
          );
          if (containerElement && containerElement.patternkitEditor) {
            const editor = containerElement.patternkitEditor;
            if (editor.populateImageAttributes) {
              editor.populateImageAttributes(response.attributes);
            }
          }
        }
      };

      // Abort if a URL is not available for the media library opener.
      if (
        !window.JSONEditor ||
        !settings.patternkitEditor.imageUrl ||
        !settings.patternkitEditor.videoUrl
      ) {
        return;
      }

      // Abort if configuration is missing for allowed media types.
      const { mediaMapping } = settings.patternkitEditor;
      const imageOptions = {
        title: Drupal.t('Select image'),
        button_text: Drupal.t('Select/Upload image'),
        library_url: settings.patternkitEditor.imageUrl,
        media_types: mediaMapping.image_types,
      };
      const videoOptions = {
        title: Drupal.t('Select video'),
        button_text: Drupal.t('Select/Upload video'),
        library_url: settings.patternkitEditor.videoUrl,
        media_types: mediaMapping.video_types,
      };

      JSONEditor.defaults.options.drupal_image_media = imageOptions;
      JSONEditor.defaults.options.drupal_image_url = imageOptions;
      JSONEditor.defaults.options.drupal_video_media = videoOptions;
      JSONEditor.defaults.editors.drupal_image_media = DrupalImageEditor;
      JSONEditor.defaults.editors.drupal_image_url = DrupalImageUrlEditor;
      JSONEditor.defaults.editors.drupal_video_media = DrupalVideoEditor;
      const patternkitMediaResolver = (schema) => {
        if (schema.type === 'string') {
          switch (schema.format) {
            case 'image':
              return 'drupal_image_url';
            case 'image-media':
              return 'drupal_image_media';
            case 'video-media':
              return 'drupal_video_media';
            default:
              return null;
          }
        }
        return null;
      };
      JSONEditor.defaults.resolvers.unshift(patternkitMediaResolver);
    },
  };
})(jQuery, Drupal, JSONEditor);
