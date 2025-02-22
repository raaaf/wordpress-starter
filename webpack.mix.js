let mix = require("laravel-mix");
let path = require("path");

// Set public path
mix.setPublicPath(path.resolve("./"));

// Compile JavaScript
mix.js("resources/js/app.js", "dist/")
   .minify("dist/app.js"); // Minifies JS for production

// Compile CSS with TailwindCSS and enable PurgeCSS in production
mix.postCss("resources/css/app.css", "dist/app.css", [
   require("@tailwindcss/postcss"),
   require("autoprefixer"),
   require("cssnano")({ preset: "default" }) // Minifies CSS
]);

// Optimize Editor Styles separately
mix.postCss("resources/css/editor-style.css", "dist/editor-style.css", [
   require("@tailwindcss/postcss"),
   require("autoprefixer"),
]);

// Copy assets into `dist/`
mix.copyDirectory("resources/img", "dist/img");
mix.copyDirectory("resources/fonts", "dist/fonts");
mix.copyDirectory("resources/favicons", "dist/favicons");

// Prevent Laravel Mix from processing URLs in CSS
mix.options({
   processCssUrls: false,
   terser: { extractComments: false }, // Removes comments from minified JS
});

// Enable BrowserSync for live reloading
mix.browserSync({
   files: ["resources/**/*", "templates/**/*", "index.php"],
   proxy: "http://buk-stuttgart.local",
   https: false,
});

// Enable versioning (cache-busting) for production
if (mix.inProduction()) {
   mix.version();
}