# WordPress Starter Theme

A modern WordPress starter theme built with cutting-edge technologies and best practices.

## 🚀 Features

- **Vite 7.3** - Lightning fast build tool with HMR
- **Alpine.js v3.15** - Lightweight reactive framework (bundled, no CDN)
- **TailwindCSS v4.1** - Utility-first CSS framework with text shadows
- **Laravel Blade** - Powerful templating engine via Illuminate v12
- **ACF Pro Integration** - Helper functions, Blade directives, and block support
- **TypeScript** - Full TypeScript support with WordPress/ACF types
- **PHP 8.4+** - Modern PHP with strict types (required for Laravel 12)
- **Service Provider Architecture** - Clean, modular code organization
- **Code Quality Tools** - ESLint, Prettier, PHPCS, PHPStan
- **Performance Optimizations** - Image optimization, compression, code splitting
- **Environment Configuration** - `.env` file support
- **Security First** - CSP headers with nonce support
- **Zero jQuery** - Lightweight, modern JavaScript
- **Pirsch Analytics** - Privacy-friendly analytics integration

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
git clone https://github.com/yourusername/wordpress-starter.git your-theme-name
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
├── blocks/             # ACF Gutenberg blocks (28 blocks included)
│   ├── accordion/     # Expandable accordion (FAQ)
│   ├── before-after/  # Image comparison slider
│   ├── cards/        # Feature cards with icons
│   ├── contact-form/ # Contact Form 7 integration
│   ├── cta/          # Call to action block
│   ├── divider/      # Decorative divider
│   ├── gallery/      # Image gallery with lightbox
│   ├── hero/         # Hero section with background
│   ├── image/        # Single image block
│   ├── logo-slider/  # Partner/client logos
│   ├── map/          # Google Maps with GDPR consent
│   ├── posts/        # Dynamic blog posts
│   ├── pricing-table/# Pricing plans
│   ├── stats/        # Animated counters
│   ├── tabs/         # Tabbed content
│   ├── table/        # Data table
│   ├── team/         # Team members
│   ├── testimonials/ # Customer quotes
│   ├── timeline/     # Chronological events
│   └── [layout blocks]/ # Column layouts (1-4 columns, asymmetric)
├── compiled/          # Blade template cache
├── config/            # Configuration files
│   ├── app.php       # Application config
│   └── functions.php # Bootstrap file
├── dist/             # Compiled assets (gitignored)
├── resources/        # Source assets
│   ├── css/         # CSS files
│   │   ├── app.css          # Main stylesheet
│   │   ├── editor-style.css # Editor styles
│   │   └── block-editor.css # Block editor styles
│   ├── js/          # TypeScript/JavaScript files
│   │   └── app.ts   # Main TypeScript entry
│   └── fonts/       # Font files
├── src/              # PHP source code
│   ├── Acf/         # ACF helper classes
│   │   ├── BlockFields.php     # Block field registration
│   │   ├── Blocks.php          # Block registration
│   │   ├── FieldDefinitions.php # Shared field definitions
│   │   ├── Fields.php          # Field helpers
│   │   ├── FlexibleContent.php # Flexible content layouts
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
│   ├── Security.php
│   ├── Vite.php
│   └── helpers.php
├── templates/        # Blade templates
│   ├── layouts/     # Layout templates
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
```

### Service Providers

The theme uses a service provider architecture for clean code organization:

- `BladeServiceProvider` - Blade templating engine setup
- `MenuServiceProvider` - Navigation menu registration
- `ThemeServiceProvider` - Core WordPress theme features
- `SecurityServiceProvider` - Security headers and CSP
- `AcfServiceProvider` - ACF integration, blocks, and directives
- `AnalyticsServiceProvider` - Pirsch Analytics integration
- `PluginServiceProvider` - Auto-install required plugins on activation
- `WelcomeServiceProvider` - Theme activation welcome notice

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

## 📊 Analytics with Pirsch

The theme includes built-in support for [Pirsch Analytics](https://pirsch.io/), a privacy-friendly, cookie-free analytics solution.

### Setting up Pirsch

1. Create an account at [pirsch.io](https://pirsch.io/)
2. Add your site and get your site ID
3. Add the site ID to your `.env` file:
   ```env
   PIRSCH_ID=your-pirsch-site-id
   ```

### Event Tracking

The theme automatically tracks interactions on various elements:

#### Tracked Events:
- **CTA Clicks** - Call to action button clicks with block context
- **Hero CTA Clicks** - Hero section button clicks with button text
- **Accordion Toggles** - Accordion item expansions with item title
- **Video Consent** - YouTube video consent button clicks with video URL
- **Link Clicks** - Internal and external link clicks with URL and text
- **Image Link Clicks** - Clicks on linked images
- **Privacy Policy Clicks** - Privacy policy link clicks

#### Event Attributes:
All events include metadata for better insights:
- `pirsch-event` - The event name (e.g., "CTA", "Hero_CTA_Click")
- `pirsch-meta-key` - The block type (e.g., "cta_block", "hero_block")
- Additional metadata specific to each event type

#### Adding Custom Events:
To track custom events, add data attributes to any element:

```html
<button pirsch-event="Custom_Action" 
        pirsch-meta-key="custom_block"
        pirsch-meta-custom="value">
    Click Me
</button>
```

### Privacy Compliance

Pirsch Analytics is:
- GDPR compliant
- Cookie-free
- No personal data collection
- Server-side tracking
- Hosted in EU (Germany)

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

# Analytics
PIRSCH_ID=your-pirsch-site-id
```

### Theme Configuration

Edit `config/app.php` for theme-specific settings.

### ACF Configuration

1. **Local JSON**: Field groups are automatically synced to `acf-json/`
2. **Options Pages**: Configured in `src/Acf/Options.php`
3. **Blocks**: Add new blocks in `blocks/` directory

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

## 🎨 Working with ACF

### Creating ACF Blocks

1. Create a new directory in `blocks/`
2. Add `block.json` with block configuration
3. Create `template.blade.php` for the block template
4. The block will be auto-registered

### Pre-built ACF Blocks

The theme includes 28 pre-configured ACF blocks:

**Layout Blocks:**
- **one-column** - Single column content block
- **two-columns** - Two equal column layout
- **three-columns** - Three column content layout
- **four-columns** - Four column content layout
- **one-third-two-thirds** - Asymmetric layout (1/3 + 2/3)
- **two-thirds-one-third** - Asymmetric layout (2/3 + 1/3)
- **two-columns-images** - Two columns with image support

**Content Blocks:**
- **hero** - Hero section with background image
- **accordion** - Expandable accordion (FAQ) with Schema.org markup
- **cta** - Call to action with title, content, and button
- **image** - Single image with optional caption
- **video** - Video from WordPress media or YouTube/Vimeo
- **divider** - Decorative divider / spacer

**Interactive Blocks:**
- **testimonials** - Customer quotes with repeater
- **cards** - Feature cards with icons and links
- **gallery** - Image gallery with medium-zoom lightbox
- **logo-slider** - Partner/client logo carousel
- **contact-form** - Contact Form 7 integration
- **map** - Google Maps with GDPR consent
- **tabs** - Tabbed content with Alpine.js

**Additional Blocks:**
- **pricing-table** - Pricing plans comparison
- **team** - Team member profiles
- **stats** - Animated number counters
- **timeline** - Chronological events
- **posts** - Dynamic blog post display
- **before-after** - Image comparison slider
- **table** - Data table with headers
- **button** - Standalone CTA button

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