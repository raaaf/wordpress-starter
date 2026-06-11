<?php

declare(strict_types=1);

namespace WordpressStarter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use WordpressStarter\Application;

/**
 * Render smoke test for all Blade templates.
 *
 * Renders every templates/**\/*.blade.php with the WordPress mocks from the
 * test bootstrap. Catches the two failure classes that broke production on
 * 2026-06-11 and that no other check covers:
 *
 * 1. References to classes that do not exist in this theme (e.g. an
 *    unconverted namespace from another theme in the fork family).
 * 2. Templates corrupted by mass edits (e.g. a stripped Blade directive
 *    leaving a bare ($var) echoed as literal text).
 *
 * Missing WordPress core functions/classes in the mock environment are
 * tolerated; everything else that throws fails the test.
 */
final class TemplateRenderTest extends TestCase
{
    /** Namespaces of the other themes in the fork family — must never leak in. */
    private const FOREIGN_NAMESPACES = [
        'ForeignThemeA\\',
        'ForeignThemeB\\',
        'foreignthemed\\',
        'ForeignThemeE\\',
        'ForeignThemeC\\',
    ];

    /** Default view data so standalone renders of partials do not fatal on required params. */
    private const VIEW_DATA = [
        'layoutCounters' => [],
        'items' => [],
        'idPrefix' => 'test',
        'pagination' => '',
        'ariaLabel' => 'test',
        'navClass' => '',
        'title' => 'test',
        'text' => 'test',
        'svgPath' => '',
    ];

    public function testAllTemplatesRenderWithoutRealErrors(): void
    {
        $app = Application::getInstance();
        $app->boot();

        $factory = blade();
        $factory->getFinder()->addLocation($this->templatesDir());

        $failures = [];
        $rendered = 0;
        $tolerated = 0;
        $viewData = self::VIEW_DATA + ['slot' => new \Illuminate\View\ComponentSlot()];

        foreach ($this->templateFiles() as $path) {
            $view = $this->viewName($path);

            // Standalone component renders produce undefined-variable warnings;
            // only Throwables are relevant here.
            $level = error_reporting(E_ERROR | E_PARSE);

            try {
                $factory->make($view, $viewData)->render();
                $rendered++;
            } catch (Throwable $e) {
                $root = $e;
                while ($root->getPrevious() !== null) {
                    $root = $root->getPrevious();
                }

                if ($this->isMockGap($root->getMessage())) {
                    $tolerated++;
                } else {
                    $failures[] = $path . ' — ' . $root->getMessage();
                }
            } finally {
                error_reporting($level);
                $factory->flushState();
            }
        }

        $this->assertGreaterThan(0, $rendered, 'No template rendered at all — harness broken?');
        $this->assertSame(
            [],
            $failures,
            "Templates failed to render with real errors:\n" . implode("\n", $failures),
        );
    }

    public function testNoForeignThemeNamespaceReferences(): void
    {
        $offenders = [];

        foreach ($this->templateFiles() as $path) {
            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }

            foreach (self::FOREIGN_NAMESPACES as $namespace) {
                if (str_contains($content, $namespace)) {
                    $offenders[] = $path . ' — references ' . rtrim($namespace, '\\');
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Templates reference a foreign theme namespace (unconverted propagation):\n" . implode("\n", $offenders),
        );
    }

    public function testNoBareOutputExpressions(): void
    {
        // A bare ($var) in output position is the residue of a stripped Blade
        // directive (e.g. @kses($var) mangled by a faulty mass edit).
        $pattern = '/(^\s*|>)\(\$\w+(\[.{1,3}\w+.{1,3}\])?\)(<|\s*$)/m';
        $offenders = [];

        foreach ($this->templateFiles() as $path) {
            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }

            if (preg_match($pattern, $content, $match)) {
                $offenders[] = $path . ' — bare output expression: ' . trim($match[0]);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Templates contain bare output expressions (stripped directive?):\n" . implode("\n", $offenders),
        );
    }

    /**
     * WordPress core functions/classes are not fully mocked in the test
     * bootstrap; their absence is not a theme bug.
     */
    private function isMockGap(string $message): bool
    {
        // Any undefined function is an unmocked WordPress core function:
        // PHP falls back from the namespace to the global scope, so the error
        // only occurs when the global is missing too. Genuinely misspelled
        // theme functions are phpstan's job, not this test's.
        if (str_contains($message, 'Call to undefined function ')) {
            return true;
        }

        if (preg_match('/Class "(WP_[A-Za-z_]+|WP\\\\[A-Za-z_\\\\]+)" not found/', $message)) {
            return true;
        }

        return false;
    }

    /** @return list<string> */
    private function templateFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->templatesDir(), RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if (str_ends_with($path, '.blade.php')) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    private function viewName(string $path): string
    {
        $relative = substr($path, strlen($this->templatesDir()) + 1);

        return str_replace(['/', '.blade.php'], ['.', ''], $relative);
    }

    private function templatesDir(): string
    {
        return dirname(__DIR__, 2) . '/templates';
    }
}
