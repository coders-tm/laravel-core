# Laravel Section-Based Page Builder (LSB) — Project Specification

## 1. Product Overview

- **Name:** Laravel Section Builder (LSB)
- **Purpose:** Schema-driven, file-based page composition for Laravel. Non-technical users build pages using predefined sections/layouts, never touching HTML/CSS/JS.
- **Audience:** Laravel SaaS builders, CMS/admin developers, CodeCanyon customers, agencies.

## 2. Core Philosophy

- Developers write HTML; users only modify data.
- Pages are composed, not designed.
- Layouts define editable areas; schema defines UI.
- Filesystem is the source of truth.
- Themes are optional; Blade remains standard.

## 3. What This System Is / Is Not

- ✔ Section-based, Shopify-like, WPBakery-style, file-driven, Laravel-native.
- ❌ Not a WYSIWYG, CSS playground, GrapesJS, CMS, or code editor.

## 4. High-Level Architecture

- Admin UI (Vue/React) → JSON (schema-driven)
- Builder Core (Laravel Package)
- Filesystem (configurable paths): layouts, sections, templates (JSON), snippets, assets, locales

## 5. Filesystem Architecture

### 5.1 Default (No Theme Mode)

```
resources/
├── views/
│   ├── layouts/              # Page wrappers (theme.blade.php, blog.blade.php, etc.)
│   ├── sections/             # Individual section Blade files
│   ├── section-groups/       # Section group JSON files (NEW)
│   ├── snippets/             # Reusable partial components
│   └── templates/            # Template JSON files
├── assets/                   # CSS, JS, images
├── config/
│   └── builder/              # Builder-specific configs
└── lang/                     # Localization files
```

### 5.2 Optional Theme Mode

```
resources/themes/{theme-name}/
├── layouts/                  # Theme-specific layouts
├── sections/                 # Theme-specific sections
├── section-groups/           # Theme-specific section groups
├── snippets/                 # Theme-specific snippets
├── templates/                # Theme-specific templates
├── assets/                   # Theme assets (CSS, JS, images)
├── config/                   # Theme-specific configs
└── lang/                     # Theme-specific localization
```

### 5.3 Key Points

- ✔ All paths are **configurable** via `config/pagebuilder.php`
- ✔ Section groups stored **separately** from sections for clarity
- ✔ Theme mode **completely optional** — defaults to resources/
- ✔ Multi-tenant ready — each tenant can have isolated paths

## 6. Configuration

### 6.1 Central Config File

Create `config/pagebuilder.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Paths
    |--------------------------------------------------------------------------
    | Configure where builder files are located. All paths can be customized
    | for multi-tenant or theme-based applications.
    */
    'paths' => [
        // Layout Blade files (wrappers for pages)
        'layouts' => resource_path('views/layouts'),

        // Individual section Blade files
        'sections' => resource_path('views/sections'),

        // Section group JSON files
        'section-groups' => resource_path('views/section-groups'),

        // Reusable snippet Blade files
        'snippets' => resource_path('views/snippets'),

        // Page template JSON files
        'templates' => resource_path('views/templates'),

        // Asset files (CSS, JS, images)
        'assets' => resource_path('assets'),

        // Builder-specific config files
        'config' => resource_path('config/builder'),

        // Localization files
        'lang' => resource_path('lang'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme System
    |--------------------------------------------------------------------------
    | Enable optional theme mode. When enabled, paths are resolved from
    | resources/themes/{theme-name}/ instead of resources/.
    */
    'theme' => [
        'enabled' => env('PAGEBUILDER_THEME_ENABLED', false),
        'name' => env('PAGEBUILDER_THEME_NAME', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    | Cache section schemas and templates for performance.
    */
    'cache' => [
        'enabled' => env('PAGEBUILDER_CACHE_ENABLED', true),
        'ttl' => env('PAGEBUILDER_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    | Security settings for the page builder.
    */
    'security' => [
        'read_only' => env('PAGEBUILDER_READ_ONLY', false),
        'allowed_blade_tags' => [
            '@snippet', '@sections', '@include', '@foreach',
            '@if', '@unless', '@switch', '@for', '@while'
        ],
    ],
];
```

### 6.2 Environment Variables

Add to `.env`:

```env
# Page Builder Configuration
PAGEBUILDER_THEME_ENABLED=false
PAGEBUILDER_THEME_NAME=default

# Caching
PAGEBUILDER_CACHE_ENABLED=true
PAGEBUILDER_CACHE_TTL=3600

# Security
PAGEBUILDER_READ_ONLY=false
```

### 6.3 Runtime Path Override (Multi-Tenant)

For multi-tenant applications, override paths at runtime:

```php
// In middleware or service provider
config([
    'pagebuilder.paths.layouts' => tenant_path('views/layouts'),
    'pagebuilder.paths.sections' => tenant_path('views/sections'),
    'pagebuilder.paths.section-groups' => tenant_path('views/section-groups'),
    'pagebuilder.paths.snippets' => tenant_path('views/snippets'),
    'pagebuilder.paths.templates' => tenant_path('views/templates'),
    'pagebuilder.paths.assets' => tenant_path('assets'),
]);
```

### 6.4 Publishing Config

Publish the config file:

```bash
php artisan vendor:publish --provider="Coderstm\\PageBuilder\\PageBuilderServiceProvider" --tag=config
```

**Benefits:**

- ✔ **Single source of truth** — All paths in one place
- ✔ **Easily overrideable** — Change paths per request
- ✔ **Multi-tenant ready** — Tenant isolation via runtime config
- ✔ **Theme support** — Optional theme mode
- ✔ **Caching** — Configurable cache strategy
- ✔ **Security** — Allowed Blade tags whitelist

## 7. Layout System

- Layouts in `layouts/`, extended via `@layout()`.
- Layouts define header/footer, fixed UI, and content slots.
- **Detailed specification:** See [Layout Specification](./layout-specification.md)

## 8. Templates

Templates are JSON data files that compose pages from sections. They define which sections appear, their order, and their configuration—all without any HTML or Blade code.

**Key Features:**

- ✔ **Data-only files** — No HTML or code, just JSON
- ✔ **Section-based** — Reference sections defined elsewhere
- ✔ **Merchant-editable** — Sections can be added, removed, reordered in admin UI
- ✔ **Multiple types** — Homepage, product, collection, custom pages
- ✔ **Alternate templates** — Multiple variants of same type (e.g., product.json, product.video.json)
- ✔ **Layout-aware** — Specify which layout wraps the sections
- ✔ **Limits** — Max 25 sections per template, 50 blocks per section
- ✔ **Shopify-aligned** — Architecture matches Shopify's JSON template system

**Location:** `resources/views/templates/` (or theme-specific path when using themes)

**Example:**

```json
{
    "layout": "theme",
    "wrapper": "main",
    "sections": {
        "hero": {
            "type": "hero",
            "settings": {
                "title": "Welcome"
            }
        },
        "featured": {
            "type": "featured-products",
            "settings": {
                "count": 4
            }
        }
    },
    "order": ["hero", "featured"]
}
```

**📖 Complete specification:** See [Templates Specification](./templates-specification.md)

The comprehensive templates document includes template types, schema reference, validation rules, API endpoints, rendering pipeline, and best practices.

## 8.5. Pages Editor

Beyond reusable templates, LSB provides **individual page editing** for site-specific pages (about us, contact, landing pages, etc.). Pages use the same section-based structure as templates but store page-specific configurations.

**Key Concepts:**

- ✔ **Individual pages** — Each page has its own `{page-slug}.json` in `pages/` directory
- ✔ **Fully editable** — JSON pages can be edited in the pages editor
- ✔ **Blade override** — Creating `{page-slug}.blade.php` locks page from editing (preview-only)
- ✔ **Unlock by deletion** — Delete the `.blade.php` file to make page editable again
- ✔ **Theme support** — Pages load/store from theme when theme mode is enabled

**Example Page:**

```json
{
    "sections": {
        "hero": {
            "type": "page-hero",
            "settings": {
                "title": "About Our Company"
            }
        },
        "team": {
            "type": "team-members",
            "settings": {
                "count": 6
            }
        }
    },
    "order": ["hero", "team"]
}
```

**Making Pages Editable/Locked:**

```bash
# Page is EDITABLE (JSON only)
resources/views/pages/about-us.json

# Page is LOCKED (Blade overrides JSON)
resources/views/pages/about-us.blade.php

# To UNLOCK: Delete the Blade file
rm resources/views/pages/about-us.blade.php
# Now JSON is used again; page becomes editable
```

**📖 Complete specification:** See [Pages Editor Specification](./pages-editor-specification.md)

The comprehensive pages editor document includes filesystem structure, JSON schema, Blade overrides, rendering pipeline, API endpoints, caching, and complete examples.

## 9. Section System

- One section = one Blade file: HTML at top, schema at bottom, optional inline CSS/JS.
- Schema is never rendered.

## 9.5. Section Groups (Shopify-Style)

### Purpose

Section groups are JSON data files that bundle collections of sections together, allowing merchants to manage multiple sections in layout areas (header, footer, sidebar) without touching code.

### Key Differences: Sections vs Section Groups

- **Sections:** Individual, reusable Blade files; referenced in templates or within section groups.
- **Section Groups:** JSON files that contain multiple sections with their settings and order; referenced in layouts via `@sections()`.

### Section Group Structure

```json
{
    "type": "header",
    "name": "Header Group",
    "sections": {
        "section-id-1": {
            "type": "header",
            "disabled": false,
            "settings": {
                "logo_width": 90
            }
        },
        "section-id-2": {
            "type": "announcement-bar",
            "settings": {
                "text": "Free shipping!"
            }
        }
    },
    "order": ["section-id-1", "section-id-2"]
}
```

### Section Group Limits

- Max 25 sections per group
- Max 50 blocks per section
- Unique IDs within the group
- Can render up to 25 sections

### Section Group Types

- `header` — For header area
- `footer` — For footer area
- `aside` — For sidebar area
- `custom.<name>` — Custom group types (e.g., `custom.blog-sidebar`)

### When to Use Section Groups

- ✔ Layout-controlled areas (header, footer, sidebars)
- ✔ Areas where merchants add/remove/reorder sections
- ✔ Reusable section bundles across multiple pages
- ❌ Not for template body content (use templates instead)

### Contextual Section Groups

Section groups can be contextually overridden for specific scenarios. Additional context files follow the pattern:

```
header-group.json               // Base section group
header-group.custom-name.json   // Custom context override
```

The context file can override or add to sections without modifying the base file.

### Rendering Behavior

- Only **JSON is loaded and parsed** from section group files
- Section Blade files are loaded dynamically based on section `type` references
- No Blade template code is executed from the section group JSON itself
- Blade files render with settings from the section group data

### Filesystem Location

Section groups live in `sections/` directory alongside individual sections:

```
resources/views/sections/
├── header.blade.php
├── header-group.json
├── footer.blade.php
├── footer-group.json
└── announcement-bar.blade.php
```

## 11. Schema System

- Schemas define admin UI fields, allowed config, validation.
- Supported fields: text, textarea, select, checkbox, image, repeater, color (palette only).
- No raw HTML/CSS/JS injection.

## 12. Blocks

- Repeatable child elements inside a section, defined in schema and rendered in Blade.

## 13. Rendering Pipeline

- Resolve template JSON → layout → loop sections → load Blade → strip schema → render with settings/blocks → inject into layout.
- Public API: `PageBuilder::render('home')`

## 14. Blade Directives

- `@snippet('header', [...])` → includes snippet, config-aware.
- `@layout('full-width')` → extends layout, Shopify-style.
- `@sections('header-group')` → renders section group JSON (loads JSON only; Blade files rendered dynamically by section type).

## 15. Admin Builder UI

- Left: Sections/Blocks; Right: Live preview; Settings: Schema-driven form.
- Users can add/remove/reorder sections, change content, switch layout.
- Users cannot edit HTML/CSS/JS.

## 16. Storage Strategy

- DB stores only: `page_slug`, `template_name`.
- Templates, sections, assets, config: filesystem.

## 17. File Storage Abstraction

### 17.1 Critical Design Rule

❌ **NEVER** let the editor write directly to `/resources/views`

✅ **ALWAYS** go through a file-writer abstraction interface

### 17.2 Why This Matters

- **Permissions** — Control who can write where
- **Multi-tenant safety** — Tenant isolation via disk configuration
- **CI/CD compatibility** — Immutable deployments possible
- **Future flexibility** — Support any storage backend

### 17.3 BuilderFileStore Interface

```php
<?php

namespace Coderstm\Contracts;

interface BuilderFileStore
{
    /**
     * Read file contents.
     *
     * @param string $path Relative path from configured base
     * @return string File contents
     * @throws FileNotFoundException
     */
    public function read(string $path): string;

    /**
     * Write file contents.
     *
     * @param string $path Relative path from configured base
     * @param string $content File contents to write
     * @return bool Success
     * @throws WriteException
     */
    public function write(string $path, string $content): bool;

    /**
     * Delete a file.
     *
     * @param string $path Relative path from configured base
     * @return bool Success
     */
    public function delete(string $path): bool;

    /**
     * Check if file exists.
     *
     * @param string $path Relative path from configured base
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * Get all files in a directory.
     *
     * @param string $directory Relative directory path
     * @return array List of relative file paths
     */
    public function listDirectory(string $directory): array;
}
```

### 17.4 Implementation: Local Filesystem

```php
<?php

namespace Coderstm\Services\FileStore;

use Coderstm\Contracts\BuilderFileStore;
use Illuminate\Filesystem\Filesystem;

class LocalFileStore implements BuilderFileStore
{
    public function __construct(
        protected Filesystem $filesystem,
        protected string $basePath
    ) {}

    public function read(string $path): string
    {
        $fullPath = $this->resolvePath($path);

        if (!$this->filesystem->exists($fullPath)) {
            throw new FileNotFoundException("File not found: {$path}");
        }

        return $this->filesystem->get($fullPath);
    }

    public function write(string $path, string $content): bool
    {
        $fullPath = $this->resolvePath($path);
        $directory = dirname($fullPath);

        // Create directory if needed
        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }

        return (bool) $this->filesystem->put($fullPath, $content);
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->resolvePath($path);

        if ($this->filesystem->exists($fullPath)) {
            return $this->filesystem->delete($fullPath);
        }

        return false;
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->exists($this->resolvePath($path));
    }

    public function listDirectory(string $directory): array
    {
        $fullPath = $this->resolvePath($directory);

        if (!$this->filesystem->isDirectory($fullPath)) {
            return [];
        }

        return $this->filesystem->files($fullPath);
    }

    protected function resolvePath(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }
}
```

### 17.5 Service Provider Registration

```php
<?php

namespace Coderstm\Providers;

use Illuminate\Support\ServiceProvider;
use Coderstm\Contracts\BuilderFileStore;
use Coderstm\Services\FileStore\LocalFileStore;
use Coderstm\Facades\Theme;

class PageBuilderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(BuilderFileStore::class, function ($app) {
            return new LocalFileStore(
                $app['files'],
                resource_path('views')
            );
        });

        // Register PageBuilder facade
        $this->app->singleton('page-builder', function ($app) {
            return new PageBuilder($app[BuilderFileStore::class]);
        });
    }
}
```

### 17.6 Usage in Editor

```php
<?php

namespace Coderstm\Http\Controllers;

use Coderstm\Contracts\BuilderFileStore;

class SectionController extends Controller
{
    public function store(Request $request, BuilderFileStore $fileStore)
    {
        $path = 'sections/' . $request->input('name') . '.blade.php';
        $content = $request->input('content');

        // Always go through abstraction
        $fileStore->write($path, $content);

        return response()->json(['message' => 'Section saved']);
    }
}
```

**MVP Benefits:**

- ✔ **Testable** — Mock file store in tests
- ✔ **Secure** — Permissions enforced at filesystem level
- ✔ **Simple** — Local filesystem, no external dependencies
- ✔ **Future-proof** — Abstraction ready for S3, Git, Database later

**Future Extensions (Post-MVP):**

- Cloud storage (S3, Azure, GCS)
- Version control (Git repository)
- Database storage
- Multi-disk strategies

## 18. Theme System

Theme support is **completely optional** and fully decoupled from the core builder. Themes provide a way to bundle sections, layouts, snippets, and assets together for easy distribution and switching.

**Key Features:**

- ✔ Completely optional — pages work fine without themes
- ✔ Simple facade pattern with `Theme::use()`
- ✔ Full validation before activation
- ✔ Runtime theme switching
- ✔ View::share() for global theme context access
- ✔ Artisan command for scaffolding new themes
- ✔ Service provider handles automatic loading

### Quick Start

**Create a new theme:**

```bash
php artisan themes:make gympify
```

**Activate in .env:**

```env
PAGEBUILDER_THEME_ENABLED=true
PAGEBUILDER_THEME_NAME=gympify
```

**Use in code:**

```php
use Coderstm\Facades\Theme;

// Load theme
Theme::use('gympify');

// Check status
Theme::isActive('gympify');
Theme::available();

// Access in views
{{ $theme['name'] }}
{{ $theme['config']['author'] }}
```

### Complete Documentation

**📖 See dedicated specification:** [Theme System Specification](./theme-specification.md)

The comprehensive theme specification document includes:

- ✅ Theme structure and directory requirements
- ✅ Facade pattern and complete usage examples
- ✅ ThemeManager class implementation (8 methods)
- ✅ Exception classes (ThemeNotFoundException, InvalidThemeException)
- ✅ Service provider registration and boot process
- ✅ Artisan command for theme scaffolding (`themes:make`)
- ✅ Theme serving with View::share() integration
- ✅ Access theme data in Blade templates
- ✅ Access theme data from services/controllers
- ✅ Troubleshooting guide
- ✅ Comparison with Shopify architecture

## 18.5 PageBuilder Facade

The `PageBuilder` facade provides convenient helper methods for accessing page builder functionality with automatic theme-aware path resolution.

### PageBuilder Facade

```php
<?php

namespace Coderstm\Facades;

use Illuminate\Support\Facades\Facade;

class PageBuilder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'page-builder';
    }
}
```

### PageBuilder Helper Class

```php
<?php

namespace Coderstm\Services;

use Coderstm\Contracts\BuilderFileStore;

class PageBuilder
{
    public function __construct(
        protected BuilderFileStore $fileStore
    ) {}

    /**
     * Get the file store instance.
     */
    public function files(): BuilderFileStore
    {
        return $this->fileStore;
    }

    /**
     * Read a section file.
     */
    public function readSection(string $name): string
    {
        return $this->fileStore->read("sections/{$name}.blade.php");
    }

    /**
     * Write a section file.
     */
    public function writeSection(string $name, string $content): bool
    {
        return $this->fileStore->write("sections/{$name}.blade.php", $content);
    }

    /**
     * Read a template file.
     */
    public function readTemplate(string $name): string
    {
        return $this->fileStore->read("templates/{$name}.json");
    }

    /**
     * Write a template file.
     */
    public function writeTemplate(string $name, string $content): bool
    {
        return $this->fileStore->write("templates/{$name}.json", $content);
    }

    /**
     * Read a layout file.
     */
    public function readLayout(string $name): string
    {
        return $this->fileStore->read("layouts/{$name}.blade.php");
    }

    /**
     * Write a layout file.
     */
    public function writeLayout(string $name, string $content): bool
    {
        return $this->fileStore->write("layouts/{$name}.blade.php", $content);
    }

    /**
     * List all sections.
     */
    public function listSections(): array
    {
        return $this->fileStore->listDirectory('sections');
    }

    /**
     * List all templates.
     */
    public function listTemplates(): array
    {
        return $this->fileStore->listDirectory('templates');
    }

    /**
     * Check if section exists.
     */
    public function sectionExists(string $name): bool
    {
        return $this->fileStore->exists("sections/{$name}.blade.php");
    }

    /**
     * Check if template exists.
     */
    public function templateExists(string $name): bool
    {
        return $this->fileStore->exists("templates/{$name}.json");
    }
}
```

### Usage Examples

**In Controllers:**

```php
use Coderstm\Facades\PageBuilder;

// Read section
$sectionContent = PageBuilder::readSection('hero');

// Write section
PageBuilder::writeSection('hero', $bladeContent);

// List all templates
$templates = PageBuilder::listTemplates();

// Check if template exists
if (PageBuilder::templateExists('home')) {
    $home = PageBuilder::readTemplate('home');
}

// Access file store directly for advanced operations
PageBuilder::files()->write('custom/file.blade.php', $content);
```

**In Services:**

```php
use Coderstm\Facades\PageBuilder;

class SectionService
{
    public function create(string $name, array $schema): bool
    {
        $blade = $this->generateBladeFromSchema($schema);
        return PageBuilder::writeSection($name, $blade);
    }

    public function delete(string $name): bool
    {
        if (!PageBuilder::sectionExists($name)) {
            throw new SectionNotFoundException("Section '{$name}' not found");
        }

        return PageBuilder::files()->delete("sections/{$name}.blade.php");
    }
}
```

**In API Endpoints:**

```php
use Coderstm\Facades\PageBuilder;

class TemplateController extends Controller
{
    public function show($template)
    {
        if (!PageBuilder::templateExists($template)) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $content = PageBuilder::readTemplate($template);

        return response()->json([
            'name' => $template,
            'content' => json_decode($content, true),
        ]);
    }

    public function update($template, Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|json',
        ]);

        $success = PageBuilder::writeTemplate(
            $template,
            json_encode($validated['content'], JSON_PRETTY_PRINT)
        );

        return response()->json([
            'message' => $success ? 'Template updated' : 'Failed to update template',
            'success' => $success,
        ]);
    }
}
```

### Theme-Aware Path Resolution

The `PageBuilder` facade automatically handles theme-aware path resolution through the `BuilderFileStore`:

**When theme is ENABLED:**

```php
// Files read from: resources/themes/{theme-name}/
PageBuilder::readSection('hero');
// Reads: resources/themes/gympify/sections/hero.blade.php
```

**When theme is DISABLED:**

```php
// Files read from: resources/views/
PageBuilder::readSection('hero');
// Reads: resources/views/sections/hero.blade.php
```

The service provider automatically detects the theme configuration and adjusts the base path accordingly.

## 19. Security

- No user-supplied code execution; schema parsing only; controlled Blade rendering; optional read-only mode.

## 20. Performance

- Cache section schema, template JSON, compiled Blade; optional asset bundling.

## 21. Extensibility

- Add new sections/layouts/snippets, override paths, register addons—no core code changes.

## 22. Multi-Tenant Ready

- Isolated paths per tenant, configurable per request, no shared mutable state.

## 23. CodeCanyon Readiness

- Laravel-native, no heavy JS, no SaaS dependency, clear separation, highly documented, enterprise-grade.

## 24. Final Positioning

> Shopify-style section engine for Laravel, WPBakery-level usability, superior architecture.  
> Not a page builder—a composition engine.

---

This spec is your single source of truth for building the Laravel Section-Based Page Builder.
