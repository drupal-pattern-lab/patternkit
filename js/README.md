# Patternkit JavaScript Dependencies

## JSON Editor JS

- `js/patternkit.jsoneditor.js` is an ES6 module served directly by Drupal.
  It is not transpiled or bundled — edit the source directly.

## Dependencies

- `@json-editor/json-editor` and `handlebars` are built into the `js/`
  directory via the Gulp `copy:lib` task (`gulp copy:lib`).
- Run the build script to update copied library files:

      npm run build

## Getting Started

- Use Node Version Manager:

      nvm use

- Install dependencies:

      npm install

- Copy library files to `js/`:

      gulp copy:lib
