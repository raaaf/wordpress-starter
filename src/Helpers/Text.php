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

        return wp_kses(str_replace('[br]', '<br>', $text), ['br' => []]);
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
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Entwicklerhinweis, kein Frontend-Output.
            trigger_error(
                sprintf('Bild #%d hat keinen Alt-Text und keinen Kontext.', (int) $attachmentId),
                E_USER_WARNING
            );
        }

        return '';
    }
}
