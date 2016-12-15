const gulp = require('gulp');
const shell = require('gulp-shell');
const del = require('del');

const config = {
    svn: {
        url: 'https://plugins.svn.wordpress.org/multiple-content-types/',
        src: [
            './**',
            '!**/screenshot-1.png',
            '!**/svn',
            '!**/svn/**',
            '!**/readme.md',
            '!**/package.json',
            '!**/node_modules',
            '!**/node_modules/**',
            '!**/gulpfile.js',
            '!**/composer.json',
            '!**/*.lock'
        ],
        dest: './svn/trunk',
        clean: './svn/trunk/**/*'
    }
};

gulp.task('svn:checkout', shell.task('svn co ' + config.svn.url + ' svn'));

gulp.task('svn:clean', function () {
    return del(config.svn.clean);
});

gulp.task('svn:copy', ['svn:clean'], function () {
    return gulp.src(config.svn.src).pipe(gulp.dest(config.svn.dest));
});

gulp.task('svn:stage', ['svn:copy']);

gulp.task('default', ['svn:stage']);