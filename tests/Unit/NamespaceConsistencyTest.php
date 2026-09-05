<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
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
        $composerContents = file_get_contents($composerFile);
        if ($composerContents === false) {
            $this->fail("Could not read {$composerFile}");
        }

        $composer = json_decode($composerContents, true);
        if (!is_array($composer)) {
            $this->fail("Could not parse {$composerFile} as JSON");
        }

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

            $errors = [...$errors, ...$this->checkPhpFileNamespaces($relativePath, $content, $file)];
        }

        $this->assertEmpty($errors, "Namespace inconsistencies found:\n" . implode("\n", $errors));
    }

    /**
     * Check a single src/ PHP file's namespace declaration, use statements and inline
     * fully-qualified references for namespace inconsistencies. Extracted so unit tests
     * can exercise the detection logic directly, without needing real fixture files.
     *
     * @return string[] Error messages, empty when the file is consistent.
     */
    private function checkPhpFileNamespaces(string $relativePath, string $content, ?string $file = null): array
    {
        $errors = [];

        // Check for namespace declaration
        if (preg_match('/^namespace\s+([A-Za-z0-9_\\\\]+);/m', $content, $matches)) {
            $namespace = $matches[1];
            if (!str_starts_with($namespace, $this->projectNamespace)) {
                $expected = $file !== null ? $this->getExpectedNamespace($file) : $this->projectNamespace;
                $errors[] = "{$relativePath}: Invalid namespace '{$namespace}' (expected '{$expected}')";
            }
        }

        // Check for use statements with non-standard namespaces
        preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)(?:\s+as\s+[A-Za-z0-9_]+)?;/m', $content, $matches);
        foreach ($matches[1] as $usedNamespace) {
            if ($this->isProjectNamespace($usedNamespace) && !str_starts_with($usedNamespace, $this->projectNamespace . '\\')) {
                $errors[] = "{$relativePath}: Invalid use statement '{$usedNamespace}' (should start with '" . $this->projectNamespace . "\\')";
            }
        }

        // Check for inline fully-qualified namespace references (e.g. \\GoldeneStrategie\\Foo::bar()).
        // These survive a propagation sweep even with no `use` statement carrying them,
        // so `\\WordpressStarter\\` stays allowed but every other fork's namespace is flagged.
        preg_match_all('/\\\\([A-Za-z][A-Za-z0-9_]*)\\\\([A-Za-z0-9_\\\\]+)/', $content, $inlineMatches, PREG_SET_ORDER);
        foreach ($inlineMatches as $inlineMatch) {
            if (in_array($inlineMatch[1], $this->knownForeignNamespaces(), true)) {
                $errors[] = "{$relativePath}: Invalid inline namespace reference '\\{$inlineMatch[1]}\\{$inlineMatch[2]}' (should use '\\" . $this->projectNamespace . "\\')";
            }
        }

        return $errors;
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function foreignNamespaceProvider(): iterable
    {
        yield 'goldene-strategie' => ['GoldeneStrategie'];
        yield 'stiftungs-navigator' => ['StiftungsNavigator'];
        yield 'moenius' => ['moenius'];
        yield 'siera' => ['Siera'];
        yield 'fim-vertrieb' => ['FIMVertrieb'];
    }

    #[DataProvider('foreignNamespaceProvider')]
    public function testLooksLikeProjectNamespaceDetectsAllForeignForks(string $foreignNamespace): void
    {
        $method = new \ReflectionMethod($this, 'looksLikeProjectNamespace');
        $method->setAccessible(true);

        $this->assertTrue(
            $method->invoke($this, $foreignNamespace),
            "Expected '{$foreignNamespace}' to be detected as a foreign fork namespace",
        );
    }

    #[DataProvider('foreignNamespaceProvider')]
    public function testDetectsInlineForeignNamespaceReferenceInSrcFile(string $foreignNamespace): void
    {
        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$this->projectNamespace};

        class Leaked
        {
            public static function value(): mixed
            {
                return \\{$foreignNamespace}\\Acf\\Fields::option('some_key', '');
            }
        }
        PHP;

        $errors = $this->checkPhpFileNamespaces('src/Leaked.php', $content);

        $this->assertNotEmpty(
            $errors,
            "Expected an inline reference to \\{$foreignNamespace}\\ to be flagged",
        );
        $this->assertStringContainsString($foreignNamespace, $errors[0]);
    }

    public function testAllowsInlineWordpressStarterReferenceInSrcFile(): void
    {
        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$this->projectNamespace};

        class Fine
        {
            public static function value(): mixed
            {
                return \\{$this->projectNamespace}\\Acf\\Fields::option('some_key', '');
            }
        }
        PHP;

        $errors = $this->checkPhpFileNamespaces('src/Fine.php', $content);

        $this->assertEmpty($errors, "Own-namespace inline reference should not be flagged:\n" . implode("\n", $errors));
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
            preg_match_all('/\\\\([A-Za-z][A-Za-z0-9_]*)\\\\([A-Za-z0-9_\\\\]+)/', $content, $matches, PREG_SET_ORDER);

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
            "composer.json autoload should have '" . $this->projectNamespace . "\\' PSR-4 entry",
        );

        // Ensure no other project-like namespaces exist
        foreach (array_keys($autoload) as $namespace) {
            if ($namespace !== $this->projectNamespace . '\\' && $this->looksLikeProjectNamespace(rtrim($namespace, '\\'))) {
                $this->fail("composer.json has unexpected namespace '{$namespace}' (should only have '" . $this->projectNamespace . "\\')");
            }
        }
    }

    /**
     * Extends the fork-leak scan (see checkPhpFileNamespaces()) beyond
     * src/ and templates/ to config/, scripts/, bin/ and tests/, which the
     * other tests here never touch. This file itself is excluded: its own
     * comments and fixture data intentionally reference every foreign fork
     * namespace and would otherwise flag itself.
     */
    public function testConfigScriptsBinAndTestsDoNotLeakForeignNamespaces(): void
    {
        $errors = [];
        $excludedFiles = [
            $this->basePath . '/tests/Unit/NamespaceConsistencyTest.php',
        ];

        foreach (['config', 'scripts', 'bin', 'tests'] as $directory) {
            foreach ($this->getPhpFiles($this->basePath . '/' . $directory) as $file) {
                if (in_array($file, $excludedFiles, true)) {
                    continue;
                }

                $content = file_get_contents($file);
                if ($content === false) {
                    $this->fail("Could not read {$file}");
                }

                $relativePath = str_replace($this->basePath . '/', '', $file);

                preg_match_all('/\\\\([A-Za-z][A-Za-z0-9_]*)\\\\([A-Za-z0-9_\\\\]+)/', $content, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    if (in_array($match[1], $this->knownForeignNamespaces(), true)) {
                        $errors[] = "{$relativePath}: Invalid inline namespace reference '\\{$match[1]}\\{$match[2]}' (should use '\\" . $this->projectNamespace . "\\')";
                    }
                }
            }
        }

        $this->assertEmpty(
            $errors,
            "Namespace leaks found outside src/ and templates/:\n" . implode("\n", $errors),
        );
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
                "functions.php should have namespace '" . $this->projectNamespace . "'",
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
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
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
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
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
            'RuntimeException',
            'LogicException',
            'InvalidArgumentException',
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
            'WebPExpress',  // WebP Express plugin
            // WordPress core globals (auto-imported by php-cs-fixer)
            'WP_Post',
            'WP_Post_Type',
            'WP_Query',
            'WP_Error',
            'WP_Term',
            'WP_User',
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

        // Explicit list first: the PascalCase heuristic below misses forks whose
        // namespace doesn't have a second capital letter (Siera, FIMVertrieb, moenius).
        if (in_array($name, $this->knownForeignNamespaces(), true)) {
            return true;
        }

        // Look for PascalCase names that could be project namespaces
        // Typically these have multiple capital letters (e.g., WordpressStarter, MyProject)
        return preg_match('/^[A-Z][a-z]+[A-Z]/', $name) === 1;
    }

    /**
     * Namespaces of the sibling forks derived from this starter theme.
     * A leaked reference to any of these indicates a propagation-sweep mistake
     * (see the 2026-06-11 incident this test guards against).
     *
     * @return string[]
     */
    private function knownForeignNamespaces(): array
    {
        return [
            'GoldeneStrategie',
            'StiftungsNavigator',
            'moenius',
            'Siera',
            'FIMVertrieb',
        ];
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
