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
}
