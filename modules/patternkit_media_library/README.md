## Introduction

The Patternkit Media Library Support module allows you to use Drupal's media
library browser to select images for patterns.

This module will add a button to image fields. You can click this button to open
the media library browser in a modal window.

## Installation

Enable this module (e.g., `drush en patternkit_media_library`).

## Configuration

You can configure this module at path
`/admin/config/user-interface/patternkit/media-library`.

### Bundled styles

You can choose whether to load supplemental styles for the media library
browser. This feature relies on templates from [Media Library Theme
Reset](https://www.drupal.org/project/media_library_theme_reset).

## Example

This module adds the media library browser button to patterns where both:

* `type`   = `string`
* `format` = `image`

See [example.json](modules/patternkit_example/lib/patternkit/src/atoms/example/src/example.json).
