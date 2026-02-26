<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Tests that all namespace references consistently use the project namespace.
 *
 * The expected namespace is read from composer.json PSR-4 autoload config,
 * so this test works both for the original starter theme and after setup.
 */
final class NamespaceConsistencyTest extends TestCase
{
    private string $basePath;
    private string $projectNamespace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 2);
        $this->projectNamespace = $this->resolveProjectNamespace();
    }

    /**
     * Read the project namespace from composer.json PSR-4 autoload.
     */
    private function resolveProjectNamespace(): string
    {
        $composerFile = $this->basePath . '/composer.json';
        $composer = json_decode(file_get_contents($composerFile), true);

        foreach ($composer['autoload']['psr-4'] ?? [] as $namespace => $path) {
            if ($path === 'src/') {
                return rtrim($namespace, '\\');
            }
        }

        $this->fail('No PSR-4 autoload entry mapping to src/ found in composer.json');
    }

    public function testPhpFilesUseProjectNamespace(): void
    {
        $srcPath = $this->basePath . '/src';
        $errors = [];

        foreach ($this->getPhpFiles($srcPath) as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace($this->basePath . '/', '', $file);

            // Check for namespace declaration
            if (preg_match('/^namespace\s+([A-Za-z0-9_\\\\]+);/m', $content, $matches)) {
                $namespace = $matches[1];
                if (!str_starts_with($namespace, $this->projectNamespace)) {
                    $errors[] = "{$relativePath}: Invalid namespace '{$namespace}' (expected '{$this->getExpectedNamespace($file)}')";
                }
            }

            // Check for use statements with non-standard namespaces
            preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)(?:\s+as\s+[A-Za-z0-9_]+)?;/m', $content, $matches);
            foreach ($matches[1] as $usedNamespace) {
                if ($this->isProjectNamespace($usedNamespace) && !str_starts_with($usedNamespace, $this->projectNamespace . '\\')) {
                    $errors[] = "{$relativePath}: Invalid use statement '{$usedNamespace}' (should start with '" . $this->projectNamespace . "\\')";
                }
            }
        }

        $this->assertEmpty($errors, "Namespace inconsistencies found:\n" . implode("\n", $errors));
    }

    public function testBladeTemplatesUseProjectNamespace(): void
    {
        $templatesPath = $this->basePath . '/templates';
        $errors = [];

        foreach ($this->getBladeFiles($templatesPath) as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace($this->basePath . '/', '', $file);

            // Check for fully qualified namespace references in Blade templates
            // Pattern: \SomeNamespace\Class (common in Blade PHP blocks)
            preg_match_all('/\\\\([A-Z][A-Za-z0-9_]*)\\\\([A-Za-z0-9_\\\\]+)/', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $fullNamespace = $match[1] . '\\' . $match[2];

                // Skip known external namespaces
                if ($this->isExternalNamespace($match[1])) {
                    continue;
                }

                // Check if it looks like a project namespace but isn't WordpressStarter
                if ($this->looksLikeProjectNamespace($match[1]) && $match[1] !== $this->projectNamespace) {
                    $errors[] = "{$relativePath}: Invalid namespace reference '\\{$fullNamespace}' (should use '\\" . $this->projectNamespace . "\\')";
                }
            }
        }

        $this->assertEmpty($errors, "Namespace inconsistencies in Blade templates:\n" . implode("\n", $errors));
    }

    public function testComposerJsonHasExactlyOneProjectNamespace(): void
    {
        $composerFile = $this->basePath . '/composer.json';
        $this->assertFileExists($composerFile, 'composer.json not found');

        $composer = json_decode(file_get_contents($composerFile), true);
        $this->assertIsArray($composer, 'Failed to parse composer.json');

        $autoload = $composer['autoload']['psr-4'] ?? [];

        $this->assertArrayHasKey(
            $this->projectNamespace . '\\',
            $autoload,
            "composer.json autoload should have '" . $this->projectNamespace . "\\' PSR-4 entry"
        );

        // Ensure no other project-like namespaces exist
        foreach (array_keys($autoload) as $namespace) {
            if ($namespace !== $this->projectNamespace . '\\' && $this->looksLikeProjectNamespace(rtrim($namespace, '\\'))) {
                $this->fail("composer.json has unexpected namespace '{$namespace}' (should only have '" . $this->projectNamespace . "\\')");
            }
        }
    }

    public function testFunctionsPhpUsesProjectNamespace(): void
    {
        $functionsFile = $this->basePath . '/functions.php';
        $this->assertFileExists($functionsFile, 'functions.php not found');

        $content = file_get_contents($functionsFile);

        // Check namespace declaration
        if (preg_match('/^namespace\s+([A-Za-z0-9_\\\\]+);/m', $content, $matches)) {
            $this->assertSame(
                $this->projectNamespace,
                $matches[1],
                "functions.php should have namespace '" . $this->projectNamespace . "'"
            );
        }

        // Check use statements
        preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)/m', $content, $matches);
        foreach ($matches[1] as $usedNamespace) {
            if ($this->isProjectNamespace($usedNamespace) && !str_starts_with($usedNamespace, $this->projectNamespace . '\\')) {
                $this->fail("functions.php has invalid use statement '{$usedNamespace}'");
            }
        }
    }

    /**
     * Get all PHP files in a directory recursively.
     *
     * @return iterable<string>
     */
    private function getPhpFiles(string $directory): iterable
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }

    /**
     * Get all Blade template files in a directory recursively.
     *
     * @return iterable<string>
     */
    private function getBladeFiles(string $directory): iterable
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                yield $file->getPathname();
            }
        }
    }

    /**
     * Check if a namespace is likely a project namespace (not external).
     */
    private function isProjectNamespace(string $namespace): bool
    {
        $firstPart = explode('\\', $namespace)[0];
        return !$this->isExternalNamespace($firstPart);
    }

    /**
     * Check if a namespace root is a known external namespace.
     */
    private function isExternalNamespace(string $namespaceRoot): bool
    {
        $externalNamespaces = [
            'Illuminate',
            'PHPUnit',
            'Tests',
            'Psr',
            'Symfony',
            'Carbon',
            'enshrined',
            'BladeOne',
            'Composer',
            'PhpOption',
            'GrahamCampbell',
            'Dotenv',
            'voku',
            'Ramsey',
            'Brick',
            'DateTimeInterface',
            'DateTime',
            'Exception',
            'Throwable',
            'stdClass',
            'Closure',
            'Generator',
            'Iterator',
            'ArrayAccess',
            'Countable',
            'JsonSerializable',
            // External packages
            'YahnisElsts',  // Plugin Update Checker
            'Spatie',       // Schema.org
            'SchemaOrg',    // Schema.org (alternate)
            'phpseclib3',   // SFTP client
            // Internal sub-namespaces (to avoid false positives in Blade templates)
            'PostTypes',
            'Taxonomies',
            'Providers',
        ];

        return in_array($namespaceRoot, $externalNamespaces, true);
    }

    /**
     * Check if a string looks like it could be a project namespace.
     * Project namespaces typically are PascalCase with multiple words.
     */
    private function looksLikeProjectNamespace(string $name): bool
    {
        // Skip PHP built-in types and common external namespaces
        if ($this->isExternalNamespace($name)) {
            return false;
        }

        // Look for PascalCase names that could be project namespaces
        // Typically these have multiple capital letters (e.g., WordpressStarter, MyProject)
        return preg_match('/^[A-Z][a-z]+[A-Z]/', $name) === 1;
    }

    /**
     * Get the expected namespace for a file based on its path.
     */
    private function getExpectedNamespace(string $filePath): string
    {
        $relativePath = str_replace($this->basePath . '/src/', '', $filePath);
        $directory = dirname($relativePath);

        if ($directory === '.') {
            return $this->projectNamespace;
        }

        return $this->projectNamespace . '\\' . str_replace('/', '\\', $directory);
    }
}
