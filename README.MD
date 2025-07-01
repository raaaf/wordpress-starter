# WordPress Starter Theme

A modern WordPress starter theme built with cutting-edge technologies and best practices.

## 🚀 Features

- **Vite 7** - Lightning fast build tool with HMR
- **Alpine.js v3.14.9** - Lightweight reactive framework (bundled, no CDN)
- **TailwindCSS v4** - Utility-first CSS framework
- **Laravel Blade** - Powerful templating engine via Illuminate v11
- **ACF Pro Integration** - Helper functions, Blade directives, and block support
- **TypeScript** - Full TypeScript support with WordPress/ACF types
- **PHP 8.2+** - Modern PHP with strict types
- **Service Provider Architecture** - Clean, modular code organization
- **Code Quality Tools** - ESLint, Prettier, PHPCS, PHPStan
- **Performance Optimizations** - Image optimization, compression, code splitting
- **Environment Configuration** - `.env` file support
- **Security First** - CSP headers with nonce support
- **Zero jQuery** - Lightweight, modern JavaScript
- **Pirsch Analytics** - Privacy-friendly analytics integration

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- WordPress 6.0+
- ACF Pro 6.0+ (for ACF features)
- Local by Flywheel (recommended for Mac development)

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/wordpress-starter.git your-theme-name
cd your-theme-name
```

### 2. Run Setup Script

The theme includes an interactive setup script that will configure everything for your project:

```bash
php setup.php
```

The setup script will:
- Configure your theme name and text domain
- Set up PHP namespace
- Configure local development URL
- Update all configuration files
- Create style.css with proper headers

#### Using a Configuration File

You can also run the setup with a configuration file:

```bash
cp setup-config.json.example setup-config.json
# Edit setup-config.json with your values
php setup.php --config setup-config.json
```

### 3. Install Dependencies

```bash
composer install
npm install
```

### 4. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` file with your local development settings.

### 5. Start Development

```bash
npm run dev
```

## 🏗️ Project Structure

```
wordpress-starter/
├── acf-json/           # ACF field group JSON files
├── blocks/             # ACF Gutenberg blocks (13 blocks included)
│   ├── accordion/     # Expandable accordion
│   ├── cta/          # Call to action block
│   ├── divider/      # Decorative divider
│   ├── four-columns/ # Four column layout
│   ├── hero/         # Hero section with background
│   ├── image/        # Single image block
│   ├── one-column/   # Single column content
│   ├── one-third-two-thirds/  # Asymmetric columns
│   ├── three-columns/         # Three column layout
│   ├── two-columns/           # Two equal columns
│   ├── two-columns-images/    # Two columns with images
│   ├── two-thirds-one-third/  # Asymmetric columns (reversed)
│   └── video/        # Video embed block
├── compiled/          # Blade template cache
├── config/            # Configuration files
│   ├── app.php       # Application config
│   ├── functions.php # Bootstrap file
│   └── vite.php      # Vite integration
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
│   │   ├── Blocks.php   # Block registration
│   │   ├── Fields.php   # Field helpers
│   │   └── Options.php  # Options pages
│   ├── Providers/   # Service providers
│   │   ├── AcfServiceProvider.php
│   │   ├── BladeServiceProvider.php
│   │   ├── MenuServiceProvider.php
│   │   ├── SecurityServiceProvider.php
│   │   └── ThemeServiceProvider.php
│   ├── types/       # TypeScript definitions
│   │   ├── alpine.d.ts
│   │   ├── global.d.ts
│   │   └── wordpress.d.ts
│   ├── Application.php
│   ├── Config.php
│   ├── Security.php
│   └── helpers.php
├── templates/        # Blade templates
│   ├── layouts/     # Layout templates
│   └── partials/    # Partial templates
├── .env.example      # Environment example
├── .eslintrc.json   # ESLint configuration
├── .gitignore       # Git ignore rules
├── .prettierrc      # Prettier configuration
├── composer.json    # PHP dependencies
├── package.json     # Node dependencies
├── phpcs.xml       # PHP CodeSniffer config
├── phpstan.neon    # PHPStan config
├── postcss.config.js # PostCSS config
├── safelist.txt    # TailwindCSS safelist
├── setup.php       # Setup script
├── setup-config.json.example # Setup config example
├── style.css       # Theme header (generated)
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
```

### Service Providers

The theme uses a service provider architecture for clean code organization:

- `BladeServiceProvider` - Blade templating engine setup
- `MenuServiceProvider` - Navigation menu registration
- `ThemeServiceProvider` - Core WordPress theme features
- `SecurityServiceProvider` - Security headers and CSP
- `AcfServiceProvider` - ACF integration, blocks, and directives
- `AnalyticsServiceProvider` - Pirsch Analytics integration

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

The theme includes 13 pre-configured ACF blocks:

- **accordion** - Expandable accordion with multiple items
- **cta** - Call to action section with title, content, and button
- **divider** - Decorative divider with custom icon
- **four-columns** - Four column content layout
- **hero** - Hero section with background image and content
- **image** - Single image with optional caption and border
- **one-column** - Single column content block
- **one-third-two-thirds** - Asymmetric two column layout (1/3 + 2/3)
- **three-columns** - Three column content layout
- **two-columns** - Two equal column layout
- **two-columns-images** - Two columns with image support
- **two-thirds-one-third** - Asymmetric two column layout (2/3 + 1/3)
- **video** - Video embed from WordPress media or external sources (YouTube)

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