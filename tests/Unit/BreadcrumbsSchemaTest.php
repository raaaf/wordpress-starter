<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\TestCase;

/**
 * Tests for the breadcrumb JSON-LD schema output.
 */
final class BreadcrumbsSchemaTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $GLOBALS['wp_mock_is_front_page'],
            $GLOBALS['wp_mock_is_singular'],
            $GLOBALS['wp_mock_is_page'],
            $GLOBALS['wp_mock_titles'],
        );
        parent::tearDown();
    }

    public function testJsonLdEscapesScriptTagsInTitle(): void
    {
        $GLOBALS['wp_mock_is_front_page'] = false;
        $GLOBALS['wp_mock_is_singular'] = false;
        $GLOBALS['wp_mock_is_page'] = false;
        $GLOBALS['wp_mock_titles'][0] = 'Angebot & Nachfrage </script><script>alert(1)</script>';

        $output = $this->renderBreadcrumbs();

        $ldJsonStart = strpos($output, '<script type="application/ld+json"');
        $this->assertNotFalse($ldJsonStart, 'ld+json script block not found');
        $ldJsonEnd = strpos($output, '</script>', $ldJsonStart);
        $ldJsonBlock = substr($output, $ldJsonStart, $ldJsonEnd - $ldJsonStart);

        $this->assertStringNotContainsString('</script><script>', $ldJsonBlock);
        // JSON_HEX_TAG (set in breadcrumbs.blade.php) hex-escapes < and >
        // to \u003C/\u003E; JSON_UNESCAPED_SLASHES leaves the slash literal,
        // so a dropped flag would fail this assertion.
        $this->assertStringContainsString('\u003C/script\u003E\u003Cscript\u003E', $ldJsonBlock);
        $this->assertStringContainsString('</script>', $output);

        // JSON_HEX_AMP (also set in breadcrumbs.blade.php) hex-escapes "&" to
        // \u0026; a raw "&" inside the ld+json block would mean the flag was
        // dropped.
        $this->assertStringContainsString('\u0026', $ldJsonBlock);
        // Forbidding every "&" in the whole block would also fail on an
        // ordinary URL query string; only the hostile payload's own "&" is
        // the regression to catch, so assert that raw substring specifically,
        // not the character in general.
        $this->assertStringNotContainsString('Angebot & Nachfrage', $ldJsonBlock);
    }

    private function renderBreadcrumbs(): string
    {
        return $this->renderTemplate('partials.breadcrumbs');
    }
}
