# PatternKit

[![Join the Patternkit Community on Slack](https://drupalslack.herokuapp.com/badge.svg)](https://drupalslack.herokuapp.com) [Drupal.org](https://www.drupal.org/project/patternkit)

Drupal module that wraps a PatternLab library of patterns.
 
This code will parse a pattern library (local or through REST endpoints) to generate a list of blocks that can be drag/dropped into layouts.

If enabled, the configuration screen is simply an iframe wrapper of a simplified version of the PatternLab schema builder.

When pattern configurations are saved, the template is downloaded locally (to mitigate origin failures and lock in version at time of configuration.)

Rendered templates may contain drupal tokens, which are then processed in context.

## Get Help / Ask a Question
See [CONTRIBUTING.md#get-an-answer-to-a-question](CONTRIBUTING.md#get-an-answer-to-a-question)

## Installation
Install the patternkit module as usual, and review the important variables below to determine if you would like to change the defaults.

```
composer require drupal/patternkit
drush en -y patternkit
```

The patternkit module by itself only provides the glue for other modules to present components. Define one by adding a 'patterns' section to your libraries.yml.

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
      lib/patternkit/src/atoms: {}
```

There are two different plugins currently available, 
* PatternkitRESTLibrary
* PatternkitTwigLibrary (based on PatternkitJSONLibrary)

Use the former for dynamic REST based components, and the latter for locally sourced.

You can create your own plugins if you'd like to add support for other library types. Feel free to [create a pull request](https://github.com/cybtachyon/patternkit/pulls) to have it added to the repo!

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
* https://github.com/drupal-pattern-lab/roadmap/issues/8 Solve the problem of mapping Drupal fields to pattern Variables.
* Error handling.
* Better information about available tokens.
* More investigation into the appropriate handling of web component configuration.
* Finalize the CSS/JS management strategy.
More documentation will be added.

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
