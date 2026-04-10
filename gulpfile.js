const gulp = require('gulp');
const rename = require('gulp-rename');
const postcss = require('gulp-postcss');
const drupalize = require('./js/postcss/drupalize');

const gulpConfig = {
  dist: './js',
  main: 'patternkit.jsoneditor.js',
  lib: [
    './node_modules/@json-editor/json-editor/dist/**',
    './node_modules/handlebars/dist/handlebars.js',
    './node_modules/handlebars/dist/handlebars.min.js',
  ],
};

gulp.task('copy:lib', () =>
  gulp.src(gulpConfig.lib).pipe(gulp.dest(gulpConfig.dist)),
);

// Prepare processing for prefixing CSS style rules.
const patternkitTargetId = '#patternkit-editor-target';
const drupalModalId = '#drupal-off-canvas-wrapper';
const nonModalSelector = `${patternkitTargetId}:not(${drupalModalId} *)`;

// Prefix to target both inside and outside the sidebar tray.
const prefixPlugin = drupalize({
  prefixes: [drupalModalId, nonModalSelector],
});
// Append the CKEditor scope restriction to all rules outside the sidebar tray.
const suffixPlugin = drupalize({
  suffixes: [':not(.ck *)'],
  filter: patternkitTargetId,
});

// Prefixes upstream JSON Editor's core styles, so they can be used in
// non-shadow DOM version of Cygnet.
gulp.task('prefix-css:cygnet-theme-upstream', () =>
  gulp
    .src('./node_modules/@json-editor/json-editor/src/style.css')
    .pipe(postcss([drupalize({ prefixes: ['[data-theme=cygnet]'] })]))
    .pipe(rename('cygnet--prefixed-for-drupal--upstream.css'))
    .pipe(gulp.dest('./css/cygnet')),
);

// Prefixes Cygnet's styles, so they work when JSON Editor gets loaded outside
// the shadow DOM.
gulp.task('prefix-css:cygnet-theme', () =>
  gulp
    .src('./css/cygnet/cygnet.css')
    .pipe(rename('cygnet--prefixed-for-drupal.css'))
    .pipe(postcss([prefixPlugin]))
    .pipe(postcss([suffixPlugin]))
    .pipe(gulp.dest('./css/cygnet')),
);

gulp.task(
  'default',
  gulp.parallel([
    'copy:lib',
    'prefix-css:cygnet-theme',
    'prefix-css:cygnet-theme-upstream',
  ]),
);
