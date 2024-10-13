const mix = require("laravel-mix");
const path = require("path");
const fs = require("fs");

// Get the theme name from the environment variable or use 'foundation' as default
const themePublic = process.env.MIX_THEME_PUBLIC;
const themeBasePath = process.env.THEME_PATH || "themes";
const themeName = process.env.THEME_NAME || "foundation"; // Default to 'foundation'
const themePath = path.join(themeBasePath, themeName);
let publicPath = path.join("public/themes", themeName);

if (themePublic) {
  const themePublicPath = path.join(themePath, ".public");

  if (fs.existsSync(themePublicPath)) {
    const randomPath = fs.readFileSync(themePublicPath, "utf8").trim();
    if (randomPath) {
      publicPath = path.join("public", randomPath);
    }
  } else {
    // Generate a random 6-digit number and check if a directory with that number already exists
    let randomPath;

    do {
      randomPath = Math.floor(10000000 + Math.random() * 90000000); // Generate a random 6-digit number
      publicPath = path.join("public/themes", randomPath.toString());
    } while (fs.existsSync(publicPath)); // Keep generating if the directory exists

    // Write the generated number to .public file
    fs.writeFileSync(
      themePublicPath,
      path.join("themes", randomPath.toString())
    );
  }
}

// Ensure that the public path directory exists before writing to it
if (!fs.existsSync(publicPath)) {
  fs.mkdirSync(publicPath, { recursive: true });
}

// Function to calculate the devServer config
const getDevServerConfig = () => {
  if (!mix.inProduction()) {
    return {
      https: {
        key: fs.readFileSync(process.env.MIX_CERTIFICATE_KEY),
        cert: fs.readFileSync(process.env.MIX_CERTIFICATE_CERT),
      },
      // Additional devServer options can be added here
    };
  }
  return {}; // Return {} in production
};

// Assign devServer configuration based on the environment
const devServerConfig = getDevServerConfig();

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
    processCssUrls: true,
  })
  .setPublicPath(publicPath) // Set the dynamic public path
  .js(`${themePath}/assets/js/app.js`, "js") // Compile JS
  .sass(`${themePath}/assets/sass/app.scss`, "css") // Compile Sass
  .version() // Enable versioning (cache busting)
  .webpackConfig({
    resolve: {
      symlinks: false,
      alias: {
        "@": path.resolve(__dirname, `${themePath}/assets/js/`), // Set alias for JS
      },
    },
    devServer: devServerConfig,
  })
  .options({
    hmrOptions: {
      host: process.env.APP_DOMAIN, // Host for hot module replacement
      port: 8080, // Port for HMR
    },
  });
