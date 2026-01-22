#!/usr/bin/env php
<?php
/**
 * WP-Starter Theme Setup Script
 *
 * Run this script after cloning to customize the theme for your project.
 * Usage: php bin/setup.php
 *
 * @package WP-Starter
 */

declare(strict_types=1);

// Ensure we're running from CLI
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Change to theme root directory
chdir(dirname(__DIR__));

/**
 * Theme Setup Class
 */
class ThemeSetup
{
    private string $themeDir;
    private array $config = [];
    private array $pluginSelections = [];
    private array $blockSelections = [];
    private array $socialLinks = [];
    private bool $dependenciesInstalled = false;
    private ?string $detectedSiteName = null;
    private bool $quickSetup = false;

    private array $defaults = [
        'theme_name' => 'WP-Starter',
        'theme_slug' => 'wp-starter',
        'theme_uri' => 'https://github.com/raaaf/starter',
        'author' => 'Rafael Alex',
        'author_uri' => 'https://rafaelalex.de',
        'author_email' => 'info@example.com',
        'description' => 'A modern WordPress theme with Blade templating and TailwindCSS',
        'version' => '1.0.0',
        'text_domain' => 'wp-starter',
        'namespace' => 'WordpressStarter',
        'package_name' => 'raaaf/starter',
    ];

    /** @var array<string, string[]> Block presets for quick selection */
    private array $blockPresets = [
        'minimal' => [
            'hero', 'one-column', 'two-columns', 'image', 'cta', 'contact-form',
        ],
        'standard' => [
            // Layout (all 7)
            'one-column', 'two-columns', 'three-columns', 'four-columns',
            'one-third-two-thirds', 'two-thirds-one-third', 'two-columns-images',
            // Content (all 6)
            'hero', 'accordion', 'cta', 'image', 'video', 'divider',
            // Interactive (6 of 7 - skip map for GDPR simplicity)
            'testimonials', 'cards', 'gallery', 'logo-slider', 'contact-form', 'tabs',
            // Additional (4 most common)
            'team', 'stats', 'posts', 'button',
        ],
        'full' => [], // Empty means all blocks
    ];

    private array $socialPlatforms = [
        'linkedin' => 'LinkedIn',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'xing' => 'XING',
        'twitter' => 'X (Twitter)',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
    ];

    private array $availableBlocks = [
        'layout' => [
            'name' => 'Layout Blocks',
            'blocks' => [
                'one-column' => 'Einspaltig',
                'two-columns' => 'Zweispaltig',
                'three-columns' => 'Dreispaltig',
                'four-columns' => 'Vierspaltig',
                'one-third-two-thirds' => '1/3 + 2/3',
                'two-thirds-one-third' => '2/3 + 1/3',
                'two-columns-images' => 'Zweispaltig mit Bildern',
            ],
        ],
        'content' => [
            'name' => 'Content Blocks',
            'blocks' => [
                'hero' => 'Hero Section',
                'accordion' => 'Akkordeon/FAQ',
                'cta' => 'Call to Action',
                'image' => 'Bild',
                'video' => 'Video',
                'divider' => 'Trenner',
            ],
        ],
        'interactive' => [
            'name' => 'Interaktive Blocks',
            'blocks' => [
                'testimonials' => 'Testimonials',
                'cards' => 'Karten',
                'gallery' => 'Galerie',
                'logo-slider' => 'Logo-Slider',
                'contact-form' => 'Kontaktformular',
                'map' => 'Google Maps',
                'tabs' => 'Tabs',
            ],
        ],
        'additional' => [
            'name' => 'Weitere Blocks',
            'blocks' => [
                'pricing-table' => 'Preistabelle',
                'team' => 'Team',
                'stats' => 'Statistiken',
                'timeline' => 'Timeline',
                'posts' => 'Blog-Beiträge',
                'before-after' => 'Vorher/Nachher',
                'table' => 'Tabelle',
                'button' => 'Button',
            ],
        ],
    ];

    private array $availablePlugins = [
        'seo' => [
            'name' => 'SEO & Content',
            'plugins' => [
                'wordpress-seo' => ['name' => 'Yoast SEO', 'description' => 'SEO-Optimierung'],
                'acf-content-analysis-for-yoast-seo' => ['name' => 'ACF Content Analysis for Yoast', 'description' => 'ACF-Yoast Integration'],
                'acf-extended' => ['name' => 'ACF Extended', 'description' => 'Erweiterte ACF-Funktionen'],
            ],
        ],
        'forms' => [
            'name' => 'Formulare & E-Mail',
            'plugins' => [
                'contact-form-7' => ['name' => 'Contact Form 7', 'description' => 'Kontaktformulare'],
                'wp-mail-smtp' => ['name' => 'WP Mail SMTP', 'description' => 'SMTP E-Mail-Versand'],
            ],
        ],
        'performance' => [
            'name' => 'Performance & Analytics',
            'plugins' => [
                'wp-optimize' => ['name' => 'WP-Optimize', 'description' => 'Datenbank & Caching'],
                'pirsch-analytics' => ['name' => 'Pirsch Analytics', 'description' => 'DSGVO-konforme Analytics'],
            ],
        ],
        'admin' => [
            'name' => 'Admin Tools',
            'plugins' => [
                'admin-site-enhancements' => ['name' => 'Admin and Site Enhancements', 'description' => '60+ Admin-Verbesserungen'],
            ],
        ],
    ];

    private array $samplePages = [
        'home' => ['title' => 'Startseite', 'template' => 'page-home'],
        'about' => ['title' => 'Über uns', 'template' => ''],
        'services' => ['title' => 'Leistungen', 'template' => ''],
        'contact' => ['title' => 'Kontakt', 'template' => ''],
        'privacy' => ['title' => 'Datenschutz', 'template' => ''],
        'imprint' => ['title' => 'Impressum', 'template' => ''],
        'styleguide' => ['title' => 'Styleguide', 'template' => '', 'status' => 'private'],
    ];

    /** @var array<int, array{title: string, content: string, excerpt: string}> Sample blog posts */
    private array $samplePosts = [
        [
            'title' => 'Willkommen auf unserem Blog',
            'excerpt' => 'Erfahren Sie mehr über aktuelle Themen, Neuigkeiten und Einblicke aus unserem Unternehmen.',
            'content' => '<p>Herzlich willkommen auf unserem Blog! Hier teilen wir regelmäßig interessante Einblicke, Neuigkeiten und Fachartikel mit Ihnen.</p>
<p>Unser Team arbeitet kontinuierlich daran, Ihnen wertvolle Informationen und praktische Tipps zu liefern. Bleiben Sie gespannt auf kommende Beiträge!</p>
<p>Haben Sie Fragen oder Anregungen? Wir freuen uns über Ihre Nachricht.</p>',
        ],
        [
            'title' => 'Unsere Mission und Werte',
            'excerpt' => 'Was uns antreibt und welche Werte unser tägliches Handeln bestimmen.',
            'content' => '<p>Bei allem, was wir tun, stehen unsere Kunden im Mittelpunkt. Qualität, Zuverlässigkeit und Innovation sind die Säulen unserer Arbeit.</p>
<p>Wir glauben an langfristige Partnerschaften und nachhaltige Lösungen. Jedes Projekt ist für uns eine Chance, gemeinsam zu wachsen und Außergewöhnliches zu schaffen.</p>
<p>Erfahren Sie mehr über unsere Arbeitsweise und wie wir Ihnen helfen können, Ihre Ziele zu erreichen.</p>',
        ],
        [
            'title' => 'Tipps für den Erfolg',
            'excerpt' => 'Praktische Ratschläge und bewährte Strategien für Ihren Erfolg.',
            'content' => '<p>Erfolg kommt selten über Nacht. Er ist das Ergebnis von Planung, harter Arbeit und der Bereitschaft, aus Fehlern zu lernen.</p>
<p>In diesem Beitrag teilen wir einige bewährte Strategien, die uns und unseren Kunden geholfen haben:</p>
<ul>
<li>Klare Ziele setzen und regelmäßig überprüfen</li>
<li>Kontinuierliche Verbesserung als Prinzip</li>
<li>Offene Kommunikation pflegen</li>
<li>Flexibel auf Veränderungen reagieren</li>
</ul>
<p>Welche Strategien haben sich bei Ihnen bewährt? Teilen Sie Ihre Erfahrungen mit uns!</p>',
        ],
    ];

    /** @var array<string, string[]> Menu assignments: menu location => page slugs */
    private array $menuAssignments = [
        'header-menu' => ['about', 'services', 'contact'],
        'legal-menu'  => ['privacy', 'imprint'],
        'footer-menu' => ['about', 'services', 'contact'],
    ];

    public function __construct()
    {
        $this->themeDir = getcwd();
        $this->detectedSiteName = $this->detectLocalSiteName();
        $this->detectGitUser();
    }

    /**
     * Detect git user name and email from git config
     */
    private function detectGitUser(): void
    {
        $gitName = trim(shell_exec('git config user.name 2>/dev/null') ?? '');
        $gitEmail = trim(shell_exec('git config user.email 2>/dev/null') ?? '');

        if (!empty($gitName)) {
            $this->defaults['author'] = $gitName;
        }
        if (!empty($gitEmail)) {
            $this->defaults['author_email'] = $gitEmail;
        }
    }

    /**
     * Detect Local by Flywheel site name from directory structure
     */
    private function detectLocalSiteName(): ?string
    {
        $cwd = getcwd();

        // Check for typical Local by Flywheel path patterns
        // macOS: ~/Local Sites/SiteName/app/public/wp-content/themes/theme-name
        // Windows: C:\Users\...\Local Sites\SiteName\app\public\wp-content\themes\theme-name
        if (preg_match('#[/\\\\]Local Sites[/\\\\]([^/\\\\]+)[/\\\\]#i', $cwd, $matches)) {
            return $matches[1];
        }

        // Alternative: Check if we're in a typical WordPress themes directory
        // and try to get the site name from wp-config or directory
        $parentDir = dirname($cwd);
        if (basename($parentDir) === 'themes') {
            // We're in wp-content/themes/theme-name
            $wpContentDir = dirname($parentDir);
            $publicDir = dirname($wpContentDir);

            // Check for Local by Flywheel structure
            if (basename($publicDir) === 'public' || basename($publicDir) === 'htdocs') {
                $appDir = dirname($publicDir);
                if (basename($appDir) === 'app') {
                    $siteDir = dirname($appDir);
                    return basename($siteDir);
                }
            }
        }

        return null;
    }

    public function run(): void
    {
        $this->printHeader();
        $this->collectThemeInfo();
        $this->collectPluginPreferences();
        $this->collectBlockPreferences();
        $this->collectContentOptions();
        $this->collectSocialMedia();
        $this->collectAnalytics();
        $this->confirmChanges();
        $this->applyChanges();
        $this->runInstallCommands();
        $this->initGitRepo();
        $this->printSuccess();
    }

    private function printHeader(): void
    {
        $this->clearScreen();
        echo "\n";
        echo $this->color("╔══════════════════════════════════════════════════════════════════╗\n", 'cyan');
        echo $this->color("║                                                                  ║\n", 'cyan');
        echo $this->color("║   ", 'cyan') . $this->color("WP-Starter Theme Setup Wizard", 'white', true) . $this->color("                            ║\n", 'cyan');
        echo $this->color("║   ", 'cyan') . "Configure your theme for your new project" . $this->color("                  ║\n", 'cyan');
        echo $this->color("║                                                                  ║\n", 'cyan');
        echo $this->color("╚══════════════════════════════════════════════════════════════════╝\n", 'cyan');
        echo "\n";

        // Show detected info
        $detections = [];
        if ($this->detectedSiteName) {
            $detections[] = "Site: " . $this->color($this->detectedSiteName, 'green');
        }
        if ($this->defaults['author'] !== 'Rafael Alex') {
            $detections[] = "Author: " . $this->color($this->defaults['author'], 'green');
        }
        if (!empty($detections)) {
            echo "  " . $this->color("✓ Auto-detected: ", 'gray') . implode(', ', $detections) . "\n\n";
        }

        echo "Choose setup mode:\n\n";
        echo "  " . $this->color("[q]", 'yellow') . " " . $this->color("Quick Setup", 'white', true) . " - Just theme name, uses smart defaults\n";
        echo "      " . $this->color("(Standard blocks, recommended plugins, skip optional config)", 'gray') . "\n\n";
        echo "  " . $this->color("[f]", 'yellow') . " " . $this->color("Full Setup", 'white', true) . "  - Configure everything step by step\n";
        echo "      " . $this->color("(All options, block selection, social media, analytics)", 'gray') . "\n\n";

        $mode = strtolower($this->prompt('Setup mode', 'q', '[q]uick or [f]ull'));
        $this->quickSetup = ($mode !== 'f' && $mode !== 'full');

        if ($this->quickSetup) {
            echo "\n  " . $this->color("→ Quick Setup selected", 'green') . "\n";
        } else {
            echo "\n  " . $this->color("→ Full Setup selected", 'cyan') . "\n";
        }
        echo "\n";
    }

    private function collectThemeInfo(): void
    {
        $this->printSection("Step 1: Theme Information");

        // Use detected Local site name as default if available
        $defaultThemeName = $this->detectedSiteName ?? $this->defaults['theme_name'];

        $this->config['theme_name'] = $this->prompt(
            'Theme Name',
            $defaultThemeName,
            'The display name of your theme'
        );

        // Auto-derive other values from theme name
        $this->config['theme_slug'] = $this->slugify($this->config['theme_name']);
        $this->config['text_domain'] = $this->config['theme_slug'];
        $this->config['namespace'] = $this->pascalCase($this->config['theme_name']);
        $this->config['description'] = 'WordPress theme for ' . $this->config['theme_name'];
        $this->config['version'] = '1.0.0';

        if ($this->quickSetup) {
            // Quick mode: use all defaults
            $this->config['author'] = $this->defaults['author'];
            $this->config['author_email'] = $this->defaults['author_email'];
            $this->config['author_uri'] = $this->defaults['author_uri'];
            $this->config['theme_uri'] = '';
            $this->config['package_name'] = strtolower($this->slugify($this->config['author']) . '/' . $this->config['theme_slug']);

            echo "\n  " . $this->color("Using defaults:", 'gray') . "\n";
            echo "  • Slug: " . $this->color($this->config['theme_slug'], 'cyan') . "\n";
            echo "  • Author: " . $this->color($this->config['author'], 'cyan') . "\n";
            echo "  • Email: " . $this->color($this->config['author_email'], 'cyan') . "\n";
        } else {
            // Full mode: ask everything
            echo $this->color("(Press Enter to keep default value shown in brackets)\n\n", 'gray');

            $this->config['theme_slug'] = $this->prompt(
                'Theme Slug',
                $this->config['theme_slug'],
                'Lowercase with hyphens'
            );

            $this->config['text_domain'] = $this->prompt(
                'Text Domain',
                $this->config['theme_slug'],
                'For translations'
            );

            $this->config['namespace'] = $this->prompt(
                'PHP Namespace',
                $this->config['namespace'],
                'PascalCase'
            );

            $this->config['description'] = $this->prompt(
                'Description',
                $this->config['description']
            );

            echo "\n";
            $this->printSubSection("Author Information");

            $this->config['author'] = $this->prompt(
                'Author Name',
                $this->defaults['author']
            );

            $this->config['author_email'] = $this->promptWithValidation(
                'Author Email',
                $this->defaults['author_email'],
                'email',
                'Valid email address'
            );

            $this->config['author_uri'] = $this->promptWithValidation(
                'Author Website',
                $this->defaults['author_uri'],
                'url',
                'https://...'
            );

            echo "\n";
            $this->printSubSection("Repository & Version");

            $this->config['theme_uri'] = $this->collectRepositoryUrl();

            $this->config['version'] = $this->prompt(
                'Initial Version',
                '1.0.0'
            );

            $this->config['package_name'] = $this->prompt(
                'Composer Package',
                strtolower($this->slugify($this->config['author']) . '/' . $this->config['theme_slug']),
                'vendor/package'
            );
        }
    }

    private function collectPluginPreferences(): void
    {
        if ($this->quickSetup) {
            // Quick mode: select recommended plugins automatically
            $recommendedPlugins = ['wordpress-seo', 'contact-form-7', 'wp-mail-smtp', 'admin-site-enhancements'];
            foreach ($this->availablePlugins as $category) {
                foreach ($category['plugins'] as $slug => $plugin) {
                    $this->pluginSelections[$slug] = in_array($slug, $recommendedPlugins, true);
                }
            }
            echo "\n  " . $this->color("Using recommended plugins:", 'gray') . " Yoast SEO, Contact Form 7, WP Mail SMTP, Admin Enhancements\n";
            return;
        }

        $this->printSection("Step 2: Plugin Selection");
        echo "Select which plugins should be " . $this->color("auto-installed", 'green') . " when you first\n";
        echo "activate the theme in WordPress.\n\n";
        echo $this->color("Note: ACF PRO (required) must be installed manually (premium plugin).\n\n", 'yellow');

        foreach ($this->availablePlugins as $categoryKey => $category) {
            $this->printSubSection($category['name']);

            foreach ($category['plugins'] as $slug => $plugin) {
                $default = 'y';
                $answer = $this->prompt(
                    "Install {$plugin['name']}?",
                    $default,
                    $plugin['description'] . ' (y/n)'
                );

                $this->pluginSelections[$slug] = strtolower($answer) === 'y' || strtolower($answer) === 'yes';
            }
            echo "\n";
        }
    }

    private function collectContentOptions(): void
    {
        if ($this->quickSetup) {
            // Quick mode: use theme name as company, enable all defaults
            $this->config['company_name'] = $this->config['theme_name'];
            $this->config['company_address'] = '';
            $this->config['company_phone'] = '';
            $this->config['company_email'] = $this->config['author_email'] ?? $this->defaults['author_email'];
            $this->config['create_pages'] = true;
            $this->config['create_posts'] = true;
            $this->config['delete_default_content'] = true;
            $this->config['set_permalink_structure'] = true;
            $this->config['color_scheme'] = 'system';

            echo "\n  " . $this->color("Content options:", 'gray') . " Standardseiten + 3 Blog-Beiträge werden erstellt, pretty permalinks aktiviert\n";
            return;
        }

        $this->printSection("Step 4: Content Options");
        echo "Configure what should happen when WordPress is set up.\n\n";

        $this->printSubSection("Company Information");
        echo "  " . $this->color("These values will be used for contact info, footer, and copyright.\n\n", 'gray');

        $this->config['company_name'] = $this->prompt(
            'Company Name',
            $this->config['theme_name'],
            'For contact info and legal pages'
        );

        $this->config['company_address'] = $this->prompt(
            'Address',
            '',
            'Street, City (optional)'
        );

        $this->config['company_phone'] = $this->prompt(
            'Phone',
            '',
            '+49 123 456789 (optional)'
        );

        $this->config['company_email'] = $this->prompt(
            'Email',
            $this->config['author_email'] ?? $this->defaults['author_email'],
            'Contact email address'
        );

        echo "\n";
        $this->printSubSection("Page Setup");

        $this->config['create_pages'] = strtolower($this->prompt(
            'Create default pages?',
            'y',
            'Startseite, Über uns, Kontakt, etc. (y/n)'
        )) === 'y';

        if ($this->config['create_pages']) {
            echo "\n  " . $this->color("Pages to create:", 'gray') . "\n";
            foreach ($this->samplePages as $key => $page) {
                echo "    - {$page['title']}\n";
            }
            echo "\n";
        }

        $this->config['create_posts'] = strtolower($this->prompt(
            'Create sample blog posts?',
            'y',
            '3 example posts for the blog (y/n)'
        )) === 'y';

        if ($this->config['create_posts']) {
            echo "\n  " . $this->color("Posts to create:", 'gray') . "\n";
            foreach ($this->samplePosts as $post) {
                echo "    - {$post['title']}\n";
            }
            echo "\n";
        }

        $this->config['delete_default_content'] = strtolower($this->prompt(
            'Delete WordPress default content?',
            'y',
            'Removes "Hello World" post and sample page (y/n)'
        )) === 'y';

        $this->config['set_permalink_structure'] = strtolower($this->prompt(
            'Set pretty permalinks?',
            'y',
            'Changes to /%postname%/ structure (y/n)'
        )) === 'y';

        echo "\n";
        $this->printSubSection("Darstellung");

        $colorSchemeChoice = $this->prompt(
            'Farbschema',
            's',
            '[s]ystem (empfohlen), [l]ight, [d]ark'
        );

        $this->config['color_scheme'] = match (strtolower($colorSchemeChoice)) {
            'l', 'light' => 'light',
            'd', 'dark' => 'dark',
            default => 'system',
        };
    }

    private function collectBlockPreferences(): void
    {
        // Get all block slugs
        $allBlockSlugs = [];
        foreach ($this->availableBlocks as $category) {
            foreach ($category['blocks'] as $slug => $name) {
                $allBlockSlugs[] = $slug;
            }
        }

        if ($this->quickSetup) {
            // Quick mode: use standard preset
            $this->applyBlockPreset('standard', $allBlockSlugs);
            $keptCount = count(array_filter($this->blockSelections));
            echo "\n  " . $this->color("Using standard blocks:", 'gray') . " {$keptCount} blocks selected\n";
            return;
        }

        $this->printSection("Step 3: Block Selection");
        echo "Das Theme enthält " . $this->color("28 ACF Blocks", 'cyan') . ". Wähle ein Preset oder einzeln:\n\n";

        echo "  " . $this->color("[m]", 'yellow') . " Minimal    - 6 Blocks  (Hero, Spalten, Bild, CTA, Kontakt)\n";
        echo "  " . $this->color("[s]", 'yellow') . " Standard   - 23 Blocks (Alle außer Pricing, Timeline, Map, Before/After, Table)\n";
        echo "  " . $this->color("[f]", 'yellow') . " Full       - 28 Blocks (Alle behalten)\n";
        echo "  " . $this->color("[c]", 'yellow') . " Custom     - Einzeln auswählen\n\n";

        $choice = strtolower($this->prompt('Block-Auswahl', 's', '[m]inimal, [s]tandard, [f]ull, [c]ustom'));

        if ($choice === 'm' || $choice === 'minimal') {
            $this->applyBlockPreset('minimal', $allBlockSlugs);
            echo "  " . $this->color("✓ Minimal: 6 Blocks ausgewählt.", 'green') . "\n";
        } elseif ($choice === 'f' || $choice === 'full') {
            $this->applyBlockPreset('full', $allBlockSlugs);
            echo "  " . $this->color("✓ Full: Alle 28 Blocks behalten.", 'green') . "\n";
        } elseif ($choice === 'c' || $choice === 'custom') {
            echo "\n";
            foreach ($this->availableBlocks as $categoryKey => $category) {
                $this->printSubSection($category['name']);

                foreach ($category['blocks'] as $slug => $name) {
                    $answer = $this->prompt(
                        $name,
                        'y',
                        'behalten? (y/n)'
                    );
                    $this->blockSelections[$slug] = strtolower($answer) === 'y' || strtolower($answer) === 'yes';
                }
                echo "\n";
            }
        } else {
            // Default to standard
            $this->applyBlockPreset('standard', $allBlockSlugs);
            echo "  " . $this->color("✓ Standard: 23 Blocks ausgewählt.", 'green') . "\n";
        }
    }

    /**
     * Apply a block preset
     *
     * @param string $preset Preset name (minimal, standard, full)
     * @param string[] $allBlockSlugs All available block slugs
     */
    private function applyBlockPreset(string $preset, array $allBlockSlugs): void
    {
        $presetBlocks = $this->blockPresets[$preset] ?? [];

        foreach ($allBlockSlugs as $slug) {
            // Empty preset means keep all
            $this->blockSelections[$slug] = empty($presetBlocks) || in_array($slug, $presetBlocks, true);
        }
    }

    private function collectSocialMedia(): void
    {
        if ($this->quickSetup) {
            // Skip in quick mode
            echo "\n  " . $this->color("Social Media:", 'gray') . " Übersprungen (kann später in Theme-Einstellungen konfiguriert werden)\n";
            return;
        }

        $this->printSection("Step 5: Social Media");
        echo "Füge deine Social Media Profile hinzu (erscheinen im Footer).\n";
        echo $this->color("Leer lassen um zu überspringen.\n\n", 'gray');

        foreach ($this->socialPlatforms as $key => $name) {
            $url = $this->prompt(
                $name,
                '',
                "URL zu deinem {$name} Profil"
            );

            if (!empty($url)) {
                // Validate URL
                if (!$this->isValidUrl($url)) {
                    echo "  " . $this->color("⚠ Ungültige URL, übersprungen.", 'yellow') . "\n";
                    continue;
                }
                $this->socialLinks[] = [
                    'platform' => $key,
                    'url' => $url,
                ];
            }
        }

        if (empty($this->socialLinks)) {
            echo "\n  " . $this->color("Keine Social Media Links konfiguriert.", 'gray') . "\n";
        } else {
            echo "\n  " . $this->color("✓ " . count($this->socialLinks) . " Social Media Links konfiguriert.", 'green') . "\n";
        }
    }

    private function collectAnalytics(): void
    {
        if ($this->quickSetup) {
            // Skip in quick mode
            $this->config['pirsch_code'] = '';
            echo "\n  " . $this->color("Analytics:", 'gray') . " Übersprungen (kann später in Theme-Einstellungen konfiguriert werden)\n";
            return;
        }

        $this->printSection("Step 6: Analytics");
        echo "Das Theme unterstützt " . $this->color("Pirsch Analytics", 'cyan') . " - DSGVO-konform & cookie-frei.\n";
        echo $this->color("https://pirsch.io - Kein Cookie-Banner erforderlich!\n\n", 'gray');

        $this->config['pirsch_code'] = $this->prompt(
            'Pirsch Site Code',
            '',
            'Aus pirsch.io Dashboard (oder leer lassen)'
        );

        if (empty($this->config['pirsch_code'])) {
            echo "  " . $this->color("Kein Analytics konfiguriert. Kann später hinzugefügt werden.", 'gray') . "\n";
        } else {
            echo "  " . $this->color("✓ Pirsch Analytics wird aktiviert.", 'green') . "\n";
        }
    }

    private function confirmChanges(): void
    {
        if ($this->quickSetup) {
            // Quick mode: minimal summary, auto-confirm
            echo "\n";
            $this->printSection("Quick Setup Summary");
            echo "  Theme:   " . $this->color($this->config['theme_name'], 'green') . "\n";
            echo "  Slug:    " . $this->color($this->config['theme_slug'], 'cyan') . "\n";
            echo "  Author:  {$this->config['author']}\n";
            echo "  Blocks:  " . $this->color(count(array_filter($this->blockSelections)) . " (standard)", 'cyan') . "\n";
            echo "  Plugins: " . $this->color(count(array_filter($this->pluginSelections)) . " (recommended)", 'cyan') . "\n";
            echo "\n";

            $confirm = $this->prompt(
                $this->color('Apply?', 'yellow'),
                'y',
                '(y/n)'
            );

            if (strtolower($confirm) !== 'y' && strtolower($confirm) !== 'yes') {
                echo "\n" . $this->color("Setup cancelled.", 'red') . "\n";
                exit(1);
            }
            return;
        }

        $this->printSection("Configuration Summary");

        echo $this->color("Theme Settings:\n", 'white', true);
        echo "  Name:        " . $this->color($this->config['theme_name'], 'green') . "\n";
        echo "  Slug:        " . $this->color($this->config['theme_slug'], 'cyan') . "\n";
        echo "  Namespace:   " . $this->color($this->config['namespace'], 'cyan') . "\n";
        echo "  Author:      {$this->config['author']} <{$this->config['author_email']}>\n";
        echo "  Version:     {$this->config['version']}\n";
        echo "\n";

        echo $this->color("Plugins to Auto-Install:\n", 'white', true);
        $selectedPlugins = array_filter($this->pluginSelections);
        if (empty($selectedPlugins)) {
            echo "  " . $this->color("None selected", 'yellow') . "\n";
        } else {
            foreach ($selectedPlugins as $slug => $enabled) {
                $pluginName = $this->getPluginName($slug);
                echo "  " . $this->color("✓", 'green') . " {$pluginName}\n";
            }
        }
        echo "\n";

        echo $this->color("Blocks:\n", 'white', true);
        $selectedBlocks = array_filter($this->blockSelections);
        $removedBlocks = array_filter($this->blockSelections, fn($v) => !$v);
        echo "  " . $this->color(count($selectedBlocks) . " behalten", 'green');
        if (!empty($removedBlocks)) {
            echo ", " . $this->color(count($removedBlocks) . " entfernen", 'yellow');
        }
        echo "\n\n";

        echo $this->color("Content Options:\n", 'white', true);
        echo "  Create pages:          " . ($this->config['create_pages'] ? $this->color('Yes', 'green') : $this->color('No', 'red')) . "\n";
        echo "  Delete default content: " . ($this->config['delete_default_content'] ? $this->color('Yes', 'green') : $this->color('No', 'red')) . "\n";
        echo "  Pretty permalinks:     " . ($this->config['set_permalink_structure'] ? $this->color('Yes', 'green') : $this->color('No', 'red')) . "\n";
        echo "  Farbschema:            " . $this->color($this->config['color_scheme'] ?? 'system', 'cyan') . "\n";
        echo "\n";

        echo $this->color("Social Media:\n", 'white', true);
        if (empty($this->socialLinks)) {
            echo "  " . $this->color("Nicht konfiguriert", 'gray') . "\n";
        } else {
            foreach ($this->socialLinks as $link) {
                echo "  " . $this->color("✓", 'green') . " {$this->socialPlatforms[$link['platform']]}\n";
            }
        }
        echo "\n";

        echo $this->color("Analytics:\n", 'white', true);
        if (empty($this->config['pirsch_code'])) {
            echo "  " . $this->color("Nicht konfiguriert", 'gray') . "\n";
        } else {
            echo "  " . $this->color("✓", 'green') . " Pirsch Analytics\n";
        }
        echo "\n";

        $confirm = $this->prompt(
            $this->color('Apply these changes?', 'yellow'),
            'yes',
            'yes/no'
        );

        if (strtolower($confirm) !== 'yes' && strtolower($confirm) !== 'y') {
            echo "\n" . $this->color("Setup cancelled.", 'red') . "\n";
            exit(0);
        }
    }

    private function applyChanges(): void
    {
        $this->printSection("Applying Changes");

        $this->task('Updating style.css', fn() => $this->updateStyleCss());
        $this->task('Updating package.json', fn() => $this->updatePackageJson());
        $this->task('Updating composer.json + plugins', fn() => $this->updateComposerJson());
        $this->task('Updating PHP namespaces', fn() => $this->updatePhpFiles());
        $this->task('Updating Blade templates', fn() => $this->updateBladeTemplates());
        $this->task('Updating block.json files', fn() => $this->updateBlockJsonFiles());
        $this->task('Updating CLAUDE.md', fn() => $this->updateClaudeMd());
        $this->task('Updating documentation files', fn() => $this->updateDocumentation());
        $this->task('Cleaning up old plugin config', fn() => $this->savePluginPreferences());
        $this->task('Saving content options', fn() => $this->saveContentOptions());
        $this->task('Saving ACF options (prefill)', fn() => $this->saveAcfOptions());
        $this->task('Removing unused blocks', fn() => $this->removeUnusedBlocks());
        $this->task('Creating .env file', fn() => $this->createEnvFile());
        $this->task('Removing old setup script', fn() => $this->removeOldSetupScript());
    }

    private function removeOldSetupScript(): void
    {
        $oldSetupPath = $this->themeDir . '/setup.php';
        if (file_exists($oldSetupPath) && $oldSetupPath !== __FILE__) {
            unlink($oldSetupPath);
        }
    }

    private function runInstallCommands(): void
    {
        $this->printSection("Step 7: Installing Dependencies");

        echo "The theme requires dependencies to be installed before it can be used.\n\n";

        $runInstall = strtolower($this->prompt(
            'Run install commands automatically?',
            'y',
            'composer update, npm install, npm run build (y/n)'
        )) === 'y';

        if (!$runInstall) {
            echo "\n" . $this->color("Skipped. Run these commands manually:", 'yellow') . "\n";
            echo "  " . $this->color("composer dump-autoload && composer update", 'cyan') . "\n";
            echo "  " . $this->color("npm install && npm run build", 'cyan') . "\n";
            return;
        }

        echo "\n";

        // Check for required tools
        $hasComposer = $this->commandExists('composer');
        $hasNpm = $this->commandExists('npm');

        if (!$hasComposer) {
            echo $this->color("⚠ Composer not found. Please install Composer first.", 'yellow') . "\n";
            echo "  " . $this->color("https://getcomposer.org/download/", 'cyan') . "\n\n";
        }

        if (!$hasNpm) {
            echo $this->color("⚠ npm not found. Please install Node.js first.", 'yellow') . "\n";
            echo "  " . $this->color("https://nodejs.org/", 'cyan') . "\n\n";
        }

        if (!$hasComposer && !$hasNpm) {
            return;
        }

        // Run composer commands
        if ($hasComposer) {
            $this->runCommand('Updating Composer autoload', 'composer dump-autoload');
            // Use 'update' instead of 'install' because composer.json was modified with plugins
            $this->runCommand('Installing PHP dependencies', 'composer update --no-interaction');
        }

        // Run npm commands
        if ($hasNpm) {
            $this->runCommand('Installing Node.js dependencies', 'npm install');

            $buildAssets = strtolower($this->prompt(
                'Build production assets now?',
                'y',
                'npm run build (y/n)'
            )) === 'y';

            if ($buildAssets) {
                $this->runCommand('Building production assets', 'npm run build');
            }

            $this->dependenciesInstalled = true;
        }

        if ($hasComposer && !$hasNpm) {
            $this->dependenciesInstalled = true;
        }

        echo "\n";
    }

    private function commandExists(string $command): bool
    {
        $check = strncasecmp(PHP_OS, 'WIN', 3) === 0 ? 'where' : 'which';
        $result = shell_exec("{$check} {$command} 2>/dev/null");
        return !empty(trim($result ?? ''));
    }

    private function isGhAuthenticated(): bool
    {
        if (!$this->commandExists('gh')) {
            return false;
        }
        $result = shell_exec('gh auth status 2>&1');
        return str_contains($result ?? '', 'Logged in to');
    }

    private function collectRepositoryUrl(): string
    {
        $hasGh = $this->isGhAuthenticated();

        if ($hasGh) {
            echo "  " . $this->color("GitHub CLI detected and authenticated.", 'green') . "\n\n";

            $choice = $this->prompt(
                'GitHub Repository',
                'c',
                '[c]reate new, [e]xisting URL, [s]kip'
            );

            $choice = strtolower($choice);

            if ($choice === 'c' || $choice === 'create') {
                return $this->createGitHubRepo();
            } elseif ($choice === 'e' || $choice === 'existing') {
                return $this->prompt(
                    'Repository URL',
                    '',
                    'https://github.com/username/repo'
                );
            } else {
                echo "  " . $this->color("Skipped. You can add a repository later.", 'gray') . "\n";
                return '';
            }
        } else {
            echo "  " . $this->color("Tip: Install GitHub CLI (gh) to create repos automatically.", 'gray') . "\n";
            echo "  " . $this->color("https://cli.github.com/", 'cyan') . "\n\n";

            $choice = $this->prompt(
                'Repository URL',
                '',
                'Enter URL or leave empty to skip'
            );

            return $choice;
        }
    }

    private function createGitHubRepo(): string
    {
        $repoName = $this->prompt(
            'Repository name',
            $this->config['theme_slug'],
            'Will be created on GitHub'
        );

        $visibility = $this->prompt(
            'Visibility',
            'private',
            '[private] or [public]'
        );
        $visibility = strtolower($visibility) === 'public' ? 'public' : 'private';

        $description = $this->config['description'] ?? 'WordPress theme';

        echo "\n  Creating GitHub repository...\n";

        // Remove old origin if it exists (e.g., from cloning the starter repo)
        $existingOrigin = trim(shell_exec('git remote get-url origin 2>/dev/null') ?? '');
        if (!empty($existingOrigin)) {
            shell_exec('git remote remove origin 2>/dev/null');
            echo "  " . $this->color("Removed old origin: {$existingOrigin}", 'gray') . "\n";
        }

        $command = sprintf(
            'gh repo create %s --description %s --%s --source=. --remote=origin 2>&1',
            escapeshellarg($repoName),
            escapeshellarg($description),
            $visibility
        );

        $output = shell_exec($command);

        // Extract repo URL from output
        if (preg_match('/https:\/\/github\.com\/[^\s]+/', $output, $matches)) {
            $repoUrl = rtrim($matches[0], '/');
            echo "  " . $this->color("✓ Repository created: {$repoUrl}", 'green') . "\n\n";
            return $repoUrl;
        } else {
            echo "  " . $this->color("⚠ Could not create repository: {$output}", 'yellow') . "\n";
            return $this->prompt(
                'Enter repository URL manually',
                '',
                'Or leave empty to skip'
            );
        }
    }

    private function runCommand(string $description, string $command): void
    {
        echo "  " . str_pad($description . "...", 40);

        $output = [];
        $returnCode = 0;

        // Run command and capture output
        exec("{$command} 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            echo $this->color("Done", 'green') . "\n";
        } else {
            echo $this->color("Failed", 'red') . "\n";
            // Show first few lines of error output
            $errorLines = array_slice($output, 0, 3);
            foreach ($errorLines as $line) {
                echo "     " . $this->color($line, 'gray') . "\n";
            }
        }
    }

    private function task(string $name, callable $callback): void
    {
        echo "  " . str_pad($name . "...", 35);
        try {
            $callback();
            echo $this->color("Done", 'green') . "\n";
        } catch (Exception $e) {
            echo $this->color("Error: " . $e->getMessage(), 'red') . "\n";
        }
    }

    private function updateStyleCss(): void
    {
        $content = <<<CSS
/*
Theme Name: {$this->config['theme_name']}
Theme URI: {$this->config['theme_uri']}
Author: {$this->config['author']}
Author URI: {$this->config['author_uri']}
Description: {$this->config['description']}
Version: {$this->config['version']}
License: GNU GPL version 2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: {$this->config['text_domain']}
*/

CSS;

        file_put_contents($this->themeDir . '/style.css', $content);
    }

    private function updatePackageJson(): void
    {
        $packagePath = $this->themeDir . '/package.json';
        $package = json_decode(file_get_contents($packagePath), true);

        $package['name'] = $this->config['theme_slug'];
        $package['description'] = $this->config['description'];
        $package['version'] = $this->config['version'];
        $package['author'] = $this->config['author'];

        if (isset($package['repository']['url'])) {
            $package['repository']['url'] = 'git+' . $this->config['theme_uri'] . '.git';
        }

        file_put_contents(
            $packagePath,
            json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    private function updateComposerJson(): void
    {
        $composerPath = $this->themeDir . '/composer.json';
        $composer = json_decode(file_get_contents($composerPath), true);

        $composer['name'] = $this->config['package_name'];
        $composer['description'] = $this->config['description'];
        $composer['authors'] = [
            [
                'name' => $this->config['author'],
                'email' => $this->config['author_email'],
            ]
        ];

        // Update namespace in autoload PSR-4
        $newNamespace = $this->config['namespace'] . '\\';

        // Find and replace the existing namespace (handles both fresh setup and re-runs)
        if (isset($composer['autoload']['psr-4'])) {
            $existingNamespaces = array_keys($composer['autoload']['psr-4']);
            foreach ($existingNamespaces as $existingNs) {
                // Check if this namespace maps to src/ (our theme namespace)
                if ($composer['autoload']['psr-4'][$existingNs] === 'src/') {
                    unset($composer['autoload']['psr-4'][$existingNs]);
                    break;
                }
            }
            // Add the new namespace
            $composer['autoload']['psr-4'][$newNamespace] = 'src/';
        }

        // Add selected plugins as wpackagist dependencies
        $selectedPlugins = array_keys(array_filter($this->pluginSelections));
        foreach ($selectedPlugins as $pluginSlug) {
            $packageName = 'wpackagist-plugin/' . $pluginSlug;
            // Only add if not already present
            if (!isset($composer['require'][$packageName])) {
                $composer['require'][$packageName] = '*';
            }
        }

        file_put_contents(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    private function updatePhpFiles(): void
    {
        $oldNamespace = 'WordpressStarter';
        $newNamespace = $this->config['namespace'];
        $oldTextDomain = 'wp-starter';
        $newTextDomain = $this->config['text_domain'];

        if ($oldNamespace === $newNamespace && $oldTextDomain === $newTextDomain) {
            return;
        }

        // Collect all PHP files that need namespace updates
        $phpFiles = array_merge(
            $this->findFiles($this->themeDir . '/src', '*.php'),
            $this->findFiles($this->themeDir . '/tests', '*.php'),
            glob($this->themeDir . '/config/*.php') ?: []
        );

        // Also check for root-level PHP files (excluding bin/setup.php itself)
        $rootPhpFiles = glob($this->themeDir . '/*.php') ?: [];
        foreach ($rootPhpFiles as $file) {
            if (basename($file) !== 'setup.php') {
                $phpFiles[] = $file;
            }
        }

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $original = $content;

            // Update namespace declarations (namespace WordpressStarter;)
            $content = str_replace("namespace {$oldNamespace}", "namespace {$newNamespace}", $content);

            // Update escaped backslash patterns in PHP strings FIRST
            // These are for Blade directive definitions like: "\\WordpressStarter\\Class"
            $content = str_replace("\\\\{$oldNamespace}\\\\", "\\\\{$newNamespace}\\\\", $content);

            // Update direct namespace references (WordpressStarter\Vite::init())
            // This catches: use statements, direct calls, and any other references
            $content = str_replace("{$oldNamespace}\\", "{$newNamespace}\\", $content);

            // Update text domain
            $content = str_replace("'{$oldTextDomain}'", "'{$newTextDomain}'", $content);

            if ($content !== $original) {
                file_put_contents($file, $content);
            }
        }
    }

    private function updateBladeTemplates(): void
    {
        $oldNamespace = 'WordpressStarter';
        $newNamespace = $this->config['namespace'];
        $oldTextDomain = 'wp-starter';
        $newTextDomain = $this->config['text_domain'];

        if ($oldNamespace === $newNamespace && $oldTextDomain === $newTextDomain) {
            return;
        }

        $bladeFiles = array_merge(
            $this->findFiles($this->themeDir . '/templates', '*.blade.php'),
            $this->findFiles($this->themeDir . '/blocks', '*.blade.php')
        );

        foreach ($bladeFiles as $file) {
            $content = file_get_contents($file);
            $original = $content;

            // Update fully qualified namespace calls (e.g., \WordpressStarter\Vite::)
            $content = str_replace("\\{$oldNamespace}\\", "\\{$newNamespace}\\", $content);
            // Update text domain
            $content = str_replace("'{$oldTextDomain}'", "'{$newTextDomain}'", $content);

            if ($content !== $original) {
                file_put_contents($file, $content);
            }
        }
    }

    private function updateBlockJsonFiles(): void
    {
        $oldTextDomain = 'wp-starter';
        $newTextDomain = $this->config['text_domain'];

        if ($oldTextDomain === $newTextDomain) {
            return;
        }

        $blockJsonFiles = $this->findFiles($this->themeDir . '/blocks', 'block.json');

        foreach ($blockJsonFiles as $file) {
            $content = file_get_contents($file);
            $original = $content;

            $content = str_replace("\"textdomain\": \"{$oldTextDomain}\"", "\"textdomain\": \"{$newTextDomain}\"", $content);

            if ($content !== $original) {
                file_put_contents($file, $content);
            }
        }
    }

    private function updateClaudeMd(): void
    {
        $claudePath = $this->themeDir . '/CLAUDE.md';
        if (!file_exists($claudePath)) {
            return;
        }

        $content = file_get_contents($claudePath);

        $content = str_replace(
            'Namespace for PHP classes: `WordpressStarter\\`',
            "Namespace for PHP classes: `{$this->config['namespace']}\\`",
            $content
        );

        $content = str_replace(
            'Theme text domain: `wp-starter`',
            "Theme text domain: `{$this->config['text_domain']}`",
            $content
        );

        file_put_contents($claudePath, $content);
    }

    private function updateDocumentation(): void
    {
        $oldNamespace = 'WordpressStarter';
        $newNamespace = $this->config['namespace'];
        $oldTextDomain = 'wp-starter';
        $newTextDomain = $this->config['text_domain'];

        if ($oldNamespace === $newNamespace && $oldTextDomain === $newTextDomain) {
            return;
        }

        $docFiles = [
            $this->themeDir . '/README.md',
            $this->themeDir . '/README.MD',
            $this->themeDir . '/CONTRIBUTING.md',
            $this->themeDir . '/TROUBLESHOOTING.md',
        ];

        foreach ($docFiles as $docPath) {
            if (!file_exists($docPath)) {
                continue;
            }

            $content = file_get_contents($docPath);
            $original = $content;

            // Update namespace references in documentation
            $content = str_replace("{$oldNamespace}\\", "{$newNamespace}\\", $content);
            $content = str_replace("`{$oldNamespace}`", "`{$newNamespace}`", $content);
            // Update text domain references
            $content = str_replace("'{$oldTextDomain}'", "'{$newTextDomain}'", $content);
            $content = str_replace("`{$oldTextDomain}`", "`{$newTextDomain}`", $content);

            if ($content !== $original) {
                file_put_contents($docPath, $content);
            }
        }
    }

    private function savePluginPreferences(): void
    {
        // Plugins are now added to composer.json in updateComposerJson()
        // Remove old config file if it exists
        $oldConfigFile = $this->themeDir . '/config/plugins-to-install.php';
        if (file_exists($oldConfigFile)) {
            unlink($oldConfigFile);
        }
    }

    private function saveContentOptions(): void
    {
        $options = [
            'create_pages' => $this->config['create_pages'],
            'create_posts' => $this->config['create_posts'] ?? false,
            'delete_default_content' => $this->config['delete_default_content'],
            'set_permalink_structure' => $this->config['set_permalink_structure'],
            'pages' => $this->config['create_pages'] ? $this->samplePages : [],
            'posts' => ($this->config['create_posts'] ?? false) ? $this->samplePosts : [],
            'menu_assignments' => $this->config['create_pages'] ? $this->menuAssignments : [],
            'color_scheme' => $this->config['color_scheme'] ?? 'system',
        ];

        $configContent = "<?php\n\n";
        $configContent .= "declare(strict_types=1);\n\n";
        $configContent .= "/**\n";
        $configContent .= " * Auto-generated by setup script - " . date('Y-m-d H:i:s') . "\n";
        $configContent .= " * Content options for first-time setup\n";
        $configContent .= " *\n";
        $configContent .= " * @return array<string, mixed>\n";
        $configContent .= " */\n";
        $configContent .= "return " . var_export($options, true) . ";\n";

        file_put_contents($this->themeDir . '/config/setup-options.php', $configContent);
    }

    /**
     * Save ACF options that will be pre-filled on theme activation
     */
    private function saveAcfOptions(): void
    {
        $acfOptions = [
            // General settings
            'company_name' => $this->config['company_name'] ?? '',
            'address' => $this->config['company_address'] ?? '',
            'phone' => $this->config['company_phone'] ?? '',
            'email' => $this->config['company_email'] ?? '',
            'color_scheme' => $this->config['color_scheme'] ?? 'system',

            // Footer copyright
            'copyright_text' => '© {year} ' . ($this->config['company_name'] ?? $this->config['theme_name']) . '. Alle Rechte vorbehalten.',

            // Social links
            'social_links' => $this->socialLinks,

            // Analytics
            'pirsch_code' => $this->config['pirsch_code'] ?? '',
        ];

        $configContent = "<?php\n\n";
        $configContent .= "declare(strict_types=1);\n\n";
        $configContent .= "/**\n";
        $configContent .= " * Auto-generated by setup script - " . date('Y-m-d H:i:s') . "\n";
        $configContent .= " * ACF options to pre-fill on theme activation\n";
        $configContent .= " *\n";
        $configContent .= " * @return array<string, mixed>\n";
        $configContent .= " */\n";
        $configContent .= "return " . var_export($acfOptions, true) . ";\n";

        file_put_contents($this->themeDir . '/config/acf-options.php', $configContent);
    }

    /**
     * Remove blocks that were not selected
     */
    private function removeUnusedBlocks(): void
    {
        $blocksDir = $this->themeDir . '/blocks';
        $removedBlocks = array_keys(array_filter($this->blockSelections, fn($v) => !$v));

        foreach ($removedBlocks as $blockSlug) {
            $blockPath = $blocksDir . '/' . $blockSlug;
            if (is_dir($blockPath)) {
                $this->deleteDirectory($blockPath);
            }
        }
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Initialize git repository after setup
     */
    private function initGitRepo(): void
    {
        $this->printSection("Step 8: Git Repository");

        // Check if already a git repo
        if (is_dir($this->themeDir . '/.git')) {
            echo "  " . $this->color("Git repository already exists.", 'gray') . "\n";

            $makeCommit = strtolower($this->prompt(
                'Create initial commit with setup changes?',
                'y',
                '(y/n)'
            )) === 'y';

            if ($makeCommit) {
                $this->runCommand('Staging all changes', 'git add .');
                $commitMsg = 'Initial theme setup: ' . $this->config['theme_name'];
                $this->runCommand('Creating commit', "git commit -m " . escapeshellarg($commitMsg));

                // Check if remote exists and offer to push
                $this->offerToPush();
            }
            return;
        }

        $initGit = strtolower($this->prompt(
            'Initialize git repository?',
            'y',
            '(y/n)'
        )) === 'y';

        if (!$initGit) {
            echo "  " . $this->color("Skipped.", 'gray') . "\n";
            return;
        }

        $this->runCommand('Initializing git', 'git init');
        $this->runCommand('Staging all files', 'git add .');

        $commitMsg = 'Initial theme setup: ' . $this->config['theme_name'];
        $this->runCommand('Creating initial commit', "git commit -m " . escapeshellarg($commitMsg));

        echo "\n  " . $this->color("✓ Git repository initialized with initial commit.", 'green') . "\n";

        // Check if remote exists and offer to push
        $this->offerToPush();
    }

    /**
     * Check if a remote exists and offer to push
     */
    private function offerToPush(): void
    {
        // Check if origin remote exists
        $remoteUrl = trim(shell_exec('git remote get-url origin 2>/dev/null') ?? '');

        // If remote points to starter template, offer to set up own repo
        if (empty($remoteUrl) || $this->isStarterRepo($remoteUrl)) {
            $this->offerToSetupRemote($remoteUrl);
            return;
        }

        echo "\n  " . $this->color("Remote found: ", 'gray') . $this->color($remoteUrl, 'cyan') . "\n";

        $doPush = strtolower($this->prompt(
            'Push to remote?',
            'y',
            '(y/n)'
        )) === 'y';

        if ($doPush) {
            $this->runCommand('Pushing to remote', 'git push -u origin HEAD');
            echo "\n  " . $this->color("✓ Pushed to remote repository.", 'green') . "\n";
        }
    }

    /**
     * Offer to set up own repository when remote is missing or points to starter
     */
    private function offerToSetupRemote(string $currentRemote): void
    {
        // Check if we already collected a repo URL in step 1 (full mode)
        $collectedUrl = $this->config['theme_uri'] ?? '';
        if (!empty($collectedUrl) && !$this->isStarterRepo($collectedUrl)) {
            echo "\n  " . $this->color("Using repository from setup: ", 'gray') . $this->color($collectedUrl, 'cyan') . "\n";
            $this->setRemoteAndPush($collectedUrl, $currentRemote);
            return;
        }

        if (!empty($currentRemote)) {
            echo "\n  " . $this->color("⚠ Remote still points to starter template:", 'yellow') . "\n";
            echo "  " . $this->color($currentRemote, 'gray') . "\n\n";
        } else {
            echo "\n  " . $this->color("No remote repository configured.", 'gray') . "\n\n";
        }

        $hasGh = $this->isGhAuthenticated();

        if ($hasGh) {
            $choice = $this->prompt(
                'Set up your own repository?',
                'c',
                '[c]reate GitHub repo, [e]nter URL, [s]kip'
            );

            $choice = strtolower($choice);

            if ($choice === 'c' || $choice === 'create') {
                $repoUrl = $this->createGitHubRepo();
                if (!empty($repoUrl)) {
                    // Push to the new repo
                    $this->runCommand('Pushing to new repository', 'git push -u origin HEAD');
                    echo "\n  " . $this->color("✓ Pushed to new repository.", 'green') . "\n";
                } else {
                    echo "  " . $this->color("⚠ Repository creation failed. Try manually:", 'yellow') . "\n";
                    echo "  " . $this->color("git remote set-url origin <your-repo-url>", 'cyan') . "\n";
                }
            } elseif ($choice === 'e' || $choice === 'existing') {
                $newUrl = $this->prompt(
                    'Repository URL',
                    '',
                    'https://github.com/username/repo'
                );

                if (!empty($newUrl)) {
                    $this->setRemoteAndPush($newUrl, $currentRemote);
                }
            } else {
                echo "  " . $this->color("Skipped. You can set up a remote later with:", 'gray') . "\n";
                echo "  " . $this->color("git remote set-url origin <your-repo-url>", 'cyan') . "\n";
            }
        } else {
            echo "  " . $this->color("Tip: Install GitHub CLI (gh) to create repos automatically.", 'gray') . "\n";
            echo "  " . $this->color("https://cli.github.com/", 'cyan') . "\n\n";

            $newUrl = $this->prompt(
                'Enter repository URL',
                '',
                'Leave empty to skip'
            );

            if (!empty($newUrl)) {
                $this->setRemoteAndPush($newUrl, $currentRemote);
            } else {
                echo "  " . $this->color("Skipped. You can set up a remote later with:", 'gray') . "\n";
                echo "  " . $this->color("git remote set-url origin <your-repo-url>", 'cyan') . "\n";
            }
        }
    }

    /**
     * Set git remote and push
     */
    private function setRemoteAndPush(string $newUrl, string $currentRemote): void
    {
        // Update remote
        if (!empty($currentRemote)) {
            exec('git remote remove origin 2>/dev/null');
        }

        $output = [];
        $returnCode = 0;
        exec('git remote add origin ' . escapeshellarg($newUrl) . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            echo "  " . $this->color("⚠ Failed to set remote: " . implode("\n", $output), 'yellow') . "\n";
            return;
        }
        echo "  " . $this->color("✓ Remote set to: {$newUrl}", 'green') . "\n";

        // Push
        $doPush = strtolower($this->prompt(
            'Push to remote?',
            'y',
            '(y/n)'
        )) === 'y';

        if ($doPush) {
            $this->runCommand('Pushing to remote', 'git push -u origin HEAD');
        }
    }

    /**
     * Check if a URL points to the starter template repository
     */
    private function isStarterRepo(string $url): bool
    {
        $starterPatterns = [
            'raaaf/starter',
            'github.com/raaaf/starter',
        ];

        foreach ($starterPatterns as $pattern) {
            if (str_contains(strtolower($url), strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    private function createEnvFile(): void
    {
        $envExample = $this->themeDir . '/.env.example';
        $envFile = $this->themeDir . '/.env';

        if (!file_exists($envExample) || file_exists($envFile)) {
            return;
        }

        $content = file_get_contents($envExample);

        // Build local URL from site name (Local by Flywheel convention)
        $localUrl = 'http://wordpressstarter.local';
        if ($this->detectedSiteName) {
            // Local by Flywheel uses sitename.local format
            $slug = $this->slugify($this->detectedSiteName);
            $localUrl = "http://{$slug}.local";
        } elseif (!empty($this->config['theme_slug'])) {
            $localUrl = "http://{$this->config['theme_slug']}.local";
        }

        // Replace values with collected config
        $replacements = [
            'BROWSERSYNC_PROXY=http://wordpressstarter.local' => "BROWSERSYNC_PROXY={$localUrl}",
            'THEME_VERSION=0.0.1' => "THEME_VERSION={$this->config['version']}",
            'THEME_TEXT_DOMAIN=wp-starter' => "THEME_TEXT_DOMAIN={$this->config['text_domain']}",
        ];

        // Add Pirsch ID if configured
        if (!empty($this->config['pirsch_code'])) {
            $replacements['PIRSCH_ID='] = "PIRSCH_ID={$this->config['pirsch_code']}";
        }

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        file_put_contents($envFile, $content);
    }

    private function printSuccess(): void
    {
        echo "\n";
        echo $this->color("╔══════════════════════════════════════════════════════════════════╗\n", 'green');
        echo $this->color("║                                                                  ║\n", 'green');
        echo $this->color("║   ", 'green') . $this->color("Setup Complete!", 'white', true) . $this->color("                                            ║\n", 'green');
        echo $this->color("║                                                                  ║\n", 'green');
        echo $this->color("╚══════════════════════════════════════════════════════════════════╝\n", 'green');
        echo "\n";

        if ($this->dependenciesInstalled) {
            // Dependencies were installed automatically
            echo $this->color("✓ All dependencies installed successfully!\n", 'green');
            echo "\n";
            echo $this->color("Next Steps:\n", 'white', true);
            echo "\n";
            echo "  " . $this->color("1.", 'yellow') . " Activate the theme in WordPress\n";
            echo "     Go to: " . $this->color("Design → Themes", 'cyan') . "\n";
            echo "\n";
            echo "  " . $this->color("2.", 'yellow') . " Complete the Theme Setup\n";
            echo "     The wizard will guide you through plugin installation.\n";
            echo "\n";
            echo "  " . $this->color("3.", 'yellow') . " For development with hot reload:\n";
            echo "     " . $this->color("npm run dev", 'cyan') . "\n";
            echo "\n";
        } else {
            // Dependencies were not installed - show manual steps
            echo $this->color("Next Steps:\n", 'white', true);
            echo "\n";
            echo "  " . $this->color("1.", 'yellow') . " Update Composer autoloading:\n";
            echo "     " . $this->color("composer dump-autoload", 'cyan') . "\n";
            echo "\n";
            echo "  " . $this->color("2.", 'yellow') . " Install dependencies:\n";
            echo "     " . $this->color("composer update && npm install && npm run build", 'cyan') . "\n";
            echo "\n";
            echo "  " . $this->color("3.", 'yellow') . " Activate the theme in WordPress\n";
            echo "     Go to: " . $this->color("Design → Themes", 'cyan') . "\n";
            echo "\n";
            echo "  " . $this->color("4.", 'yellow') . " For development with hot reload:\n";
            echo "     " . $this->color("npm run dev", 'cyan') . "\n";
            echo "\n";
        }

        if (!empty(array_filter($this->pluginSelections))) {
            $pluginCount = count(array_filter($this->pluginSelections));
            echo $this->color("✓ {$pluginCount} plugins added to composer.json - will be installed with composer update.\n", 'green');
            echo $this->color("  Note: ACF PRO must be installed manually (premium plugin).\n", 'gray');
            echo "\n";
        }

        echo $this->color("💡 Tip: ", 'yellow') . "Add this to your " . $this->color("wp-config.php", 'cyan') . " for auto-debug when Vite runs:\n";
        echo $this->color("   \$vite_dev = @fsockopen('localhost', 5173, \$e, \$m, 0.1) !== false;\n", 'gray');
        echo $this->color("   define('WP_DEBUG', \$vite_dev);\n", 'gray');
        echo "\n";

        echo $this->color("To revert all changes: ", 'gray') . $this->color("git checkout .", 'cyan') . "\n";
        echo "\n";
    }

    // === Helper Methods ===

    private function prompt(string $question, string $default = '', string $hint = ''): string
    {
        $defaultDisplay = $default !== '' ? " [{$default}]" : '';
        $hintDisplay = $hint ? $this->color(" ({$hint})", 'gray') : '';

        echo "  {$question}{$hintDisplay}{$defaultDisplay}: ";

        $input = trim(fgets(STDIN));
        return $input !== '' ? $input : $default;
    }

    /**
     * Prompt with validation - keeps asking until valid input or empty (uses default)
     */
    private function promptWithValidation(string $question, string $default, string $type, string $hint = ''): string
    {
        while (true) {
            $value = $this->prompt($question, $default, $hint);

            // Empty value uses default, which we assume is valid
            if ($value === $default) {
                return $value;
            }

            // Validate based on type
            $isValid = match ($type) {
                'email' => $this->isValidEmail($value),
                'url' => $this->isValidUrl($value),
                default => true,
            };

            if ($isValid) {
                return $value;
            }

            // Show error and retry
            $errorMsg = match ($type) {
                'email' => 'Ungültige E-Mail-Adresse. Bitte erneut eingeben.',
                'url' => 'Ungültige URL (muss mit http:// oder https:// beginnen). Bitte erneut eingeben.',
                default => 'Ungültige Eingabe. Bitte erneut eingeben.',
            };
            echo "  " . $this->color("⚠ {$errorMsg}", 'yellow') . "\n";
        }
    }

    /**
     * Validate email address
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     */
    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function pressEnterToContinue(): void
    {
        echo $this->color("Press Enter to continue...", 'gray');
        fgets(STDIN);
    }

    private function printSection(string $title): void
    {
        echo "\n";
        echo $this->color("┌─ ", 'cyan') . $this->color($title, 'white', true) . $this->color(" ", 'cyan');
        echo str_repeat("─", max(0, 50 - strlen($title))) . "\n";
        echo "\n";
    }

    private function printSubSection(string $title): void
    {
        echo "  " . $this->color("▸ {$title}", 'yellow') . "\n\n";
    }

    private function clearScreen(): void
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            system('cls');
        } else {
            system('clear');
        }
    }

    private function color(string $text, string $color, bool $bold = false): string
    {
        // Skip colors if not in a TTY
        if (!stream_isatty(STDOUT)) {
            return $text;
        }

        $colors = [
            'black' => '30',
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'magenta' => '35',
            'cyan' => '36',
            'white' => '37',
            'gray' => '90',
        ];

        $code = $colors[$color] ?? '37';
        $boldCode = $bold ? '1;' : '';

        return "\033[{$boldCode}{$code}m{$text}\033[0m";
    }

    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = preg_replace('/[^a-z0-9-]/', '', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }

    private function pascalCase(string $text): string
    {
        $words = preg_split('/[\s\-_]+/', $text);
        $words = array_map('ucfirst', $words);
        return implode('', $words);
    }

    private function getPluginName(string $slug): string
    {
        foreach ($this->availablePlugins as $category) {
            if (isset($category['plugins'][$slug])) {
                return $category['plugins'][$slug]['name'];
            }
        }
        return $slug;
    }

    private function findFiles(string $directory, string $pattern): array
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (fnmatch($pattern, $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}

// Run the setup
$setup = new ThemeSetup();
$setup->run();
