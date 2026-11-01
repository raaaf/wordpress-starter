<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Builds a throwaway child theme folder (settings.json plus asset files) and
 * points the stylesheet mocks at it, so get_stylesheet() !== get_template().
 */
trait ChildThemeFixture
{
    private string $childDir = '';

    private string $childBase = '';

    public const CHILD_BUILD_ID = '0f8c2a4e-3b1d-4c5e-9a7f-1e2d3c4b5a69';

    /**
     * @param array<string, mixed>|string|null $settings JSON data, a raw string, or null for no settings.json
     * @param array<string, string> $files relative path => content
     */
    protected function makeChildTheme(array|string|null $settings, array $files = []): string
    {
        $this->childBase = sys_get_temp_dir() . '/wps-child-' . bin2hex(random_bytes(6));
        $this->childDir = $this->childBase . '/wp-starter-child';
        mkdir($this->childDir, 0777, true);

        foreach ($files as $relative => $content) {
            $path = $this->childDir . '/' . $relative;
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, $content);
        }

        if ($settings !== null) {
            file_put_contents(
                $this->childDir . '/settings.json',
                is_string($settings) ? $settings : (string) json_encode($settings),
            );
        }

        $GLOBALS['wp_mock_stylesheet'] = 'wp-starter-child';
        $GLOBALS['wp_mock_stylesheet_directory'] = $this->childDir;
        $GLOBALS['wp_mock_stylesheet_directory_uri'] = 'https://example.com/wp-content/themes/wp-starter-child';

        return $this->childDir;
    }

    /**
     * Contract v1 settings with every key filled.
     *
     * @return array<string, mixed>
     */
    protected function childSettings(array $overrides = []): array
    {
        return array_replace([
            'contract' => 1,
            'build_id' => self::CHILD_BUILD_ID,
            'generator' => '1.0.0',
            'tokens' => ['css' => 'assets/tokens.css', 'editor_css' => 'assets/tokens-editor.css'],
            'fonts_css' => 'assets/fonts/fonts.css',
            'logo' => ['light' => 'assets/logo-light.svg', 'dark' => 'assets/logo-dark.png'],
            'buttons' => 'neutral',
        ], $overrides);
    }

    /** @return array<string, string> */
    protected function childAssets(): array
    {
        return [
            'assets/tokens.css' => ':root{--bg-accent:#123456}',
            'assets/tokens-editor.css' => ':root{--bg-accent:#123456}',
            'assets/fonts/fonts.css' => '@font-face{font-family:Test;src:url(test.woff2)}',
            'assets/fonts/test.woff2' => 'wOF2',
            'assets/logo-light.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><script>alert(1)</script><rect width="10" height="10"/></svg>',
            // Smallest valid PNG (1x1, transparent).
            'assets/logo-dark.png' => (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='),
        ];
    }

    protected function removeChildTheme(): void
    {
        if ($this->childBase === '' || !is_dir($this->childBase)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->childBase, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->childBase);
        $this->childBase = '';
    }
}
