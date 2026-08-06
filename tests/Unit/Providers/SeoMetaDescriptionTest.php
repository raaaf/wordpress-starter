<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use ReflectionMethod;
use Tests\Support\TestCase;
use WordpressStarter\Providers\SeoServiceProvider;

/**
 * Tests for the meta description fallback.
 *
 * The theme emits <meta name="description"> only when no SEO plugin does, and
 * has to build it itself. The excerpt is not a sufficient source here: page
 * content lives in ACF Flexible Content, so post_content is usually empty and
 * WordPress derives no excerpt from it.
 *
 * The password case is the one that matters most. ACF sections render outside
 * the_content(), so they are readable while the password gate is up — deriving
 * a description from them would publish protected content in a tag every
 * crawler reads.
 */
final class SeoMetaDescriptionTest extends TestCase
{
    private SeoServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new SeoServiceProvider();

        $GLOBALS['wp_mock_is_singular'] = true;
        $GLOBALS['wp_mock_password_required'] = false;
        $GLOBALS['wp_mock_excerpt'] = '';
        $GLOBALS['wp_mock_fields'] = [];
        $GLOBALS['wp_mock_bloginfo'] = ['description' => 'Die Standard-Beschreibung der Website'];
    }

    protected function tearDown(): void
    {
        foreach (['wp_mock_is_singular', 'wp_mock_password_required', 'wp_mock_excerpt', 'wp_mock_is_search', 'wp_mock_bloginfo'] as $key) {
            unset($GLOBALS[$key]);
        }

        $GLOBALS['wp_mock_fields'] = [];

        parent::tearDown();
    }

    private function call(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(SeoServiceProvider::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($this->provider, ...$args);
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     */
    private function withSections(array $sections): void
    {
        $GLOBALS['wp_mock_fields']['page_sections'] = $sections;
    }

    // =====================================================================
    // Password protection — the reason this needs a guard at all
    // =====================================================================

    public function testProtectedPostFallsBackToTaglineInsteadOfItsSections(): void
    {
        $GLOBALS['wp_mock_password_required'] = true;
        $this->withSections([
            ['acf_fc_layout' => 'one_column', 'content' => 'Interne Zahlen für das laufende Quartal.'],
        ]);

        $description = $this->call('getMetaDescription');

        $this->assertStringNotContainsString('Interne Zahlen', $description);
        $this->assertSame('Die Standard-Beschreibung der Website', $description);
    }

    public function testProtectedPostDoesNotLeakTheExcerptPlaceholder(): void
    {
        // WordPress returns its own "no excerpt, this post is protected" text
        // here, which would be a nonsensical description.
        $GLOBALS['wp_mock_password_required'] = true;
        $GLOBALS['wp_mock_excerpt'] = 'Es gibt keinen Textauszug, da dies ein geschützter Beitrag ist.';

        $description = $this->call('getMetaDescription');

        $this->assertStringNotContainsString('geschützter Beitrag', $description);
    }

    // =====================================================================
    // Source selection
    // =====================================================================

    public function testExcerptWinsWhenPresent(): void
    {
        $GLOBALS['wp_mock_excerpt'] = 'Ein von der Redaktion gepflegter Textauszug.';
        $this->withSections([
            ['acf_fc_layout' => 'one_column', 'content' => 'Text aus einer Sektion.'],
        ]);

        $this->assertSame('Ein von der Redaktion gepflegter Textauszug.', $this->call('getMetaDescription'));
    }

    public function testSectionsAreUsedWhenTheExcerptIsEmpty(): void
    {
        // The normal case in this theme: content is in ACF, post_content empty.
        $this->withSections([
            ['acf_fc_layout' => 'hero', 'copy' => 'Wir begleiten Stiftungen bei der Mittelverwendung.'],
        ]);

        $this->assertSame('Wir begleiten Stiftungen bei der Mittelverwendung.', $this->call('getMetaDescription'));
    }

    public function testTaglineIsTheLastResort(): void
    {
        $this->withSections([]);

        $this->assertSame('Die Standard-Beschreibung der Website', $this->call('getMetaDescription'));
    }

    // =====================================================================
    // Section extraction
    // =====================================================================

    public function testOnlyProseFieldsAreCollected(): void
    {
        // Headlines, labels and button captions make a poor snippet, so the
        // extraction ignores them even though they are the first strings in
        // the row.
        $this->withSections([
            [
                'acf_fc_layout' => 'hero',
                'title' => 'Überschrift',
                'label' => 'Neu',
                'button' => ['title' => 'Jetzt anfragen'],
                'copy' => 'Der eigentliche Fließtext der Sektion.',
            ],
        ]);

        $description = $this->call('descriptionFromSections', 1);

        $this->assertSame('Der eigentliche Fließtext der Sektion.', $description);
    }

    public function testSectionsAreReadInPageOrder(): void
    {
        $this->withSections([
            ['acf_fc_layout' => 'hero', 'copy' => 'Erste Sektion.'],
            ['acf_fc_layout' => 'one_column', 'content' => 'Zweite Sektion.'],
        ]);

        $this->assertStringStartsWith('Erste Sektion.', $this->call('descriptionFromSections', 1));
    }

    public function testHtmlIsStrippedFromSectionContent(): void
    {
        $this->withSections([
            ['acf_fc_layout' => 'one_column', 'content' => '<p>Ein <strong>wichtiger</strong> Satz.</p>'],
        ]);

        $this->assertSame('Ein wichtiger Satz.', $this->call('getMetaDescription'));
    }

    public function testNonArraySectionsAreIgnored(): void
    {
        $GLOBALS['wp_mock_fields']['page_sections'] = 'kein Array';

        $this->assertSame('', $this->call('descriptionFromSections', 1));
    }

    // =====================================================================
    // Normalization
    // =====================================================================

    public function testWhitespaceIsCollapsed(): void
    {
        $normalized = $this->call('normalizeDescription', "Ein  Satz\n\tmit   viel Weißraum.");

        $this->assertSame('Ein Satz mit viel Weißraum.', $normalized);
    }

    public function testLongTextIsCutOnAWordBoundary(): void
    {
        $text = str_repeat('Barrierefreiheit ist wichtig. ', 20);

        $normalized = $this->call('normalizeDescription', $text);

        $this->assertLessThanOrEqual(156, mb_strlen($normalized), 'must respect the length cap');
        $this->assertStringEndsWith('…', $normalized);

        // The cut must land on a space in the original, not inside a word.
        // Asserting "does not end in letters" would pass for any complete
        // word too, and so would never catch a mid-word cut.
        $body = rtrim(mb_substr($normalized, 0, -1));
        $original = trim($text);

        $this->assertStringStartsWith($body, $original, 'the kept text must be a prefix of the original');
        $this->assertSame(
            ' ',
            mb_substr($original, mb_strlen($body), 1),
            'the character after the cut must be a space, otherwise a word was split',
        );
    }

    public function testShortTextIsLeftAlone(): void
    {
        $this->assertSame('Kurz und knapp.', $this->call('normalizeDescription', 'Kurz und knapp.'));
    }

    public function testEmptyInputStaysEmpty(): void
    {
        $this->assertSame('', $this->call('normalizeDescription', '   '));
    }
}
