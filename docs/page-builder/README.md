# Page Builder Documentation

## Overview

This directory contains the complete specification for the **Laravel Section-Based Page Builder (LSB)** — a Shopify-style section engine for Laravel with WPBakery-level usability.

## Documentation Files

### 1. **project-specification.md** (Main Entry Point)

**Purpose:** Single source of truth for the entire page builder architecture.

**Contents:**

- Product overview and core philosophy
- High-level architecture
- Filesystem architecture (default and theme modes)
- Central configuration system
- Layout system overview (with link to detailed spec)
- Templates, sections, section groups
- Schema system, blocks, rendering pipeline
- Blade directives
- Admin UI specifications
- Storage strategy
- File storage abstraction (interface & MVP implementation)
- Theme system overview (with link to dedicated spec)
- Security, performance, extensibility considerations
- Multi-tenant readiness
- CodeCanyon readiness
- Final positioning

**When to read:** Start here to understand the complete LSB architecture.

**Size:** ~18 KB | 24 sections

---

### 2. **theme-specification.md** (Theme Deep Dive)

**Purpose:** Complete guide to the optional theme system.

**Contents:**

- Theme system overview
- Theme directory structure and validation
- Theme facade pattern
- ThemeManager class (complete implementation with 8 methods)
- Exception classes (ThemeNotFoundException, InvalidThemeException)
- Service provider registration
- Usage examples (loading, validating, checking status)
- Controller integration examples
- Path resolution pattern
- Theme commands (`themes:make` command)
- ThemesMakeCommand implementation with stub generation
- Theme serving system with View::share()
- Accessing theme data in views
- Accessing theme data from services/controllers
- Troubleshooting guide
- Comparison with Shopify

**When to read:** After understanding the main LSB architecture; essential for implementing theme support.

**Size:** ~26 KB | 15 sections

**Key Classes Documented:**

- `ThemeManager` (8 methods)
- `ThemesMakeCommand` (Artisan command)
- `Theme` Facade
- Service Providers (PageBuilderServiceProvider, ThemeServiceProvider)
- Exception classes

---

### 3. **templates-specification.md** (Templates Deep Dive)

**Purpose:** Complete guide to the template system — the data layer of LSB.

**Contents:**

- Template overview and use cases
- Template types (content, e-commerce, utility, custom)
- File location and naming conventions
- JSON template schema (layout, wrapper, sections, order)
- Section data format and attributes
- Block attributes and structure
- Wrapper syntax (div, main, section with IDs/classes)
- Layout configuration (default, custom, false)
- Complete example templates (homepage, product, collection, etc.)
- Alternate templates (multiple versions of same type)
- Template rendering pipeline
- Template context and data access
- Validation rules and implementation
- Template loading and parsing services
- Template caching strategy
- Theme editor integration
- RESTful template API endpoints
- Best practices
- Troubleshooting guide

**When to read:** Before implementing the template system; essential for understanding page composition.

**Size:** ~25 KB | 21 sections

**Key Reference:** Aligned with [Shopify Template Architecture](https://shopify.dev/docs/storefronts/themes/architecture/templates)

---

### 4. **pages-editor-specification.md** (Page-Specific Editing)

**Purpose:** Complete guide to individual page editing with template inheritance.

**Contents:**

- Overview of pages editor system
- Page vs template comparison
- Filesystem architecture (pages directory)
- Page JSON structure and schema
- Blade page override (locking pages)
- Making pages editable/locked
- Page loading logic and resolution order
- PageLoader service implementation
- Page rendering pipeline
- Settings merge strategy (page overrides template)
- Pages editor API (REST endpoints)
- API response examples
- Page validation rules
- Pages editor UI components
- Theme integration and fallback strategy
- Page-specific features (duplicate, lock, unlock)
- Page caching strategy
- Best practices
- Complete about page example
- Managing page overrides workflow
- CLI commands (optional)
- Troubleshooting guide

**When to read:** After understanding templates; essential for site-specific page management.

**Size:** ~32 KB | 19 sections

**Key Features:**

- 📄 JSON pages are fully editable via pages editor
- 🔒 Blade pages are preview-only (locked)
- 🎯 Delete `.blade.php` to unlock page for editing
- 🎨 Template-based defaults with page-level overrides
- ♻️ Duplicate pages for faster creation
- 🧪 Full validation before saving

---

### 5. **layout-specification.md** (Layouts in Detail)

**Purpose:** Dedicated specification for the layout system.

**Contents:**

- Layout system overview
- Layout structure and anatomy
- Creating layouts
- Using directives in layouts
- Template vs layout
- Snippets vs layouts
- Layouts with query strings
- Layouts with asset loading
- Nested layouts
- Default layouts
- Layout inheritance
- Layout blocks and content areas
- Best practices
- Complete code examples

**When to read:** When implementing or customizing the layout system.

**Size:** ~11 KB | 18 sections

---

## Quick Navigation

### I Want to...

#### **Understand the overall architecture**

→ Read [project-specification.md](./project-specification.md) sections 1-6

#### **Build templates**

→ Read [templates-specification.md](./templates-specification.md) sections 1-8

#### **Build pages with editor**

→ Read [pages-editor-specification.md](./pages-editor-specification.md) sections 1-8

#### **Build sections**

→ Read [project-specification.md](./project-specification.md) section 9

#### **Build layouts**

→ Read [layout-specification.md](./layout-specification.md) sections 1-5

#### **Implement file storage**

→ Read [project-specification.md](./project-specification.md) section 17

#### **Implement theme support**

→ Read [theme-specification.md](./theme-specification.md) sections 1-8

#### **Add theme support**

→ Read [theme-specification.md](./theme-specification.md) in full

#### **Create new themes**

→ Read [theme-specification.md](./theme-specification.md) section "Theme Commands"

#### **Lock/unlock pages**

→ Read [pages-editor-specification.md](./pages-editor-specification.md) sections 4-5

#### **Understand rendering pipeline**

→ Read [project-specification.md](./project-specification.md) section 13

#### **Learn about section groups**

→ Read [project-specification.md](./project-specification.md) section 9.5

#### **Implement multi-tenant support**

→ Read [project-specification.md](./project-specification.md) section 6.3

#### **Set up security**

→ Read [project-specification.md](./project-specification.md) section 19

---

## File Structure

These specifications document the following project structure:

```
resources/
├── views/
│   ├── layouts/              # Page wrappers
│   ├── sections/             # Section Blade files
│   ├── section-groups/       # Section group JSON files
│   ├── snippets/             # Reusable partials
│   └── templates/            # Template JSON files
├── assets/                   # CSS, JS, images
├── config/
│   └── builder/              # Builder configs
└── lang/                      # Localization

resources/themes/{name}/
├── views/
│   ├── layouts/
│   ├── sections/
│   ├── section-groups/
│   ├── snippets/
│   └── templates/
├── assets/
├── config/
└── lang/
```

---

## Key Concepts

### Sections

Individual Blade files that render UI based on schema-defined settings. Cannot be WYSIWYG edited.

### Section Groups

JSON files that bundle multiple sections together for layout areas (header, footer, sidebar).

### Layouts

Blade wrappers that define page structure (header, footer, content areas). Extended via `@layout()`.

### Templates

JSON files that define page composition: which layout, which sections, section order, and section settings.

### Schema

Field definitions within sections (text, select, checkbox, image, etc.) that drive the admin UI. Never rendered.

### Themes

Optional collections of sections, layouts, snippets, and assets bundled together. Can be created, distributed, and switched.

---

## Quick Start Commands

```bash
# Create a new theme
php artisan themes:make myTheme

# Publish configuration
php artisan vendor:publish --tag=pagebuilder-config

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Configuration

Key environment variables:

```env
# Theme system
PAGEBUILDER_THEME_ENABLED=false
PAGEBUILDER_THEME_NAME=default

# Caching
PAGEBUILDER_CACHE_ENABLED=true
PAGEBUILDER_CACHE_TTL=3600

# Security
PAGEBUILDER_READ_ONLY=false
```

---

## Architecture Principles

- **Developer-centric:** Developers write HTML; users only modify data
- **Schema-driven:** Admin UI generated from section schemas
- **File-based:** Filesystem is source of truth
- **Composer pattern:** Compose pages from predefined sections
- **Not WYSIWYG:** No CSS playground, no code editing UI
- **Shopify-aligned:** Borrowing battle-tested concepts from Shopify's architecture
- **Laravel-native:** Uses Blade, config system, service providers
- **Extensible:** Abstract interfaces for storage, new sections/layouts easily added
- **Multi-tenant ready:** Configurable paths, runtime overrides possible

---

## Design Decisions

### Why Separate Specifications?

- **Modularity:** Each document focuses on one area
- **Maintainability:** Changes to themes don't require touching main spec
- **Clarity:** Readers can go deep on what they need
- **Navigation:** Quick links between related docs

### Why Three Files?

1. **project-specification.md** — Overview + core architecture
2. **theme-specification.md** — Complete theme system (optional but important)
3. **layout-specification.md** — Layout system deep dive

This structure allows readers to understand the entire system or zoom into specific areas.

---

## Implementation Checklist

When implementing LSB:

- [ ] Read project-specification.md (overview)
- [ ] Read theme-specification.md (if using themes)
- [ ] Read layout-specification.md (for layout system)
- [ ] Set up central config
- [ ] Implement file storage abstraction
- [ ] Create base layout
- [ ] Create first sections
- [ ] Build template system
- [ ] Implement admin UI
- [ ] Add theme support (optional)
- [ ] Write tests

---

## Related Files

- `/config/pagebuilder.php` — Central configuration
- `/src/Coderstm.php` — Service provider registration
- `/src/Services/` — Business logic
- `/src/Contracts/` — Interfaces
- `/src/Commands/` — Artisan commands
- `/tests/` — Test suite

---

## Version

**Current:** 2.0.0 (Draft)  
**Updated:** January 27, 2026  
**Status:** Specification phase — Ready for implementation

---

## Support

For questions or clarifications:

- Review the relevant specification file
- Check the Quick Navigation section above
- Reference code examples in the specifications
- See troubleshooting sections in theme-specification.md

---

**Happy building! You're creating Shopify's architecture with Laravel's flexibility.** 🚀
