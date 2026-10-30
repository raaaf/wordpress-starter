<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ChildThemeFixture;
use Tests\Support\TestCase;
use WordpressStarter\Support\ChildThemeSettings;
use WordpressStarter\ThemeContext;

/**
 * Child contract v1: the parent reads settings.json of a generated child
 * theme, but only files inside the child folder with allowed extensions.
 */
final class ChildThemeSettingsTest extends TestCase
{
    use ChildThemeFixture;

    protected function tearDown(): void
    {
        $this->removeChildTheme();
        parent::tearDown();
    }

    public function testInactiveWithoutChildTheme(): void
    {
        $this->assertFalse(ThemeContext::isChildActive());
        $this->assertFalse(ChildThemeSettings::active());
        $this->assertNull(ChildThemeSettings::problem());
        $this->assertNull(ChildThemeSettings::tokensCss());
    }

    public function testHandMadeChildWithoutSettingsIsLeftAlone(): void
    {
        $this->makeChildTheme(null);

        $this->assertTrue(ThemeContext::isChildActive());
        $this->assertFalse(ChildThemeSettings::active());
        $this->assertNull(ChildThemeSettings::problem());
    }

    /** @return array<string, array{0: string}> */
    public static function invalidSettings(): array
    {
        return [
            'no json' => ['{contract: 1'],
            'list' => ['[1, 2]'],
            'no contract' => ['{"build_id": "x"}'],
            'contract as string' => ['{"contract": "1"}'],
            'contract zero' => ['{"contract": 0}'],
        ];
    }

    #[DataProvider('invalidSettings')]
    public function testInvalidSettingsAreIgnoredWithProblem(string $raw): void
    {
        $this->makeChildTheme($raw, $this->childAssets());

        $this->assertFalse(ChildThemeSettings::active());
        $this->assertSame(ChildThemeSettings::PROBLEM_INVALID, ChildThemeSettings::problem());
        $this->assertNull(ChildThemeSettings::tokensCss());
    }

    public function testResolvesEveryFileOfContractOne(): void
    {
        $dir = $this->makeChildTheme($this->childSettings(), $this->childAssets());
        $base = 'https://example.com/wp-content/themes/wp-starter-child/assets/';

        $this->assertTrue(ChildThemeSettings::active());
        $this->assertNull(ChildThemeSettings::problem());
        $this->assertSame(1, ChildThemeSettings::contract());
        $this->assertSame(self::CHILD_BUILD_ID, ChildThemeSettings::buildId());
        $this->assertSame(['path' => realpath($dir . '/assets/tokens.css'), 'url' => $base . 'tokens.css'], ChildThemeSettings::tokensCss());
        $this->assertSame($base . 'tokens-editor.css', ChildThemeSettings::editorCss()['url'] ?? null);
        $this->assertSame($base . 'fonts/fonts.css', ChildThemeSettings::fontsCss()['url'] ?? null);
        $this->assertSame($base . 'logo-light.svg', ChildThemeSettings::logo('light')['url'] ?? null);
        $this->assertSame($base . 'logo-dark.png', ChildThemeSettings::logo('dark')['url'] ?? null);
    }

    public function testNewerContractKeepsKnownKeysAndReportsIt(): void
    {
        $this->makeChildTheme($this->childSettings(['contract' => 2, 'future_key' => true]), $this->childAssets());

        $this->assertTrue(ChildThemeSettings::active());
        $this->assertSame(ChildThemeSettings::PROBLEM_NEWER, ChildThemeSettings::problem());
        $this->assertSame(2, ChildThemeSettings::contract());
        $this->assertNotNull(ChildThemeSettings::tokensCss());
    }

    public function testBuildIdMustBeAUuid(): void
    {
        $this->makeChildTheme($this->childSettings(['build_id' => '../../wp_options']), $this->childAssets());

        $this->assertNull(ChildThemeSettings::buildId());

        ChildThemeSettings::reset();
        $this->makeChildTheme($this->childSettings(['build_id' => strtoupper(self::CHILD_BUILD_ID)]), $this->childAssets());
        $this->assertSame(self::CHILD_BUILD_ID, ChildThemeSettings::buildId());
    }

    public function testMissingFilesAndNullKeysAreIgnored(): void
    {
        $this->makeChildTheme($this->childSettings(['fonts_css' => null, 'logo' => ['light' => 'assets/nope.svg', 'dark' => null]]), [
            'assets/tokens.css' => ':root{}',
        ]);

        $this->assertNotNull(ChildThemeSettings::tokensCss());
        $this->assertNull(ChildThemeSettings::editorCss());
        $this->assertNull(ChildThemeSettings::fontsCss());
        $this->assertNull(ChildThemeSettings::logo('light'));
        $this->assertNull(ChildThemeSettings::logo('dark'));
    }

    public function testRejectsPathsOutsideTheChildFolder(): void
    {
        $dir = $this->makeChildTheme($this->childSettings([
            'tokens' => ['css' => '../outside.css', 'editor_css' => '/assets/../../outside.css'],
            'fonts_css' => 'assets/link.css',
        ]), ['assets/.keep' => '']);
        file_put_contents(dirname($dir) . '/outside.css', ':root{}');
        symlink(dirname($dir) . '/outside.css', $dir . '/assets/link.css');

        $this->assertNull(ChildThemeSettings::tokensCss());
        $this->assertNull(ChildThemeSettings::editorCss());
        $this->assertNull(ChildThemeSettings::fontsCss(), 'a symlink out of the child folder is not followed');
    }

    public function testOnlyAllowedExtensionsPerKind(): void
    {
        $this->makeChildTheme($this->childSettings([
            'tokens' => ['css' => 'assets/tokens.php', 'editor_css' => 'assets/logo-light.svg'],
            'logo' => ['light' => 'assets/tokens.css', 'dark' => 'assets/x.woff2'],
        ]), $this->childAssets() + ['assets/tokens.php' => '<?php', 'assets/x.woff2' => 'wOF2']);

        $this->assertNull(ChildThemeSettings::tokensCss());
        $this->assertNull(ChildThemeSettings::editorCss(), 'an SVG is no stylesheet');
        $this->assertNull(ChildThemeSettings::logo('light'), 'a stylesheet is no logo');
        $this->assertNull(ChildThemeSettings::logo('dark'));
    }

    public function testEncodesPathSegmentsInUrls(): void
    {
        $this->makeChildTheme($this->childSettings(['tokens' => ['css' => 'assets/my tokens.css']]), [
            'assets/my tokens.css' => ':root{}',
        ]);

        $this->assertSame(
            'https://example.com/wp-content/themes/wp-starter-child/assets/my%20tokens.css',
            ChildThemeSettings::tokensCss()['url'] ?? null,
        );
    }
}
