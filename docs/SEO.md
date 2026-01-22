# SEO Guide

This document describes the SEO features implemented in the WordPress Starter Theme.

## Structured Data (JSON-LD)

The theme automatically outputs Schema.org structured data for better search engine understanding.

### Implemented Schemas

#### WebSite Schema

Output on all pages:

```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Site Name",
  "url": "https://example.com",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://example.com/?s={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
```

#### Organization Schema

Output when company info is configured in Theme Options:

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Company Name",
  "url": "https://example.com",
  "logo": "https://example.com/logo.png",
  "telephone": "+49 123 456789",
  "email": "info@example.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Street 123, 12345 City"
  }
}
```

#### Article Schema

Output on single blog posts:

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Post Title",
  "url": "https://example.com/post-slug/",
  "datePublished": "2024-01-15T10:00:00+00:00",
  "dateModified": "2024-01-16T14:30:00+00:00",
  "author": {
    "@type": "Person",
    "name": "Author Name"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Site Name",
    "url": "https://example.com"
  },
  "image": "https://example.com/featured-image.jpg",
  "description": "Post excerpt..."
}
```

#### BreadcrumbList Schema

Output on all pages except front page:

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://example.com/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Current Page"
    }
  ]
}
```

#### FAQPage Schema

Output on accordion layouts with FAQ content:

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Question text?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Answer text..."
      }
    }
  ]
}
```

## Open Graph Tags

Automatically generated for social sharing:

```html
<meta property="og:type" content="website" />
<meta property="og:title" content="Page Title" />
<meta property="og:description" content="Page description" />
<meta property="og:url" content="https://example.com/page/" />
<meta property="og:site_name" content="Site Name" />
<meta property="og:locale" content="de_DE" />
<meta property="og:image" content="https://example.com/image.jpg" />

<!-- Article-specific -->
<meta property="article:published_time" content="2024-01-15T10:00:00+00:00" />
<meta property="article:modified_time" content="2024-01-16T14:30:00+00:00" />
<meta property="article:author" content="Author Name" />
```

## Twitter Card Tags

Automatically generated:

```html
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="Page Title" />
<meta name="twitter:description" content="Page description" />
<meta name="twitter:image" content="https://example.com/image.jpg" />
```

## Canonical URLs

Canonical URLs are automatically generated when Yoast SEO is not active:

```html
<link rel="canonical" href="https://example.com/page/" />
```

The theme handles:

- Single posts/pages
- Front page
- Blog page
- Post type archives
- Date/author archives
- Search pages

## Sitemap

**Note:** Sitemap generation is handled by Yoast SEO (recommended plugin).

When Yoast SEO is active:

- XML sitemaps are generated automatically
- Located at `/sitemap_index.xml`
- Includes all public post types
- Automatically pings search engines

### robots.txt Reference

```
Sitemap: https://example.com/sitemap_index.xml
```

## Meta Tags Best Practices

### Title Tags

WordPress handles title tags via `add_theme_support('title-tag')`.

Customize with Yoast SEO or filter:

```php
add_filter('pre_get_document_title', function ($title) {
    if (is_front_page()) {
        return 'Custom Homepage Title';
    }
    return $title;
});
```

### Meta Descriptions

Set via Yoast SEO or ACF custom field.

For programmatic descriptions:

```php
add_action('wp_head', function () {
    if (is_singular()) {
        $description = get_the_excerpt();
        echo '<meta name="description" content="' . esc_attr($description) . '">';
    }
});
```

## Image SEO

### Alt Text

Always add descriptive alt text to images:

```blade
<img src="{{ $image['url'] }}"
     alt="{{ $image['alt'] ?: $image['title'] }}"
     width="{{ $image['width'] }}"
     height="{{ $image['height'] }}">
```

### Lazy Loading

Built-in lazy loading for images below the fold:

```blade
<img src="{{ $image['url'] }}"
     alt="{{ $image['alt'] }}"
     loading="lazy"
     decoding="async">
```

### WebP Support

The theme generates responsive images with srcset:

```php
echo wp_get_attachment_image($imageId, 'large', false, [
    'loading' => 'lazy',
    'decoding' => 'async',
]);
```

## Performance for SEO

### Core Web Vitals

The theme is optimized for Core Web Vitals:

- **LCP**: Critical fonts preloaded, optimized images
- **FID**: Minimal JavaScript, deferred loading
- **CLS**: Fixed image dimensions, layout stability

### Preloading

Critical assets are preloaded in `header.blade.php`:

```html
<link rel="preload" href="/dist/app.css" as="style" />
<link rel="preload" href="/dist/app.js" as="script" />
<link rel="preload" href="/fonts/main.woff2" as="font" type="font/woff2" crossorigin />
```

## Yoast SEO Integration

When Yoast SEO is installed:

1. **Schema**: Theme schemas coexist with Yoast's
2. **Canonical URLs**: Theme defers to Yoast
3. **Breadcrumbs**: Use `@include('partials.breadcrumbs')` for Yoast breadcrumbs
4. **ACF Integration**: Install "ACF Content Analysis for Yoast SEO" plugin

## Content Guidelines

### Headings

- One `<h1>` per page (typically the title)
- Logical heading hierarchy (h1 → h2 → h3)
- Descriptive, keyword-rich headings

### Links

- Descriptive anchor text (not "click here")
- External links: `rel="noopener noreferrer"`
- Internal linking for important pages

### Content Structure

- Short paragraphs (3-4 sentences)
- Bullet points for lists
- Tables for structured data
- Clear call-to-actions

## Testing SEO

### Tools

- [Google Search Console](https://search.google.com/search-console)
- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [Schema Markup Validator](https://validator.schema.org/)
- [PageSpeed Insights](https://pagespeed.web.dev/)
- [Ahrefs Site Audit](https://ahrefs.com/site-audit)

### Validation Steps

1. Test structured data with Rich Results Test
2. Check Open Graph with Facebook Sharing Debugger
3. Verify Twitter Card with Twitter Card Validator
4. Run PageSpeed Insights for performance
5. Check mobile-friendliness with Mobile-Friendly Test

## Adding Custom Schemas

### In Theme Options

Add fields in `src/Acf/Options.php` for custom schema data.

### Programmatically

Use the Spatie Schema.org package:

```php
use Spatie\SchemaOrg\Schema;

$localBusiness = Schema::localBusiness()
    ->name('Business Name')
    ->address(
        Schema::postalAddress()
            ->streetAddress('Street 123')
            ->addressLocality('City')
            ->postalCode('12345')
    )
    ->telephone('+49 123 456789')
    ->openingHours('Mo-Fr 09:00-18:00');

echo $localBusiness->toScript();
```

## Troubleshooting

### Schema Not Appearing

1. Check if conditions are met (e.g., company_name field filled)
2. View page source for `application/ld+json` scripts
3. Test with Schema Validator

### Duplicate Schemas

When using Yoast SEO, some schemas may duplicate. This is generally fine as search engines handle it, but you can disable theme schemas:

```php
// Disable theme Organization schema
add_filter('wp_starter_output_organization_schema', '__return_false');
```

### Missing Open Graph Image

1. Set featured image on post
2. Or configure site logo in Theme Options
3. Check image minimum size (1200×630 recommended)
