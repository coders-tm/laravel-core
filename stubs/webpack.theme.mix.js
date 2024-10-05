const mix = require("laravel-mix");
const path = require("path");
const fs = require("fs");

// Get the theme name from the command line argument
const themeName = process.env.THEME_NAME || "foundation"; // Default to 'foundation'
const useThemePath = process.env.MIX_THEME_PUBLIC === "theme";
const themePath = `themes/${themeName}`;
const publicPath = useThemePath ? `${themePath}/public` : `public/${themePath}`;

// Ensure that the theme directory exists before writing to it
if (!fs.existsSync(publicPath)) {
  fs.mkdirSync(publicPath, { recursive: true });
}

/*
|--------------------------------------------------------------------------
| Mix Asset Management
|--------------------------------------------------------------------------
|
| Mix provides a clean, fluent API for defining some Webpack build steps
| for your Laravel application. By default, we are compiling the Sass
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
    processCssUrls: !useThemePath,
  })
  .setPublicPath(publicPath)
  .js(`${themePath}/assets/js/app.js`, "js")
  .sass(`${themePath}/assets/sass/app.scss`, "css")
  .version()
  .webpackConfig({
    resolve: {
      symlinks: false,
      alias: {
        "@": path.resolve(__dirname, `${themePath}/assets/js/`),
      },
    },
    devServer: {
      static: {
        directory: path.resolve(__dirname, `${themePath}/assets`), // Specify the public folder to serve
      },
      compress: true,
      https: {
        key: fs.readFileSync(process.env.MIX_CERTIFICATE_KEY),
        cert: fs.readFileSync(process.env.MIX_CERTIFICATE_CERT),
      },
    },
  })
  .options({
    hmrOptions: {
      host: process.env.APP_DOMAIN,
      port: 8080,
    },
  });
