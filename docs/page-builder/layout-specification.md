# Laravel Section-Based Page Builder — Layout Specification

## Overview

Layouts are the **base foundation** of the page builder. They provide:

- Standard HTML document structure
- Fixed header/footer regions
- Content injection points
- Shared meta tags and scripts
- Template-specific CSS hooks

Layouts are **Blade files**, not JSON. They remain standard Laravel Blade templates.

---

## 1. Purpose & Role

Layouts serve as the wrapper for all page content. They:

- Define the HTML shell (`<!DOCTYPE>`, `<html>`, `<head>`, `<body>`)
- Include header/footer via `@sections()` directives
- Provide injection points for page-specific content
- Manage meta tags, scripts, and stylesheets
- Support template-specific CSS selectors for styling

**Key Rule:** Layouts control areas outside template content. Templates control body content.

---

## 2. Filesystem Location

```
resources/views/layouts/
├── theme.blade.php              // Default layout
├── blog.blade.php               // Blog-specific layout
├── checkout.blade.php           // Checkout layout
└── minimal.blade.php            // Alternative layout
```

**Optional Theme Mode:**

```
resources/themes/{theme-name}/layouts/
├── theme.blade.php
├── blog.blade.php
└── ...
```

---

## 3. Default Layout (theme.blade.php)

Every application must have a `theme.blade.php` layout. This is the fallback layout for all templates.

### Basic Structure

```blade
<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $page->description ?? config('app.description') }}">

    <title>{{ $page->title ?? config('app.name') }}</title>

    @include('snippets.meta')
    @include('snippets.styles')

    {{ $head ?? '' }}
</head>

<body class="template-{{ $template ?? 'default' }}">
    @sections('header-group')

    <main class="page-content">
        {{ $content }}
    </main>

    @sections('footer-group')

    @include('snippets.scripts')

    {{ $footer ?? '' }}
</body>
</html>
```

---

## 4. Layout Structure Components

### 4.1 HTML Head Section

Required elements in `<head>`:

```blade
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $page->title ?? config('app.name') }}</title>

<!-- SEO Meta Tags -->
<meta name="description" content="{{ $page->description }}">
<meta name="keywords" content="{{ $page->keywords }}">

<!-- OG Tags for Social Sharing -->
<meta property="og:title" content="{{ $page->title }}">
<meta property="og:description" content="{{ $page->description }}">
<meta property="og:image" content="{{ $page->image_url }}">
<meta property="og:url" content="{{ url()->current() }}">

<!-- CSS -->
<link rel="stylesheet" href="{{ asset('css/app.css') }}">

<!-- Additional Head Content -->
{{ $head ?? '' }}
```

### 4.2 Section Groups

Section groups (header, footer) are included via `@sections()` directive:

```blade
<!-- Header Section Group -->
@sections('header-group')

<!-- Footer Section Group -->
@sections('footer-group')
```

These render the JSON section group and dynamically load Blade files for each section.

### 4.3 Content Injection Point

The main content area receives rendered template content:

```blade
<main class="page-content">
    {{ $content }}
</main>
```

This `$content` variable contains the rendered template body.

### 4.4 Scripts Section

Scripts are loaded before closing `</body>` tag:

```blade
<!-- Google Analytics, Tracking, etc. -->
@include('snippets.analytics')

<!-- App JS -->
<script src="{{ asset('js/app.js') }}" defer></script>

<!-- Additional Footer Scripts -->
{{ $footer ?? '' }}
```

---

## 5. Template-Specific CSS Selectors

Output the template name as a CSS class on `<body>`:

```blade
<body class="template-{{ $template ?? 'default' }}">
```

This allows template-specific styling without per-template CSS files:

```css
/* Default styles */
.page-content {
    max-width: 1200px;
    margin: 0 auto;
}

/* Product-specific styles */
.template-product .page-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

/* Blog-specific styles */
.template-blog .page-content {
    max-width: 768px;
}

/* Home page styles */
.template-home .page-content {
    display: block;
}
```

---

## 6. Custom Layouts

Developers can create additional layouts for specific use cases:

### 6.1 Blog Layout

```blade
<!-- resources/views/layouts/blog.blade.php -->
<!DOCTYPE html>
<html>
<head>
    @include('snippets.meta')
    <title>{{ $page->title }} | Blog</title>
</head>

<body class="template-{{ $template }} layout-blog">
    @sections('header-group')

    <div class="blog-container">
        <aside class="sidebar">
            @sections('blog-sidebar')
        </aside>

        <main class="blog-content">
            {{ $content }}
        </main>
    </div>

    @sections('footer-group')

    @include('snippets.scripts')
</body>
</html>
```

### 6.2 Checkout Layout (Minimal)

```blade
<!-- resources/views/layouts/checkout.blade.php -->
<!DOCTYPE html>
<html>
<head>
    @include('snippets.meta')
    <title>Checkout</title>
</head>

<body class="template-checkout layout-minimal">
    <header class="checkout-header">
        <a href="{{ route('home') }}">Logo</a>
    </header>

    <main class="checkout-content">
        {{ $content }}
    </main>

    @include('snippets.scripts')
</body>
</html>
```

---

## 7. Specifying Layouts in Templates

### 7.1 In JSON Templates

Specify layout in template JSON:

```json
{
  "layout": "blog",
  "sections": { ... }
}
```

### 7.2 In Blade Templates (Fallback)

If a template is purely Blade, specify layout:

```blade
@layout('blog')

<!-- Template content here -->
```

If no layout is specified, the default `theme` layout is used.

---

## 8. Data Available in Layouts

Layouts receive the following data:

| Variable    | Type   | Description                                  |
| ----------- | ------ | -------------------------------------------- |
| `$content`  | string | Rendered template body HTML                  |
| `$page`     | object | Current page data (title, description, etc.) |
| `$template` | string | Current template name (for CSS selectors)    |
| `$head`     | string | Optional custom head content                 |
| `$footer`   | string | Optional custom footer content               |
| `$settings` | array  | Page-level settings from config              |

---

## 9. Best Practices

### 9.1 Do's ✔

- ✔ Keep layouts simple and structural
- ✔ Use semantic HTML (`<header>`, `<main>`, `<footer>`, `<article>`)
- ✔ Include required meta tags for SEO
- ✔ Use `@sections()` for reusable header/footer groups
- ✔ Output template name for CSS targeting
- ✔ Include analytics in layouts
- ✔ Keep layouts DRY using snippets

### 9.2 Don'ts ❌

- ❌ Do NOT put business logic in layouts
- ❌ Do NOT hardcode section references (use `@sections()` instead)
- ❌ Do NOT include template-specific content in layouts
- ❌ Do NOT load heavy scripts in `<head>` (use deferred or async)
- ❌ Do NOT duplicate header/footer HTML (use `@sections()`)

---

## 10. Snippets vs Layouts

**Key Difference:**

| Snippets                                     | Layouts                                      |
| -------------------------------------------- | -------------------------------------------- |
| Reusable partial HTML components             | Full page shell wrapper                      |
| Used inside sections or layouts              | Wraps templates                              |
| Small, focused pieces                        | Complete document structure                  |
| No content injection                         | Has content injection point                  |
| Example: `header.blade.php`, `nav.blade.php` | Example: `theme.blade.php`, `blog.blade.php` |

---

## 11. Layout Inheritance Chain

```
Layout (theme.blade.php)
    ↓
Template (e.g., home.json)
    ↓
Sections (e.g., hero.blade.php, features.blade.php)
    ↓
Snippets (e.g., button.blade.php, card.blade.php)
    ↓
HTML Output
```

---

## 12. Examples

### Example 1: Standard Blog Layout

```blade
<!-- resources/views/layouts/blog.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $page->title }} | Blog</title>
    @include('snippets.seo')
</head>

<body class="template-{{ $template }}">
    @sections('header-group')

    <article class="blog-post">
        {{ $content }}
    </article>

    <aside class="blog-sidebar">
        @include('snippets.recent-posts')
        @include('snippets.categories')
    </aside>

    @sections('footer-group')
</body>
</html>
```

### Example 2: Checkout Layout (No Header)

```blade
<!-- resources/views/layouts/checkout.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
</head>

<body class="template-checkout">
    <div class="checkout-wrapper">
        <header class="checkout-header">
            <a href="/">Store Logo</a>
        </header>

        <main class="checkout-content">
            {{ $content }}
        </main>
    </div>
</body>
</html>
```

### Example 3: Custom Product Page Layout

```blade
<!-- resources/views/layouts/product.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $page->title }}</title>
    <meta property="og:title" content="{{ $page->title }}">
    <meta property="og:image" content="{{ $page->image }}">
</head>

<body class="template-product">
    @sections('header-group')

    <div class="product-container">
        <main class="product-main">
            {{ $content }}
        </main>

        <aside class="product-sidebar">
            @sections('product-sidebar')
        </aside>
    </div>

    @sections('footer-group')
</body>
</html>
```

---

## 13. Configuration

Layouts use the same configuration as the page builder. No separate layout config is needed.

**Path Configuration:**

```php
// config/pagebuilder.php
'paths' => [
    'layouts' => resource_path('views/layouts'),
    // ...
],
```

---

## 14. Multi-Tenant Support

For multi-tenant applications, layouts can be isolated per tenant:

```php
// Runtime configuration per tenant
config([
    'pagebuilder.paths.layouts' => tenant_path('views/layouts'),
]);
```

Each tenant can have custom layouts without affecting others.

---

## Summary

Layouts in the Laravel Section-Based Page Builder:

- Are standard **Blade files** located in `layouts/`
- Provide the **HTML shell** for all pages
- Use `@sections()` for **reusable header/footer groups**
- Support **template-specific CSS selectors** via template name
- Can be **custom per use-case** (blog, checkout, etc.)
- Are specified in templates or default to `theme.blade.php`
- Keep **structural concerns separate** from content concerns

This keeps the architecture clean, DRY, and maintainable.
