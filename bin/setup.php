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
    private bool $dependenciesInstalled = false;

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
    ];

    public function __construct()
    {
        $this->themeDir = getcwd();
    }

    public function run(): void
    {
        $this->printHeader();
        $this->collectThemeInfo();
        $this->collectPluginPreferences();
        $this->collectContentOptions();
        $this->confirmChanges();
        $this->applyChanges();
        $this->runInstallCommands();
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
        echo "This wizard will help you:\n";
        echo "  " . $this->color("1.", 'yellow') . " Set up theme information (name, author, etc.)\n";
        echo "  " . $this->color("2.", 'yellow') . " Select plugins to auto-install on first WordPress login\n";
        echo "  " . $this->color("3.", 'yellow') . " Configure initial content options\n";
        echo "  " . $this->color("4.", 'yellow') . " Install dependencies (composer & npm)\n";
        echo "\n";
        echo $this->color("Press Ctrl+C at any time to cancel.\n", 'gray');
        echo "\n";
        $this->pressEnterToContinue();
    }

    private function collectThemeInfo(): void
    {
        $this->printSection("Step 1: Theme Information");
        echo "Configure the basic theme details.\n";
        echo $this->color("(Press Enter to keep default value shown in brackets)\n\n", 'gray');

        $this->config['theme_name'] = $this->prompt(
            'Theme Name',
            $this->defaults['theme_name'],
            'The display name of your theme'
        );

        $this->config['theme_slug'] = $this->prompt(
            'Theme Slug',
            $this->slugify($this->config['theme_name']),
            'Lowercase with hyphens, used for folders and IDs'
        );

        $this->config['text_domain'] = $this->prompt(
            'Text Domain',
            $this->config['theme_slug'],
            'Used for translations (usually same as slug)'
        );

        $this->config['namespace'] = $this->prompt(
            'PHP Namespace',
            $this->pascalCase($this->config['theme_name']),
            'PascalCase, for PHP classes'
        );

        $this->config['description'] = $this->prompt(
            'Description',
            $this->defaults['description'],
            'Short description of your theme'
        );

        echo "\n";
        $this->printSubSection("Author Information");

        $this->config['author'] = $this->prompt(
            'Author Name',
            $this->defaults['author']
        );

        $this->config['author_email'] = $this->prompt(
            'Author Email',
            $this->defaults['author_email']
        );

        $this->config['author_uri'] = $this->prompt(
            'Author Website',
            $this->defaults['author_uri']
        );

        echo "\n";
        $this->printSubSection("Repository & Version");

        $this->config['theme_uri'] = $this->prompt(
            'Theme/Repository URL',
            $this->defaults['theme_uri']
        );

        $this->config['version'] = $this->prompt(
            'Initial Version',
            '1.0.0'
        );

        $this->config['package_name'] = $this->prompt(
            'Composer Package',
            strtolower($this->slugify($this->config['author']) . '/' . $this->config['theme_slug']),
            'Format: vendor/package'
        );
    }

    private function collectPluginPreferences(): void
    {
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
        $this->printSection("Step 3: Content Options");
        echo "Configure what should happen when WordPress is set up.\n\n";

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
    }

    private function confirmChanges(): void
    {
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

        echo $this->color("Content Options:\n", 'white', true);
        echo "  Create pages:          " . ($this->config['create_pages'] ? $this->color('Yes', 'green') : $this->color('No', 'red')) . "\n";
        echo "  Delete default content: " . ($this->config['delete_default_content'] ? $this->color('Yes', 'green') : $this->color('No', 'red')) . "\n";
        echo "  Pretty permalinks:     " . ($this->config['set_permalink_structure'] ? $this->color('Yes', 'green') : $this->color('No', 'red')) . "\n";
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
        $this->task('Updating composer.json', fn() => $this->updateComposerJson());
        $this->task('Updating PHP namespaces', fn() => $this->updatePhpFiles());
        $this->task('Updating Blade templates', fn() => $this->updateBladeTemplates());
        $this->task('Updating block.json files', fn() => $this->updateBlockJsonFiles());
        $this->task('Updating CLAUDE.md', fn() => $this->updateClaudeMd());
        $this->task('Updating documentation files', fn() => $this->updateDocumentation());
        $this->task('Saving plugin preferences', fn() => $this->savePluginPreferences());
        $this->task('Saving content options', fn() => $this->saveContentOptions());
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
        $this->printSection("Step 4: Installing Dependencies");

        echo "The theme requires dependencies to be installed before it can be used.\n\n";

        $runInstall = strtolower($this->prompt(
            'Run install commands automatically?',
            'y',
            'composer install, npm install, npm run build (y/n)'
        )) === 'y';

        if (!$runInstall) {
            echo "\n" . $this->color("Skipped. Run these commands manually:", 'yellow') . "\n";
            echo "  " . $this->color("composer dump-autoload && composer install", 'cyan') . "\n";
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
            $this->runCommand('Installing PHP dependencies', 'composer install --no-interaction');
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

            // Update namespace declarations
            $content = str_replace("namespace {$oldNamespace}", "namespace {$newNamespace}", $content);
            // Update use statements
            $content = str_replace("use {$oldNamespace}\\", "use {$newNamespace}\\", $content);
            // Update fully qualified class names in strings
            $content = str_replace("'{$oldNamespace}\\\\", "'{$newNamespace}\\\\", $content);
            $content = str_replace("\"{$oldNamespace}\\\\", "\"{$newNamespace}\\\\", $content);
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
        $selectedPlugins = array_keys(array_filter($this->pluginSelections));

        $configContent = "<?php\n\n";
        $configContent .= "declare(strict_types=1);\n\n";
        $configContent .= "/**\n";
        $configContent .= " * Auto-generated by setup script - " . date('Y-m-d H:i:s') . "\n";
        $configContent .= " * Plugins to auto-install on theme activation\n";
        $configContent .= " *\n";
        $configContent .= " * @return array<string>\n";
        $configContent .= " */\n";
        $configContent .= "return " . var_export($selectedPlugins, true) . ";\n";

        $configDir = $this->themeDir . '/config';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        file_put_contents($configDir . '/plugins-to-install.php', $configContent);
    }

    private function saveContentOptions(): void
    {
        $options = [
            'create_pages' => $this->config['create_pages'],
            'delete_default_content' => $this->config['delete_default_content'],
            'set_permalink_structure' => $this->config['set_permalink_structure'],
            'pages' => $this->config['create_pages'] ? $this->samplePages : [],
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

    private function createEnvFile(): void
    {
        $envExample = $this->themeDir . '/.env.example';
        $envFile = $this->themeDir . '/.env';

        if (!file_exists($envExample) || file_exists($envFile)) {
            return;
        }

        copy($envExample, $envFile);
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
            echo "     " . $this->color("composer install && npm install && npm run build", 'cyan') . "\n";
            echo "\n";
            echo "  " . $this->color("3.", 'yellow') . " Activate the theme in WordPress\n";
            echo "     Go to: " . $this->color("Design → Themes", 'cyan') . "\n";
            echo "\n";
            echo "  " . $this->color("4.", 'yellow') . " For development with hot reload:\n";
            echo "     " . $this->color("npm run dev", 'cyan') . "\n";
            echo "\n";
        }

        if (!empty(array_filter($this->pluginSelections))) {
            echo $this->color("Selected plugins will be auto-installed when you activate the theme.\n", 'gray');
            echo "\n";
        }

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
