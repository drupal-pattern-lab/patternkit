const gulp = require('gulp');
const babelify = require('babelify');
const browserify = require('browserify');
const buffer = require('vinyl-buffer');
const source = require('vinyl-source-stream');
const rename = require('gulp-rename');
const drupalize = require('./js/postcss/drupalize');
const postcss = require('gulp-postcss');

const gulpConfig = {
  src: './js/**es6.js',
  dist: './js',
  main: 'patternkit.jsoneditor.es6.js',
  lib: [
    './node_modules/@json-editor/json-editor/dist/**',
    './node_modules/ajv/dist/ajv.min.js',
    './node_modules/ajv/dist/ajv.min.js.map',
    './node_modules/handlebars/dist/handlebars.js',
    './node_modules/handlebars/dist/handlebars.min.js',
  ],
};

// @todo Move to Rollup from Browserify to support modular bundling.
gulp.task('compile:es6', function () {
  return browserify(`${gulpConfig.dist}/${gulpConfig.main}`)
    .transform(babelify)
    .bundle()
    .pipe(source(gulpConfig.main.replace('.es6', '')))
    .pipe(buffer())
    .pipe(gulp.dest(gulpConfig.dist));
});

gulp.task('copy:lib', function () {
  return gulp.src(gulpConfig.lib).pipe(gulp.dest(gulpConfig.dist));
});

// Prepare processing for prefixing CSS style rules.
const patternkitTargetId = '#patternkit-editor-target';
const drupalModalId = '#drupal-off-canvas-wrapper';
const nonModalSelector = `${patternkitTargetId}:not(${drupalModalId} *)`;

// Prefix to target both inside and outside the sidebar tray.
const prefixPlugin = drupalize({
  prefixes: [
    drupalModalId,
    nonModalSelector,
  ],
});
// Append the CKEditor scope restriction to all rules outside the sidebar tray.
const suffixPlugin = drupalize({
  suffixes: [':not(.ck *)'],
  filter: patternkitTargetId,
});

// Prefixes upstream JSON Editor's core styles, so they can be used in
// non-shadow DOM version of Cygnet.
gulp.task('prefix-css:cygnet-theme-upstream', function () {
  return gulp
    .src('./node_modules/@json-editor/json-editor/src/style.css')
    .pipe(postcss([drupalize({prefixes: ['[data-theme=cygnet]']})]))
    .pipe(rename('cygnet--prefixed-for-drupal--upstream.css'))
    .pipe(gulp.dest('./css/cygnet'));
});

// Prefixes Cygnet's styles, so they work when JSON Editor gets loaded outside
// the shadow DOM.
gulp.task('prefix-css:cygnet-theme', function () {
  return gulp
    .src('./css/cygnet/cygnet.css')
    .pipe(rename('cygnet--prefixed-for-drupal.css'))
    .pipe(postcss([prefixPlugin]))
    .pipe(postcss([suffixPlugin]))
    .pipe(gulp.dest('./css/cygnet'));
});

gulp.task('watch', function () {
  return gulp.watch(gulpConfig.src, 'compile:es6');
});

gulp.task(
  'default',
  gulp.parallel([
    'compile:es6',
    'copy:lib',
    'prefix-css:cygnet-theme',
    'prefix-css:cygnet-theme-upstream',
  ]),
);
