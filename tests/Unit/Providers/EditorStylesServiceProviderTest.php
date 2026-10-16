<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\EditorStylesServiceProvider;

/**
 * Tests for the TinyMCE icon-picker output, which builds icon-button HTML
 * from an icon name at runtime in the browser. Two XSS vectors were fixed:
 * - the picker JS used inline onmouseover/onmouseout HTML attributes built
 *   from an escaped-but-still-attacker-influenced icon name, now replaced
 *   with addEventListener() so no attribute string is built at all.
 * - the icon list handed to the browser via wp_add_inline_script() relies on
 *   wp_json_encode()'s default forward-slash escaping to stop an icon name
 *   containing "</script>" from closing the surrounding <script> tag early.
 */
final class EditorStylesServiceProviderTest extends TestCase
{
    private EditorStylesServiceProvider $provider;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new EditorStylesServiceProvider();
    }

    protected function tearDown(): void
    {
        if (isset($this->tempDir)) {
            $this->cleanupTempDir($this->tempDir);
        }
        parent::tearDown();
    }

    /**
     * The picker builds every node with createElement/textContent, so an icon
     * name or label never passes through an HTML parser. Inline handler
     * attributes or innerHTML would reopen that path.
     */
    public function testIconPickerPluginJsBuildsNoHtmlStrings(): void
    {
        $js = $this->invokeMethod($this->provider, 'getIconPickerPluginJs');

        $this->assertStringNotContainsString('onmouseover', $js);
        $this->assertStringNotContainsString('innerHTML', $js);
        $this->assertStringNotContainsString('insertAdjacentHTML', $js);
    }

    /**
     * Keyboard users must get the same highlight as mouse users.
     */
    public function testIconPickerPluginJsStylesFocusLikeHover(): void
    {
        $js = $this->invokeMethod($this->provider, 'getIconPickerPluginJs');

        $this->assertStringContainsString('.tip-tile:hover{', $js);
        $this->assertStringContainsString('.tip-tile:focus-visible{', $js);
    }

    /**
     * The icon name builds the <img src>, where it must be URL-encoded, and
     * the `[icon name="..."]` shortcode, where a literal `"` would close the
     * attribute early and let extra shortcode attributes be injected.
     */
    public function testIconPickerPluginJsEncodesImgSrcAndStripsShortcodeQuotes(): void
    {
        $js = $this->invokeMethod($this->provider, 'getIconPickerPluginJs');

        $this->assertStringContainsString('encodeURIComponent(icon.slug)', $js);
        $this->assertStringContainsString("slug.replace(/\"/g, '')", $js);
    }

    public function testLocalizedIconDataEscapesScriptClosingTagInIconName(): void
    {
        $maliciousIcon = '<script>alert(1)</script>"';

        $this->tempDir = sys_get_temp_dir() . '/wp-starter-test-editor-' . bin2hex(random_bytes(4));
        mkdir($this->tempDir . '/config', 0o700, true);
        file_put_contents(
            $this->tempDir . '/config/icons.json',
            (string) wp_json_encode(['icons' => [$maliciousIcon => ['label' => 'Test']]]),
        );
        $this->setTemplateDirectory($this->tempDir);

        $this->invokeMethod($this->provider, 'localizeIconData');

        $callback = $this->lastRegisteredCallback('admin_enqueue_scripts');
        $callback('post.php');

        $script = $GLOBALS['wp_mock_inline_scripts']['editor']['before'][0];

        // The raw closing tag must never appear unescaped, or the icon name
        // could terminate the surrounding <script> block early.
        $this->assertStringNotContainsString('</script>', $script);
        $this->assertStringContainsString('<\/script>', $script);

        // Escaping must not corrupt the payload: extracting and decoding the
        // JSON must yield the original icon name back unchanged.
        $this->assertMatchesRegularExpression('/^window\.themeIconsData = (\{.*\});$/s', $script);
        preg_match('/^window\.themeIconsData = (\{.*\});$/s', $script, $matches);
        $decoded = json_decode($matches[1], true);

        $this->assertSame([$maliciousIcon], $decoded['icons']);
        $this->assertSame([$maliciousIcon => 'Test'], $decoded['labels']);
    }

    /**
     * Returns the callback most recently registered for $hook via add_action().
     */
    private function lastRegisteredCallback(string $hook): callable
    {
        $registrations = $GLOBALS['wp_mock_hooks']['actions'][$hook] ?? [];
        $this->assertNotEmpty($registrations, "No callback registered for '{$hook}'");

        return $registrations[array_key_last($registrations)]['callback'];
    }
}
