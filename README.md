# PatternKit

[![Join the Patternkit Community on Slack](https://drupalslack.herokuapp.com/badge.svg)](https://drupalslack.herokuapp.com) [Drupal.org](https://www.drupal.org/project/patternkit)

Drupal module that loads a ([Drupal Theme](https://drupal.org/projects/patternfly), [PatternLab](https://patternlab.io), [Knapsack](https://www.knapsack.cloud/), [Storybook](https://storybook.js.org/), [REST API](https://github.com/drupal-pattern-lab/patternapi)) library of templates (patterns) as blocks to be used in Layout Builder, Page Manager, and more.

Components (patterns) can be provided by your existing theme templates - just add a [JSON file in schema format](https://json-schema.org/learn/getting-started-step-by-step.html) next to your template to allow editors to fill out and map the components, or download an existing Twig (or other frontend tech) library with JSON schema to be able to drag and drop those components on your layout.

When pattern configurations are saved, the template is downloaded locally (to mitigate origin failures and lock in version at time of configuration.)

Rendered templates may contain Drupal tokens, which are then processed from block context.

## Get Help / Ask a Question
See [CONTRIBUTING.md#get-an-answer-to-a-question](CONTRIBUTING.md#get-an-answer-to-a-question)

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


You can create your own plugins if you'd like to add support for other library types. Feel free to [create a pull request](https://github.com/drupal-pattern-lab/patternkit/pulls) to have it added to the repo!

## Important Variables
* ```patternkit_cache_enabled``` - Whether or not the metadata and render cache are enabled. (Disable during development)
* ```patternkit_pl_host``` - The scheme://hostname:port/ of the PatternLab library host.
* ```patternkit_default_module_ttl``` - How long the rendered pattern should be cached.
* ```patternkit_show_errors``` - Whether or not to display messages on the site.
* ```patternkit_log_errors``` - Whether or not to log errors to php error log.

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
