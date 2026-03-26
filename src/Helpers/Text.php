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
}
