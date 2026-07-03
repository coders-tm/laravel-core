# Templates Specification — Laravel Section-Based Page Builder

> **Reference:** [Shopify Template Architecture](https://shopify.dev/docs/storefronts/themes/architecture/templates)  
> **JSON Templates:** [Shopify JSON Templates](https://shopify.dev/docs/storefronts/themes/architecture/templates/json-templates)

---

## 1. Overview

Templates are **JSON data files** that define which sections appear on a page, their order, and their configuration. They are the blueprint for page composition in LSB.

### Key Concepts

- ✔ **Data-only files** — No HTML, CSS, or Blade code
- ✔ **Section-based** — Reference sections defined elsewhere
- ✔ **Merchant-editable** — Sections can be added, removed, reordered in the admin UI
- ✔ **Configurable** — Settings for sections and blocks stored in template
- ✔ **Layout-aware** — Specify which layout wraps the sections

### When to Use Templates

Templates should be used when:

- You want a **composable page** (sections added/removed by non-developers)
- You need **multiple variants** of the same page type (e.g., product with side panel)
- You want **merchant control** over page layout without code changes

Templates should **NOT** be used when:

- The page structure is **fixed and immutable** (use Blade templates directly)
- The page has **complex dynamic logic** (use controllers/services, render to Blade)

---

## 2. Template Types (Shopify-Aligned)

LSB supports the following template types. Each represents a distinct page context:

### Content Pages

| Type      | Use Case         | Example                |
| --------- | ---------------- | ---------------------- |
| `index`   | Home page        | Store homepage         |
| `page`    | Static pages     | About, Contact, Policy |
| `blog`    | Blog listing     | /blog                  |
| `article` | Single blog post | /blog/article-title    |

### E-Commerce Pages

| Type               | Use Case                | Example                 |
| ------------------ | ----------------------- | ----------------------- |
| `product`          | Product page            | /products/awesome-shirt |
| `collection`       | Product collection      | /collections/shoes      |
| `list-collections` | All collections listing | /collections            |
| `search`           | Search results          | /search?q=shirt         |
| `cart`             | Shopping cart           | /cart                   |

### Utility Pages

| Type         | Use Case             | Example                  |
| ------------ | -------------------- | ------------------------ |
| `404`        | Not found page       | Invalid URL              |
| `password`   | Password protection  | /password                |
| `robots.txt` | SEO robots directive | /robots.txt (Blade only) |

### Custom Pages

You can create custom template types as needed:

- `custom.landing` — Custom landing page template
- `custom.case-study` — Case study template
- `custom.pricing` — Pricing page template

---

## 3. File Location & Naming

### Default Mode (No Theme)

```
resources/views/
└── templates/
    ├── index.json
    ├── page.json
    ├── page.alternate.json          # Alternate page template
    ├── product.json
    ├── product.video.json            # Alternate product template
    ├── product.subscription.json      # Another alternate
    ├── collection.json
    ├── search.json
    ├── 404.json
    ├── custom.landing.json           # Custom template
    └── custom.landing.minimal.json    # Custom alternate
```

### Theme Mode

When using themes, templates live in the theme directory:

```
resources/themes/{theme-name}/
└── templates/
    ├── index.json
    ├── product.json
    └── ...
```

### Naming Rules

- **Required:** Template type (e.g., `product`, `page`, `custom.landing`)
- **Optional:** Alternate suffix (e.g., `.video`, `.minimal`, `.sidebar`)
- **Format:** `{type}.json` or `{type}.{alternate}.json`
- **Limits:** Max 1,000 JSON templates per theme
- **Uniqueness:** Cannot have both `product.json` and `product.blade.php` in same theme

---

## 4. JSON Template Schema

### Root Structure

```json
{
    "layout": "theme",
    "wrapper": "main",
    "sections": {
        /* ... */
    },
    "order": [
        /* ... */
    ]
}
```

### Schema Attributes

| Attribute  | Type              | Required | Description                                                                                                                                |
| ---------- | ----------------- | -------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| `layout`   | String or `false` | No       | Layout filename (without extension). Defaults to `theme`. Set to `false` to render template without layout (non-editable in theme editor). |
| `wrapper`  | String            | No       | HTML element to wrap all sections. Options: `div`, `main`, `section`. Can include attributes (see [Wrapper Syntax](#wrapper-syntax)).      |
| `sections` | Object            | Yes      | Object with section data. Keys are section IDs, values are section configuration. Must contain at least 1 section.                         |
| `order`    | Array             | Yes      | Array of section IDs in render order. All section IDs must be listed.                                                                      |

### Limits

- ✔ **Sections per template:** Max 25
- ✔ **Blocks per section:** Max 50
- ✔ **Duplicate IDs:** Not allowed (within template)

---

## 5. Section Data Format

### Section Object Structure

```json
{
    "sections": {
        "hero": {
            "type": "hero",
            "disabled": false,
            "settings": {
                "title": "Welcome to our store",
                "subtitle": "Amazing products await",
                "image": "cdn://image.jpg"
            },
            "blocks": {
                "button-1": {
                    "type": "button",
                    "settings": {
                        "label": "Shop Now",
                        "url": "/products"
                    }
                }
            },
            "block_order": ["button-1"]
        }
    }
}
```

### Section Attributes

| Attribute     | Type    | Required | Description                                                            |
| ------------- | ------- | -------- | ---------------------------------------------------------------------- |
| `type`        | String  | Yes      | Section filename (without extension). Alphanumeric only.               |
| `disabled`    | Boolean | No       | If `true`, section is customizable but not rendered. Default: `false`. |
| `settings`    | Object  | No       | Section-level settings. Keys defined by section schema.                |
| `blocks`      | Object  | No       | Repeatable block items within section.                                 |
| `block_order` | Array   | No       | Order in which blocks should render.                                   |

### Block Attributes

| Attribute  | Type   | Required | Description                                                 |
| ---------- | ------ | -------- | ----------------------------------------------------------- |
| `type`     | String | Yes      | Block type as defined in section schema. Alphanumeric only. |
| `settings` | Object | No       | Block-level settings as defined in schema.                  |

---

## 6. Wrapper Syntax

The `wrapper` attribute allows you to wrap all sections with an HTML element and specify attributes.

### Basic Syntax

```json
{
    "wrapper": "main"
}
```

**Output:**

```html
<main>
    <!-- sections rendered here -->
</main>
```

### With ID and Classes

```json
{
    "wrapper": "div#main-content.container.fluid"
}
```

**Output:**

```html
<div id="main-content" class="container fluid">
    <!-- sections rendered here -->
</div>
```

### With Custom Attributes

```json
{
    "wrapper": "section[data-section=hero][role=region]"
}
```

**Output:**

```html
<section data-section="hero" role="region">
    <!-- sections rendered here -->
</section>
```

### Allowed Elements

Only these HTML elements are allowed:

- `<div>`
- `<main>`
- `<section>`

**Invalid wrappers** (will cause errors):

```json
{
    "wrapper": "article" // ❌ Not allowed
}
```

---

## 7. Layout Configuration

### Default Layout

If no `layout` is specified, template uses `theme.blade.php`:

```json
{
    "sections": {
        /* ... */
    }
    // layout defaults to "theme"
}
```

### Specify Custom Layout

```json
{
    "layout": "full-width",
    "sections": {
        /* ... */
    }
}
```

This loads `resources/views/layouts/full-width.blade.php`

### No Layout

Set `layout` to `false` to render sections without any layout wrapper:

```json
{
    "layout": false,
    "sections": {
        /* ... */
    }
}
```

**Important:** Templates without layouts **cannot be customized** in the theme editor.

---

## 8. Complete Example Templates

### Example 1: Homepage (index.json)

```json
{
    "layout": "theme",
    "wrapper": "main",
    "sections": {
        "hero": {
            "type": "hero",
            "settings": {
                "title": "Welcome",
                "background_image": "cdn://hero.jpg"
            }
        },
        "featured-products": {
            "type": "featured-products",
            "settings": {
                "title": "Featured Items",
                "product_count": 4
            }
        },
        "newsletter": {
            "type": "newsletter",
            "settings": {
                "heading": "Join Our Newsletter"
            }
        }
    },
    "order": ["hero", "featured-products", "newsletter"]
}
```

### Example 2: Product Page (product.json)

```json
{
    "layout": "theme",
    "wrapper": "main[role=main]",
    "sections": {
        "main": {
            "type": "product",
            "settings": {
                "show_vendor": true,
                "show_reviews": true
            }
        },
        "recommendations": {
            "type": "product-recommendations",
            "settings": {
                "heading": "You might also like"
            }
        }
    },
    "order": ["main", "recommendations"]
}
```

### Example 3: Product with Blocks (product.json)

```json
{
    "layout": "theme",
    "sections": {
        "main": {
            "type": "product",
            "settings": {
                "show_vendor": true
            },
            "blocks": {
                "tab-1": {
                    "type": "tab",
                    "settings": {
                        "title": "Description",
                        "content": "This is the product description"
                    }
                },
                "tab-2": {
                    "type": "tab",
                    "settings": {
                        "title": "Shipping",
                        "content": "Free shipping on orders over $50"
                    }
                }
            },
            "block_order": ["tab-1", "tab-2"]
        }
    },
    "order": ["main"]
}
```

### Example 4: Collection Page (collection.json)

```json
{
    "layout": "theme",
    "sections": {
        "header": {
            "type": "collection-header",
            "settings": {
                "show_image": true
            }
        },
        "filters": {
            "type": "collection-filters",
            "settings": {
                "show_price_filter": true,
                "show_type_filter": true
            }
        },
        "products": {
            "type": "collection-products",
            "settings": {
                "products_per_page": 12,
                "sort_options": ["newest", "price-asc", "price-desc"]
            }
        }
    },
    "order": ["header", "filters", "products"]
}
```

### Example 5: Alternate Product Template (product.subscription.json)

```json
{
    "layout": "theme",
    "sections": {
        "main": {
            "type": "product",
            "settings": {
                "show_vendor": false
            }
        },
        "subscription-details": {
            "type": "subscription-info",
            "settings": {
                "show_billing_cycle": true,
                "show_save_percentage": true
            }
        },
        "recommendations": {
            "type": "product-recommendations"
        }
    },
    "order": ["main", "subscription-details", "recommendations"]
}
```

---

## 9. Alternate Templates

Create **multiple versions** of the same template type for different use cases.

### Creating Alternates

Use the format: `{type}.{alternate}.json`

```
templates/
├── product.json                # Default product template
├── product.video.json          # For products with videos
├── product.subscription.json    # For subscription products
├── product.bundle.json         # For bundled products
└── product.digital.json        # For digital downloads
```

### Using Alternates

In the admin UI, merchants can **select which template variant** to use for each page/product.

### When to Create Alternates

Create alternates when:

- ✔ **Different page structures** — Video products need video section
- ✔ **Different workflows** — Subscriptions need subscription info
- ✔ **Different layouts** — Some products need sidebar, others full-width
- ✔ **A/B testing** — Different designs for conversion testing

---

## 10. Template Rendering Pipeline

### Rendering Process

```
1. Load template JSON
   ↓
2. Resolve layout (e.g., theme.blade.php)
   ↓
3. For each section in order array:
   ├─ Load section Blade file (type = sections/{type}.blade.php)
   ├─ Pass settings & blocks as data
   ├─ Render section with data
   └─ Append to output
   ↓
4. If wrapper specified, wrap all sections
   ↓
5. Inject into layout
   ↓
6. Render final HTML to browser
```

### Key Points

- **Order matters:** Sections render in the order specified in `order` array
- **Disabled sections:** Skipped during rendering (but editable in theme editor)
- **Schema validation:** Invalid settings ignored; defaults used instead
- **Caching:** Template JSON can be cached; sections rendered fresh each request

---

## 11. Template Context & Data Access

### Data Available in Sections

When a section renders, it has access to:

```php
// From template settings
$section['settings']         // Section-level settings
$section['blocks']           // Repeatable blocks with their settings

// From Laravel context
$page                        // Page object (if applicable)
$product                     // Product object (for product template)
$collection                  // Collection object (for collection template)

// From system
$currentUser                 // Authenticated user
$store                       // Store/app context
```

### Example: Accessing Data in Section

**Template (product.json):**

```json
{
    "sections": {
        "main": {
            "type": "product",
            "settings": {
                "show_reviews": true,
                "review_limit": 5
            }
        }
    }
}
```

**Section (sections/product.blade.php):**

```blade
<div class="product">
  <h1>{{ $product->name }}</h1>

  @if($section['settings']['show_reviews'])
    <div class="reviews">
      @foreach($product->reviews()->limit($section['settings']['review_limit'])->get() as $review)
        <div class="review">{{ $review->content }}</div>
      @endforeach
    </div>
  @endif
</div>
```

---

## 12. Validation Rules

### Template Validation

LSB validates templates before rendering:

| Check                                | Error                       | Fix                             |
| ------------------------------------ | --------------------------- | ------------------------------- |
| `sections` object missing            | `SectionsRequiredException` | Add `sections` object           |
| `order` array missing                | `OrderRequiredException`    | Add `order` array               |
| Section in `order` not in `sections` | `SectionNotFound`           | Add section to `sections`       |
| Duplicate IDs in `order`             | `DuplicateSectionId`        | Remove duplicates               |
| Section ID not alphanumeric          | `InvalidSectionId`          | Use only `[a-zA-Z0-9_-]`        |
| Sections exceed 25 limit             | `TooManySections`           | Remove sections                 |
| Blocks exceed 50 per section         | `TooManyBlocks`             | Remove blocks                   |
| Layout file not found                | `LayoutNotFound`            | Create layout file              |
| Section Blade file not found         | `SectionNotFound`           | Create section file             |
| Invalid wrapper element              | `InvalidWrapperElement`     | Use `div`, `main`, or `section` |

### Sample Validation Code

```php
namespace Coderstm\Services\PageBuilder;

use Coderstm\Exceptions\PageBuilder\ValidationException;

class TemplateValidator
{
    public function validate(array $template): bool
    {
        // Validate required fields
        if (!isset($template['sections'])) {
            throw ValidationException::missingField('sections');
        }

        if (!isset($template['order'])) {
            throw ValidationException::missingField('order');
        }

        // Validate section count
        if (count($template['sections']) > 25) {
            throw ValidationException::tooManySections();
        }

        // Validate order references sections
        foreach ($template['order'] as $id) {
            if (!isset($template['sections'][$id])) {
                throw ValidationException::sectionNotInOrder($id);
            }
        }

        // Validate no duplicates in order
        if (count($template['order']) !== count(array_unique($template['order']))) {
            throw ValidationException::duplicateSectionIds();
        }

        return true;
    }
}
```

---

## 13. Loading & Parsing Templates

### Template Loader Service

```php
namespace Coderstm\Services\PageBuilder;

use Coderstm\Contracts\BuilderFileStore;

class TemplateLoader
{
    public function __construct(
        protected BuilderFileStore $fileStore,
        protected TemplateValidator $validator
    ) {}

    /**
     * Load a template by name.
     *
     * @param string $templateName Template filename (without .json)
     * @return array Parsed template data
     * @throws TemplateNotFoundException
     * @throws ValidationException
     */
    public function load(string $templateName): array
    {
        $path = "templates/{$templateName}.json";

        if (!$this->fileStore->exists($path)) {
            throw new TemplateNotFoundException($templateName);
        }

        $json = $this->fileStore->read($path);
        $template = json_decode($json, true);

        // Validate before returning
        $this->validator->validate($template);

        return $template;
    }

    /**
     * Get all available templates.
     *
     * @return array Array of template names
     */
    public function all(): array
    {
        $files = $this->fileStore->listDirectory('templates');

        return array_map(function ($file) {
            return basename($file, '.json');
        }, $files);
    }
}
```

### Usage

```php
use Coderstm\Services\PageBuilder\TemplateLoader;

// Inject loader into controller
class PageController extends Controller
{
    public function show($slug, TemplateLoader $loader)
    {
        // Load template
        $template = $loader->load('product');

        // Render sections
        return PageBuilder::render($template);
    }
}
```

---

## 14. Template Caching Strategy

### Cache Keys

Cache templates at multiple levels:

```php
// Cache template JSON
$cache->remember("template:{$templateName}", 3600, function () {
    return $loader->load($templateName);
});

// Cache section availability for template type
$cache->remember("sections:available:product", 3600, function () {
    return $this->getAvailableSectionsForType('product');
});

// Cache section schema
$cache->remember("section:schema:{$sectionName}", 3600, function () {
    return $this->loader->loadSchema($sectionName);
});
```

### Cache Invalidation

Invalidate caches when templates change:

```php
class TemplateService
{
    public function save(string $name, array $template): void
    {
        $this->fileStore->write("templates/{$name}.json", json_encode($template));

        // Invalidate caches
        Cache::forget("template:{$name}");
        Cache::forget("sections:available:" . $this->getTemplateType($name));
    }

    public function delete(string $name): void
    {
        $this->fileStore->delete("templates/{$name}.json");
        Cache::forget("template:{$name}");
    }
}
```

---

## 15. Theme Editor Integration

### Editing Templates in Admin

The theme editor displays templates with:

1. **Section list** — All sections referenced in template
2. **Add section** — Button to add available sections
3. **Reorder** — Drag to reorder sections
4. **Remove** — Delete unwanted sections
5. **Settings** — Form for section/block settings

### Restrictions

- ✔ **Can add/remove** sections if defined in section schema's `presets`
- ✔ **Cannot add/remove** sections without presets (add manually in JSON)
- ✔ **Cannot edit** templates with `layout: false`
- ❌ **Cannot edit JSON directly** in most theme editors

### Presets

Sections must define presets in their schema to be available in theme editor:

```json
{
    "presets": [
        {
            "name": "Default Hero",
            "category": "Hero"
        }
    ]
}
```

---

## 16. API Endpoints for Templates

### RESTful Template API

```
# List templates
GET /api/templates

# Get single template
GET /api/templates/{templateName}

# Create template
POST /api/templates
Body: { "name": "product.special", "template": {...} }

# Update template
PUT /api/templates/{templateName}
Body: { "template": {...} }

# Delete template
DELETE /api/templates/{templateName}

# Get template by type
GET /api/templates/type/{type}
// Returns: product.json, product.video.json, product.subscription.json, ...

# Duplicate template
POST /api/templates/{templateName}/duplicate
Body: { "name": "product.new-variant" }
```

### Response Example

```json
{
    "name": "product",
    "layout": "theme",
    "wrapper": "main",
    "sections": {
        "main": {
            "type": "product",
            "settings": {
                "show_vendor": true
            }
        }
    },
    "order": ["main"],
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-20T14:45:00Z"
}
```

---

## 17. Best Practices

### ✅ DO

- ✔ Keep templates **focused on structure** — Logic belongs in sections
- ✔ Use **meaningful section IDs** — `hero`, `featured`, `testimonials`
- ✔ Provide **sensible defaults** in section settings
- ✔ Create **alternates for variations** — Don't force one template to fit all
- ✔ Document **custom template types** — Help merchants understand when to use each
- ✔ Cache templates aggressively — They don't change often
- ✔ Version templates — Track changes in git
- ✔ Test templates in **both admin and frontend**

### ❌ DON'T

- ❌ Put **HTML in templates** — Use sections instead
- ❌ Put **logic in templates** — Use section controllers
- ❌ Exceed **25 sections** per template
- ❌ Create **too many alternates** — Confuses merchants
- ❌ Hardcode **image URLs** — Use CDN/config
- ❌ Reference **missing sections** — Always validate
- ❌ Cache templates **too long** — Risk stale data (cache 1 hour max)
- ❌ Create **templates without validation** — Always validate on load

---

## 18. Troubleshooting

### Common Issues

**Issue:** Section doesn't appear in template  
**Cause:** Section ID in `sections` but not in `order`  
**Fix:** Add section ID to `order` array

```json
{
    "sections": {
        "hero": { "type": "hero" },
        "missing": { "type": "other" } // Not in order!
    },
    "order": ["hero"]
}
```

**Issue:** Template throws validation error  
**Cause:** Invalid JSON syntax  
**Fix:** Validate JSON using `json_decode()` and check `json_last_error()`

```php
$json = file_get_contents('template.json');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('Invalid JSON: ' . json_last_error_msg());
}
```

**Issue:** Section receives wrong data  
**Cause:** Template settings not matching section schema  
**Fix:** Check section schema for required/optional fields

```php
// Section schema defines:
"settings": [
  { "id": "title", "type": "text", "default": "Untitled" }
]

// Template passes:
"settings": {
  "title": "My Title"  // ✓ Correct
}
```

**Issue:** Wrapper HTML not appearing  
**Cause:** Invalid wrapper element  
**Fix:** Use only `div`, `main`, or `section`

```json
{
  "wrapper": "article"  // ❌ Invalid
}

{
  "wrapper": "div"  // ✓ Valid
}
```

---

## 19. Quick Reference

### Template Structure Checklist

```json
{
    "layout": "theme", // (optional) default: "theme"
    "wrapper": "main", // (optional) default: none
    "sections": {
        // (required) at least 1
        "section-id": {
            // alphanumeric ID
            "type": "section-name", // (required) section filename
            "disabled": false, // (optional) default: false
            "settings": {
                // (optional) section config
                "key": "value"
            },
            "blocks": {
                // (optional) repeatable items
                "block-id": {
                    "type": "block-type", // (required) block type
                    "settings": {
                        "key": "value"
                    }
                }
            },
            "block_order": ["block-id"] // (optional) block order
        }
    },
    "order": ["section-id"] // (required) render order
}
```

### Common Template Patterns

```json
// Single section template
{
  "sections": {
    "main": { "type": "main-section" }
  },
  "order": ["main"]
}

// Multiple sections
{
  "sections": {
    "hero": { "type": "hero" },
    "content": { "type": "content" },
    "cta": { "type": "call-to-action" }
  },
  "order": ["hero", "content", "cta"]
}

// With blocks
{
  "sections": {
    "main": {
      "type": "main-section",
      "blocks": {
        "block-1": { "type": "item" },
        "block-2": { "type": "item" }
      },
      "block_order": ["block-1", "block-2"]
    }
  },
  "order": ["main"]
}

// With layout and wrapper
{
  "layout": "full-width",
  "wrapper": "main.container",
  "sections": { /* ... */ }
}
```

---

## 20. Related Documentation

- [Project Specification](./project-specification.md) — Overview of all LSB features
- [Section System](./project-specification.md#9-section-system) — How sections work
- [Layout System](./layout-specification.md) — Layout templates
- [Theme System](./theme-specification.md) — Optional theme support

---

## 21. Summary

**Templates are the data layer of LSB.**

- ✅ JSON-only — No Blade/HTML code
- ✅ Section-based — Compose from reusable sections
- ✅ Merchant-editable — Add/remove/reorder in admin UI
- ✅ Type-based — Homepage, product, collection, custom types
- ✅ Validated — Full schema validation before rendering
- ✅ Cached — High-performance rendering
- ✅ Alternate-ready — Multiple variants per type
- ✅ Layout-aware — Wrap with layout or render standalone

**Next Steps:**

1. Review [Section System](./project-specification.md#9-section-system) to understand sections
2. Review [Layout System](./layout-specification.md) to understand layouts
3. Design your template types for your use cases
4. Create template JSON files in `resources/views/templates/`
5. Test templates with sections and layouts

---

**Shopify Reference Alignment:**

This specification is aligned with Shopify's template architecture:

- ✅ JSON-only templates (no markup)
- ✅ 25 sections per template limit
- ✅ 50 blocks per section limit
- ✅ Wrapper element support
- ✅ Layout configuration
- ✅ Alternate templates
- ✅ Validation & error handling
- ✅ Admin editor integration

**Laravel Adaptation:**

Adapted for Laravel with:

- ✅ Blade sections instead of Liquid sections
- ✅ Configurable file paths
- ✅ Theme system integration
- ✅ Service provider architecture
- ✅ File storage abstraction
