<?php

declare(strict_types=1);

namespace WordpressStarter\Helpers;

class Text
{
    /**
     * Replace [br] placeholders with <br> tags.
     * Only allows <br> — all other HTML is stripped for security.
     */
    public static function lineBreaks(?string $text): string
    {
        if (!$text) {
            return '';
        }

        return self::typography(wp_kses(str_replace('[br]', '<br>', $text), ['br' => []]));
    }

    /**
     * File type label of a download URL: the uppercase extension of the URL path
     * when it is a known document type, otherwise an empty string.
     */
    public static function fileType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            return '';
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['pdf', 'docx', 'doc', 'xlsx', 'jpg', 'png', 'zip'], true)
            ? strtoupper($extension)
            : '';
    }

    /**
     * German typography glue for text nodes: a number never ends a line before its
     * unit, "p. a." and "z. B." stay together, % takes a no-break space and
     * " - " between words becomes an en dash. Tags and attributes are left alone,
     * so URLs and class names keep their hyphens and spaces. The contents of
     * script, style, pre, code and textarea are left alone too.
     *
     * Also hooked on acf/format_value, which can pass null or false: anything
     * that is not a string is returned unchanged.
     */
    public static function typography(mixed $html): mixed
    {
        if (!is_string($html)) {
            return $html;
        }

        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }

        // Name of the raw element (script, code, ...) whose contents are being skipped.
        $raw = null;

        foreach ($parts as $i => $part) {
            // Odd indexes are the captured tags.
            if ($i % 2 === 1) {
                if ($raw === null && preg_match('/^<(script|style|pre|code|textarea)\b/i', $part, $m) === 1 && !str_ends_with($part, '/>')) {
                    $raw = strtolower($m[1]);
                } elseif ($raw !== null && preg_match('/^<\/' . $raw . '\b/i', $part) === 1) {
                    $raw = null;
                }
                continue;
            }
            if ($part === '' || $raw !== null) {
                continue;
            }
            $part = preg_replace('/(?<=\d) (?=(?:Jahren|Jahre|kWh|MWp|kg|EUR|€)(?![\p{L}]))/u', "\u{00A0}", $part) ?? $part;
            $part = preg_replace('/\bp\. a\./u', "p.\u{00A0}a.", $part) ?? $part;
            $part = preg_replace('/\bz\. B\./u', "z.\u{00A0}B.", $part) ?? $part;
            $part = preg_replace('/(?<=\d) (?=[%‰])/u', "\u{00A0}", $part) ?? $part;
            $parts[$i] = str_replace(' - ', ' – ', $part);
        }

        return implode('', $parts);
    }

    /**
     * Plain text of an HTML snippet for attributes such as aria-label.
     * A <br> becomes a space first, or the words around it would be glued together.
     */
    public static function plain(?string $html): string
    {
        if (!$html) {
            return '';
        }

        $spaced = (string) preg_replace('/<br\s*\/?>/i', ' ', $html);
        $plain = wp_strip_all_tags($spaced);

        $collapsed = (string) preg_replace('/\s+/', ' ', $plain);

        return trim($collapsed);
    }

    /**
     * Build a dialable tel: href from a formatted phone number.
     *
     * The German trunk prefix is written as "(0)" between country code and area
     * code. Stripping only the punctuation would keep that zero and produce an
     * undialable number, so it is removed before the remaining formatting.
     */
    public static function telHref(?string $phone): string
    {
        if (!$phone) {
            return '';
        }

        $normalized = preg_replace('/\(\s*0\s*\)/', '', $phone) ?? $phone;

        return preg_replace('/[^0-9+]/', '', $normalized) ?? '';
    }

    /**
     * Alt-Text eines Bildes aufloesen.
     *
     * Reihenfolge: gepflegter Alt-Text der Mediathek, dann ein Kontexttext aus
     * dem Layout (Kartenlabel, Personenname, Beitragstitel), dann die
     * Bildunterschrift. Der Dateiname wird bewusst nie genommen, "placeholder-1"
     * ist fuer Screenreader schlechter als gar nichts.
     *
     * Gibt es keinen davon, bleibt der Alt-Text leer: das Bild gilt dann als
     * dekorativ. Mit WP_DEBUG meldet der Aufruf die Luecke, damit sie in der
     * Entwicklung auffaellt statt still live zu gehen.
     *
     * Am 2026-08-10 hatten 60 von 87 Bildern der Styleguide-Seite alt="".
     */
    public static function imageAlt(int $attachmentId, string $context = ''): string
    {
        if ($attachmentId <= 0) {
            return '';
        }

        $alt = trim( (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true));
        if ($alt !== '') {
            return $alt;
        }

        // Kontext auf eine Zeile kuerzen. Ein Alt-Text ist eine Beschriftung,
        // kein Absatz; Screenreader lesen ihn am Stueck vor.
        $context = trim(wp_strip_all_tags($context));
        if ($context !== '') {
            $context = trim( (string) preg_replace('/\s+/', ' ', $context));

            return mb_strlen($context) > 100 ? mb_substr($context, 0, 97) . '…' : $context;
        }

        $caption = trim( (string) wp_get_attachment_caption($attachmentId));
        if ($caption !== '') {
            return $caption;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Entwicklerhinweis, kein Frontend-Output.
            trigger_error(
                sprintf('Bild #%d hat keinen Alt-Text und keinen Kontext.', (int) $attachmentId),
                E_USER_WARNING
            );
        }

        return '';
    }
}
