<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Support\TestCase;
use WordpressStarter\Providers\AcfServiceProvider;

/**
 * Textarea-Felder werden beim Speichern von allen Tags befreit, mit einer
 * Ausnahme: die Tabellenzelle verspricht "HTML erlaubt" und gibt ihren Inhalt
 * beim Rendern durch wp_kses_post aus. Ohne die Ausnahme ging jedes HTML in
 * einer Zelle beim Speichern verloren.
 */
final class TextareaSanitizeTest extends TestCase
{
    private const HTML = '<strong>fett</strong> <a href="https://example.com">Link</a><br>';

    public function testTabellenzelleBehaeltErlaubtesHtml(): void
    {
        $ergebnis = AcfServiceProvider::sanitizeTextarea(self::HTML, 1, ['key' => 'field_flex_table_cell_content']);

        $this->assertStringContainsString('<strong>', $ergebnis);
        $this->assertStringContainsString('<a href=', $ergebnis);
        $this->assertStringContainsString('<br', $ergebnis);
    }

    public function testTabellenzelleEntferntFormularelemente(): void
    {
        // Produktionsfilter registrieren (TestCase::setUp/tearDown setzt die Hooks zurueck).
        add_filter('wp_kses_allowed_html', [AcfServiceProvider::class, 'allowFormControlTags'], 20, 2);

        $eingabe = self::HTML . '<form action="https://evil.example"><input type="password" name="p"></form>';

        // Sanity: wp_kses_post laesst Formulare mit dem Filter durch, das Entfernen kommt also von sanitizeTextarea.
        $this->assertStringContainsString('<form', wp_kses_post($eingabe));

        $ergebnis = AcfServiceProvider::sanitizeTextarea($eingabe, 1, ['key' => 'field_flex_table_cell_content']);

        $this->assertStringContainsString('<strong>', $ergebnis);
        $this->assertStringContainsString('<a href=', $ergebnis);
        $this->assertStringNotContainsString('<form', $ergebnis);
        $this->assertStringNotContainsString('<input', $ergebnis);
    }

    public function testAndereTextareaWirdVonTagsBefreit(): void
    {
        $ergebnis = AcfServiceProvider::sanitizeTextarea(self::HTML, 1, ['key' => 'field_flex_alert_text']);

        $this->assertStringNotContainsString('<', $ergebnis);
    }
}
