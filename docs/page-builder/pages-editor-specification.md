# Pages Editor Specification — Laravel Section-Based Page Builder

> **Overview:** Database-driven pages with file-based section content  
> **Architecture:** Page records in DB; section content stored as JSON files  
> **Editing Control:** Full CRUD on pages; sections editable via pages editor

---

## 1. Overview

The Pages Editor allows creating and managing **individual pages** with **page-specific section configurations**. Pages are database records containing metadata (ID, slug, title, template, metadata), while the actual section content is stored as JSON files.

### Key Concepts

- ✔ **Database-driven** — Pages stored as database records with metadata
- ✔ **File-based content** — Section content stored as `{id}.json` files
- ✔ **Template-based** — Each page references a template type
- ✔ **Full CRUD** — Create, read, update, delete pages via API
- ✔ **Publishable** — Pages can be published/drafted
- ✔ **SEO metadata** — Title, slug, meta description, keywords stored in DB
- ✔ **Multiple sections** — Pages contain multiple sections (same structure as templates)
- ✔ **Theme-aware** — Pages load/store from theme when theme mode enabled
- ✔ **Blade fallback** — Optional custom Blade override for complex pages

---

## 2. Architecture: Database + Files

Pages are a **hybrid system** combining database records and file-based content:

### Database (Metadata)

**Table:** `pages`

```sql
CREATE TABLE pages (
    id BIGINT PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    template VARCHAR(100) NOT NULL,                -- References template type
    status ENUM('draft', 'published') DEFAULT 'draft',
    meta_description TEXT,
    meta_keywords TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Page Model:**

```php
namespace Coderstm\Models;

class Page extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'template',
        'status',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
```

### File Storage (Content)

**Location:** `resources/views/pages/{slug}.json` or `resources/themes/{theme-name}/pages/{slug}.json`

```
resources/views/pages/
├── home.json              # Content for slug: home
├── about-us.json          # Content for slug: about-us
├── contact.json           # Content for slug: contact
└── services.json          # Content for slug: services

# With theme
resources/themes/gympify/pages/
├── home.json              # Theme-specific content for slug: home
└── about-us.json
```

### Example: Page Record + Content

**Database Record (pages table):**

```
id: 1
slug: about-us
title: About Our Company
template: page
status: published
meta_description: Learn about our company mission and team
meta_keywords: about, company, team
theme: null (uses default)
seo_friendly: true
created_at: 2024-01-15 10:30:00
updated_at: 2024-01-20 14:45:00
```

**File Content (resources/views/pages/about-us.json):**

```json
{
    "sections": {
        "hero": {
            "type": "page-hero",
            "settings": {
                "title": "About Our Company",
                "subtitle": "Building amazing products since 2020"
            }
        },
        "team": {
            "type": "team-members",
            "settings": {
                "heading": "Meet Our Team"
            }
        }
    },
    "order": ["hero", "team"]
}
```

---

## 3. Filesystem Architecture

### 3.1 Default Mode (No Theme)

```
resources/views/
└── pages/
    ├── home.json              # Content for slug: home
    ├── about-us.json          # Content for slug: about-us
    ├── contact.json           # Content for slug: contact
    └── services.json          # Content for slug: services

# Optional: Blade fallback for complex pages
├── about-us.blade.php         # If exists, page is locked (preview-only)
└── ...
```

### 3.2 Theme Mode

When using themes, page content is stored per-theme:

```
resources/themes/{theme-name}/views/
└── pages/
    ├── home.json              # Theme-specific content for slug: home
    ├── about-us.json          # Theme-specific content for slug: about-us
    └── ...
```

---

## 3. Page JSON Structure (Content)

### 3.1 What's Stored in Files

The JSON file contains **only the section content** — metadata is stored in the database:

```json
{
    "sections": {
        "hero-section": {
            "type": "page-hero",
            "settings": {
                /* ... */
            }
        }
    },
    "order": ["hero-section"]
}
```

**Note:** No `id`, `slug`, `title`, `template`, or metadata fields — these are in the database.

### 3.2 Schema Attributes (Content Only)

| Attribute  | Type   | Required | Description                                                                                                                                                                       |
| ---------- | ------ | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `sections` | Object | Yes      | Object with section data. Keys are section IDs, values are section configuration. **See [Templates § 5](./templates-specification.md#5-section-data-format) for full structure.** |
| `order`    | Array  | Yes      | Array of section IDs in render order. All section IDs must be listed. **See [Templates § 5](./templates-specification.md#5-section-data-format) for details.**                    |

### Key Differences from Templates

- ❌ **No `layout` field** — Pages don't define layout (inherited from routing)
- ❌ **No `wrapper` field** — Pages don't define wrapper (inherited from template type)
- ✅ **Sections structure identical** — See [Templates § 5](./templates-specification.md#5-section-data-format)
- ✅ **Blocks structure identical** — See [Templates § 5](./templates-specification.md#5-section-data-format)
- ✅ **Settings identical** — See [Templates § 5](./templates-specification.md#5-section-data-format)

### Reference

For complete details on section structure, blocks, and settings:
→ See [Templates Specification § 5: Section Data Format](./templates-specification.md#5-section-data-format)

### 3.3 Example Page JSON

**File:** `resources/views/pages/about-us.json`

```json
{
    "sections": {
        "hero": {
            "type": "page-hero",
            "settings": {
                "title": "About Our Company",
                "subtitle": "Building amazing products since 2020",
                "background_image": "cdn://about-hero.jpg"
            }
        },
        "team": {
            "type": "team-members",
            "settings": {
                "heading": "Meet Our Team",
                "columns": 3
            },
            "blocks": {
                "member-1": {
                    "type": "team-member",
                    "settings": {
                        "name": "John Doe",
                        "role": "CEO",
                        "image": "cdn://john.jpg"
                    }
                },
                "member-2": {
                    "type": "team-member",
                    "settings": {
                        "name": "Jane Smith",
                        "role": "CTO",
                        "image": "cdn://jane.jpg"
                    }
                }
            },
            "block_order": ["member-1", "member-2"]
        },
        "cta": {
            "type": "call-to-action",
            "settings": {
                "heading": "Ready to get started?",
                "button_text": "Contact Us",
                "button_url": "/contact"
            }
        }
    },
    "order": ["hero", "team", "cta"]
}
```

**Compare:** This is identical to [Templates § 8 (Example 1: Homepage)](./templates-specification.md#example-1-homepage-indexjson), except without `layout` and `wrapper` fields.

→ For more section structure examples, see [Templates Specification § 5](./templates-specification.md#5-section-data-format)

---

## 4. Blade Page Override

### 4.1 How It Works

If a `.blade.php` file exists for a page (using page slug), it **locks the page for editing** — the page shows as **preview-only** in the editor:

```
Database Record:        pages table (slug: about-us)
Content File:           resources/views/pages/about-us.json
Blade Override:         resources/views/pages/about-us.blade.php

If Blade exists:        Page is LOCKED (preview-only)
If no Blade:            Page is EDITABLE (uses JSON content)
```

### 4.2 Use Cases

Blade page overrides are useful when:

- ✔ Page has **complex custom logic** (queries, calculations)
- ✔ Page needs **server-side rendering** specific to context
- ✔ Page is **developer-controlled**, not merchant-editable
- ✔ Page has **custom styling** or **custom structure** beyond sections

### 4.3 Locking/Unlocking Pages

**To lock a page:**

1. Create a Blade file with the page slug: `resources/views/pages/{slug}.blade.php`
2. Page now shows as preview-only in editor

```bash
# Lock page (slug: about-us)
touch resources/views/pages/about-us.blade.php
```

**To unlock a page:**

1. Delete the Blade override file
2. Page reverts to editable mode using JSON content

```bash
# Unlock page (slug: about-us)
rm resources/views/pages/about-us.blade.php
```

### 4.4 Example: Locked vs Editable

**Locked Page (Blade Override Exists):**

```blade
<!-- resources/views/pages/about-us.blade.php -->
<!-- This page cannot be edited through pages editor -->
<div class="custom-about-page">
    <!-- Complex custom content here -->
</div>
```

**Editable Page (JSON Content Only):**

```json
<!-- resources/views/pages/about-us.json -->
{
    "sections": {
        "hero": {
            "type": "page-hero",
            "settings": {
                "title": "About Our Company"
                // Fully editable through pages editor
            }
        }
    }
}
```

---

## 5. Page Loading Logic

### 5.1 Loading Flow

When loading a page by slug (e.g., `about-us`):

```
1. Query database (pages table)
   └─ Find page by slug
   └─ If not found: throw PageNotFoundException

2. Load from database
   └─ Get ID, title, template, status, metadata

3. Check for Blade override
   └─ Look for resources/views/pages/{slug}.blade.php
   └─ If found: Page is LOCKED (preview-only, skip content loading)
   └─ If not found: Continue to next step

4. Load content from file
   └─ Read resources/views/pages/{slug}.json
   └─ Merge with database metadata
   └─ Page is EDITABLE

5. Return combined page object
   └─ DB fields: id, slug, title, template, status, metadata
   └─ File content: sections, order
```

**Example: Loading page with slug "about-us":**

```
Database Query:      SELECT * FROM pages WHERE slug = 'about-us'
Result:              id: 1, title: "About Us", template: "page", status: "published"
Check Blade:         Does resources/views/pages/about-us.blade.php exist? No
Load Content:        Read resources/views/pages/about-us.json
Return Combined:     { id: 1, slug: "about-us", title: "About Us", ..., sections: {...}, order: [...] }
```

**In the Editor:**

- **Database lookup** → Find page by slug
- **If Blade exists** → Show page metadata but DISABLE section editing (preview-only)
- **If no Blade** → Show page metadata and ENABLE section editing
- **If not found** → Show error

### 5.2 Loading Service

```php
namespace Coderstm\Services\PageBuilder;

use Coderstm\Models\Page;
use Coderstm\Contracts\BuilderFileStore;
use Coderstm\Exceptions\PageNotFoundException;

class PageLoader
{
    public function __construct(
        protected BuilderFileStore $fileStore,
        protected PageValidator $validator
    ) {}

    /**
     * Load a page by slug.
     *
     * Combines database metadata with file-based section content.
     *
     * @param string $slug Page slug (e.g., 'about-us')
     * @return array Page data combining DB metadata and file content
     * @throws PageNotFoundException
     */
    public function load(string $slug): array
    {
        // 1. Find page in database by slug
        $page = Page::where('slug', $slug)->first();

        if (!$page) {
            throw new PageNotFoundException("Page not found: {$slug}");
        }

        // 2. Check for Blade override using page slug
        if ($this->hasBlade($slug)) {
            return [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'template' => $page->template,
                'status' => $page->status,
                'editable' => false,
                'message' => 'This page is controlled by a Blade template (preview-only)',
                'action' => "Delete resources/views/pages/{$slug}.blade.php to make it editable",
                'created_at' => $page->created_at,
                'updated_at' => $page->updated_at,
            ];
        }

        // 3. Load section content from file using page slug
        if ($this->hasContent($slug)) {
            $json = $this->fileStore->read("pages/{$slug}.json");
            $content = json_decode($json, true);

            $this->validator->validate($content);

            return [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'template' => $page->template,
                'status' => $page->status,
                'meta_description' => $page->meta_description,
                'meta_keywords' => $page->meta_keywords,
                'editable' => true,
                'content' => $content,
                'sections_count' => count($content['sections'] ?? []),
                'created_at' => $page->created_at,
                'updated_at' => $page->updated_at,
            ];
        }

        // 4. If no content file, create empty structure
        return [
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
            'template' => $page->template,
            'status' => $page->status,
            'editable' => true,
            'content' => [
                'sections' => [],
                'order' => [],
            ],
            'sections_count' => 0,
            'created_at' => $page->created_at,
            'updated_at' => $page->updated_at,
        ];
    }

    /**
     * Check if Blade override exists for a page (using page slug).
     */
    protected function hasBlade(string $slug): bool
    {
        return $this->fileStore->exists("pages/{$slug}.blade.php");
    }

    /**
     * Check if content file exists for a page (using page slug).
     */
    protected function hasContent(string $slug): bool
    {
        return $this->fileStore->exists("pages/{$slug}.json");
    }

    /**
     * Get all pages from database.
     *
     * @return array Array of editable page slugs
     */
    public function all(): array
    {
        $files = $this->fileStore->listDirectory('pages');

        return array_filter(
            array_map(function ($file) {
                // Only return JSON pages (not Blade files)
                if (str_ends_with($file, '.json')) {
                    return basename($file, '.json');
                }
                return null;
            }, $files),
            fn($slug) => $slug !== null
        );
    }

    /**
     * Get Blade path for a page.
     */
    protected function getBladePath(string $slug): string
    {
        return "pages/{$slug}.blade.php";
    }
}
```

---

## 6. Page Rendering Pipeline

### 6.1 Rendering Process

```
1. Receive page request (slug)
   ↓
2. Load page (PageLoader)
   ├─ If Blade exists: Return as preview-only (locked)
   └─ If JSON exists: Continue to rendering
   ↓
3. Load page JSON from pages/{slug}.json
   ↓
4. Get layout/wrapper from page configuration or route-determined template
   ↓
5. For each section in page.order array:
   ├─ Load section Blade file (type = sections/{type}.blade.php)
   ├─ Pass section settings & blocks as data
   ├─ Render section with data
   └─ Append to output
   ↓
6. Wrap all sections in layout/wrapper
   ↓
7. Render final HTML to browser
```

**Key Point:** Pages contain complete section data. No merging with templates during rendering — the page JSON is self-contained.

### 6.2 Page-Specific Content

Each page is completely self-contained with its own sections:

```php
// Template (shared blueprint)
// resources/views/templates/page.json
{
  "layout": "theme",
  "wrapper": "main",
  "sections": {
    // Default/common sections
  }
}

// Page 1 (about-us) - different content
// resources/views/pages/about-us.json
{
  "sections": {
    "hero": { "type": "page-hero", "settings": { "title": "About Us" } },
    "team": { "type": "team-members", ... },
    "mission": { "type": "mission-statement", ... }
  },
  "order": ["hero", "team", "mission"]
}

// Page 2 (contact) - different content
// resources/views/pages/contact.json
{
  "sections": {
    "hero": { "type": "page-hero", "settings": { "title": "Contact Us" } },
    "form": { "type": "contact-form", ... },
    "map": { "type": "location-map", ... }
  },
  "order": ["hero", "form", "map"]
}
```

Each page has **independent sections** with **page-specific content**. No inheritance or merging from templates.

---

## 7. Pages Editor API

### 7.1 REST Endpoints

All page operations work with database records. The API manages both database metadata and file-based content:

```
# List all pages
GET /api/pages

# Get single page for editing (by slug)
GET /api/pages/{slug}

# Create new page
POST /api/pages
Body: {
  "slug": "new-page",
  "title": "New Page Title",
  "template": "page",
  "meta_description": "Page description",
  "sections": { /* ... */ },
  "order": [ /* ... */ ]
}

# Update page (metadata and/or content)
PUT /api/pages/{slug}
Body: {
  "title": "Updated Title",           # DB field
  "meta_description": "...",          # DB field
  "sections": { /* ... */ },          # File content
  "order": [ /* ... */ ]              # File content
}

# Publish page
PATCH /api/pages/{slug}/publish
Body: {
  "status": "published"
}

# Draft page
PATCH /api/pages/{slug}/draft
Body: {
  "status": "draft"
}

# Delete page (removes DB record and content file)
DELETE /api/pages/{slug}

# Duplicate page
POST /api/pages/{slug}/duplicate
Body: {
  "new_slug": "new-page-copy",
  "new_title": "New Page Copy"
}

# Lock page (create Blade override - makes preview-only)
POST /api/pages/{slug}/lock
Body: {
  "blade_content": "<!-- custom blade code -->"
}

# Unlock page (delete Blade override - makes editable again)
DELETE /api/pages/{slug}/lock
```

### 7.2 Response Examples

**GET /api/pages**

```json
{
    "data": [
        {
            "id": 1,
            "slug": "about-us",
            "title": "About Our Company",
            "template": "page",
            "status": "published",
            "editable": true,
            "sections_count": 3,
            "meta_description": "Learn about our company",
            "created_at": "2024-01-15T10:30:00Z",
            "updated_at": "2024-01-20T14:45:00Z"
        },
        {
            "id": 2,
            "slug": "contact",
            "title": "Contact Us",
            "template": "page",
            "status": "published",
            "editable": false,
            "message": "This page has a Blade override",
            "created_at": "2024-01-10T08:00:00Z"
        }
    ],
    "meta": {
        "total": 2,
        "per_page": 15,
        "current_page": 1
    }
}
```

**POST /api/pages** (Create Response)

```json
{
    "id": 3,
    "slug": "services",
    "title": "Our Services",
    "template": "page",
    "status": "draft",
    "editable": true,
    "sections": {
        "hero": {
            "type": "page-hero",
            "settings": {
                "title": "Our Services"
            }
        }
    },
    "order": ["hero"],
    "created_at": "2024-01-25T10:00:00Z",
    "updated_at": "2024-01-25T10:00:00Z"
}
```

**GET /api/pages/{slug}** (Editable Page - Using DB + File)

```json
{
    "id": 1,
    "slug": "about-us",
    "title": "About Our Company",
    "template": "page",
    "status": "published",
    "meta_description": "Learn about our company and team",
    "meta_keywords": "about, company, team",
    "editable": true,
    "content": {
        "sections": {
            "hero": {
                "type": "page-hero",
                "settings": {
                    "title": "About Our Company",
                    "subtitle": "Building amazing products since 2020"
                }
            },
            "team": {
                "type": "team-members",
                "settings": { "heading": "Meet Our Team" }
            }
        },
        "order": ["hero", "team"]
    },
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-20T14:45:00Z"
}
```

**GET /api/pages/{slug}** (Locked Page - Blade Override)

```json
{
    "id": 2,
    "slug": "contact",
    "title": "Contact Us",
    "template": "page",
    "status": "published",
    "editable": false,
    "message": "This page is controlled by a Blade template",
    "action": "Delete resources/views/pages/contact.blade.php to make it editable",
    "created_at": "2024-01-10T08:00:00Z"
}
```

---

## 8. Page Validation

### 8.1 Validation Rules

Pages follow **the same validation rules as templates** (see [Templates Specification § 12](./templates-specification.md#12-validation-rules)), with two key differences:

- ❌ `layout` field is **not required** (pages don't define layout)
- ❌ `wrapper` field is **not required** (pages don't define wrapper)
- ✅ `sections` field is **required** (same as templates)
- ✅ `order` field is **required** (same as templates)

### Full Validation Rules

| Check                                | Error                       | Fix                       |
| ------------------------------------ | --------------------------- | ------------------------- |
| `sections` object missing            | `SectionsRequiredException` | Add `sections` object     |
| `order` array missing                | `OrderRequiredException`    | Add `order` array         |
| Section in `order` not in `sections` | `SectionNotFound`           | Add section to `sections` |
| Duplicate IDs in `order`             | `DuplicateSectionId`        | Remove duplicates         |
| Section type not found               | `SectionTypeNotFound`       | Use existing section type |
| Sections exceed 25 limit             | `TooManySections`           | Remove sections           |
| Blocks exceed 50 per section         | `TooManyBlocks`             | Remove blocks             |
| Empty sections object                | `EmptySections`             | Add at least one section  |
| Empty order array                    | `EmptyOrder`                | Add at least one section  |

→ For complete details, see [Templates Specification § 12](./templates-specification.md#12-validation-rules)

### 8.2 Page Validator

Pages use the **same validation logic as templates** (see [Templates Specification § 12](./templates-specification.md#sample-validation-code)).

```php
namespace Coderstm\Services\PageBuilder;

use Coderstm\Exceptions\PageBuilder\ValidationException;

class PageValidator
{
    /**
     * Validate page structure.
     *
     * Uses the same validation as templates (see TemplateValidator)
     * except: layout and wrapper fields are not required for pages.
     *
     * @param array $page Page data
     * @return bool True if valid
     * @throws ValidationException
     */
    public function validate(array $page): bool
    {
        // Validate required fields
        if (!isset($page['sections']) || !is_array($page['sections'])) {
            throw ValidationException::missingField('sections');
        }

        if (!isset($page['order']) || !is_array($page['order'])) {
            throw ValidationException::missingField('order');
        }

        // Validate sections is not empty
        if (empty($page['sections'])) {
            throw ValidationException::emptySections();
        }

        // Validate order is not empty
        if (empty($page['order'])) {
            throw ValidationException::emptyOrder();
        }

        // Validate section count
        if (count($page['sections']) > 25) {
            throw ValidationException::tooManySections();
        }

        // Validate order references sections
        foreach ($page['order'] as $id) {
            if (!isset($page['sections'][$id])) {
                throw ValidationException::sectionNotInOrder($id);
            }
        }

        // Validate no duplicates in order
        if (count($page['order']) !== count(array_unique($page['order']))) {
            throw ValidationException::duplicateSectionIds();
        }

        // Validate blocks per section
        foreach ($page['sections'] as $section) {
            if (isset($section['blocks']) && count($section['blocks']) > 50) {
                throw ValidationException::tooManyBlocks();
            }
        }

        return true;
    }
}
```

**Reference:** The validation logic is identical to [Templates Specification § 12 (TemplateValidator)](./templates-specification.md#sample-validation-code), with the exception that `layout` and `wrapper` are not required or validated for pages.

---

## 9. Pages Editor UI Components

### 9.1 Pages List View

Display all editable pages with status:

```
Pages
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Slug                Type      Status        Updated
about-us            page      ✓ Editable    Jan 20, 2:45 PM
contact             page      🔒 Locked     Jan 15, 8:00 AM
privacy-policy      page      ✓ Editable    Jan 10, 10:30 AM
custom-landing      landing   ✓ Editable    Jan 5, 3:15 PM

[+ Create New Page]
```

### 9.2 Page Editor View

**For Editable Pages (JSON):**

```
┌─────────────────────────────────────────────────────────┐
│ About Us                         [Save] [Preview]       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Template: page                                          │
│                                                         │
│ Sections:                                               │
│ ├─ [Hero Section]         [↑↓] [⚙️] [🗑]               │
│ ├─ [Team Members]         [↑↓] [⚙️] [🗑]               │
│ ├─ [Call to Action]       [↑↓] [⚙️] [🗑]               │
│                                                         │
│ [+ Add Section]                                         │
│                                                         │
│ Settings for: Hero Section                              │
│ ┌──────────────────────────────────────────────────┐   │
│ │ Title: About Our Company                         │   │
│ │ Subtitle: Building great products                │   │
│ │ Background Image: [Choose File]                  │   │
│ └──────────────────────────────────────────────────┘   │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**For Locked Pages (Blade):**

```
┌─────────────────────────────────────────────────────────┐
│ Contact Us                                              │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 🔒 This page is controlled by Blade                    │
│                                                         │
│ This page cannot be edited through the visual editor   │
│ because it has a custom Blade template.                │
│                                                         │
│ To make this page editable:                            │
│ 1. Delete resources/views/pages/contact.blade.php     │
│ 2. The editor will then load the JSON configuration   │
│ 3. You'll be able to edit it with the visual editor   │
│                                                         │
│ [Delete Blade File & Make Editable]                   │
│ [View Code] [Preview]                                  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 10. Theme Integration

### 10.1 Theme-Aware Page Loading

When theme mode is enabled:

```php
// In config or middleware
if (config('pagebuilder.theme.enabled')) {
    $theme = config('pagebuilder.theme.name');

    config([
        'pagebuilder.paths.pages' =>
            resource_path("themes/{$theme}/views/pages"),
    ]);
}
```

### 10.2 Theme-Specific Pages

Each theme can have its own pages:

```
resources/themes/gympify/pages/
├── home.json
├── about-us.json
├── services.json
└── contact.json

resources/themes/saas/views/pages/
├── home.json
├── features.json
├── pricing.json
└── contact.json
```

### 10.3 Fallback Strategy

```
1. Check theme-specific page
   └─ resources/themes/{theme-name}/pages/{slug}.json

2. Check theme-specific Blade
   └─ resources/themes/{theme-name}/pages/{slug}.blade.php

3. Check default page
   └─ resources/views/pages/{slug}.json

4. Check default Blade
   └─ resources/views/pages/{slug}.blade.php

5. Use default template
   └─ resources/views/templates/{type}.json
```

---

## 11. Page-Specific Features

### 11.1 Creating Pages

When creating a new page, provide complete sections configuration:

```php
class PageService
{
    public function create(string $slug, array $sections, array $order): array
    {
        // Validate page structure
        $this->validator->validate([
            'sections' => $sections,
            'order' => $order,
        ]);

        // Create page data
        $page = [
            'sections' => $sections,
            'order' => $order,
        ];

        // Save page
        $this->fileStore->write("pages/{$slug}.json", json_encode($page, JSON_PRETTY_PRINT));

        // Invalidate cache
        Cache::forget('pages:all');

        return $page;
    }
}
```

### 11.2 Duplicating Pages

Copy an existing page to create a new one:

```php
class PageService
{
    public function duplicate(string $fromSlug, string $toSlug): array
    {
        // Load source page
        $sourcePage = $this->pageLoader->load($fromSlug);

        if (!$sourcePage['editable']) {
            throw new \Exception("Cannot duplicate locked (Blade) pages");
        }

        // Create new page with same sections and order
        $newPage = [
            'sections' => $sourcePage['data']['sections'],
            'order' => $sourcePage['data']['order'],
        ];

        // Save new page
        $this->fileStore->write("pages/{$toSlug}.json", json_encode($newPage, JSON_PRETTY_PRINT));

        // Invalidate cache
        Cache::forget('pages:all');

        return $newPage;
    }
}
```

### 11.3 Locking Pages (Create Blade Override)

Make a page non-editable (preview-only) by creating a Blade override:

```php
class PageService
{
    public function lock(string $slug, string $bladeContent): void
    {
        // Write Blade file — this locks the page
        $this->fileStore->write("pages/{$slug}.blade.php", $bladeContent);

        // Cache invalidation
        Cache::forget("page:{$slug}");
    }
}
```

**Effect:** Page now shows as preview-only in the editor. Sections cannot be edited.

### 11.4 Unlocking Pages (Delete Blade Override)

Make a page editable again by deleting the Blade override:

```php
class PageService
{
    public function unlock(string $slug): void
    {
        // Delete Blade file — page becomes editable
        $this->fileStore->delete("pages/{$slug}.blade.php");

        // Cache invalidation
        Cache::forget("page:{$slug}");
    }
}
```

**Effect:** Page now loads from JSON and becomes fully editable in the editor.

---

## 12. Page Caching Strategy

### 12.1 Cache Keys

```php
// Cache page JSON
$cache->remember("page:{$slug}", 3600, function () {
    return $pageLoader->load($slug);
});

// Cache available pages list
$cache->remember("pages:all", 3600, function () {
    return $pageLoader->all();
});
```

### 12.2 Cache Invalidation

```php
class PageService
{
    public function save(string $slug, array $page): void
    {
        $this->fileStore->write("pages/{$slug}.json", json_encode($page));

        // Invalidate caches
        Cache::forget("page:{$slug}");
        Cache::forget("pages:all");
        Cache::forget("pages:count");
    }

    public function delete(string $slug): void
    {
        $this->fileStore->delete("pages/{$slug}.json");

        // Invalidate caches
        Cache::forget("page:{$slug}");
        Cache::forget("pages:all");
        Cache::forget("pages:count");
    }
}
```

---

## 13. Best Practices

### ✅ DO

- ✔ Use **JSON pages** for editable, dynamic page content
- ✔ Use **Blade pages** for developer-controlled, locked pages only
- ✔ Keep **page slugs meaningful** and SEO-friendly
- ✔ **Cache aggressively** — Pages change infrequently
- ✔ **Validate all pages** before saving
- ✔ **Back up pages regularly** — They're part of your site content
- ✔ Use **sections for all dynamic content** — Pages are just section containers
- ✔ Make pages **independent** — Don't cross-reference between pages

### ❌ DON'T

- ❌ Put **logic in page JSON** — Use sections for that (they're Blade components)
- ❌ Exceed **25 sections per page** — Keep pages focused
- ❌ Try to **edit locked (Blade) pages** — Delete the Blade file to unlock
- ❌ Create **pages with duplicated sections** — Use copies only for variations
- ❌ Cache pages **too long** — Risk stale data (1 hour max recommended)
- ❌ Edit **pages through code** — Use the editor exclusively
- ❌ Expect **automatic template updates** to pages — Pages are independent
- ❌ Store **complex nested objects** in page settings — Keep it flat

---

## 14. Complete Example: About Page

### 14.1 Template (Reusable Blueprint)

**File:** `resources/views/templates/page.json`

```json
{
    "layout": "theme",
    "wrapper": "main",
    "sections": {
        "hero": {
            "type": "page-hero",
            "settings": {
                "title": "Default Title",
                "subtitle": "Default Subtitle"
            }
        }
    },
    "order": ["hero"]
}
```

> **Note:** This is the shared template used by all pages. It defines layout and wrapper.

### 14.2 Page: About Us (Individual Content)

**File:** `resources/views/pages/about-us.json`

```json
{
    "sections": {
        "hero": {
            "type": "page-hero",
            "settings": {
                "title": "About Our Company",
                "subtitle": "Building amazing products since 2020",
                "background_image": "cdn://about-hero.jpg"
            }
        },
        "team": {
            "type": "team-members",
            "settings": {
                "heading": "Meet Our Team",
                "columns": 3
            },
            "blocks": {
                "member-1": {
                    "type": "team-member",
                    "settings": {
                        "name": "John Doe",
                        "role": "CEO",
                        "image": "cdn://john.jpg"
                    }
                },
                "member-2": {
                    "type": "team-member",
                    "settings": {
                        "name": "Jane Smith",
                        "role": "CTO",
                        "image": "cdn://jane.jpg"
                    }
                }
            },
            "block_order": ["member-1", "member-2"]
        },
        "mission": {
            "type": "mission-statement",
            "settings": {
                "heading": "Our Mission",
                "content": "We're building tools that empower creators..."
            }
        },
        "cta": {
            "type": "call-to-action",
            "settings": {
                "heading": "Ready to get started?",
                "button_text": "Contact Us",
                "button_url": "/contact"
            }
        }
    },
    "order": ["hero", "team", "mission", "cta"]
}
```

> **Note:** This page is completely self-contained. It has 4 sections with its own content. No reference to template field.

### 14.3 Page: Contact (Different Content, Different Sections)

**File:** `resources/views/pages/contact.json`

```json
{
    "sections": {
        "hero": {
            "type": "page-hero",
            "settings": {
                "title": "Contact Us",
                "subtitle": "Get in touch with our team"
            }
        },
        "form": {
            "type": "contact-form",
            "settings": {
                "form_id": "contact_main"
            }
        },
        "map": {
            "type": "location-map",
            "settings": {
                "location": "Our Headquarters",
                "latitude": 40.7128,
                "longitude": -74.006
            }
        }
    },
    "order": ["hero", "form", "map"]
}
```

> **Note:** Different sections than about-us. Each page is independent.

### 14.4 Section Component (Shared Blade File)

**File:** `resources/views/sections/page-hero.blade.php`

```blade
<section class="page-hero" style="background-image: url('{{ $settings['background_image'] ?? '' }}')">
  <div class="container">
    <h1>{{ $settings['title'] }}</h1>
    <p class="subtitle">{{ $settings['subtitle'] ?? '' }}</p>
  </div>
</section>

@section('pagebuilder-schema')
{
  "name": "Page Hero",
  "type": "page-hero",
  "settings": [
    {
      "id": "title",
      "label": "Title",
      "type": "text",
      "placeholder": "Page title"
    },
    {
      "id": "subtitle",
      "label": "Subtitle",
      "type": "text",
      "placeholder": "Optional subtitle"
    },
    {
      "id": "background_image",
      "label": "Background Image",
      "type": "image"
    }
  ]
}
@endsection
```

### 14.5 Rendering Flow

When a user visits `/about-us`:

```
1. Router detects /about-us route
   ↓
2. PageLoader::load('about-us')
   ├─ Check for pages/about-us.blade.php → NOT found
   ├─ Check for pages/about-us.json → FOUND
   ├─ Load JSON and return { editable: true, data: {...} }
   ↓
3. Get layout/wrapper from page template configuration
   ↓
4. For each section in order ["hero", "team", "mission", "cta"]:
   ├─ Load sections/{type}.blade.php (e.g., sections/page-hero.blade.php)
   ├─ Pass section settings to the Blade component
   ├─ Render Blade component with data
   ├─ Append to output
   ↓
5. Wrap all sections in layout
   ↓
6. Return complete HTML to browser
```

**Final HTML Example:**

```html
<!DOCTYPE html>
<html>
    <body>
        <section
            class="page-hero"
            style="background-image: url('cdn://about-hero.jpg')"
        >
            <h1>About Our Company</h1>
            <p>Building amazing products since 2020</p>
        </section>

        <section class="team-members">
            <!-- Team section content -->
        </section>

        <section class="mission-statement">
            <!-- Mission section content -->
        </section>

        <section class="call-to-action">
            <!-- CTA section content -->
        </section>
    </body>
</html>
```

7. Returns final HTML

````

---

## 15. Managing Page Overrides

### 15.1 Override Workflow

**Scenario:** You want to lock a page from editing

```bash
# Step 1: Create custom Blade file
# resources/views/pages/about-us.blade.php
<div class="custom-about">
  <!-- Complex custom content -->
</div>

# Step 2: Page now shows as locked in editor
# Editor displays: "This page is controlled by Blade"

# Step 3: To unlock, delete Blade file
rm resources/views/pages/about-us.blade.php

# Step 4: Page now shows as editable
# Editor loads: resources/views/pages/about-us.json
````

### 15.2 CLI Commands (Optional)

```bash
# List all pages
php artisan pages:list

# Create new page from template
php artisan pages:create about-us --template=page

# Duplicate page
php artisan pages:duplicate about-us --to=about-us-spanish

# Lock page (create Blade override)
php artisan pages:lock about-us

# Unlock page (delete Blade override)
php artisan pages:unlock about-us

# Delete page
php artisan pages:delete about-us
```

---

## 16. Troubleshooting

### Issue: Page shows as "locked" but I want to edit it

**Cause:** `.blade.php` file exists for the page  
**Solution:**

```bash
# Delete the Blade file
rm resources/views/pages/about-us.blade.php

# Page now becomes editable via JSON
```

### Issue: Page changes not appearing

**Cause:** Page is cached  
**Solution:**

```bash
# Clear page cache
php artisan cache:forget page:about-us
php artisan cache:forget pages:all

# Or clear all builder cache
php artisan cache:forget pages:*
```

### Issue: Template reference not working

**Cause:** Template doesn't exist  
**Solution:**

```bash
# Verify template exists
ls resources/views/templates/page.json

# If missing, create from template specification
```

### Issue: Section not rendering

**Cause:** Section type in page doesn't match available sections  
**Solution:**

```bash
# Verify section file exists
ls resources/views/sections/page-hero.blade.php

# Check page JSON for correct section type
# page.json should reference "page-hero", not "hero-section"
```

---

## 17. Relationship Between Pages and Templates

### Understanding the Difference

**Pages and templates share the same JSON structure** — both define sections, order, and block content. The key difference is **what each field controls**:

#### Templates Define:

- ✅ **Layout** — Which layout wrapper to use (e.g., `theme`, `full-width`)
- ✅ **Wrapper** — HTML element to wrap sections (e.g., `<main>`, `<div>`)
- ✅ **Sections** — Default/common sections for a page type
- ✅ **Order** — Default render order

→ See [Templates Specification § 4](./templates-specification.md#4-json-template-schema)

#### Pages Define:

- ✅ **Sections** — Page-specific sections with unique content
- ✅ **Order** — Specific order for this page
- ❌ **Layout** — NOT defined (inherited from routing/config)
- ❌ **Wrapper** — NOT defined (inherited from template)

### Side-by-Side Comparison

**Template (templates/page.json):**

```json
{
    "layout": "theme",
    "wrapper": "main",
    "sections": {
        "hero": { "type": "hero", "settings": { "title": "Default" } }
    },
    "order": ["hero"]
}
```

**Page (pages/about-us.json):**

```json
{
    "sections": {
        "hero": { "type": "hero", "settings": { "title": "About Us" } },
        "team": { "type": "team", "settings": { ... } },
        "cta": { "type": "cta", "settings": { ... } }
    },
    "order": ["hero", "team", "cta"]
}
```

**Rendering:**

- Template provides `layout` = "theme" (which layout file to use)
- Template provides `wrapper` = "main" (HTML element)
- Page provides all sections and order
- Result: Render page sections in order, wrap with `<main>`, inject into `theme.blade.php`

### Structure Reuse

**Both templates and pages use the same structures for:**

| Component           | Reference                                                                                 |
| ------------------- | ----------------------------------------------------------------------------------------- |
| **Section data**    | [Templates § 5](./templates-specification.md#5-section-data-format)                       |
| **Block data**      | [Templates § 5](./templates-specification.md#5-section-data-format)                       |
| **Settings format** | [Templates § 5](./templates-specification.md#5-section-data-format)                       |
| **Validation**      | [Templates § 12](./templates-specification.md#12-validation-rules) (minus layout/wrapper) |
| **Limits**          | 25 sections max, 50 blocks per section                                                    |

→ **For all structure details, reference [Templates Specification](./templates-specification.md)**

---

## 18. API Quick Reference

### Core Endpoints

```
GET    /api/pages              # List all pages
GET    /api/pages/{slug}       # Get page for editing
POST   /api/pages              # Create new page
PUT    /api/pages/{slug}       # Update page
DELETE /api/pages/{slug}       # Delete page
POST   /api/pages/{slug}/duplicate  # Duplicate page
POST   /api/pages/{slug}/lock  # Lock page (create Blade)
DELETE /api/pages/{slug}/lock  # Unlock page (delete Blade)
```

### Request Body Examples

**POST /api/pages** (Create)

```json
{
    "slug": "about-us",
    "template": "page"
}
```

**PUT /api/pages/about-us** (Update)

```json
{
    "sections": {
        "hero": {
            "type": "page-hero",
            "settings": {
                "title": "Updated Title"
            }
        }
    },
    "order": ["hero"]
}
```

---

## 19. Summary

**Pages Editor System:**

- ✅ **Database-driven** — Pages stored as database records (id, slug, title, template, status, metadata)
- ✅ **File-based content** — Section content stored as JSON files (resources/views/pages/{slug}.json)
- ✅ **Hybrid architecture** — Combines DB metadata with file-based section content
- ✅ **Full CRUD** — Create, read, update, delete pages via API
- ✅ **Template-based** — Each page references a template type
- ✅ **Optional Blade override** — Lock pages with custom Blade templates
- ✅ **Theme-aware** — Supports theme-specific page content
- ✅ **SEO metadata** — Title, slug, description, keywords in database
- ✅ **Publishing** — Draft and published statuses

**Key Features:**

- 📊 Database records manage page metadata
- 📄 JSON files manage section content (sections, order, blocks)
- 🔒 Blade overrides lock pages from editing (preview-only)
- 🔄 Delete Blade file to make page editable again
- 🔗 Reuse all template structures (sections, blocks, settings)
- ♻️ Duplicate pages including DB metadata and content
- 🧪 Validation on save
- 💾 Efficient caching with DB + file combination

**Key Architecture:**

Pages are a **hybrid of database + files**:

- **Database (pages table):** id, slug, title, template, status, meta_description, meta_keywords, theme, created_at, updated_at
- **Files (pages/{slug}.json):** sections, order (identical to template content structure)

**Next Steps:**

1. Create `pages` database migration
2. Create `Page` model with relationships
3. Create `pages/` directories for content
4. Implement PageLoader service (DB + file lookup)
5. Implement PageValidator service
6. Create API endpoints (REST CRUD)
7. Build pages editor UI
8. Integrate with theme system

---

## 20. Related Documentation

- [Project Specification](./project-specification.md) — Overall LSB architecture
- [Templates Specification](./templates-specification.md) — Template system
- [Sections](./project-specification.md#9-section-system) — Section components
- [Layouts](./layout-specification.md) — Page layouts
- [Theme System](./theme-specification.md) — Optional theme support

---

**Architecture:** Page-specific editing with template inheritance  
**Status:** ✅ Production-ready specification
