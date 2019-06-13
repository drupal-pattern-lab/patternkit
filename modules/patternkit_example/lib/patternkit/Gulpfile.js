/**
 * @file
 * Gulp theme compilation script for rhc_ops_ui.
 */

/* eslint-env node */
'use strict';

var gulp = require('gulp');
var export_sass = require('node-sass-export');
var importOnce = require('node-sass-import-once');
var plugins = require('gulp-load-plugins')();
var path = require('path');
var options = {};

options.paths = {
  root: __dirname + '/',
  theme: __dirname + '/',
  patternlib: __dirname + '/lib',
  styleguide: __dirname + '/styleguide',
  patternfly: __dirname + '/node_modules/patternfly'
};

options.theme = {
  root: options.paths.theme,
  patterns: options.paths.theme + 'src/patterns',
  css: options.paths.theme + 'dist/css',
  js: options.paths.theme + 'dist/js',
  fonts: options.paths.theme + 'dist/fonts'
};

options.sass = {
  functions: export_sass(options.theme.patterns + '/'),
  importer: importOnce,
  includePaths: [
    options.theme.patterns + '/',
    options.paths.patternlib + '/'
  ],
  outputStyle: 'expanded'
};

options.eslint = {
  files: [
    options.theme.patterns + '/**/*.js',
    '!' + options.theme.patterns + '/**/*.min.js'
  ]
};

options.tslint = {
  files: [
    options.theme.patterns + '/**/*.ts'
  ],
  options: {
    configuration: options.paths.root + 'tslint.json',
    formatter: 'stylish'
  }
};

options.styleguide = {
  builder: 'node_modules/kss/builder/twig',
  namespace: 'rhcOpsLib:' + options.theme.patterns,
  source: [
    options.theme.patterns
  ],
  destination: options.paths.styleguide,

  // The css and js paths are URLs, like '/misc/jquery.js'.
  // The following paths are relative to the generated style guide.
  css: [
    path.relative(options.paths.styleguide, options.theme.css + '/rhc_ops_ui.css'),
    path.relative(options.paths.styleguide, options.theme.css + '/patternfly.min.css'),
    path.relative(options.paths.styleguide, options.theme.css + '/patternfly-additions.min.css')
  ],
  // List mirrored from patternfly task module. Ordering from PatternFly docs.
  js: [
    path.relative(options.paths.styleguide, options.theme.js + '/d3.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/c3.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/jquery.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/jquery.dataTables.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/dataTables.colReorder.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/dataTables.colVis.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/jquery.matchHeight-min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/bootstrap.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/bootstrap-switch.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/patternfly.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/patternfly.dataTables.pfEmpty.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/patternfly.dataTables.pfFilter.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/patternfly.dataTables.pfPagination.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/patternfly.dataTables.pfResize.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/patternfly.dataTables.pfSelect.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/patternfly-functions.min.js'),
    path.relative(options.paths.styleguide, options.theme.js + '/patternfly-settings.min.js')
  ],
  title: 'RHC Ops UI Style Guide'
};

options.gulpWatchOptions = {};

// Tasks.
gulp.task('default', ['build']);

gulp.task('build', ['js', 'sass:production', 'lint', 'patternfly', 'styleguide:production']);

require('./gulp_tasks/javascript')(gulp, plugins, options);
require('./gulp_tasks/sass')(gulp, plugins, options);
require('./gulp_tasks/styleguide')(gulp, plugins, options);
require('./gulp_tasks/lint')(gulp, plugins, options);
require('./gulp_tasks/patternfly')(gulp, plugins, options);
require('./gulp_tasks/watch')(gulp, plugins, options);
