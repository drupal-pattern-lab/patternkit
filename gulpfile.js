const gulp = require('gulp');
const babelify = require('babelify');
const browserify = require("browserify");
const buffer = require('vinyl-buffer');
const source = require('vinyl-source-stream');
const prefixCss = require('gulp-prefix-css');
const rename = require('gulp-rename');

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
  ]
};

// @todo Move to Rollup from Browserify to support modular bundling.
gulp.task('compile:es6', function () {
  return browserify(gulpConfig.dist + '/' + gulpConfig.main)
    .transform(babelify)
    .bundle()
    .pipe(source(gulpConfig.main.replace('.es6', '')))
    .pipe(buffer())
    .pipe(gulp.dest(gulpConfig.dist));
});

gulp.task('copy:lib', function() {
  return gulp.src(gulpConfig.lib).pipe(gulp.dest(gulpConfig.dist));
});

// Prefixes upstream JSON Editor's core styles so they can be used in non-shadow
// DOM version of Cygnet.
const drupalModalId = '#drupal-off-canvas';
gulp.task('prefix-css:cygnet-theme-upstream', function(){
  return gulp.src('./node_modules/@json-editor/json-editor/src/style.css')
    .pipe(prefixCss(drupalModalId))
    .pipe(rename('cygnet--prefixed-for-drupal--upstream.css'))
    .pipe(gulp.dest('./css/cygnet'));
});

// Prefixes Cygnet's styles so they work when JSON Editor gets loaded outside
// the shadow DOM.
gulp.task('prefix-css:cygnet-theme', function(){
  return gulp.src('./css/cygnet/cygnet.css')
    .pipe(prefixCss(drupalModalId))
    .pipe(rename('cygnet--prefixed-for-drupal.css'))
    .pipe(gulp.dest('./css/cygnet'));
});

gulp.task('watch', function() {
  return gulp.watch(gulpConfig.src, 'compile:es6');
});

gulp.task('default', gulp.parallel(['compile:es6', 'copy:lib', 'prefix-css:cygnet-theme', 'prefix-css:cygnet-theme-upstream']));
