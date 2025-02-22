let mix = require('laravel-mix');
let path = require('path');

mix.setPublicPath(path.resolve('./'));

mix.js('resources/js/app.js', 'dist/');

mix.postCss("resources/css/app.css", "dist/app.css");

mix.postCss("resources/css/editor-style.css", "./");

mix.copyDirectory("resources/img", "./img")

mix.copyDirectory("resources/fonts", "./fonts")

mix.copyDirectory("resources/favicons", "./favicons")

mix.options({
    processCssUrls: false,
    postCss: [
        require('postcss-nested-ancestors'),
        require('postcss-nested'),
        require('postcss-import'),
        require('tailwindcss'),
        require('autoprefixer'),
    ]
});

mix.browserSync({
    files: [
        'resources/**/*',
        'templates/**/*',
        'index.php',
    ],
    proxy: 'http://localhost:10048',
    https: false
});

mix.version();
