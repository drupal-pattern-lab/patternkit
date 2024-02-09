/*globals Console:false */
/*globals Drupal:false */
/*globals jQuery:false */
/*globals JSONEditor:false */
/*globals once:false */
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
 * @external once
 *
 * @todo .patternkit-editor-target .card all: initial
 */

import {patternkitEditorCKEditor5} from './patternkit.jsoneditor.ckeditor5.es6';
import {patternkitEditorCygnet} from './patternkit.jsoneditor.cygnet.es6.js';
import {patternkitEditorObject} from './patternkit.jsoneditor.editor.object.es6';
import {patternkitEditorArray} from './patternkit.jsoneditor.editor.array.es6';

// Instantiates wysiwyg plugins.
patternkitEditorCKEditor5(jQuery, Drupal, JSONEditor);
patternkitEditorCygnet(jQuery, Drupal, JSONEditor);

// Overrides the object and array json-editor editors, to customize certain
// methods.  The only use case so far is to trigger toggling of items by
// clicking on the button's title instead of just the small button itself.
patternkitEditorObject(jQuery, Drupal, JSONEditor);
patternkitEditorArray(jQuery, Drupal, JSONEditor);

(function ($, Drupal, JSONEditor, once) {
 'use strict';

 class PatternkitJsonEditor {

   constructor(target, settings, defaults) {
     this.target = target;
     this.settings = settings;
     this.editor_root = document;
     this.beforeSerializeCallbacks = [];
     this.beforeSubmitCallbacks = [];

     this.data = {
       schema: JSON.parse(settings.schemaJson),
       starting: JSON.parse(settings.startingJson)
     };
   }

   loadTheme() {
     let theme_js = this.settings.themeJS;
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
     if (this.settings.themeStylesheet) {
       let theme_element = document.createElement('link');
       theme_element.rel = "stylesheet";
       theme_element.id = "theme_stylesheet";
       theme_element.href = this.settings.themeStylesheet;
       document.getElementsByTagName('head')[0].appendChild(theme_element);
       editor_dom = '<link rel="stylesheet" id="theme_stylesheet" href="' + this.settings.themeStylesheet + '">';
     }
     // @todo Re-eval with this shadow dom webfont bug:
     // https://bugs.chromium.org/p/chromium/issues/detail?id=336876
     if (this.settings.iconStylesheet) {
       let icons_element = document.createElement('link');
       icons_element.rel = "stylesheet";
       icons_element.id = "icon_stylesheet";
       icons_element.href = this.settings.iconStylesheet;
       document.getElementsByTagName('head')[0].appendChild(icons_element);
       editor_dom += '<link rel="stylesheet" id="icon_stylesheet" href="' + this.settings.iconStylesheet + '">';
     }
     editor_dom += '<div id="editor_holder"></div>';

     this.target.html(editor_dom);
   }

   setDefaults() {
     JSONEditor.defaults.options.theme = this.settings.theme;
     JSONEditor.defaults.options.iconlib = this.settings.icons;
     JSONEditor.defaults.options.keep_oneof_values = false;
     JSONEditor.defaults.options.disable_edit_json = this.settings.disableJsonEditButton;
     JSONEditor.defaults.options.disable_collapse = false;
     JSONEditor.defaults.options.collapse = false;
     JSONEditor.defaults.options.ajax = true;
     JSONEditor.defaults.options.ajax_cache_responses = this.settings.ajaxCacheResponses;
     JSONEditor.defaults.options.ajax_cache_buster = this.settings.ajaxCacheBuster;
     JSONEditor.defaults.options.disable_properties = this.settings.disablePropertiesButtons;

     // Overrides alert message when removing an item.
     JSONEditor.defaults.languages.en.button_delete_node_warning = Drupal.t('Are you sure you want to remove this item?');

     // Override object properties button text for user friendliness.
     JSONEditor.defaults.languages.en.button_properties = Drupal.t('Properties');
     JSONEditor.defaults.languages.en.button_object_properties = Drupal.t('Properties');

     // Uses Handlebars template engine so that we can use logic in
     // `headerTemplate` property in schemas.
     JSONEditor.defaults.options.template = 'handlebars';

     // @todo Loop through all editor plugins and add them at runtime.

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
         let uri = settings.path.baseUrl + url;
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
             // @todo Actually validate the schema so we can throw an error.
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
   }

   loadEditor() {
     // Initialize the editor with a JSON schema.
     let config = {
       schema: this.data.schema,
       refs: {}
     };
     window.patternkitEditor = new JSONEditor(this.editor_root.getElementById('editor_holder'), config);
     window.patternkitEditor.patternkit = this;

     window.patternkitEditor.on('ready', this.onReady.bind(this));
     window.patternkitEditor.on('change', this.saveSchema.bind(this));

     // Expose methods for editors to register AJAX event cycle callbacks.
     const self = this;
     window.patternkitEditor.beforeSerialize = (callback) => {
       self.beforeSerializeCallbacks.push(callback);
     }
     window.patternkitEditor.beforeSubmit = (callback) => {
       self.beforeSubmitCallbacks.push(callback);
     }
   }

   saveSchema() {
     $('#schema_instance_config').val(JSON.stringify(window.patternkitEditor.getValue()));
   }

   onReady() {
     try {
       // If we provide starting JSON as a value, JSON Editor hides all
       // non-required fields, which is desired behavior by most users of
       // the library. For patterns, we want to include any new schema
       // fields in our values, so they are displayed by default, optional or
       // not. This also allows us to pre-populate based on the schema
       // provided.
       if (typeof this.data.starting === 'object' && !$.isEmptyObject(this.data.starting)) {
         window.patternkitEditor.setValue({...window.patternkitEditor.getValue(), ...this.data.starting});
       }
     }
     catch (e) {
       window.console.error(e);
     }

     this.hideLoadingIndicator();
   }

   beforeSerialize() {
     const callbacks = this.beforeSerializeCallbacks;
     callbacks.forEach((callback) => {
       callback();
     })
   }

   beforeSubmit() {
     const callbacks = this.beforeSubmitCallbacks;
     callbacks.forEach((callback) => {
       callback();
     })
   }

   onSubmit(e) {
     // Call all serialize and submission handlers since this function is only
     // triggered outside an AJAX content and those handlers would not be
     // called otherwise.
     this.beforeSerialize();
     this.beforeSubmit();

     this.saveSchema();
     window.patternkitEditor.destroy();
     delete window.patternkitEditor;
     $(once.remove('patternkit-editor', $('#patternkit-editor-target')));
     $(this).off('submit');
   }

   showLoadingIndicator() {
     const loadingIndicatorClass = 'js-patternkit-loading__on';

     // Only add the indicator if it isn't already present.
     let $indicator = $('.' + loadingIndicatorClass);
     if ($indicator.length === 0) {
       this.target.before(`<div class="${loadingIndicatorClass}"></div>`);
     }
   }

   hideLoadingIndicator() {
     const loadingIndicatorClass = 'js-patternkit-loading__on';
     $('.' + loadingIndicatorClass).remove();
   }
 }

 Drupal.behaviors.patternkitEditor = {
   attach: function (context, settings) {
     if (!window.JSONEditor) {
       return;
     }
     // Ajax command response to allow updating Editor field values.
     Drupal.AjaxCommands.prototype.patternkitEditorUpdate = function (ajax, response, status) {
       window.patternkitEditor.getEditor(response.selector).setValue(response.value);
     };

     let $target = $('#patternkit-editor-target', context);
     let editor = new PatternkitJsonEditor($target, settings.patternkitEditor);

     $(once('patternkit-editor', $target)).each(function () {
       editor.showLoadingIndicator();
       editor.loadTheme();
       editor.setDefaults();
       editor.loadEditor();

       let $submitButton = $('[data-drupal-selector="edit-actions-submit"]');
       let $form = $submitButton.parent('form');

       // Get the ajax handler instance.
       let id = $submitButton.attr('id');
       let ajax = Drupal.ajax.instances.find(element => element &&
         element.selector && element.selector === '#' + id);

       // If there is no AJAX handler for the submit button, we're most likely
       // viewing the form directly without an AJAX interface. In this scenario,
       // we need to capture and react to the form submit event directly since
       // there will be no AJAX event cycle to hook into.
       if (ajax === undefined) {
         $(once('json-editor-processed', $form, context)).each(function () {
           $(this).on('submit', editor.onSubmit.bind(editor));
         });

         // Exit here since there is no more handling needed.
         return;
       }

       // Drupal triggers Ajax submit via input events before the form events
       // bubble up for listeners, so we need to add a pre-hook within this
       // event cycle to trigger the editor update with latest field values
       // before the values are finalized and submitted.
       if (!ajax.hasOwnProperty('beforeSerialize')) {
         let beforeSerialize = ajax.beforeSerialize;
         ajax.beforeSerialize = function (element, options) {
           if (window.patternkitEditor) {
             editor.beforeSerialize();
             editor.saveSchema();
           }

           beforeSerialize.apply(this, [element, options]);
         }
       }

       // Drupal triggers Ajax submit via input events.
       // This is before allowing other events, so we need to add a pre-hook
       // to trigger the editor update with latest field values.
       // @todo Add handling for AJAX errors and re-attach.
       if (!ajax.hasOwnProperty('beforeSubmit')) {
         let beforeSubmit = ajax.beforeSubmit;
         ajax.beforeSubmit = function (formValues, elementSettings, options) {
           if (window.patternkitEditor) {
             // Run any registered beforeSubmit handlers before destroying the
             // JSONEditor form.
             editor.beforeSubmit();

             // Ensure the submitted form values are updated with all changes.
             // If the submit button was clicked without clicking out of a text
             // field, for example, the change event from that field will not
             // have fired, and the serialized form values will not have been
             // updated. In this case, we need to make sure the submitted config
             // value is updated.
             let index = formValues.findIndex(function (o) {
               return o.name === "settings[instance_config]";
             });
             if (index !== -1) {
               window.patternkitEditor.disable();
               formValues[index].value = JSON.stringify(window.patternkitEditor.getValue());
             }
           }

           beforeSubmit.apply(this, [formValues, elementSettings, options]);
         };
       }
     });
   },

   detach(context, settings, trigger) {
     let editors;
     if (trigger === 'serialize') {
       editors = once.filter('patternkit-editor', '#patternkit-editor-target', context);
     }
     else {
       editors = once.remove('patternkit-editor', '#patternkit-editor-target', context);
     }

     editors.forEach((editor) => {
       if (window.patternkitEditor) {
         if (trigger === 'serialize') {
           window.patternkitEditor.patternkit.saveSchema();
         }
         else {
           // Add a status flag to easily check when the editor is being
           // destroyed.
           window.patternkitEditor.detaching = true;
           window.patternkitEditor.disable();
           window.patternkitEditor.destroy();
           delete window.patternkitEditor;
         }
       }
     });
   }
 };

})(jQuery, Drupal, JSONEditor, once);
