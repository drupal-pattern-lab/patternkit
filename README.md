# PatternKit

Patternkit is a Drupal module that loads a library of patterns as blocks to be
used in Layout Builder. These patterns can originate from a Drupal Theme,
[PatternLab](https://patternlab.io), [Knapsack](https://www.knapsack.cloud/),
[Storybook](https://storybook.js.org/), or an API.

Patterns or components can be provided by your existing theme templates by
adding a [JSON file in schema format](https://json-schema.org/learn/getting-started-step-by-step.html)
next to your template to allow content authors to fill out and map the
components, or download an existing Twig library with JSON schema to be able to
drag and drop those components on your layout.

When pattern configurations are saved, the template is downloaded locally (to
mitigate origin failures and lock in version at time of configuration.)

Rendered templates may contain Drupal tokens, which are then processed from
block context.

## Get Help / Ask a Question

Join the [#contrib-patternkit](https://drupal.slack.com/archives/C9A9ZD9K2)
channel on Slack.

## Installation

1. Install the patternkit module as usual, and review the important variables
   below to determine if you would like to change the defaults.

    ```bash
    composer require drupal/patternkit
    drush en -y patternkit
    ```

2. The patternkit module by itself only provides the glue for other modules
   to present components. Define one by adding a 'patterns' section to your
   libraries.yml.

   An example implementation follows:
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

    The primary plugin for usage at this time is: PatternkitTwigLibrary
    (based on PatternkitJSONLibrary) `type: twig`

    This plugin searches for locally sourced Twig templates like those in a
    Drupal theme paired with a related JSON schema file.

3. If not already present in the library, add companion JSON schema files to
   your templates so that Patternkit can display the editor to allow for
   drag-n-drop mapping in the Layout Builder of your choice.

   See [example.json](modules/patternkit_example/lib/patternkit/src/atoms/example/src/example.json).

You can create your own plugins if you'd like to add support for other library
types. If you feel others can benefit from your plugin please feel free to
contribute to the project.

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
Drupal (which are set at `/admin/appearance`). The default JSON Editor theme is
Cygnet (based on vanilla HTML but customized for Patternkit). Patternkit comes
bundled with other themes, based on Bootstrap, Foundation, etc.

#### WYSIWYG support

A pattern can support WYSIWYG for fields in its JSON schema. To do this, the
schema must set the `type` to `string`, the `format` to `html`, and under
`options`, set `wysiwyg` to `true`. Example:

```json
{
  "my_wysiwyg_field": {
    "title": "My WYSIWYG field",
    "type": "string",
    "format": "html",
    "options": {
      "wysiwyg": true
    }
  }
}
```

At `/admin/config/user-interface/patternkit/json`, you can select which WYSIWYG
plugin to use. Options:

* CKEditor (preferred)
  * Pick a Drupal text format in _CKEditor toolbar_ (see below for details)

##### Text format CKEditor configuration

Under the hood, Patternkit uses Drupal core's bundled CKEditor plugin. To enable
CKEditor in Patternkit, you first must define a Text format (at
`/admin/config/content/formats`) that uses CKEditor as its text editor.
Configure the text format to include the desired CKEditor buttons. Then, on
`/admin/config/user-interface/patternkit/json`, you can select this text format
in _CKEditor toolbar_.

## Definitions

Most of the thinking and vernacular used in Patternkit is inspired by
conversations that have happened around Design Systems. A great reference for
this is [Brad Frost's Atomic Design Book](http://atomicdesign.bradfrost.com/).

* **Category**: The design system category for a pattern, e.g. _Atom_,
  _Molecule_, _Organism_.
* **Design System**: A modular and manageable approach to creating reusable
  design patterns for building GUI's.
  See http://atomicdesign.bradfrost.com/chapter-1/
* **Pattern**: A component, widget, or template that can be rendered to an
  HTML UI.

## Drush commands

The following Drush commands are provided to assist in working with the
Patternkit module and viewing the data it has discovered. A full and current
list of the commands provided by the Patternkit module may be viewed using
the following command:

```bash
drush list --filter=patternkit
```

Specific usage instructions and examples for each may also be viewed using
`drush help <command>`.

### Update all existing patterns

When a new pattern block is placed, the pattern data including the schema
and template content are saved in the database as a Pattern entity. If the
pattern schema or template is updated, these cached versions also need to be
updated to match. This may be done on a per-block basis through the UI by
editing the pattern block, or it may be done wholesale using this Drush command:

```bash
drush patternkit:batch-update
```

### View all discovered patterns

A list of all discovered patterns may be viewed using the following command:

```bash
drush patternkit:pattern:list
```

### View information about a single pattern

Detailed information about a single pattern may be viewed using the
following command:

```bash
drush patternkit:pattern:info '@patternkit/atoms/example/src/example'
```

### View the schema for a single pattern

Oftentimes it can be helpful to view the schema saved for a specific pattern.
This may be done easily from the commandline using the following command:

```bash
drush patternkit:pattern:schema '@patternkit/atoms/example/src/example'
```

## Dependencies

* **drupal/block_content (Core Module)**: This module will auto-enable when
  Patternkit is installed.

## Optional Dependencies

* **drupal/layout_builder (Core Module)**: This module provides the most common
  method for creating and exposing Patternkit block content within pages.

## Development and Debugging

When working with patterns and Patternkit, having more tools available for
debugging and testing can always make your life easier. To this end, the
[Patternkit Development Tools module](https://www.drupal.org/project/patternkit_devel)
can be helpful to add insight and testing tools that may prove valuable.

Aside from the supporting module, there is also some debugging functionality
built directly into the Patternkit module that may be enabled on an
environment-specific basis. Very similar to the way [debugging for Twig](https://www.drupal.org/docs/theming-drupal/twig-in-drupal/debugging-twig-templates#s-enable-debugging)
may be enabled, some configuration options are available through service
parameters for Patternkit as well. An overall debug mode for the module may be
enabled by adding the following to your `sites/default/services.yml` or
`sites/development.services.yml` file directly:

```yaml
parameters:
  patternkit.config:
    debug: true
```

Additionally, specific features may be enabled as seen below:

```yaml
parameters:
  # Configuration options to influence behavior of the Patternkit modules.
  patternkit.config:

    # This may be enabled to toggle debug features in the module.
    debug: true

    # Various operational features of the module may be toggled here.
    features:

      # Enable this to bypass cache loading of pattern definitions and
      # rediscover patterns during every request.
      bypass_pattern_cache: true

      # Enable this to bypass loading of pattern schemas and templates
      # from saved pattern entities in the database. When enabled, each
      # block will ignore the referenced database Pattern entity and load
      # the pattern directly from the available parser definition instead.
      bypass_database_cache: true
```

A complete list of available features to toggle may be always be found in the
module's [`patternkit.services.yml`](patternkit.services.yml) file.

### JSON Editor version management

Patternkit ships pre-built JSON Editor files in the `js/` directory. These
committed files are what production sites receive when installing Patternkit
via Composer (`composer require drupal/patternkit`). The
`npm-asset/json-editor--json-editor` dependency in `composer.json` is declared
in `require-dev` and pinned to the **exact version** matching these committed
files.

**Why `require-dev` with exact pinning?** Production sites use the committed
`js/` files — they never resolve or install the npm-asset package. Composer
`repositories` blocks from sub-packages are not inherited by the root project,
`post-install-cmd`/`post-update-cmd` scripts only fire for the root project,
and the `slowprog/composer-copy-file` handler is also a dev dependency. Placing
the npm-asset package in `require-dev` accurately reflects this: it is a
development tool for building the committed JS files, not a runtime dependency
for consuming sites. Exact version pinning (no caret) ensures that the dev/CI
environment tests against the same JSON Editor version that production sites
receive, preventing silent version drift and non-deterministic test results.

**Updating JSON Editor:** To update to a new JSON Editor version:

1. Change the version in `composer.json` (e.g. `"2.15.2"` to `"2.16.0"`)
2. Run `composer update npm-asset/json-editor--json-editor`
3. The `post-update-cmd` copy script fires automatically, updating `js/`
4. Run the test suite to validate the new version
5. Commit both `composer.json` and `js/` together as a single reviewable change

## Contributing

See [CONTRIBUTING.MD](CONTRIBUTING.md).
