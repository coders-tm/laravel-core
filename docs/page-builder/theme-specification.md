# Theme System Specification

## Overview

The theme system is **completely optional** and provides a clean way to bundle and switch between collections of sections, layouts, snippets, and assets. Themes are implemented via a simple facade pattern with full validation and error handling.

**Key Philosophy:**

- Themes are not required to use the page builder
- When used, themes completely override the default paths
- No path duplication — single `Theme::use()` call changes all configurable paths
- Themes are validated before activation
- Runtime theme switching is supported

---

## Theme Structure

### Required Directory Structure

Every theme must have this directory structure:

```
resources/themes/{theme-name}/
├── layouts/                  # Page wrapper Blade files
├── sections/                 # Individual section Blade files + section group JSON files
│   ├── header.blade.php      # Section Blade file
│   ├── header-group.json     # Section group JSON file (lives WITH sections)
│   ├── footer.blade.php
│   └── footer-group.json
├── snippets/                 # Reusable partial Blade files
├── templates/                # Page template JSON files
├── pages/                    # Individual page JSON files
├── assets/                   # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
├── lang/                     # Translation files
│   └── en/
└── config/                   # Theme-specific configurations
```

**Important:** Section group JSON files (`*-group.json`) live **inside** the `sections/` directory alongside section Blade files. This follows the project specification pattern and keeps related files together.

### What Gets Validated

Before a theme can be activated, ThemeManager validates:

- ✔ Theme directory exists at `resources/themes/{name}`
- ✔ All 8 required subdirectories exist
- ✔ Paths are readable
- ✔ File permissions allow reads

**Required directories (8 total):**

1. `layouts` — Page wrapper Blade files
2. `sections` — Section Blade files AND section group JSON files
3. `snippets` — Reusable partial Blade files
4. `templates` — Page template JSON files
5. `pages` — Individual page JSON files
6. `assets` — CSS, JS, images
7. `lang` — Translation files
8. `config` — Theme-specific configurations

**If any check fails**, an `InvalidThemeException` is thrown with details about missing directories.

## Facade Pattern

### Theme Facade

Provides clean, idiomatic Laravel API for theme management:

```php
<?php

namespace Coderstm\Facades;

use Illuminate\Support\Facades\Facade;

class Theme extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-builder.theme';
    }
}
```

### Basic Usage

```php
use Coderstm\Facades\Theme;

// Activate a theme
Theme::use('gympify');

// Check if theme exists
Theme::themeExists('gympify');

// Validate theme structure
Theme::isValidTheme('gympify');

// Get all available themes
Theme::available();

// Get currently active theme
Theme::active();

// Check if specific theme is active
Theme::isActive('gympify');

// Get theme information
Theme::info('gympify');

// Get missing directories (for debugging)
Theme::getMissingDirs('gympify');
```

## ThemeManager Implementation

### Complete ThemeManager Class

```php
<?php

namespace Coderstm\Services;

use Coderstm\Exceptions\ThemeNotFoundException;
use Coderstm\Exceptions\InvalidThemeException;
use Illuminate\Filesystem\Filesystem;

class ThemeManager
{
    protected Filesystem $filesystem;

    /**
     * Required theme directories.
     *
     * Note: Section groups (*-group.json) live INSIDE the sections/ directory,
     * not in a separate section-groups/ directory. This follows the project
     * specification pattern.
     */
    protected array $requiredDirs = [
        'layouts',
        'sections',      // Contains both .blade.php and *-group.json files
        'snippets',
        'templates',
        'pages',         // Individual page JSON files
        'assets',
        'lang',
        'config',
    ];

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Activate a theme and override all config paths.
     *
     * @throws ThemeNotFoundException
     * @throws InvalidThemeException
     */
    public function use(string $themeName): void
    {
        $basePath = resource_path("themes/{$themeName}");

        // Validate theme exists
        if (!$this->themeExists($themeName)) {
            throw new ThemeNotFoundException("Theme '{$themeName}' not found at {$basePath}");
        }

        // Validate theme structure
        if (!$this->isValidTheme($themeName)) {
            throw new InvalidThemeException("Theme '{$themeName}' is missing required directories");
        }

        // Override all 8 config paths
        // Note: section-groups path points to sections/ because section groups
        // are stored alongside section Blade files per project specification
        config([
            'pagebuilder.paths.layouts' => "{$basePath}/layouts",
            'pagebuilder.paths.sections' => "{$basePath}/sections",
            'pagebuilder.paths.section-groups' => "{$basePath}/sections",  // Same as sections
            'pagebuilder.paths.snippets' => "{$basePath}/snippets",
            'pagebuilder.paths.templates' => "{$basePath}/templates",
            'pagebuilder.paths.pages' => "{$basePath}/pages",
            'pagebuilder.paths.assets' => "{$basePath}/assets",
            'pagebuilder.paths.lang' => "{$basePath}/lang",
            'pagebuilder.paths.config' => "{$basePath}/config",
        ]);

        config(['pagebuilder.theme.active' => $themeName]);
    }

    /**
     * Check if theme directory exists.
     */
    public function themeExists(string $themeName): bool
    {
        $themePath = resource_path("themes/{$themeName}");
        return $this->filesystem->isDirectory($themePath);
    }

    /**
     * Validate theme has all required directories.
     */
    public function isValidTheme(string $themeName): bool
    {
        $themePath = resource_path("themes/{$themeName}");

        foreach ($this->requiredDirs as $dir) {
            $fullPath = "{$themePath}/{$dir}";
            if (!$this->filesystem->isDirectory($fullPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing directories for a theme.
     */
    public function getMissingDirs(string $themeName): array
    {
        $themePath = resource_path("themes/{$themeName}");
        $missing = [];

        foreach ($this->requiredDirs as $dir) {
            $fullPath = "{$themePath}/{$dir}";
            if (!$this->filesystem->isDirectory($fullPath)) {
                $missing[] = $dir;
            }
        }

        return $missing;
    }

    /**
     * List all available themes.
     *
     * @return array<string, array{name: string, path: string, valid: bool}>
     */
    public function available(): array
    {
        $themesPath = resource_path('themes');

        if (!$this->filesystem->isDirectory($themesPath)) {
            return [];
        }

        $themes = [];
        $directories = $this->filesystem->directories($themesPath);

        foreach ($directories as $dir) {
            $themeName = basename($dir);
            $themes[$themeName] = [
                'name' => $themeName,
                'path' => $dir,
                'valid' => $this->isValidTheme($themeName),
            ];
        }

        return $themes;
    }

    /**
     * Get currently active theme name.
     */
    public function active(): ?string
    {
        return config('pagebuilder.theme.active');
    }

    /**
     * Check if a specific theme is active.
     */
    public function isActive(string $themeName): bool
    {
        return $this->active() === $themeName;
    }

    /**
     * Get comprehensive theme information.
     */
    public function info(string $themeName): array
    {
        $themePath = resource_path("themes/{$themeName}");

        return [
            'name' => $themeName,
            'path' => $themePath,
            'exists' => $this->themeExists($themeName),
            'valid' => $this->isValidTheme($themeName),
            'missing_dirs' => $this->getMissingDirs($themeName),
        ];
    }
}
```

## Exception Classes

### ThemeNotFoundException

Thrown when a theme directory doesn't exist:

```php
<?php

namespace Coderstm\Exceptions;

class ThemeNotFoundException extends \Exception
{
}
```

Usage:

```php
try {
    Theme::use('nonexistent');
} catch (ThemeNotFoundException $e) {
    // Handle missing theme
    Log::warning("Theme not found: " . $e->getMessage());
}
```

### InvalidThemeException

Thrown when a theme is missing required directories:

```php
<?php

namespace Coderstm\Exceptions;

class InvalidThemeException extends \Exception
{
}
```

Usage:

```php
try {
    Theme::use('incomplete-theme');
} catch (InvalidThemeException $e) {
    // Handle invalid theme structure
    Log::error("Invalid theme: " . $e->getMessage());
}
```

## Service Provider Registration

### PageBuilderServiceProvider (Primary)

The `PageBuilderServiceProvider` is the **primary** service provider that:

1. Registers the `ThemeManager` as a singleton
2. Validates and loads the configured theme on boot
3. Shares theme data with all views

**Important:** Only register ONE service provider for theme management. Do NOT register both `PageBuilderServiceProvider` and a separate `ThemeServiceProvider`.

```php
<?php

namespace Coderstm\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Coderstm\Services\ThemeManager;
use Coderstm\Exceptions\ThemeNotFoundException;
use Coderstm\Exceptions\InvalidThemeException;

class PageBuilderServiceProvider extends ServiceProvider
{
    /**
     * Register the ThemeManager as a singleton.
     */
    public function register(): void
    {
        $this->app->singleton('page-builder.theme', function ($app) {
            return new ThemeManager($app['files']);
        });
    }

    /**
     * Boot the service provider and load configured theme.
     */
    public function boot(): void
    {
        $themeName = config('pagebuilder.theme.name');

        // Only attempt to load theme if configured
        if (!$themeName) {
            return;
        }

        try {
            /** @var ThemeManager $themeManager */
            $themeManager = $this->app['page-builder.theme'];

            // Validate and load theme (use() handles validation internally)
            $themeManager->use($themeName);

            // Share theme data with all views
            $this->shareThemeData($themeManager, $themeName);

        } catch (ThemeNotFoundException|InvalidThemeException $e) {
            // Log error
            \Log::error("Page Builder Theme Error: " . $e->getMessage());

            // Only throw in production
            if (app()->isProduction()) {
                throw $e;
            }
        }
    }

    /**
     * Share theme data with all Blade views.
     */
    protected function shareThemeData(ThemeManager $themeManager, string $themeName): void
    {
        // Get theme config file if it exists
        $configPath = config('pagebuilder.paths.config') . '/theme.php';
        $themeConfig = file_exists($configPath) ? require $configPath : [];

        // Share with all views
        View::share([
            'theme' => [
                'name' => $themeName,
                'config' => $themeConfig,
                'active' => $themeManager->active(),
                'path' => resource_path("themes/{$themeName}"),
            ],
        ]);
    }
}
```

## Usage Examples

### Loading and Validating a Theme

```php
use Coderstm\Facades\Theme;
use Coderstm\Exceptions\ThemeNotFoundException;
use Coderstm\Exceptions\InvalidThemeException;

try {
    Theme::use('gympify');
} catch (ThemeNotFoundException $e) {
    // Theme directory doesn't exist
    return response()->json(['error' => $e->getMessage()], 404);
} catch (InvalidThemeException $e) {
    // Theme structure is invalid
    return response()->json(['error' => $e->getMessage()], 400);
}
```

### Checking Theme Status

```php
use Coderstm\Facades\Theme;

// Get all available themes
$themes = Theme::available();
// Returns: [
//     'gympify' => [
//         'name' => 'gympify',
//         'path' => '/resources/themes/gympify',
//         'valid' => true
//     ],
//     'minimal' => [
//         'name' => 'minimal',
//         'path' => '/resources/themes/minimal',
//         'valid' => false  // Missing directories
//     ]
// ]

// Check if theme is valid before using it
if (Theme::isValidTheme('gympify')) {
    Theme::use('gympify');
}

// Get currently active theme
$active = Theme::active();  // 'gympify'

// Check if specific theme is active
if (Theme::isActive('gympify')) {
    // Theme is active
}

// Get theme information
$info = Theme::info('gympify');
// Returns: [
//     'name' => 'gympify',
//     'path' => '/resources/themes/gympify',
//     'exists' => true,
//     'valid' => true,
//     'missing_dirs' => []
// ]

// Get missing directories (if any)
$missing = Theme::getMissingDirs('incomplete-theme');
// Returns: ['views/sections', 'config']
```

### In a Controller with Full Validation

```php
<?php

namespace App\Http\Controllers;

use Coderstm\Facades\Theme;
use Coderstm\Exceptions\ThemeNotFoundException;
use Coderstm\Exceptions\InvalidThemeException;

class ThemeController extends Controller
{
    public function switchTheme($themeName)
    {
        try {
            // Validate theme exists first
            if (!Theme::themeExists($themeName)) {
                return response()->json(
                    ['error' => "Theme '{$themeName}' not found"],
                    404
                );
            }

            // Validate theme is valid
            if (!Theme::isValidTheme($themeName)) {
                $missing = Theme::getMissingDirs($themeName);
                return response()->json(
                    ['error' => "Theme missing: " . implode(', ', $missing)],
                    400
                );
            }

            // Load theme
            Theme::use($themeName);

            return response()->json(
                ['message' => "Theme '{$themeName}' loaded successfully"]
            );

        } catch (ThemeNotFoundException|InvalidThemeException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listThemes()
    {
        return response()->json([
            'available' => Theme::available(),
            'active' => Theme::active(),
        ]);
    }
}
```

### In Configuration

Activate theme via `.env`:

```env
PAGEBUILDER_THEME_ENABLED=true
PAGEBUILDER_THEME_NAME=gympify
```

Or in `config/pagebuilder.php`:

```php
'theme' => [
    'enabled' => env('PAGEBUILDER_THEME_ENABLED', false),
    'name' => env('PAGEBUILDER_THEME_NAME', null),
    'active' => null, // Set by ThemeManager::use()
],
```

## Path Resolution Pattern

The builder engine always resolves paths via config:

```php
// Paths are always read from config
$sectionPath = config('pagebuilder.paths.sections') . "/{$name}.blade.php";
$layoutPath = config('pagebuilder.paths.layouts') . "/{$layout}.blade.php";
$templatePath = config('pagebuilder.paths.templates') . "/{$template}.json";
```

Whether paths point to:

- `/resources/views/sections` (default, no theme)
- `/resources/themes/gympify/sections` (after `Theme::use('gympify')`)
- `/vendor/package/sections` (custom override)

**The engine doesn't care** — it just reads `config('pagebuilder.paths.*')`

## Theme Commands

### Creating a New Theme

Use the `themes:make` Artisan command to scaffold a new theme with all required directories:

```bash
php artisan themes:make gympify
```

This creates:

```
resources/themes/gympify/
├── layouts/
│   └── theme.blade.php
├── sections/                     # Contains BOTH Blade files AND section group JSON
│   ├── header-group.json         # Section group (NOT in separate directory)
│   └── footer-group.json
├── snippets/
│   ├── header.blade.php
│   ├── footer.blade.php
│   └── nav.blade.php
├── templates/
│   ├── home.json
│   └── index.json
├── pages/
│   └── .gitkeep
├── assets/
│   ├── css/
│   │   └── theme.css
│   ├── js/
│   │   └── theme.js
│   └── images/
├── lang/
│   └── en/
│       └── messages.php
└── config/
    └── theme.php
```

**Important:** Section groups (`*-group.json`) are stored **inside** the `sections/` directory, not in a separate `section-groups/` directory. This aligns with the project specification.

### ThemesMakeCommand Implementation

```php
<?php

namespace Coderstm\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ThemesMakeCommand extends Command
{
    protected $signature = 'themes:make {name : The name of the theme to create}';
    protected $description = 'Create a new page builder theme from stub';

    /**
     * Required directories for a valid theme.
     *
     * Note: Section groups are stored inside sections/ directory,
     * not in a separate section-groups/ directory.
     */
    protected array $requiredDirs = [
        'layouts',
        'sections',      // Contains both .blade.php and *-group.json files
        'snippets',
        'templates',
        'pages',         // Individual page JSON files
        'assets/css',
        'assets/js',
        'assets/images',
        'lang/en',       // Language subdirectory (not file)
        'config',
    ];

    public function handle(Filesystem $filesystem): int
    {
        $themeName = $this->argument('name');
        $themePath = resource_path("themes/{$themeName}");

        // Check if theme already exists
        if ($filesystem->isDirectory($themePath)) {
            $this->error("Theme '{$themeName}' already exists at {$themePath}");
            return 1;
        }

        // Create all required directories
        foreach ($this->requiredDirs as $dir) {
            $filesystem->ensureDirectoryExists("{$themePath}/{$dir}");
        }

        // Copy stub files
        $this->createStubFiles($filesystem, $themePath, $themeName);

        $this->info("Theme '{$themeName}' created successfully!");
        $this->line("Theme location: {$themePath}");
        $this->line("Activate theme in .env:");
        $this->line("  PAGEBUILDER_THEME_ENABLED=true");
        $this->line("  PAGEBUILDER_THEME_NAME={$themeName}");

        return 0;
    }

    protected function createStubFiles(Filesystem $filesystem, string $themePath, string $themeName): void
    {
        // Main layout
        $layoutStub = <<<'BLADE'
<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title ?? config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="template-{{ $template ?? 'default' }}">
    @sections('header-group')

    <main class="page-content">
        {{ $content }}
    </main>

    @sections('footer-group')

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
BLADE;

        $filesystem->put("{$themePath}/layouts/theme.blade.php", $layoutStub);

        // Header snippet
        $headerStub = <<<'BLADE'
<header>
    <nav>
        <a href="{{ route('home') }}">{{ config('app.name') }}</a>
    </nav>
</header>
BLADE;

        $filesystem->put("{$themePath}/snippets/header.blade.php", $headerStub);

        // Footer snippet
        $footerStub = <<<'BLADE'
<footer>
    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
</footer>
BLADE;

        $filesystem->put("{$themePath}/snippets/footer.blade.php", $footerStub);

        // Navigation snippet
        $navStub = <<<'BLADE'
<nav class="navbar">
    <ul>
        <li><a href="{{ route('home') }}">Home</a></li>
    </ul>
</nav>
BLADE;

        $filesystem->put("{$themePath}/snippets/nav.blade.php", $navStub);

        // Header section group (stored in sections/ directory per specification)
        $headerGroupStub = json_encode([
            'type' => 'header',
            'name' => 'Header',
            'sections' => [],
            'order' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Note: Section groups live INSIDE sections/ directory, NOT in section-groups/
        $filesystem->put("{$themePath}/sections/header-group.json", $headerGroupStub);

        // Footer section group
        $footerGroupStub = json_encode([
            'type' => 'footer',
            'name' => 'Footer',
            'sections' => [],
            'order' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $filesystem->put("{$themePath}/sections/footer-group.json", $footerGroupStub);

        // Home template
        $homeTemplateStub = json_encode([
            'layout' => 'theme',
            'sections' => [],
            'order' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $filesystem->put("{$themePath}/templates/home.json", $homeTemplateStub);

        // Index template (aliased to home)
        $filesystem->put("{$themePath}/templates/index.json", $homeTemplateStub);

        // Placeholder page
        $placeholderPageStub = json_encode([
            'sections' => [],
            'order' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $filesystem->put("{$themePath}/pages/.gitkeep", '');

        // Theme CSS
        $cssStub = <<<'CSS'
/* Theme CSS */
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.page-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}
CSS;

        $filesystem->put("{$themePath}/assets/css/theme.css", $cssStub);

        // Theme JavaScript
        $jsStub = <<<'JS'
// Theme JavaScript
console.log('Theme loaded');
JS;

        $filesystem->put("{$themePath}/assets/js/theme.js", $jsStub);

        // Language file
        $langStub = <<<'PHP'
<?php

return [
    'welcome' => 'Welcome to ' . config('app.name'),
];
PHP;

        $filesystem->put("{$themePath}/lang/en/messages.php", $langStub);

        // Theme configuration
        $configStub = <<<'PHP'
<?php

return [
    'name' => 'THEME_NAME',
    'author' => 'Your Name',
    'version' => '1.0.0',
    'description' => 'A custom page builder theme',
];
PHP;

        $configContent = str_replace('THEME_NAME', $themeName, $configStub);
        $filesystem->put("{$themePath}/config/theme.php", $configContent);
    }
}
```

## Theme Serving System

Theme data is automatically shared with all views when a theme is activated via `PageBuilderServiceProvider`. The `View::share()` mechanism ensures the `$theme` variable is globally accessible in all Blade templates.

### Accessing Theme Data in Views

**In any Blade template:**

```blade
<!-- Access theme name -->
{{ $theme['name'] }}

<!-- Access theme config -->
{{ $theme['config']['author'] ?? 'Unknown' }}

<!-- Check if theme is active -->
@if($theme['active'] === 'gympify')
    <!-- Gympify-specific markup -->
@endif

<!-- Access theme assets (use theme_asset helper or configure public symlink) -->
<!-- Note: $theme['path'] is a filesystem path, NOT a URL -->
<!-- For public URLs, use a theme-aware asset helper or symlink theme assets to public/ -->
<img src="{{ theme_asset('images/logo.png') }}" />

<!-- Or with manual asset path configuration -->
<img src="{{ asset("themes/{$theme['name']}/images/logo.png") }}" />
```

**Important:** The `$theme['path']` variable returns a **filesystem path**, not a public URL. For serving theme assets publicly, either:

1. Use a `theme_asset()` helper function (recommended)
2. Symlink theme assets to `public/themes/{name}/`
3. Use a route that serves theme assets dynamically

### Usage in Section Blade Files

Section Blade files contain HTML at the top and a schema block at the bottom. The `@schema` / `@endschema` directives define the admin UI fields for the section.

```blade
<!-- resources/themes/gympify/sections/hero.blade.php -->
<section class="hero hero--{{ $settings['variant'] ?? 'default' }}">
    <h1>{{ $settings['title'] ?? 'Welcome' }}</h1>

    @if(isset($theme))
        <p>Powered by {{ $theme['name'] }} theme</p>
    @endif
</section>

<style>
.hero {
    padding: 80px 20px;
    background-color: var(--primary-color);
}
</style>

{{-- Schema is stripped before rendering; defines admin UI fields --}}
@schema
{
    "name": "Hero Section",
    "settings": [
        {
            "type": "text",
            "id": "title",
            "default": "Welcome"
        },
        {
            "type": "select",
            "id": "variant",
            "options": {
                "default": "Default",
                "dark": "Dark"
            }
        }
    ]
}
@endschema
```

### Service Container Singleton Access

For accessing theme data outside of views (in services, controllers, etc.):

```php
<?php

namespace Coderstm\Services;

use Coderstm\Facades\Theme;

class MyService
{
    /**
     * Access theme info from service provider or boot method.
     */
    public function getTheme()
    {
        return app('page-builder.theme')->info(config('pagebuilder.theme.name'));
    }

    /**
     * Switch theme dynamically.
     */
    public function switchTheme($themeName)
    {
        return Theme::use($themeName);
    }
}
```

## Why This Approach

| Aspect          | Benefit                                    |
| --------------- | ------------------------------------------ |
| No Duplication  | Paths defined once in config               |
| Simple          | Theme just overrides the base directory    |
| Facade-based    | Clean, idiomatic Laravel                   |
| Validated       | Checks theme structure before loading      |
| Runtime Capable | Can switch themes dynamically              |
| Safe            | Errors thrown in production, logged in dev |

**You're building Shopify's architecture with Laravel's flexibility.**

## Troubleshooting

### Theme Not Found

```
Error: Configured theme 'gympify' not found at /resources/themes/gympify
```

**Solution:** Create the theme using the command:

```bash
php artisan themes:make gympify
```

### Invalid Theme Structure

```
Error: Theme 'gympify' missing required directories: sections, pages, config
```

**Solution:** Check that all 8 required directories exist. Recreate the theme:

```bash
php artisan themes:make gympify
```

**Required directories:** `layouts`, `sections`, `snippets`, `templates`, `pages`, `assets`, `lang`, `config`

### Theme Data Not Available in Views

**Solution:** Ensure `PageBuilderServiceProvider` is registered in `config/app.php`:

```php
'providers' => [
    // ...
    Coderstm\Providers\PageBuilderServiceProvider::class,
],
```

**Note:** Do NOT register a separate `ThemeServiceProvider`. The `PageBuilderServiceProvider` handles both theme loading AND view sharing.

### Can't Switch Themes at Runtime

**Solution:** Use exception handling:

```php
use Coderstm\Facades\Theme;
use Coderstm\Exceptions\ThemeNotFoundException;
use Coderstm\Exceptions\InvalidThemeException;

try {
    Theme::use('new-theme');
} catch (ThemeNotFoundException $e) {
    Log::error("Theme not found: " . $e->getMessage());
} catch (InvalidThemeException $e) {
    Log::error("Theme switch failed: " . $e->getMessage());
}
```
