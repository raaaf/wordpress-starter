#!/usr/bin/env php
<?php

/**
 * WordPress Starter Theme Setup Script
 * 
 * This script helps configure the theme for first-time installation
 */

class ThemeSetup
{
    private array $config = [];
    private array $files = [];
    
    public function __construct()
    {
        $this->files = [
            'composer.json',
            'package.json',
            'config/app.php',
            '.env.example',
            'style.css',
            'CLAUDE.md',
            'README.md',
            'src/**/*.php',
            'config/**/*.php',
        ];
    }
    
    public function run(): void
    {
        $this->printHeader();
        
        // Check for config file argument
        global $argv;
        if (isset($argv[1]) && $argv[1] === '--config' && isset($argv[2])) {
            $this->loadConfigFromFile($argv[2]);
        } else {
            $this->gatherConfiguration();
        }
        
        $this->confirmConfiguration();
        $this->applyConfiguration();
        $this->createEnvFile();
        $this->runPostSetup();
        $this->printSuccess();
    }
    
    private function printHeader(): void
    {
        echo "\n";
        echo "====================================\n";
        echo "WordPress Starter Theme Setup\n";
        echo "====================================\n\n";
        echo "This script will help you configure the theme for your project.\n";
        echo "Usage: php setup.php [--config <file>]\n\n";
    }
    
    private function loadConfigFromFile(string $filename): void
    {
        if (!file_exists($filename)) {
            echo "Error: Configuration file '$filename' not found.\n";
            exit(1);
        }
        
        $json = file_get_contents($filename);
        $config = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error: Invalid JSON in configuration file.\n";
            exit(1);
        }
        
        $this->config = $config;

        // Derive repo_url from repo_name if not explicitly set
        if (empty($this->config['repo_url']) && !empty($this->config['repo_name'])) {
            $this->config['repo_url'] = "https://github.com/{$this->config['repo_name']}";
        }

        echo "✓ Loaded configuration from $filename\n\n";
    }
    
    private function gatherConfiguration(): void
    {
        // Theme Name
        $this->config['theme_name'] = $this->ask(
            'Theme name',
            'My WordPress Theme'
        );
        
        // Theme Slug (for text domain)
        $defaultSlug = $this->slugify($this->config['theme_name']);
        $this->config['theme_slug'] = $this->ask(
            'Theme slug (text domain)',
            $defaultSlug
        );
        
        // Namespace
        $defaultNamespace = $this->pascalCase($this->config['theme_slug']);
        $this->config['namespace'] = $this->ask(
            'PHP namespace',
            $defaultNamespace
        );
        
        // Development URL
        $this->config['dev_url'] = $this->ask(
            'Local development URL',
            "http://{$this->config['theme_slug']}.local"
        );
        
        // Author
        $this->config['author_name'] = $this->ask(
            'Author name',
            'Your Name'
        );
        
        $this->config['author_email'] = $this->ask(
            'Author email',
            'your@email.com'
        );
        
        $this->config['author_uri'] = $this->ask(
            'Author website',
            'https://yourwebsite.com'
        );
        
        // Repository
        $defaultRepo = "raaaf/{$this->config['theme_slug']}";
        $this->config['repo_name'] = $this->ask(
            'GitHub repository name (e.g. raaaf/my-theme)',
            $defaultRepo
        );
        $this->config['repo_url'] = "https://github.com/{$this->config['repo_name']}";
    }
    
    private function confirmConfiguration(): void
    {
        echo "\n";
        echo "====================================\n";
        echo "Configuration Summary\n";
        echo "====================================\n";
        echo "Theme Name: {$this->config['theme_name']}\n";
        echo "Theme Slug: {$this->config['theme_slug']}\n";
        echo "Namespace: {$this->config['namespace']}\n";
        echo "Dev URL: {$this->config['dev_url']}\n";
        echo "Author: {$this->config['author_name']} <{$this->config['author_email']}>\n";
        echo "Author URI: {$this->config['author_uri']}\n";
        echo "GitHub Repo: {$this->config['repo_name']}\n";
        echo "Repository URL: {$this->config['repo_url']}\n";
        echo "\n";
        
        $confirm = $this->ask('Is this correct? (y/n)', 'y');
        
        if (strtolower($confirm) !== 'y') {
            echo "\nSetup cancelled.\n";
            exit(1);
        }
    }
    
    private function applyConfiguration(): void
    {
        echo "\nApplying configuration...\n";
        
        // Update composer.json
        $this->updateJsonFile('composer.json', [
            'name' => $this->slugify($this->config['author_name']) . '/' . $this->config['theme_slug'],
            'description' => $this->config['theme_name'] . ' WordPress Theme',
            'authors' => [[
                'name' => $this->config['author_name'],
                'email' => $this->config['author_email']
            ]],
            'autoload' => [
                'psr-4' => [
                    $this->config['namespace'] . '\\' => 'src/'
                ],
                'files' => ['src/helpers.php']
            ]
        ]);
        
        // Update package.json
        $this->updateJsonFile('package.json', [
            'name' => $this->config['theme_slug'],
            'description' => $this->config['theme_name'] . ' WordPress Theme',
            'author' => $this->config['author_name'],
            'repository' => [
                'type' => 'git',
                'url' => $this->config['repo_url']
            ],
            'theme_uri' => $this->config['repo_url'],
            'author_uri' => $this->config['author_uri'],
            'text_domain' => $this->config['theme_slug']
        ]);
        
        // Update PHP files with new namespace
        $this->updateNamespaceInPhpFiles();

        // Update ThemeUpdateProvider
        $this->updateThemeUpdateProvider();

        // Update config files
        $this->updateConfigFiles();

        // Create style.css
        $this->createStyleCss();
    }
    
    private function updateJsonFile(string $filename, array $updates): void
    {
        if (!file_exists($filename)) {
            echo "Warning: $filename not found\n";
            return;
        }
        
        $json = json_decode(file_get_contents($filename), true);
        $json = array_merge($json, $updates);
        
        file_put_contents($filename, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        echo "✓ Updated $filename\n";
    }
    
    private function updateNamespaceInPhpFiles(): void
    {
        // Update namespace declarations
        $this->replaceInFiles(
            'src/**/*.php',
            'namespace WordpressStarter',
            'namespace ' . $this->config['namespace']
        );
        
        // Update use statements
        $this->replaceInFiles(
            'src/**/*.php',
            'use WordpressStarter\\',
            'use ' . $this->config['namespace'] . '\\'
        );
        
        // Update config files
        $this->replaceInFiles(
            'config/*.php',
            'WordpressStarter\\',
            $this->config['namespace'] . '\\'
        );
        
        // Update templates
        $this->replaceInFiles(
            'templates/**/*.blade.php',
            'WordpressStarter\\',
            $this->config['namespace'] . '\\'
        );
        
        echo "✓ Updated PHP namespaces\n";
    }
    
    private function updateThemeUpdateProvider(): void
    {
        $file = 'src/Providers/ThemeUpdateProvider.php';

        $this->replaceInFile(
            $file,
            "private const GITHUB_REPO = 'https://github.com/raaaf/starter/';",
            "private const GITHUB_REPO = 'https://github.com/{$this->config['repo_name']}/';"
        );

        $this->replaceInFile(
            $file,
            "private const THEME_SLUG = 'wp-starter';",
            "private const THEME_SLUG = '{$this->config['theme_slug']}';"
        );

        echo "✓ Updated ThemeUpdateProvider.php\n";
    }

    private function updateConfigFiles(): void
    {
        // Update .env.example
        $this->replaceInFile(
            '.env.example',
            'BROWSERSYNC_PROXY=http://wordpressstarter.local',
            'BROWSERSYNC_PROXY=' . $this->config['dev_url']
        );
        
        $this->replaceInFile(
            '.env.example',
            'THEME_TEXT_DOMAIN=wp-starter',
            'THEME_TEXT_DOMAIN=' . $this->config['theme_slug']
        );
        
        // Update config/app.php
        if (file_exists('config/app.php')) {
            $this->replaceInFile(
                'config/app.php',
                "'text_domain' => 'wp-starter'",
                "'text_domain' => '{$this->config['theme_slug']}'"
            );
            
            $this->replaceInFile(
                'config/app.php',
                "'name' => 'WP Starter'",
                "'name' => '{$this->config['theme_name']}'"
            );
            
            $this->replaceInFile(
                'config/app.php',
                "'author' => 'Rafael Alex'",
                "'author' => '{$this->config['author_name']}'"
            );
            
            $this->replaceInFile(
                'config/app.php',
                "'author_uri' => 'https://rafaelalex.de'",
                "'author_uri' => '{$this->config['author_uri']}'"
            );
        }
        
        echo "✓ Updated configuration files\n";
    }
    
    private function createStyleCss(): void
    {
        $content = "/*
Theme Name: {$this->config['theme_name']}
Theme URI: {$this->config['repo_url']}
Author: {$this->config['author_name']}
Author URI: {$this->config['author_uri']}
Description: A modern WordPress starter theme with Vite, Alpine.js, and TailwindCSS
Version: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT
Text Domain: {$this->config['theme_slug']}
Domain Path: /languages
Tags: custom-background, custom-logo, custom-menu, featured-images, threaded-comments, translation-ready

This theme, like WordPress, is licensed under the GPL.
Use it to make something cool, have fun, and share what you've learned with others.
*/

/* Theme styles are compiled in dist/app.css */
";
        
        file_put_contents('style.css', $content);
        echo "✓ Created style.css\n";
    }
    
    private function createEnvFile(): void
    {
        if (!file_exists('.env') && file_exists('.env.example')) {
            copy('.env.example', '.env');
            
            // Update .env with development URL
            $this->replaceInFile(
                '.env',
                'BROWSERSYNC_PROXY=http://wordpressstarter.local',
                'BROWSERSYNC_PROXY=' . $this->config['dev_url']
            );
            
            echo "✓ Created .env file\n";
        }
    }
    
    private function runPostSetup(): void
    {
        echo "\nRunning post-setup tasks...\n";
        
        // Update CLAUDE.md
        $this->replaceInFile(
            'CLAUDE.md',
            '- **Namespace:** `WordpressStarter\`',
            '- **Namespace:** `' . $this->config['namespace'] . '\`'
        );

        $this->replaceInFile(
            'CLAUDE.md',
            '- **Text Domain:** `wp-starter`',
            '- **Text Domain:** `' . $this->config['theme_slug'] . '`'
        );

        echo "✓ Updated CLAUDE.md\n";
    }
    
    private function printSuccess(): void
    {
        echo "\n";
        echo "====================================\n";
        echo "✅ Setup Complete!\n";
        echo "====================================\n\n";
        echo "Next steps:\n";
        echo "1. Run 'composer install' to install PHP dependencies\n";
        echo "2. Run 'npm install' to install Node dependencies\n";
        echo "3. Update your local hosts file to point {$this->config['dev_url']} to your local server\n";
        echo "4. Run 'npm run dev' to start development\n\n";
        echo "Happy coding! 🚀\n\n";
    }
    
    private function ask(string $question, string $default = ''): string
    {
        $defaultText = $default ? " [$default]" : '';
        echo "$question$defaultText: ";
        
        $input = trim(fgets(STDIN));
        
        return $input ?: $default;
    }
    
    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        return empty($text) ? 'theme' : $text;
    }
    
    private function pascalCase(string $text): string
    {
        $text = str_replace(['-', '_'], ' ', $text);
        $text = ucwords($text);
        $text = str_replace(' ', '', $text);
        
        return $text;
    }
    
    private function replaceInFile(string $file, string $search, string $replace): void
    {
        if (!file_exists($file)) {
            return;
        }
        
        $content = file_get_contents($file);
        $content = str_replace($search, $replace, $content);
        file_put_contents($file, $content);
    }
    
    private function replaceInFiles(string $pattern, string $search, string $replace): void
    {
        $files = glob($pattern, GLOB_BRACE);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $this->replaceInFile($file, $search, $replace);
            }
        }
        
        // Handle recursive patterns
        if (strpos($pattern, '**') !== false) {
            $dir = dirname(str_replace('**', '', $pattern));
            $filePattern = basename($pattern);

            if (!is_dir($dir)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && fnmatch($filePattern, $file->getFilename())) {
                    $this->replaceInFile($file->getPathname(), $search, $replace);
                }
            }
        }
    }
}

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Run the setup
$setup = new ThemeSetup();
$setup->run();