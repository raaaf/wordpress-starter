let mix = require("laravel-mix");
let path = require("path");

mix.setPublicPath(path.resolve("./"));

mix.js("resources/js/app.js", "dist/");

mix.postCss("resources/css/app.css", "dist/app.css", [require("@tailwindcss/postcss")]);

mix.postCss("resources/css/editor-style.css", "./", [require("@tailwindcss/postcss")]);

mix.copyDirectory("resources/img", "./img");

mix.copyDirectory("resources/fonts", "./fonts");

mix.copyDirectory("resources/favicons", "./favicons");

mix.options({
  processCssUrls: false,
});

mix.browserSync({
  files: ["resources/**/*", "templates/**/*", "index.php"],
  proxy: "LOCAL-URL GOES HERE",
  https: false,
});

mix.version();
