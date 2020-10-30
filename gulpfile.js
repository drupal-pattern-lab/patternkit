const gulp = require('gulp');
const babelify = require('babelify');
const browserify = require("browserify");
const buffer = require('vinyl-buffer');
const source = require('vinyl-source-stream');

const gulpConfig = {
  src: './js/**es6.js',
  dist: './js',
  main: 'patternkit.jsoneditor.es6.js',
  lib: [
    './node_modules/@json-editor/json-editor/dist/**',
    './node_modules/ajv/dist/ajv.min.js',
    './node_modules/ajv/dist/ajv.min.js.map'
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

gulp.task('watch', function() {
  return gulp.watch(gulpConfig.src, 'compile:es6');
});

gulp.task('default', gulp.parallel(['compile:es6', 'copy:lib']));
