'use strict';
const gulp = require('gulp');
const del = require('del');
const webpack = require('webpack-stream');
const sass = require('gulp-sass');
const ghPages = require('gh-pages');
const handlebars = require('gulp-compile-handlebars');
const handlebarsLayouts = require('handlebars-layouts');
const rename = require('gulp-rename');
const header = require('gulp-header');
const shell = require('gulp-shell');
const sourcemaps = require('gulp-sourcemaps');
const autoprefixer = require('gulp-autoprefixer');
const cleanCss = require('gulp-clean-css');
const fs = require('fs');
const pkg = JSON.parse(fs.readFileSync('./package.json'));

handlebars.Handlebars.registerHelper(handlebarsLayouts(handlebars.Handlebars));

let banner = `/*!
 * Bootstrap Colorpicker - <%= pkg.description %>
 * @package <%= pkg.name %>
 * @version v<%= pkg.version %>
 * @license <%= pkg.license %>
 * @link <%= pkg.homepage %>
 * @link <%= pkg.repository.url %>
 */
`;

let distDir = 'dist', buildDir = 'build', docsDir = buildDir + '/docs';

/**
 * Returns a callback that runs the given tasks programmatically
 * @returns {Function}
 */
let tasksCb = function () {
  let tasks = Array.prototype.slice.call(arguments);

  return function () {
    return gulp.start(tasks);
  };
};

gulp.task('clean', function () {
  return del([
    distDir + '/**/*',
    buildDir + '/**/*'
  ]);
});

// ##########################
// JS files (webpack + babel)
// ##########################

gulp.task('js:clean', function () {
  return del([distDir + '/js/**/*']);
});

gulp.task('js:compile', function () {
  return gulp.src([])
    .pipe(webpack(require('./webpack.config.js')))
    .pipe(header(banner, {pkg: pkg}))
    .pipe(gulp.dest(distDir + '/js/'));
});

gulp.task('js', ['js:clean'], tasksCb('js:compile'));

// ##########################
// SASS files
// ##########################

gulp.task('css:clean', function () {
  return del([distDir + '/css/**/*']);
});

gulp.task('css:compile', function () {
  return gulp.src('./src/sass/colorpicker.scss')
    .pipe(sourcemaps.init())
    .pipe(header(banner, {pkg: pkg}))
    .pipe(sass({style: 'expanded'}).on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(rename('bootstrap-colorpicker.css'))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(distDir + '/css/'));
});

gulp.task('css:minify', ['css:compile'], function () {
  return gulp.src(distDir + '/css/bootstrap-colorpicker.css')
    .pipe(sourcemaps.init())
    .pipe(cleanCss())
    .pipe(rename({extname: '.min.css'}))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(distDir + '/css/'));
});

gulp.task('css', ['css:clean'], tasksCb('css:minify'));

// ##########################
// Examples written in handlebars
// ##########################

gulp.task('examples:compile', function () {
  let data = {banner: banner, package: pkg};
  let options = {
    batch: ['./src/docs', './src/docs/examples'],
    helpers: {
      code: function (hbsOptions) {
        let entityMap = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;',
          '/': '&#x2F;'
        };

        return String(hbsOptions.fn(this)).trim().replace(/[&<>"'\/]/g, function (s) {
          return entityMap[s];
        }).replace(/^ {2,8}/mgi, '');
      }
    }
  };

  gulp.src('src/docs/examples.json')
    .pipe(gulp.dest(buildDir + '/examples'));

  return gulp.src('src/docs/examples/**/*.hbs')
    .pipe(handlebars(data, options))
    .pipe(rename({extname: '.html'}))
    .pipe(gulp.dest(buildDir + '/examples'));
});

// ##########################
// JSDoc documentation
// ##########################

gulp.task('docs:clean', function () {
  return del([docsDir + '/*']);
});

gulp.task('docs:compile', ['examples:compile'], shell.task([
  'echo "Compiling docs..."',
  'node_modules/.bin/jsdoc --configure .jsdoc.json --verbose'
]));

gulp.task('docs:add-dist', shell.task([
  'echo "Adding dist files to docs..."',
  `mkdir -p ${distDir} && mkdir -p ${docsDir}`,
  `cp -R ${distDir} ${docsDir}/dist`
]));

gulp.task('docs:add-v2-docs', shell.task([
  'echo "Adding v2 docs..."',
  'mkdir -p build/tmp',
  'rm -rf build/tmp/* build/docs/v2/*',
  `git clone ${pkg.repository.url} build/tmp/v2`,
  'cd build/tmp/v2 && git checkout v2 && cd ../../../',
  'mkdir -p build/docs/v2 && cp build/tmp/v2/index.html build/docs/v2/index.html',
  'mkdir -p build/docs/v2/dist && cp -R build/tmp/v2/dist/* build/docs/v2/dist',
  'mkdir -p build/docs/v2/docs/assets && cp -R build/tmp/v2/docs/assets/* build/docs/v2/docs/assets',
  'rm -rf build/tmp/*'
]));

gulp.task('docs', ['docs:clean'], tasksCb('docs:compile', 'docs:add-dist'));

// ##########################
// Publish tools
// ##########################

gulp.task('publish-gh-pages', function () {
  // WARNING! You won't be able to publish unless you have write permissions on the repo.
  // Check the gh-pages npm package documentation.
  ghPages.publish('build/docs',
    {
      message: 'Update build with latest master changes'
    },
    function () {
      console.info('The gh-pages branch is updated and pushed 📦.');
    }
  );
});

gulp.task('npm-prepublish', shell.task([
  'cp LICENSE dist/LICENSE',
  'cp README.md dist/README.md'
]));

// ##########################
// Default
// ##########################

gulp.task('watch', ['default'], function () {
  gulp.watch('src/docs/**/*.{hbs,tmpl,css,js}', ['docs']);
  gulp.watch('src/sass/**/*.scss', ['css']);
  gulp.watch('src/js/**/*.js', ['js']);
});

gulp.task('dist', ['js', 'css']);
gulp.task('default', ['dist']);

// To list all tasks run "gulp --tasks" or "gulp --tasks-simple"
