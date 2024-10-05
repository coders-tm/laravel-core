const mix = require("laravel-mix");
const fs = require("fs-extra");
const path = require("path");

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
  .options({
    terser: {
      terserOptions: {
        compress: {
          drop_console: true,
        },
      },
    },
    processCssUrls: true,
  })
  .setPublicPath("public/statics")
  .js(`resources/js/app.js`, "js")
  .sass(`resources/sass/app.scss`, "css")
  .version()
  .webpackConfig({
    resolve: {
      symlinks: false,
      alias: {
        "@": path.resolve(__dirname, `resources/js/`),
      },
    },
    devServer: {
      https: {
        key: fs.readFileSync(process.env.MIX_CERTIFICATE_KEY),
        cert: fs.readFileSync(process.env.MIX_CERTIFICATE_CERT),
      },
    },
  })
  .options({
    hmrOptions: {
      host: process.env.APP_DOMAIN,
      port: 8090,
    },
  });

// Additional production-specific configuration
if (mix.inProduction()) {
  mix
    .copy("dist/spa/index.html", "resources/views/app.blade.php")
    .copyDirectory("dist/spa", "public")

    // Delete the 'index.html' file from the 'public' directory
    .then(() => {
      fs.remove("public/index.html");
    });
}
