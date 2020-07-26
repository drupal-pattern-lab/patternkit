const gulp = require('gulp');
const babelify = require('babelify');
const browserify = require("browserify");
const buffer = require('vinyl-buffer');
const source = require('vinyl-source-stream');

const gulpConfig = {
  src: './js/**es6.js',
  dist: './js',
  main: 'patternkit.jsoneditor.media_library.es6.js'
};

const compileEs6 = function () {
  return browserify(gulpConfig.dist + '/' + gulpConfig.main)
    .transform(babelify)
    .bundle()
    .pipe(source(gulpConfig.main.replace('.es6', '')))
    .pipe(buffer())
    .pipe(gulp.dest(gulpConfig.dist));
};

gulp.task('compile:es6', compileEs6);

gulp.task('watch', function() {
  return gulp.watch(gulpConfig.src, 'compile:es6');
});

gulp.task('default', compileEs6);
