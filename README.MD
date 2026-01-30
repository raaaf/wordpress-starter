# WordPress Starter Theme

A modern WordPress starter theme built with cutting-edge technologies and best practices.

## 🚀 Features

- **Vite 7.3** - Lightning fast build tool with HMR
- **Alpine.js v3.15** - Lightweight reactive framework (bundled, no CDN)
- **TailwindCSS v4.1** - Utility-first CSS framework with text shadows
- **Laravel Blade** - Powerful templating engine via Illuminate v12
- **ACF Pro + ACF Extended** - Flexible Content page builder with enhanced UX
- **TypeScript** - Full TypeScript support with WordPress/ACF types
- **PHP 8.4+** - Modern PHP with strict types (required for Laravel 12)
- **Service Provider Architecture** - Clean, modular code organization
- **Code Quality Tools** - ESLint, Prettier, PHPCS, PHPStan
- **Performance Optimizations** - Image optimization, compression, code splitting
- **Environment Configuration** - `.env` file support
- **Security First** - CSP headers with nonce support
- **Zero jQuery** - Lightweight, modern JavaScript
- **Rybbit Analytics** - Privacy-friendly analytics integration (via plugin)

## 📋 Requirements

- PHP 8.4 or higher
- Composer
- Node.js 20.19+ or 22.12+ and npm
- WordPress 6.8+
- ACF Pro 6.0+ (for ACF features)
- Local by Flywheel (recommended for Mac development)

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/raaaf/starter.git your-theme-name
cd your-theme-name
```

### 2. Run Setup Script

The theme includes an interactive setup wizard that configures everything for your project:

```bash
php bin/setup.php
```

The setup wizard will:
1. Configure theme name, author, and metadata
2. Update PHP namespace across all files
3. Let you select plugins to auto-install on theme activation
4. Configure initial content options (pages, permalinks)
5. Install dependencies automatically (composer install, npm install, npm run build)
6. Create `.env` file from example

### 3. Activate Theme & Start Development

After setup, activate the theme in WordPress (Design → Themes). For development with hot reload:

```bash
npm run dev
```

## 🏗️ Project Structure

```
wordpress-starter/
├── acf-json/           # ACF field group JSON files
├── compiled/          # Blade template cache
├── config/            # Configuration files
│   ├── app.php       # Application config
│   └── functions.php # Bootstrap file
├── dist/             # Compiled assets (gitignored)
├── resources/        # Source assets
│   ├── css/         # CSS files
│   │   ├── app.css          # Main stylesheet
│   │   └── editor-style.css # Editor styles
│   ├── js/          # TypeScript/JavaScript files
│   │   └── app.ts   # Main TypeScript entry
│   └── fonts/       # Font files
├── src/              # PHP source code
│   ├── Acf/         # ACF helper classes
│   │   ├── AcfExtended.php     # ACF Extended configuration
│   │   ├── FieldDefinitions.php # Shared field definitions
│   │   ├── Fields.php          # Field helpers
│   │   ├── FlexibleContent.php # Flexible Content layouts (28 layouts)
│   │   └── Options.php         # Options pages
│   ├── Providers/   # Service providers
│   │   ├── AcfServiceProvider.php
│   │   ├── AnalyticsServiceProvider.php
│   │   ├── BladeServiceProvider.php
│   │   ├── MenuServiceProvider.php
│   │   ├── PluginServiceProvider.php
│   │   ├── SecurityServiceProvider.php
│   │   ├── ThemeServiceProvider.php
│   │   └── WelcomeServiceProvider.php
│   ├── types/       # TypeScript definitions
│   ├── Application.php
│   ├── Config.php
│   ├── EditorConfig.php # Classic Editor configuration
│   ├── Security.php
│   ├── Vite.php
│   └── helpers.php
├── templates/        # Blade templates
│   ├── layouts/     # Layout templates
│   ├── flexible/    # Flexible Content layout templates (28 templates)
│   ├── components/  # Blade components
│   └── partials/    # Partial templates
├── .env.example      # Environment example
├── eslint.config.js # ESLint 9 flat config
├── .gitignore       # Git ignore rules
├── .prettierrc      # Prettier configuration
├── composer.json    # PHP dependencies
├── package.json     # Node dependencies
├── phpcs.xml       # PHP CodeSniffer config
├── phpstan.neon    # PHPStan config
├── postcss.config.js # PostCSS config
├── safelist.txt    # TailwindCSS safelist
├── style.css       # Theme header (generated)
├── bin/
│   └── setup.php   # Interactive setup wizard
├── tsconfig.json   # TypeScript config
├── vite.config.js  # Vite configuration
└── CLAUDE.md       # AI assistant guidelines
```

## 🔧 Development

### Available Commands

#### JavaScript/TypeScript
```bash
# Start development server with HMR
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Analyze bundle size
npm run analyze

# Lint JavaScript/TypeScript
npm run lint
npm run lint:fix

# Format code with Prettier
npm run format
```

#### PHP
```bash
# Run PHP CodeSniffer
composer phpcs
composer phpcs:fix

# Run PHPStan analysis
composer phpstan

# Run all PHP linters
composer lint

# Run PHP unit tests
composer test
```

#### Testing
```bash
# Run all tests
npm test
composer test

# Run JavaScript tests with coverage
npm run test:coverage

# Run PHP tests
./vendor/bin/phpunit

# Run E2E tests (requires running WordPress)
npm run test:e2e

# Run E2E tests with UI
npm run test:e2e:ui

# Run accessibility tests only
npm run test:a11y
```

See [tests/README.md](tests/README.md) for detailed testing documentation.

### Service Providers

The theme uses a service provider architecture for clean code organization:

- `BladeServiceProvider` - Blade templating engine setup
- `MenuServiceProvider` - Navigation menu registration
- `ThemeServiceProvider` - Core WordPress theme features, SEO schemas
- `SecurityServiceProvider` - Security headers and CSP
- `AcfServiceProvider` - ACF integration, blocks, and directives
- `SecurityServiceProvider` - Security headers and CSP (Rybbit via plugin)
- `PluginServiceProvider` - Auto-install required plugins on activation
- `WelcomeServiceProvider` - Theme activation welcome notice
- `ImageServiceProvider` - Custom image sizes
- `PostTypeServiceProvider` - Custom post types and taxonomies
- `LogServiceProvider` - Error logging to debug.log
- `CronServiceProvider` - Scheduled maintenance tasks
- `ThemeUpdateProvider` - GitHub-based theme updates

### Adding New Features

1. Create a new service provider in `src/Providers/`
2. Register it in `src/Application.php`
3. Use dependency injection and modern PHP practices

### Blade Templates

Templates use Laravel's Blade syntax with ACF directives:

```blade
@extends('layouts.app')

@section('content')
    <div class="container">
        {{-- Regular WordPress loop --}}
        @loop
            <article>
                <h2>{{ get_the_title() }}</h2>
                <div>{!! get_the_content() !!}</div>
            </article>
        @endloop
        
        {{-- ACF Fields --}}
        @hasfield('hero_title')
            <h1>@field('hero_title')</h1>
        @endhasfield
        
        {{-- ACF Repeater --}}
        @repeater('items')
            <div>{{ $item['title'] }}</div>
        @endrepeater
        
        {{-- ACF Options --}}
        <p>@option('site_copyright')</p>
    </div>
@endsection
```

### Alpine.js Components

Create reactive components with TypeScript:

```typescript
// In resources/js/app.ts
import Alpine from 'alpinejs';

// Define component with TypeScript
Alpine.data('dropdown', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    }
}));

Alpine.start();
```

```blade
{{-- In templates --}}
<div x-data="dropdown">
    <button @click="toggle()">Menu</button>
    <div x-show="open" x-transition>
        <!-- Menu content -->
    </div>
</div>
```

### Design Tokens

The theme uses a token-based design system in `resources/css/tokens.css`, auto-generated from Figma:

**Light/Dark Mode:**
```html
<!-- Toggle dark mode -->
<html data-theme="dark">
```

Or let it follow system preference automatically via `prefers-color-scheme`.

**Semantic Tokens:**
```css
/* Background */
background: var(--bg-primary);     /* White / Dark gray */
background: var(--bg-brand);       /* Brand color */
background: var(--bg-brand-subtle); /* Light brand tint */

/* Text */
color: var(--text-primary);   /* Main text */
color: var(--text-secondary); /* Muted text */
color: var(--text-brand);     /* Brand color */

/* Border */
border-color: var(--border-default);
border-color: var(--border-brand);
```

**Component Tokens:**
- Button sizes: `--button-sm-*`, `--button-md-*`, `--button-lg-*`
- Input styles: `--input-sm-*`, `--input-md-*`, `--input-lg-*`
- Card styles: `--card-*`
- Shadows: `--shadow-button`, `--shadow-card`, `--shadow-input`

## 🚀 Production Build

```bash
npm run build
```

This will:
- Minify all assets with Terser
- Generate hashed filenames for cache busting
- Optimize images with imagemin
- Create gzip and brotli compressed versions
- Generate legacy browser bundles
- Split code into vendor chunks
- Tree shake unused code
- Generate bundle analysis report

## 📊 Analytics with Rybbit

The theme includes built-in support for [Rybbit Analytics](https://rybbit.io/), a privacy-friendly, cookie-free analytics solution.

### Setting up Rybbit

1. Install the [Rybbit WordPress Plugin](https://github.com/maki-it/rybbit-wordpress-plugin/releases)
2. Configure the plugin under `Settings → Rybbit Analytics`
3. Enter your Site ID from the Rybbit Dashboard

### Event Tracking

The theme automatically tracks interactions on various elements using Rybbit's declarative tracking:

#### Tracked Events:
- **CTA Clicks** - Call to action button clicks with block context
- **Hero CTA Clicks** - Hero section button clicks with button text
- **Link Clicks** - Internal and external link clicks with URL and text
- **Image Link Clicks** - Clicks on linked images

#### Event Attributes:
All events include metadata for better insights:
- `data-rybbit-event` - The event name (e.g., "External_Link_Click", "button_click")
- `data-rybbit-prop-key` - The block type (e.g., "content_link", "image_block")
- Additional properties via `data-rybbit-prop-*` attributes

#### Adding Custom Events:
To track custom events, add data attributes to any element:

```html
<button data-rybbit-event="Custom_Action"
        data-rybbit-prop-key="custom_block"
        data-rybbit-prop-custom="value">
    Click Me
</button>
```

Or use JavaScript:
```javascript
rybbit.event('Custom_Action', { key: 'custom_block', custom: 'value' });
```

### Privacy Compliance

Rybbit Analytics is:
- GDPR compliant
- Cookie-free
- No personal data collection
- Self-hostable for full data control

## 🔒 Security

The theme implements several security best practices:

- Content Security Policy (CSP) headers with configurable directives
- Nonce-based script execution
- XSS protection with proper escaping
- Secure defaults for all user inputs
- TypeScript for type safety
- PHPStan for static analysis

## 📝 Configuration

### Environment Variables

Key environment variables in `.env`:

```env
# Development
VITE_DEV_SERVER_HOST=localhost
VITE_DEV_SERVER_PORT=5173
BROWSERSYNC_PROXY=http://yoursite.local

# Features
ENABLE_CSP=true
LOAD_CRITICAL_CSS=true

# Debug
WP_DEBUG=true
WP_DEBUG_LOG=true
WP_DEBUG_DISPLAY=false
```

### Theme Configuration

Edit `config/app.php` for theme-specific settings.

### ACF Configuration

1. **Local JSON**: Field groups are automatically synced to `acf-json/`
2. **Options Pages**: Configured in `src/Acf/Options.php`
3. **Flexible Content**: Layouts defined in `src/Acf/FlexibleContent.php`
4. **ACF Extended**: Configuration in `src/Acf/AcfExtended.php`

### TypeScript Configuration

TypeScript is pre-configured with:
- WordPress global types
- ACF function types
- Alpine.js types
- Strict mode enabled

## 🛠️ Local Development with Local by Flywheel

For Mac users, we recommend using [Local by Flywheel](https://localwp.com/) for WordPress development:

1. Download and install Local
2. Create a new WordPress site
3. Clone this theme into `wp-content/themes/`
4. Run the setup script
5. Activate the theme in WordPress admin

## 🎨 Working with ACF Flexible Content

The theme uses **Classic Editor + ACF Flexible Content** as the page builder. Gutenberg is disabled.

### Page Builder Architecture

- **28 Flexible Content layouts** defined in `src/Acf/FlexibleContent.php`
- **ACF Extended (FREE)** provides enhanced UX with modal selection, copy/paste, and categories
- Templates in `templates/flexible/` render each layout

### Pre-built Layouts

**Layout:**
- **one-column** - Single column content
- **two-columns** - Two equal columns
- **three-columns** - Three column layout
- **four-columns** - Four column layout
- **one-third-two-thirds** - Asymmetric (1/3 + 2/3)
- **two-thirds-one-third** - Asymmetric (2/3 + 1/3)
- **two-columns-images** - Columns with images

**Content:**
- **hero** - Hero section with background
- **accordion** - Expandable FAQ with Schema.org
- **cta** - Call to action
- **image** - Single image
- **video** - Media or YouTube/Vimeo
- **divider** - Decorative spacer
- **button** - Standalone CTA

**Interactive:**
- **testimonials** - Customer quotes
- **cards** - Feature cards with icons
- **gallery** - Image gallery with lightbox
- **logo-slider** - Partner logo carousel
- **contact-form** - Contact Form 7 integration
- **map** - Google Maps with GDPR consent
- **tabs** - Tabbed content

**Data:**
- **pricing-table** - Pricing plans
- **team** - Team member profiles
- **stats** - Animated counters
- **timeline** - Chronological events
- **posts** - Dynamic blog posts
- **before-after** - Image comparison slider
- **table** - Data table

### Creating New Layouts

1. Add layout method in `src/Acf/FlexibleContent.php`:
```php
private static function myNewLayout(): array
{
    return [
        'key' => 'layout_my_new',
        'name' => 'my_new',
        'label' => 'My New Layout',
        'display' => 'block',
        'sub_fields' => FieldDefinitions::myNewFields('flex_my_new'),
        'acfe_flexible_category' => self::CATEGORIES['content'],
    ];
}
```

2. Add field definitions in `src/Acf/FieldDefinitions.php`

3. Create template `templates/flexible/my-new.blade.php`:
```blade
@php
    $title = get_sub_field('title');
    $background = get_sub_field('background_color') ?: 'primary';
@endphp

<x-section :background="$background">
    @if($title)
        <h2>{{ $title }}</h2>
    @endif
</x-section>
```

4. Register in `getLayouts()` array

### ACF Helper Functions

```php
use WordpressStarter\Acf\Fields;

// Get field with default
$title = Fields::get('title', null, 'Default Title');

// Get image with size
$image = Fields::image('hero_image', 'large');

// Get repeater field
$items = Fields::repeater('items');

// Get option with caching
$footer = Fields::option('footer_text');
```

## 📝 Custom Post Types

Create custom post types using the abstract base class:

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

Register in `PostTypeServiceProvider`:

```php
Service::register();
```

## 📚 Documentation

Detailed documentation is available in the `docs/` folder:

- [ARCHITECTURE.md](docs/ARCHITECTURE.md) - Service provider pattern and bootstrap flow
- [DEPLOYMENT.md](docs/DEPLOYMENT.md) - Production deployment guide
- [SECURITY.md](docs/SECURITY.md) - Security practices and CSP
- [SEO.md](docs/SEO.md) - SEO implementation and structured data
- [DESIGN-TOKENS.md](docs/DESIGN-TOKENS.md) - Design token system

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Run linters before committing:
   ```bash
   npm run lint
   composer lint
   ```
4. Commit your changes (`git commit -m 'Add some amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

## 📜 License

This theme is open source and available under the [MIT License](LICENSE).

## 🙏 Credits

Built with ❤️ using:
- [Laravel Blade](https://laravel.com/docs/blade)
- [Alpine.js](https://alpinejs.dev/)
- [TailwindCSS](https://tailwindcss.com/)
- [Vite](https://vitejs.dev/)
- [ACF Pro](https://www.advancedcustomfields.com/)
- [TypeScript](https://www.typescriptlang.org/)
- [Local by Flywheel](https://localwp.com/)

---

Happy coding! 🚀