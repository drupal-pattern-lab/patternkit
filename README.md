# PatternKit

Patternkit is a Drupal module that loads a library of patterns as blocks to be used in Layout Builder.  These patterns can originate from a Drupal Theme, [PatternLab](https://patternlab.io), [Knapsack](https://www.knapsack.cloud/), [Storybook](https://storybook.js.org/), or an API.

Patterns or components can be provided by your existing theme templates by adding a [JSON file in schema format](https://json-schema.org/learn/getting-started-step-by-step.html) next to your template to allow content authors to fill out and map the components, or download an existing Twig library with JSON schema to be able to drag and drop those components on your layout.

When pattern configurations are saved, the template is downloaded locally (to mitigate origin failures and lock in version at time of configuration.)

Rendered templates may contain Drupal tokens, which are then processed from block context.

## Get Help / Ask a Question
Join the [#contrib-patternkit](https://drupal.slack.com/archives/C9A9ZD9K2) channel on Slack.

## Installation

1. Install the patternkit module as usual, and review the important variables below to determine if you would like to change the defaults.

    ```
    composer require drupal/patternkit
    drush en -y patternkit
    ```

1. The patternkit module by itself only provides the glue for other modules to present components. Define one by adding a 'patterns' section to your libraries.yml.

    An example implementation follows
    ```YAML
    patternkit_example.pkexample:
      version: VERSION
      css:
        theme:
          lib/patternkit/dist/patternkit.css: {}
      js:
        lib/patternkit/dist/patternkit.min.js: {}
      patterns:
        atoms:
          lib/patternkit/src/atoms: {type: twig}
    ```

    There are two different plugins currently available,
    * PatternkitRESTLibrary `type: rest`
    * PatternkitTwigLibrary (based on PatternkitJSONLibrary) `type: twig`

    Use the former for dynamic REST based components, and the latter for locally sourced Twig templates like those in a Drupal theme.

1. If not already present in the library, add companion JSON schema files to your templates so that Patternkit can display the editor to allow for drag-n-drop mapping in the Layout Builder of your choice.

    See [example.json](modules/patternkit_example/lib/patternkit/src/atoms/example/src/example.json).


You can create your own plugins if you'd like to add support for other library types.  If you feel others can benefit from your plugin please feel free to contribute to the project.

## Configuration

### General settings

Go to `/admin/config/user-interface/patternkit` to configure general Patternkit
settings. You can exclude sets of patterns, if desired, and set cache settings.

### JSON Editor settings

Patternkit uses [JSON Editor](https://github.com/json-editor/json-editor) for
building each Patternkit block's form, using schema derived from the JSON schema
file that accompanies the pattern's template (see _Installation_ above).

You can configure JSON Editor settings at
`/admin/config/user-interface/patternkit/json`.

#### Theme

The JSON Editor interface loads with its own theme (not a Drupal theme), so it
will appear differently from the Drupal main theme or administration theme in
Drupal (which are set at `/admin/appearance`).  The default JSON Editor theme is
Cygnet (based on vanilla HTML but customized for Patternkit). Patternkit comes
bundled with other themes, based on Bootstrap, Foundation, etc.

#### Shadow DOM?

Most of our bundled JSON Editor themes work under the assumption that Patternkit
has loaded JSON Editor in the context of a shadow DOM. However, you can set
Patternkit to load JSON Editor without a shadow DOM. The main reason to do this
is to enable CKEditor for wysiwyg support (see below).

#### WYSIWYG support

A pattern can support WYSIWYG for fields in its JSON schema. To do this, the
schema must set the `type` to `string`, the `format` to `html`, and under
`options`, set `wysiwyg` to `true`. Example:

```json
    "my_wysiwyg_field": {
      "title": "My WYSIWYG field",
      "type": "string",
      "format": "html",
      "options": {
        "wysiwyg": true
      }
    }
```

At `/admin/config/user-interface/patternkit/json`, you can select which WYSIWYG
plugin to use. Options:

* CKEditor (preferred)
  * Pick a Drupal text format in _CKEditor toolbar_ (see below for details)
* ProseMirror
* Quill

##### Text format CKEditor configuration

Under the hood, Patternkit uses Drupal core's bundled CKEditor plugin. To enable
CKEditor in Patternkit, you first must define a Text format (at
`/admin/config/content/formats`) that uses CKEditor as its text editor.
Configure the text format to include the desired CKEditor buttons. Then, on
`/admin/config/user-interface/patternkit/json`, you can select this text format
in _CKEditor toolbar_.

// TODO:  Include language about Patternkit processing text format filters once this feature has been implemented.


##### CKEditor content filtering

If using CKEditor as the wysiwyg plugin, you can configure the schema to allow
or disallow certain HTML tags and attributes. This feature uses [CKEditor's
content filtering
system](https://ckeditor.com/docs/ckeditor4/latest/guide/dev_acf.html).

###### Setting allowed content

To set which content is allowed, use property `allowedContent` in the `options`
key of your schema. The property's value will be passed as-is to CKEditor's
`allowedContent` configuration ([expected
format](https://ckeditor.com/docs/ckeditor4/latest/guide/dev_allowed_content_rules.html)).

###### Setting disallowed content

Conversely, to prevent certain content, use property `disallowedContent` in the
`options` key of your schema. The property's value will be passed as-is to
CKEditor's `disallowedContent` configuration ([expected
format](https://ckeditor.com/docs/ckeditor4/latest/guide/dev_disallowed_content.html)).

###### Example

In this example, only links, bold/strong, and italic/emphasis tags are allowed.
You can use any attributes on these elements (including class, style, and other
attributes), except ones prefixed with `data-`.

```json
    "formatted_text": {
      "title": "Formatted Text",
      "type": "string",
      "format": "html",
      "options": {
        "wysiwyg": true,
        "allowedContent": "a b strong em i[*](*){*}",
        "disallowedContent": "*[data-*]"
      }
    },
```

_Security note_: Filtering content at the CKEditor layer (i.e., at content
entry) does not filter it when rendered. For example, if you disallow `onclick`
attributes in CKEditor but the pattern's template (e.g., Twig) does not strip
those attibutes, a user might possibly save content with an `onclick` attribute,
and that attribute would be rendered to the page.

### Other settings
The following settings are not available for configuration in the UI, so you must set
them in config item `patternkit.settings` manually:

* `patternkit_pl_host` - The PatternLab library host in format `scheme://hostname:port/`.

## Definitions
Most of the thinking and vernacular used in Patternkit is inspired by conversations that have happened around Design Systems. A great reference for this is [Brad Frost's Atomic Design Book](http://atomicdesign.bradfrost.com/).
* **Category** The design system category for a pattern, e.g. _Atom_, _Molecule_, _Organism_.
* **Design System** A modular and manageable approach to creating reusable design patterns for building GUI's. See http://atomicdesign.bradfrost.com/chapter-1/
* **Pattern** A component, widget, or template that can be rendered to an HTML GUI.


## TODOs
* Allow this module to close out [this issue](https://github.com/drupal-pattern-lab/roadmap/issues/8).
* Error handling.
* More investigation into the appropriate handling of web component configuration.
* Finalize the CSS/JS management strategy.
* More documentation.

## Dependencies
* **drupal/block_content (Core Module)** This module will auto-enable when Patternkit is installed.

## Optional Dependencies
[PatternLab](https://github.com/pattern-lab/starterkit-twig-drupal-minimal)
* Schema display support requires: https://github.com/pattern-lab/patternlab-php-core/issues/117

## D7 v2.0.0 & D8 Ecosystem
* Exists to solve this issue: https://github.com/drupal-pattern-lab/roadmap/issues/8
* PatternLAB with Restful Extensions provides library + endpoints (Ported from PatternKit)
* PatternKit provides Drupal endpoint consumer (renamed from PKPlugins)

## Legacy Ecosystem
* PatternKit provides library + RESTful endpoints
* PKPlugins provides Drupal endpoint consumer

## Contributing
See [CONTRIBUTING.MD](CONTRIBUTING.md)
