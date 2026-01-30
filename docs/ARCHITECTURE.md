# Architecture

This document describes the architecture and design patterns used in the WordPress Starter Theme.

## Overview

The theme uses a **Service Provider pattern** inspired by Laravel, providing a clean separation of concerns and modular architecture.

```
┌─────────────────────────────────────────────────────────────┐
│                     WordPress                                │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                  functions.php                        │   │
│  │                       ↓                               │   │
│  │               Application.php                         │   │
│  │                       ↓                               │   │
│  │  ┌─────────────────────────────────────────────┐     │   │
│  │  │            Service Providers                 │     │   │
│  │  │  ┌─────────┐ ┌─────────┐ ┌─────────┐       │     │   │
│  │  │  │ Security│ │  Blade  │ │   ACF   │ ...   │     │   │
│  │  │  └─────────┘ └─────────┘ └─────────┘       │     │   │
│  │  └─────────────────────────────────────────────┘     │   │
│  └─────────────────────────────────────────────────────┘   │
│                           ↓                                  │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                 Blade Templates                       │   │
│  │  layouts/ → partials/ → components/ → flexible/      │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Bootstrap Flow

1. **functions.php** → Loads `config/functions.php`
2. **config/functions.php** → Composer autoloader, helpers, Vite, Application boot
3. **Application::boot()** → Two-phase provider initialization:
   - **Phase 1 (Register)**: Providers bind services to container
   - **Phase 2 (Boot)**: Providers hook into WordPress

```php
// Simplified bootstrap flow
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/helpers.php';
\WordpressStarter\Vite::init();
\WordpressStarter\Application::getInstance()->boot();
```

## Service Providers

Service providers are the central place for bootstrapping theme functionality.

### Provider Lifecycle

1. **Constructor** - Called when provider is instantiated
2. **register()** - Bind services to container (no WordPress hooks yet)
3. **boot()** - Hook into WordPress, access other services

### Registered Providers

| Provider                  | Responsibility                       |
| ------------------------- | ------------------------------------ |
| `PluginServiceProvider`   | Plugin recommendations, setup wizard |
| `WelcomeServiceProvider`  | Theme activation, onboarding         |
| `SecurityServiceProvider` | CSP headers, nonce generation        |
| `BladeServiceProvider`    | Blade templating engine              |
| `AcfServiceProvider`      | ACF configuration, directives        |
| `MenuServiceProvider`     | Navigation menus                     |
| `ThemeServiceProvider`    | Core theme features, SEO             |
| `ImageServiceProvider`    | Custom image sizes                   |
| `SeoServiceProvider`      | SEO enhancements                     |
| `ThemeUpdateProvider`     | GitHub-based updates                 |
| `PostTypeServiceProvider` | Custom post types                    |

### Creating a New Provider

```php
<?php

namespace WordpressStarter\Providers;

class MyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind services to container
    }

    public function boot(): void
    {
        // Hook into WordPress
        add_action('init', [$this, 'init']);
    }

    public function init(): void
    {
        // ...
    }
}
```

Register in `Application.php`:

```php
$this->providers = [
    // ...existing providers...
    MyServiceProvider::class,
];
```

## Directory Structure

```
src/
├── Acf/                    # ACF configuration
│   ├── FieldDefinitions.php  # Reusable field configs
│   ├── Fields.php           # Blade directive helpers
│   ├── FlexibleContent.php  # Page builder layouts
│   ├── Options.php          # Theme options pages
│   └── AcfExtended.php      # ACF Extended config
├── PostTypes/              # Custom Post Types
│   ├── AbstractPostType.php # Base CPT class
│   └── Testimonial.php      # Example CPT
├── Taxonomies/             # Custom Taxonomies
│   └── AbstractTaxonomy.php # Base taxonomy class
├── Providers/              # Service providers
│   ├── ServiceProvider.php  # Base provider class
│   └── *.php               # Individual providers
├── Application.php         # IoC container & bootstrap
├── Config.php              # Configuration loader
├── EditorConfig.php        # Gutenberg disabler
├── RateLimiter.php         # AJAX rate limiting
├── Security.php            # CSP implementation
└── Vite.php                # Vite asset integration

templates/
├── layouts/
│   └── app.blade.php       # Base layout
├── partials/
│   ├── header.blade.php    # Site header
│   ├── footer.blade.php    # Site footer
│   └── *.blade.php         # Other partials
├── components/
│   ├── section.blade.php   # Section wrapper
│   ├── button.blade.php    # Button component
│   └── *.blade.php         # Other components
├── flexible/
│   └── *.blade.php         # Flexible Content layouts
├── page.blade.php          # Default page template
├── single.blade.php        # Single post template
└── *.blade.php             # Other templates

resources/
├── css/
│   ├── app.css             # Main stylesheet
│   └── tokens.css          # Design tokens (generated)
├── js/
│   └── app.ts              # Main JavaScript/Alpine.js
├── fonts/                  # Custom fonts
├── icons/                  # SVG icons
└── images/                 # Theme images
```

## IoC Container

The theme uses Laravel's IoC container for dependency injection:

```php
// Bind a service
$app->bind(MyService::class, function ($container) {
    return new MyService($container->make(Dependency::class));
});

// Resolve a service
$service = $app->make(MyService::class);

// Singleton binding
$app->singleton(MyService::class, MyService::class);
```

## Blade Templating

Blade templates use Laravel's Illuminate View package:

### Template Hierarchy

1. Custom page template (e.g., `page-home.blade.php`)
2. `page-{slug}.blade.php`
3. `page-{id}.blade.php`
4. `page.blade.php`

### Custom Directives

```blade
{{-- ACF Fields --}}
@field('field_name')           {{-- Escaped output --}}
@fieldRaw('field_name')        {{-- HTML output --}}
@option('option_name')         {{-- Theme option --}}

{{-- Conditionals --}}
@hasfield('field_name')
    ...
@endhasfield

{{-- Repeaters --}}
@repeater('repeater_field')
    {{ get_sub_field('sub_field') }}
@endrepeater

{{-- Flexible Content --}}
@flexible('page_content')
    @layout('hero')
        @include('flexible.hero')
    @endlayout
@endflexible
```

## ACF Flexible Content

The page builder uses ACF Flexible Content with 28 layouts organized by category.

### Adding a New Layout

1. Define layout in `FlexibleContent.php`:

```php
private static function myLayout(): array
{
    return [
        'key' => 'layout_my_layout',
        'name' => 'my_layout',
        'label' => 'My Layout',
        'display' => 'block',
        'sub_fields' => FieldDefinitions::myLayoutFields('flex_my_layout'),
        'acfe_flexible_category' => self::CATEGORIES['content'],
    ];
}
```

2. Add field definitions in `FieldDefinitions.php`

3. Create template `templates/flexible/my-layout.blade.php`

4. Register in `getLayouts()` array

## Custom Post Types

Use the abstract base class for consistent CPT registration:

```php
<?php

namespace WordpressStarter\PostTypes;

class Service extends AbstractPostType
{
    protected static string $postType = 'service';
    protected static string $singular = 'Leistung';
    protected static string $plural = 'Leistungen';
    protected static string $menuIcon = 'dashicons-admin-generic';

    public static function registerFields(): void
    {
        // Register ACF fields
    }
}
```

## Design Patterns Used

- **Service Provider Pattern** - Modular bootstrapping
- **Singleton Pattern** - Application instance
- **Factory Pattern** - ACF field definitions
- **Template Method Pattern** - Abstract post types
- **Strategy Pattern** - Background color variants

## Best Practices

1. **Single Responsibility** - Each provider handles one concern
2. **Dependency Injection** - Use container for dependencies
3. **Configuration over Code** - Use ACF for content structure
4. **Composition over Inheritance** - Use traits and composition
5. **Type Safety** - Strict types and PHPStan level 9
