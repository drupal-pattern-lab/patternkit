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
 * @todo .patternkit-editor-target .card all: initial
 */

 import {patternkitEditorQuill} from './patternkit.jsoneditor.quill.es6.js';
 import {patternkitEditorCKEditor} from './patternkit.jsoneditor.ckeditor.es6';
 import {patternkitEditorCygnet} from './patternkit.jsoneditor.cygnet.es6.js';

 patternkitEditorQuill(jQuery, Drupal, JSONEditor);
 patternkitEditorCKEditor(jQuery, Drupal, JSONEditor);
 patternkitEditorCygnet(jQuery, Drupal, JSONEditor);

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
       let $target = $('#patternkit-editor-target', context);
       $target.once('patternkit-editor').each(function () {

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
           let theme_element = document.createElement('link');
           theme_element.rel = "stylesheet";
           theme_element.id = "theme_stylesheet";
           theme_element.href = settings.patternkitEditor.themeStylesheet;
           document.getElementsByTagName('head')[0].appendChild(theme_element);
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

         let editor_root = document;
         // We need to use a Shadow Dom to use themes, which has its own complications
         // with other JS libraries trying to access editor components, for example WYSIWYG.
         if (settings.patternkitEditor.theme !== 'html') {
           let shadow = this.attachShadow({mode: 'open'});
           shadow.innerHTML += editor_dom;
           editor_root = $target[0].shadowRoot;
         }
         else {
           $target.html(editor_dom);
         }

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
         // Initialize the editor with a JSON schema.
         let config = {
           schema: data.schema,
           refs: {}
         };
         window.patternkitEditor = new JSONEditor(editor_root.getElementById('editor_holder'), config);
         window.patternkitEditor.on('ready', function () {
           // If we provide starting JSON as a value, JSON Editor hides all
           // non-required fields, which is desired behavior by most users of the
           // library. For patterns, we want to include any new schema fields in
           // our values so they are displayed by default, optional or not.
           // This also allows us to pre-populate based on the schema provided.
           if (typeof data.starting === 'object' && !$.isEmptyObject(data.starting)) {
             window.patternkitEditor.setValue({...window.patternkitEditor.getValue(), ...data.starting});
           }
           // Material Design JS doesn't update fields on ready event.
           // We call it to make up for that gap.
           if (window.M) {
             window.M.updateTextFields();
           }
         });
         window.patternkitEditor.on('change', saveSchema);
         // Drupal triggers Ajax submit via input events.
         // This is before allowing other events, so we need to add a pre-hook
         // to trigger the editor update with latest field values.
         // @todo Add handling for AJAX errors and re-attach.
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
               $target.removeOnce('patternkit-editor');
             }
           }
           parent_call.call(this, formValues, elementSettings, options);
         };
         $('[data-drupal-selector="edit-actions-submit"]').parent('form').once().each(function () {
           $(this).on('submit', (e) => {
             window.patternkitEditor.disable();
             saveSchema();
             window.patternkitEditor.destroy();
             delete window.patternkitEditor;
             $target.removeOnce('patternkit-editor');
             $(this).off('submit');
           });
         });
       });
     }
   };
 })(jQuery, Drupal, JSONEditor);
